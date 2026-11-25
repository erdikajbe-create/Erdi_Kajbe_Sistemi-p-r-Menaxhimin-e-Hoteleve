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

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
