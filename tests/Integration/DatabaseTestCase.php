<?php

declare(strict_types=1);

namespace HotelHub\Tests\Integration;

use Closure;
use DateTimeImmutable;
use HotelHub\Core\SqlScript;
use HotelHub\Repositories\GuestRepository;
use HotelHub\Repositories\ReservationRepository;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\ServiceRepository;
use HotelHub\Services\InvoiceService;
use HotelHub\Services\ReservationService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Testet e integrimit punojnë me një databazë MySQL të veçantë, që rikrijohet nga migrimet.
 * Aktivizohen vetëm kur vendoset TEST_DB_DATABASE (emri duhet të mbarojë me _test):
 *
 *   TEST_DB_DATABASE=hotelhub_test TEST_DB_USERNAME=root TEST_DB_PASSWORD= composer test
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $pdo = null;

    public const STAFF = ['id' => 2, 'name' => 'Arta Recepsioni', 'email' => 'recepsion@hotelhub.test', 'role' => 'receptionist'];
    public const GUEST = ['id' => 3, 'name' => 'Besa Mysafire', 'email' => 'besa@example.test', 'role' => 'guest'];

    public static function setUpBeforeClass(): void
    {
        $database = (string) getenv('TEST_DB_DATABASE');
        if ($database === '') {
            self::markTestSkipped('Vendos TEST_DB_DATABASE për testet e integrimit me MySQL.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+_test$/', $database)) {
            self::fail('TEST_DB_DATABASE duhet të mbarojë me "_test", sepse databaza fshihet dhe rikrijohet.');
        }

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', getenv('TEST_DB_HOST') ?: '127.0.0.1', getenv('TEST_DB_PORT') ?: '3306'),
            getenv('TEST_DB_USERNAME') ?: 'root',
            (string) getenv('TEST_DB_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false],
        );
        $pdo->exec(sprintf("SET time_zone = '%s'", date('P')));
        $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
        $pdo->exec(sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $database));
        $pdo->exec(sprintf('USE `%s`', $database));
        foreach (SqlScript::files(dirname(__DIR__, 2) . '/database') as $file) {
            SqlScript::runFile($pdo, $file);
        }

        self::$pdo = $pdo;
    }

    /**
     * @param string|null $now p.sh. "+5 days 10:00"; null = koha reale
     */
    protected function reservations(?string $now = null): ReservationService
    {
        $clock = $now === null ? null : static fn (): DateTimeImmutable => new DateTimeImmutable($now);

        return new ReservationService(
            self::$pdo,
            new ReservationRepository(self::$pdo),
            new RoomRepository(self::$pdo),
            new GuestRepository(self::$pdo),
            new ServiceRepository(self::$pdo),
            $clock instanceof Closure ? $clock : null,
        );
    }

    protected function invoice(int $reservationId): array
    {
        $repository = new ReservationRepository(self::$pdo);

        return (new InvoiceService($repository))->forReservation($repository->find($reservationId));
    }

    protected static function day(int $offset): string
    {
        return (new DateTimeImmutable('today'))->modify(sprintf('%+d days', $offset))->format('Y-m-d');
    }

    protected static function value(string $sql, array $params = []): mixed
    {
        $statement = self::$pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }
}
