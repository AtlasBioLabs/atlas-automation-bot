<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$blocked = [
    '/.env',
    '/.env.example',
    '/app/',
    '/config/',
    '/database/',
    '/storage/',
    '/tools/',
    '/vendor/',
    '/composer.json',
    '/composer.lock',
];

foreach ($blocked as $prefix) {
    if ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix)) {
        http_response_code(403);
        echo 'Forbidden';
        return true;
    }
}

$file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);
if ($path !== '/' && is_file($file)) {
    return false;
}

http_response_code(404);
echo 'Not found';
return true;
