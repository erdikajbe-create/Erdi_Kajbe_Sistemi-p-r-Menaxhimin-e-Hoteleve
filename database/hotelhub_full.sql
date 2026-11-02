-- HotelHub · skedar i plotë (skemë + triggers + views + seed) për import në phpMyAdmin
DROP DATABASE IF EXISTS hotelhub;
CREATE DATABASE hotelhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotelhub;

-- 001 · users
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL COMMENT 'password_hash() - asnjëherë fjalëkalim i hapur',
    role          ENUM('admin','receptionist','guest') NOT NULL DEFAULT 'guest',
    phone         VARCHAR(30)  NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    CONSTRAINT chk_users_email CHECK (email LIKE '%_@_%.__%')
) ENGINE=InnoDB;

-- 002 · room_types
CREATE TABLE IF NOT EXISTS room_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)   NOT NULL,
    description TEXT          NULL,
    base_price  DECIMAL(10,2) NOT NULL COMMENT 'çmimi bazë për natë (€)',
    capacity    TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_room_types_name (name),
    CONSTRAINT chk_room_types_price    CHECK (base_price > 0),
    CONSTRAINT chk_room_types_capacity CHECK (capacity BETWEEN 1 AND 6)
) ENGINE=InnoDB;

-- 003 · amenities
CREATE TABLE IF NOT EXISTS amenities (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_amenities_name (name)
) ENGINE=InnoDB;

