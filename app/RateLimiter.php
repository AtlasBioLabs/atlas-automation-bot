<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class RateLimiter
{
    public static function hit(string $action, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        $pdo = Database::pdo();
        $since = (new DateTimeImmutable("-{$windowSeconds} seconds"))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE created_at < ?');
        $stmt->execute([$since]);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE action = ? AND identifier = ? AND created_at >= ?');
        $stmt->execute([$action, $identifier, $since]);
        if ((int) $stmt->fetchColumn() >= $maxAttempts) {
            return false;
        }

        $stmt = $pdo->prepare('INSERT INTO rate_limits (action, identifier, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$action, $identifier]);
        return true;
    }
}
