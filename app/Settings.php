<?php

declare(strict_types=1);

require_once __DIR__ . '/BusinessProfile.php';

final class Settings
{
    private const PROFILE_MAP = [
        'APP_NAME' => 'brand_name',
        'BUSINESS_NAME' => 'business_name',
        'BUSINESS_TAGLINE' => 'tagline',
        'MAIL_FROM_NAME' => 'sender_name',
        'MAIL_FROM_EMAIL' => 'sender_email',
        'MAIL_REPLY_TO' => 'reply_to_email',
        'ADMIN_EMAIL' => 'admin_notification_email',
        'BUSINESS_ADDRESS' => 'business_address',
        'WEBSITE_URL' => 'website_url',
        'COMPANY_PROFILE_URL' => 'company_profile_url',
        'DEFAULT_SIGNATURE' => 'default_signature',
        'COMPLIANCE_FOOTER' => 'compliance_footer',
        'DAILY_SEND_LIMIT' => 'daily_send_limit',
    ];

    public static function get(string $key, mixed $default = null, ?int $businessProfileId = null): mixed
    {
        $businessProfileId ??= BusinessProfile::currentId();
        $stmt = Database::pdo()->prepare('SELECT setting_value FROM settings WHERE business_profile_id = ? AND setting_key = ? LIMIT 1');
        $stmt->execute([$businessProfileId, $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? $default : $value;
    }

    public static function set(string $key, string $value, ?int $businessProfileId = null): void
    {
        $businessProfileId ??= BusinessProfile::currentId();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO settings (business_profile_id, setting_key, setting_value, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $stmt->execute([$businessProfileId, $key, $value]);
    }

    public static function all(?int $businessProfileId = null): array
    {
        $businessProfileId ??= BusinessProfile::currentId();
        $stmt = Database::pdo()->prepare('SELECT setting_key, setting_value FROM settings WHERE business_profile_id = ? ORDER BY setting_key');
        $stmt->execute([$businessProfileId]);
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public static function option(string $key, mixed $default = null, ?int $businessProfileId = null): mixed
    {
        $businessProfileId ??= BusinessProfile::currentId();
        $value = self::get($key, null, $businessProfileId);
        if ($value !== null) {
            return $value;
        }

        $legacyKey = strtolower($key);
        $value = self::get($legacyKey, null, $businessProfileId);
        if ($value !== null) {
            return $value;
        }

        $business = BusinessProfile::find($businessProfileId);
        if ($business && isset(self::PROFILE_MAP[$key])) {
            $profileValue = $business[self::PROFILE_MAP[$key]] ?? null;
            if ($profileValue !== null && $profileValue !== '') {
                return $profileValue;
            }
        }

        return $default;
    }

    public static function setOption(string $key, string $value, ?int $businessProfileId = null): void
    {
        self::set($key, $value, $businessProfileId);
    }

    public static function effectiveBusiness(int $businessProfileId): ?array
    {
        $business = BusinessProfile::find($businessProfileId);
        if (!$business) {
            return null;
        }

        $profileFallbacks = [
            'business_name' => 'BUSINESS_NAME',
            'brand_name' => 'APP_NAME',
            'tagline' => 'BUSINESS_TAGLINE',
            'sender_name' => 'MAIL_FROM_NAME',
            'sender_email' => 'MAIL_FROM_EMAIL',
            'reply_to_email' => 'MAIL_REPLY_TO',
            'admin_notification_email' => 'ADMIN_EMAIL',
            'business_address' => 'BUSINESS_ADDRESS',
            'website_url' => 'WEBSITE_URL',
            'company_profile_url' => 'COMPANY_PROFILE_URL',
            'default_signature' => 'DEFAULT_SIGNATURE',
            'compliance_footer' => 'COMPLIANCE_FOOTER',
        ];
        foreach ($profileFallbacks as $profileField => $settingKey) {
            if (trim((string) ($business[$profileField] ?? '')) === '') {
                $business[$profileField] = (string) self::option($settingKey, '', $businessProfileId);
            }
        }
        $business['unsubscribe_footer_text'] = (string) self::option('UNSUBSCRIBE_FOOTER_TEXT', 'You can unsubscribe using the link included in this email.', $businessProfileId);
        $business['daily_send_limit'] = (int) ($business['daily_send_limit'] ?: self::option('DAILY_SEND_LIMIT', 30, $businessProfileId));
        if ($business['company_profile_url'] === '') {
            $business['company_profile_url'] = $business['website_url'];
        }
        $timezone = trim((string) ($business['timezone'] ?? ''));
        $business['timezone'] = valid_timezone_name($timezone) ? $timezone : app_config('app_timezone', 'Africa/Douala');

        return $business;
    }
}
