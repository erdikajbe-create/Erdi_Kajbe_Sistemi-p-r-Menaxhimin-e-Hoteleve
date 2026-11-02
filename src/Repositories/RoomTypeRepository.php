<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

final readonly class RoomTypeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, description, base_price, capacity FROM room_types ORDER BY name',
        );
        $types = $statement->fetchAll();
        $amenities = $this->pdo->query(
            'SELECT art.room_type_id, a.id, a.name FROM amenity_room_type art '
            . 'JOIN amenities a ON a.id = art.amenity_id ORDER BY a.name',
        )->fetchAll();
        $byType = [];

        foreach ($amenities as $amenity) {
            $byType[(int) $amenity['room_type_id']][] = $amenity;
        }

        foreach ($types as &$type) {
            $type['amenities'] = $byType[(int) $type['id']] ?? [];
        }
        unset($type);

        return $types;
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, description, base_price, capacity FROM room_types WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $type = $statement->fetch();

        if ($type === false) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT a.id, a.name FROM amenities a '
            . 'JOIN amenity_room_type art ON art.amenity_id = a.id '
            . 'WHERE art.room_type_id = :id ORDER BY a.name',
        );
        $statement->execute(['id' => $id]);
        $type['amenities'] = $statement->fetchAll();

        return $type;
    }

    public function create(string $name, ?string $description, string $price, int $capacity): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO room_types (name, description, base_price, capacity) '
            . 'VALUES (:name, :description, :base_price, :capacity)',
        );
        $statement->execute([
            'name' => $name,
            'description' => $description,
            'base_price' => $price,
            'capacity' => $capacity,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, ?string $description, string $price, int $capacity): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE room_types SET name = :name, description = :description, '
            . 'base_price = :base_price, capacity = :capacity WHERE id = :id',
        );
        $statement->execute([
            'name' => $name,
            'description' => $description,
            'base_price' => $price,
            'capacity' => $capacity,
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM room_types WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function setAmenities(int $id, array $amenityIds): void
    {
        $delete = $this->pdo->prepare('DELETE FROM amenity_room_type WHERE room_type_id = :id');
        $delete->execute(['id' => $id]);

        $insert = $this->pdo->prepare(
            'INSERT INTO amenity_room_type (amenity_id, room_type_id) VALUES (:amenity_id, :room_type_id)',
        );
        foreach (array_unique($amenityIds) as $amenityId) {
            $insert->execute(['amenity_id' => (int) $amenityId, 'room_type_id' => $id]);
        }
    }

    public function amenities(): array
    {
        return $this->pdo->query('SELECT id, name FROM amenities ORDER BY name')->fetchAll();
    }

    public function createAmenity(string $name): void
    {
        $statement = $this->pdo->prepare('INSERT INTO amenities (name) VALUES (:name)');
        $statement->execute(['name' => $name]);
    }

    public function updateAmenity(int $id, string $name): void
    {
        $statement = $this->pdo->prepare('UPDATE amenities SET name = :name WHERE id = :id');
        $statement->execute(['name' => $name, 'id' => $id]);
    }

    public function deleteAmenity(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM amenities WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
