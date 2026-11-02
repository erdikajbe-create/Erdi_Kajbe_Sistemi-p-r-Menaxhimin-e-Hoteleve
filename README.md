# HotelHub

HotelHub është aplikacion PHP pa framework për administrimin e dhomave të hotelit.
Ndërtohet me PHP 8.3, PDO, MySQL 8 dhe pamje PHP/CSS pa build tools.

## Kërkesat

- PHP 8.3 me PDO MySQL, `mbstring` dhe `fileinfo`
- Composer
- MySQL 8.0.16 ose më i ri (InnoDB)
- Apache me `mod_rewrite`, ose serveri i integruar PHP

> XAMPP mund të përfshijë MariaDB në vend të MySQL. Ekzekuto `database/setup.php` për
> kontrollin e versionit; MariaDB dhe MySQL më të vjetër se 8.0.16 refuzohen, sepse
> mund të mos zbatojnë `CHECK` constraints.

## Instalimi lokal (Windows / XAMPP / Laragon)

1. Hape terminalin në rrënjën e projektit dhe instalo varësitë:

   ```powershell
   composer install
   ```

2. Kopjo `.env.example` në `.env` dhe plotëso kredencialet e databazës. Për XAMPP,
   nëse PHP/Composer nuk janë në `PATH`, përdor shtigjet e instalimit, p.sh.:

   ```powershell
   Copy-Item .env.example .env
   C:\xampp\php\php.exe database\setup.php
   ```

   Për Laragon, përdor `php.exe` nga versioni aktiv i PHP-së në `laragon\bin\php`.
   Setup-i përdor një databazë të re ose bosh dhe **nuk i fshin databazat/tabelat**.
   Nëse databaza e konfiguruar ka tabela, skripti ndalet; zgjidh një databazë të re.

3. Nise aplikacionin nga rrënja:

   ```powershell
   C:\xampp\php\php.exe -S localhost:8000 -t public
   ```

   Hape `http://localhost:8000`. Me Apache, vendos document root te dosja `public/`
   dhe aktivizo `mod_rewrite`.

Për konfigurimin e përdoruesit të databazës me privilegje minimale, përshtat
`database/create_app_user.sql` për MySQL-in tënd dhe përdor atë llogari në `.env`.
Mos përdor `root` në instalim të publikuar.

### Paralajmërim për `install.sql`

`database/install.sql` është skripti i klientit `mysql` për instalim manual dhe
përmban `DROP DATABASE IF EXISTS hotelhub`; ky veprim fshin databazën ekzistuese.
Për instalim të sigurt lokal rekomandohet `database/setup.php`, i cili ndalon kur
databaza ka tabela.

## Llogaritë demo

Pas setup-it mund të hysh me fjalëkalimin `password`:

| Roli | Email |
|---|---|
| Admin | `admin@hotelhub.test` |
| Recepsionist | `recepsion@hotelhub.test` |
| Mysafir | `besa@example.test` |

Ndrysho kredencialet demo para përdorimit jashtë ambientit lokal.

## Fazat e përfunduara

- **Faza 0:** bazat MVC, konfigurimi, PDO, Router, sesioni, layout dhe stilet.
- **Faza 1:** regjistrim mysafiri, hyrje/dalje, CSRF, hash fjalëkalimi, rate limit
  5 tentativa/IP/minutë dhe krijimi i recepsionistëve nga admini.
- **Faza 2:** llojet e dhomave, lidhja e pajisjeve, dhomat, ndryshimi i statusit
  dhe mbrojtja nga fshirja e dhomave me rezervime.

Pajisjet përdorin tabelat `amenities`/`amenity_room_type`; fshirja e një lloji që
ka dhoma bllokohet nga FK ekzistuese. Skema e ofruar mbetet burimi i së vërtetës
dhe nuk është ndryshuar. Në veçanti, `guests.phone` në migrimin ekzistues është
nullable; regjistrimi publik nuk kërkon telefon.

**Supozime:** për regjistrimin publik kërkohen emri dhe mbiemri në të njëjtën
fushë; paneli i dhomave u jepet adminit dhe recepsionistit, ndërsa krijimi dhe
fshirja u lejohen vetëm administratorëve. Pagesat, shërbimet dhe rezervimet
mbeten për fazat pasuese.

## Testet

```powershell
composer test
```

Testet njësi të përfshira nuk kërkojnë lidhje databaze.
