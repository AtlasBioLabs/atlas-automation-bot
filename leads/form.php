<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<?php $leadReadOnly = !empty($lead['deleted_at']); ?>
<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Company name</label><input class="form-control" name="company_name" required value="<?= e($lead['company_name']) ?>"<?= $leadReadOnly ? ' disabled' : '' ?>></div>
      <div class="col-md-6"><label class="form-label">Contact name</label><input class="form-control" name="contact_name" value="<?= e($lead['contact_name']) ?>"<?= $leadReadOnly ? ' disabled' : '' ?>></div>
      <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required value="<?= e($lead['email']) ?>"<?= $leadReadOnly ? ' disabled' : '' ?>></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($lead['phone']) ?>"<?= $leadReadOnly ? ' disabled' : '' ?>></div>
      <div class="col-md-6"><label class="form-label">Website</label><input class="form-control" name="website" value="<?= e($lead['website']) ?>"<?= $leadReadOnly ? ' disabled' : '' ?>></div>
      <div class="col-md-6"><label class="form-label">Country</label><input class="form-control" name="country" value="<?= e($lead['country']) ?>"<?= $leadReadOnly ? ' disabled' : '' ?>></div>
      <div class="col-md-6">
        <label class="form-label">Category</label>
        <select class="form-select" name="category"<?= $leadReadOnly ? ' disabled' : '' ?>><?php foreach (lead_categories(BusinessProfile::currentId()) as $option): ?><option value="<?= e($option) ?>"<?= selected((string) $lead['category'], $option) ?>><?= e($option) ?></option><?php endforeach; ?></select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Status</label>
        <select class="form-select" name="status"<?= $leadReadOnly ? ' disabled' : '' ?>><?php foreach (lead_statuses() as $option): ?><option value="<?= e($option) ?>"<?= selected((string) $lead['status'], $option) ?>><?= e($option) ?></option><?php endforeach; ?></select>
      </div>
      <div class="col-md-6"><label class="form-label">Source</label><input class="form-control" name="source" value="<?= e($lead['source']) ?>"<?= $leadReadOnly ? ' disabled' : '' ?>></div>
      <?php if (!empty($lead['id'])): ?>
        <div class="col-md-3"><div class="form-check mt-2 mt-md-5"><input class="form-check-input" type="checkbox" name="bounced" id="bounced"<?= checked((bool) $lead['bounced']) ?><?= $leadReadOnly ? ' disabled' : '' ?>><label class="form-check-label" for="bounced">Bounced</label></div></div>
        <div class="col-md-3"><div class="form-check mt-2 mt-md-5"><input class="form-check-input" type="checkbox" name="unsubscribed" id="unsubscribed"<?= checked((bool) $lead['unsubscribed']) ?><?= $leadReadOnly ? ' disabled' : '' ?>><label class="form-check-label" for="unsubscribed">Unsubscribed</label></div></div>
      <?php endif; ?>
      <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="5"<?= $leadReadOnly ? ' disabled' : '' ?>><?= e($lead['notes']) ?></textarea></div>
      <div class="col-12">
        <div class="d-grid d-md-flex gap-2 action-stack">
          <?php if (!$leadReadOnly): ?><button class="btn btn-primary" type="submit">Save lead</button><?php endif; ?>
          <a class="btn btn-outline-secondary" href="/leads/index.php">Back</a>
        </div>
      </div>
    </form>
  </div>
</div>
