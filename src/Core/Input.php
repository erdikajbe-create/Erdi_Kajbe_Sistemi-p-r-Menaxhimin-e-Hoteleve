<?php

declare(strict_types=1);

namespace HotelHub\Core;

final class Input
{
    /**
     * Reads scalar form input without triggering array-to-string conversion warnings.
     *
     * @param array<string, mixed> $input
     */
    public static function string(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) || is_int($value) ? (string) $value : '';
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string|list<string>>
     */
    public static function safeValues(array $input): array
    {
        $values = [];
        foreach ($input as $key => $value) {
            if (is_string($value) || is_int($value)) {
                $values[$key] = (string) $value;
            } elseif (is_array($value)) {
                $values[$key] = array_values(array_map(
                    static fn (string|int $item): string => (string) $item,
                    array_filter($value, static fn (mixed $item): bool => is_string($item) || is_int($item)),
                ));
            }
        }

        return $values;
    }
}
