<?php

declare(strict_types=1);

namespace HotelHub\Core;

use HotelHub\Controllers\AuthController;
use HotelHub\Controllers\AvailabilityController;
use HotelHub\Controllers\DashboardController;
use HotelHub\Controllers\RoomController;
use HotelHub\Controllers\RoomTypeController;
use HotelHub\Controllers\ReservationController;
use HotelHub\Controllers\ServiceController;
use HotelHub\Controllers\Api\AuthApiController;
use HotelHub\Controllers\Api\DashboardApiController;
use HotelHub\Controllers\Api\ReservationApiController;
use HotelHub\Controllers\Api\RoomApiController;
use HotelHub\Repositories\DashboardRepository;
use HotelHub\Repositories\ReservationRepository;
use HotelHub\Repositories\ServiceRepository;
use HotelHub\Repositories\AvailabilityRepository;
use HotelHub\Repositories\GuestRepository;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\RoomTypeRepository;
use HotelHub\Repositories\UserRepository;
use HotelHub\Services\AuthService;
use HotelHub\Services\AvailabilityService;
use HotelHub\Services\RoomManagementService;
use HotelHub\Services\ReservationService;
use HotelHub\Services\InvoiceService;
use HotelHub\Services\ServiceCatalogService;
use PDO;

final class Application
{
    private ?PDO $pdo = null;
    private ?UserRepository $users = null;
    private ?GuestRepository $guests = null;
    private ?AvailabilityRepository $availabilityRepository = null;
    private ?RoomRepository $rooms = null;
    private ?RoomTypeRepository $roomTypes = null;
    private ?AuthService $auth = null;
    private ?AvailabilityService $availability = null;
    private ?RoomManagementService $roomManagement = null;

    /**
     * @param array{host: string, port: string, database: string, username: string, password: string} $databaseConfig
     */
    public function __construct(private readonly array $databaseConfig)
    {
    }

    public function router(): Router
    {
        $router = new Router();
        $router->get('/', static function (): void {
            Response::html(View::render('home', [
                'title' => 'HotelHub',
                'user' => Auth::user(),
            ]));
        });
        $router->get('/availability', function (): void {
            $this->availabilityController()->index();
        });
        // API JSON me sesion dhe CSRF; Services janë të njëjta me faqet HTML.
        $router->get('/api/availability', function (): void { $this->roomApiController()->availability(); });
        $router->get('/api/auth/csrf', function (): void { $this->authApiController()->csrf(); });
        $router->post('/api/auth/register', function (): void { $this->authApiController()->register(); });
        $router->post('/api/auth/login', function (): void { $this->authApiController()->login(); });
        $router->post('/api/auth/logout', function (): void { $this->authApiController()->logout(); });
        $router->get('/api/auth/me', function (): void { $this->authApiController()->me(); });
        $router->get('/api/room-types', function (): void { $this->roomApiController()->types(); });
        $router->post('/api/room-types', function (): void { $this->roomApiController()->createType(); });
        $router->patch('/api/room-types/{id}', function (string $id): void { $this->roomApiController()->updateType($id); });
        $router->delete('/api/room-types/{id}', function (string $id): void { $this->roomApiController()->deleteType($id); });
        $router->get('/api/rooms', function (): void { $this->roomApiController()->rooms(); });
        $router->post('/api/rooms', function (): void { $this->roomApiController()->createRoom(); });
        $router->patch('/api/rooms/{id}/status', function (string $id): void { $this->roomApiController()->updateStatus($id); });
        $router->get('/api/reservations', function (): void { $this->reservationApiController()->index(); });
        $router->post('/api/reservations', function (): void { $this->reservationApiController()->store(); });
        $router->get('/api/reservations/{id}', function (string $id): void { $this->reservationApiController()->show($id); });
        $router->post('/api/reservations/{id}/confirm', function (string $id): void { $this->reservationApiController()->confirm($id); });
        $router->post('/api/reservations/{id}/cancel', function (string $id): void { $this->reservationApiController()->cancel($id); });
        $router->post('/api/reservations/{id}/check-in', function (string $id): void { $this->reservationApiController()->checkIn($id); });
        $router->post('/api/reservations/{id}/check-out', function (string $id): void { $this->reservationApiController()->checkOut($id); });
        $router->post('/api/reservations/{id}/services', function (string $id): void { $this->reservationApiController()->addService($id); });
        $router->post('/api/reservations/{id}/payments', function (string $id): void { $this->reservationApiController()->addPayment($id); });
        $router->get('/api/reservations/{id}/invoice', function (string $id): void { $this->reservationApiController()->invoice($id); });
        $router->get('/api/dashboard/stats', function (): void { $this->dashboardApiController()->stats(); });

        $router->get('/register', function (): void {
            $this->authController()->registerForm();
        });
        $router->post('/register', function (): void {
            $this->authController()->register();
        });
        $router->get('/login', function (): void {
            $this->authController()->loginForm();
        });
        $router->post('/login', function (): void {
            $this->authController()->login();
        });
        $router->post('/logout', function (): void {
            $this->authController()->logout();
        });
        $router->get('/admin/staff', function (): void {
            $this->authController()->staffIndex();
        });
        $router->post('/admin/staff', function (): void {
            $this->authController()->createStaff();
        });

        $router->get('/admin/room-types', function (): void {
            $this->roomTypeController()->index();
        });
        $router->post('/admin/room-types', function (): void {
            $this->roomTypeController()->create();
        });
        $router->post('/admin/room-types/{id}', function (string $id): void {
            $this->roomTypeController()->update($id);
        });
        $router->post('/admin/room-types/{id}/delete', function (string $id): void {
            $this->roomTypeController()->delete($id);
        });
        $router->post('/admin/amenities', function (): void {
            $this->roomTypeController()->createAmenity();
        });
        $router->post('/admin/amenities/{id}', function (string $id): void {
            $this->roomTypeController()->updateAmenity($id);
        });
        $router->post('/admin/amenities/{id}/delete', function (string $id): void {
            $this->roomTypeController()->deleteAmenity($id);
        });

        $router->get('/rooms', function (): void {
            $this->roomController()->index();
        });
        $router->post('/rooms', function (): void {
            $this->roomController()->create();
        });
        $router->post('/rooms/{id}', function (string $id): void {
            $this->roomController()->update($id);
        });
        $router->post('/rooms/{id}/status', function (string $id): void {
            $this->roomController()->updateStatus($id);
        });
        $router->post('/rooms/{id}/delete', function (string $id): void {
            $this->roomController()->delete($id);
        });

        $router->get('/reservations', function (): void { $this->reservationController()->index(); });
        $router->get('/reservations/create', function (): void { $this->reservationController()->create(); });
        $router->post('/reservations', function (): void { $this->reservationController()->store(); });
        $router->get('/reservations/{id}', function (string $id): void { $this->reservationController()->show($id); });
        $router->post('/reservations/{id}/confirm', function (string $id): void { $this->reservationController()->confirm($id); });
        $router->post('/reservations/{id}/cancel', function (string $id): void { $this->reservationController()->cancel($id); });
        $router->post('/reservations/{id}/check-in', function (string $id): void { $this->reservationController()->checkIn($id); });
        $router->post('/reservations/{id}/check-out', function (string $id): void { $this->reservationController()->checkOut($id); });
        $router->post('/reservations/{id}/services', function (string $id): void { $this->reservationController()->addService($id); });
        $router->post('/reservations/{id}/payments', function (string $id): void { $this->reservationController()->addPayment($id); });

        $router->get('/admin/services', function (): void { $this->serviceController()->index(); });
        $router->post('/admin/services', function (): void { $this->serviceController()->create(); });
        $router->post('/admin/services/{id}', function (string $id): void { $this->serviceController()->update($id); });
        $router->get('/dashboard', function (): void { $this->dashboardController()->index(); });

        return $router;
    }

