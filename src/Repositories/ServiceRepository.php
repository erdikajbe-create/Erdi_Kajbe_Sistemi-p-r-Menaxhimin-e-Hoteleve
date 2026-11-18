<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

/**
 * Katalogu i shërbimeve shtesë (mëngjes, parking, spa).
 */
final readonly class ServiceRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT id, name, price, active FROM services'
            . ($onlyActive ? ' WHERE active = 1' : '')
            . ' ORDER BY name';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, price, active FROM services WHERE id = :id');
        $statement->execute(['id' => $id]);
        $service = $statement->fetch();

        return $service === false ? null : $service;
    }

    public function create(string $name, string $price): int
    {
        $statement = $this->pdo->prepare('INSERT INTO services (name, price, active) VALUES (:name, :price, 1)');
        $statement->execute(['name' => $name, 'price' => $price]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $price, bool $active): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE services SET name = :name, price = :price, active = :active WHERE id = :id',
        );
        $statement->execute(['name' => $name, 'price' => $price, 'active' => (int) $active, 'id' => $id]);
    }
}
