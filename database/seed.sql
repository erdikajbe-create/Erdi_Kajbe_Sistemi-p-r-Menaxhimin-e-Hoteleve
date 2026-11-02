-- Të dhëna demo. Fjalëkalimi i të gjitha llogarive: "password" (ndryshoje në prodhim!)
-- Nëse nuk ke zgjedhur databazën: USE hotelhub;

INSERT INTO users (name, email, password_hash, role, phone) VALUES
('Admin Hotel',     'admin@hotelhub.test',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',        '+38344000001'),
('Arta Recepsioni', 'recepsion@hotelhub.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', '+38344000002'),
('Besa Mysafire',   'besa@example.test',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guest',        '+38344000003');

INSERT INTO room_types (name, description, base_price, capacity) VALUES
('Single',    'Dhomë me një shtrat për një person',          40.00, 1),
('Double',    'Dhomë me shtrat dyshe ose dy shtretër',       65.00, 2),
('Familjare', 'Dhomë e gjerë për familje',                   95.00, 4),
('Suite',     'Suitë luksoze me ballkon dhe zonë ndenjeje', 120.00, 3);

INSERT INTO amenities (name) VALUES ('Wi-Fi'), ('TV'), ('Minibar'), ('Ballkon');

INSERT INTO amenity_room_type (amenity_id, room_type_id) VALUES
(1,1),(1,2),(1,3),(1,4),
(2,1),(2,2),(2,3),(2,4),
(3,2),(3,4),
(4,3),(4,4);

INSERT INTO rooms (room_type_id, number, floor, status) VALUES
(1,'101',1,'available'),(1,'102',1,'available'),
(2,'201',2,'available'),(2,'202',2,'available'),(2,'203',2,'available'),
(3,'301',3,'available'),(3,'302',3,'maintenance'),
(4,'401',4,'available'),(4,'402',4,'available');

INSERT INTO guests (user_id, first_name, last_name, email, phone, document_number) VALUES
(3,    'Besa',  'Mysafire', 'besa@example.test',  '+38344000003', 'ID1234567'),
(NULL, 'Arben', 'Krasniqi', 'arben@example.test', '+38344111222', 'ID7654321');

INSERT INTO services (name, price, active) VALUES
('Mëngjes', 6.00, 1), ('Parking', 5.00, 1), ('Spa', 25.00, 1);

-- 1) pending nga mysafiri online (dhoma 201, 3 netë × 65)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('A1B2C3D4', 1, 3, 3, CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 13 DAY, 2, 0, 'pending', 195.00);

-- 2) confirmed, mbërrin sot (Suite 401, 2 netë × 120)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('E5F6G7H8', 2, 8, 2, CURDATE(), CURDATE() + INTERVAL 2 DAY, 2, 0, 'confirmed', 240.00);

-- 3) checked_in me shërbime dhe pagesë të pjesshme (Familjare 301, 3 netë × 95)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('J9K2L3M4', 2, 6, 2, CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 2 DAY, 2, 2, 'checked_in', 285.00);
UPDATE rooms SET status = 'occupied' WHERE id = 6;
INSERT INTO reservation_service (reservation_id, service_id, quantity, unit_price) VALUES (3, 1, 2, 6.00), (3, 2, 1, 5.00);
INSERT INTO payments (reservation_id, amount, method, reference, received_by) VALUES (3, 100.00, 'cash', 'Paradhënie', 2);

-- 4) i përfunduar para 18 ditësh, bilanc 0 (Double 202, 2 netë × 65)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('P5Q6R7S8', 1, 4, 2, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 18 DAY, 2, 0, 'checked_out', 130.00);
INSERT INTO payments (reservation_id, amount, method, received_by, paid_at) VALUES (4, 130.00, 'card', 2, NOW() - INTERVAL 18 DAY);

-- 5) e anuluar (nuk bllokon datat e rezervimit 1)
INSERT INTO reservations (code, guest_id, room_id, created_by, check_in, check_out, adults, children, status, total_amount)
VALUES ('T1U2V3W4', 1, 3, 3, CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 12 DAY, 2, 0, 'cancelled', 130.00);
