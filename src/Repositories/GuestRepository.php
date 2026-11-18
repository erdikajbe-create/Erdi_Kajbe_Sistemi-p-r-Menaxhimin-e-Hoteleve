<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

final readonly class GuestRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createForUser(
        int $userId,
        string $firstName,
        string $lastName,
        string $email,
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO guests (user_id, first_name, last_name, email) '
            . 'VALUES (:user_id, :first_name, :last_name, :email)',
        );
        $statement->execute([
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mysafir pa llogari, i regjistruar nga recepsioni (UC2).
     */
    public function createWalkIn(
        string $firstName,
        string $lastName,
        ?string $email,
        ?string $phone,
        ?string $documentNumber,
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO guests (first_name, last_name, email, phone, document_number) '
            . 'VALUES (:first_name, :last_name, :email, :phone, :document_number)',
        );
        $statement->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'document_number' => $documentNumber,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, first_name, last_name, email, phone, document_number FROM guests WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $guest = $statement->fetch();

        return $guest === false ? null : $guest;
    }

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, first_name, last_name, email, phone, document_number '
            . 'FROM guests WHERE user_id = :user_id',
        );
        $statement->execute(['user_id' => $userId]);
        $guest = $statement->fetch();

        return $guest === false ? null : $guest;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(int $limit = 500): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, phone FROM guests '
            . 'ORDER BY last_name, first_name LIMIT :limit',
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
