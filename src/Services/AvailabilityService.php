<?php

declare(strict_types=1);

namespace HotelHub\Services;

use HotelHub\Core\Validator;
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
        $validated = Validator::validate($input, [
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|positive|max_value:6',
        ]);

        return $this->availability->search(
            $validated['check_in'],
            $validated['check_out'],
            (int) $validated['guests'],
        );
    }
}
