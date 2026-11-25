<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use DateTimeImmutable;
use HotelHub\Core\StayRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StayRulesTest extends TestCase
{
    /**
     * RB01 + RB02 me rezervimin ekzistues 12–15 dhjetor.
     *
     * @return array<string, array{string, string, bool}>
     */
    public static function overlapCases(): array
    {
        return [
            'të njëjtat data' => ['2026-12-12', '2026-12-15', true],
            'brenda' => ['2026-12-13', '2026-12-14', true],
            'përfshin të gjithin' => ['2026-12-10', '2026-12-20', true],
            'mbivendoset në fillim' => ['2026-12-10', '2026-12-13', true],
            'mbivendoset në fund' => ['2026-12-14', '2026-12-18', true],
            'largohet kur tjetri mbërrin (RB02)' => ['2026-12-15', '2026-12-17', false],
            'mbërrin kur tjetri largohet (RB02)' => ['2026-12-10', '2026-12-12', false],
            'krejt para' => ['2026-12-01', '2026-12-05', false],
            'krejt pas' => ['2026-12-20', '2026-12-22', false],
        ];
    }

    /**
     * @dataProvider overlapCases
     */
    #[DataProvider('overlapCases')]
    public function testOverlapUsesStrictComparison(string $in, string $out, bool $expected): void
    {
        self::assertSame($expected, StayRules::overlaps('2026-12-12', '2026-12-15', $in, $out));
    }

    public function testNights(): void
    {
        self::assertSame(3, StayRules::nights('2026-12-12', '2026-12-15'));
        self::assertSame(1, StayRules::nights('2026-12-31', '2027-01-01'));
        self::assertSame(1, StayRules::nights('2026-03-28', '2026-03-29'), 'ndërrimi i orës verore');
    }

    public function testGuestCancellationWindowIs24HoursBeforeCheckInTime(): void
    {
        // mbërritja 15 dhjetor nga ora 14:00 → afati 14 dhjetor 14:00
        self::assertTrue(StayRules::guestCanCancel('2026-12-15', new DateTimeImmutable('2026-12-12 09:00')));
        self::assertTrue(StayRules::guestCanCancel('2026-12-15', new DateTimeImmutable('2026-12-14 14:00')));
        self::assertFalse(StayRules::guestCanCancel('2026-12-15', new DateTimeImmutable('2026-12-14 14:01')));
        self::assertFalse(StayRules::guestCanCancel('2026-12-15', new DateTimeImmutable('2026-12-15 08:00')));
    }

    public function testCheckInIsAllowedFromArrivalUntilBeforeDeparture(): void
    {
        self::assertFalse(StayRules::canCheckInOn('2026-12-12', '2026-12-15', '2026-12-11'));
        self::assertTrue(StayRules::canCheckInOn('2026-12-12', '2026-12-15', '2026-12-12'));
        self::assertTrue(StayRules::canCheckInOn('2026-12-12', '2026-12-15', '2026-12-14'));
        self::assertFalse(StayRules::canCheckInOn('2026-12-12', '2026-12-15', '2026-12-15'));
    }

    public function testDateValidationRejectsImpossibleDates(): void
    {
        self::assertTrue(StayRules::isValidDate('2028-02-29'));
        self::assertFalse(StayRules::isValidDate('2026-02-30'));
        self::assertFalse(StayRules::isValidDate('12.12.2026'));
        self::assertFalse(StayRules::isValidDate("2026-12-12' OR 1=1 --"));
    }
}
