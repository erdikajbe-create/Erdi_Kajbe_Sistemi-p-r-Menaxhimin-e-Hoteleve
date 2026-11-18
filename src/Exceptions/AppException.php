<?php

declare(strict_types=1);

namespace HotelHub\Exceptions;

use RuntimeException;
use Throwable;

class AppException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        ?Throwable $previous = null,
        private readonly ?string $errorCode = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Kodi i qëndrueshëm që lexohet nga klientët e /api (KJF11).
     */
    public function getErrorCode(): string
    {
        return $this->errorCode ?? match ($this->statusCode) {
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'TOO_MANY_REQUESTS',
            default => 'BAD_REQUEST',
        };
    }
}
