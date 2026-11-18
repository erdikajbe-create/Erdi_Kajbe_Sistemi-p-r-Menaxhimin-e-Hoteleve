<?php

declare(strict_types=1);

namespace HotelHub\Repositories;

use PDO;

/**
 * Query-t e dashboard-it të menaxherit (KF21, UC8).
 */
final readonly class DashboardRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string, int|string>
     */
    public function stats(string $today, string $monthStart, string $nextMonthStart): array
    {
        $statement = $this->pdo->prepare(
            'SELECT '
            . '(SELECT COUNT(*) FROM rooms) AS total_rooms, '
            . "(SELECT COUNT(*) FROM rooms WHERE status = 'maintenance') AS maintenance_rooms, "
            . "(SELECT COUNT(DISTINCT room_id) FROM reservations WHERE status = 'checked_in') AS occupied_rooms, "
            . "(SELECT COUNT(*) FROM reservations WHERE check_in = :today_a AND status IN ('pending','confirmed')) "
            . 'AS arrivals_today, '
            . "(SELECT COUNT(*) FROM reservations WHERE check_out = :today_d AND status = 'checked_in') "
            . 'AS departures_today, '
            . "(SELECT COUNT(*) FROM reservations WHERE check_out < :today_o AND status = 'checked_in') "
            . 'AS overdue_departures, '
            . "(SELECT COUNT(*) FROM reservations WHERE status = 'pending') AS pending_reservations, "
            . '(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE paid_at >= :month_start AND paid_at < :next_month) '
            . 'AS month_revenue',
        );
        $statement->execute([
            'today_a' => $today,
            'today_d' => $today,
            'today_o' => $today,
            'month_start' => $monthStart . ' 00:00:00',
            'next_month' => $nextMonthStart . ' 00:00:00',
        ]);
        $row = $statement->fetch();

        return [
            'total_rooms' => (int) $row['total_rooms'],
            'maintenance_rooms' => (int) $row['maintenance_rooms'],
            'occupied_rooms' => (int) $row['occupied_rooms'],
            'arrivals_today' => (int) $row['arrivals_today'],
            'departures_today' => (int) $row['departures_today'],
            'overdue_departures' => (int) $row['overdue_departures'],
            'pending_reservations' => (int) $row['pending_reservations'],
            'month_revenue' => (string) $row['month_revenue'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function arrivals(string $today): array
    {
        return $this->stays(
            "res.check_in = :day AND res.status IN ('pending','confirmed')",
            $today,
        );
    }

    /**
     * Largimet e sotme dhe ato që e kanë kaluar datën e largimit pa check-out.
     *
     * @return list<array<string, mixed>>
     */
    public function departures(string $today): array
    {
        return $this->stays("res.check_out <= :day AND res.status = 'checked_in'", $today);
    }

    /**
     * @return list<array{month: string, revenue: string}>
     */
    public function revenueByMonth(string $fromMonthStart): array
    {
        $statement = $this->pdo->prepare(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS month, SUM(amount) AS revenue FROM payments "
            . 'WHERE paid_at >= :from GROUP BY month ORDER BY month',
        );
        $statement->execute(['from' => $fromMonthStart . ' 00:00:00']);

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function stays(string $condition, string $day): array
    {
        $statement = $this->pdo->prepare(
            'SELECT res.id, res.code, res.status, res.check_in, res.check_out, res.adults, res.children, '
            . 'g.first_name, g.last_name, r.number AS room_number '
            . 'FROM reservations res JOIN guests g ON g.id = res.guest_id JOIN rooms r ON r.id = res.room_id '
            . 'WHERE ' . $condition . ' ORDER BY r.number',
        );
        $statement->execute(['day' => $day]);

        return $statement->fetchAll();
    }
}
