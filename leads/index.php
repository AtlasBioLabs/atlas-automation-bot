<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$pdo = Database::pdo();
$businessId = BusinessProfile::currentId();
$templates = QueueService::templates($businessId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'unsubscribe') {
        $pdo->prepare('UPDATE leads SET unsubscribed = 1, status = "unsubscribed", updated_at = NOW() WHERE id = ? AND business_profile_id = ?')->execute([$id, $businessId]);
        flash('success', 'Lead unsubscribed.');
    } elseif ($action === 'queue') {
        flash('error', 'Select a template and preview the email before queueing outreach.');
    }
    redirect('/leads/index.php');
}

$search = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$country = trim((string) ($_GET['country'] ?? ''));
$source = trim((string) ($_GET['source'] ?? ''));
$followupStage = trim((string) ($_GET['followup_stage'] ?? ''));
$createdFrom = trim((string) ($_GET['created_from'] ?? ''));
$createdTo = trim((string) ($_GET['created_to'] ?? ''));
$where = [];
$where = ['business_profile_id = ?'];
$params = [$businessId];
if ($search !== '') {
    $where[] = '(company_name LIKE ? OR contact_name LIKE ? OR email LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
}
if ($country !== '') {
    $where[] = 'country = ?';
    $params[] = $country;
}
if ($source !== '') {
    $where[] = 'source = ?';
    $params[] = $source;
}
if ($followupStage !== '') {
    $where[] = 'followup_stage = ?';
    $params[] = $followupStage;
}
if ($createdFrom !== '') {
    [$startUtc,] = app_local_day_bounds_utc($createdFrom, $businessId);
    $where[] = 'created_at >= ?';
    $params[] = $startUtc;
}
if ($createdTo !== '') {
    [, $endUtc] = app_local_day_bounds_utc($createdTo, $businessId);
    $where[] = 'created_at < ?';
    $params[] = $endUtc;
}
$sql = 'SELECT * FROM leads WHERE ' . implode(' AND ', $where) . ' ORDER BY updated_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

render_header('Leads');
?>
<div class="d-flex gap-2 mb-3">
  <a class="btn btn-primary" href="/leads/create.php">New lead</a>
  <a class="btn btn-outline-secondary" href="/leads/import.php">Import CSV</a>
  <a class="btn btn-outline-primary" href="/queue/create.php?send_type=filtered&<?= e(http_build_query($_GET)) ?>">Queue Email to Filtered Leads</a>
</div>
<form class="row g-2 mb-3">
  <div class="col-md-4"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search company, contact, email"></div>
  <div class="col-md-2">
    <select class="form-select" name="status">
      <option value="">All statuses</option>
      <?php foreach (lead_statuses() as $option): ?><option value="<?= e($option) ?>"<?= selected($status, $option) ?>><?= e($option) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select class="form-select" name="category">
      <option value="">All categories</option>
      <?php foreach (lead_categories($businessId) as $option): ?><option value="<?= e($option) ?>"<?= selected($category, $option) ?>><?= e($option) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><input class="form-control" name="country" value="<?= e($country) ?>" placeholder="Country"></div>
  <div class="col-md-2"><input class="form-control" name="source" value="<?= e($source) ?>" placeholder="Source"></div>
  <div class="col-md-2"><input class="form-control" name="followup_stage" value="<?= e($followupStage) ?>" placeholder="Follow-up stage"></div>
  <div class="col-md-2"><input class="form-control" type="date" name="created_from" value="<?= e($createdFrom) ?>"></div>
  <div class="col-md-2"><input class="form-control" type="date" name="created_to" value="<?= e($createdTo) ?>"></div>
  <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>
<form method="post" action="/queue/create.php">
  <?= csrf_field() ?>
  <input type="hidden" name="send_type" value="selected">
  <div class="card shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label">Bulk action</label><select class="form-select" name="bulk_action"><option value="queue_selected">Queue Email to Selected Leads</option></select></div>
      <div class="col-md-3"><label class="form-label">Campaign name</label><input class="form-control" name="campaign_name" value="Selected leads campaign"></div>
      <div class="col-md-2"><label class="form-label">Template</label><select class="form-select" name="template_id" required><option value="">Template</option><?php foreach ($templates as $template): ?><option value="<?= e($template['id']) ?>"><?= e($template['name']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Schedule</label><input class="form-control" type="datetime-local" name="scheduled_at" value="<?= e(app_local_input_value(null, $businessId)) ?>"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Preview Selected</button></div>
    </div>
  </div>
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.lead-check').forEach(cb => cb.checked = this.checked)"></th><th>Company</th><th>Contact</th><th>Email</th><th>Category</th><th>Status</th><th>Next follow-up</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($leads as $lead): ?>
        <tr>
          <td><input class="form-check-input lead-check" type="checkbox" name="selected_lead_ids[]" value="<?= e($lead['id']) ?>"></td>
          <td><a href="/leads/edit.php?id=<?= e($lead['id']) ?>"><?= e($lead['company_name']) ?></a></td>
          <td><?= e($lead['contact_name']) ?></td>
          <td><?= e($lead['email']) ?></td>
          <td><?= e($lead['category']) ?></td>
          <td><?= badge_status($lead['status']) ?></td>
          <td><?= e(format_app_datetime($lead['next_followup_at'], $businessId)) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="/leads/edit.php?id=<?= e($lead['id']) ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</form>
<?php render_footer(); ?>
