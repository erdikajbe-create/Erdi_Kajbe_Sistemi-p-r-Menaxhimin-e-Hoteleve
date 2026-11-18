<?php

declare(strict_types=1);

namespace HotelHub\Tests\Integration;

use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\RoomNotAvailableException;
use HotelHub\Exceptions\ValidationException;
use PDOException;

/**
 * Rregullat kritike të Raportit 1 kundër MySQL real: mbivendosja, kalimet e statusit,
 * autorizimi, check-out me bilanc dhe çmimi historik i shërbimeve.
 * Dhomat e seed-it: 1=101 Single 40€, 3=201 Double 65€, 7=302 maintenance, 8=401 Suite 120€.
 */
final class ReservationServiceTest extends DatabaseTestCase
{
    public function testStaffReservationIsConfirmedWithServerCalculatedTotal(): void
    {
        $id = $this->reservations()->create([
            'room_id' => '3', 'guest_id' => '2', 'check_in' => self::day(100), 'check_out' => self::day(103),
            'adults' => '2', 'status' => 'checked_out', 'total_amount' => '1', 'code' => 'HACKED00',
        ], self::STAFF);

        $row = self::$pdo->query("SELECT status, total_amount, code, created_by FROM reservations WHERE id = $id")->fetch();
        self::assertSame('confirmed', $row['status']);
        self::assertSame('195.00', $row['total_amount'], '3 netë × 65 €');
        self::assertNotSame('HACKED00', $row['code']);
        self::assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $row['code']);
        self::assertSame(2, (int) $row['created_by']);
    }

    public function testGuestReservationIsPendingAndAlwaysForTheGuestHimself(): void
    {
        $id = $this->reservations()->create([
            'room_id' => '3', 'guest_id' => '2', 'check_in' => self::day(110), 'check_out' => self::day(112), 'adults' => '2',
        ], self::GUEST);

        self::assertSame('pending', self::value('SELECT status FROM reservations WHERE id = ?', [$id]));
        self::assertSame(3, (int) self::value(
            'SELECT g.user_id FROM reservations r JOIN guests g ON g.id = r.guest_id WHERE r.id = ?',
            [$id],
        ), 'guest_id nga klienti injorohet');
    }

    public function testOverlappingReservationIsRejectedButAdjacentAndCancelledAreAllowed(): void
    {
        $service = $this->reservations();
        $base = ['room_id' => '8', 'guest_id' => '2', 'adults' => '2'];
        $first = $service->create($base + ['check_in' => self::day(120), 'check_out' => self::day(123)], self::STAFF);

        try {
            $service->create($base + ['check_in' => self::day(122), 'check_out' => self::day(125)], self::STAFF);
            self::fail('Rezervimi i mbivendosur duhej refuzuar.');
        } catch (RoomNotAvailableException $exception) {
            self::assertSame(409, $exception->getStatusCode());
            self::assertSame('ROOM_NOT_AVAILABLE', $exception->getErrorCode());
        }

        // RB02: largimi dhe mbërritja në të njëjtën ditë
        $adjacent = $service->create($base + ['check_in' => self::day(123), 'check_out' => self::day(125)], self::STAFF);
        self::assertGreaterThan($first, $adjacent);

        // RB01: rezervimet e anuluara nuk bllokojnë
        $service->cancel($first, self::STAFF);
        $service->create($base + ['check_in' => self::day(121), 'check_out' => self::day(122)], self::STAFF);
        self::assertSame(2, (int) self::value(
            "SELECT COUNT(*) FROM reservations WHERE room_id = 8 AND status <> 'cancelled' AND check_in BETWEEN ? AND ?",
            [self::day(120), self::day(125)],
        ));
    }

    public function testCapacityPastDatesAndMaintenanceAreRejected(): void
    {
        $service = $this->reservations();

        try {
            $service->create(['room_id' => '1', 'guest_id' => '2', 'check_in' => self::day(130), 'check_out' => self::day(131), 'adults' => '1', 'children' => '1'], self::STAFF);
            self::fail('Kapaciteti 1 duhej të refuzonte 2 persona.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('adults', $exception->errors());
        }

        try {
            $service->create(['room_id' => '3', 'guest_id' => '2', 'check_in' => self::day(-1), 'check_out' => self::day(1), 'adults' => '1'], self::STAFF);
            self::fail('Data në të kaluarën duhej refuzuar.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('check_in', $exception->errors());
        }

        $this->expectException(RoomNotAvailableException::class);
        $service->create(['room_id' => '7', 'guest_id' => '2', 'check_in' => self::day(130), 'check_out' => self::day(131), 'adults' => '1'], self::STAFF);
    }

    public function testGuestCanCancelOnlyUntil24HoursBeforeArrival(): void
    {
        $arrival = self::day(140);
        $id = $this->reservations()->create(['room_id' => '4', 'check_in' => $arrival, 'check_out' => self::day(141), 'adults' => '1'], self::GUEST);

        try {
            $this->reservations($arrival . ' 09:00')->cancel($id, self::GUEST);
            self::fail('Anulimi i mysafirit ditën e mbërritjes duhej refuzuar.');
        } catch (AppException $exception) {
            self::assertSame('CANCELLATION_WINDOW_CLOSED', $exception->getErrorCode());
        }

        // stafi mund të anulojë në çdo kohë para check-in
        $this->reservations($arrival . ' 09:00')->cancel($id, self::STAFF);
        self::assertSame('cancelled', self::value('SELECT status FROM reservations WHERE id = ?', [$id]));
    }

    public function testGuestCannotTouchSomeoneElsesReservation(): void
    {
        $id = $this->reservations()->create(['room_id' => '5', 'guest_id' => '2', 'check_in' => self::day(150), 'check_out' => self::day(151), 'adults' => '1'], self::STAFF);

        foreach (['findVisible', 'cancel'] as $method) {
            try {
                $this->reservations()->{$method}($id, self::GUEST);
                self::fail($method . ' duhej të kthente 403.');
            } catch (AppException $exception) {
                self::assertSame(403, $exception->getStatusCode(), $method);
            }
        }
    }

    public function testFullStayCheckInServicesPaymentsCheckOut(): void
    {
        $arrival = self::day(160);
        $id = $this->reservations()->create(['room_id' => '9', 'guest_id' => '2', 'check_in' => $arrival, 'check_out' => self::day(162), 'adults' => '2'], self::STAFF);

        // KF16: jo para datës së mbërritjes
        try {
            $this->reservations()->checkIn($id, self::STAFF);
            self::fail('Check-in para datës duhej refuzuar.');
        } catch (AppException $exception) {
            self::assertSame('CHECK_IN_NOT_ALLOWED', $exception->getErrorCode());
        }

        $onArrival = $this->reservations($arrival . ' 15:00');
        $onArrival->checkIn($id, self::STAFF);
        self::assertSame('occupied', self::value('SELECT status FROM rooms WHERE id = 9'));

        // RB09: çmimi ruhet në momentin e shtimit
        $onArrival->addService($id, ['service_id' => '1', 'quantity' => '2'], self::STAFF);   // 2 × 6 €
        self::$pdo->exec("UPDATE services SET price = 9.00 WHERE id = 1");
        $onArrival->addService($id, ['service_id' => '1', 'quantity' => '1'], self::STAFF);   // 1 × 9 €
        self::$pdo->exec("UPDATE services SET price = 6.00 WHERE id = 1");

        $invoice = $this->invoice($id);
        self::assertSame('240.00', $invoice['nights_total'], '2 netë × 120 €');
        self::assertSame('21.00', $invoice['services_total']);
        self::assertSame('261.00', $invoice['balance']);

        // RB08: check-out vetëm me bilanc 0
        try {
            $onArrival->checkOut($id, self::STAFF);
            self::fail('Check-out me bilanc duhej refuzuar.');
        } catch (AppException $exception) {
            self::assertSame('OUTSTANDING_BALANCE', $exception->getErrorCode());
        }

        // pagesa më e madhe se bilanci do ta bllokonte check-out-in përgjithmonë
        try {
            $onArrival->addPayment($id, ['amount' => '300', 'method' => 'cash'], self::STAFF);
            self::fail('Pagesa mbi bilancin duhej refuzuar.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('amount', $exception->errors());
        }

        $onArrival->addPayment($id, ['amount' => '100', 'method' => 'cash'], self::STAFF);
        $onArrival->addPayment($id, ['amount' => '161,00', 'method' => 'card'], self::STAFF);
        self::assertSame('0.00', $this->invoice($id)['balance']);

        $onArrival->checkOut($id, self::STAFF);
        self::assertSame('checked_out', self::value('SELECT status FROM reservations WHERE id = ?', [$id]));
        self::assertSame('cleaning', self::value('SELECT status FROM rooms WHERE id = 9'));

        self::$pdo->exec("UPDATE rooms SET status = 'available' WHERE id = 9");
    }

    public function testGuestCannotPerformStaffActions(): void
    {
        $id = $this->reservations()->create(['room_id' => '4', 'check_in' => self::day(170), 'check_out' => self::day(171), 'adults' => '1'], self::GUEST);

        foreach (['confirm', 'checkIn', 'checkOut'] as $method) {
            try {
                $this->reservations()->{$method}($id, self::GUEST);
                self::fail($method . ' duhej të kthente 403.');
            } catch (AppException $exception) {
                self::assertSame(403, $exception->getStatusCode(), $method);
            }
        }
    }

    public function testDatabaseTriggerBlocksOverlapEvenWhenServicesAreBypassed(): void
    {
        $this->reservations()->create(['room_id' => '6', 'guest_id' => '2', 'check_in' => self::day(180), 'check_out' => self::day(182), 'adults' => '1'], self::STAFF);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Dhoma nuk është më e lirë');
        self::$pdo->prepare(
            "INSERT INTO reservations (code, guest_id, room_id, check_in, check_out, adults, status, total_amount) "
            . "VALUES ('DIRECT01', 2, 6, ?, ?, 1, 'confirmed', 95)",
        )->execute([self::day(181), self::day(183)]);
    }
}
