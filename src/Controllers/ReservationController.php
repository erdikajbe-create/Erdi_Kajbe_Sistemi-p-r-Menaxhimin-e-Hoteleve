<?php

declare(strict_types=1);

namespace HotelHub\Controllers;

use HotelHub\Core\Auth;
use HotelHub\Core\Csrf;
use HotelHub\Core\Flash;
use HotelHub\Core\Input;
use HotelHub\Core\Response;
use HotelHub\Core\StayRules;
use HotelHub\Core\View;
use HotelHub\Enums\ReservationStatus;
use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\RoomNotAvailableException;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\GuestRepository;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\ServiceRepository;
use HotelHub\Services\InvoiceService;
use HotelHub\Services\ReservationService;

/**
 * Controller-i nuk ka rregulla biznesi: merr input-in, thërret ReservationService
 * dhe vendos ku shkon përdoruesi (Post/Redirect/Get me 303).
 */
final readonly class ReservationController
{
    public function __construct(
        private ReservationService $reservations,
        private InvoiceService $invoices,
        private RoomRepository $rooms,
        private GuestRepository $guests,
        private ServiceRepository $services,
    ) {
    }

    public function index(): void
    {
        $user = Auth::requireLogin();
        $filters = Input::safeValues($_GET);

        Response::html(View::render('reservations/index', [
            'title' => Auth::isStaff() ? 'Rezervimet' : 'Rezervimet e mia',
            'reservations' => $this->reservations->listFor($filters, $user),
            'filters' => $filters,
            'isStaff' => Auth::isStaff(),
            'statuses' => ReservationStatus::cases(),
            'scripts' => ['/js/reservations-search.js'],
        ]));
    }

    public function create(): void
    {
        Auth::requireRole('guest', 'receptionist', 'admin');
        $this->renderCreate(Input::safeValues($_GET), [], null, 200);
    }

    public function store(): void
    {
        $user = Auth::requireRole('guest', 'receptionist', 'admin');
        Csrf::verifyRequest();

        try {
            $id = $this->reservations->create($_POST, $user);
        } catch (ValidationException $exception) {
            $this->renderCreate(Input::safeValues($_POST), $exception->errors(), null, 422);
        } catch (RoomNotAvailableException $exception) {
            $this->renderCreate(Input::safeValues($_POST), [], $exception->getMessage(), 409);
        }

        Flash::add('success', Auth::isStaff()
            ? 'Rezervimi u krijua dhe u konfirmua.'
            : 'Rezervimi u krijua. Recepsioni do ta konfirmojë së shpejti.');
        Response::redirect('/reservations/' . $id);
    }

    public function show(string $id): void
    {
        $this->renderShow((int) $id, [], [], 200);
    }

    public function confirm(string $id): void
    {
        $this->action($id, fn (array $user) => $this->reservations->confirm((int) $id, $user), 'Rezervimi u konfirmua.');
    }

    public function cancel(string $id): void
    {
        $this->action($id, fn (array $user) => $this->reservations->cancel((int) $id, $user), 'Rezervimi u anulua.');
    }

    public function checkIn(string $id): void
    {
        $this->action($id, fn (array $user) => $this->reservations->checkIn((int) $id, $user), 'Check-in u krye. Dhoma është e zënë.');
    }

    public function checkOut(string $id): void
    {
        $this->action($id, fn (array $user) => $this->reservations->checkOut((int) $id, $user), 'Check-out u krye. Dhoma kaloi në pastrim.');
    }

    public function addService(string $id): void
    {
        $this->action($id, fn (array $user) => $this->reservations->addService((int) $id, $_POST, $user), 'Shërbimi u shtua në faturë.');
    }

    public function addPayment(string $id): void
    {
        $this->action($id, fn (array $user) => $this->reservations->addPayment((int) $id, $_POST, $user), 'Pagesa u regjistrua.');
    }

    /**
     * Veprimet mbi një rezervim: gabimet e validimit dhe të rregullave shfaqen në të njëjtën faqe.
     *
     * @param callable(array): mixed $operation
     */
    private function action(string $id, callable $operation, string $successMessage): void
    {
        $user = Auth::requireLogin();
        Csrf::verifyRequest();

        try {
            $operation($user);
        } catch (ValidationException $exception) {
            $this->renderShow((int) $id, $exception->errors(), Input::safeValues($_POST), 422);
        } catch (AppException $exception) {
            if (in_array($exception->getStatusCode(), [403, 404], true)) {
                throw $exception;
            }
            $this->renderShow((int) $id, ['_action' => $exception->getMessage()], Input::safeValues($_POST), $exception->getStatusCode());
        }

        Flash::add('success', $successMessage);
        Response::redirect('/reservations/' . (int) $id);
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    private function renderShow(int $id, array $errors, array $old, int $status): never
    {
        $user = Auth::requireLogin();
        $reservation = $this->reservations->findVisible($id, $user);
        $isStaff = Auth::isStaff();

        Response::html(View::render('reservations/show', [
            'title' => 'Rezervimi ' . $reservation['code'],
            'reservation' => $reservation,
            'invoice' => $this->invoices->forReservation($reservation),
            'status' => ReservationStatus::from($reservation['status']),
            'isStaff' => $isStaff,
            'guestCanCancel' => StayRules::guestCanCancel($reservation['check_in'], new \DateTimeImmutable()),
            'canCheckInToday' => StayRules::canCheckInOn($reservation['check_in'], $reservation['check_out'], date('Y-m-d')),
            'services' => $isStaff ? $this->services->all(true) : [],
            'errors' => $errors,
            'old' => $old,
        ]), $status);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function renderCreate(array $values, array $errors, ?string $conflict, int $status): never
    {
        $isStaff = Auth::isStaff();
        $roomId = (int) ($values['room_id'] ?? 0);
        $room = null;
        foreach ($this->rooms->all() as $candidate) {
            if ((int) $candidate['id'] === $roomId) {
                $room = $candidate;
                break;
            }
        }

        if (!isset($values['adults']) && isset($values['guests'])) {
            $values['adults'] = $values['guests'];
        }

        Response::html(View::render('reservations/create', [
            'title' => 'Rezervim i ri',
            'room' => $room,
            'rooms' => $isStaff ? $this->rooms->all() : [],
            'guests' => $isStaff ? $this->guests->all() : [],
            'isStaff' => $isStaff,
            'old' => $values,
            'errors' => $errors,
            'conflict' => $conflict,
            'today' => date('Y-m-d'),
        ]), $status);
    }
}
