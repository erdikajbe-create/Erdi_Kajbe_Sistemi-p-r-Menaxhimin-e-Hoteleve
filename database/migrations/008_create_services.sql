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
