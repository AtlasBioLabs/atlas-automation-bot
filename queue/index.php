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
        $stmt = Database::pdo()->prepare('UPDATE email_queue SET status = "skipped", error_message = "Cancelled by admin.", updated_at = NOW() WHERE id = ? AND business_profile_id = ? AND status = "pending"');
        $stmt->execute([$id, $businessId]);
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
    $where[] = 'DATE(q.scheduled_at) = ?';
    $params[] = $scheduledDate;
}

$stmt = Database::pdo()->prepare(
    'SELECT q.*, l.company_name, l.contact_name, l.email, t.name AS template_name
     FROM email_queue q
     JOIN leads l ON l.id = q.lead_id
     LEFT JOIN email_templates t ON t.id = q.template_id AND t.business_profile_id = q.business_profile_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY q.scheduled_at DESC
     LIMIT 250'
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Email Queue');
?>
<div class="d-flex flex-wrap gap-2 mb-3">
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
      <?php foreach (['pending', 'sent', 'failed', 'skipped'] as $option): ?><option value="<?= e($option) ?>"<?= selected($status, $option) ?>><?= e($option) ?></option><?php endforeach; ?>
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
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Campaign</th><th>Lead</th><th>Company</th><th>Template</th><th>Scheduled</th><th>Status</th><th>Sent</th><th>Error</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['campaign_name'] ?: 'Direct queue') ?></td>
          <td><a href="/leads/edit.php?id=<?= e($row['lead_id']) ?>"><?= e($row['contact_name'] ?: $row['email']) ?></a></td>
          <td><?= e($row['company_name']) ?></td>
          <td><?= e($row['template_name'] ?: 'Missing/inactive template') ?></td>
          <td><?= e($row['scheduled_at']) ?></td>
          <td><?= badge_status($row['status']) ?></td>
          <td><?= e($row['sent_at']) ?></td>
          <td class="small"><?= e($row['error_message']) ?></td>
          <td class="text-end">
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
