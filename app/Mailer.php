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
        return strtr($content, self::templateVariables($lead, $business));
    }

    public static function templateVariables(array $lead = [], ?array $business = null): array
    {
        $business ??= isset($lead['business_profile_id']) ? BusinessProfile::find((int) $lead['business_profile_id']) : BusinessProfile::current();
        $unsubscribe = '';
        if (!empty($lead['unsubscribe_token'])) {
            $unsubscribe = app_url('/unsubscribe.php?business_id=' . (int) $business['id'] . '&token=' . urlencode((string) $lead['unsubscribe_token']));
        }

        return [
            '{{business_name}}' => $business['business_name'] ?? '',
            '{{brand_name}}' => $business['brand_name'] ?? '',
            '{{tagline}}' => $business['tagline'] ?? '',
            '{{business_tagline}}' => $business['tagline'] ?? '',
            '{{industry}}' => $business['industry'] ?? '',
            '{{website_url}}' => $business['website_url'] ?? '',
            '{{business_logo_url}}' => $business['logo_url'] ?? '',
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
    }

    public static function renderTemplate(array $template, array $lead = [], ?array $business = null): array
    {
        $business ??= isset($lead['business_profile_id']) ? Settings::effectiveBusiness((int) $lead['business_profile_id']) : BusinessProfile::current();
        $variables = self::templateVariables($lead, $business);

        $subject = strtr((string) ($template['subject'] ?? ''), $variables);
        $preheader = strtr((string) ($template['preheader'] ?? ''), $variables);
        $legacyBody = strtr((string) ($template['body'] ?? ''), $variables);
        $textBody = trim((string) ($template['body_text'] ?? '')) !== ''
            ? strtr((string) $template['body_text'], $variables)
            : $legacyBody;
        $htmlSource = trim((string) ($template['body_html'] ?? ''));
        if ($htmlSource !== '') {
            $renderedHtmlSource = strtr($htmlSource, $variables);
            $htmlBody = preg_match('/<(?:html|body|table)\b/i', $renderedHtmlSource)
                ? $renderedHtmlSource
                : self::wrapHtmlTemplate($subject, $preheader, $renderedHtmlSource, $business, $variables);
        } else {
            $htmlBody = self::wrapHtmlTemplate($subject, $preheader, nl2br(e($textBody)), $business, $variables);
        }

        $textBody = self::appendCompliance($textBody, $business);
        $htmlBody = self::appendComplianceHtml($htmlBody, $business);
        $missing = self::missingVariables([$subject, $preheader, $textBody, $htmlBody]);

        return [
            'subject' => $subject,
            'preheader' => $preheader,
            'html' => $htmlBody,
            'text' => $textBody,
            'variables' => $variables,
            'missing_variables' => $missing,
            'unsubscribe_link' => $variables['{{unsubscribe_link}}'] ?? '',
        ];
    }

    public static function send(string $toEmail, string $toName, string $subject, string $body, ?array $business = null, array $content = []): array
    {
        $business ??= BusinessProfile::current();
        $business = Settings::effectiveBusiness((int) $business['id']) ?? $business;
        $provider = strtolower((string) Settings::option('MAIL_PROVIDER', 'log', (int) $business['id']));
        $base = self::baseResultContext($provider, $business, $toEmail);
        $missing = BusinessProfile::requiredMailFieldsMissing($business);
        if ($missing) {
            return self::mergeResult($base, [
                'sent' => false,
                'error' => 'Business profile is missing required email fields: ' . implode(', ', $missing),
            ]);
        }

        $prepared = !empty($content['prepared']);
        $textBody = (string) ($content['text'] ?? $body);
        $htmlBody = (string) ($content['html'] ?? self::toHtmlBody($textBody));
        if (!$prepared) {
            $textBody = self::appendCompliance($textBody, $business);
            $htmlBody = self::appendComplianceHtml($htmlBody, $business);
        }
        $preheader = trim((string) ($content['preheader'] ?? ''));
        if ($provider === 'log') {
            return self::logMail($toEmail, $toName, $subject, $htmlBody, $textBody, $preheader, $business, $base);
        }

        if ($provider === 'brevo_api') {
            return self::sendBrevoApi($toEmail, $toName, $subject, $htmlBody, $textBody, $preheader, $business, $base);
        }

        if ($provider !== 'smtp') {
            return self::mergeResult($base, [
                'sent' => false,
                'error' => 'Unsupported MAIL_PROVIDER. Use log, smtp, or brevo_api.',
            ]);
        }

        if (!class_exists(PHPMailer::class)) {
            return self::mergeResult($base, [
                'sent' => false,
                'error' => 'PHPMailer is not installed. Run Composer install.',
            ]);
        }

        $smtpHost = (string) Settings::option('MAIL_SMTP_HOST', '', (int) $business['id']);
        $smtpPort = (int) Settings::option('MAIL_SMTP_PORT', 587, (int) $business['id']);
        $smtpUser = (string) Settings::option('MAIL_SMTP_USER', '', (int) $business['id']);
        if ($smtpHost === '' || $smtpPort <= 0 || $smtpUser === '') {
            return self::mergeResult($base, [
                'sent' => false,
                'error' => 'SMTP settings are incomplete for this business profile.',
            ]);
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
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->send();

            return self::mergeResult($base, ['sent' => true, 'error' => null, 'reference' => null]);
        } catch (MailException $exception) {
            return self::mergeResult($base, [
                'sent' => false,
                'error' => $exception->getMessage(),
                'reference' => null,
            ]);
        }
    }

    public static function sendBrevoDiagnosticEmail(string $recipientEmail, ?array $business = null): array
    {
        $business ??= BusinessProfile::current();
        $business = Settings::effectiveBusiness((int) $business['id']) ?? $business;
        $provider = strtolower((string) Settings::option('MAIL_PROVIDER', 'log', (int) $business['id']));
        $base = self::baseResultContext($provider, $business, $recipientEmail);

        if ($provider !== 'brevo_api') {
            return self::mergeResult($base, [
                'sent' => false,
                'error' => 'MAIL_PROVIDER must be brevo_api for the Brevo diagnostic send.',
            ]);
        }

        return self::sendBrevoApi(
            $recipientEmail,
            'Brevo Diagnostic',
            'Atlas BioLabs Brevo API Test',
            self::wrapHtmlTemplate(
                'Atlas BioLabs Brevo API Test',
                '',
                '<p style="margin:0 0 16px;">This is a one-off Brevo API test from the Atlas BioLabs automation bot.</p>',
                $business,
                self::templateVariables([], $business)
            ),
            'This is a one-off Brevo API test from the Atlas BioLabs automation bot.',
            '',
            $business,
            $base
        );
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

    private static function appendComplianceHtml(string $html, array $business): string
    {
        $footer = trim((string) $business['compliance_footer']);
        $unsubscribeFooter = trim((string) Settings::option('UNSUBSCRIBE_FOOTER_TEXT', '', (int) $business['id']));
        $address = trim((string) $business['business_address']);

        $footerBlock = '';
        if ($footer !== '') {
            $footerBlock .= '<p style="margin:0 0 8px;color:#5B6B7E;font-size:12px;line-height:18px;">' . nl2br(e($footer)) . '</p>';
        }
        if ($address !== '') {
            $footerBlock .= '<p style="margin:0 0 8px;color:#5B6B7E;font-size:12px;line-height:18px;">' . nl2br(e($address)) . '</p>';
        }
        if ($unsubscribeFooter !== '') {
            $footerBlock .= '<p style="margin:0;color:#5B6B7E;font-size:12px;line-height:18px;">' . nl2br(e($unsubscribeFooter)) . '</p>';
        }
        if ($footerBlock === '') {
            return $html;
        }

        $block = '<div style="margin-top:24px;border-top:1px solid #D9DEE6;padding-top:18px;">' . $footerBlock . '</div>';
        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $block . '</body>', $html, 1) ?? ($html . $block);
        }

        return $html . $block;
    }

    private static function logMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody, string $preheader, array $business, array $base): array
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
        if ($preheader !== '') {
            $message .= "Preheader: {$preheader}\n";
        }
        $message .= "Subject: {$subject}\n";
        $message .= "Content-Type: multipart/alternative\n\n";
        $message .= "---TEXT---\n{$textBody}\n\n---HTML---\n{$htmlBody}\n";
        file_put_contents($file, $message);

        return self::mergeResult($base, ['sent' => true, 'error' => null, 'reference' => basename($file)]);
    }

    private static function sendBrevoApi(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody, string $preheader, array $business, array $base): array
    {
        $validation = self::validateBrevoSend($business, $toEmail, $base['provider']);
        if ($validation !== null) {
            return self::mergeResult($base, $validation);
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
            'htmlContent' => $htmlBody,
            'textContent' => self::toPlainText($textBody),
        ];

        $replyTo = trim((string) ($business['reply_to_email'] ?: Settings::option('MAIL_REPLY_TO', '', (int) $business['id'])));
        if ($replyTo !== '') {
            $payload['replyTo'] = ['email' => $replyTo];
        }

        $result = self::sendBrevoRequest('/smtp/email', 'POST', $payload);
        $reference = self::extractBrevoReference($result['decoded']);
        if ($result['ok']) {
            return self::mergeResult($base, [
                'sent' => true,
                'error' => null,
                'reference' => $reference,
                'status' => $result['status'],
                'response_message' => $result['message'],
                'response_body' => $result['response_body'],
            ]);
        }

        return self::mergeResult($base, [
            'sent' => false,
            'error' => $result['message'],
            'reference' => $reference,
            'status' => $result['status'],
            'response_message' => $result['message'],
            'response_body' => $result['response_body'],
            'curl_error' => $result['curl_error'],
        ]);
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
            'response_body' => $result['response_body'],
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
                'response_body' => null,
                'curl_error' => null,
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
                'response_body' => null,
                'curl_error' => $curlError !== '' ? $curlError : 'request failed',
            ];
        }

        $decoded = json_decode((string) $responseBody, true);
        $safeMessage = self::extractBrevoErrorMessage($decoded);
        $safeBody = self::safeResponseBody($decoded, (string) $responseBody);

        if ($status >= 200 && $status < 300) {
            $message = self::extractBrevoSuccessMessage($decoded);
            return [
                'ok' => true,
                'status' => $status,
                'message' => $message,
                'decoded' => $decoded,
                'response_body' => $safeBody,
                'curl_error' => null,
            ];
        }

        $message = trim('Brevo API returned HTTP ' . $status . ($safeMessage !== '' ? ': ' . $safeMessage : ''));
        error_log('[Mailer] ' . $message);
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'decoded' => $decoded,
            'response_body' => $safeBody,
            'curl_error' => null,
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

    private static function validateBrevoSend(array $business, string $toEmail, string $provider): ?array
    {
        if ($provider !== 'brevo_api') {
            return ['sent' => false, 'error' => 'MAIL_PROVIDER must be brevo_api for Brevo API sending.'];
        }

        $senderName = trim((string) ($business['sender_name'] ?? ''));
        if ($senderName === '') {
            return ['sent' => false, 'error' => 'MAIL_FROM_NAME is required for Brevo API sending.'];
        }

        $senderEmail = trim((string) ($business['sender_email'] ?? ''));
        if ($senderEmail === '') {
            return ['sent' => false, 'error' => 'MAIL_FROM_EMAIL is required for Brevo API sending.'];
        }

        if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'error' => 'MAIL_FROM_EMAIL must be a valid email address for Brevo API sending.'];
        }

        if (trim($toEmail) === '') {
            return ['sent' => false, 'error' => 'Recipient email is required for Brevo API sending.'];
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'error' => 'Recipient email must be a valid email address for Brevo API sending.'];
        }

        if (trim((string) app_config('mail_api_key', '')) === '') {
            return ['sent' => false, 'error' => 'Brevo API key missing from environment variable MAIL_API_KEY'];
        }

        if (!function_exists('curl_init')) {
            return ['sent' => false, 'error' => 'cURL is required for Brevo API sending.'];
        }

        return null;
    }

    private static function safeResponseBody(mixed $decoded, string $raw): ?string
    {
        if (is_array($decoded)) {
            $safe = [];
            foreach (['message', 'code', 'error', 'messageId', 'message_id', 'requestId'] as $key) {
                if (isset($decoded[$key]) && is_scalar($decoded[$key])) {
                    $safe[$key] = (string) $decoded[$key];
                }
            }

            if ($safe !== []) {
                return json_encode($safe, JSON_UNESCAPED_SLASHES);
            }
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        return mb_substr($raw, 0, 500);
    }

    private static function baseResultContext(string $provider, array $business, string $recipientEmail): array
    {
        return [
            'sent' => false,
            'error' => null,
            'reference' => null,
            'provider' => $provider,
            'status' => null,
            'response_message' => null,
            'response_body' => null,
            'curl_error' => null,
            'sender_email' => (string) ($business['sender_email'] ?? ''),
            'recipient_email' => $recipientEmail,
            'business_profile_id' => (int) ($business['id'] ?? 0),
            'business_name' => (string) ($business['brand_name'] ?? $business['business_name'] ?? ''),
        ];
    }

    private static function mergeResult(array $base, array $overrides): array
    {
        return array_merge($base, $overrides);
    }

    private static function wrapHtmlTemplate(string $subject, string $preheader, string $bodyHtml, array $business, array $variables): string
    {
        $logo = trim((string) ($business['logo_url'] ?? ''));
        $brand = trim((string) ($business['brand_name'] ?? $business['business_name'] ?? 'Business profile'));
        $tagline = trim((string) ($business['tagline'] ?? ''));
        $website = trim((string) ($business['website_url'] ?? ''));
        $primary = trim((string) ($business['primary_color'] ?? '#0A1A2F'));
        $accent = trim((string) ($business['accent_color'] ?? '#2E6BFF'));
        $signature = nl2br(e((string) ($variables['{{default_signature}}'] ?? '')));
        $unsubscribe = trim((string) ($variables['{{unsubscribe_link}}'] ?? ''));
        $unsubscribeText = trim((string) ($variables['{{unsubscribe_footer_text}}'] ?? ''));

        $logoMarkup = $logo !== ''
            ? '<img src="' . e($logo) . '" alt="' . e($brand) . ' logo" style="display:block;max-width:180px;height:auto;border:0;">'
            : '<div style="font-size:24px;font-weight:700;color:' . e($primary) . ';">' . e($brand) . '</div>';

        $ctaMarkup = $website !== ''
            ? '<tr><td style="padding:0 36px 28px;"><a href="' . e($website) . '" style="display:inline-block;background:' . e($accent) . ';color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:600;">Visit ' . e($brand) . '</a></td></tr>'
            : '';
        $unsubscribeMarkup = ($unsubscribe !== '' && $unsubscribeText !== '')
            ? '<p style="margin:12px 0 0;color:#5B6B7E;font-size:12px;line-height:18px;">' . e($unsubscribeText) . ' <a href="' . e($unsubscribe) . '" style="color:' . e($accent) . ';">Unsubscribe</a></p>'
            : '';

        return '<!doctype html><html><body style="margin:0;padding:0;background:#F4F7FB;">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . e($preheader) . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F4F7FB;"><tr><td align="center" style="padding:24px 12px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#FFFFFF;border:1px solid #D9DEE6;border-radius:8px;overflow:hidden;">'
            . '<tr><td style="background:' . e($primary) . ';padding:28px 36px;">' . $logoMarkup
            . ($tagline !== '' ? '<div style="margin-top:8px;color:#C7CED6;font-size:14px;line-height:20px;">' . e($tagline) . '</div>' : '')
            . '</td></tr>'
            . '<tr><td style="padding:32px 36px 20px;"><h1 style="margin:0 0 16px;color:#102033;font-size:24px;line-height:32px;">' . e($subject) . '</h1>'
            . '<div style="color:#243548;font-size:15px;line-height:24px;">' . $bodyHtml . '</div></td></tr>'
            . $ctaMarkup
            . '<tr><td style="padding:0 36px 24px;"><div style="color:#243548;font-size:14px;line-height:22px;">' . $signature . '</div>' . $unsubscribeMarkup . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private static function missingVariables(array $contents): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', implode("\n", $contents), $matches);
        return array_values(array_unique($matches[0] ?? []));
    }
}
