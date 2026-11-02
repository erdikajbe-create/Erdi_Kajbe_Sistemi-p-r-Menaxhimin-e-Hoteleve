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
