<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$rows = OutreachService::campaignReportRows($businessId);

render_header('Campaigns');
?>
<div class="d-grid d-md-flex gap-2 mb-3 action-stack">
  <a class="btn btn-primary" href="/queue/create.php">Create campaign</a>
  <a class="btn btn-outline-secondary" href="/queue/index.php">Open queue</a>
</div>
<div class="card shadow-sm">
  <div class="mobile-card-list p-3">
    <?php foreach ($rows as $row): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="mobile-meta">Campaign</div>
          <div class="fw-semibold mb-1"><?= e($row['name']) ?></div>
          <div class="d-flex flex-wrap gap-2 mb-2">
            <?= badge_status((string) $row['status']) ?>
            <span class="badge text-bg-light border"><?= e($row['audience_type']) ?></span>
          </div>
          <div class="small text-muted mb-1">Scheduled: <?= e(format_app_datetime($row['scheduled_at'], $businessId)) ?></div>
          <div class="small text-muted mb-3">Total <?= e($row['total_recipients']) ?> / Eligible <?= e($row['eligible_recipients']) ?> / Sent <?= e($row['sent_count']) ?></div>
          <div class="d-grid">
            <a class="btn btn-outline-primary" href="/campaigns/view.php?id=<?= e($row['id']) ?>">View Campaign</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="table-responsive desktop-table-only">
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
