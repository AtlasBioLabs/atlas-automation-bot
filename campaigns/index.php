<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$rows = OutreachService::campaignReportRows($businessId);

render_header('Campaigns');
?>
<div class="d-flex gap-2 mb-3">
  <a class="btn btn-primary" href="/queue/create.php">Create campaign</a>
  <a class="btn btn-outline-secondary" href="/queue/index.php">Open queue</a>
</div>
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Name</th><th>Status</th><th>Audience</th><th>Scheduled</th><th>Total</th><th>Eligible</th><th>Skipped</th><th>Sent</th><th>Failed</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['name']) ?></td>
          <td><?= badge_status((string) $row['status']) ?></td>
          <td><?= e($row['audience_type']) ?></td>
          <td><?= e(format_app_datetime($row['scheduled_at'], $businessId)) ?></td>
          <td><?= e($row['total_recipients']) ?></td>
          <td><?= e($row['eligible_recipients']) ?></td>
          <td><?= e($row['skipped_recipients']) ?></td>
          <td><?= e($row['sent_count']) ?></td>
          <td><?= e($row['failed_count']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/campaigns/view.php?id=<?= e($row['id']) ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
