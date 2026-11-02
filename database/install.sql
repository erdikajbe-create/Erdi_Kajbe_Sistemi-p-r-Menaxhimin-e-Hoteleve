-- Ekzekuto nga dosja database/:   mysql -u root -p < install.sql
DROP DATABASE IF EXISTS hotelhub;
CREATE DATABASE hotelhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotelhub;

SOURCE migrations/001_create_users.sql;
SOURCE migrations/002_create_room_types.sql;
SOURCE migrations/003_create_amenities.sql;
SOURCE migrations/004_create_amenity_room_type.sql;
SOURCE migrations/005_create_rooms.sql;
SOURCE migrations/006_create_guests.sql;
SOURCE migrations/007_create_reservations.sql;
SOURCE migrations/008_create_services.sql;
SOURCE migrations/009_create_reservation_service.sql;
SOURCE migrations/010_create_payments.sql;
SOURCE migrations/011_create_login_attempts.sql;
SOURCE migrations/012_create_triggers.sql;
SOURCE migrations/013_create_views.sql;
SOURCE seed.sql;
