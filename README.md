# HotelHub

HotelHub është aplikacion PHP pa framework për administrimin e dhomave të hotelit.
Ndërtohet me PHP 8.3, PDO, MySQL 8 dhe pamje PHP/CSS pa build tools.

## Kërkesat

- PHP 8.3 me PDO MySQL, `mbstring`, `bcmath` dhe `fileinfo`
- Composer
- MySQL 8.0.16+ ose MariaDB 10.11+ (InnoDB; `CHECK` constraints aktive)
- Apache me `mod_rewrite`, ose serveri i integruar PHP

> Setup-i kontrollon versionin e serverit. MariaDB 10.11+ dhe MySQL 8.0.16+ pranohen;
> versionet më të vjetra refuzohen sepse mund të mos zbatojnë `CHECK` constraints.

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
`database/create_app_user.sql` për serverin tënd dhe përdor atë llogari në `.env`.
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

## Funksionalitetet

- Autentikimi me role admin, recepsionist dhe mysafir; regjistrimi publik krijon
  vetëm mysafirë. Sesionet rigjenerohen pas kyçjes, formularët mbrohen me CSRF
  dhe hyrja kufizohet në 5 tentativa/IP/minutë.
- CRUD për llojet e dhomave, pajisjet dhe dhomat, me kontrolle që parandalojnë
  fshirjen e të dhënave të lidhura me rezervime.
- Kërkim publik i dhomave të lira sipas datave dhe kapacitetit.
- Rezervime me kode unike, parandalim të mbivendosjes në transaksion, role dhe
  kontroll pronësie; recepsioni mund të regjistrojë mysafirë pa llogari.
- Konfirmim, anulim, check-in dhe check-out me kalime të vlefshme statusi.
- Katalog shërbimesh, çmime të ruajtura në momentin e shtimit, pagesa të
  pjesshme/të plota dhe faturë me total e bilanc.
- Dashboard administrativ me gjendjen e dhomave, mbërritjet/largimet dhe
  të ardhurat mujore.
- API JSON nën `/api` për autentikim, disponueshmëri, dhoma, rezervime, fatura
  dhe statistika. Për kërkesat me shkrim API kërkon sesion dhe token CSRF në
  `X-CSRF-Token`; shembujt gjenden te
  [koleksioni Postman](docs/HotelHub.postman_collection.json).

## Arkitektura

`public/index.php` është front controller; `src/Controllers` trajton kërkesat,
`src/Services` zbaton rregullat e biznesit, `src/Repositories` përmban SQL me PDO
prepared statements, ndërsa `views/` përmban shabllonet HTML. Skema krijohet
nga migrimet e renditura në `database/migrations/` dhe të dhënat demo nga
`database/seed.sql`.

## Testet

```powershell
composer test
```

Testet njësi (statuset, mbivendosja, afati i anulimit, paratë, router-i) nuk kërkojnë databazë.
Testet e integrimit (`tests/Integration`) ekzekutojnë `ReservationService` kundër MySQL real dhe
aktivizohen me një databazë testimi (fshihet dhe rikrijohet, prandaj emri duhet të mbarojë me `_test`):

```powershell
$env:TEST_DB_DATABASE="hotelhub_test"; $env:TEST_DB_USERNAME="root"; $env:TEST_DB_PASSWORD=""
composer test
```

Demonstrim i rezervimeve paralele (KJF08), me aplikacionin të ndezur:

```powershell
php tests/manual/concurrent_booking.php http://localhost:8000 10
# HTTP 201 × 1, HTTP 409 × 9
```
