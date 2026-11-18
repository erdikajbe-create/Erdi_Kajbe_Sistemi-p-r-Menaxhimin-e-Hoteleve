# HotelHub

HotelHub është aplikacion PHP pa framework për administrimin e dhomave të hotelit.
Ndërtohet me PHP 8.3, PDO, MySQL 8 dhe pamje PHP/CSS pa build tools.

## Kërkesat

- PHP 8.3 me PDO MySQL, `mbstring` dhe `bcmath` (në XAMPP për Windows `bcmath` është i përfshirë)
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

## Çfarë përfshin

| # | Moduli | Faqet | API |
|---|---|---|---|
| 1 | Autentikimi dhe rolet | `/login`, `/register`, `/admin/staff` | `/api/auth/*` |
| 2 | Llojet e dhomave dhe dhomat | `/admin/room-types`, `/rooms` | `/api/room-types`, `/api/rooms` |
| 3 | Disponueshmëria | `/availability` (punon edhe pa JS) | `GET /api/availability` |
| 4 | Rezervimet | `/reservations`, `/reservations/create`, `/reservations/{id}` | `/api/reservations` |
| 5 | Check-in / check-out | butonat në faqen e rezervimit | `POST /api/reservations/{id}/check-in`, `/check-out` |
| 6 | Shërbimet shtesë | `/admin/services` + forma në rezervim | `POST /api/reservations/{id}/services` |
| 7 | Pagesat dhe fatura | forma në rezervim | `/payments`, `GET /invoice` |
| 8 | Dashboard | `/dashboard` (admin) | `GET /api/dashboard/stats` |

Pas hyrjes: admini shkon te `/dashboard`, recepsionisti te `/reservations`, mysafiri te
`/availability` (ose te faqja që kishte hapur para hyrjes).

### Rregullat kryesore (Services)

- **Mosmbivendosja (RB01, RB02, KJF08):** `ReservationService::create()` hap transaksion, bllokon
  rreshtin e dhomës me `SELECT ... FOR UPDATE` dhe kontrollon `check_in < i_ri.check_out AND
  check_out > i_ri.check_in`. Trigger-i `trg_reservations_bi` është rrjeti i fundit në databazë.
- **Input-i i klientit:** merren vetëm fushat e lejuara; `status`, `code`, `created_by` dhe
  `total_amount` caktohen nga serveri. Mysafiri rezervon gjithmonë në emër të vet.
- **Statuset (RB06):** `ReservationStatus::canTransitionTo()`; çdo kalim tjetër → 422
  `INVALID_STATUS_TRANSITION`.
- **Anulimi (RB07):** mysafiri deri 24 orë para orës 14:00 të mbërritjes; stafi deri në check-in.
- **Check-in (KF16):** vetëm `confirmed`, nga dita e mbërritjes dhe me dhomë `available` → `occupied`.
- **Check-out (RB08):** vetëm me bilanc 0 → dhoma `cleaning`. Pagesa më e madhe se bilanci refuzohet,
  sepse përndryshe check-out-i nuk do të bëhej kurrë.
- **Paratë:** `DECIMAL(10,2)` në databazë dhe `bcmath` në PHP (`Core\Money`), pa float.
- **Statusi manual i dhomës:** `occupied` vendoset/hiqet vetëm nga check-in/check-out.

## API JSON dhe Postman

Importo `docs/HotelHub.postman_collection.json`. Sesioni ruhet me cookie. Çdo POST/PATCH/DELETE
kërkon header-in `X-CSRF-Token` (merret me `GET /api/auth/csrf`; koleksioni e bën automatikisht).
Gabimet kanë gjithmonë të njëjtin format:

```json
{ "success": false, "error": "ROOM_NOT_AVAILABLE", "message": "Dhoma 201 është e zënë për datat e zgjedhura." }
```

Kodet: 401 `UNAUTHENTICATED`, 403 `FORBIDDEN`, 404 `RESERVATION_NOT_FOUND`/`NOT_FOUND`,
409 `ROOM_NOT_AVAILABLE`, 422 `VALIDATION_ERROR` (+ `errors` për çdo fushë),
`INVALID_STATUS_TRANSITION`, `OUTSTANDING_BALANCE`, `CANCELLATION_WINDOW_CLOSED`, 429 `TOO_MANY_REQUESTS`.

## Ndryshimet në skemë

- `014_reservation_service_line_items.sql`: `reservation_service` merr `id` si PK dhe `added_by`.
  Me PK `(reservation_id, service_id)` i njëjti shërbim nuk mund të shtohej dy herë (p.sh. mëngjes
  çdo ditë) me çmimin e momentit të shtimit (RB09).
- `database/hotelhub_full.sql` rigjenerohet nga migrimet + seed.
- Lidhja PDO vendos `time_zone` sipas `APP_TIMEZONE`, që `CURDATE()` në MySQL dhe `date()` në PHP
  të jenë e njëjta "sot".

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
