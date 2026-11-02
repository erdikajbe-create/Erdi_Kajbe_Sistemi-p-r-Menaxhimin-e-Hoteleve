-- 013 · Views: fatura dhe dashboard (KF19, KF21)

CREATE OR REPLACE VIEW v_invoice AS
SELECT
    r.id                                   AS reservation_id,
    r.code,
    r.guest_id,
    r.status,
    r.total_amount                         AS nights_total,
    COALESCE(s.services_total, 0)          AS services_total,
    COALESCE(p.paid_total, 0)              AS paid_total,
    r.total_amount + COALESCE(s.services_total, 0) - COALESCE(p.paid_total, 0) AS balance
FROM reservations r
LEFT JOIN (SELECT reservation_id, SUM(quantity * unit_price) AS services_total
           FROM reservation_service GROUP BY reservation_id) s ON s.reservation_id = r.id
LEFT JOIN (SELECT reservation_id, SUM(amount) AS paid_total
           FROM payments GROUP BY reservation_id) p ON p.reservation_id = r.id;

CREATE OR REPLACE VIEW v_dashboard AS
SELECT
    (SELECT COUNT(*) FROM reservations
      WHERE status = 'checked_in')                                   AS occupied_rooms,
    (SELECT COUNT(*) FROM rooms)                                     AS total_rooms,
    (SELECT COUNT(*) FROM reservations
      WHERE check_in  = CURDATE() AND status IN ('pending','confirmed')) AS arrivals_today,
    (SELECT COUNT(*) FROM reservations
      WHERE check_out = CURDATE() AND status = 'checked_in')         AS departures_today,
    (SELECT COALESCE(SUM(amount), 0) FROM payments
      WHERE paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        AND paid_at <  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 MONTH) AS month_revenue;
