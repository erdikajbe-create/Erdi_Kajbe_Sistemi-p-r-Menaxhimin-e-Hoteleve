<?php

declare(strict_types=1);

namespace HotelHub\Services;

use HotelHub\Core\Input;
use HotelHub\Core\Money;
use HotelHub\Core\StayRules;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\RoomRepository;

final readonly class AvailabilityService
{
    public const MAX_NIGHTS = 60;
    public const MAX_GUESTS = 6;

    public function __construct(private RoomRepository $rooms)
    {
    }

    /**
     * KF10: dhoma të lira sipas datës së mbërritjes, largimit dhe numrit të personave.
     *
     * @param array<string, mixed> $input check_in, check_out, guests
     * @return array{check_in: string, check_out: string, nights: int, guests: int, rooms: list<array<string, mixed>>}
     */
    public function search(array $input, string $today): array
    {
        $criteria = self::validateCriteria($input, $today);
        $rooms = [];

        foreach ($this->rooms->available($criteria['check_in'], $criteria['check_out'], $criteria['guests']) as $room) {
            $rooms[] = [
                'id' => (int) $room['id'],
                'number' => $room['number'],
                'floor' => (int) $room['floor'],
                'room_type' => $room['room_type_name'],
                'description' => $room['description'],
                'capacity' => (int) $room['capacity'],
                'amenities' => $room['amenities'] === null ? [] : explode(', ', $room['amenities']),
                'price_per_night' => Money::normalize($room['base_price']),
                'total' => Money::mul($room['base_price'], $criteria['nights']),
            ];
        }

        return $criteria + ['rooms' => $rooms];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{check_in: string, check_out: string, nights: int, guests: int}
     */
    public static function validateCriteria(array $input, string $today): array
    {
        $errors = [];
        $checkIn = trim(Input::string($input, 'check_in'));
        $checkOut = trim(Input::string($input, 'check_out'));
        $guests = trim(Input::string($input, 'guests'));

        if (!StayRules::isValidDate($checkIn)) {
            $errors['check_in'] = 'Zgjidhni datën e mbërritjes.';
        } elseif ($checkIn < $today) {
            $errors['check_in'] = 'Data e mbërritjes nuk mund të jetë në të kaluarën.';
        }

        if (!StayRules::isValidDate($checkOut)) {
            $errors['check_out'] = 'Zgjidhni datën e largimit.';
        } elseif (!isset($errors['check_in']) && $checkOut <= $checkIn) {
            $errors['check_out'] = 'Data e largimit duhet të jetë pas mbërritjes.';
        } elseif (!isset($errors['check_in']) && StayRules::nights($checkIn, $checkOut) > self::MAX_NIGHTS) {
            $errors['check_out'] = sprintf('Qëndrimi nuk mund të kalojë %d netë.', self::MAX_NIGHTS);
        }

        if (filter_var($guests, FILTER_VALIDATE_INT) === false || (int) $guests < 1 || (int) $guests > self::MAX_GUESTS) {
            $errors['guests'] = sprintf('Numri i personave duhet të jetë 1–%d.', self::MAX_GUESTS);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => StayRules::nights($checkIn, $checkOut),
            'guests' => (int) $guests,
        ];
    }
}
