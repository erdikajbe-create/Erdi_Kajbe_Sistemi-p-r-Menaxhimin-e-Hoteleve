-- Përdorues me të drejta minimale për aplikacionin (mos përdor root në .env)
CREATE USER IF NOT EXISTS 'hotelhub_app'@'localhost' IDENTIFIED BY 'NDRYSHO_KETE_FJALEKALIM';
GRANT SELECT, INSERT, UPDATE, DELETE ON hotelhub.* TO 'hotelhub_app'@'localhost';
FLUSH PRIVILEGES;
-- .env:  DB_USERNAME=hotelhub_app  dhe  DB_PASSWORD=<fjalëkalimi i mësipërm>
