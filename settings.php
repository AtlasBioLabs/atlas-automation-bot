<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/SettingsValidator.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$business = Settings::effectiveBusiness($businessId) ?? BusinessProfile::current();
$errors = [];
$testErrors = [];
$testEmail = '';
$testResult = null;
$businessWarnings = business_profile_preview_warnings($business);

$fields = [
    'APP_NAME' => $business['brand_name'],
    'BUSINESS_NAME' => $business['business_name'],
    'BUSINESS_TAGLINE' => $business['tagline'],
    'MAIL_PROVIDER' => Settings::option('MAIL_PROVIDER', 'log', $businessId),
    'MAIL_FROM_NAME' => $business['sender_name'],
    'MAIL_FROM_EMAIL' => $business['sender_email'],
    'MAIL_REPLY_TO' => $business['reply_to_email'],
    'MAIL_SMTP_HOST' => Settings::option('MAIL_SMTP_HOST', '', $businessId),
    'MAIL_SMTP_PORT' => Settings::option('MAIL_SMTP_PORT', '587', $businessId),
    'MAIL_SMTP_USER' => Settings::option('MAIL_SMTP_USER', '', $businessId),
    'ADMIN_EMAIL' => $business['admin_notification_email'],
    'BUSINESS_ADDRESS' => $business['business_address'],
    'WEBSITE_URL' => $business['website_url'] ?? '',
    'COMPANY_PROFILE_URL' => $business['company_profile_url'] ?? '',
    'TIMEZONE' => $business['timezone'] ?: app_config('app_timezone', 'Africa/Douala'),
    'DAILY_SEND_LIMIT' => (string) $business['daily_send_limit'],
    'FOLLOWUP_1_DAYS' => Settings::option('FOLLOWUP_1_DAYS', Settings::option('followup_1_days', '3', $businessId), $businessId),
    'FOLLOWUP_2_DAYS' => Settings::option('FOLLOWUP_2_DAYS', Settings::option('followup_2_days', '7', $businessId), $businessId),
    'DEFAULT_SIGNATURE' => $business['default_signature'],
    'COMPLIANCE_FOOTER' => $business['compliance_footer'],
    'UNSUBSCRIBE_FOOTER_TEXT' => Settings::option('UNSUBSCRIBE_FOOTER_TEXT', 'You can unsubscribe using the link included in this email.', $businessId),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save_settings');
    if ($action === 'send_test_email') {
        $testEmail = trim((string) ($_POST['test_email'] ?? ''));
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $testErrors[] = 'Enter a valid test email address.';
        } else {
            $provider = strtolower((string) Settings::option('MAIL_PROVIDER', 'log', $businessId));
            $result = Mailer::send(
                $testEmail,
                'Settings Test',
                ($business['brand_name'] ?: 'Email Automation') . ' provider test',
                "This is a one-off provider connectivity test for {$business['brand_name']}.\n\nProvider: {$provider}\nBusiness profile ID: {$businessId}\n\nNo campaign or queue rows were sent by this test.",
                $business
            );
            $testResult = $result;
            if (!$result['sent']) {
                $testErrors[] = (string) $result['error'];
            }
        }
    } else {
        foreach ($fields as $key => $value) {
            $fields[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        $fields['MAIL_PROVIDER'] = strtolower($fields['MAIL_PROVIDER']);

        $errors = SettingsValidator::validate($fields);

        if (!$errors) {
            foreach ($fields as $key => $value) {
                Settings::setOption($key, $value, $businessId);
            }
            Settings::set('followup_1_days', $fields['FOLLOWUP_1_DAYS'], $businessId);
            Settings::set('followup_2_days', $fields['FOLLOWUP_2_DAYS'], $businessId);

            $stmt = Database::pdo()->prepare(
                'UPDATE business_profiles
                 SET business_name = ?, brand_name = ?, tagline = ?, sender_name = ?, sender_email = ?, reply_to_email = ?,
                     admin_notification_email = ?, business_address = ?, website_url = ?, company_profile_url = ?, compliance_footer = ?, default_signature = ?,
                     daily_send_limit = ?, timezone = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([
                $fields['BUSINESS_NAME'],
                $fields['APP_NAME'],
                $fields['BUSINESS_TAGLINE'],
                $fields['MAIL_FROM_NAME'],
                $fields['MAIL_FROM_EMAIL'],
                $fields['MAIL_REPLY_TO'],
                $fields['ADMIN_EMAIL'],
                $fields['BUSINESS_ADDRESS'],
                $fields['WEBSITE_URL'],
                $fields['COMPANY_PROFILE_URL'],
                $fields['COMPLIANCE_FOOTER'],
                $fields['DEFAULT_SIGNATURE'],
                (int) $fields['DAILY_SEND_LIMIT'],
                $fields['TIMEZONE'],
                $businessId,
            ]);

            flash('success', 'Settings updated for the current business profile.');
            redirect('/settings.php');
        }
    }
}

render_header('Settings');
?>
<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<?php if ($businessWarnings): ?>
  <div class="alert alert-warning small">
    <strong>Business profile warnings:</strong> <?= e(implode(' ', $businessWarnings)) ?>
    Review these values here or in the active business profile record before sending live email.
  </div>
<?php endif; ?>
<form method="post" class="row g-4">
  <?= csrf_field() ?>
  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body row g-3">
      <h2 class="h5">Business Profile</h2>
      <div class="col-md-4"><label class="form-label">App / brand name</label><input class="form-control" name="APP_NAME" value="<?= e($fields['APP_NAME']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Business name</label><input class="form-control" name="BUSINESS_NAME" value="<?= e($fields['BUSINESS_NAME']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Business tagline</label><input class="form-control" name="BUSINESS_TAGLINE" value="<?= e($fields['BUSINESS_TAGLINE']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Website URL</label><input class="form-control" type="url" name="WEBSITE_URL" value="<?= e($fields['WEBSITE_URL']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Company profile URL</label><input class="form-control" type="url" name="COMPANY_PROFILE_URL" value="<?= e($fields['COMPANY_PROFILE_URL']) ?>"></div>
      <div class="col-12"><label class="form-label">Business address</label><textarea class="form-control" name="BUSINESS_ADDRESS" rows="3" required><?= e($fields['BUSINESS_ADDRESS']) ?></textarea></div>
      <div class="col-12"><label class="form-label">Default signature</label><textarea class="form-control" name="DEFAULT_SIGNATURE" rows="4"><?= e($fields['DEFAULT_SIGNATURE']) ?></textarea></div>
    </div></div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body row g-3">
      <h2 class="h5">Email Sending</h2>
      <div class="col-md-3">
        <label class="form-label">Mail provider</label>
        <select class="form-select" name="MAIL_PROVIDER">
          <option value="log"<?= selected($fields['MAIL_PROVIDER'], 'log') ?>>log</option>
          <option value="smtp"<?= selected($fields['MAIL_PROVIDER'], 'smtp') ?>>smtp</option>
          <option value="brevo_api"<?= selected($fields['MAIL_PROVIDER'], 'brevo_api') ?>>Brevo API (recommended for Railway)</option>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">Sender name</label><input class="form-control" name="MAIL_FROM_NAME" value="<?= e($fields['MAIL_FROM_NAME']) ?>" required></div>
      <div class="col-md-3"><label class="form-label">Sender email</label><input class="form-control" type="email" name="MAIL_FROM_EMAIL" value="<?= e($fields['MAIL_FROM_EMAIL']) ?>" required></div>
      <div class="col-md-3"><label class="form-label">Reply-to email</label><input class="form-control" type="email" name="MAIL_REPLY_TO" value="<?= e($fields['MAIL_REPLY_TO']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Admin notification email</label><input class="form-control" type="email" name="ADMIN_EMAIL" value="<?= e($fields['ADMIN_EMAIL']) ?>"></div>
    </div></div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body row g-3">
      <h2 class="h5">SMTP Settings</h2>
      <div class="col-md-4"><label class="form-label">SMTP host</label><input class="form-control" name="MAIL_SMTP_HOST" value="<?= e($fields['MAIL_SMTP_HOST']) ?>"></div>
      <div class="col-md-2"><label class="form-label">SMTP port</label><input class="form-control" name="MAIL_SMTP_PORT" value="<?= e($fields['MAIL_SMTP_PORT']) ?>"></div>
      <div class="col-md-6"><label class="form-label">SMTP user</label><input class="form-control" name="MAIL_SMTP_USER" value="<?= e($fields['MAIL_SMTP_USER']) ?>"></div>
      <div class="col-12"><div class="alert alert-info small mb-0">SMTP password and API key are stored in <code>.env</code> for security and are not shown here. SMTP may be blocked on Railway Free/Hobby. Use Brevo API for Railway.</div></div>
    </div></div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body row g-3">
      <h2 class="h5">Provider Test</h2>
      <?php if ($testErrors): ?><div class="col-12"><div class="alert alert-danger mb-0"><?= e(implode(' ', $testErrors)) ?></div></div><?php endif; ?>
      <?php if ($testResult): ?>
        <div class="col-12">
          <div class="alert alert-<?= $testResult['sent'] ? 'success' : 'danger' ?> mb-0">
            <div><strong>Result:</strong> <?= $testResult['sent'] ? 'success' : 'failure' ?></div>
            <div><strong>Provider used:</strong> <?= e((string) ($testResult['provider'] ?? 'unknown')) ?></div>
            <div><strong>HTTP status:</strong> <?= e(($testResult['status'] ?? null) !== null ? (string) $testResult['status'] : 'n/a') ?></div>
            <div><strong>Message:</strong> <?= e((string) ($testResult['error'] ?: ($testResult['response_message'] ?? 'OK'))) ?></div>
            <?php if (!empty($testResult['reference'])): ?><div><strong>Provider reference:</strong> <?= e((string) $testResult['reference']) ?></div><?php endif; ?>
            <?php if (!empty($testResult['curl_error'])): ?><div><strong>cURL error:</strong> <?= e((string) $testResult['curl_error']) ?></div><?php endif; ?>
            <div><strong>Sender email used:</strong> <?= e((string) ($testResult['sender_email'] ?? $business['sender_email'])) ?></div>
            <div><strong>Recipient email used:</strong> <?= e((string) ($testResult['recipient_email'] ?? $testEmail)) ?></div>
            <div><strong>Active business profile:</strong> <?= e((string) ($testResult['business_name'] ?? $business['brand_name'])) ?> (#<?= e((string) ($testResult['business_profile_id'] ?? $businessId)) ?>)</div>
          </div>
        </div>
      <?php endif; ?>
      <div class="col-12"><div class="alert alert-warning small mb-0">This sends one direct provider connectivity test email only. It does not queue a campaign or contact leads in bulk. Save settings first if you want to test the latest provider selection. For provider health and Brevo account checks, use <a href="/tools/mail_diagnostics.php" class="alert-link">Mail Diagnostics</a>.</div></div>
      <div class="col-md-8"><label class="form-label">Test email recipient</label><input class="form-control" type="email" name="test_email" value="<?= e($testEmail) ?>" placeholder="you@example.com"></div>
      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100" type="submit" name="action" value="send_test_email">Send test email</button>
      </div>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card shadow-sm h-100"><div class="card-body row g-3">
      <h2 class="h5">Outreach Limits</h2>
      <div class="col-12"><label class="form-label">Daily send limit</label><input class="form-control" name="DAILY_SEND_LIMIT" value="<?= e($fields['DAILY_SEND_LIMIT']) ?>" required></div>
    </div></div>
  </div>

  <div class="col-md-6">
    <div class="card shadow-sm h-100"><div class="card-body row g-3">
      <h2 class="h5">Follow-Up Settings</h2>
      <div class="col-md-6"><label class="form-label">Follow-up 1 days</label><input class="form-control" name="FOLLOWUP_1_DAYS" value="<?= e($fields['FOLLOWUP_1_DAYS']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Follow-up 2 days</label><input class="form-control" name="FOLLOWUP_2_DAYS" value="<?= e($fields['FOLLOWUP_2_DAYS']) ?>" required></div>
    </div></div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body row g-3">
      <h2 class="h5">Timezone &amp; Scheduling</h2>
      <div class="col-md-4">
        <label class="form-label">Timezone</label>
        <select class="form-select" name="TIMEZONE" required>
          <?php foreach (timezone_choices() as $timezone): ?>
            <option value="<?= e($timezone) ?>"<?= selected((string) $fields['TIMEZONE'], (string) $timezone) ?>><?= e($timezone) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Current app time preview</div>
        <div class="fw-semibold"><?= e(format_app_datetime(app_utc_now(), $businessId)) ?></div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Current UTC time preview</div>
        <div class="fw-semibold"><?= e(app_utc_now()->format('Y-m-d H:i:s')) ?></div>
      </div>
      <div class="col-12"><div class="alert alert-info small mb-0">This timezone controls campaign scheduling, queue sending, follow-up scheduling, reports, and displayed timestamps.</div></div>
    </div></div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body row g-3">
      <h2 class="h5">Compliance Footer</h2>
      <div class="col-12"><label class="form-label">Compliance footer</label><textarea class="form-control" name="COMPLIANCE_FOOTER" rows="4" required><?= e($fields['COMPLIANCE_FOOTER']) ?></textarea></div>
      <div class="col-12"><label class="form-label">Unsubscribe footer text</label><textarea class="form-control" name="UNSUBSCRIBE_FOOTER_TEXT" rows="2"><?= e($fields['UNSUBSCRIBE_FOOTER_TEXT']) ?></textarea></div>
    </div></div>
  </div>

  <div class="col-12">
    <div class="d-grid d-md-flex gap-2 action-stack">
      <button class="btn btn-primary" type="submit" name="action" value="save_settings">Save settings</button>
      <a class="btn btn-outline-secondary" href="/tools/mail_diagnostics.php">Open diagnostics</a>
    </div>
  </div>
</form>
<?php render_footer(); ?>
