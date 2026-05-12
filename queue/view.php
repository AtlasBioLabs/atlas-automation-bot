<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/LeadService.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$business = Settings::effectiveBusiness($businessId) ?? BusinessProfile::current();
$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'cancel') {
        $stmt = $pdo->prepare('UPDATE email_queue SET status = "cancelled", error_message = "Cancelled by admin.", updated_at = NOW() WHERE id = ? AND business_profile_id = ? AND status = "pending"');
        $stmt->execute([$id, $businessId]);
        $campaignId = (int) ($_GET['campaign_id'] ?? 0);
        if ($campaignId === 0) {
            $lookup = $pdo->prepare('SELECT campaign_id FROM email_queue WHERE id = ? AND business_profile_id = ? LIMIT 1');
            $lookup->execute([$id, $businessId]);
            $campaignId = (int) ($lookup->fetchColumn() ?: 0);
        }
        if ($campaignId > 0) {
            OutreachService::syncCampaignStatus($businessId, $campaignId);
        }
        flash('success', 'Pending queue item cancelled.');
        redirect('/queue/view.php?id=' . $id);
    }
    if ($action === 'requeue') {
        $stmt = $pdo->prepare(
            'SELECT q.*, l.company_name, l.contact_name, l.email, l.category, l.unsubscribe_token, l.status AS lead_status, l.bounced, l.unsubscribed, l.archived_at, l.deleted_at,
                    t.subject, t.preheader, t.body, t.body_html, t.body_text
             FROM email_queue q
             LEFT JOIN leads l ON l.id = q.lead_id
             LEFT JOIN email_templates t ON t.id = q.template_id AND t.business_profile_id = q.business_profile_id
             WHERE q.id = ? AND q.business_profile_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $businessId]);
        $source = $stmt->fetch();
        if (!$source || (string) $source['status'] !== 'failed') {
            flash('error', 'Only failed queue items can be requeued.');
            redirect('/queue/view.php?id=' . $id);
        }
        $reason = LeadService::suppressionReason([
            'email' => $source['email'] ?? '',
            'status' => $source['lead_status'] ?? '',
            'bounced' => $source['bounced'] ?? 0,
            'unsubscribed' => $source['unsubscribed'] ?? 0,
            'archived_at' => $source['archived_at'] ?? null,
            'deleted_at' => $source['deleted_at'] ?? null,
        ]);
        if ($reason !== null) {
            flash('error', 'This lead cannot be requeued: ' . skip_reason_label($reason) . '.');
            redirect('/queue/view.php?id=' . $id);
        }
        $duplicate = $pdo->prepare('SELECT id FROM email_queue WHERE business_profile_id = ? AND lead_id = ? AND template_id = ? AND status = "pending" LIMIT 1');
        $duplicate->execute([$businessId, (int) $source['lead_id'], (int) $source['template_id']]);
        if ($duplicate->fetch()) {
            flash('error', 'A pending queue item already exists for this lead and template.');
            redirect('/queue/view.php?id=' . $id);
        }

        $render = Mailer::renderTemplate($source, $source, $business);
        $insert = $pdo->prepare(
            'INSERT INTO email_queue (business_profile_id, campaign_id, campaign_name, lead_id, template_id, rendered_subject, rendered_preheader, rendered_html, rendered_text, rendered_variables, scheduled_at, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW(), NOW())'
        );
        $insert->execute([
            $businessId,
            $source['campaign_id'] ? (int) $source['campaign_id'] : null,
            $source['campaign_name'],
            (int) $source['lead_id'],
            (int) $source['template_id'],
            $render['subject'],
            $render['preheader'],
            $render['html'],
            $render['text'],
            json_encode($render['variables'], JSON_UNESCAPED_SLASHES),
            now_sql(),
        ]);
        if (!empty($source['campaign_id'])) {
            OutreachService::syncCampaignStatus($businessId, (int) $source['campaign_id']);
        }
        flash('success', 'Failed queue item requeued.');
        redirect('/queue/view.php?id=' . $id);
    }
}

