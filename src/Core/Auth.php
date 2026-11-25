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
        $intended = $_SESSION['_intended'] ?? null;
        session_regenerate_id(true);
        $_SESSION = ['user' => $user];
        if ($intended !== null) {
            $_SESSION['_intended'] = $intended;
        }
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

    public static function isStaff(): bool
    {
        $user = self::user();

        return $user !== null && in_array($user['role'], ['admin', 'receptionist'], true);
    }

    public static function requireLogin(): array
    {
        $user = self::user();

        if ($user === null) {
            if (str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/api/')) {
                throw new AppException('Duhet të kyçeni për të vazhduar.', 401);
            }
            Response::redirect('/login');
        }

        return $user;
    }

    /**
     * Faqja ku duhet ridrejtuar pas hyrjes; vetëm shtigje lokale.
     */
    public static function pullIntended(): ?string
    {
        self::assertSessionStarted();
        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);

        return is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')
            ? $intended
            : null;
    }

    public static function homeFor(string $role): string
    {
        return match ($role) {
            'admin' => '/dashboard',
            'receptionist' => '/reservations',
            default => '/availability',
        };
    }

    public static function id(): int
    {
        return (int) self::requireLogin()['id'];
    }

    public static function isStaff(): bool
    {
        $user = self::user();

        return $user !== null && in_array($user['role'], ['admin', 'receptionist'], true);
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
