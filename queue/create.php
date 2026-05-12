<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/QueueService.php';
require_once __DIR__ . '/../app/LeadService.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$templates = QueueService::templates($businessId);
$segments = QueueService::savedSegments($businessId);
$errors = [];
$preview = null;
$created = null;

$input = [
    'campaign_name' => $_REQUEST['campaign_name'] ?? '',
    'campaign_description' => $_REQUEST['campaign_description'] ?? '',
    'template_id' => $_REQUEST['template_id'] ?? '',
    'send_type' => $_REQUEST['send_type'] ?? 'filtered',
    'scheduled_at' => $_REQUEST['scheduled_at'] ?? app_local_input_value(null, $businessId),
    'lead_id' => $_REQUEST['lead_id'] ?? '',
    'selected_lead_ids' => $_REQUEST['selected_lead_ids'] ?? ($_REQUEST['lead_ids'] ?? []),
    'filters' => [
        'q' => $_REQUEST['q'] ?? '',
        'status' => $_REQUEST['status'] ?? '',
        'category' => $_REQUEST['category'] ?? '',
        'country' => $_REQUEST['country'] ?? '',
        'source' => $_REQUEST['source'] ?? '',
        'followup_stage' => $_REQUEST['followup_stage'] ?? '',
        'created_from' => $_REQUEST['created_from'] ?? '',
        'created_to' => $_REQUEST['created_to'] ?? '',
        'visibility' => $_REQUEST['visibility'] ?? LeadService::VISIBILITY_ACTIVE,
        'segment_id' => $_REQUEST['segment_id'] ?? '',
    ],
];

if (is_array($input['selected_lead_ids'])) {
    $input['selected_lead_ids'] = array_values(array_filter(array_map('intval', $input['selected_lead_ids'])));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'preview');
    if ($action === 'confirm') {
        $created = QueueService::createCampaign($input, $businessId);
        if ($created['created']) {
            flash('success', 'Campaign queued. Eligible: ' . $created['queued'] . '. Skipped: ' . $created['skipped'] . '.');
            redirect('/queue/index.php');
        }
        $errors = $created['errors'];
    }

    $preview = QueueService::preview($input, $businessId);
    $errors = array_merge($errors, $preview['errors'] ?? []);
}

