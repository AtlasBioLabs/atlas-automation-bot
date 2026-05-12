<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$status = trim((string) ($_GET['status'] ?? ''));
$templateId = (int) ($_GET['template_id'] ?? 0);
$campaignName = trim((string) ($_GET['campaign_name'] ?? ''));
$scheduledDate = trim((string) ($_GET['scheduled_date'] ?? ''));
$templates = QueueService::templates($businessId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'cancel') {
        $id = (int) ($_POST['id'] ?? 0);
        $campaignLookup = Database::pdo()->prepare('SELECT campaign_id FROM email_queue WHERE id = ? AND business_profile_id = ? LIMIT 1');
        $campaignLookup->execute([$id, $businessId]);
        $campaignId = (int) ($campaignLookup->fetchColumn() ?: 0);
        $stmt = Database::pdo()->prepare('UPDATE email_queue SET status = "cancelled", error_message = "Cancelled by admin.", updated_at = NOW() WHERE id = ? AND business_profile_id = ? AND status = "pending"');
        $stmt->execute([$id, $businessId]);
        if ($campaignId > 0) {
            OutreachService::syncCampaignStatus($businessId, $campaignId);
        }
        flash('success', 'Pending queue item cancelled.');
        redirect('/queue/index.php');
    }
    if ($action === 'run_sender') {
        $stats = OutreachService::processDaily($businessId);
        flash('success', 'Sender run complete: ' . json_encode($stats));
        redirect('/queue/index.php');
    }
}

$where = ['q.business_profile_id = ?'];
$params = [$businessId];
if ($status !== '') {
    $where[] = 'q.status = ?';
    $params[] = $status;
}
if ($templateId > 0) {
    $where[] = 'q.template_id = ?';
    $params[] = $templateId;
}
if ($campaignName !== '') {
    $where[] = 'q.campaign_name LIKE ?';
    $params[] = "%{$campaignName}%";
}
if ($scheduledDate !== '') {
    [$scheduledStartUtc, $scheduledEndUtc] = app_local_day_bounds_utc($scheduledDate, $businessId);
    $where[] = 'q.scheduled_at >= ? AND q.scheduled_at < ?';
    $params[] = $scheduledStartUtc;
    $params[] = $scheduledEndUtc;
}

$stmt = Database::pdo()->prepare(
    'SELECT q.*, l.company_name, l.contact_name, l.email, l.deleted_at, t.name AS template_name
     FROM email_queue q
     LEFT JOIN leads l ON l.id = q.lead_id
     LEFT JOIN email_templates t ON t.id = q.template_id AND t.business_profile_id = q.business_profile_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY q.scheduled_at DESC
     LIMIT 250'
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Email Queue');
?>
<div class="d-grid d-md-flex gap-2 mb-3 action-stack">
  <a class="btn btn-primary" href="/queue/create.php">Create campaign queue</a>
  <form method="post" class="d-inline">
    <?= csrf_field() ?><input type="hidden" name="action" value="run_sender">
    <button class="btn btn-outline-primary" type="submit">Run Sender Now</button>
  </form>
</div>
<form class="row g-2 mb-3">
  <div class="col-md-3">
    <select class="form-select" name="status">
      <option value="">All queue statuses</option>
      <?php foreach (['pending', 'sent', 'failed', 'skipped', 'cancelled'] as $option): ?><option value="<?= e($option) ?>"<?= selected($status, $option) ?>><?= e($option) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <select class="form-select" name="template_id">
      <option value="">All templates</option>
      <?php foreach ($templates as $template): ?><option value="<?= e($template['id']) ?>"<?= selected((string) $templateId, (string) $template['id']) ?>><?= e($template['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><input class="form-control" name="campaign_name" value="<?= e($campaignName) ?>" placeholder="Campaign name"></div>
  <div class="col-md-2"><input class="form-control" type="date" name="scheduled_date" value="<?= e($scheduledDate) ?>"></div>
  <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>
<div class="card shadow-sm">
  <div class="mobile-card-list p-3">
    <?php foreach ($rows as $row): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="mobile-meta">Queued Email</div>
          <div class="fw-semibold mb-1">
            <?php if (!empty($row['campaign_id'])): ?>
              <a href="/campaigns/view.php?id=<?= e($row['campaign_id']) ?>" class="text-decoration-none"><?= e($row['campaign_name'] ?: 'Campaign #' . $row['campaign_id']) ?></a>
            <?php else: ?>
              <?= e($row['campaign_name'] ?: 'Direct queue') ?>
            <?php endif; ?>
          </div>
          <div class="small text-muted mb-1"><?= e($row['template_name'] ?: 'Missing/inactive template') ?></div>
          <div class="small text-break mb-2"><?= e($row['contact_name'] ?: 'Deleted lead') ?><?php if (!empty($row['company_name'])): ?> / <?= e($row['company_name']) ?><?php endif; ?></div>
          <div class="small text-break mb-2"><?= e($row['email'] ?: 'Deleted lead') ?></div>
          <div class="d-flex flex-wrap gap-2 mb-2">
            <?= badge_status($row['status']) ?>
          </div>
          <div class="small text-muted mb-1">Scheduled: <?= e(format_app_datetime($row['scheduled_at'], $businessId)) ?></div>
          <div class="small text-muted mb-3">Sent: <?= e(format_app_datetime($row['sent_at'], $businessId)) ?></div>
          <?php if ($row['error_message'] !== ''): ?><div class="small text-danger mb-3"><?= e($row['error_message']) ?></div><?php endif; ?>
          <div class="d-grid gap-2">
            <a class="btn btn-outline-secondary" href="/queue/view.php?id=<?= e($row['id']) ?>">View</a>
            <?php if ($row['status'] === 'pending'): ?>
              <form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= e($row['id']) ?>">
                <button class="btn btn-outline-danger w-100" type="submit">Cancel</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="table-responsive desktop-table-only">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Campaign</th><th>Lead</th><th>Company</th><th>Template</th><th>Scheduled</th><th>Status</th><th>Sent</th><th>Error</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td>
            <?php if (!empty($row['campaign_id'])): ?>
              <a href="/campaigns/view.php?id=<?= e($row['campaign_id']) ?>"><?= e($row['campaign_name'] ?: 'Campaign #' . $row['campaign_id']) ?></a>
            <?php else: ?>
              <?= e($row['campaign_name'] ?: 'Direct queue') ?>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($row['lead_id']) && empty($row['deleted_at'])): ?>
              <a href="/leads/edit.php?id=<?= e($row['lead_id']) ?>"><?= e($row['contact_name'] ?: $row['email']) ?></a>
            <?php else: ?>
              <?= e($row['contact_name'] ?: 'Deleted lead') ?>
            <?php endif; ?>
          </td>
          <td><?= e($row['company_name'] ?: 'Deleted lead') ?></td>
          <td><?= e($row['template_name'] ?: 'Missing/inactive template') ?></td>
          <td><?= e(format_app_datetime($row['scheduled_at'], $businessId)) ?></td>
          <td><?= badge_status($row['status']) ?></td>
          <td><?= e(format_app_datetime($row['sent_at'], $businessId)) ?></td>
          <td class="small"><?= e($row['error_message']) ?></td>
          <td class="text-end d-flex justify-content-end gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="/queue/view.php?id=<?= e($row['id']) ?>">View</a>
            <?php if ($row['status'] === 'pending'): ?>
              <form method="post" class="d-inline">
                <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= e($row['id']) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
