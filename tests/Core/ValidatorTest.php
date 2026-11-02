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
}
