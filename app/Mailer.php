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
            '{{company_profile_url}}' => $business['company_profile_url'] ?? ($business['website_url'] ?? ''),
            '{{business_logo_url}}' => $business['logo_url'] ?? '',
            '{{sender_name}}' => $business['sender_name'] ?? '',
            '{{sender_email}}' => $business['sender_email'] ?? '',
            '{{reply_to_email}}' => ($business['reply_to_email'] ?? '') !== '' ? ($business['reply_to_email'] ?? '') : ($business['sender_email'] ?? ''),
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
        $htmlNeedsFooter = false;
        if ($htmlSource !== '') {
            $renderedHtmlSource = strtr($htmlSource, $variables);
            $isFullHtml = (bool) preg_match('/<(?:html|body|table)\b/i', $renderedHtmlSource);
            $htmlBody = $isFullHtml
                ? $renderedHtmlSource
                : self::wrapHtmlTemplate($subject, $preheader, $renderedHtmlSource, $business, $variables);
            $htmlNeedsFooter = $isFullHtml;
        } else {
            $htmlBody = self::wrapHtmlTemplate($subject, $preheader, nl2br(e($textBody)), $business, $variables);
        }

        $textBody = self::appendCompliance($textBody, $business, $variables);
        if ($htmlNeedsFooter) {
            $htmlBody = self::appendComplianceHtml($htmlBody, $business, $variables);
        }
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

    public static function renderedContentNeedsRefresh(array $snapshot, ?array $business = null): bool
    {
        $businessId = (int) ($business['id'] ?? 0);
        $combined = implode("\n", array_filter([
            (string) ($snapshot['subject'] ?? ''),
            (string) ($snapshot['preheader'] ?? ''),
            (string) ($snapshot['html'] ?? ''),
            (string) ($snapshot['text'] ?? ''),
            (string) ($snapshot['rendered_subject'] ?? ''),
            (string) ($snapshot['rendered_preheader'] ?? ''),
            (string) ($snapshot['rendered_html'] ?? ''),
            (string) ($snapshot['rendered_text'] ?? ''),
        ]));

        foreach ([
            'Business address placeholder',
            'no-reply@example.com',
            'https://example.com',
            'http://example.com',
        ] as $needle) {
            if ($combined !== '' && stripos($combined, $needle) !== false) {
                return true;
            }
        }

        if ($businessId === 1 && stripos($combined, 'View company profile') !== false) {
            return true;
        }

        return false;
    }

    private static function appendCompliance(string $body, array $business, array $variables = []): string
    {
        $parts = [trim($body)];
        $footer = self::cleanComplianceFooter((string) ($business['compliance_footer'] ?? ''), (string) Settings::option('UNSUBSCRIBE_FOOTER_TEXT', '', (int) $business['id']));
        $address = trim((string) ($business['business_address'] ?? ''));
        $unsubscribeLink = trim((string) ($variables['{{unsubscribe_link}}'] ?? ''));
        if ($footer !== '' && !str_contains($body, $footer)) {
            if ($address !== '' && !str_contains($body, $address)) {
                $parts[] = $address;
            }
            $parts[] = $footer;
        }
        if ($footer === '' && $address !== '' && !str_contains($body, $address)) {
            $parts[] = $address;
        }
        if ($unsubscribeLink !== '' && stripos($body, $unsubscribeLink) === false) {
            $parts[] = 'You can unsubscribe here: ' . $unsubscribeLink;
        }

        return implode("\n\n", array_values(array_filter($parts, static fn ($part) => trim((string) $part) !== '')));
    }

    private static function appendComplianceHtml(string $html, array $business, array $variables = []): string
    {
        $footer = self::cleanComplianceFooter((string) ($business['compliance_footer'] ?? ''), (string) Settings::option('UNSUBSCRIBE_FOOTER_TEXT', '', (int) $business['id']));
        $address = trim((string) $business['business_address']);
        $brand = trim((string) ($business['brand_name'] ?? $business['business_name'] ?? ''));
        $tagline = trim((string) ($business['tagline'] ?? ''));
        $senderEmail = trim((string) ($variables['{{sender_email}}'] ?? $business['sender_email'] ?? ''));
        $website = trim((string) ($variables['{{website_url}}'] ?? $business['website_url'] ?? ''));
        $unsubscribeLink = trim((string) ($variables['{{unsubscribe_link}}'] ?? ''));

        $footerBlock = '';
        if ($brand !== '') {
            $footerBlock .= '<p style="margin:0 0 4px;color:#0A1A2F;font-size:12px;line-height:18px;font-weight:700;">' . e($brand) . '</p>';
        }
        if ($tagline !== '') {
            $footerBlock .= '<p style="margin:0 0 8px;color:#5B6B7E;font-size:12px;line-height:18px;">' . e($tagline) . '</p>';
        }
        if ($senderEmail !== '' || $website !== '') {
            $footerBlock .= '<p style="margin:0 0 8px;color:#5B6B7E;font-size:12px;line-height:18px;">'
                . ($senderEmail !== '' ? '<a href="mailto:' . e($senderEmail) . '" style="color:#5B6B7E;text-decoration:none;">' . e($senderEmail) . '</a>' : '')
                . (($senderEmail !== '' && $website !== '') ? ' | ' : '')
                . ($website !== '' ? '<a href="' . e($website) . '" style="color:#5B6B7E;text-decoration:none;">' . e($website) . '</a>' : '')
                . '</p>';
        }
        if ($address !== '') {
            $footerBlock .= '<p style="margin:0 0 8px;color:#5B6B7E;font-size:12px;line-height:18px;">' . nl2br(e($address)) . '</p>';
        }
        if ($footer !== '') {
            $footerBlock .= '<p style="margin:0 0 8px;color:#5B6B7E;font-size:12px;line-height:18px;">' . nl2br(e($footer)) . '</p>';
        }
        if ($unsubscribeLink !== '') {
            $footerBlock .= '<p style="margin:0;color:#5B6B7E;font-size:12px;line-height:18px;">You can unsubscribe here: <a href="' . e($unsubscribeLink) . '" style="color:#2E6BFF;">Unsubscribe</a></p>';
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

    private static function cleanComplianceFooter(string $footer, string $unsubscribeFooterText): string
    {
        $footer = trim($footer);
        $unsubscribeFooterText = trim($unsubscribeFooterText);
        if ($footer === '') {
            return '';
        }

        if ($unsubscribeFooterText !== '') {
            $footer = str_ireplace($unsubscribeFooterText, '', $footer);
        }

        $footer = preg_replace('/\s+/', ' ', $footer) ?? $footer;
        return trim((string) $footer);
    }

    private static function wrapHtmlTemplate(string $subject, string $preheader, string $bodyHtml, array $business, array $variables): string
    {
        $logo = trim((string) ($business['logo_url'] ?? ''));
        $brand = trim((string) ($business['brand_name'] ?? $business['business_name'] ?? 'Business profile'));
        $tagline = trim((string) ($business['tagline'] ?? ''));
        $website = trim((string) ($business['website_url'] ?? ''));
        $companyProfileUrl = trim((string) ($business['company_profile_url'] ?? $website));
        $primary = trim((string) ($business['primary_color'] ?? '#0A1A2F'));
        $accent = trim((string) ($business['accent_color'] ?? '#2E6BFF'));
        $secondary = trim((string) ($business['secondary_color'] ?? '#FFFFFF'));
        $signature = nl2br(e((string) ($variables['{{default_signature}}'] ?? '')));
        $unsubscribe = trim((string) ($variables['{{unsubscribe_link}}'] ?? ''));
        $unsubscribeText = trim((string) ($variables['{{unsubscribe_footer_text}}'] ?? ''));
        $supportEmail = trim((string) ($variables['{{reply_to_email}}'] ?? $variables['{{sender_email}}'] ?? ''));
        $senderEmail = trim((string) ($variables['{{sender_email}}'] ?? ''));
        $footerCompliance = self::cleanComplianceFooter((string) ($business['compliance_footer'] ?? ''), $unsubscribeText);
        $subheadline = trim($preheader !== '' ? $preheader : ($tagline !== '' ? $tagline : 'Premium sourcing communication for qualified B2B buyers.'));

        $logoMarkup = $logo !== ''
            ? '<img src="' . e($logo) . '" alt="' . e($brand) . ' logo" width="220" class="email-shell-logo-img" style="display:block;width:100%;max-width:220px;height:auto;margin:0 auto;border:0;outline:none;text-decoration:none;">'
            : '<div class="email-shell-logo-fallback" style="display:block;width:100%;max-width:220px;margin:0 auto;font-size:24px;font-weight:700;line-height:30px;color:' . e($secondary) . ';letter-spacing:0;text-align:center;word-break:break-word;overflow-wrap:anywhere;">' . e($brand) . '</div>';

        $secondaryCtaMarkup = $companyProfileUrl !== ''
            ? '<a href="' . e($companyProfileUrl) . '" style="color:' . e($accent) . ';text-decoration:none;font-weight:600;">view our company profile</a>'
            : '';
        $unsubscribeMarkup = $unsubscribe !== ''
            ? '<p style="margin:12px 0 0;color:#5B6B7E;font-size:12px;line-height:18px;">You can unsubscribe here: <a href="' . e($unsubscribe) . '" style="color:' . e($accent) . ';">Unsubscribe</a></p>'
            : '';
        $headerPill = '<span style="display:inline-block;background:#113158;border:1px solid rgba(255,255,255,0.12);color:#D6E5FF;padding:8px 13px;border-radius:999px;font-size:12px;line-height:12px;font-weight:700;letter-spacing:0.2px;">' . e($brand) . '</span>';
        $heroMeta = $website !== ''
            ? '<a href="' . e($website) . '" style="color:#CFE0FF;text-decoration:none;">' . e(preg_replace('#^https?://#', '', $website) ?? $website) . '</a>'
            : 'Qualified B2B sourcing communication';
        $heroSupportLine = 'Built for clear commercial follow-up, documentation-aware discussion, and qualified supply coordination.';
        $logoCard = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" class="email-shell-logo-wrap" style="width:100%;max-width:220px;margin:0 auto;background:#102744;border:1px solid #23466E;border-radius:18px;overflow:hidden;"><tr><td align="center" style="padding:14px 14px 12px;overflow:hidden;"><table role="presentation" cellspacing="0" cellpadding="0" border="0" class="email-shell-logo-lock" style="width:100%;max-width:220px;margin:0 auto;"><tr><td align="center" style="width:100%;max-width:220px;overflow:hidden;">' . $logoMarkup . '</td></tr></table></td></tr></table>';
        $trustRows = [
            ['title' => 'MOQ flexibility', 'copy' => 'Structured quantity planning for early-stage and scaling programs.'],
            ['title' => 'Documentation support', 'copy' => 'Clear, review-ready discussion for technical and commercial review.'],
            ['title' => 'Qualified supply dialogue', 'copy' => 'Commercially clear communication from inquiry through next-step alignment.'],
        ];
        $trustMarkup = '';
        foreach ($trustRows as $row) {
            $trustMarkup .= '<td width="33.33%" valign="top" class="email-shell-tile" style="padding:0 10px 0 0;">'
                . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#102643;border:1px solid #24476F;border-radius:16px;"><tr><td style="padding:16px 16px 15px;">'
                . '<div style="width:36px;height:36px;background:#173A63;border:1px solid #3D6DA8;border-radius:12px;font-size:0;line-height:0;">&nbsp;</div>'
                . '<div style="margin-top:13px;font-size:14px;line-height:20px;font-weight:700;color:#FFFFFF;">' . e($row['title']) . '</div>'
                . '<div style="margin-top:6px;font-size:12px;line-height:19px;color:#B6C8DD;">' . e($row['copy']) . '</div>'
                . '</td></tr></table>'
                . '</td>';
        }

        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<style>body,table,td,a{font-family:Arial,Helvetica,sans-serif;}body{margin:0!important;padding:0!important;}table{border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;}img{max-width:100%!important;height:auto!important;border:0;display:block;outline:none;text-decoration:none;}.email-shell-box,.email-shell-box *{box-sizing:border-box;}.email-shell-hero-copy,.email-shell-hero-copy div,.email-shell-headline,.email-shell-subheadline,.email-shell-supportline{word-break:break-word!important;overflow-wrap:anywhere!important;}.email-shell-logo-wrap,.email-shell-logo-lock,.email-shell-logo-img{overflow:hidden!important;}@media only screen and (max-width:620px){.email-shell{width:100%!important;max-width:100%!important;}.email-shell-hero-pad{padding-left:18px!important;padding-right:18px!important;}.email-shell-card-pad{padding-left:20px!important;padding-right:20px!important;}.email-shell-footer-pad{padding:18px 18px 20px!important;}.email-shell-mobile-block,.email-shell-mobile-block tbody,.email-shell-mobile-block tr,.email-shell-mobile-block td{display:block!important;width:100%!important;max-width:100%!important;}.email-shell-topbar,.email-shell-topbar tbody,.email-shell-topbar tr,.email-shell-topbar td{display:block!important;width:100%!important;}.email-shell-topbar td + td{padding-top:10px!important;text-align:left!important;}.email-shell-logo-col{display:block!important;width:100%!important;max-width:100%!important;padding-right:0!important;padding-bottom:14px!important;}.email-shell-logo-wrap{max-width:180px!important;}.email-shell-logo-lock{max-width:180px!important;}.email-shell-logo-img{max-width:180px!important;}.email-shell-logo-fallback{max-width:180px!important;font-size:22px!important;line-height:28px!important;}.email-shell-hero-copy{display:block!important;width:100%!important;max-width:100%!important;overflow:hidden!important;}.email-shell-headline{font-size:22px!important;line-height:28px!important;}.email-shell-subheadline{font-size:15px!important;line-height:22px!important;}.email-shell-supportline{font-size:13px!important;line-height:20px!important;}.email-shell-button{display:block!important;width:100%!important;}.email-shell-tile{display:block!important;width:100%!important;padding:0 0 10px!important;}.email-shell-content img{max-width:100%!important;height:auto!important;}.email-shell-content table{width:100%!important;}.email-shell-iframe-safe{width:100%!important;}}</style></head><body style="margin:0;padding:0;background:#F4F7FB;">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">' . e($preheader) . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F4F7FB;"><tr><td align="center" style="padding:28px 12px 40px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="width:100%;max-width:600px;">'
            . '<tr><td style="padding-bottom:18px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell-box" style="width:100%;max-width:600px;background:' . e($primary) . ';border:1px solid #163257;border-radius:24px;overflow:hidden;box-sizing:border-box;">'
            . '<tr><td style="height:4px;background:' . e($accent) . ';font-size:0;line-height:0;">&nbsp;</td></tr>'
            . '<tr><td class="email-shell-hero-pad" style="padding:22px 30px 0;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell-topbar"><tr>'
            . '<td valign="middle">' . $headerPill . '</td>'
            . '<td align="right" valign="middle" style="font-size:12px;line-height:18px;color:#9CB3CE;word-break:break-word;overflow-wrap:anywhere;">' . $heroMeta . '</td>'
            . '</tr></table>'
            . '</td></tr>'
            . '<tr><td class="email-shell-hero-pad" style="padding:18px 30px 10px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell-mobile-block"><tr>'
            . '<td valign="top" width="220" class="email-shell-logo-col" style="width:220px;max-width:220px;padding:0 20px 0 0;overflow:hidden;">' . $logoCard . '</td>'
            . '<td valign="top" width="100%" class="email-shell-hero-copy" style="width:100%;max-width:100%;min-width:0;overflow:hidden;">'
            . ($tagline !== '' ? '<div style="font-size:13px;line-height:20px;color:#AFC1D7;font-weight:600;letter-spacing:0.2px;word-break:break-word;overflow-wrap:anywhere;">' . e($tagline) . '</div>' : '')
            . '<div class="email-shell-headline" style="margin-top:10px;font-size:28px;line-height:34px;font-weight:700;color:#FFFFFF;letter-spacing:0;word-break:break-word;overflow-wrap:anywhere;">' . e($subject) . '</div>'
            . '<div class="email-shell-subheadline" style="margin-top:12px;font-size:16px;line-height:24px;color:#D8E5F5;max-width:100%;word-break:break-word;overflow-wrap:anywhere;">' . e($subheadline) . '</div>'
            . '<div class="email-shell-supportline" style="margin-top:12px;font-size:13px;line-height:21px;color:#9CB7D4;max-width:100%;word-break:break-word;overflow-wrap:anywhere;">' . e($heroSupportLine) . '</div>'
            . '</td>'
            . '</tr></table>'
            . '</td></tr>'
            . '<tr><td class="email-shell-hero-pad" style="padding:16px 30px 28px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell-mobile-block"><tr>'
            . $trustMarkup
            . '</tr></table>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '<tr><td>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#FFFFFF;border:1px solid #DCE6F2;border-radius:22px;overflow:hidden;box-shadow:0 12px 36px rgba(10,26,47,0.06);">'
            . '<tr><td class="email-shell-card-pad" style="padding:0 32px;"><div style="height:6px;background:linear-gradient(90deg, ' . e($accent) . ' 0%, #8BB1FF 55%, #E7F0FF 100%);font-size:0;line-height:0;">&nbsp;</div></td></tr>'
            . '<tr><td class="email-shell-card-pad" style="padding:12px 32px 0;"><div style="height:1px;background:#E8EEF5;font-size:0;line-height:0;">&nbsp;</div></td></tr>'
            . '<tr><td class="email-shell-card-pad" style="padding:30px 32px 12px;color:#243548;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:26px;"><div class="email-shell-content">' . $bodyHtml . '</div></td></tr>'
            . '<tr><td class="email-shell-card-pad" style="padding:0 32px 30px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F7FAFD;border:1px solid #DCE6F2;border-radius:18px;"><tr>'
            . '<td style="padding:18px 20px;">'
            . '<div style="font-size:13px;line-height:18px;font-weight:700;color:#0A1A2F;text-transform:uppercase;letter-spacing:0.2px;">Next Step</div>'
            . '<div style="margin-top:6px;font-size:14px;line-height:22px;color:#526173;">Reply directly'
            . ($supportEmail !== '' ? ' at <a href="mailto:' . e($supportEmail) . '" style="color:' . e($accent) . ';text-decoration:none;">' . e($supportEmail) . '</a>' : '')
            . ($secondaryCtaMarkup !== '' ? ' or ' . $secondaryCtaMarkup . '.' : '.')
            . ' We will keep the conversation concise, commercial, and requirement-focused.</div>'
            . '</td></tr></table>'
            . '</td></tr>'
            . '<tr><td class="email-shell-card-pad" style="padding:0 32px 28px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-top:1px solid #E1E8F1;"><tr>'
            . '<td style="padding-top:18px;font-size:14px;line-height:22px;color:#243548;">' . $signature . '</td>'
            . '</tr></table>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '<tr><td style="padding-top:16px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#EEF4FA;border:1px solid #D9E4F1;border-radius:20px;"><tr><td class="email-shell-footer-pad" style="padding:22px 24px;">'
            . '<div style="font-size:12px;line-height:18px;font-weight:700;color:#0A1A2F;">' . e($brand) . '</div>'
            . ($tagline !== '' ? '<div style="margin-top:4px;font-size:12px;line-height:19px;color:#5B6B7E;">' . e($tagline) . '</div>' : '')
            . (($senderEmail !== '' || $website !== '') ? '<div style="margin-top:10px;font-size:12px;line-height:19px;color:#5B6B7E;">'
                . ($senderEmail !== '' ? '<a href="mailto:' . e($senderEmail) . '" style="color:#5B6B7E;text-decoration:none;">' . e($senderEmail) . '</a>' : '')
                . (($senderEmail !== '' && $website !== '') ? ' | ' : '')
                . ($website !== '' ? '<a href="' . e($website) . '" style="color:#5B6B7E;text-decoration:none;">' . e($website) . '</a>' : '')
                . '</div>' : '')
            . '<div style="margin-top:10px;font-size:12px;line-height:19px;color:#5B6B7E;">' . nl2br(e((string) ($business['business_address'] ?? ''))) . '</div>'
            . ($footerCompliance !== '' ? '<div style="margin-top:12px;font-size:12px;line-height:19px;color:#5B6B7E;">' . nl2br(e($footerCompliance)) . '</div>' : '')
            . $unsubscribeMarkup
            . '</td></tr></table>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private static function missingVariables(array $contents): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', implode("\n", $contents), $matches);
        return array_values(array_unique($matches[0] ?? []));
    }
}
