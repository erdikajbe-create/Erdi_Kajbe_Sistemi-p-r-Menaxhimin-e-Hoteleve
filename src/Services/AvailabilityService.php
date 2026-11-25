<?php

declare(strict_types=1);

namespace HotelHub\Services;

use HotelHub\Core\Validator;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\AvailabilityRepository;

final readonly class AvailabilityService
{
    public function __construct(private AvailabilityRepository $availability)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return list<array<string, int|string>>
     */
    public function search(array $input): array
    {
        $validated = self::validateCriteria($input);

        return $this->availability->search(
            $validated['check_in'],
            $validated['check_out'],
            (int) $validated['guests'],
        );
    }

    /**
     * Validon kriteret e kërkimit dhe rezervimit me të njëjtat rregulla.
     *
     * @param array<string, mixed> $input
     * @return array{check_in: string, check_out: string, guests: string}
     */
    public static function validateCriteria(array $input, ?string $today = null): array
    {
        $validated = Validator::validate($input, [
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|positive|max_value:6',
        ]);

        $today ??= (new \DateTimeImmutable('today'))->format('Y-m-d');
        if ($validated['check_in'] < $today) {
            throw new ValidationException(['check_in' => 'Data e mbërritjes nuk mund të jetë në të kaluarën.']);
        }

        return $validated;
    }
}
