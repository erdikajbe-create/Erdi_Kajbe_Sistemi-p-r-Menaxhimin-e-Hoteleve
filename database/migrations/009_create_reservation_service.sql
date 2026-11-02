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
