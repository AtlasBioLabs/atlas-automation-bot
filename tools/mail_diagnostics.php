<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();

$businessId = BusinessProfile::currentId();
$business = Settings::effectiveBusiness($businessId) ?? BusinessProfile::current();
$provider = strtolower((string) Settings::option('MAIL_PROVIDER', 'log', $businessId));
$allowedProviders = ['log', 'smtp', 'brevo_api'];
$accountDiagnosticResult = null;
$sendDiagnosticResult = null;
$diagnosticEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'test_brevo_connection') {
        $accountDiagnosticResult = Mailer::testBrevoConnection();
    } elseif ($action === 'send_brevo_diagnostic_email') {
        $diagnosticEmail = trim((string) ($_POST['diagnostic_email'] ?? ''));
        if (!filter_var($diagnosticEmail, FILTER_VALIDATE_EMAIL)) {
            $sendDiagnosticResult = [
                'sent' => false,
                'provider' => $provider,
                'status' => null,
                'error' => 'Enter a valid diagnostic recipient email address.',
                'reference' => null,
                'sender_email' => (string) $business['sender_email'],
                'recipient_email' => $diagnosticEmail,
                'business_profile_id' => $businessId,
                'business_name' => (string) $business['brand_name'],
                'curl_error' => null,
            ];
        } else {
            $sendDiagnosticResult = Mailer::sendBrevoDiagnosticEmail($diagnosticEmail, $business);
        }
    }
}

$mailApiKeyExists = trim((string) app_config('mail_api_key', '')) !== '';
$rfqApiTokenExists = trim((string) app_config('rfq_api_token', '')) !== '';
$atlasBotTokenExists = trim((string) app_config('atlas_bot_api_token', '')) !== '';
$curlEnabled = extension_loaded('curl') && function_exists('curl_init');
$providerValid = in_array($provider, $allowedProviders, true);
$replyTo = trim((string) $business['reply_to_email']);
$serverTimezone = date_default_timezone_get();
$businessTimezone = app_timezone($businessId);
$serverNow = new DateTimeImmutable('now');
$utcNow = app_utc_now();
$localNow = app_now($businessId);
$nextQueueStmt = Database::pdo()->prepare('SELECT scheduled_at FROM email_queue WHERE business_profile_id = ? AND status = "pending" ORDER BY scheduled_at ASC LIMIT 1');
$nextQueueStmt->execute([$businessId]);
$nextQueuedAt = $nextQueueStmt->fetchColumn();

