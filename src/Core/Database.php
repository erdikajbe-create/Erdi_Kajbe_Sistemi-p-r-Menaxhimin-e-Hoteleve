<?php

declare(strict_types=1);

namespace HotelHub\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    /**
     * @param array{host: string, port: string, database: string, username: string, password: string} $config
     */
    public static function connect(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database'],
        );

        try {
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Lidhja me databazën dështoi.', 0, $exception);
        }
    }
}
