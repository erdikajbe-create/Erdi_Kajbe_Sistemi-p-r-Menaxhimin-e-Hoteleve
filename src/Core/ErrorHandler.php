<?php

declare(strict_types=1);

namespace HotelHub\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line,
        ): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $exception) use ($debug): never {
            error_log((string) $exception);

            $details = $debug
                ? '<pre>' . e((string) $exception) . '</pre>'
                : '';

            Response::html(
                '<!doctype html><html lang="sq"><meta charset="utf-8">'
                . '<title>Gabim i brendshëm</title><h1>Ndodhi një gabim i brendshëm.</h1>'
                . $details
                . '</html>',
                500,
            );
        });
    }
}
