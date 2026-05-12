<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/LeadService.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$business = Settings::effectiveBusiness($businessId) ?? BusinessProfile::current();
$id = (int) ($_GET['id'] ?? 0);
$tab = trim((string) ($_GET['tab'] ?? 'overview'));
$pdo = Database::pdo();

$stmt = $pdo->prepare(
    'SELECT c.*, t.name AS template_name, t.subject, t.preheader, t.body, t.body_html, t.body_text,
            a.email AS created_by_email, bp.brand_name, bp.business_name
     FROM campaigns c
     JOIN business_profiles bp ON bp.id = c.business_profile_id
     LEFT JOIN email_templates t ON t.id = c.template_id AND t.business_profile_id = c.business_profile_id
     LEFT JOIN admins a ON a.id = c.created_by
     WHERE c.id = ? AND c.business_profile_id = ?
     LIMIT 1'
);
$stmt->execute([$id, $businessId]);
$campaign = $stmt->fetch();
if (!$campaign) {
    http_response_code(404);
    exit('Campaign not found.');
}

$countStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_queued,
        SUM(status = "sent") AS sent_count,
        SUM(status = "pending") AS pending_count,
        SUM(status = "failed") AS failed_count,
        SUM(status = "skipped") AS skipped_count,
        SUM(status = "cancelled") AS cancelled_count
     FROM email_queue
     WHERE campaign_id = ? AND business_profile_id = ?'
);
$countStmt->execute([$id, $businessId]);
$counts = $countStmt->fetch() ?: [];
$totalQueued = (int) ($counts['total_queued'] ?? 0);
$sentCount = (int) ($counts['sent_count'] ?? 0);
$pendingCount = (int) ($counts['pending_count'] ?? 0);
$failedCount = (int) ($counts['failed_count'] ?? 0);
$skippedQueueCount = (int) ($counts['skipped_count'] ?? 0);
$cancelledCount = (int) ($counts['cancelled_count'] ?? 0);
$progress = $campaign['eligible_recipients'] > 0 ? (int) round(($sentCount / max(1, (int) $campaign['eligible_recipients'])) * 100) : 0;

$queueStmt = $pdo->prepare(
    'SELECT q.*, l.company_name, l.contact_name, l.email, l.deleted_at, el.provider_reference
     FROM email_queue q
     LEFT JOIN leads l ON l.id = q.lead_id
     LEFT JOIN (
       SELECT queue_id, MAX(id) AS max_id
       FROM email_logs
       WHERE business_profile_id = ?
       GROUP BY queue_id
     ) latest ON latest.queue_id = q.id
     LEFT JOIN email_logs el ON el.id = latest.max_id
     WHERE q.business_profile_id = ? AND q.campaign_id = ?
     ORDER BY q.scheduled_at ASC, q.id ASC'
);
$queueStmt->execute([$businessId, $businessId, $id]);
$recipientRows = $queueStmt->fetchAll();

$skipStmt = $pdo->prepare('SELECT * FROM campaign_skips WHERE business_profile_id = ? AND campaign_id = ? ORDER BY id ASC');
$skipStmt->execute([$businessId, $id]);
$skippedRows = $skipStmt->fetchAll();

$sampleRow = $recipientRows[0] ?? null;
$preview = null;
if ($sampleRow) {
    $preview = [
        'subject' => (string) ($sampleRow['rendered_subject'] ?? ''),
        'preheader' => (string) ($sampleRow['rendered_preheader'] ?? ''),
        'html' => (string) ($sampleRow['rendered_html'] ?? ''),
        'text' => (string) ($sampleRow['rendered_text'] ?? ''),
    ];
}
if ($preview === null || ($preview['subject'] === '' && !empty($campaign['template_name']))) {
    $leadForRender = [
        'business_profile_id' => $businessId,
        'contact_name' => $sampleRow['contact_name'] ?? 'Sample Contact',
        'company_name' => $sampleRow['company_name'] ?? 'Sample Company',
        'email' => $sampleRow['email'] ?? 'sample@example.com',
        'category' => 'Other',
        'unsubscribe_token' => '',
    ];
    $preview = Mailer::renderTemplate($campaign, $leadForRender, $business);
}

