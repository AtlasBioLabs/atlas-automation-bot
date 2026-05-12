<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" required value="<?= e($template['name']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Category</label><input class="form-control" name="category" value="<?= e($template['category']) ?>"></div>
      <div class="col-md-8"><label class="form-label">Subject</label><input class="form-control" name="subject" required value="<?= e($template['subject']) ?>"></div>
      <div class="col-md-2"><label class="form-label">Follow-up stage</label><input class="form-control" type="number" min="0" name="followup_stage" value="<?= e($template['followup_stage']) ?>"></div>
      <div class="col-md-2 form-check mt-5"><input class="form-check-input" type="checkbox" name="active" id="active"<?= checked((bool) $template['active']) ?>><label class="form-check-label" for="active">Active</label></div>
      <div class="col-12">
        <label class="form-label">Body</label>
        <textarea class="form-control" name="body" rows="14" required><?= e($template['body']) ?></textarea>
        <div class="form-text">Supported variables: {{business_name}}, {{brand_name}}, {{tagline}}, {{industry}}, {{website_url}}, {{sender_name}}, {{business_address}}, {{default_signature}}, {{compliance_footer}}, {{contact_name}}, {{company_name}}, {{category}}, {{unsubscribe_link}}</div>
      </div>
      <div class="col-12"><button class="btn btn-primary" type="submit">Save template</button> <a class="btn btn-outline-secondary" href="/templates/index.php">Back</a></div>
    </form>
  </div>
</div>
