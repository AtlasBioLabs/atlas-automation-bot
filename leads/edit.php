<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/LeadService.php';

Auth::require();
$pdo = Database::pdo();
$businessId = BusinessProfile::currentId();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM leads WHERE id = ? AND business_profile_id = ?');
$stmt->execute([$id, $businessId]);
$lead = $stmt->fetch();
if (!$lead) {
    http_response_code(404);
    exit('Lead not found.');
}

$errors = [];
$templates = QueueService::templates($businessId);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'archive') {
        LeadService::archive($id, $businessId);
        flash('success', 'Lead archived.');
        redirect('/leads/edit.php?id=' . $id);
    }
    if ($action === 'restore') {
        LeadService::restore($id, $businessId);
        flash('success', 'Lead restored.');
        redirect('/leads/edit.php?id=' . $id);
    }
    if ($action === 'delete') {
        LeadService::permanentlyDelete($id, $businessId);
        flash('success', 'Lead permanently deleted. Queue and campaign history were preserved.');
        redirect('/leads/index.php?visibility=all');
    }
    if (!empty($lead['deleted_at'])) {
        flash('error', 'Deleted leads are read-only.');
        redirect('/leads/edit.php?id=' . $id);
    }

    $fields = ['company_name', 'contact_name', 'email', 'phone', 'website', 'country', 'category', 'source', 'status', 'notes'];
    foreach ($fields as $field) {
        $lead[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $lead['email'] = normalize_email($lead['email']);
    $lead['bounced'] = !empty($_POST['bounced']) ? 1 : 0;
    $lead['unsubscribed'] = !empty($_POST['unsubscribed']) ? 1 : 0;
    if ($lead['unsubscribed']) {
        $lead['status'] = 'unsubscribed';
    } elseif ($lead['bounced']) {
        $lead['status'] = 'bounced';
    }

    if ($lead['company_name'] === '') {
        $errors[] = 'Company name is required.';
    }
    if (!filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE leads SET company_name = ?, contact_name = ?, email = ?, phone = ?, website = ?, country = ?, category = ?, source = ?, status = ?, notes = ?, bounced = ?, unsubscribed = ?, updated_at = NOW() WHERE id = ? AND business_profile_id = ?'
        );
        $stmt->execute([
            $lead['company_name'], $lead['contact_name'], $lead['email'], $lead['phone'], $lead['website'], $lead['country'],
            $lead['category'], $lead['source'], $lead['status'], $lead['notes'], $lead['bounced'], $lead['unsubscribed'], $id, $businessId,
        ]);
        flash('success', 'Lead updated.');
        redirect('/leads/edit.php?id=' . $id);
    }
}

render_header('Edit Lead');
if (!empty($lead['deleted_at'])): ?>
  <div class="alert alert-dark">This lead has been permanently deleted. Identifying data was removed, and the record is preserved only for queue, log, and campaign history.</div>
<?php elseif (!empty($lead['archived_at'])): ?>
  <div class="alert alert-warning">This lead is archived and will be skipped by queue creation and sender processing until restored.</div>
<?php endif;
require __DIR__ . '/form.php';
?>
<div class="d-flex gap-2 mt-3">
  <?php if (empty($lead['deleted_at']) && empty($lead['archived_at'])): ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="archive">
      <button class="btn btn-outline-warning" type="submit">Archive Lead</button>
    </form>
  <?php elseif (empty($lead['deleted_at'])): ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="restore">
      <button class="btn btn-outline-success" type="submit">Restore Lead</button>
    </form>
  <?php endif; ?>
  <?php if (empty($lead['deleted_at'])): ?>
    <form method="post" onsubmit="return confirm('Permanently delete this lead? Identifying data will be removed, pending emails will be cancelled, and campaign history will remain.');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <button class="btn btn-outline-danger" type="submit">Delete Permanently</button>
    </form>
  <?php endif; ?>
</div>
<div class="card shadow-sm mt-4">
  <div class="card-body">
    <h2 class="h5">Queue Email to This Lead</h2>
    <form method="get" action="/queue/create.php" class="row g-3">
      <input type="hidden" name="send_type" value="single">
      <input type="hidden" name="lead_id" value="<?= e($lead['id']) ?>">
      <div class="col-md-4"><label class="form-label">Campaign name</label><input class="form-control" name="campaign_name" value="Single lead email - <?= e($lead['company_name']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Template</label><select class="form-select" name="template_id" required><option value="">Select template</option><?php foreach ($templates as $template): ?><option value="<?= e($template['id']) ?>"><?= e($template['name']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Schedule</label><input class="form-control" type="datetime-local" name="scheduled_at" value="<?= e(app_local_input_value(null, $businessId)) ?>"></div>
      <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary" type="submit"<?= (!empty($lead['archived_at']) || !empty($lead['deleted_at'])) ? ' disabled' : '' ?>>Preview</button></div>
    </form>
  </div>
</div>
<?php
render_footer();
