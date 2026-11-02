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
    ): void {
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
    }
}
