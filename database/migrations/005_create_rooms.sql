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