render_header('Mail Diagnostics');
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3">Current Mail Configuration</h2>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <tbody>
              <tr><th scope="row" style="width:32%">Active business profile</th><td><?= e($business['brand_name']) ?> (#<?= e((string) $business['id']) ?>)</td></tr>
              <tr><th scope="row">MAIL_PROVIDER</th><td><?= e($provider) ?></td></tr>
              <tr><th scope="row">MAIL_FROM_NAME</th><td><?= e((string) $business['sender_name']) ?></td></tr>
              <tr><th scope="row">MAIL_FROM_EMAIL</th><td><?= e((string) $business['sender_email']) ?></td></tr>
              <tr><th scope="row">MAIL_REPLY_TO</th><td><?= e($replyTo !== '' ? $replyTo : '(empty)') ?></td></tr>
              <tr><th scope="row">MAIL_API_KEY in env</th><td><?= $mailApiKeyExists ? 'yes' : 'no' ?></td></tr>
              <tr><th scope="row">APP_URL</th><td><?= e((string) app_config('app_url', '')) ?></td></tr>
              <tr><th scope="row">PHP cURL extension</th><td><?= $curlEnabled ? 'enabled' : 'missing' ?></td></tr>
              <tr><th scope="row">Selected provider valid</th><td><?= $providerValid ? 'yes' : 'no' ?></td></tr>
              <tr><th scope="row">Server timezone</th><td><?= e($serverTimezone) ?></td></tr>
              <tr><th scope="row">Selected business timezone</th><td><?= e($businessTimezone) ?></td></tr>
              <tr><th scope="row">Current server time</th><td><?= e($serverNow->format('Y-m-d H:i:s')) ?></td></tr>
              <tr><th scope="row">Current business local time</th><td><?= e($localNow->format('Y-m-d H:i:s')) ?></td></tr>
              <tr><th scope="row">Current UTC time</th><td><?= e($utcNow->format('Y-m-d H:i:s')) ?></td></tr>
              <tr><th scope="row">Next queued email time</th><td><?= e($nextQueuedAt ? format_app_datetime((string) $nextQueuedAt, $businessId) : 'No pending queue items') ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h5 mb-3">Environment Checks</h2>
        <ul class="list-unstyled small mb-3">
          <li class="mb-2"><strong>MAIL_API_KEY:</strong> <?= $mailApiKeyExists ? 'present' : 'missing' ?></li>
          <li class="mb-2"><strong>RFQ_API_TOKEN:</strong> <?= $rfqApiTokenExists ? 'present' : 'missing' ?></li>
          <li class="mb-2"><strong>ATLAS_BOT_API_TOKEN:</strong> <?= $atlasBotTokenExists ? 'present' : 'missing' ?></li>
        </ul>
        <div class="alert alert-info small mb-0">
          Secrets are checked for presence only. Their values are never shown on this page.
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <div>
            <h2 class="h5 mb-1">Brevo API Connectivity Test</h2>
            <div class="text-muted small">Calls <code>GET /v3/account</code> with the configured <code>MAIL_API_KEY</code>. This does not send a campaign or queue any emails.</div>
          </div>
          <form method="post" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn-outline-primary" type="submit" name="action" value="test_brevo_connection">Test Brevo API Connection</button>
          </form>
        </div>

        <?php if ($accountDiagnosticResult !== null): ?>
          <div class="alert alert-<?= $accountDiagnosticResult['success'] ? 'success' : 'danger' ?> mb-0">
            <div><strong>Result:</strong> <?= $accountDiagnosticResult['success'] ? 'success' : 'failure' ?></div>
            <div><strong>HTTP status:</strong> <?= e($accountDiagnosticResult['status'] !== null ? (string) $accountDiagnosticResult['status'] : 'n/a') ?></div>
            <div><strong>Message:</strong> <?= e((string) $accountDiagnosticResult['message']) ?></div>
          </div>
        <?php else: ?>
          <div class="alert alert-warning mb-0 small">
            Use this after setting <code>MAIL_PROVIDER=brevo_api</code> and adding <code>MAIL_API_KEY</code> in Railway. If SMTP is blocked on Railway, this is the fastest way to tell whether Brevo credentials are healthy.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <div>
            <h2 class="h5 mb-1">Send Brevo Diagnostic Email</h2>
            <div class="text-muted small">Calls the real <code>POST /v3/smtp/email</code> endpoint with the active business sender settings. This sends one direct diagnostic email only and does not touch the queue.</div>
          </div>
        </div>

        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <div class="col-md-8">
            <label class="form-label">Recipient email</label>
            <input class="form-control" type="email" name="diagnostic_email" value="<?= e($diagnosticEmail) ?>" placeholder="you@example.com" required>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-outline-primary w-100" type="submit" name="action" value="send_brevo_diagnostic_email">Send Brevo Diagnostic Email</button>
          </div>
        </form>

        <?php if ($sendDiagnosticResult !== null): ?>
          <div class="alert alert-<?= !empty($sendDiagnosticResult['sent']) ? 'success' : 'danger' ?> mt-3 mb-0">
            <div><strong>Result:</strong> <?= !empty($sendDiagnosticResult['sent']) ? 'success' : 'failure' ?></div>
            <div><strong>HTTP status:</strong> <?= e(($sendDiagnosticResult['status'] ?? null) !== null ? (string) $sendDiagnosticResult['status'] : 'n/a') ?></div>
            <div><strong>Provider:</strong> <?= e((string) ($sendDiagnosticResult['provider'] ?? $provider)) ?></div>
            <div><strong>Message:</strong> <?= e((string) ($sendDiagnosticResult['error'] ?: ($sendDiagnosticResult['response_message'] ?? 'OK'))) ?></div>
            <?php if (!empty($sendDiagnosticResult['reference'])): ?><div><strong>Brevo message ID:</strong> <?= e((string) $sendDiagnosticResult['reference']) ?></div><?php endif; ?>
            <?php if (!empty($sendDiagnosticResult['curl_error'])): ?><div><strong>cURL error:</strong> <?= e((string) $sendDiagnosticResult['curl_error']) ?></div><?php endif; ?>
            <div><strong>Sender email used:</strong> <?= e((string) ($sendDiagnosticResult['sender_email'] ?? $business['sender_email'])) ?></div>
            <div><strong>Recipient email used:</strong> <?= e((string) ($sendDiagnosticResult['recipient_email'] ?? $diagnosticEmail)) ?></div>
            <div><strong>Active business profile:</strong> <?= e((string) ($sendDiagnosticResult['business_name'] ?? $business['brand_name'])) ?> (#<?= e((string) ($sendDiagnosticResult['business_profile_id'] ?? $businessId)) ?>)</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3">Notes</h2>
        <ul class="mb-0">
          <li>The Settings page test email uses the same <code>Mailer::send()</code> provider logic as queued campaign sends.</li>
          <li><code>MAIL_PROVIDER=log</code> still writes files to <code>storage/mail</code>.</li>
          <li><code>MAIL_PROVIDER=smtp</code> is still available for environments where outbound SMTP is allowed.</li>
          <li><code>MAIL_PROVIDER=brevo_api</code> uses Brevo HTTPS API and is recommended on Railway.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
