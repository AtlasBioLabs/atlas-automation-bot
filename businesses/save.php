<?php

$fields = [
    'business_name', 'brand_name', 'tagline', 'industry', 'sender_name', 'sender_email', 'reply_to_email',
    'admin_notification_email', 'business_address', 'website_url', 'logo_url', 'primary_color', 'secondary_color',
    'accent_color', 'compliance_footer', 'default_signature', 'daily_send_limit', 'timezone',
];

foreach ($fields as $field) {
    $business[$field] = trim((string) ($_POST[$field] ?? ''));
}
$business['active'] = !empty($_POST['active']) ? 1 : 0;
$business['daily_send_limit'] = max(1, (int) $business['daily_send_limit']);
$leadCategories = trim((string) ($_POST['lead_categories'] ?? implode("\n", default_lead_categories())));
$followup1Days = max(1, (int) ($_POST['followup_1_days'] ?? app_config('followup_1_days', 3)));
$followup2Days = max(1, (int) ($_POST['followup_2_days'] ?? app_config('followup_2_days', 7)));

foreach (['business_name', 'brand_name', 'sender_name', 'sender_email', 'business_address', 'compliance_footer', 'default_signature'] as $required) {
    if ($business[$required] === '') {
        $errors[] = str_replace('_', ' ', ucfirst($required)) . ' is required.';
    }
}

foreach (['sender_email', 'reply_to_email', 'admin_notification_email'] as $emailField) {
    if ($business[$emailField] !== '' && !filter_var($business[$emailField], FILTER_VALIDATE_EMAIL)) {
        $errors[] = str_replace('_', ' ', ucfirst($emailField)) . ' must be a valid email.';
    }
}

if (!valid_timezone_name($business['timezone'])) {
    $errors[] = 'Timezone must be a valid IANA timezone.';
}

foreach (['primary_color', 'secondary_color', 'accent_color'] as $colorField) {
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $business[$colorField])) {
        $errors[] = str_replace('_', ' ', ucfirst($colorField)) . ' must be a hex color.';
    }
}

if (!empty($business['id']) && !$business['active']) {
    $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM business_profiles WHERE active = 1 AND id <> ?');
    $stmt->execute([(int) $business['id']]);
    if ((int) $stmt->fetchColumn() === 0) {
        $errors[] = 'At least one active business profile is required.';
    }
}

if (!$errors) {
    if (!empty($business['id'])) {
        $stmt = Database::pdo()->prepare(
            'UPDATE business_profiles
             SET business_name = ?, brand_name = ?, tagline = ?, industry = ?, sender_name = ?, sender_email = ?, reply_to_email = ?,
                 admin_notification_email = ?, business_address = ?, website_url = ?, logo_url = ?, primary_color = ?, secondary_color = ?,
                 accent_color = ?, compliance_footer = ?, default_signature = ?, daily_send_limit = ?, timezone = ?, active = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $business['business_name'], $business['brand_name'], $business['tagline'], $business['industry'], $business['sender_name'],
            $business['sender_email'], $business['reply_to_email'], $business['admin_notification_email'], $business['business_address'],
            $business['website_url'], $business['logo_url'], $business['primary_color'], $business['secondary_color'], $business['accent_color'],
            $business['compliance_footer'], $business['default_signature'], $business['daily_send_limit'], $business['timezone'],
            $business['active'], $business['id'],
        ]);
        $savedBusinessId = (int) $business['id'];
    } else {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO business_profiles
             (business_name, brand_name, tagline, industry, sender_name, sender_email, reply_to_email, admin_notification_email,
              business_address, website_url, logo_url, primary_color, secondary_color, accent_color, compliance_footer,
              default_signature, daily_send_limit, timezone, active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $business['business_name'], $business['brand_name'], $business['tagline'], $business['industry'], $business['sender_name'],
            $business['sender_email'], $business['reply_to_email'], $business['admin_notification_email'], $business['business_address'],
            $business['website_url'], $business['logo_url'], $business['primary_color'], $business['secondary_color'], $business['accent_color'],
            $business['compliance_footer'], $business['default_signature'], $business['daily_send_limit'], $business['timezone'], $business['active'],
        ]);
        $savedBusinessId = (int) Database::pdo()->lastInsertId();
        require_once __DIR__ . '/../app/TemplateSeeder.php';
        TemplateSeeder::seedGeneric($savedBusinessId);
    }

    Settings::set('lead_categories', $leadCategories, $savedBusinessId);
    Settings::set('followup_1_days', (string) $followup1Days, $savedBusinessId);
    Settings::set('followup_2_days', (string) $followup2Days, $savedBusinessId);
}
