<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use HotelHub\Core\Validator;
use HotelHub\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testItReturnsOnlyValidatedFields(): void
    {
        $validated = Validator::validate(
            ['name' => 'Hotel', 'unexpected' => 'ignored'],
            ['name' => 'required|max:80'],
        );

        self::assertSame(['name' => 'Hotel'], $validated);
    }

    public function testItReportsFieldValidationErrors(): void
    {
        try {
            Validator::validate(['email' => 'not-an-email'], ['email' => 'required|email']);
            self::fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('email', $exception->errors());
        }
    }

    public function testItValidatesIsoDatesAndDateOrdering(): void
    {
        $validated = Validator::validate(
            ['check_in' => '2030-10-12', 'check_out' => '2030-10-15'],
            ['check_in' => 'required|date', 'check_out' => 'required|date|after:check_in'],
        );

        self::assertSame('2030-10-12', $validated['check_in']);
        self::assertSame('2030-10-15', $validated['check_out']);
    }

    public function testItRejectsInvalidOrNonIncreasingDates(): void
    {
        try {
            Validator::validate(
                ['check_in' => '2030-02-30', 'check_out' => '2030-02-30'],
                ['check_in' => 'required|date', 'check_out' => 'required|date|after:check_in'],
            );
            self::fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('check_in', $exception->errors());
        }
    }
}
