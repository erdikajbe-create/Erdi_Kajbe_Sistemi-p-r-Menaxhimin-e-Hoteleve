<?php

declare(strict_types=1);

namespace HotelHub\Core;

use DateTimeImmutable;

/**
 * Rregulla të pastra (pa databazë) për datat e qëndrimit, që testohen me PHPUnit.
 */
final class StayRules
{
    public const GUEST_CANCEL_HOURS = 24;
    public const CHECK_IN_TIME = '14:00';

    /**
     * RB01 + RB02: mbivendosje me krahasim strikt; largimi dhe mbërritja në të njëjtën ditë lejohen.
     */
    public static function overlaps(string $existingIn, string $existingOut, string $newIn, string $newOut): bool
    {
        return $existingIn < $newOut && $existingOut > $newIn;
    }

    public static function nights(string $checkIn, string $checkOut): int
    {
        $diff = (new DateTimeImmutable($checkIn))->diff(new DateTimeImmutable($checkOut));

        return $diff->invert === 1 ? -$diff->days : (int) $diff->days;
    }

    /**
     * RB07: mysafiri anulon vetë deri 24 orë para mbërritjes (ora e check-in 14:00).
     */
    public static function guestCanCancel(string $checkIn, DateTimeImmutable $now): bool
    {
        $arrival = new DateTimeImmutable($checkIn . ' ' . self::CHECK_IN_TIME);

        return $now <= $arrival->modify(sprintf('-%d hours', self::GUEST_CANCEL_HOURS));
    }

    /**
     * KF16: check-in në ditën e mbërritjes ose më vonë, por para datës së largimit.
     */
    public static function canCheckInOn(string $checkIn, string $checkOut, string $today): bool
    {
        return $today >= $checkIn && $today < $checkOut;
    }

    public static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
