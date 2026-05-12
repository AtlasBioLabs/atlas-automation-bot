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

function now_sql(): string
{
    return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
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
