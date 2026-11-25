<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/Core/helpers.php';

final class HelpersTest extends TestCase
{
    public function testAlbanianDateAndMoneyFormatting(): void
    {
        self::assertSame('12 dhjetor 2026', date_al('2026-12-12'));
        self::assertSame('12 dhjetor 2026, 14:30', date_al('2026-12-12 14:30:00', true));
        self::assertSame('1.250,50 €', money('1250.50'));
    }

    public function testReservationBadgeEscapesUnknownStatus(): void
    {
        self::assertSame(
            '<span class="status-badge status-badge--confirmed">Konfirmuar</span>',
            reservation_badge('confirmed'),
        );
        self::assertStringNotContainsString('<script>', reservation_badge('<script>'));
    }

    public function testFieldErrorsAreEscaped(): void
    {
        self::assertSame(
            '<small class="field-error" role="alert">&lt;script&gt;</small>',
            field_error(['name' => '<script>'], 'name'),
        );
        self::assertSame('', field_error([], 'missing'));
    }
}
