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
