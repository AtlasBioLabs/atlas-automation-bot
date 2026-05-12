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

        $business['business_name'] = (string) self::option('BUSINESS_NAME', $business['business_name'], $businessProfileId);
        $business['brand_name'] = (string) self::option('APP_NAME', $business['brand_name'], $businessProfileId);
        $business['tagline'] = (string) self::option('BUSINESS_TAGLINE', $business['tagline'], $businessProfileId);
        $business['sender_name'] = (string) self::option('MAIL_FROM_NAME', $business['sender_name'], $businessProfileId);
        $business['sender_email'] = (string) self::option('MAIL_FROM_EMAIL', $business['sender_email'], $businessProfileId);
        $business['reply_to_email'] = (string) self::option('MAIL_REPLY_TO', $business['reply_to_email'], $businessProfileId);
        $business['admin_notification_email'] = (string) self::option('ADMIN_EMAIL', $business['admin_notification_email'], $businessProfileId);
        $business['business_address'] = (string) self::option('BUSINESS_ADDRESS', $business['business_address'], $businessProfileId);
        $business['default_signature'] = (string) self::option('DEFAULT_SIGNATURE', $business['default_signature'], $businessProfileId);
        $business['compliance_footer'] = (string) self::option('COMPLIANCE_FOOTER', $business['compliance_footer'], $businessProfileId);
        $business['daily_send_limit'] = (int) self::option('DAILY_SEND_LIMIT', $business['daily_send_limit'], $businessProfileId);

        return $business;
    }
}