-- 004 · amenity_room_type (pivot M:N)
CREATE TABLE IF NOT EXISTS amenity_room_type (
    amenity_id   INT UNSIGNED NOT NULL,
    room_type_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (amenity_id, room_type_id),
    KEY idx_art_room_type (room_type_id),
    CONSTRAINT fk_art_amenity   FOREIGN KEY (amenity_id)
        REFERENCES amenities (id) ON DELETE CASCADE,
    CONSTRAINT fk_art_room_type FOREIGN KEY (room_type_id)
        REFERENCES room_types (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 005 · rooms
CREATE TABLE IF NOT EXISTS rooms (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_type_id INT UNSIGNED NOT NULL,
    number       VARCHAR(10)  NOT NULL,
    floor        TINYINT      NOT NULL,
    status       ENUM('available','occupied','cleaning','maintenance')
                 NOT NULL DEFAULT 'available',
    PRIMARY KEY (id),
    UNIQUE KEY uq_rooms_number (number),
    KEY idx_rooms_type (room_type_id),
    KEY idx_rooms_status (status),
    CONSTRAINT fk_rooms_type FOREIGN KEY (room_type_id)
        REFERENCES room_types (id) ON DELETE RESTRICT          -- RB10
) ENGINE=InnoDB;

-- 006 · guests
CREATE TABLE IF NOT EXISTS guests (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NULL COMMENT 'NULL = mysafir pa llogari (regjistruar nga recepsioni)',
    first_name      VARCHAR(80)  NOT NULL,
    last_name       VARCHAR(80)  NOT NULL,
    email           VARCHAR(150) NULL,
    phone           VARCHAR(30)  NULL,
    document_number VARCHAR(50)  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guests_user (user_id),
    KEY idx_guests_name (last_name, first_name),
    KEY idx_guests_email (email),
    CONSTRAINT fk_guests_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 007 · reservations (qendra e modelit)
CREATE TABLE IF NOT EXISTS reservations (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         CHAR(8)      NOT NULL COMMENT 'kod unik 8 karakterësh',
    guest_id     INT UNSIGNED NOT NULL,
    room_id      INT UNSIGNED NOT NULL,
    created_by   INT UNSIGNED NULL COMMENT 'përdoruesi që e krijoi',
    check_in     DATE         NOT NULL,
    check_out    DATE         NOT NULL,
    adults       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    children     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status       ENUM('pending','confirmed','checked_in','checked_out','cancelled')
                 NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL COMMENT 'netë × çmimi bazë në momentin e rezervimit (RB05)',
    notes        TEXT         NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reservations_code (code),
    KEY idx_res_availability (room_id, check_in, check_out),    -- KJF07
    KEY idx_res_guest  (guest_id),
    KEY idx_res_status (status, check_in),
    KEY idx_res_check_out (check_out),
    CONSTRAINT fk_res_guest   FOREIGN KEY (guest_id)   REFERENCES guests (id) ON DELETE RESTRICT,
    CONSTRAINT fk_res_room    FOREIGN KEY (room_id)    REFERENCES rooms  (id) ON DELETE RESTRICT,
    CONSTRAINT fk_res_creator FOREIGN KEY (created_by) REFERENCES users  (id) ON DELETE SET NULL,
    CONSTRAINT chk_res_dates  CHECK (check_out > check_in),     -- RB03
    CONSTRAINT chk_res_adults CHECK (adults >= 1),
    CONSTRAINT chk_res_total  CHECK (total_amount >= 0)
) ENGINE=InnoDB;

-- 008 · services
CREATE TABLE IF NOT EXISTS services (
    id     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name   VARCHAR(80)   NOT NULL,
    price  DECIMAL(10,2) NOT NULL,
    active TINYINT(1)    NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_name (name),
    CONSTRAINT chk_services_price CHECK (price >= 0)
) ENGINE=InnoDB;

-- 009 · reservation_service (pivot M:N me çmim historik - RB09)
CREATE TABLE IF NOT EXISTS reservation_service (
    reservation_id INT UNSIGNED NOT NULL,
    service_id     INT UNSIGNED NOT NULL,
    quantity       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price     DECIMAL(10,2) NOT NULL COMMENT 'çmimi në momentin e shtimit',
    added_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reservation_id, service_id),
    KEY idx_rs_service (service_id),
    CONSTRAINT fk_rs_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations (id) ON DELETE CASCADE,
    CONSTRAINT fk_rs_service FOREIGN KEY (service_id)
        REFERENCES services (id) ON DELETE RESTRICT,
    CONSTRAINT chk_rs_quantity CHECK (quantity > 0),
    CONSTRAINT chk_rs_price    CHECK (unit_price >= 0)
) ENGINE=InnoDB;

-- 010 · payments
CREATE TABLE IF NOT EXISTS payments (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    reservation_id INT UNSIGNED  NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    method         ENUM('cash','card','bank_transfer') NOT NULL,
    reference      VARCHAR(100)  NULL,
    received_by    INT UNSIGNED  NULL,
    paid_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pay_reservation (reservation_id),
    KEY idx_pay_paid_at (paid_at),                              -- të ardhurat mujore
    CONSTRAINT fk_pay_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pay_user FOREIGN KEY (received_by)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_pay_amount CHECK (amount > 0)                -- KF18
) ENGINE=InnoDB;

-- 011 · login_attempts (OPSIONALE - për KJF06: 5 tentativa/minutë për IP)
-- Nëse e mban kufizimin në sesion/cache, mos e ekzekuto këtë skedar.
CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_address   VARCHAR(45)  NOT NULL,
    email        VARCHAR(150) NULL,
    attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_la_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;
-- Numrimi: SELECT COUNT(*) FROM login_attempts
--          WHERE ip_address = :ip AND attempted_at > NOW() - INTERVAL 1 MINUTE;
-- Pastrimi: DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY;

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

-- Të dhëna demo. Fjalëkalimi i të gjitha llogarive: "password" (ndryshoje në prodhim!)
-- Nëse nuk ke zgjedhur databazën: USE hotelhub;

INSERT INTO users (name, email, password_hash, role, phone) VALUES
('Admin Hotel',     'admin@hotelhub.test',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',        '+38344000001'),
('Arta Recepsioni', 'recepsion@hotelhub.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', '+38344000002'),
('Besa Mysafire',   'besa@example.test',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guest',        '+38344000003');

INSERT INTO room_types (name, description, base_price, capacity) VALUES
('Single',    'Dhomë me një shtrat për një person',          40.00, 1),
('Double',    'Dhomë me shtrat dyshe ose dy shtretër',       65.00, 2),
('Familjare', 'Dhomë e gjerë për familje',                   95.00, 4),
('Suite',     'Suitë luksoze me ballkon dhe zonë ndenjeje', 120.00, 3);

INSERT INTO amenities (name) VALUES ('Wi-Fi'), ('TV'), ('Minibar'), ('Ballkon');

INSERT INTO amenity_room_type (amenity_id, room_type_id) VALUES
(1,1),(1,2),(1,3),(1,4),
(2,1),(2,2),(2,3),(2,4),
(3,2),(3,4),
(4,3),(4,4);

INSERT INTO rooms (room_type_id, number, floor, status) VALUES
(1,'101',1,'available'),(1,'102',1,'available'),
(2,'201',2,'available'),(2,'202',2,'available'),(2,'203',2,'available'),
(3,'301',3,'available'),(3,'302',3,'maintenance'),
(4,'401',4,'available'),(4,'402',4,'available');

INSERT INTO guests (user_id, first_name, last_name, email, phone, document_number) VALUES
(3,    'Besa',  'Mysafire', 'besa@example.test',  '+38344000003', 'ID1234567'),
(NULL, 'Arben', 'Krasniqi', 'arben@example.test', '+38344111222', 'ID7654321');

INSERT INTO services (name, price, active) VALUES
('Mëngjes', 6.00, 1), ('Parking', 5.00, 1), ('Spa', 25.00, 1);

-- 1) pending nga mysafiri online (dhoma 201, 3 netë × 65)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('A1B2C3D4', 1, 3, 3, CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 13 DAY, 2, 0, 'pending', 195.00);

-- 2) confirmed, mbërrin sot (Suite 401, 2 netë × 120)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('E5F6G7H8', 2, 8, 2, CURDATE(), CURDATE() + INTERVAL 2 DAY, 2, 0, 'confirmed', 240.00);

-- 3) checked_in me shërbime dhe pagesë të pjesshme (Familjare 301, 3 netë × 95)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('J9K2L3M4', 2, 6, 2, CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 2 DAY, 2, 2, 'checked_in', 285.00);
UPDATE rooms SET status = 'occupied' WHERE id = 6;
INSERT INTO reservation_service (reservation_id, service_id, quantity, unit_price) VALUES (3, 1, 2, 6.00), (3, 2, 1, 5.00);
INSERT INTO payments (reservation_id, amount, method, reference, received_by) VALUES (3, 100.00, 'cash', 'Paradhënie', 2);

-- 4) i përfunduar para 18 ditësh, bilanc 0 (Double 202, 2 netë × 65)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('P5Q6R7S8', 1, 4, 2, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 18 DAY, 2, 0, 'checked_out', 130.00);
INSERT INTO payments (reservation_id, amount, method, received_by, paid_at) VALUES (4, 130.00, 'card', 2, NOW() - INTERVAL 18 DAY);

-- 5) e anuluar (nuk bllokon datat e rezervimit 1)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('T1U2V3W4', 1, 3, 3, CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 12 DAY, 2, 0, 'cancelled', 130.00);