$stmt = $pdo->prepare(
    'SELECT q.*, bp.business_name, bp.brand_name, c.name AS campaign_title,
            l.company_name, l.contact_name, l.email, l.status AS lead_status, l.category, l.unsubscribe_token, l.archived_at, l.deleted_at,
            t.name AS template_name, t.subject AS template_subject, t.preheader AS template_preheader, t.body, t.body_html, t.body_text, t.active AS template_active
     FROM email_queue q
     JOIN business_profiles bp ON bp.id = q.business_profile_id
     LEFT JOIN campaigns c ON c.id = q.campaign_id AND c.business_profile_id = q.business_profile_id
     LEFT JOIN leads l ON l.id = q.lead_id
     LEFT JOIN email_templates t ON t.id = q.template_id AND t.business_profile_id = q.business_profile_id
     WHERE q.id = ? AND q.business_profile_id = ?
     LIMIT 1'
);
$stmt->execute([$id, $businessId]);
$queue = $stmt->fetch();
if (!$queue) {
    http_response_code(404);
    exit('Queue item not found.');
}

$logStmt = $pdo->prepare('SELECT provider_reference, error_message, created_at FROM email_logs WHERE queue_id = ? AND business_profile_id = ? ORDER BY id DESC LIMIT 1');
$logStmt->execute([$id, $businessId]);
$latestLog = $logStmt->fetch() ?: [];

$leadForRender = [
    'business_profile_id' => $businessId,
    'contact_name' => $queue['contact_name'] ?? '',
    'company_name' => $queue['company_name'] ?? 'Deleted lead',
    'email' => $queue['email'] ?? '',
    'category' => $queue['category'] ?? 'Other',
    'unsubscribe_token' => $queue['unsubscribe_token'] ?? '',
];
$render = (!empty($queue['rendered_subject']) && (!empty($queue['rendered_html']) || !empty($queue['rendered_text'])))
    ? [
        'subject' => (string) $queue['rendered_subject'],
        'preheader' => (string) ($queue['rendered_preheader'] ?? ''),
        'html' => (string) ($queue['rendered_html'] ?? ''),
        'text' => (string) ($queue['rendered_text'] ?? ''),
        'variables' => json_decode((string) ($queue['rendered_variables'] ?? ''), true) ?: Mailer::templateVariables($leadForRender, $business),
        'unsubscribe_link' => Mailer::templateVariables($leadForRender, $business)['{{unsubscribe_link}}'] ?? '',
        'missing_variables' => [],
    ]
    : Mailer::renderTemplate($queue, $leadForRender, $business);

