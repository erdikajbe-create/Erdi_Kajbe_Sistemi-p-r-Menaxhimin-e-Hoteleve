<?php

declare(strict_types=1);

namespace HotelHub\Tests\Enums;

use HotelHub\Enums\RoomStatus;
use PHPUnit\Framework\TestCase;

final class RoomStatusTest extends TestCase
{
    public function testRoomStatusesMatchDatabaseEnumValues(): void
    {
        self::assertSame(
            ['available', 'occupied', 'cleaning', 'maintenance'],
            array_column(RoomStatus::cases(), 'value'),
        );
    }
}
