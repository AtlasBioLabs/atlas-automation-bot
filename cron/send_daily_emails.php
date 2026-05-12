<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/OutreachService.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$businessId = null;
$allBusinesses = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--business=')) {
        $businessId = (int) substr($arg, 11);
    } elseif ($arg === '--all-businesses') {
        $allBusinesses = true;
    }
}

$stats = OutreachService::processDaily($allBusinesses ? null : $businessId);
echo json_encode($stats, JSON_PRETTY_PRINT) . PHP_EOL;
