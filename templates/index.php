<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$stmt = Database::pdo()->prepare('SELECT * FROM email_templates WHERE business_profile_id = ? ORDER BY followup_stage, category, name');
$stmt->execute([BusinessProfile::currentId()]);
$templates = $stmt->fetchAll();

render_header('Email Templates');
?>
<div class="mb-3"><a class="btn btn-primary" href="/templates/create.php">New template</a></div>
<div class="card shadow-sm">
  <div class="mobile-card-list p-3">
    <?php foreach ($templates as $template): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="mobile-meta">Template</div>
          <div class="fw-semibold mb-1"><?= e($template['name']) ?></div>
          <div class="small text-muted mb-2"><?= e($template['category']) ?></div>
          <div class="mb-2"><?= e($template['subject']) ?></div>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <?php if (!empty($template['body_html'])): ?><span class="badge text-bg-primary">HTML</span><?php endif; ?>
            <?php if (!empty($template['body_text']) || !empty($template['body'])): ?><span class="badge text-bg-secondary">Text</span><?php endif; ?>
            <span class="badge text-bg-light border">Stage <?= e($template['followup_stage']) ?></span>
            <span class="badge text-bg-<?= $template['active'] ? 'success' : 'secondary' ?>"><?= $template['active'] ? 'Active' : 'Inactive' ?></span>
          </div>
          <div class="d-grid gap-2">
            <a class="btn btn-outline-secondary" href="/templates/preview.php?id=<?= e($template['id']) ?>">Preview</a>
            <a class="btn btn-outline-primary" href="/templates/edit.php?id=<?= e($template['id']) ?>">Edit</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="table-responsive desktop-table-only">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Name</th><th>Category</th><th>Subject</th><th>Format</th><th>Stage</th><th>Active</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($templates as $template): ?>
        <tr>
          <td><?= e($template['name']) ?></td>
          <td><?= e($template['category']) ?></td>
          <td><?= e($template['subject']) ?></td>
          <td>
            <?php if (!empty($template['body_html'])): ?><span class="badge text-bg-primary">HTML</span><?php endif; ?>
            <?php if (!empty($template['body_text']) || !empty($template['body'])): ?><span class="badge text-bg-secondary">Text</span><?php endif; ?>
          </td>
          <td><?= e($template['followup_stage']) ?></td>
          <td><?= $template['active'] ? 'Yes' : 'No' ?></td>
          <td class="text-end d-flex justify-content-end gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="/templates/preview.php?id=<?= e($template['id']) ?>">Preview</a>
            <a class="btn btn-sm btn-outline-primary" href="/templates/edit.php?id=<?= e($template['id']) ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
