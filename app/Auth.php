<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class Auth
{
    public static function user(): ?array
    {
        start_app_session();
        if (empty($_SESSION['admin_id'])) {
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT id, email, name FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function attempt(string $email, string $password): bool
    {
        start_app_session();
        $stmt = Database::pdo()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([normalize_email($email)]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        Database::pdo()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
        return true;
    }

    public static function require(): void
    {
        if (!self::user()) {
            redirect('/login.php');
        }
    }

    public static function logout(): void
    {
        start_app_session();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function adminCount(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    }
}
