<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$data = OutreachService::dailyReportData($businessId);
$campaignRows = OutreachService::campaignReportRows($businessId);
$rfqSources = OutreachService::rfqsBySource($businessId);
$interestedFromRfqs = Database::pdo()->prepare('SELECT COUNT(*) FROM leads WHERE business_profile_id = ? AND status = "interested" AND source = "website_rfq"');
$interestedFromRfqs->execute([$businessId]);

render_header('Reports');
?>
<div class="row g-3">
  <?php foreach ($data as $label => $value): ?>
    <div class="col-sm-6 col-xl-3">
      <div class="card stat-card shadow-sm">
        <div class="card-body">
          <div class="small text-muted"><?= e(str_replace('_', ' ', ucwords($label, '_'))) ?></div>
          <div class="fs-3 fw-semibold"><?= e($value) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card shadow-sm">
      <div class="card-body">
        <div class="small text-muted">Interested leads from RFQs</div>
        <div class="fs-3 fw-semibold"><?= e((int) $interestedFromRfqs->fetchColumn()) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">Campaign Performance</h2>
      </div>
      <div class="mobile-card-list p-3">
        <?php foreach ($campaignRows as $campaign): ?>
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="mobile-meta">Campaign</div>
              <div class="fw-semibold mb-1"><?= e($campaign['name']) ?></div>
              <div class="d-flex flex-wrap gap-2 mb-2">
                <?= badge_status($campaign['status']) ?>
                <span class="badge text-bg-light border"><?= e($campaign['audience_type']) ?></span>
              </div>
              <div class="small text-muted mb-1">Scheduled: <?= e(format_app_datetime($campaign['scheduled_at'], $businessId)) ?></div>
              <div class="small text-muted">Total <?= e($campaign['total_recipients']) ?> / Eligible <?= e($campaign['eligible_recipients']) ?> / Sent <?= e($campaign['sent_count']) ?> / Failed <?= e($campaign['failed_count']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$campaignRows): ?><div class="text-muted">No campaigns yet.</div><?php endif; ?>
      </div>
      <div class="table-responsive desktop-table-only">
        <table class="table mb-0 align-middle">
          <thead><tr><th>Campaign</th><th>Status</th><th>Audience</th><th>Scheduled</th><th>Total</th><th>Eligible</th><th>Skipped</th><th>Pending</th><th>Sent</th><th>Failed</th></tr></thead>
          <tbody>
          <?php foreach ($campaignRows as $campaign): ?>
            <tr>
              <td><?= e($campaign['name']) ?></td>
              <td><?= badge_status($campaign['status']) ?></td>
              <td><?= e($campaign['audience_type']) ?></td>
              <td><?= e(format_app_datetime($campaign['scheduled_at'], $businessId)) ?></td>
              <td><?= e($campaign['total_recipients']) ?></td>
              <td><?= e($campaign['eligible_recipients']) ?></td>
              <td><?= e((int) $campaign['skipped_recipients'] + (int) $campaign['skipped_queue_count']) ?></td>
              <td><?= e($campaign['pending_count']) ?></td>
              <td><?= e($campaign['sent_count']) ?></td>
              <td><?= e($campaign['failed_count']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$campaignRows): ?><tr><td colspan="10" class="text-muted">No campaigns yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">RFQs by Source</h2>
        <?php if ($rfqSources): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($rfqSources as $source): ?>
              <li class="list-group-item d-flex justify-content-between px-0"><span><?= e($source['source']) ?></span><strong><?= e($source['total']) ?></strong></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted mb-0">No RFQs yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
