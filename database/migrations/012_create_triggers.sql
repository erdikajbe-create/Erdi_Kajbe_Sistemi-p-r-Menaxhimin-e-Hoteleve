-- 012 · Triggers: rrjeti i sigurisë në nivel databaze
-- Rregullat kryesore mbeten në Services (siç thotë dokumenti, seksioni 6);
-- këto parandalojnë gabimet edhe nëse dikush shkruan drejt në databazë.
-- Ekzekutohet me klientin mysql (DELIMITER). Në phpMyAdmin: fusha "Delimiter" = $$

DROP TRIGGER IF EXISTS trg_reservations_bi;
DROP TRIGGER IF EXISTS trg_reservations_bu;

DELIMITER $$

CREATE TRIGGER trg_reservations_bi
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    DECLARE v_capacity TINYINT UNSIGNED;
    DECLARE v_overlaps INT DEFAULT 0;

    -- RB04: kapaciteti
    SELECT rt.capacity INTO v_capacity
    FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id
    WHERE r.id = NEW.room_id;

    IF NEW.adults + NEW.children > v_capacity THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Numri i personave kalon kapacitetin e dhomës';
    END IF;

    -- RB01 + RB02: mbivendosja (krahasim strikt; cancelled nuk llogaritet)
    IF NEW.status <> 'cancelled' THEN
        SELECT COUNT(*) INTO v_overlaps
        FROM reservations x
        WHERE x.room_id   = NEW.room_id
          AND x.status   <> 'cancelled'
          AND x.check_in  < NEW.check_out
          AND x.check_out > NEW.check_in;

        IF v_overlaps > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Dhoma nuk është më e lirë';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_reservations_bu
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    DECLARE v_capacity TINYINT UNSIGNED;
    DECLARE v_overlaps INT DEFAULT 0;
    DECLARE v_services DECIMAL(10,2) DEFAULT 0;
    DECLARE v_paid     DECIMAL(10,2) DEFAULT 0;

    -- RB06: kalimet e lejuara të statusit
    IF NEW.status <> OLD.status THEN
        IF NOT (
               (OLD.status = 'pending'     AND NEW.status IN ('confirmed','cancelled'))
            OR (OLD.status = 'confirmed'   AND NEW.status IN ('checked_in','cancelled'))
            OR (OLD.status = 'checked_in'  AND NEW.status = 'checked_out')
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Kalim i palejuar i statusit të rezervimit';
        END IF;
    END IF;

    -- RB08: check-out vetëm me bilanc 0
    IF NEW.status = 'checked_out' AND OLD.status <> 'checked_out' THEN
        SELECT COALESCE(SUM(quantity * unit_price), 0) INTO v_services
        FROM reservation_service WHERE reservation_id = OLD.id;

        SELECT COALESCE(SUM(amount), 0) INTO v_paid
        FROM payments WHERE reservation_id = OLD.id;

        IF NEW.total_amount + v_services - v_paid <> 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Check-out nuk lejohet: bilanci nuk është 0';
        END IF;
    END IF;

    -- Rikontrollo kapacitetin dhe mbivendosjen kur ndryshon dhoma/datat/personat
    IF NEW.room_id <> OLD.room_id OR NEW.check_in <> OLD.check_in
       OR NEW.check_out <> OLD.check_out
       OR NEW.adults <> OLD.adults OR NEW.children <> OLD.children
       OR (NEW.status <> 'cancelled' AND OLD.status = 'cancelled') THEN

        SELECT rt.capacity INTO v_capacity
        FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id
        WHERE r.id = NEW.room_id;

        IF NEW.adults + NEW.children > v_capacity THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Numri i personave kalon kapacitetin e dhomës';
        END IF;

        IF NEW.status <> 'cancelled' THEN
            SELECT COUNT(*) INTO v_overlaps
            FROM reservations x
            WHERE x.room_id   = NEW.room_id
              AND x.id       <> OLD.id
              AND x.status   <> 'cancelled'
              AND x.check_in  < NEW.check_out
              AND x.check_out > NEW.check_in;

            IF v_overlaps > 0 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Dhoma nuk është më e lirë';
            END IF;
        END IF;
    END IF;
END$$

DELIMITER ;
