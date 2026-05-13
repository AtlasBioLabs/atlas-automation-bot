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
$leadForRender = [
    'business_profile_id' => $businessId,
    'contact_name' => $sampleRow['contact_name'] ?? 'Sample Contact',
    'company_name' => $sampleRow['company_name'] ?? 'Sample Company',
    'email' => $sampleRow['email'] ?? (string) ($business['reply_to_email'] ?: $business['sender_email'] ?: 'sample@example.com'),
    'category' => 'Other',
    'unsubscribe_token' => '',
];
$preview = Mailer::renderTemplate($campaign, $leadForRender, $business);
$businessWarnings = $preview['business_warnings'] ?? [];

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
<style>
  .email-preview-frame {
    background: linear-gradient(180deg, #f7fafd 0%, #eef4fa 100%);
    border: 1px solid #dbe6f1;
    border-radius: 18px;
    padding: 24px;
  }
  .email-preview-canvas {
    width: 100%;
    background: #ffffff;
    border: 1px solid #d6e0eb;
    border-radius: 18px;
    box-shadow: 0 18px 40px rgba(10, 26, 47, 0.08);
    overflow: hidden;
  }
  .email-preview-canvas iframe {
    display: block;
    width: 100%;
    border: 0;
    overflow: hidden;
  }
  .email-preview-meta {
    background: #f7fafd;
    border: 1px solid #dce6f2;
    border-radius: 14px;
    padding: 16px 18px;
  }
  @media (max-width: 767.98px) {
    .email-preview-frame { padding: 14px; }
    .preview-iframe { min-height: 620px; }
  }
</style>
<div class="d-grid d-md-flex gap-2 mb-3 btn-group-mobile">
  <a class="btn btn-outline-secondary" href="/campaigns/index.php">Back to campaigns</a>
  <a class="btn btn-outline-primary" href="/queue/index.php?campaign_name=<?= urlencode((string) $campaign['name']) ?>">Open queue filter</a>
</div>

<?php if ($businessWarnings): ?>
  <div class="alert alert-warning small">
    <strong>Business profile warnings:</strong> <?= e(implode(' ', $businessWarnings)) ?>
  </div>
<?php endif; ?>

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

<div class="nav-tabs-wrap mb-3">
<ul class="nav nav-tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <li class="nav-item"><a class="nav-link<?= $tab === $key ? ' active' : '' ?>" href="/campaigns/view.php?id=<?= e($id) ?>&tab=<?= e($key) ?>"><?= e($label) ?></a></li>
  <?php endforeach; ?>
</ul>
</div>

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
  <div class="mobile-card-list">
    <?php foreach ($recipientRows as $row): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="fw-semibold"><?= e(LeadService::displayName($row)) ?></div>
              <div class="text-muted small"><?= e($row['company_name'] ?: 'Deleted lead') ?></div>
              <div class="small text-break"><?= e($row['email'] ?: 'Deleted lead') ?></div>
            </div>
            <?= badge_status((string) $row['status']) ?>
          </div>
          <div class="row g-2 mt-2 small">
            <div class="col-6"><div class="mobile-meta">Scheduled</div><div><?= e(format_app_datetime($row['scheduled_at'], $businessId)) ?></div></div>
            <div class="col-6"><div class="mobile-meta">Sent</div><div><?= e(format_app_datetime($row['sent_at'], $businessId)) ?></div></div>
            <div class="col-12"><div class="mobile-meta">Provider ref</div><div class="text-break"><?= e((string) ($row['provider_reference'] ?? '')) ?></div></div>
            <?php if ((string) $row['error_message'] !== ''): ?><div class="col-12"><div class="mobile-meta">Error</div><div class="small text-danger"><?= e((string) $row['error_message']) ?></div></div><?php endif; ?>
          </div>
          <div class="mobile-actions mt-3"><a class="btn btn-outline-primary" href="/queue/view.php?id=<?= e($row['id']) ?>">Queue detail</a></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card shadow-sm"><div class="table-responsive desktop-table-only"><table class="table mb-0 align-middle">
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
  <div class="mobile-card-list">
    <?php foreach ($skippedRows as $row): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold"><?= e($row['lead_contact_name'] ?: 'Deleted lead') ?></div>
          <div class="text-muted small"><?= e($row['lead_company_name'] ?: 'Deleted lead') ?></div>
          <div class="small text-break"><?= e($row['lead_email'] ?: 'Deleted lead') ?></div>
          <div class="mobile-meta mt-3">Skipped reason</div>
          <div><?= e(skip_reason_label((string) $row['reason'])) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card shadow-sm"><div class="table-responsive desktop-table-only"><table class="table mb-0 align-middle">
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
  <div class="mobile-card-list">
    <?php foreach ($recipientRows as $row): ?>
      <?php if ($row['status'] !== 'failed') {
          continue;
      } ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold"><?= e(LeadService::displayName($row)) ?></div>
          <div class="text-muted small"><?= e($row['company_name'] ?: 'Deleted lead') ?></div>
          <div class="small text-break"><?= e($row['email'] ?: 'Deleted lead') ?></div>
          <div class="mobile-meta mt-3">Safe error</div>
          <div class="small text-danger"><?= e((string) $row['error_message']) ?></div>
          <div class="mobile-actions mt-3"><a class="btn btn-outline-primary" href="/queue/view.php?id=<?= e($row['id']) ?>">Retry from queue</a></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card shadow-sm"><div class="table-responsive desktop-table-only"><table class="table mb-0 align-middle">
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
    <h2 class="h5 mb-3">Rendered Email</h2>
    <div class="email-preview-meta mb-3">
      <div class="small text-muted mb-1">Subject</div>
      <div class="fw-semibold"><?= e((string) $preview['subject']) ?></div>
    </div>
    <?php if (!empty($preview['preheader'])): ?>
      <div class="email-preview-meta mb-3">
        <div class="small text-muted mb-1">Preheader</div>
        <div><?= e((string) $preview['preheader']) ?></div>
      </div>
    <?php endif; ?>
    <h3 class="h6 mb-3">Desktop Preview</h3>
    <div class="email-preview-frame mb-4 preview-scroll-frame">
      <div class="email-preview-canvas mx-auto" style="max-width:620px;">
        <iframe class="preview-iframe" srcdoc="<?= e((string) $preview['html']) ?>"></iframe>
      </div>
    </div>
    <h3 class="h6 mb-3">Mobile Preview</h3>
    <div class="email-preview-frame preview-scroll-frame">
      <div class="email-preview-canvas mx-auto" style="width:100%;max-width:380px;">
        <iframe class="preview-iframe" srcdoc="<?= e((string) $preview['html']) ?>"></iframe>
      </div>
    </div>
  </div></div>
  <div class="card shadow-sm"><div class="card-body">
    <h2 class="h5 mb-3">Plain Text Fallback</h2>
    <pre class="bg-light border rounded p-3 mb-0" style="white-space:pre-wrap"><?= e((string) $preview['text']) ?></pre>
  </div></div>
<?php else: ?>
  <div class="mobile-card-list">
    <?php foreach ($recipientRows as $row): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between gap-3">
            <div>
              <div class="mobile-meta">Time</div>
              <div class="fw-semibold"><?= e(format_app_datetime($row['updated_at'], $businessId)) ?></div>
            </div>
            <?= badge_status((string) $row['status']) ?>
          </div>
          <div class="row g-2 mt-2 small">
            <div class="col-6"><div class="mobile-meta">Queue ID</div><div><?= e($row['id']) ?></div></div>
            <div class="col-6"><div class="mobile-meta">Recipient</div><div class="text-break"><?= e($row['email'] ?: 'Deleted lead') ?></div></div>
            <div class="col-12"><div class="mobile-meta">Provider ref</div><div class="text-break"><?= e((string) ($row['provider_reference'] ?? '')) ?></div></div>
            <?php if ((string) $row['error_message'] !== ''): ?><div class="col-12"><div class="mobile-meta">Error</div><div class="small text-danger"><?= e((string) $row['error_message']) ?></div></div><?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card shadow-sm"><div class="table-responsive desktop-table-only"><table class="table mb-0 align-middle">
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