render_header('Create Campaign Queue');
?>
<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<style>
  .queue-preview-frame {
    background: linear-gradient(180deg, #f7fafd 0%, #eef4fa 100%);
    border: 1px solid #dbe6f1;
    border-radius: 18px;
    padding: 16px;
  }
  @media (max-width: 767.98px) {
    .queue-preview-frame { padding: 12px; }
    .preview-iframe { min-height: 620px; }
  }
</style>
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="preview">
      <div class="col-md-4"><label class="form-label">Campaign name</label><input class="form-control" name="campaign_name" value="<?= e($input['campaign_name']) ?>" required></div>
      <div class="col-md-4">
        <label class="form-label">Template</label>
        <select class="form-select" name="template_id" required>
          <option value="">Select template</option>
          <?php foreach ($templates as $template): ?><option value="<?= e($template['id']) ?>"<?= selected((string) $input['template_id'], (string) $template['id']) ?>><?= e($template['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Send type</label>
        <select class="form-select" name="send_type">
          <?php foreach (['single', 'selected', 'filtered'] as $type): ?><option value="<?= e($type) ?>"<?= selected((string) $input['send_type'], $type) ?>><?= e($type) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">Scheduled at</label><input class="form-control" type="datetime-local" name="scheduled_at" value="<?= e($input['scheduled_at']) ?>"></div>
      <div class="col-md-4">
        <label class="form-label">Saved segment</label>
        <select class="form-select" name="segment_id">
          <option value="">No saved segment</option>
          <?php foreach ($segments as $segment): ?><option value="<?= e($segment['id']) ?>"<?= selected((string) $input['filters']['segment_id'], (string) $segment['id']) ?>><?= e($segment['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-8"><label class="form-label">Description</label><input class="form-control" name="campaign_description" value="<?= e($input['campaign_description']) ?>" placeholder="Optional internal note"></div>

      <input type="hidden" name="lead_id" value="<?= e($input['lead_id']) ?>">
      <?php foreach ((array) $input['selected_lead_ids'] as $leadId): ?><input type="hidden" name="selected_lead_ids[]" value="<?= e($leadId) ?>"><?php endforeach; ?>
      <?php foreach ($input['filters'] as $key => $value): ?><?php if ($key !== 'segment_id'): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e(is_array($value) ? json_encode($value) : $value) ?>"><?php endif; ?><?php endforeach; ?>

      <div class="col-12">
        <div class="d-grid d-md-flex gap-2 action-stack">
          <button class="btn btn-primary" type="submit">Preview Queue</button>
          <a class="btn btn-outline-secondary" href="/leads/index.php">Back to leads</a>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if ($preview && !$errors): ?>
  <div class="card shadow-sm">
    <div class="card-body">
      <h2 class="h5">Preview Before Queue</h2>
      <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="small text-muted">Selected template</div><div class="fw-semibold"><?= e($preview['template']['name']) ?></div></div>
        <div class="col-md-3"><div class="small text-muted">Matching leads</div><div class="fs-4 fw-semibold"><?= e($preview['total_count']) ?></div></div>
        <div class="col-md-3"><div class="small text-muted">Eligible leads</div><div class="fs-4 fw-semibold"><?= e($preview['eligible_count']) ?></div></div>
        <div class="col-md-3"><div class="small text-muted">Skipped leads</div><div class="fs-4 fw-semibold"><?= e($preview['skipped_count']) ?></div></div>
        <div class="col-md-3"><div class="small text-muted">Daily send limit</div><div class="fs-4 fw-semibold"><?= e(Settings::option('DAILY_SEND_LIMIT', Settings::option('daily_send_limit', 30, $businessId), $businessId)) ?></div></div>
        <div class="col-md-3"><div class="small text-muted">Scheduled at</div><div class="fw-semibold"><?= e(format_app_datetime(QueueService::normalizeScheduledAt((string) $input['scheduled_at'], $businessId), $businessId)) ?></div></div>
      </div>
      <div class="alert alert-warning small">Queued emails are not sent instantly. The sender processes pending queue rows later and still respects the daily send limit, unsubscribes, bounces, stopped statuses, and duplicate prevention. Schedule times are interpreted in <?= e(app_timezone($businessId)) ?>.</div>
      <?php if ($preview['skipped_reason_counts']): ?>
        <div class="mb-3">
          <h3 class="h6">Skipped reasons</h3>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($preview['skipped_reason_counts'] as $reason => $count): ?>
              <span class="badge text-bg-secondary"><?= e(skip_reason_label((string) $reason)) ?>: <?= e($count) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <h3 class="h6">Sample subject</h3>
      <pre class="p-3 bg-light border rounded"><?= e($preview['sample_subject']) ?></pre>
      <?php if ($preview['sample_preheader'] !== ''): ?>
        <h3 class="h6">Sample preheader</h3>
        <pre class="p-3 bg-light border rounded"><?= e($preview['sample_preheader']) ?></pre>
      <?php endif; ?>
      <div class="row g-3">
        <div class="col-lg-7">
          <h3 class="h6">Desktop Preview</h3>
          <div class="queue-preview-frame preview-scroll-frame mb-3">
            <div class="mx-auto bg-white border rounded-4 overflow-hidden" style="max-width:680px;">
              <iframe class="preview-iframe" srcdoc="<?= e($preview['sample_html']) ?>"></iframe>
            </div>
          </div>
          <h3 class="h6">Mobile Preview</h3>
          <div class="queue-preview-frame preview-scroll-frame">
            <div class="mx-auto bg-white border rounded-4 overflow-hidden" style="width:100%;max-width:392px;">
              <iframe class="preview-iframe" srcdoc="<?= e($preview['sample_html']) ?>"></iframe>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <h3 class="h6">Plain text fallback</h3>
          <pre class="p-3 bg-light border rounded h-100" style="white-space:pre-wrap"><?= e($preview['sample_text']) ?></pre>
        </div>
      </div>
      <?php if ($preview['missing_variables']): ?>
        <div class="alert alert-warning small mt-3 mb-0">Unresolved variables: <?= e(implode(', ', $preview['missing_variables'])) ?></div>
      <?php endif; ?>
      <?php if ($preview['skipped']): ?>
        <h3 class="h6">Skipped sample</h3>
        <ul class="small">
          <?php foreach (array_slice($preview['skipped'], 0, 10) as $skipped): ?>
            <li><?= e($skipped['lead']['email']) ?>: <?= e(skip_reason_label((string) $skipped['reason'])) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="confirm">
        <input type="hidden" name="campaign_name" value="<?= e($input['campaign_name']) ?>">
        <input type="hidden" name="campaign_description" value="<?= e($input['campaign_description']) ?>">
        <input type="hidden" name="template_id" value="<?= e($input['template_id']) ?>">
        <input type="hidden" name="send_type" value="<?= e($input['send_type']) ?>">
        <input type="hidden" name="scheduled_at" value="<?= e($input['scheduled_at']) ?>">
        <input type="hidden" name="lead_id" value="<?= e($input['lead_id']) ?>">
        <?php foreach ((array) $input['selected_lead_ids'] as $leadId): ?><input type="hidden" name="selected_lead_ids[]" value="<?= e($leadId) ?>"><?php endforeach; ?>
        <?php foreach ($input['filters'] as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e(is_array($value) ? json_encode($value) : $value) ?>"><?php endforeach; ?>
        <div class="d-grid d-md-flex gap-2 action-stack">
          <button class="btn btn-primary" type="submit"<?= $preview['eligible_count'] === 0 ? ' disabled' : '' ?>>Confirm and Queue Campaign</button>
          <a class="btn btn-outline-secondary" href="/queue/index.php">Back to queue</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>
<?php render_footer(); ?>
