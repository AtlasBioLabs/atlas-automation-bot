<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Auth.php';

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

echo "Atlas BioLabs admin setup\n";
$name = trim(readline('Name: '));
$email = normalize_email(trim(readline('Email: ')));
$password = (string) readline('Password: ');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
    exit("Name, valid email, and a password of at least 10 characters are required.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = Database::pdo()->prepare(
    'INSERT INTO admins (name, email, password_hash, created_at, updated_at)
     VALUES (?, ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), updated_at = NOW()'
);
$stmt->execute([$name, $email, $hash]);

echo "Admin account saved for {$email}.\n";
