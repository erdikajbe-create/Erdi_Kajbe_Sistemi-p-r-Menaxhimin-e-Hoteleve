<?php

declare(strict_types=1);

namespace HotelHub\Tests\Services;

use HotelHub\Exceptions\ValidationException;
use HotelHub\Services\AvailabilityService;
use PHPUnit\Framework\TestCase;

final class AvailabilityCriteriaTest extends TestCase
{
    public function testAcceptsValidStayAndReturnsNormalizedCriteria(): void
    {
        self::assertSame(
            ['check_in' => '2026-12-10', 'check_out' => '2026-12-12', 'guests' => '2'],
            AvailabilityService::validateCriteria([
                'check_in' => '2026-12-10',
                'check_out' => '2026-12-12',
                'guests' => '2',
            ], '2026-11-25'),
        );
    }

    public function testRejectsMissingDates(): void
    {
        try {
            AvailabilityService::validateCriteria([], '2026-11-25');
            self::fail('Kriteret bosh duhej të refuzoheshin.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('check_in', $exception->errors());
            self::assertArrayHasKey('check_out', $exception->errors());
            self::assertArrayHasKey('guests', $exception->errors());
        }
    }

    public function testRejectsPastArrivalAndInvalidDateOrder(): void
    {
        try {
            AvailabilityService::validateCriteria([
                'check_in' => '2026-11-24',
                'check_out' => '2026-11-26',
                'guests' => '1',
            ], '2026-11-25');
            self::fail('Data në të kaluarën duhej refuzuar.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('check_in', $exception->errors());
        }

        try {
            AvailabilityService::validateCriteria([
                'check_in' => '2026-12-10',
                'check_out' => '2026-12-10',
                'guests' => '1',
            ], '2026-11-25');
            self::fail('Largimi në të njëjtën ditë duhej refuzuar.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('check_out', $exception->errors());
        }
    }
}