    private function authController(): AuthController
    {
        return new AuthController($this->authService(), $this->userRepository());
    }

    private function availabilityController(): AvailabilityController
    {
        return new AvailabilityController($this->availabilityService());
    }

    private function roomController(): RoomController
    {
        return new RoomController($this->roomManagementService(), $this->roomRepository());
    }

    private function roomTypeController(): RoomTypeController
    {
        return new RoomTypeController($this->roomManagementService(), $this->roomTypeRepository());
    }

    private function reservationController(): ReservationController
    {
        return new ReservationController(
            $this->reservationService(),
            $this->invoiceService(),
            $this->roomRepository(),
            $this->guestRepository(),
            new ServiceRepository($this->pdo()),
        );
    }

    private function reservationService(): ReservationService
    {
        $pdo = $this->pdo();
        $services = new ServiceRepository($pdo);

        return new ReservationService(
            $pdo,
            new ReservationRepository($pdo),
            $this->roomRepository(),
            $this->guestRepository(),
            $services,
        );
    }

    private function invoiceService(): InvoiceService
    {
        return new InvoiceService(new ReservationRepository($this->pdo()));
    }

    private function serviceController(): ServiceController
    {
        $services = new ServiceRepository($this->pdo());
        return new ServiceController(new ServiceCatalogService($services), $services);
    }

    private function dashboardController(): DashboardController
    {
        return new DashboardController(new DashboardRepository($this->pdo()));
    }

    private function authApiController(): AuthApiController
    {
        return new AuthApiController($this->authService());
    }

    private function reservationApiController(): ReservationApiController
    {
        return new ReservationApiController($this->reservationService(), $this->invoiceService());
    }

    private function roomApiController(): RoomApiController
    {
        return new RoomApiController(
            $this->availabilityService(),
            $this->roomManagementService(),
            $this->roomRepository(),
            $this->roomTypeRepository(),
        );
    }

    private function dashboardApiController(): DashboardApiController
    {
        return new DashboardApiController(new DashboardRepository($this->pdo()));
    }

    private function authService(): AuthService
    {
        return $this->auth ??= new AuthService($this->pdo(), $this->userRepository(), $this->guestRepository());
    }

    private function availabilityService(): AvailabilityService
    {
        return $this->availability ??= new AvailabilityService($this->availabilityRepository());
    }

    private function roomManagementService(): RoomManagementService
    {
        return $this->roomManagement ??= new RoomManagementService(
            $this->pdo(),
            $this->roomRepository(),
            $this->roomTypeRepository(),
        );
    }

    private function userRepository(): UserRepository
    {
        return $this->users ??= new UserRepository($this->pdo());
    }

    private function guestRepository(): GuestRepository
    {
        return $this->guests ??= new GuestRepository($this->pdo());
    }

    private function availabilityRepository(): AvailabilityRepository
    {
        return $this->availabilityRepository ??= new AvailabilityRepository($this->pdo());
    }

    private function roomRepository(): RoomRepository
    {
        return $this->rooms ??= new RoomRepository($this->pdo());
    }

    private function roomTypeRepository(): RoomTypeRepository
    {
        return $this->roomTypes ??= new RoomTypeRepository($this->pdo());
    }

    private function pdo(): PDO
    {
        return $this->pdo ??= Database::connect($this->databaseConfig);
    }
}
