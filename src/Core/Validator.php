<?php

declare(strict_types=1);

namespace HotelHub\Core;

use HotelHub\Exceptions\ValidationException;
use DateTimeImmutable;

final class Validator
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    public static function validate(array $input, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $rule) {
            $value = trim(Input::string($input, $field));
            $validated[$field] = $value;
            $options = explode('|', $rule);

            if (in_array('required', $options, true) && $value === '') {
                $errors[$field] = 'Kjo fushë është e detyrueshme.';
                continue;
            }

            if ($value === '') {
                continue;
            }

            foreach ($options as $option) {
                if ($option === 'required') {
                    continue;
                }

                [$name, $argument] = array_pad(explode(':', $option, 2), 2, null);
                $error = match ($name) {
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                        ? 'Shkruani një adresë email të vlefshme.' : null,
                    'min' => mb_strlen($value) < (int) $argument
                        ? sprintf('Duhet të ketë të paktën %s karaktere.', $argument) : null,
                    'max' => mb_strlen($value) > (int) $argument
                        ? sprintf('Nuk mund të kalojë %s karaktere.', $argument) : null,
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) === false
                        ? 'Shkruani një numër të plotë të vlefshëm.' : null,
                    'positive' => filter_var($value, FILTER_VALIDATE_INT) === false
                        || (int) $value < 1 ? 'Vlera duhet të jetë më e madhe se zero.' : null,
                    'date' => self::parseDate($value) === null
                        ? 'Shkruani një datë të vlefshme.' : null,
                    'after' => self::dateIsAfter($value, Input::string($input, (string) $argument))
                        ? null : 'Data duhet të jetë pas datës së mëparshme.',
                    'after_or_equal' => self::dateIsAfterOrEqual(
                        $value,
                        $argument === 'today' ? (new DateTimeImmutable('today'))->format('Y-m-d')
                            : Input::string($input, (string) $argument),
                    ) ? null : 'Data nuk mund të jetë në të kaluarën.',
                    'max_value' => filter_var($value, FILTER_VALIDATE_INT) === false
                        || (int) $value > (int) $argument
                            ? sprintf('Vlera nuk mund të jetë më e madhe se %s.', $argument) : null,
                    'min_value' => filter_var($value, FILTER_VALIDATE_INT) === false
                        || (int) $value < (int) $argument
                            ? sprintf('Vlera duhet të jetë së paku %s.', $argument) : null,
                    default => null,
                };

                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    private static function dateIsAfter(string $value, string $otherValue): bool
    {
        $date = self::parseDate($value);
        $otherDate = self::parseDate($otherValue);

        return $date !== null && $otherDate !== null && $date > $otherDate;
    }

    private static function dateIsAfterOrEqual(string $value, string $otherValue): bool
    {
        $date = self::parseDate($value);
        $otherDate = self::parseDate($otherValue);

        return $date !== null && $otherDate !== null && $date >= $otherDate;
    }

    private static function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
