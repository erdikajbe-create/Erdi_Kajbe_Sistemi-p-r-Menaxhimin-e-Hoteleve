<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use HotelHub\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testItMatchesRoutesWithPathParameters(): void
    {
        $router = new Router();
        $receivedId = null;
        $router->get('/rooms/{id}', static function (string $id) use (&$receivedId): void {
            $receivedId = $id;
        });

        self::assertTrue($router->dispatch('GET', '/rooms/42'));
        self::assertSame('42', $receivedId);
        self::assertFalse($router->dispatch('GET', '/rooms/42/status'));
    }

    public function testItDoesNotDispatchRoutesForAnotherMethod(): void
    {
        $router = new Router();
        $router->post('/login', static function (): void {
        });

        self::assertFalse($router->dispatch('GET', '/login'));
    }

    public function testIdParameterAcceptsOnlyDigits(): void
    {
        $router = new Router();
        $router->get('/reservations/create', static function (): void {
        });
        $router->get('/reservations/{id}', static function (string $id): void {
        });

        self::assertTrue($router->dispatch('GET', '/reservations/15'));
        self::assertTrue($router->dispatch('GET', '/reservations/create'));
        self::assertFalse($router->dispatch('GET', '/reservations/abc'));
        self::assertFalse($router->dispatch('GET', '/reservations/1%20OR%201=1'));
    }
}
