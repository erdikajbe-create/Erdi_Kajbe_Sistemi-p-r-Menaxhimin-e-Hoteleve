<?php

declare(strict_types=1);

namespace HotelHub\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Receptionist = 'receptionist';
    case Guest = 'guest';
}
