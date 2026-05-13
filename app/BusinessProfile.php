<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class BusinessProfile
{
    public static function all(bool $includeInactive = true): array
    {
        $where = $includeInactive ? '' : ' WHERE active = 1';
        return Database::pdo()->query("SELECT * FROM business_profiles{$where} ORDER BY active DESC, business_name ASC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM business_profiles WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $business = $stmt->fetch();

        return $business ?: null;
    }

    public static function firstActive(): ?array
    {
        $stmt = Database::pdo()->query('SELECT * FROM business_profiles WHERE active = 1 ORDER BY id ASC LIMIT 1');
        $business = $stmt->fetch();

        return $business ?: null;
    }

    public static function current(): array
    {
        start_app_session();
        $selectedId = (int) ($_SESSION['business_profile_id'] ?? 0);
        $business = $selectedId > 0 ? self::find($selectedId) : null;
        if (!$business || !(bool) $business['active']) {
            $business = self::firstActive();
        }

        if (!$business) {
            throw new RuntimeException('No active business profile exists.');
        }

        $_SESSION['business_profile_id'] = (int) $business['id'];
        if (class_exists(Settings::class)) {
            return Settings::effectiveBusiness((int) $business['id']) ?? $business;
        }

        return $business;
    }

    public static function currentId(): int
    {
        return (int) self::current()['id'];
    }

    public static function switchTo(int $id): bool
    {
        $business = self::find($id);
        if (!$business || !(bool) $business['active']) {
            return false;
        }

        start_app_session();
        $_SESSION['business_profile_id'] = (int) $business['id'];
        return true;
    }

    public static function publicFromRequest(): ?array
    {
        $id = (int) ($_GET['business_id'] ?? $_POST['business_id'] ?? 0);
        if ($id > 0) {
            $business = self::find($id);
            if ($business && (bool) $business['active']) {
                if (class_exists(Settings::class)) {
                    return Settings::effectiveBusiness((int) $business['id']) ?? $business;
                }

                return $business;
            }
        }

        $business = self::firstActive();
        if ($business && class_exists(Settings::class)) {
            return Settings::effectiveBusiness((int) $business['id']) ?? $business;
        }

        return $business;
    }

    public static function requiredMailFieldsMissing(array $business): array
    {
        $required = ['sender_name', 'sender_email', 'reply_to_email', 'business_address', 'compliance_footer', 'unsubscribe_footer_text'];
        $missing = [];
        foreach ($required as $field) {
            if (trim((string) ($business[$field] ?? '')) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}
