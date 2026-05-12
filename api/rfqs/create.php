<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/RfqService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['POST requests only.']]);
    exit;
}

$providedToken = (string) ($_SERVER['HTTP_X_ATLAS_RFQ_TOKEN'] ?? '');
if (!RfqService::tokenIsValid($providedToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'errors' => ['Invalid or missing RFQ API token.']]);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!RateLimiter::hit('rfq_api', $ip, 30, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'errors' => ['Too many RFQ API requests.']]);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Invalid JSON payload.']]);
    exit;
}

$businessId = (int) ($input['business_id'] ?? $_GET['business_id'] ?? 0);
$business = $businessId > 0 ? BusinessProfile::find($businessId) : BusinessProfile::firstActive();
if (!$business || !(bool) $business['active']) {
    http_response_code(503);
    echo json_encode(['success' => false, 'errors' => ['No active business profile is available.']]);
    exit;
}
$business = Settings::effectiveBusiness((int) $business['id']) ?? $business;

$result = RfqService::submit($input, $business);
http_response_code($result['success'] ? 201 : 422);
echo json_encode([
    'success' => (bool) $result['success'],
    'errors' => $result['errors'],
    'rfq_id' => $result['rfq_id'] ?? null,
    'lead_id' => $result['lead_id'] ?? null,
]);
