<?php

declare(strict_types=1);

namespace HotelHub\Core;

use PDO;
use RuntimeException;

/**
 * Ekzekuton skedarët SQL të migrimeve me PDO, duke respektuar DELIMITER-in e trigger-ave.
 * Përdoret nga database/setup.php dhe nga testet e integrimit.
 */
final class SqlScript
{
    /**
     * @return list<string> skedarët sipas rendit: migrimet e numëruara, pastaj seed.sql
     */
    public static function files(string $databaseDir, bool $withSeed = true): array
    {
        $files = glob($databaseDir . '/migrations/*.sql');
        if ($files === false) {
            throw new RuntimeException('Migrimet e databazës nuk mund të lexohen.');
        }
        sort($files, SORT_STRING);
        if ($withSeed) {
            $files[] = $databaseDir . '/seed.sql';
        }

        return $files;
    }

    public static function runFile(PDO $pdo, string $file): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException(sprintf('Skedari "%s" nuk mund të lexohet.', basename($file)));
        }

        foreach (self::split($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    /**
     * @return list<string>
     */
    public static function split(string $sql): array
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

        if (trim(preg_replace('/^\s*--[^\r\n]*$/m', '', $buffer) ?? '') !== '') {
            throw new RuntimeException('Një komandë SQL mbeti e papërfunduar.');
        }

        return $statements;
    }
}
