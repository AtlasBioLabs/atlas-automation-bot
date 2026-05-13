<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<?php
$formBusinessId = !empty($business['id']) ? (int) $business['id'] : null;
$categoryText = $_POST['lead_categories'] ?? ($formBusinessId ? Settings::get('lead_categories', implode("\n", default_lead_categories()), $formBusinessId) : implode("\n", default_lead_categories()));
$followup1 = $_POST['followup_1_days'] ?? ($formBusinessId ? Settings::get('followup_1_days', app_config('followup_1_days', 3), $formBusinessId) : app_config('followup_1_days', 3));
$followup2 = $_POST['followup_2_days'] ?? ($formBusinessId ? Settings::get('followup_2_days', app_config('followup_2_days', 7), $formBusinessId) : app_config('followup_2_days', 7));
?>
<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Business name</label><input class="form-control" name="business_name" required value="<?= e($business['business_name']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Brand name</label><input class="form-control" name="brand_name" required value="<?= e($business['brand_name']) ?>"></div>
      <div class="col-md-8"><label class="form-label">Tagline</label><input class="form-control" name="tagline" value="<?= e($business['tagline']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Industry</label><input class="form-control" name="industry" value="<?= e($business['industry']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Sender name</label><input class="form-control" name="sender_name" required value="<?= e($business['sender_name']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Sender email</label><input class="form-control" type="email" name="sender_email" required value="<?= e($business['sender_email']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Reply-to email</label><input class="form-control" type="email" name="reply_to_email" value="<?= e($business['reply_to_email']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Admin notification email</label><input class="form-control" type="email" name="admin_notification_email" value="<?= e($business['admin_notification_email']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Website URL</label><input class="form-control" name="website_url" value="<?= e($business['website_url']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Company profile URL</label><input class="form-control" name="company_profile_url" value="<?= e($business['company_profile_url'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Logo URL</label><input class="form-control" name="logo_url" value="<?= e($business['logo_url']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Primary color</label><input class="form-control" name="primary_color" required value="<?= e($business['primary_color']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Secondary color</label><input class="form-control" name="secondary_color" required value="<?= e($business['secondary_color']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Accent color</label><input class="form-control" name="accent_color" required value="<?= e($business['accent_color']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Daily send limit</label><input class="form-control" type="number" min="1" name="daily_send_limit" required value="<?= e($business['daily_send_limit']) ?>"></div>
      <div class="col-md-6">
        <label class="form-label">Timezone</label>
        <select class="form-select" name="timezone" required>
          <?php foreach (timezone_choices() as $timezone): ?>
            <option value="<?= e($timezone) ?>"<?= selected((string) $business['timezone'], (string) $timezone) ?>><?= e($timezone) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Follow-up 1 delay days</label><input class="form-control" type="number" min="1" name="followup_1_days" required value="<?= e($followup1) ?>"></div>
      <div class="col-md-6"><label class="form-label">Follow-up 2 delay days</label><input class="form-control" type="number" min="1" name="followup_2_days" required value="<?= e($followup2) ?>"></div>
      <div class="col-12"><label class="form-label">Lead categories</label><textarea class="form-control" name="lead_categories" rows="7" required><?= e($categoryText) ?></textarea></div>
      <div class="col-12"><label class="form-label">Business address</label><textarea class="form-control" name="business_address" rows="3" required><?= e($business['business_address']) ?></textarea></div>
      <div class="col-12"><label class="form-label">Default signature</label><textarea class="form-control" name="default_signature" rows="4" required><?= e($business['default_signature']) ?></textarea></div>
      <div class="col-12"><label class="form-label">Compliance footer</label><textarea class="form-control" name="compliance_footer" rows="4" required><?= e($business['compliance_footer']) ?></textarea></div>
      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" id="active"<?= checked((bool) $business['active']) ?>><label class="form-check-label" for="active">Active</label></div></div>
      <div class="col-12">
        <div class="d-grid d-md-flex gap-2 action-stack">
          <button class="btn btn-primary" type="submit">Save business</button>
          <a class="btn btn-outline-secondary" href="/businesses/index.php">Back</a>
        </div>
      </div>
    </form>
  </div>
</div>
