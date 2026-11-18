<?php

declare(strict_types=1);

namespace HotelHub\Tests\Core;

use HotelHub\Core\SqlScript;
use PHPUnit\Framework\TestCase;

final class SqlScriptTest extends TestCase
{
    public function testSplitsStatementsAndRespectsCustomDelimiter(): void
    {
        $sql = "-- koment\nCREATE TABLE a (id INT);\nDELIMITER $$\nCREATE TRIGGER t BEFORE INSERT ON a FOR EACH ROW BEGIN SET @x = 1; END$$\nDELIMITER ;\nINSERT INTO a VALUES (1);\n-- fund\n";

        $statements = SqlScript::split($sql);

        self::assertCount(3, $statements);
        self::assertStringContainsString('SET @x = 1; END', $statements[1]);
    }

    public function testAllMigrationsCanBeParsed(): void
    {
        foreach (SqlScript::files(dirname(__DIR__, 2) . '/database') as $file) {
            self::assertNotEmpty(SqlScript::split((string) file_get_contents($file)), basename($file));
        }
    }
}