render_header('Queue Email Detail');
?>
<style>
  .email-preview-frame {
    background: linear-gradient(180deg, #f7fafd 0%, #eef4fa 100%);
    border: 1px solid #dbe6f1;
    border-radius: 18px;
    padding: 24px;
  }
  .email-preview-canvas {
    background: #ffffff;
    border: 1px solid #d6e0eb;
    border-radius: 18px;
    box-shadow: 0 18px 40px rgba(10, 26, 47, 0.08);
    overflow: hidden;
  }
  .email-preview-meta {
    background: #f7fafd;
    border: 1px solid #dce6f2;
    border-radius: 14px;
    padding: 16px 18px;
  }
  .queue-detail-card {
    background: #fff;
    border: 1px solid #dce6f2;
    border-radius: 16px;
    padding: 18px;
  }
  @media (max-width: 767.98px) {
    .email-preview-frame { padding: 14px; }
    .email-preview-canvas { border-radius: 14px; }
    .preview-iframe { min-height: 620px; }
  }
</style>
<div class="d-grid d-md-flex gap-2 mb-3 btn-group-mobile">
  <a class="btn btn-outline-secondary" href="/queue/index.php">Back to queue</a>
  <?php if (!empty($queue['campaign_id'])): ?><a class="btn btn-outline-primary" href="/campaigns/view.php?id=<?= e($queue['campaign_id']) ?>">Open campaign</a><?php endif; ?>
  <?php if ($queue['status'] === 'pending'): ?>
    <form method="post" onsubmit="return confirm('Cancel this pending email?');">
      <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
      <button class="btn btn-outline-danger" type="submit">Cancel Pending Email</button>
    </form>
  <?php endif; ?>
  <?php if ($queue['status'] === 'failed'): ?>
    <form method="post" onsubmit="return confirm('Requeue this failed email?');">
      <?= csrf_field() ?><input type="hidden" name="action" value="requeue">
      <button class="btn btn-outline-primary" type="submit">Requeue Failed Email</button>
    </form>
  <?php endif; ?>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="queue-detail-card mb-3">
          <div class="small text-muted mb-1">Queue status</div>
          <div class="d-flex flex-wrap align-items-center gap-2">
            <?= badge_status((string) $queue['status']) ?>
            <span class="small text-muted">Scheduled <?= e(format_app_datetime($queue['scheduled_at'], $businessId)) ?></span>
          </div>
        </div>
        <h2 class="h5">Queue Details</h2>
        <dl class="detail-list mb-0 small">
          <dt>Queue ID</dt><dd><?= e($queue['id']) ?></dd>
          <dt>Business</dt><dd><?= e($queue['brand_name']) ?> (#<?= e($businessId) ?>)</dd>
          <dt>Campaign</dt><dd><?= e($queue['campaign_title'] ?: ($queue['campaign_name'] ?: 'Direct queue')) ?></dd>
          <dt>Lead</dt><dd><?= e(LeadService::displayName($queue)) ?></dd>
          <dt>Company</dt><dd><?= e($queue['company_name'] ?: 'Deleted lead') ?></dd>
          <dt>Email</dt><dd><?= e($queue['email'] ?: 'Deleted lead') ?></dd>
          <dt>Lead status</dt><dd><?= e($queue['lead_status'] ?: 'Deleted lead') ?></dd>
          <dt>Template</dt><dd><?= e($queue['template_name'] ?: 'Missing template') ?></dd>
          <dt>Sent</dt><dd><?= e(format_app_datetime($queue['sent_at'], $businessId)) ?></dd>
          <dt>Provider ref</dt><dd><?= e((string) ($latestLog['provider_reference'] ?? '')) ?></dd>
          <dt>Error</dt><dd><?= e((string) ($queue['error_message'] ?: ($latestLog['error_message'] ?? ''))) ?></dd>
          <dt>Created</dt><dd><?= e(format_app_datetime($queue['created_at'], $businessId)) ?></dd>
          <dt>Updated</dt><dd><?= e(format_app_datetime($queue['updated_at'], $businessId)) ?></dd>
          <dt>Unsubscribe</dt><dd class="small text-break"><?= e((string) $render['unsubscribe_link']) ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3">Rendered Email</h2>
        <div class="email-preview-meta mb-3">
          <div class="small text-muted mb-1">Subject</div>
          <div class="fw-semibold"><?= e($render['subject']) ?></div>
        </div>
        <?php if ($render['preheader'] !== ''): ?>
          <div class="email-preview-meta mb-3">
            <div class="small text-muted mb-1">Preheader</div>
            <div><?= e($render['preheader']) ?></div>
          </div>
        <?php endif; ?>
        <h3 class="h6 mb-3">Desktop Preview</h3>
        <div class="email-preview-frame mb-4 preview-scroll-frame">
          <div class="email-preview-canvas mx-auto" style="max-width:680px;">
            <iframe class="preview-iframe" srcdoc="<?= e($render['html']) ?>"></iframe>
          </div>
        </div>
        <h3 class="h6 mb-3">Mobile Preview</h3>
        <div class="email-preview-frame preview-scroll-frame">
          <div class="email-preview-canvas mx-auto" style="width:100%;max-width:392px;">
            <iframe class="preview-iframe" srcdoc="<?= e($render['html']) ?>"></iframe>
          </div>
        </div>
      </div>
    </div>
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5">Plain Text Fallback</h2>
        <pre class="bg-light border rounded p-3 mb-0" style="white-space:pre-wrap"><?= e($render['text']) ?></pre>
      </div>
    </div>
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">Rendered Variables</h2>
        <div class="mobile-card-list">
          <?php foreach ($render['variables'] as $token => $value): ?>
            <div class="card shadow-sm">
              <div class="card-body">
                <div class="mobile-meta">Variable</div>
                <div class="fw-semibold"><code><?= e((string) $token) ?></code></div>
                <div class="mobile-meta mt-3">Value</div>
                <div class="small text-break"><?= e((string) $value) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="table-responsive desktop-table-only">
          <table class="table table-sm mb-0">
            <thead><tr><th>Variable</th><th>Value</th></tr></thead>
            <tbody>
            <?php foreach ($render['variables'] as $token => $value): ?>
              <tr><td><code><?= e((string) $token) ?></code></td><td class="small"><?= e((string) $value) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
