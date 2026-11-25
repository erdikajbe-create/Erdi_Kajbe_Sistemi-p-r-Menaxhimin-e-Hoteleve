<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

final readonly class ReservationRepository
{
    private const SELECT_DETAILS = 'SELECT res.id, res.code, res.guest_id, res.room_id, res.created_by, '
        . 'res.check_in, res.check_out, res.adults, res.children, res.status, res.total_amount, res.notes, '
        . 'res.created_at, g.first_name, g.last_name, g.email AS guest_email, g.phone AS guest_phone, '
        . 'g.document_number, g.user_id AS guest_user_id, r.number AS room_number, r.status AS room_status, '
        . 'rt.name AS room_type_name, rt.base_price, u.name AS created_by_name '
        . 'FROM reservations res '
        . 'JOIN guests g ON g.id = res.guest_id '
        . 'JOIN rooms r ON r.id = res.room_id '
        . 'JOIN room_types rt ON rt.id = r.room_type_id '
        . 'LEFT JOIN users u ON u.id = res.created_by ';

    public function __construct(private PDO $pdo)
    {
    }

    public function countOverlaps(int $roomId, string $checkIn, string $checkOut, ?int $exceptId = null): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservations WHERE room_id = :room_id AND status <> 'cancelled' "
            . 'AND check_in < :check_out AND check_out > :check_in AND id <> :except_id',
        );
        $statement->execute([
            'room_id' => $roomId,
            'check_out' => $checkOut,
            'check_in' => $checkIn,
            'except_id' => $exceptId ?? 0,
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array{code: string, guest_id: int, room_id: int, created_by: ?int, check_in: string,
     *     check_out: string, adults: int, children: int, status: string, total_amount: string, notes: ?string} $data
     */
    public function insert(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, '
            . 'children, status, total_amount, notes) VALUES (:code, :guest_id, :room_id, :created_by, '
            . ':check_in, :check_out, :adults, :children, :status, :total_amount, :notes)',
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(self::SELECT_DETAILS . 'WHERE res.id = :id');
        $statement->execute(['id' => $id]);
        $reservation = $statement->fetch();

        return $reservation === false ? null : $reservation;
    }

    /**
     * Bllokon rreshtin e rezervimit që dy veprime paralele (p.sh. check-out dhe pagesë)
     * të mos lexojnë të njëjtin status/bilanc.
     */
    public function lock(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT res.id, res.room_id, res.status, res.check_in, res.check_out, res.total_amount, '
            . 'g.user_id AS guest_user_id FROM reservations res JOIN guests g ON g.id = res.guest_id '
            . 'WHERE res.id = :id FOR UPDATE',
        );
        $statement->execute(['id' => $id]);
        $reservation = $statement->fetch();

        return $reservation === false ? null : $reservation;
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE reservations SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * @param array{status?: string, date?: string, q?: string} $filters
     * @return list<array<string, mixed>>
     */
    public function search(array $filters, ?int $guestUserId = null, int $limit = 200): array
    {
        $where = [];
        $params = [];

        if ($guestUserId !== null) {
            $where[] = 'g.user_id = :guest_user_id';
            $params['guest_user_id'] = $guestUserId;
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'res.status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['date'] ?? '') !== '') {
            // qëndrimet që përfshijnë datën: mbërrin atë ditë, është brenda ose largohet atë ditë
            $where[] = 'res.check_in <= :date_in AND res.check_out >= :date_out';
            $params['date_in'] = $filters['date'];
            $params['date_out'] = $filters['date'];
        }
        if (($filters['q'] ?? '') !== '') {
            $term = trim((string) $filters['q']);
            $escaped = str_replace(['\\\\', '%', '_'], ['\\\\\\\\', '\\\\%', '\\\\_'], $term);
            $where[] = "(res.code LIKE :q_code ESCAPE '\\\\' OR CONCAT(g.first_name, ' ', g.last_name) LIKE :q_name ESCAPE '\\\\' OR CAST(res.id AS CHAR) = :q_id OR r.number LIKE :q_room ESCAPE '\\\\')";
            $params['q_code'] = '%' . $escaped . '%';
            $params['q_name'] = '%' . $escaped . '%';
            $params['q_id'] = ctype_digit($term) ? (int) $term : -1;
            $params['q_room'] = '%' . $escaped . '%';
        }

        $sql = self::SELECT_DETAILS
            . ($where === [] ? '' : 'WHERE ' . implode(' AND ', $where) . ' ')
            . 'ORDER BY res.check_in DESC, res.id DESC LIMIT ' . max(1, min($limit, 500));
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    // ---- shërbimet (rreshtat e faturës) ----

    public function addService(int $reservationId, int $serviceId, int $quantity, string $unitPrice, int $userId): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO reservation_service (reservation_id, service_id, quantity, unit_price, added_by) '
            . 'VALUES (:reservation_id, :service_id, :quantity, :unit_price, :added_by)',
        );
        $statement->execute([
            'reservation_id' => $reservationId,
            'service_id' => $serviceId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'added_by' => $userId,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function services(int $reservationId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT rs.id, s.name, rs.quantity, rs.unit_price, rs.quantity * rs.unit_price AS line_total, '
            . 'rs.added_at FROM reservation_service rs JOIN services s ON s.id = rs.service_id '
            . 'WHERE rs.reservation_id = :id ORDER BY rs.added_at, rs.id',
        );
        $statement->execute(['id' => $reservationId]);

        return $statement->fetchAll();
    }

    // ---- pagesat ----

    public function addPayment(int $reservationId, string $amount, string $method, ?string $reference, int $userId): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO payments (reservation_id, amount, method, reference, received_by) '
            . 'VALUES (:reservation_id, :amount, :method, :reference, :received_by)',
        );
        $statement->execute([
            'reservation_id' => $reservationId,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference,
            'received_by' => $userId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function payments(int $reservationId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.amount, p.method, p.reference, p.paid_at, u.name AS received_by_name '
            . 'FROM payments p LEFT JOIN users u ON u.id = p.received_by '
            . 'WHERE p.reservation_id = :id ORDER BY p.paid_at, p.id',
        );
        $statement->execute(['id' => $reservationId]);

        return $statement->fetchAll();
    }

    /**
     * Shumat llogariten në MySQL me DECIMAL, pa float në PHP.
     *
     * @return array{services_total: string, paid_total: string}
     */
    public function totals(int $reservationId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT '
            . '(SELECT COALESCE(SUM(quantity * unit_price), 0) FROM reservation_service WHERE reservation_id = :id_s) '
            . 'AS services_total, '
            . '(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE reservation_id = :id_p) AS paid_total',
        );
        $statement->execute(['id_s' => $reservationId, 'id_p' => $reservationId]);
        $row = $statement->fetch();

        return [
            'services_total' => (string) $row['services_total'],
            'paid_total' => (string) $row['paid_total'],
        ];
    }
}
