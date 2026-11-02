<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

final readonly class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1',
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) '
            . 'VALUES (:name, :email, :password_hash, :role)',
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listStaff(): array
    {
        $statement = $this->pdo->query(
            "SELECT id, name, email, role, phone, created_at FROM users "
            . "WHERE role IN ('admin', 'receptionist') ORDER BY role, name",
        );

        return $statement->fetchAll();
    }
}
