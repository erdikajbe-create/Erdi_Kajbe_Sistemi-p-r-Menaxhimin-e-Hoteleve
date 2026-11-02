<?php

declare(strict_types=1);

namespace HotelHub\Exceptions;

final class ValidationException extends AppException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Kontrolloni fushat e shënuara.',
    ) {
        parent::__construct($message, 422);
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
