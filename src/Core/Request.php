<?php

declare(strict_types=1);

namespace HotelHub\Core;

use HotelHub\Exceptions\AppException;
use JsonException;

final class Request
{
    private static ?array $jsonBody = null;

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return is_string($path) ? $path : '/';
    }

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isApi(): bool
    {
        return str_starts_with(self::path(), '/api/') || self::path() === '/api';
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Body i request-it: JSON për /api (Postman, fetch), përndryshe $_POST.
     *
     * @return array<string, mixed>
     */
    public static function input(): array
    {
        $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        if (!str_contains($contentType, 'application/json')) {
            return $_POST;
        }

        if (self::$jsonBody !== null) {
            return self::$jsonBody;
        }

        $raw = (string) file_get_contents('php://input');
        if (trim($raw) === '') {
            return self::$jsonBody = [];
        }

        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AppException('Body i request-it nuk është JSON i vlefshëm.', 400, null, 'INVALID_JSON');
        }

        return self::$jsonBody = is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function query(): array
    {
        return $_GET;
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $_SERVER[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
