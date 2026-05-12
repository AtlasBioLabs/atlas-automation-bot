<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/Settings.php';

final class Mailer
{
    private const BREVO_API_BASE = 'https://api.brevo.com/v3';

    public static function render(string $content, array $lead = [], ?array $business = null): string
    {
        $business ??= isset($lead['business_profile_id']) ? BusinessProfile::find((int) $lead['business_profile_id']) : BusinessProfile::current();
        $unsubscribe = '';
        if (!empty($lead['unsubscribe_token'])) {
            $unsubscribe = app_url('/unsubscribe.php?business_id=' . (int) $business['id'] . '&token=' . urlencode((string) $lead['unsubscribe_token']));
        }

        $variables = [
            '{{business_name}}' => $business['business_name'] ?? '',
            '{{brand_name}}' => $business['brand_name'] ?? '',
            '{{tagline}}' => $business['tagline'] ?? '',
            '{{industry}}' => $business['industry'] ?? '',
            '{{website_url}}' => $business['website_url'] ?? '',
            '{{sender_name}}' => $business['sender_name'] ?? '',
            '{{business_address}}' => $business['business_address'] ?? '',
            '{{default_signature}}' => $business['default_signature'] ?? '',
            '{{compliance_footer}}' => $business['compliance_footer'] ?? '',
            '{{unsubscribe_footer_text}}' => Settings::option('UNSUBSCRIBE_FOOTER_TEXT', 'You can unsubscribe using the link included in this email.', (int) $business['id']),
            '{{contact_name}}' => $lead['contact_name'] ?? '',
            '{{company_name}}' => $lead['company_name'] ?? '',
            '{{category}}' => $lead['category'] ?? '',
            '{{unsubscribe_link}}' => $unsubscribe,
            '{{atlas_signature}}' => $business['default_signature'] ?? '',
        ];

        return strtr($content, $variables);
    }

    public static function send(string $toEmail, string $toName, string $subject, string $body, ?array $business = null): array
    {
        $business ??= BusinessProfile::current();
        $business = Settings::effectiveBusiness((int) $business['id']) ?? $business;
        $missing = BusinessProfile::requiredMailFieldsMissing($business);
        if ($missing) {
            return ['sent' => false, 'error' => 'Business profile is missing required email fields: ' . implode(', ', $missing)];
        }

        $body = self::appendCompliance($body, $business);
        $provider = strtolower((string) Settings::option('MAIL_PROVIDER', 'log', (int) $business['id']));
        if ($provider === 'log') {
            return self::logMail($toEmail, $toName, $subject, $body, $business);
        }

        if ($provider === 'brevo_api') {
            return self::sendBrevoApi($toEmail, $toName, $subject, $body, $business);
        }

        if ($provider !== 'smtp') {
            return ['sent' => false, 'error' => 'Unsupported MAIL_PROVIDER. Use log, smtp, or brevo_api.'];
        }

        if (!class_exists(PHPMailer::class)) {
            return ['sent' => false, 'error' => 'PHPMailer is not installed. Run Composer install.'];
        }

        $smtpHost = (string) Settings::option('MAIL_SMTP_HOST', '', (int) $business['id']);
        $smtpPort = (int) Settings::option('MAIL_SMTP_PORT', 587, (int) $business['id']);
        $smtpUser = (string) Settings::option('MAIL_SMTP_USER', '', (int) $business['id']);
        if ($smtpHost === '' || $smtpPort <= 0 || $smtpUser === '') {
            return ['sent' => false, 'error' => 'SMTP settings are incomplete for this business profile.'];
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            $mail->SMTPAuth = $smtpUser !== '';
            $mail->Username = $smtpUser;
            $mail->Password = (string) app_config('mail_smtp_pass');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom((string) $business['sender_email'], (string) $business['sender_name']);
            $replyTo = (string) ($business['reply_to_email'] ?: Settings::option('MAIL_REPLY_TO', '', (int) $business['id']));
            if ($replyTo !== '') {
                $mail->addReplyTo($replyTo);
            }
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $body;
            $mail->send();

            return ['sent' => true, 'error' => null, 'reference' => null];
        } catch (MailException $exception) {
            return ['sent' => false, 'error' => $exception->getMessage(), 'reference' => null];
        }
    }

    private static function appendCompliance(string $body, array $business): string
    {
        $parts = [trim($body)];
        $footer = trim((string) $business['compliance_footer']);
        if ($footer !== '' && !str_contains($body, $footer)) {
            $parts[] = $footer;
        }
        $unsubscribeFooter = trim((string) Settings::option('UNSUBSCRIBE_FOOTER_TEXT', '', (int) $business['id']));
        if ($unsubscribeFooter !== '' && !str_contains($body, $unsubscribeFooter)) {
            $parts[] = $unsubscribeFooter;
        }

        return implode("\n\n", $parts);
    }

    private static function logMail(string $toEmail, string $toName, string $subject, string $body, array $business): array
    {
        $dir = APP_ROOT . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.eml';
        $message = "To: {$toName} <{$toEmail}>\n";
        $message .= 'From: ' . $business['sender_name'] . ' <' . $business['sender_email'] . ">\n";
        if (!empty($business['reply_to_email'])) {
            $message .= 'Reply-To: ' . $business['reply_to_email'] . "\n";
        }
        $message .= 'Business-Profile-ID: ' . (int) $business['id'] . "\n";
        $message .= "Subject: {$subject}\n\n{$body}\n";
        file_put_contents($file, $message);

        return ['sent' => true, 'error' => null, 'reference' => basename($file)];
    }

