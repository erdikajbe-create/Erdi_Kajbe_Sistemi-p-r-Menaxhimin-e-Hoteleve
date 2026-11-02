<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use HotelHub\Core\Input;
use HotelHub\Core\Validator;
use HotelHub\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function testMalformedArrayInputBecomesAValidationError(): void
    {
        try {
            Validator::validate(['email' => ['bad']], ['email' => 'required|email']);
            self::fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('email', $exception->errors());
        }
    }

    public function testSafeValuesRetainScalarAndArrayFormValuesOnly(): void
    {
        self::assertSame(
            ['name' => 'Besa', 'amenities' => ['1', '2']],
            Input::safeValues(['name' => 'Besa', 'amenities' => [1, ['bad'], 2], 'object' => new \stdClass()]),
        );
    }
}
