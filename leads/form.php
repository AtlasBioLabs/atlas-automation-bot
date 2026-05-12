<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Company name</label><input class="form-control" name="company_name" required value="<?= e($lead['company_name']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Contact name</label><input class="form-control" name="contact_name" value="<?= e($lead['contact_name']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required value="<?= e($lead['email']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($lead['phone']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Website</label><input class="form-control" name="website" value="<?= e($lead['website']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Country</label><input class="form-control" name="country" value="<?= e($lead['country']) ?>"></div>
      <div class="col-md-6">
        <label class="form-label">Category</label>
        <select class="form-select" name="category"><?php foreach (lead_categories(BusinessProfile::currentId()) as $option): ?><option value="<?= e($option) ?>"<?= selected((string) $lead['category'], $option) ?>><?= e($option) ?></option><?php endforeach; ?></select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Status</label>
        <select class="form-select" name="status"><?php foreach (lead_statuses() as $option): ?><option value="<?= e($option) ?>"<?= selected((string) $lead['status'], $option) ?>><?= e($option) ?></option><?php endforeach; ?></select>
      </div>
      <div class="col-md-6"><label class="form-label">Source</label><input class="form-control" name="source" value="<?= e($lead['source']) ?>"></div>
      <?php if (!empty($lead['id'])): ?>
        <div class="col-md-3 form-check mt-5"><input class="form-check-input" type="checkbox" name="bounced" id="bounced"<?= checked((bool) $lead['bounced']) ?>><label class="form-check-label" for="bounced">Bounced</label></div>
        <div class="col-md-3 form-check mt-5"><input class="form-check-input" type="checkbox" name="unsubscribed" id="unsubscribed"<?= checked((bool) $lead['unsubscribed']) ?>><label class="form-check-label" for="unsubscribed">Unsubscribed</label></div>
      <?php endif; ?>
      <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="5"><?= e($lead['notes']) ?></textarea></div>
      <div class="col-12"><button class="btn btn-primary" type="submit">Save lead</button> <a class="btn btn-outline-secondary" href="/leads/index.php">Back</a></div>
    </form>
  </div>
</div>
