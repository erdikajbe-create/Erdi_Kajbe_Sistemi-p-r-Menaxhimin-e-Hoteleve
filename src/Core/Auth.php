<?php

declare(strict_types=1);

namespace HotelHub\Core;

use HotelHub\Exceptions\AppException;
use RuntimeException;

final class Auth
{
    /**
     * @param array{id: int, name: string, email: string, role: string} $user
     */
    public static function login(array $user): void
    {
        self::assertSessionStarted();
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        self::assertSessionStarted();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $parameters['path'],
                'domain' => $parameters['domain'],
                'secure' => $parameters['secure'],
                'httponly' => $parameters['httponly'],
                'samesite' => $parameters['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}|null
     */
    public static function user(): ?array
    {
        self::assertSessionStarted();
        $user = $_SESSION['user'] ?? null;

        return is_array($user) ? $user : null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();

        if ($user === null) {
            Response::redirect('/login');
        }

        return $user;
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::requireLogin();

        if (!in_array($user['role'], $roles, true)) {
            throw new AppException('Nuk keni leje për këtë veprim.', 403);
        }

        return $user;
    }

    private static function assertSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Sesioni PHP nuk është aktiv.');
        }
    }
}
