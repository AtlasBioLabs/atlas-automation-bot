<?php

declare(strict_types=1);

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/OutreachService.php';

function admin_nav_items(): array
{
    return [
        '/dashboard.php' => 'Dashboard',
        '/businesses/index.php' => 'Businesses',
        '/leads/index.php' => 'Leads',
        '/campaigns/index.php' => 'Campaigns',
        '/queue/index.php' => 'Email Queue',
        '/templates/index.php' => 'Templates',
        '/rfqs/index.php' => 'RFQs',
        '/reports/index.php' => 'Reports',
        '/settings.php' => 'Settings',
        '/tools/mail_diagnostics.php' => 'Diagnostics',
        '/logout.php' => 'Logout',
    ];
}

function current_request_path(): string
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}

function nav_link_active(string $href): bool
{
    $path = current_request_path();
    if ($href === '/logout.php') {
        return false;
    }

    return $path === $href;
}

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
    .card, .table, .form-control, .form-select, .form-control:focus, .form-select:focus { border-color: #d9dee6; }
    .stat-card { border-left: 4px solid var(--brand-accent); }
    .sidebar-link { color: #dfe7f2; text-decoration: none; display: block; padding: .7rem .8rem; border-radius: .55rem; font-weight: 500; }
    .sidebar-link:hover { background: rgba(255,255,255,.12); color: #fff; }
    .sidebar-link.active { background: rgba(255,255,255,.16); color: #fff; }
    .form-control, .form-select, .btn { min-height: 44px; }
    .btn-sm { min-height: 38px; }
    .admin-shell { max-width: 1600px; margin: 0 auto; }
    .content-wrap { min-width: 0; }
    .page-title-row { gap: .75rem; }
    .table-responsive { border-radius: 0 0 .75rem .75rem; }
    .mobile-card-list .card { border-color: #d9dee6; }
    .mobile-card-list .card + .card { margin-top: .85rem; }
    .mobile-meta { font-size: .78rem; color: #627387; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .25rem; }
    .mobile-actions .btn { width: 100%; }
    .nav-tabs-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .nav-tabs { flex-wrap: nowrap; min-width: max-content; }
    .nav-tabs .nav-link { white-space: nowrap; }
    .mobile-menu-toggle { border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.08); color: #fff; padding: .55rem .8rem; border-radius: .6rem; font-weight: 600; }
    .mobile-menu-panel { background: var(--brand-primary); border-top: 1px solid rgba(255,255,255,.08); }
    .mobile-menu-panel a { color: #dfe7f2; text-decoration: none; display: block; padding: .85rem 1rem; border-radius: .6rem; }
    .mobile-menu-panel a.active { background: rgba(255,255,255,.16); color: #fff; }
    .mobile-menu-panel a:hover { background: rgba(255,255,255,.12); color: #fff; }
    .mobile-menu-panel details summary::-webkit-details-marker { display: none; }
    .admin-summary-card { background: #fff; border: 1px solid #dce6f2; border-radius: 16px; padding: 1rem 1rem .95rem; }
    .admin-preview-meta { background: #f7fafd; border: 1px solid #dce6f2; border-radius: 14px; padding: 16px 18px; }
    @media (max-width: 767.98px) {
      body { font-size: 15px; }
      .container-fluid { padding-left: 0; padding-right: 0; }
      .page-main { padding: 1rem !important; }
      .page-title-row { margin-bottom: 1rem !important; }
      .card-body { padding: 1rem; }
      .table-responsive { display: none; }
      .desktop-table-only { display: none !important; }
      .mobile-card-list { display: block !important; }
      .action-stack > * { width: 100%; }
      .action-stack { display: grid !important; grid-template-columns: 1fr; }
    }
    @media (min-width: 768px) {
      .mobile-card-list { display: none !important; }
      .mobile-menu-shell { display: none !important; }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="/dashboard.php"><?= e($business['brand_name'] ?? 'Email Automation') ?></a>
    <?php if ($user): ?>
      <div class="d-none d-md-flex align-items-center gap-3">
        <form method="post" action="/businesses/switch.php" class="m-0" style="min-width:200px;">
          <?= csrf_field() ?>
          <select class="form-select form-select-sm" name="business_profile_id" onchange="this.form.submit()">
            <?php foreach ($businesses as $option): ?>
              <option value="<?= e($option['id']) ?>"<?= selected((string) $business['id'], (string) $option['id']) ?>><?= e($option['brand_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <div class="text-white small"><?= e($user['email']) ?> | <a class="text-white" href="/logout.php">Logout</a></div>
      </div>
      <div class="mobile-menu-shell d-md-none">
        <details>
          <summary class="mobile-menu-toggle list-unstyled">Menu</summary>
          <div class="mobile-menu-panel mt-2 rounded-3 p-2 shadow">
            <div class="px-2 pb-2 border-bottom border-light border-opacity-10 mb-2">
              <div class="small text-white-50">Current business</div>
              <div class="text-white fw-semibold"><?= e($business['brand_name'] ?? '') ?></div>
            </div>
            <?php foreach (admin_nav_items() as $href => $label): ?>
              <a href="<?= e($href) ?>" class="<?= nav_link_active($href) ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
          </div>
        </details>
      </div>
    <?php endif; ?>
  </div>
</nav>
<div class="container-fluid">
  <div class="admin-shell row min-vh-100 g-0">
    <?php if ($user): ?>
    <aside class="d-none d-md-block col-md-3 col-lg-2 p-3" style="background:<?= e($primary) ?>;">
      <?php foreach (admin_nav_items() as $href => $label): ?>
        <a class="sidebar-link<?= nav_link_active($href) ? ' active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </aside>
    <main class="content-wrap col-12 col-md-9 col-lg-10 p-4 page-main">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 page-title-row">
        <div>
          <div class="small text-muted">Current business</div>
          <div class="fw-semibold"><?= e($business['business_name']) ?></div>
        </div>
      </div>
    <?php else: ?>
    <main class="col-12 p-4 page-main">
    <?php endif; ?>
      <?php foreach ($flashes as $type => $message): ?>
        <div class="alert alert-<?= e($type === 'error' ? 'danger' : $type) ?>"><?= e($message) ?></div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between align-items-center mb-4 page-title-row">
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
        'cancelled' => 'warning',
        'pending', 'queued', 'new' => 'secondary',
        default => 'primary',
    };

    return '<span class="badge text-bg-' . $class . '">' . e($status) . '</span>';
}
