<?php

declare(strict_types=1);

namespace HotelHub\Core;

final class Router
{
    /** @var array<string, list<array{pattern: string, handler: callable}>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch(string $method, string $path): bool
    {
        foreach ($this->routes[strtoupper($method)] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            array_shift($matches);
            ($route['handler'])(...array_map('urldecode', $matches));

            return true;
        }

        return false;
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $quoted = preg_quote($path, '~');
        $pattern = preg_replace_callback(
            '/\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}/',
            static fn (array $matches): string => '([^/]+)',
            $quoted,
        );

        $this->routes[$method][] = [
            'pattern' => '~^' . $pattern . '$~D',
            'handler' => $handler,
        ];
    }
}
