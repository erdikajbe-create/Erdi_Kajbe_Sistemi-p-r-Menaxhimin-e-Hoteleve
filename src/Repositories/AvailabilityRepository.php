<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

final readonly class AvailabilityRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<array<string, int|string>>
     */
    public function search(string $checkIn, string $checkOut, int $guests): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.number, r.floor, rt.name AS room_type_name, rt.capacity, '
            . 'rt.base_price, DATEDIFF(:nights_check_out, :nights_check_in) AS nights, '
            . 'CAST(rt.base_price * DATEDIFF(:total_check_out, :total_check_in) AS DECIMAL(10,2)) AS total_amount '
            . 'FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id '
            . 'WHERE r.status <> \'maintenance\' AND rt.capacity >= :guests '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM reservations res WHERE res.room_id = r.id '
            . 'AND res.status <> \'cancelled\' '
            . 'AND res.check_in < :overlap_check_out AND res.check_out > :overlap_check_in'
            . ') ORDER BY rt.base_price, r.number',
        );
        $statement->execute([
            'nights_check_out' => $checkOut,
            'nights_check_in' => $checkIn,
            'total_check_out' => $checkOut,
            'total_check_in' => $checkIn,
            'guests' => $guests,
            'overlap_check_out' => $checkOut,
            'overlap_check_in' => $checkIn,
        ]);

        return $statement->fetchAll();
    }
}
