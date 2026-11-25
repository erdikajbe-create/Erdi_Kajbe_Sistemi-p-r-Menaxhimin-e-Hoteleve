<?php

declare(strict_types=1);

namespace HotelHub\Exceptions;

use Throwable;

final class RoomNotAvailableException extends AppException
{
    public function __construct(string $message = 'Dhoma nuk është më e lirë për datat e zgjedhura.', ?Throwable $previous = null)
    {
        parent::__construct($message, 409, $previous, 'ROOM_NOT_AVAILABLE');
    }
}
