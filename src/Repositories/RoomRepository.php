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
        $statement = $this->pdo->prepare('SELECT id, room_type_id, number FROM rooms WHERE id = :id');
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

    public function typeHasRooms(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM rooms WHERE room_type_id = :id)');
        $statement->execute(['id' => $id]);

        return (bool) $statement->fetchColumn();
    }
}
