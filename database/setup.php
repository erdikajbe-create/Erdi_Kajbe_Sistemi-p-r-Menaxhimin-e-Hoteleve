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

    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $versionComment = (string) $pdo->query('SELECT @@version_comment')->fetchColumn();
    if (stripos($version . ' ' . $versionComment, 'mariadb') !== false
        || preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) !== 1
        || version_compare($matches[1], '8.0.16', '<')) {
        throw new \RuntimeException(
            sprintf(
                'U gjet %s. Kërkohet MySQL 8.0.16+; XAMPP mund të vijë me MariaDB dhe të mos mbështesë CHECK.',
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

    $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
    if ($migrationFiles === false) {
        throw new \RuntimeException('Migrimet e databazës nuk mund të lexohen.');
    }
    sort($migrationFiles, SORT_STRING);
    $migrationFiles[] = __DIR__ . '/seed.sql';

    foreach ($migrationFiles as $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new \RuntimeException(sprintf('Skedari "%s" nuk mund të lexohet.', basename($file)));
        }

        foreach (splitSqlStatements($sql) as $statement) {
            $pdo->exec($statement);
        }
        fwrite(STDOUT, sprintf("U ekzekutua: %s\n", basename($file)));
    }

    fwrite(STDOUT, sprintf("Databaza \"%s\" u krijua me sukses në MySQL %s.\n", $database, $version));
} catch (\Throwable $exception) {
    fwrite(STDERR, "Gabim gjatë setup-it: " . $exception->getMessage() . "\n");
    exit(1);
}

/**
 * Splits SQL while respecting the custom DELIMITER used by the trigger migration.
 *
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    $delimiter = ';';
    $buffer = '';
    $statements = [];

    foreach (preg_split('/\R/', $sql) ?: [] as $line) {
        if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $matches) === 1) {
            $delimiter = $matches[1];
            continue;
        }

        $buffer .= $line . "\n";
        while (($position = strpos($buffer, $delimiter)) !== false) {
            $statement = trim(substr($buffer, 0, $position));
            $buffer = substr($buffer, $position + strlen($delimiter));
            if ($statement !== '' && preg_match('/^(?:--[^\r\n]*\s*)+$/s', $statement) !== 1) {
                $statements[] = $statement;
            }
        }
    }

    if (trim($buffer) !== '') {
        throw new \RuntimeException('Një komandë SQL mbeti e papërfunduar.');
    }

    return $statements;
}
