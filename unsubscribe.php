<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Database.php';

$token = (string) ($_GET['token'] ?? '');
$businessId = (int) ($_GET['business_id'] ?? 0);
$message = 'This unsubscribe link is invalid or expired.';
if ($businessId > 0 && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = Database::pdo()->prepare('UPDATE leads SET unsubscribed = 1, status = "unsubscribed", updated_at = NOW() WHERE business_profile_id = ? AND unsubscribe_token = ?');
    $stmt->execute([$businessId, $token]);
    if ($stmt->rowCount() > 0) {
        $message = 'You have been unsubscribed from Atlas BioLabs outreach emails.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Unsubscribe - Atlas BioLabs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5">
    <div class="mx-auto card shadow-sm" style="max-width:560px">
      <div class="card-body p-4">
        <h1 class="h4">Atlas BioLabs</h1>
        <p class="mb-0"><?= e($message) ?></p>
      </div>
    </div>
  </main>
</body>
</html>