$tabs = [
    'overview' => 'Overview',
    'recipients' => 'Recipients',
    'skipped' => 'Skipped Leads',
    'failed' => 'Failed Emails',
    'preview' => 'Email Preview',
    'activity' => 'Activity / Logs',
];

render_header('Campaign Detail');
?>
<div class="d-flex gap-2 mb-3">
  <a class="btn btn-outline-secondary" href="/campaigns/index.php">Back to campaigns</a>
  <a class="btn btn-outline-primary" href="/queue/index.php?campaign_name=<?= urlencode((string) $campaign['name']) ?>">Open queue filter</a>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-lg-8">
        <h2 class="h4 mb-1"><?= e($campaign['name']) ?></h2>
        <div class="text-muted"><?= e((string) ($campaign['description'] ?: 'No description.')) ?></div>
      </div>
      <div class="col-lg-4 text-lg-end">
        <div class="small text-muted">Business profile</div>
        <div class="fw-semibold"><?= e($campaign['brand_name']) ?> (#<?= e($businessId) ?>)</div>
      </div>
    </div>
    <div class="row g-3 mt-2">
      <div class="col-md-3"><div class="small text-muted">Audience type</div><div><?= e($campaign['audience_type']) ?></div></div>
      <div class="col-md-3"><div class="small text-muted">Template</div><div><?= e($campaign['template_name'] ?: 'Missing template') ?></div></div>
      <div class="col-md-3"><div class="small text-muted">Scheduled</div><div><?= e(format_app_datetime($campaign['scheduled_at'], $businessId)) ?></div></div>
      <div class="col-md-3"><div class="small text-muted">Created by</div><div><?= e((string) ($campaign['created_by_email'] ?: 'Unknown')) ?></div></div>
      <div class="col-md-3"><div class="small text-muted">Created</div><div><?= e(format_app_datetime($campaign['created_at'], $businessId)) ?></div></div>
      <div class="col-md-3"><div class="small text-muted">Status</div><div><?= badge_status((string) $campaign['status']) ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      'Total queued' => $totalQueued,
      'Sent' => $sentCount,
      'Pending' => $pendingCount,
      'Failed' => $failedCount,
      'Skipped' => $campaign['skipped_recipients'],
      'Cancelled' => $cancelledCount,
  ] as $label => $value): ?>
    <div class="col-sm-6 col-lg-2">
      <div class="card shadow-sm stat-card"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fs-4 fw-semibold"><?= e((string) $value) ?></div></div></div>
    </div>
  <?php endforeach; ?>
  <div class="col-sm-6 col-lg-2">
    <div class="card shadow-sm stat-card"><div class="card-body"><div class="small text-muted">Progress</div><div class="fs-4 fw-semibold"><?= e((string) $progress) ?>%</div></div></div>
  </div>
</div>

<ul class="nav nav-tabs mb-3">
  <?php foreach ($tabs as $key => $label): ?>
    <li class="nav-item"><a class="nav-link<?= $tab === $key ? ' active' : '' ?>" href="/campaigns/view.php?id=<?= e($id) ?>&tab=<?= e($key) ?>"><?= e($label) ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if ($tab === 'overview'): ?>
  <div class="card shadow-sm">
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-md-3">Total recipients</dt><dd class="col-md-9"><?= e($campaign['total_recipients']) ?></dd>
        <dt class="col-md-3">Eligible recipients</dt><dd class="col-md-9"><?= e($campaign['eligible_recipients']) ?></dd>
        <dt class="col-md-3">Skipped recipients</dt><dd class="col-md-9"><?= e($campaign['skipped_recipients']) ?></dd>
        <dt class="col-md-3">Pending emails</dt><dd class="col-md-9"><?= e((string) $pendingCount) ?></dd>
        <dt class="col-md-3">Sent emails</dt><dd class="col-md-9"><?= e((string) $sentCount) ?></dd>
        <dt class="col-md-3">Failed emails</dt><dd class="col-md-9"><?= e((string) $failedCount) ?></dd>
        <dt class="col-md-3">Cancelled emails</dt><dd class="col-md-9"><?= e((string) $cancelledCount) ?></dd>
        <dt class="col-md-3">Skipped queue rows</dt><dd class="col-md-9"><?= e((string) $skippedQueueCount) ?></dd>
        <dt class="col-md-3">Bounced / unsubscribed</dt><dd class="col-md-9">Shown through skipped and failed reasons where applicable.</dd>
        <dt class="col-md-3">Tracking</dt><dd class="col-md-9">Open and click tracking are not enabled yet.</dd>
      </dl>
    </div>
  </div>
