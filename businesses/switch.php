<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Auth.php';

Auth::require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard.php');
}

verify_csrf();
$id = (int) ($_POST['business_profile_id'] ?? 0);
$switched = BusinessProfile::switchTo($id);
flash($switched ? 'success' : 'error', $switched ? 'Business profile switched.' : 'Unable to switch to that profile.');

$back = $_SERVER['HTTP_REFERER'] ?? '/dashboard.php';
redirect(str_starts_with($back, app_config('app_url')) || str_starts_with($back, '/') ? $back : '/dashboard.php');
