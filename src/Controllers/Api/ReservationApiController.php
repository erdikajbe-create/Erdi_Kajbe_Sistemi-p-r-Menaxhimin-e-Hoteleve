<?php

declare(strict_types=1);

namespace HotelHub\Controllers\Api;

use HotelHub\Core\Auth;
use HotelHub\Core\Csrf;
use HotelHub\Core\Request;
use HotelHub\Core\Response;
use HotelHub\Services\InvoiceService;
use HotelHub\Services\ReservationService;

/**
 * Të njëjtat Services si faqet HTML, të ekspozuara si JSON për Postman.
 */
final readonly class ReservationApiController
{
    public function __construct(
        private ReservationService $reservations,
        private InvoiceService $invoices,
    ) {
    }

    public function index(): void
    {
        $user = Auth::requireLogin();
        $rows = $this->reservations->listFor(Request::query(), $user);
        Response::json(['success' => true, 'data' => array_map(static fn (array $row): array => self::present($row) + [
            'guest_name' => trim($row['first_name'] . ' ' . $row['last_name']),
        ], $rows)]);
    }

    public function store(): void
    {
        $user = Auth::requireRole('guest', 'receptionist', 'admin');
        Csrf::verifyRequest();
        $id = $this->reservations->create(Request::input(), $user);
        header('Location: /api/reservations/' . $id);
        Response::json(['success' => true, 'data' => self::present($this->reservations->findVisible($id, $user))], 201);
    }

    public function show(string $id): void
    {
        $user = Auth::requireLogin();
        $reservation = $this->reservations->findVisible((int) $id, $user);
        Response::json(['success' => true, 'data' => self::present($reservation)]);
    }

    public function confirm(string $id): void
    {
        $this->act($id, fn (array $user) => $this->reservations->confirm((int) $id, $user));
    }

    public function cancel(string $id): void
    {
        $this->act($id, fn (array $user) => $this->reservations->cancel((int) $id, $user));
    }

    public function checkIn(string $id): void
    {
        $this->act($id, fn (array $user) => $this->reservations->checkIn((int) $id, $user));
    }

    public function checkOut(string $id): void
    {
        $this->act($id, fn (array $user) => $this->reservations->checkOut((int) $id, $user));
    }

    public function addService(string $id): void
    {
        $this->act($id, fn (array $user) => $this->reservations->addService((int) $id, Request::input(), $user), 201, true);
    }

    public function addPayment(string $id): void
    {
        $this->act($id, fn (array $user) => $this->reservations->addPayment((int) $id, Request::input(), $user), 201, true);
    }

    public function invoice(string $id): void
    {
        $user = Auth::requireLogin();
        $reservation = $this->reservations->findVisible((int) $id, $user);
        Response::json(['success' => true, 'data' => $this->invoices->forReservation($reservation)]);
    }

    /**
     * @param callable(array): mixed $operation
     */
    private function act(string $id, callable $operation, int $status = 200, bool $withInvoice = false): void
    {
        $user = Auth::requireLogin();
        Csrf::verifyRequest();
        $operation($user);
        $reservation = $this->reservations->findVisible((int) $id, $user);
        $data = self::present($reservation);
        if ($withInvoice) {
            $data['invoice'] = $this->invoices->forReservation($reservation);
        }

        Response::json(['success' => true, 'data' => $data], $status);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'status' => $row['status'],
            'check_in' => $row['check_in'],
            'check_out' => $row['check_out'],
            'adults' => (int) $row['adults'],
            'children' => (int) $row['children'],
            'total_amount' => $row['total_amount'],
            'notes' => $row['notes'],
            'guest' => [
                'id' => (int) $row['guest_id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'email' => $row['guest_email'],
                'phone' => $row['guest_phone'],
            ],
            'room' => [
                'id' => (int) $row['room_id'],
                'number' => $row['room_number'],
                'type' => $row['room_type_name'],
            ],
            'created_at' => $row['created_at'],
        ];
    }
}