<?php elseif ($tab === 'recipients'): ?>
  <div class="card shadow-sm"><div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>Lead</th><th>Company</th><th>Email</th><th>Status</th><th>Scheduled</th><th>Sent</th><th>Provider ref</th><th>Error</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($recipientRows as $row): ?>
      <tr>
        <td><?= e(LeadService::displayName($row)) ?></td>
        <td><?= e($row['company_name'] ?: 'Deleted lead') ?></td>
        <td><?= e($row['email'] ?: 'Deleted lead') ?></td>
        <td><?= badge_status((string) $row['status']) ?></td>
        <td><?= e(format_app_datetime($row['scheduled_at'], $businessId)) ?></td>
        <td><?= e(format_app_datetime($row['sent_at'], $businessId)) ?></td>
        <td><?= e((string) ($row['provider_reference'] ?? '')) ?></td>
        <td class="small"><?= e((string) $row['error_message']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/queue/view.php?id=<?= e($row['id']) ?>">Queue detail</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div>
<?php elseif ($tab === 'skipped'): ?>
  <div class="card shadow-sm"><div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>Lead</th><th>Company</th><th>Email</th><th>Skipped reason</th></tr></thead>
    <tbody>
    <?php foreach ($skippedRows as $row): ?>
      <tr>
        <td><?= e($row['lead_contact_name'] ?: 'Deleted lead') ?></td>
        <td><?= e($row['lead_company_name'] ?: 'Deleted lead') ?></td>
        <td><?= e($row['lead_email'] ?: 'Deleted lead') ?></td>
        <td><?= e(skip_reason_label((string) $row['reason'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div>
<?php elseif ($tab === 'failed'): ?>
  <div class="card shadow-sm"><div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>Lead</th><th>Company</th><th>Email</th><th>Error</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($recipientRows as $row): ?>
      <?php if ($row['status'] !== 'failed') {
          continue;
      } ?>
      <tr>
        <td><?= e(LeadService::displayName($row)) ?></td>
        <td><?= e($row['company_name'] ?: 'Deleted lead') ?></td>
        <td><?= e($row['email'] ?: 'Deleted lead') ?></td>
        <td class="small"><?= e((string) $row['error_message']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/queue/view.php?id=<?= e($row['id']) ?>">Retry from queue</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div>
<?php elseif ($tab === 'preview'): ?>
  <div class="card shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Subject</h2>
    <pre class="bg-light border rounded p-3"><?= e((string) $preview['subject']) ?></pre>
    <?php if (!empty($preview['preheader'])): ?><h3 class="h6">Preheader</h3><pre class="bg-light border rounded p-3"><?= e((string) $preview['preheader']) ?></pre><?php endif; ?>
    <h3 class="h6">HTML Preview</h3>
    <iframe class="w-100 border rounded bg-white" style="min-height:640px;" srcdoc="<?= e((string) $preview['html']) ?>"></iframe>
  </div></div>
  <div class="card shadow-sm"><div class="card-body">
    <h2 class="h5">Plain Text Fallback</h2>
    <pre class="bg-light border rounded p-3 mb-0" style="white-space:pre-wrap"><?= e((string) $preview['text']) ?></pre>
  </div></div>
<?php else: ?>
  <div class="card shadow-sm"><div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>Time</th><th>Queue ID</th><th>Status</th><th>Recipient</th><th>Provider ref</th><th>Error</th></tr></thead>
    <tbody>
    <?php foreach ($recipientRows as $row): ?>
      <tr>
        <td><?= e(format_app_datetime($row['updated_at'], $businessId)) ?></td>
        <td><?= e($row['id']) ?></td>
        <td><?= badge_status((string) $row['status']) ?></td>
        <td><?= e($row['email'] ?: 'Deleted lead') ?></td>
        <td><?= e((string) ($row['provider_reference'] ?? '')) ?></td>
        <td class="small"><?= e((string) $row['error_message']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div>
<?php endif; ?>
<?php render_footer(); ?>
