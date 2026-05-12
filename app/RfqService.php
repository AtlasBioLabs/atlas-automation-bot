<?php

declare(strict_types=1);

require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/Mailer.php';

final class RfqService
{
    public static function tokenIsValid(string $providedToken): bool
    {
        $configuredToken = (string) app_config('rfq_api_token', '');
        return $configuredToken !== '' && $providedToken !== '' && hash_equals($configuredToken, $providedToken);
    }

    public static function submit(array $input, array $business): array
    {
        $businessId = (int) $business['id'];
        $data = self::normalize($input);
        $errors = self::validate($data);
        if ($errors) {
            return ['success' => false, 'errors' => $errors, 'data' => $data];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM leads WHERE business_profile_id = ? AND email = ? LIMIT 1');
            $stmt->execute([$businessId, $data['email']]);
            $lead = $stmt->fetch();
            if ($lead) {
                $leadId = (int) $lead['id'];
                $pdo->prepare(
                    'UPDATE leads
                     SET company_name = ?, contact_name = ?, phone = ?, country = ?, source = ?, status = "interested", unsubscribed = 0, updated_at = NOW()
                     WHERE id = ? AND business_profile_id = ?'
                )->execute([$data['company'], $data['name'], $data['phone'], $data['country'], $data['source'], $leadId, $businessId]);
            } else {
                $pdo->prepare(
                    'INSERT INTO leads (business_profile_id, company_name, contact_name, email, phone, country, category, source, status, notes, unsubscribe_token, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, "Other", ?, "interested", ?, ?, NOW(), NOW())'
                )->execute([$businessId, $data['company'], $data['name'], $data['email'], $data['phone'], $data['country'], $data['source'], $data['message'], bin2hex(random_bytes(32))]);
                $leadId = (int) $pdo->lastInsertId();
            }

            $pdo->prepare(
                'INSERT INTO rfqs (business_profile_id, source, lead_id, name, company, email, phone, country, product_interest, estimated_quantity, timeline, message, items_json, page_url, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            )->execute([
                $businessId,
                $data['source'],
                $leadId,
                $data['name'],
                $data['company'],
                $data['email'],
                $data['phone'],
                $data['country'],
                $data['product_interest'],
                $data['estimated_quantity'],
                $data['timeline'],
                $data['message'],
                $data['items'] ? json_encode($data['items'], JSON_UNESCAPED_SLASHES) : null,
                $data['page_url'],
                $data['user_agent'],
            ]);
            $rfqId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            return ['success' => false, 'errors' => [$throwable->getMessage()], 'data' => $data];
        }

        self::sendNotifications($data, $business, $leadId);
        return ['success' => true, 'errors' => [], 'data' => $data, 'lead_id' => $leadId, 'rfq_id' => $rfqId];
    }

    private static function normalize(array $input): array
    {
        $keys = ['name', 'company', 'email', 'phone', 'country', 'product_interest', 'estimated_quantity', 'timeline', 'message', 'source', 'page_url', 'user_agent'];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = trim((string) ($input[$key] ?? ''));
        }
        $data['email'] = normalize_email($data['email']);
        $data['source'] = $data['source'] !== '' ? $data['source'] : 'website_rfq';
        if ($data['product_interest'] === '') {
            $data['product_interest'] = match ($data['source']) {
                'contact_form' => 'Website contact form',
                'inquiry_cart' => 'Inquiry cart submission',
                'custom_request' => 'Custom request',
                'request_quote' => 'Quote request',
                default => 'Website RFQ',
            };
        }
        $items = $input['items'] ?? [];
        $data['items'] = is_array($items) ? array_values($items) : [];
        return $data;
    }

    private static function validate(array $data): array
    {
        $errors = [];
        foreach (['name', 'email', 'product_interest'] as $field) {
            if ($data[$field] === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if (strlen($data['message']) > 5000) {
            $errors[] = 'Message is too long.';
        }
        if (strlen($data['page_url']) > 500) {
            $errors[] = 'Page URL is too long.';
        }
        return $errors;
    }

    private static function sendNotifications(array $data, array $business, int $leadId): void
    {
        $businessId = (int) $business['id'];
        $itemsText = $data['items'] ? "\nItems: " . json_encode($data['items'], JSON_UNESCAPED_SLASHES) : '';
        $adminBody = "New RFQ received for {$business['brand_name']}\n\nSource: {$data['source']}\nCompany: {$data['company']}\nName: {$data['name']}\nEmail: {$data['email']}\nPhone: {$data['phone']}\nCountry: {$data['country']}\nProduct interest: {$data['product_interest']}\nEstimated quantity: {$data['estimated_quantity']}\nTimeline: {$data['timeline']}\nPage URL: {$data['page_url']}{$itemsText}\n\n{$data['message']}";
        $adminEmail = (string) ($business['admin_notification_email'] ?: app_config('admin_email'));
        Mailer::send($adminEmail, $business['brand_name'] . ' Admin', 'New ' . $business['brand_name'] . ' RFQ', $adminBody, $business);

        $tplStmt = Database::pdo()->prepare('SELECT * FROM email_templates WHERE business_profile_id = ? AND followup_stage = 9 AND active = 1 ORDER BY id LIMIT 1');
        $tplStmt->execute([$businessId]);
        $tpl = $tplStmt->fetch();
        if (!$tpl) {
            return;
        }

        $leadStmt = Database::pdo()->prepare('SELECT * FROM leads WHERE id = ? AND business_profile_id = ? LIMIT 1');
        $leadStmt->execute([$leadId, $businessId]);
        $leadForMail = $leadStmt->fetch() ?: [
            'business_profile_id' => $businessId,
            'contact_name' => $data['name'],
            'company_name' => $data['company'],
            'category' => 'RFQ',
            'unsubscribe_token' => '',
        ];
        Mailer::send($data['email'], $data['name'], Mailer::render($tpl['subject'], $leadForMail, $business), Mailer::render($tpl['body'], $leadForMail, $business), $business);
    }
}
