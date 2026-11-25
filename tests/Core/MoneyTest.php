<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use HotelHub\Core\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testArithmeticHasNoFloatRoundingErrors(): void
    {
        // me float: 0.1 + 0.2 = 0.30000000000000004
        self::assertSame('0.30', Money::add('0.10', '0.20'));
        self::assertSame('195.00', Money::mul('65.00', 3));
        self::assertSame('0.00', Money::sub(Money::add('285.00', '17.00'), '302.00'));
    }

    public function testValidation(): void
    {
        self::assertTrue(Money::isValid('120'));
        self::assertTrue(Money::isValid('120.5'));
        self::assertTrue(Money::isValid('120.50'));
        self::assertFalse(Money::isValid('120.505'));
        self::assertFalse(Money::isValid('-5'));
        self::assertFalse(Money::isValid('1e3'));
    }

    public function testFormat(): void
    {
        self::assertSame('1.234,50 €', Money::format('1234.5'));
        self::assertSame('0,00 €', Money::format(null));
        self::assertSame('−12,00 €', Money::format('-12'));
    }
}
