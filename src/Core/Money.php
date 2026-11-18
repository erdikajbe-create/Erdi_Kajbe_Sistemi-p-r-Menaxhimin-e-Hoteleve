<?php

declare(strict_types=1);

namespace HotelHub\Core;

/**
 * Llogaritje me para si string me 2 shifra dhjetore (bcmath), pa float,
 * në përputhje me DECIMAL(10,2) në databazë.
 */
final class Money
{
    private const SCALE = 2;

    public static function normalize(string|int $amount): string
    {
        return bcadd((string) $amount, '0', self::SCALE);
    }

    public static function add(string ...$amounts): string
    {
        $total = '0.00';
        foreach ($amounts as $amount) {
            $total = bcadd($total, $amount, self::SCALE);
        }

        return $total;
    }

    public static function sub(string $left, string $right): string
    {
        return bcsub($left, $right, self::SCALE);
    }

    public static function mul(string $amount, int $times): string
    {
        return bcmul($amount, (string) $times, self::SCALE);
    }

    public static function compare(string $left, string $right): int
    {
        return bccomp($left, $right, self::SCALE);
    }

    public static function isValid(string $amount): bool
    {
        return preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $amount) === 1;
    }

    public static function format(string|int|float|null $amount): string
    {
        $value = self::normalize(is_float($amount) ? number_format($amount, 2, '.', '') : (string) ($amount ?? '0'));
        [$whole, $cents] = explode('.', ltrim($value, '-'));
        $sign = str_starts_with($value, '-') ? '−' : '';

        return $sign . number_format((int) $whole, 0, ',', '.') . ',' . $cents . ' €';
    }
}
