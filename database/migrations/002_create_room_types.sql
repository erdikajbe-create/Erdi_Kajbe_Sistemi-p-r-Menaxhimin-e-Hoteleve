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
