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
