<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money(string|int|float|null $amount): string
    {
        return \HotelHub\Core\Money::format($amount);
    }
}

if (!function_exists('date_al')) {
    /**
     * 2026-12-12 → 12.12.2026 (ose me orë për DATETIME).
     */
    function date_al(?string $value, bool $withTime = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date($withTime ? 'd.m.Y H:i' : 'd.m.Y', $timestamp);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(\HotelHub\Core\Csrf::token()) . '">';
    }
}

if (!function_exists('field_error')) {
    /**
     * @param array<string, string> $errors
     */
    function field_error(array $errors, string $field): string
    {
        return isset($errors[$field])
            ? '<small class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</small>'
            : '';
    }
}

if (!function_exists('reservation_badge')) {
    function reservation_badge(string $status): string
    {
        $enum = \HotelHub\Enums\ReservationStatus::tryFrom($status);

        return '<span class="status-badge status-badge--' . e($status) . '">'
            . e($enum?->label() ?? $status) . '</span>';
    }
}
