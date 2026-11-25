<?php

declare(strict_types=1);

namespace HotelHub\Exceptions;

final class NotFoundException extends AppException
{
    public function __construct(string $message, string $errorCode = 'NOT_FOUND')
    {
        parent::__construct($message, 404, null, $errorCode);
    }
}
