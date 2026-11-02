-- 003 · amenities
CREATE TABLE IF NOT EXISTS amenities (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_amenities_name (name)
) ENGINE=InnoDB;
