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
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Name</th><th>Category</th><th>Subject</th><th>Stage</th><th>Active</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($templates as $template): ?>
        <tr>
          <td><?= e($template['name']) ?></td>
          <td><?= e($template['category']) ?></td>
          <td><?= e($template['subject']) ?></td>
          <td><?= e($template['followup_stage']) ?></td>
          <td><?= $template['active'] ? 'Yes' : 'No' ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/templates/edit.php?id=<?= e($template['id']) ?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
