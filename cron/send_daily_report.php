<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/OutreachService.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$businesses = [];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--business=')) {
        $business = BusinessProfile::find((int) substr($arg, 11));
        if ($business) {
            $businesses[] = $business;
        }
    }
}
if (!$businesses) {
    $businesses = BusinessProfile::all(false);
}

$results = [];
foreach ($businesses as $business) {
    $data = OutreachService::dailyReportData((int) $business['id']);
    $lines = [$business['brand_name'] . ' daily outreach report', ''];
    foreach ($data as $label => $value) {
        $lines[] = str_replace('_', ' ', ucwords($label, '_')) . ': ' . $value;
    }

    $adminEmail = (string) ($business['admin_notification_email'] ?: app_config('admin_email'));
    $result = Mailer::send(
        $adminEmail,
        $business['brand_name'] . ' Admin',
        $business['brand_name'] . ' Daily Outreach Report',
        implode("\n", $lines),
        $business
    );
    $results[(int) $business['id']] = ['sent' => $result['sent'], 'error' => $result['error'], 'data' => $data];
}

echo json_encode(['businesses' => $results], JSON_PRETTY_PRINT) . PHP_EOL;
