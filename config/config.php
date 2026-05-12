<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$autoload = APP_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists(Dotenv\Dotenv::class) && is_file(APP_ROOT . '/.env')) {
    Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
}

function env_value(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

function mysql_url_config(string $url): array
{
    if ($url === '') {
        return [];
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return [];
    }

    $path = trim((string) ($parts['path'] ?? ''), '/');

    return [
        'host' => $parts['host'] ?? null,
        'port' => isset($parts['port']) ? (int) $parts['port'] : null,
        'user' => isset($parts['user']) ? rawurldecode((string) $parts['user']) : null,
        'pass' => isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : null,
        'name' => $path !== '' ? $path : null,
    ];
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $mysqlUrl = mysql_url_config((string) env_value('MYSQL_URL', ''));
        $config = [
            'app_name' => (string) env_value('APP_NAME', 'Atlas BioLabs Outreach Bot'),
            'app_env' => (string) env_value('APP_ENV', 'local'),
            'app_url' => rtrim((string) env_value('APP_URL', 'http://localhost:8000'), '/'),
            'db_host' => (string) env_value('MYSQLHOST', $mysqlUrl['host'] ?? env_value('DB_HOST', '127.0.0.1')),
            'db_port' => (int) env_value('MYSQLPORT', $mysqlUrl['port'] ?? env_value('DB_PORT', 3306)),
            'db_name' => (string) env_value('MYSQLDATABASE', $mysqlUrl['name'] ?? env_value('DB_NAME', 'atlas_biolabs_bot')),
            'db_user' => (string) env_value('MYSQLUSER', $mysqlUrl['user'] ?? env_value('DB_USER', 'root')),
            'db_pass' => (string) env_value('MYSQLPASSWORD', $mysqlUrl['pass'] ?? env_value('DB_PASS', '')),
            'mail_provider' => strtolower((string) env_value('MAIL_PROVIDER', 'log')),
            'mail_from_name' => (string) env_value('MAIL_FROM_NAME', 'Atlas BioLabs'),
            'mail_from_email' => (string) env_value('MAIL_FROM_EMAIL', 'no-reply@example.com'),
            'mail_reply_to' => (string) env_value('MAIL_REPLY_TO', ''),
            'mail_smtp_host' => (string) env_value('MAIL_SMTP_HOST', ''),
            'mail_smtp_port' => (int) env_value('MAIL_SMTP_PORT', 587),
            'mail_smtp_user' => (string) env_value('MAIL_SMTP_USER', ''),
            'mail_smtp_pass' => (string) env_value('MAIL_SMTP_PASS', ''),
            'mail_api_key' => (string) env_value('MAIL_API_KEY', ''),
            'rfq_api_token' => (string) env_value('RFQ_API_TOKEN', ''),
            'admin_email' => (string) env_value('ADMIN_EMAIL', 'admin@example.com'),
            'business_address' => (string) env_value('BUSINESS_ADDRESS', 'Business address placeholder'),
            'daily_send_limit' => (int) env_value('DAILY_SEND_LIMIT', 30),
            'followup_1_days' => (int) env_value('FOLLOWUP_1_DAYS', 3),
            'followup_2_days' => (int) env_value('FOLLOWUP_2_DAYS', 7),
        ];
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}
