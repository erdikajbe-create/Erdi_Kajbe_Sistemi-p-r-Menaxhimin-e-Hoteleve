<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use HotelHub\Core\Auth;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testStaffCheckMethodExistsForReservationPages(): void
    {
        self::assertTrue(method_exists(Auth::class, 'isStaff'));
    }
}
