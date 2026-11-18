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
            if (!$exception instanceof AppException || $exception->getStatusCode() >= 500) {
                error_log((string) $exception);
            }

            if (Request::isApi()) {
                self::renderJson($exception, $debug);
            }

            if ($exception instanceof AppException) {
                $status = $exception->getStatusCode();
                Response::html(
                    View::render('errors/error', [
                        'title' => $status === 403 ? 'Qasje e ndaluar' : $exception->getMessage(),
                        'message' => $exception->getMessage(),
                        'errors' => $exception instanceof ValidationException ? $exception->errors() : [],
                    ]),
                    $status,
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

    /**
     * Një format i vetëm për të gjitha gabimet e /api (KJF11):
     * {"success": false, "error": "CODE", "message": "...", "errors"?: {...}}
     */
    private static function renderJson(Throwable $exception, bool $debug): never
    {
        if ($exception instanceof AppException) {
            $body = [
                'success' => false,
                'error' => $exception->getErrorCode(),
                'message' => $exception->getMessage(),
            ];
            if ($exception instanceof ValidationException) {
                $body['errors'] = $exception->errors();
            }

            Response::json($body, $exception->getStatusCode());
        }

        $body = ['success' => false, 'error' => 'SERVER_ERROR', 'message' => 'Ndodhi një gabim i brendshëm.'];
        if ($debug) {
            $body['debug'] = $exception->getMessage();
        }

        Response::json($body, 500);
    }
}
