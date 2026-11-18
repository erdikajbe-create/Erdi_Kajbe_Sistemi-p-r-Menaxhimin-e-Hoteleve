<?php

declare(strict_types=1);

namespace HotelHub\Tests\Enums;

use HotelHub\Enums\ReservationStatus as S;
use PHPUnit\Framework\TestCase;

/**
 * RB06: statusi kalon vetëm përpara; anulimi vetëm nga pending ose confirmed.
 */
final class ReservationStatusTest extends TestCase
{
    public function testValuesMatchDatabaseEnum(): void
    {
        self::assertSame(
            ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'],
            array_column(S::cases(), 'value'),
        );
    }

    public function testAllowedTransitions(): void
    {
        self::assertTrue(S::Pending->canTransitionTo(S::Confirmed));
        self::assertTrue(S::Pending->canTransitionTo(S::Cancelled));
        self::assertTrue(S::Confirmed->canTransitionTo(S::CheckedIn));
        self::assertTrue(S::Confirmed->canTransitionTo(S::Cancelled));
        self::assertTrue(S::CheckedIn->canTransitionTo(S::CheckedOut));
    }

    public function testForbiddenTransitions(): void
    {
        self::assertFalse(S::Pending->canTransitionTo(S::CheckedIn), 'check-in pa konfirmim');
        self::assertFalse(S::CheckedIn->canTransitionTo(S::Cancelled), 'anulim pas check-in');
        self::assertFalse(S::Confirmed->canTransitionTo(S::Pending), 'kthim prapa');
        self::assertFalse(S::Confirmed->canTransitionTo(S::CheckedOut), 'check-out pa check-in');
    }

    public function testFinalStatusesCannotChange(): void
    {
        foreach ([S::CheckedOut, S::Cancelled] as $final) {
            foreach (S::cases() as $target) {
                self::assertFalse($final->canTransitionTo($target), $final->value . ' → ' . $target->value);
            }
        }
    }

    public function testOnlyCancelledReleasesTheRoom(): void
    {
        foreach (S::cases() as $status) {
            self::assertSame($status !== S::Cancelled, $status->blocksRoom(), $status->value);
        }
    }
}
