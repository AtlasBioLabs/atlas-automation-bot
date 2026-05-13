<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" required value="<?= e($template['name']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Category</label><input class="form-control" name="category" value="<?= e($template['category']) ?>"></div>
      <div class="col-md-8"><label class="form-label">Subject</label><input class="form-control" name="subject" required value="<?= e($template['subject']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Preheader</label><input class="form-control" name="preheader" value="<?= e($template['preheader'] ?? '') ?>" placeholder="Optional inbox preview text"></div>
      <div class="col-md-2"><label class="form-label">Follow-up stage</label><input class="form-control" type="number" min="0" name="followup_stage" value="<?= e($template['followup_stage']) ?>"></div>
      <div class="col-md-2"><div class="form-check mt-2 mt-md-5"><input class="form-check-input" type="checkbox" name="active" id="active"<?= checked((bool) $template['active']) ?>><label class="form-check-label" for="active">Active</label></div></div>
      <div class="col-12">
        <label class="form-label">Main email content</label>
        <div class="border rounded-3 bg-light p-2 mb-2 wysiwyg-toolbar" role="toolbar" aria-label="Template editor toolbar">
          <button class="btn btn-sm btn-outline-secondary" type="button" data-command="bold"><strong>B</strong></button>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-command="italic"><em>I</em></button>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-command="underline"><u>U</u></button>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-command="insertUnorderedList">Bullets</button>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-command="insertOrderedList">Numbers</button>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-link="true">Link</button>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-command="removeFormat">Clear</button>
        </div>
        <div id="bodyHtmlEditor" class="form-control bg-white" contenteditable="true" style="min-height:260px;overflow:auto;line-height:1.6;"><?= sanitize_template_body_html((string) ($template['body_html'] ?? '')) ?></div>
        <textarea class="d-none" name="body_html" id="bodyHtmlInput"><?= e(sanitize_template_body_html((string) ($template['body_html'] ?? ''))) ?></textarea>
        <div class="form-text">Edit only the main content. The master email layout automatically adds the header, Next Step block, signature, compliance footer, and unsubscribe link.</div>
      </div>
      <div class="col-12">
        <label class="form-label">Plain text fallback</label>
        <textarea class="form-control" name="body_text" rows="10" required><?= e($template['body_text'] ?? $template['body'] ?? '') ?></textarea>
        <div class="form-text">Supported variables: {{contact_name}}, {{company_name}}, {{category}}, {{unsubscribe_link}}, {{business_name}}, {{brand_name}}, {{business_tagline}}, {{business_logo_url}}, {{website_url}}, {{company_profile_url}}, {{sender_name}}, {{sender_email}}, {{reply_to_email}}, {{business_address}}, {{default_signature}}, {{compliance_footer}}, {{unsubscribe_footer_text}}</div>
      </div>
      <div class="col-12">
        <div class="d-grid d-md-flex gap-2 action-stack">
          <button class="btn btn-primary" type="submit">Save template</button>
          <?php if (!empty($template['id'])): ?><a class="btn btn-outline-primary" href="/templates/preview.php?id=<?= e($template['id']) ?>">Preview</a><?php endif; ?>
          <a class="btn btn-outline-secondary" href="/templates/index.php">Back</a>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
(() => {
  const editor = document.getElementById('bodyHtmlEditor');
  const input = document.getElementById('bodyHtmlInput');
  if (!editor || !input) return;

  document.querySelectorAll('.wysiwyg-toolbar [data-command]').forEach((button) => {
    button.addEventListener('click', () => {
      editor.focus();
      document.execCommand(button.getAttribute('data-command'), false, null);
      input.value = editor.innerHTML;
    });
  });

  document.querySelectorAll('.wysiwyg-toolbar [data-link]').forEach((button) => {
    button.addEventListener('click', () => {
      const url = window.prompt('Enter a full https:// or mailto: link');
      if (!url) return;
      if (!/^(https?:\/\/|mailto:|\{\{)/i.test(url)) {
        window.alert('Use a full https:// or mailto: link.');
        return;
      }
      editor.focus();
      document.execCommand('createLink', false, url);
      input.value = editor.innerHTML;
    });
  });

  editor.addEventListener('input', () => {
    input.value = editor.innerHTML;
  });
  editor.closest('form')?.addEventListener('submit', () => {
    input.value = editor.innerHTML;
  });
})();
</script>
