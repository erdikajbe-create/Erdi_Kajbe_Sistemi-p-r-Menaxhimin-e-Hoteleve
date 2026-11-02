<?php

declare(strict_types=1);

namespace HotelHub\Core;

use ErrorException;
use HotelHub\Exceptions\AppException;
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

            if ($exception instanceof AppException) {
                Response::html(
                    View::render('errors/error', [
                        'title' => $exception->getMessage(),
                        'message' => $exception->getMessage(),
                    ]),
                    $exception->getStatusCode(),
                );
            }

            $details = $debug
                ? '<pre>' . e((string) $exception) . '</pre>'
                : '';

            Response::html(
                View::render('errors/error', [
                    'title' => 'Gabim i brendshëm',
                    'message' => 'Ndodhi një gabim i brendshëm.',
                    'details' => $details,
                ]),
                500,
            );
        });
    }
}
