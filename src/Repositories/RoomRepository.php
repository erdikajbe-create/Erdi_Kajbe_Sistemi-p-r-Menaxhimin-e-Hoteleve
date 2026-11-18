<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

final readonly class RoomRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT r.id, r.number, r.floor, r.status, rt.id AS room_type_id, '
            . 'rt.name AS room_type_name, rt.capacity '
            . 'FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id ORDER BY r.number',
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, room_type_id, number, status FROM rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
        $room = $statement->fetch();

        return $room === false ? null : $room;
    }

    public function create(int $typeId, string $number, int $floor): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO rooms (room_type_id, number, floor) '
            . 'VALUES (:room_type_id, :number, :floor)',
        );
        $statement->execute([
            'room_type_id' => $typeId,
            'number' => $number,
            'floor' => $floor,
        ]);
    }

    public function update(int $id, int $typeId, string $number, int $floor): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE rooms SET room_type_id = :room_type_id, number = :number, floor = :floor WHERE id = :id',
        );
        $statement->execute([
            'room_type_id' => $typeId,
            'number' => $number,
            'floor' => $floor,
            'id' => $id,
        ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE rooms SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function roomTypes(): array
    {
        return $this->pdo->query('SELECT id, name FROM room_types ORDER BY name')->fetchAll();
    }

    public function hasReservations(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM reservations WHERE room_id = :id)');
        $statement->execute(['id' => $id]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * KF10 + RB01 + RB04: dhomat me kapacitet të mjaftueshëm, jo në mirëmbajtje,
     * pa rezervim aktiv që mbivendoset. Përdor indeksin (room_id, check_in, check_out).
     *
     * @return list<array<string, mixed>>
     */
    public function available(string $checkIn, string $checkOut, int $guests): array
    {
        $statement = $this->pdo->prepare(
            "SELECT r.id, r.number, r.floor, r.status, rt.id AS room_type_id, rt.name AS room_type_name, "
            . "rt.description, rt.base_price, rt.capacity, "
            . "(SELECT GROUP_CONCAT(a.name ORDER BY a.name SEPARATOR ', ') FROM amenity_room_type art "
            . " JOIN amenities a ON a.id = art.amenity_id WHERE art.room_type_id = rt.id) AS amenities "
            . "FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id "
            . "WHERE r.status <> 'maintenance' AND rt.capacity >= :guests "
            . "AND NOT EXISTS (SELECT 1 FROM reservations x WHERE x.room_id = r.id "
            . "  AND x.status <> 'cancelled' AND x.check_in < :check_out AND x.check_out > :check_in) "
            . "ORDER BY rt.base_price, r.number",
        );
        $statement->execute(['guests' => $guests, 'check_in' => $checkIn, 'check_out' => $checkOut]);

        return $statement->fetchAll();
    }

    /**
     * Bllokon rreshtin e dhomës deri në fund të transaksionit (KJF08).
     */
    public function lockWithType(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.number, r.status, rt.name AS room_type_name, rt.base_price, rt.capacity '
            . 'FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id WHERE r.id = :id FOR UPDATE',
        );
        $statement->execute(['id' => $id]);
        $room = $statement->fetch();

        return $room === false ? null : $room;
    }

    public function hasActiveStay(int $id): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT EXISTS(SELECT 1 FROM reservations WHERE room_id = :id AND status = 'checked_in')",
        );
        $statement->execute(['id' => $id]);

        return (bool) $statement->fetchColumn();
    }

    public function typeHasRooms(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM rooms WHERE room_type_id = :id)');
        $statement->execute(['id' => $id]);

        return (bool) $statement->fetchColumn();
    }
}
