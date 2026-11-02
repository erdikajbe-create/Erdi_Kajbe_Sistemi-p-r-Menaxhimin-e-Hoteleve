<?php

declare(strict_types=1);

namespace HotelHub\Core;

use HotelHub\Exceptions\AppException;
use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        self::assertSessionStarted();
        $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));

        return $_SESSION['_csrf'];
    }

    public static function verify(mixed $submitted): void
    {
        self::assertSessionStarted();
        $token = $_SESSION['_csrf'] ?? '';

        if (!is_string($submitted) || !is_string($token) || $token === ''
            || !hash_equals($token, $submitted)) {
            throw new AppException('Sesioni i formularit skadoi. Provoni përsëri.', 403);
        }
    }

    private static function assertSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Sesioni PHP nuk është aktiv.');
        }
    }
}
