<?php

declare(strict_types=1);

use HotelHub\Core\Csrf;
use HotelHub\Core\Money;
use HotelHub\Enums\ReservationStatus;

if (!function_exists('e')) {
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money(string|int|float|null $amount): string
    {
        return Money::format($amount);
    }
}

if (!function_exists('date_al')) {
    function date_al(string $value, bool $withTime = false): string
    {
        try {
            $date = new DateTimeImmutable($value);
        } catch (Throwable) {
            return $value;
        }

        $months = [
            1 => 'janar', 2 => 'shkurt', 3 => 'mars', 4 => 'prill',
            5 => 'maj', 6 => 'qershor', 7 => 'korrik', 8 => 'gusht',
            9 => 'shtator', 10 => 'tetor', 11 => 'nëntor', 12 => 'dhjetor',
        ];
        $formatted = $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');

        return $withTime ? $formatted . ', ' . $date->format('H:i') : $formatted;
    }
}

if (!function_exists('reservation_badge')) {
    function reservation_badge(string $status): string
    {
        $reservationStatus = ReservationStatus::tryFrom($status);
        $label = $reservationStatus?->label() ?? $status;

        return '<span class="status-badge status-badge--' . e($status) . '">' . e($label) . '</span>';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('field_error')) {
    /** @param array<string, string|list<string>> $errors */
    function field_error(array $errors, string $field): string
    {
        if (!isset($errors[$field])) {
            return '';
        }

        $messages = is_array($errors[$field]) ? $errors[$field] : [$errors[$field]];
        $html = '';
        foreach ($messages as $message) {
            $html .= '<small class="field-error" role="alert">' . e($message) . '</small>';
        }

        return $html;
    }
}
