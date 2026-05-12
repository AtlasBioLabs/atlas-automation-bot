<?php

declare(strict_types=1);

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/OutreachService.php';

function render_header(string $title): void
{
    start_app_session();
    $user = Auth::user();
    $business = $user ? BusinessProfile::current() : null;
    $businesses = $user ? BusinessProfile::all(false) : [];
    $flashes = flash();
    $primary = $business['primary_color'] ?? '#0A1A2F';
    $accent = $business['accent_color'] ?? '#2E6BFF';
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> - <?= e($business['brand_name'] ?? app_config('app_name')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --brand-primary: <?= e($primary) ?>; --brand-accent: <?= e($accent) ?>; --atlas-grey: #C7CED6; }
    body { background: #f6f8fb; color: #102033; }
    .navbar { background: var(--brand-primary); }
    .btn-primary { background: var(--brand-accent); border-color: var(--brand-accent); }
    .card, .table, .form-control, .form-select { border-color: #d9dee6; }
    .stat-card { border-left: 4px solid var(--brand-accent); }
    .sidebar-link { color: #dfe7f2; text-decoration: none; display: block; padding: .55rem .75rem; border-radius: .4rem; }
    .sidebar-link:hover { background: rgba(255,255,255,.12); color: #fff; }
  </style>
</head>
<body>
<nav class="navbar navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="/dashboard.php"><?= e($business['brand_name'] ?? 'Email Automation') ?></a>
    <?php if ($user): ?>
      <div class="d-flex align-items-center gap-3">
        <form method="post" action="/businesses/switch.php" class="m-0">
          <?= csrf_field() ?>
          <select class="form-select form-select-sm" name="business_profile_id" onchange="this.form.submit()">
            <?php foreach ($businesses as $option): ?>
              <option value="<?= e($option['id']) ?>"<?= selected((string) $business['id'], (string) $option['id']) ?>><?= e($option['brand_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <div class="text-white small"><?= e($user['email']) ?> | <a class="text-white" href="/logout.php">Logout</a></div>
      </div>
    <?php endif; ?>
  </div>
</nav>
<div class="container-fluid">
  <div class="row min-vh-100">
    <?php if ($user): ?>
    <aside class="col-md-3 col-lg-2 p-3" style="background:<?= e($primary) ?>;">
      <a class="sidebar-link" href="/dashboard.php">Dashboard</a>
      <a class="sidebar-link" href="/businesses/index.php">Businesses</a>
      <a class="sidebar-link" href="/leads/index.php">Leads</a>
      <a class="sidebar-link" href="/leads/import.php">CSV Import</a>
      <a class="sidebar-link" href="/templates/index.php">Templates</a>
      <a class="sidebar-link" href="/queue/index.php">Email Queue</a>
      <a class="sidebar-link" href="/rfqs/index.php">RFQs</a>
      <a class="sidebar-link" href="/reports/index.php">Reports</a>
      <a class="sidebar-link" href="/settings.php">Settings</a>
    </aside>
    <main class="col-md-9 col-lg-10 p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <div class="small text-muted">Current business</div>
          <div class="fw-semibold"><?= e($business['business_name']) ?></div>
        </div>
      </div>
    <?php else: ?>
    <main class="col-12 p-4">
    <?php endif; ?>
      <?php foreach ($flashes as $type => $message): ?>
        <div class="alert alert-<?= e($type === 'error' ? 'danger' : $type) ?>"><?= e($message) ?></div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= e($title) ?></h1>
      </div>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
  </div>
</div>
</body>
</html>
    <?php
}

function badge_status(string $status): string
{
    $class = match ($status) {
        'sent', 'customer', 'interested', 'quoted' => 'success',
        'failed', 'bounced', 'unsubscribed', 'not_interested', 'complained', 'invalid' => 'danger',
        'pending', 'queued', 'new' => 'secondary',
        default => 'primary',
    };

    return '<span class="badge text-bg-' . $class . '">' . e($status) . '</span>';
}
