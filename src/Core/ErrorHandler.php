<?php

declare(strict_types=1);

namespace HotelHub\Core;

use ErrorException;
use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\ValidationException;
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

            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            if (str_starts_with($path, '/api/')) {
                $status = $exception instanceof AppException ? $exception->getStatusCode() : 500;
                $payload = [
                    'success' => false,
                    'error' => $exception instanceof AppException && $exception->getErrorCode() !== null
                        ? $exception->getErrorCode()
                        : ($exception instanceof ValidationException ? 'VALIDATION_ERROR' : match ($status) {
                            401 => 'UNAUTHENTICATED',
                            403 => 'FORBIDDEN',
                            404 => 'NOT_FOUND',
                            409 => 'CONFLICT',
                            429 => 'RATE_LIMITED',
                            422 => 'VALIDATION_ERROR',
                            default => 'INTERNAL_ERROR',
                        }),
                    'message' => $exception instanceof AppException
                        ? $exception->getMessage()
                        : 'Ndodhi një gabim i brendshëm.',
                ];
                if ($exception instanceof ValidationException) {
                    $payload['errors'] = $exception->errors();
                }

                Response::json($payload, $status);
            }

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
