<?php

declare(strict_types=1);

use Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ky skript ekzekutohet vetëm nga terminali.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

Dotenv::createImmutable($projectRoot)->safeLoad();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'hotelhub';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
    fwrite(STDERR, "Emri i databazës përmban karaktere të palejuara.\n");
    exit(1);
}

try {
    $pdo = new \PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
        $username,
        $password,
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );

    date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Belgrade');
    $pdo->exec(sprintf("SET time_zone = '%s'", date('P')));   // CURDATE() në seed.sql = "sot" e aplikacionit

    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $versionComment = (string) $pdo->query('SELECT @@version_comment')->fetchColumn();
    $isMariaDb = stripos($version . ' ' . $versionComment, 'mariadb') !== false;
    $minimumVersion = $isMariaDb ? '10.11.0' : '8.0.16';
    if (preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) !== 1
        || version_compare($matches[1], $minimumVersion, '<')) {
        throw new \RuntimeException(
            sprintf(
                'U gjet %s. Kërkohet MySQL 8.0.16+ ose MariaDB 10.11+ për zbatimin e CHECK constraints.',
                $version,
            ),
        );
    }

    $pdo->exec(
        sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $database,
        ),
    );
    $pdo->exec(sprintf('USE `%s`', $database));
    $tableCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables "
        . "WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'",
    )->fetchColumn();
    if ($tableCount > 0) {
        throw new \RuntimeException(
            sprintf(
                'Databaza "%s" përmban tashmë tabela. Setup-i nuk i fshin të dhënat; përdorni databazë të re.',
                $database,
            ),
        );
    }

    foreach (\HotelHub\Core\SqlScript::files(__DIR__) as $file) {
        \HotelHub\Core\SqlScript::runFile($pdo, $file);
        fwrite(STDOUT, sprintf("U ekzekutua: %s\n", basename($file)));
    }

    fwrite(STDOUT, sprintf("Databaza \"%s\" u krijua me sukses në %s %s.\n", $database, $isMariaDb ? 'MariaDB' : 'MySQL', $version));
} catch (\Throwable $exception) {
    fwrite(STDERR, "Gabim gjatë setup-it: " . $exception->getMessage() . "\n");
    exit(1);
}
