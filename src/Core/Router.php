<?php

declare(strict_types=1);

namespace HotelHub\Core;

final class Router
{
    /** @var array<string, array<string, callable(): void>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): bool
    {
        $handler = $this->routes[strtoupper($method)][$path] ?? null;

        if ($handler === null) {
            return false;
        }

        $handler();

        return true;
    }
}
