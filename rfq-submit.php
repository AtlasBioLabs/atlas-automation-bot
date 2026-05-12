<?php

declare(strict_types=1);

require_once __DIR__ . '/app/RfqService.php';

$errors = [];
$success = false;
$data = ['name' => '', 'company' => '', 'email' => '', 'phone' => '', 'country' => '', 'product_interest' => '', 'estimated_quantity' => '', 'message' => '', 'source' => 'website_rfq'];
$business = BusinessProfile::publicFromRequest();
if (!$business) {
    http_response_code(503);
    exit('No active business profile is available.');
}
$businessId = (int) $business['id'];
$contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
$providedToken = (string) ($_SERVER['HTTP_X_ATLAS_RFQ_TOKEN'] ?? '');
$isApiRequest = str_contains($contentType, 'application/json') || $providedToken !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = ['lead_id' => null, 'rfq_id' => null];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!RateLimiter::hit('rfq_submit:' . $businessId, $ip, 5, 3600)) {
        $errors[] = 'Too many RFQ submissions. Please try again later.';
    }

    $input = $_POST;
    if ($isApiRequest) {
        if (!RfqService::tokenIsValid($providedToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => ['Invalid or missing RFQ API token.']]);
            exit;
        }
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        $input = is_array($decoded) ? $decoded : [];
    }
    $input['source'] = trim((string) ($input['source'] ?? 'website_rfq')) ?: 'website_rfq';
    foreach ($data as $key => $value) {
        $data[$key] = trim((string) ($input[$key] ?? $value));
    }

    if (!$errors) {
        $result = RfqService::submit($input, $business);
        $data = $result['data'];
        $errors = $result['errors'];
        $success = (bool) $result['success'];
    }

    if ($isApiRequest) {
        http_response_code($success ? 201 : 422);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'errors' => $errors, 'lead_id' => $result['lead_id'] ?? null, 'rfq_id' => $result['rfq_id'] ?? null]);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RFQ - <?= e($business['brand_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
  <div class="mx-auto card shadow-sm" style="max-width:760px">
    <div class="card-body p-4">
      <h1 class="h4 mb-3"><?= e($business['brand_name']) ?> RFQ</h1>
      <?php if ($success): ?>
        <div class="alert alert-success">Your RFQ has been received. <?= e($business['brand_name']) ?> will review the request and follow up.</div>
      <?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
      <form method="post" class="row g-3">
        <input type="hidden" name="business_id" value="<?= e($businessId) ?>">
        <?php foreach (['name' => 'Name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'country' => 'Country', 'product_interest' => 'Product interest', 'estimated_quantity' => 'Estimated quantity'] as $key => $label): ?>
          <div class="col-md-6"><label class="form-label"><?= e($label) ?></label><input class="form-control" name="<?= e($key) ?>" value="<?= e($data[$key]) ?>"<?= in_array($key, ['name', 'company', 'email', 'product_interest'], true) ? ' required' : '' ?>></div>
        <?php endforeach; ?>
        <input type="hidden" name="source" value="website_rfq">
        <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5"><?= e($data['message']) ?></textarea></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Submit RFQ</button></div>
      </form>
    </div>
  </div>
</main>
</body>
</html>
