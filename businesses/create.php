<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$errors = [];
$business = [
    'business_name' => '',
    'brand_name' => '',
    'tagline' => '',
    'industry' => '',
    'sender_name' => '',
    'sender_email' => '',
    'reply_to_email' => '',
    'admin_notification_email' => '',
    'business_address' => '',
    'website_url' => '',
    'logo_url' => '',
    'primary_color' => '#0A1A2F',
    'secondary_color' => '#FFFFFF',
    'accent_color' => '#2E6BFF',
    'compliance_footer' => '',
    'default_signature' => '',
    'daily_send_limit' => '30',
    'timezone' => 'Africa/Douala',
    'active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require __DIR__ . '/save.php';
    if (!$errors) {
        flash('success', 'Business profile created.');
        redirect('/businesses/index.php');
    }
}

render_header('Create Business');
require __DIR__ . '/form.php';
render_footer();