    private static function sendBrevoApi(string $toEmail, string $toName, string $subject, string $body, array $business): array
    {
        $apiKey = trim((string) app_config('mail_api_key', ''));
        if ($apiKey === '') {
            return ['sent' => false, 'error' => 'Brevo API key missing from environment variable MAIL_API_KEY', 'reference' => null];
        }

        if (!function_exists('curl_init')) {
            return ['sent' => false, 'error' => 'cURL is required for Brevo API sending.', 'reference' => null];
        }

        $payload = [
            'sender' => [
                'name' => (string) $business['sender_name'],
                'email' => (string) $business['sender_email'],
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => $toName !== '' ? $toName : $toEmail,
            ]],
            'subject' => $subject,
            'htmlContent' => self::toHtmlBody($body),
            'textContent' => self::toPlainText($body),
        ];

        $replyTo = trim((string) ($business['reply_to_email'] ?: Settings::option('MAIL_REPLY_TO', '', (int) $business['id'])));
        if ($replyTo !== '') {
            $payload['replyTo'] = ['email' => $replyTo];
        }

        $result = self::sendBrevoRequest('/smtp/email', 'POST', $payload);
        if ($result['ok']) {
            $reference = self::extractBrevoReference($result['decoded']);
            return ['sent' => true, 'error' => null, 'reference' => $reference];
        }

        return ['sent' => false, 'error' => $result['message'], 'reference' => null];
    }

    public static function testBrevoConnection(): array
    {
        $apiKey = trim((string) app_config('mail_api_key', ''));
        if ($apiKey === '') {
            return [
                'success' => false,
                'status' => null,
                'message' => 'Brevo API key missing from environment variable MAIL_API_KEY',
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'status' => null,
                'message' => 'cURL is required for Brevo API sending.',
            ];
        }

        $result = self::sendBrevoRequest('/account', 'GET');
        return [
            'success' => $result['ok'],
            'status' => $result['status'],
            'message' => $result['ok'] ? ($result['message'] !== '' ? $result['message'] : 'Brevo API connection successful.') : $result['message'],
        ];
    }

    private static function toHtmlBody(string $body): string
    {
        return nl2br(htmlspecialchars(self::toPlainText($body), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    private static function toPlainText(string $body): string
    {
        $text = trim(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $body)));
        return $text !== '' ? $text : trim($body);
    }

    private static function extractBrevoReference(mixed $decoded): ?string
    {
        if (!is_array($decoded)) {
            return null;
        }

        foreach (['messageId', 'message_id', 'requestId'] as $key) {
            if (!empty($decoded[$key]) && is_scalar($decoded[$key])) {
                return (string) $decoded[$key];
            }
        }

        return null;
    }

    private static function extractBrevoErrorMessage(mixed $decoded): string
    {
        if (!is_array($decoded)) {
            return '';
        }

        foreach (['message', 'code', 'error'] as $key) {
            if (!empty($decoded[$key]) && is_scalar($decoded[$key])) {
                return (string) $decoded[$key];
            }
        }

        return '';
    }

    private static function sendBrevoRequest(string $path, string $method, ?array $payload = null): array
    {
        $apiKey = trim((string) app_config('mail_api_key', ''));
        if ($apiKey === '') {
            return [
                'ok' => false,
                'status' => null,
                'message' => 'Brevo API key missing from environment variable MAIL_API_KEY',
                'decoded' => null,
            ];
        }

        $url = rtrim(self::BREVO_API_BASE, '/') . $path;
        $headers = [
            'accept: application/json',
            'api-key: ' . $apiKey,
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $headers[] = 'content-type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload ?? [], JSON_UNESCAPED_SLASHES);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($responseBody === false) {
            $message = 'cURL error: ' . ($curlError !== '' ? $curlError : 'request failed');
            error_log('[Mailer] ' . $message);
            return [
                'ok' => false,
                'status' => null,
                'message' => $message,
                'decoded' => null,
            ];
        }

        $decoded = json_decode((string) $responseBody, true);
        $safeMessage = self::extractBrevoErrorMessage($decoded);

        if ($status >= 200 && $status < 300) {
            $message = self::extractBrevoSuccessMessage($decoded);
            return [
                'ok' => true,
                'status' => $status,
                'message' => $message,
                'decoded' => $decoded,
            ];
        }

        $message = trim('Brevo API returned HTTP ' . $status . ($safeMessage !== '' ? ': ' . $safeMessage : ''));
        error_log('[Mailer] ' . $message);
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'decoded' => $decoded,
        ];
    }

    private static function extractBrevoSuccessMessage(mixed $decoded): string
    {
        if (!is_array($decoded)) {
            return '';
        }

        foreach (['message', 'messageId', 'message_id', 'requestId'] as $key) {
            if (!empty($decoded[$key]) && is_scalar($decoded[$key])) {
                return (string) $decoded[$key];
            }
        }

        return '';
    }
}
