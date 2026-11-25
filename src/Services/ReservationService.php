<?php

declare(strict_types=1);

namespace HotelHub\Services;

use Closure;
use DateTimeImmutable;
use HotelHub\Core\Input;
use HotelHub\Core\Money;
use HotelHub\Core\StayRules;
use HotelHub\Enums\PaymentMethod;
use HotelHub\Enums\ReservationStatus;
use HotelHub\Enums\RoomStatus;
use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\InvalidStatusTransitionException;
use HotelHub\Exceptions\NotFoundException;
use HotelHub\Exceptions\RoomNotAvailableException;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\GuestRepository;
use HotelHub\Repositories\ReservationRepository;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\ServiceRepository;
use PDO;
use PDOException;
use Throwable;

/**
 * Rregullat e biznesit për rezervimet (RB01–RB09). Çdo veprim që lexon dhe pastaj shkruan
 * ndodh brenda një transaksioni me rreshtat përkatës të bllokuar (SELECT ... FOR UPDATE).
 *
 * @phpstan-type User array{id: int, name: string, email: string, role: string}
 */
final readonly class ReservationService
{
    /** Fushat që merren nga klienti; statusi, kodi, created_by dhe totali caktohen nga serveri. */
    public const ALLOWED_FIELDS = [
        'room_id', 'guest_id', 'check_in', 'check_out', 'adults', 'children', 'notes',
        'first_name', 'last_name', 'email', 'phone', 'document_number',
    ];

    private Closure $clock;

    public function __construct(
        private PDO $pdo,
        private ReservationRepository $reservations,
        private RoomRepository $rooms,
        private GuestRepository $guests,
        private ServiceRepository $services,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn (): DateTimeImmutable => new DateTimeImmutable();
    }

    public function today(): string
    {
        return ($this->clock)()->format('Y-m-d');
    }

    // ------------------------------------------------------------------ krijimi

    /**
     * KF11, KF12, UC1, UC2.
     *
     * @param array<string, mixed> $input
     * @param User $user
     */
    public function create(array $input, array $user): int
    {
        $input = array_intersect_key($input, array_flip(self::ALLOWED_FIELDS));
        $isStaff = self::isStaff($user);
        $data = $this->validateCreate($input, $isStaff);

        return $this->transaction(function () use ($data, $user, $isStaff): int {
            $room = $this->rooms->lockWithType($data['room_id']);
            if ($room === null) {
                throw new ValidationException(['room_id' => 'Dhoma e zgjedhur nuk ekziston.']);
            }
            if ($room['status'] === RoomStatus::Maintenance->value) {
                throw new RoomNotAvailableException(sprintf('Dhoma %s është në mirëmbajtje.', $room['number']));
            }
            if ($data['adults'] + $data['children'] > (int) $room['capacity']) {
                throw new ValidationException([
                    'adults' => sprintf('Dhoma %s pranon deri në %d persona.', $room['number'], $room['capacity']),
                ]);
            }
            if ($this->reservations->countOverlaps($data['room_id'], $data['check_in'], $data['check_out']) > 0) {
                throw new RoomNotAvailableException(
                    sprintf('Dhoma %s është e zënë për datat e zgjedhura.', $room['number']),
                );
            }

            $guestId = $isStaff ? $this->resolveStaffGuest($data) : $this->resolveOwnGuest($user);
            $nights = StayRules::nights($data['check_in'], $data['check_out']);

            return $this->insertWithUniqueCode([
                'guest_id' => $guestId,
                'room_id' => $data['room_id'],
                'created_by' => (int) $user['id'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adults' => $data['adults'],
                'children' => $data['children'],
                // rezervimet nga recepsioni krijohen direkt të konfirmuara (11.1, hapi 3)
                'status' => ($isStaff ? ReservationStatus::Confirmed : ReservationStatus::Pending)->value,
                'total_amount' => Money::mul((string) $room['base_price'], $nights),     // RB05
                'notes' => $data['notes'],
            ]);
        });
    }

    // ------------------------------------------------------------------ leximi

    /**
     * KF15: mysafiri sheh vetëm rezervimet e veta; stafi i sheh të gjitha.
     *
     * @param User $user
     */
    public function findVisible(int $id, array $user): array
    {
        $reservation = $this->reservations->find($id);
        if ($reservation === null) {
            throw new NotFoundException('Rezervimi nuk u gjet.', 'RESERVATION_NOT_FOUND');
        }
        if (!self::isStaff($user) && (int) $reservation['guest_user_id'] !== (int) $user['id']) {
            throw new AppException('Nuk keni qasje në këtë rezervim.', 403, null, 'FORBIDDEN');
        }

        return $reservation;
    }

    /**
     * @param array<string, mixed> $filters
     * @param User $user
     * @return list<array<string, mixed>>
     */
    public function listFor(array $filters, array $user): array
    {
        $clean = [
            'status' => ReservationStatus::tryFrom(Input::string($filters, 'status'))?->value ?? '',
            'date' => StayRules::isValidDate(Input::string($filters, 'date')) ? Input::string($filters, 'date') : '',
            'q' => mb_substr(trim(Input::string($filters, 'q')), 0, 60),
        ];

        return $this->reservations->search($clean, self::isStaff($user) ? null : (int) $user['id']);
    }

    // ------------------------------------------------------------------ statuset

    /**
     * KF13: recepsionisti konfirmon një rezervim pending.
     *
     * @param User $user
     */
    public function confirm(int $id, array $user): void
    {
        $this->requireStaff($user);
        $this->transaction(function () use ($id): void {
            $reservation = $this->lockReservation($id);
            $this->transition($reservation, ReservationStatus::Confirmed);
        });
    }

    /**
     * KF14, RB07: mysafiri anulon deri 24 orë para mbërritjes; stafi në çdo kohë para check-in.
     *
     * @param User $user
     */
    public function cancel(int $id, array $user): void
    {
        $this->transaction(function () use ($id, $user): void {
            $reservation = $this->lockReservation($id);

            if (!self::isStaff($user)) {
                if ((int) $reservation['guest_user_id'] !== (int) $user['id']) {
                    throw new AppException('Nuk keni qasje në këtë rezervim.', 403, null, 'FORBIDDEN');
                }
                if (!StayRules::guestCanCancel($reservation['check_in'], ($this->clock)())) {
                    throw new AppException(
                        'Anulimi online lejohet deri 24 orë para mbërritjes. Kontaktoni recepsionin.',
                        422,
                        null,
                        'CANCELLATION_WINDOW_CLOSED',
                    );
                }
            }

            $this->transition($reservation, ReservationStatus::Cancelled);
        });
    }

    /**
     * KF16, UC3: vetëm për rezervim confirmed, nga dita e mbërritjes; dhoma → occupied.
     *
     * @param User $user
     */
    public function checkIn(int $id, array $user): void
    {
        $this->requireStaff($user);
        $this->transaction(function () use ($id): void {
            $reservation = $this->lockReservation($id);
            $current = ReservationStatus::from($reservation['status']);
            if (!$current->canTransitionTo(ReservationStatus::CheckedIn)) {
                throw new InvalidStatusTransitionException($current, ReservationStatus::CheckedIn);
            }

            $today = $this->today();
            if (!StayRules::canCheckInOn($reservation['check_in'], $reservation['check_out'], $today)) {
                throw new AppException(
                    $today < $reservation['check_in']
                        ? sprintf('Check-in lejohet nga data e mbërritjes (%s).', $reservation['check_in'])
                        : 'Data e largimit ka kaluar; rezervimi nuk mund të hapet më.',
                    422,
                    null,
                    'CHECK_IN_NOT_ALLOWED',
                );
            }

            $room = $this->rooms->lockWithType((int) $reservation['room_id']);
            if ($room === null || $room['status'] !== RoomStatus::Available->value) {
                throw new AppException(
                    sprintf(
                        'Dhoma %s nuk është gati (statusi: %s). Shënojeni si të lirë pas pastrimit.',
                        $room['number'] ?? '',
                        $room['status'] ?? '?',
                    ),
                    409,
                    null,
                    'ROOM_NOT_READY',
                );
            }

            $this->transition($reservation, ReservationStatus::CheckedIn);
            $this->rooms->updateStatus((int) $reservation['room_id'], RoomStatus::Occupied->value);
        });
    }

    /**
     * KF20, RB08, UC5: vetëm me bilanc 0; dhoma → cleaning.
     *
     * @param User $user
     */
    public function checkOut(int $id, array $user): void
    {
        $this->requireStaff($user);
        $this->transaction(function () use ($id): void {
            $reservation = $this->lockReservation($id);
            $current = ReservationStatus::from($reservation['status']);
            if (!$current->canTransitionTo(ReservationStatus::CheckedOut)) {
                throw new InvalidStatusTransitionException($current, ReservationStatus::CheckedOut);
            }

            $balance = $this->balance($reservation);
            if (Money::compare($balance, '0') !== 0) {
                throw new AppException(
                    sprintf('Check-out nuk lejohet: mbeten %s për t\'u paguar.', Money::format($balance)),
                    422,
                    null,
                    'OUTSTANDING_BALANCE',
                );
            }

            $this->rooms->lockWithType((int) $reservation['room_id']);
            $this->transition($reservation, ReservationStatus::CheckedOut);
            $this->rooms->updateStatus((int) $reservation['room_id'], RoomStatus::Cleaning->value);
        });
    }

    // ------------------------------------------------------------------ shërbimet dhe pagesat

    /**
     * KF17, RB09, UC4: çmimi i shërbimit ruhet në momentin e shtimit.
     *
     * @param array<string, mixed> $input service_id, quantity
     * @param User $user
     */
    public function addService(int $id, array $input, array $user): void
    {
        $this->requireStaff($user);
        $serviceId = Input::string($input, 'service_id');
        $quantity = Input::string($input, 'quantity') === '' ? '1' : Input::string($input, 'quantity');
        $errors = [];
        if (filter_var($serviceId, FILTER_VALIDATE_INT) === false || (int) $serviceId < 1) {
            $errors['service_id'] = 'Zgjidhni shërbimin.';
        }
        if (filter_var($quantity, FILTER_VALIDATE_INT) === false || (int) $quantity < 1 || (int) $quantity > 99) {
            $errors['quantity'] = 'Sasia duhet të jetë 1–99.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->transaction(function () use ($id, $serviceId, $quantity, $user): void {
            $reservation = $this->lockReservation($id);
            $status = ReservationStatus::from($reservation['status']);
            if (!in_array($status, [ReservationStatus::Confirmed, ReservationStatus::CheckedIn], true)) {
                throw new AppException(
                    'Shërbimet shtohen vetëm në rezervime të konfirmuara ose me mysafir brenda.',
                    422,
                    null,
                    'RESERVATION_NOT_ACTIVE',
                );
            }

            $service = $this->services->find((int) $serviceId);
            if ($service === null || !(bool) $service['active']) {
                throw new ValidationException(['service_id' => 'Shërbimi nuk ekziston ose nuk është aktiv.']);
            }

            $this->reservations->addService(
                $id,
                (int) $service['id'],
                (int) $quantity,
                Money::normalize((string) $service['price']),
                (int) $user['id'],
            );
        });
    }

    /**
     * KF18: pagesë e pjesshme ose e plotë; shuma > 0 dhe jo më e madhe se bilanci.
     *
     * @param array<string, mixed> $input amount, method, reference
     * @param User $user
     */
    public function addPayment(int $id, array $input, array $user): int
    {
        $this->requireStaff($user);
        $amount = str_replace(',', '.', trim(Input::string($input, 'amount')));
        $method = PaymentMethod::tryFrom(Input::string($input, 'method'));
        $reference = trim(Input::string($input, 'reference'));
        $errors = [];
        if (!Money::isValid($amount) || Money::compare($amount, '0') <= 0) {
            $errors['amount'] = 'Shkruani një shumë pozitive me deri në dy shifra dhjetore.';
        }
        if ($method === null) {
            $errors['method'] = 'Zgjidhni metodën e pagesës.';
        }
        if (mb_strlen($reference) > 100) {
            $errors['reference'] = 'Referenca nuk mund të kalojë 100 karaktere.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($id, $amount, $method, $reference, $user): int {
            $reservation = $this->lockReservation($id);
            if (!ReservationStatus::from($reservation['status'])->isOpen()) {
                throw new AppException(
                    'Pagesat regjistrohen vetëm për rezervime aktive.',
                    422,
                    null,
                    'RESERVATION_NOT_ACTIVE',
                );
            }

            // Pa këtë kontroll, një pagesë më e madhe se bilanci e bën check-out-in të pamundur
            // (RB08 kërkon bilanc saktësisht 0 dhe rimbursimet janë jashtë scope-it).
            $balance = $this->balance($reservation);
            if (Money::compare(Money::normalize($amount), $balance) > 0) {
                throw new ValidationException([
                    'amount' => sprintf('Shuma kalon bilancin e mbetur (%s).', Money::format($balance)),
                ]);
            }

            return $this->reservations->addPayment(
                $id,
                Money::normalize($amount),
                $method->value,
                $reference === '' ? null : $reference,
                (int) $user['id'],
            );
        });
    }

    // ------------------------------------------------------------------ ndihmëse

    /**
     * @param array<string, mixed> $input
     * @return array{room_id: int, guest_id: ?int, check_in: string, check_out: string, adults: int,
     *     children: int, notes: ?string, new_guest: ?array<string, ?string>}
     */
    private function validateCreate(array $input, bool $isStaff): array
    {
        $errors = [];
        $today = $this->today();

        try {
            $criteria = AvailabilityService::validateCriteria([
                'check_in' => $input['check_in'] ?? '',
                'check_out' => $input['check_out'] ?? '',
                'guests' => '1',
            ], $today);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $criteria = null;
        }

        $roomId = Input::string($input, 'room_id');
        if (filter_var($roomId, FILTER_VALIDATE_INT) === false || (int) $roomId < 1) {
            $errors['room_id'] = 'Zgjidhni dhomën.';
        }

        $adults = trim(Input::string($input, 'adults'));
        $children = trim(Input::string($input, 'children')) === '' ? '0' : trim(Input::string($input, 'children'));
        if (filter_var($adults, FILTER_VALIDATE_INT) === false || (int) $adults < 1 || (int) $adults > 6) {
            $errors['adults'] = 'Numri i të rriturve duhet të jetë 1–6.';
        }
        if (filter_var($children, FILTER_VALIDATE_INT) === false || (int) $children < 0 || (int) $children > 5) {
            $errors['children'] = 'Numri i fëmijëve duhet të jetë 0–5.';
        }

        $notes = trim(Input::string($input, 'notes'));
        if (mb_strlen($notes) > 1000) {
            $errors['notes'] = 'Shënimet nuk mund të kalojnë 1000 karaktere.';
        }

        $guestId = null;
        $newGuest = null;
        if ($isStaff) {
            $rawGuestId = trim(Input::string($input, 'guest_id'));
            if ($rawGuestId !== '' && $rawGuestId !== 'new') {
                if (filter_var($rawGuestId, FILTER_VALIDATE_INT) === false || (int) $rawGuestId < 1) {
                    $errors['guest_id'] = 'Zgjidhni mysafirin.';
                } else {
                    $guestId = (int) $rawGuestId;
                }
            } else {
                $newGuest = $this->validateNewGuest($input, $errors);
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'room_id' => (int) $roomId,
            'guest_id' => $guestId,
            'check_in' => $criteria['check_in'],
            'check_out' => $criteria['check_out'],
            'adults' => (int) $adults,
            'children' => (int) $children,
            'notes' => $notes === '' ? null : $notes,
            'new_guest' => $newGuest,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     * @return array<string, ?string>
     */
    private function validateNewGuest(array $input, array &$errors): array
    {
        $guest = [];
        foreach (['first_name' => 80, 'last_name' => 80, 'email' => 150, 'phone' => 30, 'document_number' => 50] as $field => $max) {
            $value = trim(Input::string($input, $field));
            $guest[$field] = $value === '' ? null : $value;
            if (mb_strlen($value) > $max) {
                $errors[$field] = sprintf('Nuk mund të kalojë %d karaktere.', $max);
            }
        }

        if ($guest['first_name'] === null) {
            $errors['first_name'] = 'Shkruani emrin e mysafirit (ose zgjidhni një mysafir ekzistues).';
        }
        if ($guest['last_name'] === null) {
            $errors['last_name'] = 'Shkruani mbiemrin e mysafirit.';
        }
        if ($guest['phone'] === null && $guest['email'] === null) {
            $errors['phone'] = 'Shkruani të paktën telefonin ose email-in për kontakt.';
        }
        if ($guest['email'] !== null && filter_var($guest['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Shkruani një adresë email të vlefshme.';
        }

        return $guest;
    }

    /**
     * @param array{guest_id: ?int, new_guest: ?array<string, ?string>} $data
     */
    private function resolveStaffGuest(array $data): int
    {
        if ($data['guest_id'] !== null) {
            if ($this->guests->find($data['guest_id']) === null) {
                throw new ValidationException(['guest_id' => 'Mysafiri i zgjedhur nuk ekziston.']);
            }

            return $data['guest_id'];
        }

        $guest = $data['new_guest'];

        return $this->guests->createWalkIn(
            (string) $guest['first_name'],
            (string) $guest['last_name'],
            $guest['email'] !== null ? strtolower($guest['email']) : null,
            $guest['phone'],
            $guest['document_number'],
        );
    }

    /**
     * Mysafiri rezervon gjithmonë në emër të vet; guest_id nga klienti injorohet.
     *
     * @param User $user
     */
    private function resolveOwnGuest(array $user): int
    {
        $guest = $this->guests->findByUserId((int) $user['id']);
        if ($guest !== null) {
            return (int) $guest['id'];
        }

        $parts = preg_split('/\s+/', trim($user['name']), 2) ?: [$user['name']];

        return $this->guests->createForUser((int) $user['id'], $parts[0], $parts[1] ?? '-', $user['email']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insertWithUniqueCode(array $data): int
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->reservations->insert(['code' => self::generateCode()] + $data);
            } catch (PDOException $exception) {
                $duplicateCode = ($exception->errorInfo[1] ?? null) === 1062
                    && str_contains($exception->getMessage(), 'uq_reservations_code');
                if (!$duplicateCode || $attempt >= 3) {
                    throw $exception;
                }
            }
        }
    }

    public static function generateCode(): string
    {
        // pa 0/O dhe 1/I që kodi të lexohet lehtë në telefon
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    private function lockReservation(int $id): array
    {
        $reservation = $this->reservations->lock($id);
        if ($reservation === null) {
            throw new NotFoundException('Rezervimi nuk u gjet.', 'RESERVATION_NOT_FOUND');
        }

        return $reservation;
    }

    private function transition(array $reservation, ReservationStatus $target): void
    {
        $current = ReservationStatus::from($reservation['status']);
        if (!$current->canTransitionTo($target)) {
            throw new InvalidStatusTransitionException($current, $target);
        }

        $this->reservations->updateStatus((int) $reservation['id'], $target->value);
    }

    /**
     * KF19: mbetur = (netë + shërbime) − pagesa.
     */
    private function balance(array $reservation): string
    {
        $totals = $this->reservations->totals((int) $reservation['id']);

        return Money::sub(
            Money::add(Money::normalize((string) $reservation['total_amount']), Money::normalize($totals['services_total'])),
            Money::normalize($totals['paid_total']),
        );
    }

    /**
     * @param User $user
     */
    private function requireStaff(array $user): void
    {
        if (!self::isStaff($user)) {
            throw new AppException('Ky veprim lejohet vetëm për stafin.', 403, null, 'FORBIDDEN');
        }
    }

    /**
     * @param User $user
     */
    private static function isStaff(array $user): bool
    {
        return in_array($user['role'], ['admin', 'receptionist'], true);
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();

            return $result;
        } catch (PDOException $exception) {
            $this->rollBack();
            throw self::translateTriggerError($exception);
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    /**
     * Trigger-at në databazë (012) janë rrjeti i fundit i sigurisë; gabimet e tyre
     * (SQLSTATE 45000) kthehen në të njëjtat përgjigje si kontrollet në PHP.
     */
    private static function translateTriggerError(PDOException $exception): Throwable
    {
        if (($exception->errorInfo[0] ?? $exception->getCode()) !== '45000') {
            return $exception;
        }

        $message = $exception->errorInfo[2] ?? $exception->getMessage();

        return match (true) {
            str_contains($message, 'nuk është më e lirë') => new RoomNotAvailableException(previous: $exception),
            str_contains($message, 'bilanci') => new AppException($message, 422, $exception, 'OUTSTANDING_BALANCE'),
            str_contains($message, 'statusit') => new AppException($message, 422, $exception, 'INVALID_STATUS_TRANSITION'),
            default => new AppException($message, 422, $exception),
        };
    }

    private function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
