<?php

declare(strict_types=1);

namespace HotelHub\Exceptions;

use HotelHub\Enums\ReservationStatus;

final class InvalidStatusTransitionException extends AppException
{
    public function __construct(ReservationStatus $from, ReservationStatus $to)
    {
        parent::__construct(
            sprintf('Rezervimi nuk mund të kalojë nga "%s" në "%s".', $from->label(), $to->label()),
            422,
            null,
            'INVALID_STATUS_TRANSITION',
        );
    }
}
