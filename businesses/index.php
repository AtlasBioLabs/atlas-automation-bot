<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businesses = BusinessProfile::all(true);

render_header('Business Profiles');
?>
<div class="mb-3"><a class="btn btn-primary" href="/businesses/create.php">New business</a></div>
<div class="card shadow-sm">
  <div class="mobile-card-list p-3">
    <?php foreach ($businesses as $business): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="mobile-meta">Business Profile</div>
          <div class="fw-semibold mb-1"><?= e($business['business_name']) ?></div>
          <div class="small text-muted mb-2"><?= e($business['brand_name']) ?></div>
          <div class="small text-break mb-2"><?= e($business['sender_name']) ?> &lt;<?= e($business['sender_email']) ?>&gt;</div>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <?= $business['active'] ? badge_status('active') : badge_status('inactive') ?>
            <span class="badge text-bg-light border">Daily limit <?= e($business['daily_send_limit']) ?></span>
          </div>
          <div class="d-grid gap-2">
            <?php if ($business['active']): ?>
              <form method="post" action="/businesses/switch.php">
                <?= csrf_field() ?><input type="hidden" name="business_profile_id" value="<?= e($business['id']) ?>">
                <button class="btn btn-outline-primary w-100">Switch</button>
              </form>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="/businesses/edit.php?id=<?= e($business['id']) ?>">Edit</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="table-responsive desktop-table-only">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Business</th><th>Brand</th><th>Sender</th><th>Daily limit</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($businesses as $business): ?>
          <tr>
            <td><?= e($business['business_name']) ?></td>
            <td><?= e($business['brand_name']) ?></td>
            <td><?= e($business['sender_name']) ?> &lt;<?= e($business['sender_email']) ?>&gt;</td>
            <td><?= e($business['daily_send_limit']) ?></td>
            <td><?= $business['active'] ? badge_status('active') : badge_status('inactive') ?></td>
            <td class="text-end">
              <?php if ($business['active']): ?>
                <form class="d-inline" method="post" action="/businesses/switch.php">
                  <?= csrf_field() ?><input type="hidden" name="business_profile_id" value="<?= e($business['id']) ?>">
                  <button class="btn btn-sm btn-outline-primary">Switch</button>
                </form>
              <?php endif; ?>
              <a class="btn btn-sm btn-outline-secondary" href="/businesses/edit.php?id=<?= e($business['id']) ?>">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
