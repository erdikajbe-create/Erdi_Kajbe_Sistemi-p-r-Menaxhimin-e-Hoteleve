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
