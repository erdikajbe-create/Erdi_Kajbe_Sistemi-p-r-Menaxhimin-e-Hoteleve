-- 014 · reservation_service si rreshta fature (RB09)
-- Me PK (reservation_id, service_id) i njëjti shërbim mund të shtohej vetëm një herë:
-- "2 mëngjese" sot dhe "1 mëngjes" nesër (me çmim të ri) nuk mund të ruheshin si dy rreshta
-- me çmimin e momentit të shtimit. Çdo shtim tani është rresht më vete me id-në e vet.
ALTER TABLE reservation_service
    ADD KEY idx_rs_reservation (reservation_id),
    DROP PRIMARY KEY,
    ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
    ADD COLUMN added_by INT UNSIGNED NULL AFTER unit_price,
    ADD CONSTRAINT fk_rs_user FOREIGN KEY (added_by) REFERENCES users (id) ON DELETE SET NULL;
