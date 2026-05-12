<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('atlas_outreach_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    start_app_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function flash(?string $key = null, ?string $message = null): mixed
{
    start_app_session();
    if ($key !== null && $message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if ($key !== null) {
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

function app_url(string $path = ''): string
{
    $base = app_config('app_url', '');
    return $base . '/' . ltrim($path, '/');
}

function valid_timezone_name(string $timezone): bool
{
    try {
        new DateTimeZone($timezone);
        return true;
    } catch (Throwable) {
        return false;
    }
}

function timezone_choices(): array
{
    static $choices = null;
    if (is_array($choices)) {
        return $choices;
    }

    $preferred = [
        'Africa/Douala',
        'Africa/Lagos',
        'Africa/Cairo',
        'America/New_York',
        'America/Chicago',
        'America/Los_Angeles',
        'Europe/London',
        'Europe/Paris',
        'Asia/Dubai',
        'Asia/Singapore',
    ];

    $all = DateTimeZone::listIdentifiers();
    $choices = array_values(array_unique(array_merge($preferred, $all)));
    sort($choices);

    if (($key = array_search('Africa/Douala', $choices, true)) !== false) {
        unset($choices[$key]);
        array_unshift($choices, 'Africa/Douala');
        $choices = array_values($choices);
    }

    return $choices;
}

function app_timezone(?int $businessProfileId = null): string
{
    $fallback = (string) app_config('app_timezone', 'Africa/Douala');

    if ($businessProfileId !== null && class_exists(BusinessProfile::class)) {
        $business = BusinessProfile::find($businessProfileId);
        $timezone = trim((string) ($business['timezone'] ?? ''));
        return valid_timezone_name($timezone) ? $timezone : $fallback;
    }

    if (PHP_SAPI !== 'cli') {
        start_app_session();
        $sessionBusinessId = (int) ($_SESSION['business_profile_id'] ?? 0);
        if ($sessionBusinessId > 0 && class_exists(BusinessProfile::class)) {
            $business = BusinessProfile::find($sessionBusinessId);
            $timezone = trim((string) ($business['timezone'] ?? ''));
            if (valid_timezone_name($timezone)) {
                return $timezone;
            }
        }
    }

    return $fallback;
}

function app_utc_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function app_now(?int $businessProfileId = null): DateTimeImmutable
{
    return app_utc_now()->setTimezone(new DateTimeZone(app_timezone($businessProfileId)));
}

function app_datetime_to_utc(DateTimeInterface|string|null $datetime, ?int $businessProfileId = null): ?DateTimeImmutable
{
    if ($datetime === null || $datetime === '') {
        return null;
    }

    if ($datetime instanceof DateTimeInterface) {
        return DateTimeImmutable::createFromInterface($datetime)->setTimezone(new DateTimeZone('UTC'));
    }

    $timezone = new DateTimeZone(app_timezone($businessProfileId));
    $hasExplicitTimezone = (bool) preg_match('/(?:Z|[+\-]\d{2}:\d{2})$/', trim($datetime));
    $parsed = $hasExplicitTimezone
        ? new DateTimeImmutable($datetime)
        : new DateTimeImmutable($datetime, $timezone);

    return $parsed->setTimezone(new DateTimeZone('UTC'));
}

function utc_to_app_datetime(DateTimeInterface|string|null $datetime, ?int $businessProfileId = null): ?DateTimeImmutable
{
    if ($datetime === null || $datetime === '') {
        return null;
    }

    if ($datetime instanceof DateTimeInterface) {
        $utc = DateTimeImmutable::createFromInterface($datetime)->setTimezone(new DateTimeZone('UTC'));
    } else {
        $hasExplicitTimezone = (bool) preg_match('/(?:Z|[+\-]\d{2}:\d{2})$/', trim($datetime));
        $utc = $hasExplicitTimezone
            ? new DateTimeImmutable($datetime)
            : new DateTimeImmutable($datetime, new DateTimeZone('UTC'));
        $utc = $utc->setTimezone(new DateTimeZone('UTC'));
    }

    return $utc->setTimezone(new DateTimeZone(app_timezone($businessProfileId)));
}

function format_app_datetime(DateTimeInterface|string|null $datetime, ?int $businessProfileId = null, string $format = 'Y-m-d H:i:s'): string
{
    $local = utc_to_app_datetime($datetime, $businessProfileId);
    return $local ? $local->format($format) : '';
}

function app_local_input_value(DateTimeInterface|string|null $datetime = null, ?int $businessProfileId = null): string
{
    if ($datetime === null || $datetime === '') {
        return app_now($businessProfileId)->format('Y-m-d\TH:i');
    }

    return format_app_datetime($datetime, $businessProfileId, 'Y-m-d\TH:i');
}

function app_local_day_bounds_utc(string $date, ?int $businessProfileId = null): array
{
    $timezone = new DateTimeZone(app_timezone($businessProfileId));
    $startLocal = new DateTimeImmutable($date . ' 00:00:00', $timezone);
    $endLocal = $startLocal->modify('+1 day');

    return [
        $startLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        $endLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
    ];
}

function app_current_day_bounds_utc(?int $businessProfileId = null): array
{
    $startLocal = app_now($businessProfileId)->setTime(0, 0, 0);
    $endLocal = $startLocal->modify('+1 day');

    return [
        $startLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        $endLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
    ];
}

function app_tomorrow_day_bounds_utc(?int $businessProfileId = null): array
{
    $startLocal = app_now($businessProfileId)->setTime(0, 0, 0)->modify('+1 day');
    $endLocal = $startLocal->modify('+1 day');

    return [
        $startLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        $endLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
    ];
}

function now_sql(): string
{
    return app_utc_now()->format('Y-m-d H:i:s');
}

function default_lead_categories(): array
{
    return [
        'Skincare / cosmetic formulators',
        'Ingredient distributors',
        'Supplement / private-label teams',
        'Contract manufacturers',
        'Research supply buyers',
        'Aesthetic / beauty product developers',
        'Other',
    ];
}

function lead_categories(?int $businessProfileId = null): array
{
    if (class_exists(Settings::class)) {
        try {
            $configured = (string) Settings::get('lead_categories', '', $businessProfileId);
            if (trim($configured) !== '') {
                $categories = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $configured))));
                if ($categories) {
                    return $categories;
                }
            }
        } catch (Throwable) {
            return default_lead_categories();
        }
    }

    return default_lead_categories();
}

function lead_statuses(): array
{
    return [
        'new',
        'queued',
        'emailed',
        'follow_up_1_sent',
        'follow_up_2_sent',
        'replied',
        'interested',
        'quoted',
        'customer',
        'not_interested',
        'complained',
        'invalid',
        'bounced',
        'unsubscribed',
    ];
}

function stopped_statuses(): array
{
    return ['replied', 'interested', 'quoted', 'customer', 'not_interested', 'complained', 'invalid', 'bounced', 'unsubscribed'];
}

function normalize_email(string $email): string
{
    return mb_strtolower(trim($email));
}

function selected(string $actual, string $expected): string
{
    return $actual === $expected ? ' selected' : '';
}

function checked(bool $value): string
{
    return $value ? ' checked' : '';
}
