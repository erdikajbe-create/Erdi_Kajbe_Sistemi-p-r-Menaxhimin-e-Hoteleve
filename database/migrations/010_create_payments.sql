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
