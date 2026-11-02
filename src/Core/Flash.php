<?php

declare(strict_types=1);

namespace HotelHub\Core;

use RuntimeException;

final class Flash
{
    public static function add(string $type, string $message): void
    {
        self::assertSessionStarted();
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return list<array{type: string, message: string}>
     */
    public static function consumeAll(): array
    {
        self::assertSessionStarted();
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }

    private static function assertSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Sesioni duhet të jetë aktiv për mesazhet flash.');
        }
    }
}
