<?php

declare(strict_types=1);

namespace HotelHub\Core;

use HotelHub\Controllers\Api\AuthApiController;
use HotelHub\Controllers\Api\DashboardApiController;
use HotelHub\Controllers\Api\ReservationApiController;
use HotelHub\Controllers\Api\RoomApiController;
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
use HotelHub\Repositories\ReservationRepository;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\RoomTypeRepository;
use HotelHub\Repositories\ServiceRepository;
use HotelHub\Repositories\UserRepository;
use HotelHub\Services\AuthService;
use HotelHub\Services\AvailabilityService;
use HotelHub\Services\RoomManagementService;
use HotelHub\Services\ReservationService;
use HotelHub\Services\InvoiceService;
use HotelHub\Services\ServiceCatalogService;
use PDO;

/**
 * Lidh rrugët me controller-ët dhe ndërton objektet (një container i vogël manual).
 * Objektet krijohen vetëm kur nevojiten, që faqet pa databazë të mos hapin lidhje.
 */
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
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            $pdo = Database::connect($config['database']);
            Response::html(View::render('home', [
                'title' => 'HotelHub',
                'user' => Auth::user(),
                'roomTypes' => (new RoomTypeRepository($pdo))->all(),
                'services' => (new ServiceRepository($pdo))->all(true),
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

        // ---------------------------------------------------------------- faqet HTML
        $this->routes($router, AuthController::class, [
            ['GET', '/register', 'registerForm'],
            ['POST', '/register', 'register'],
            ['GET', '/login', 'loginForm'],
            ['POST', '/login', 'login'],
            ['POST', '/logout', 'logout'],
            ['GET', '/admin/staff', 'staffIndex'],
            ['POST', '/admin/staff', 'createStaff'],
        ]);
        $this->routes($router, RoomTypeController::class, [
            ['GET', '/admin/room-types', 'index'],
            ['POST', '/admin/room-types', 'create'],
            ['POST', '/admin/room-types/{id}', 'update'],
            ['POST', '/admin/room-types/{id}/delete', 'delete'],
            ['POST', '/admin/amenities', 'createAmenity'],
            ['POST', '/admin/amenities/{id}', 'updateAmenity'],
            ['POST', '/admin/amenities/{id}/delete', 'deleteAmenity'],
        ]);
        $this->routes($router, RoomController::class, [
            ['GET', '/rooms', 'index'],
            ['POST', '/rooms', 'create'],
            ['POST', '/rooms/{id}', 'update'],
            ['POST', '/rooms/{id}/status', 'updateStatus'],
            ['POST', '/rooms/{id}/delete', 'delete'],
        ]);
        $this->routes($router, AvailabilityController::class, [
            ['GET', '/availability', 'index'],
        ]);
        $this->routes($router, ReservationController::class, [
            ['GET', '/reservations', 'index'],
            ['GET', '/reservations/create', 'create'],
            ['POST', '/reservations', 'store'],
            ['GET', '/reservations/{id}', 'show'],
            ['POST', '/reservations/{id}/confirm', 'confirm'],
            ['POST', '/reservations/{id}/cancel', 'cancel'],
            ['POST', '/reservations/{id}/check-in', 'checkIn'],
            ['POST', '/reservations/{id}/check-out', 'checkOut'],
            ['POST', '/reservations/{id}/services', 'addService'],
            ['POST', '/reservations/{id}/payments', 'addPayment'],
        ]);
        $this->routes($router, ServiceController::class, [
            ['GET', '/admin/services', 'index'],
            ['POST', '/admin/services', 'create'],
            ['POST', '/admin/services/{id}', 'update'],
        ]);
        $this->routes($router, DashboardController::class, [
            ['GET', '/dashboard', 'index'],
        ]);

        // ---------------------------------------------------------------- JSON /api
        $this->routes($router, AuthApiController::class, [
            ['GET', '/api/auth/csrf', 'csrf'],
            ['POST', '/api/auth/register', 'register'],
            ['POST', '/api/auth/login', 'login'],
            ['POST', '/api/auth/logout', 'logout'],
            ['GET', '/api/auth/me', 'me'],
        ]);
        $this->routes($router, RoomApiController::class, [
            ['GET', '/api/availability', 'availability'],
            ['GET', '/api/room-types', 'types'],
            ['POST', '/api/room-types', 'createType'],
            ['PATCH', '/api/room-types/{id}', 'updateType'],
            ['DELETE', '/api/room-types/{id}', 'deleteType'],
            ['GET', '/api/rooms', 'rooms'],
            ['POST', '/api/rooms', 'createRoom'],
            ['PATCH', '/api/rooms/{id}/status', 'updateStatus'],
        ]);
        $this->routes($router, ReservationApiController::class, [
            ['GET', '/api/reservations', 'index'],
            ['POST', '/api/reservations', 'store'],
            ['GET', '/api/reservations/{id}', 'show'],
            ['POST', '/api/reservations/{id}/confirm', 'confirm'],
            ['POST', '/api/reservations/{id}/cancel', 'cancel'],
            ['POST', '/api/reservations/{id}/check-in', 'checkIn'],
            ['POST', '/api/reservations/{id}/check-out', 'checkOut'],
            ['POST', '/api/reservations/{id}/services', 'addService'],
            ['POST', '/api/reservations/{id}/payments', 'addPayment'],
            ['GET', '/api/reservations/{id}/invoice', 'invoice'],
        ]);
        $this->routes($router, DashboardApiController::class, [
            ['GET', '/api/dashboard/stats', 'stats'],
        ]);

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

    /**
     * @param class-string $controller
     * @param list<array{0: string, 1: string, 2: string}> $routes
     */
    private function routes(Router $router, string $controller, array $routes): void
    {
        foreach ($routes as [$method, $path, $action]) {
            $handler = function (string ...$parameters) use ($controller, $action): void {
                $this->controller($controller)->{$action}(...$parameters);
            };

            match ($method) {
                'GET' => $router->get($path, $handler),
                'POST' => $router->post($path, $handler),
                'PATCH' => $router->patch($path, $handler),
                'DELETE' => $router->delete($path, $handler),
            };
        }
    }

    private function availabilityController(): AvailabilityController
    {
        return new AvailabilityController($this->availabilityService());
    }

    private function roomController(): RoomController
    {
        return match ($class) {
            AuthController::class => new AuthController($this->authService(), $this->get(UserRepository::class)),
            RoomController::class => new RoomController(
                $this->roomManagementService(),
                $this->get(RoomRepository::class),
            ),
            RoomTypeController::class => new RoomTypeController(
                $this->roomManagementService(),
                $this->get(RoomTypeRepository::class),
            ),
            AvailabilityController::class => new AvailabilityController($this->availabilityService()),
            ReservationController::class => new ReservationController(
                $this->reservationService(),
                $this->invoiceService(),
                $this->get(RoomRepository::class),
                $this->get(GuestRepository::class),
                $this->get(ServiceRepository::class),
            ),
            ServiceController::class => new ServiceController(
                new ServiceCatalogService($this->get(ServiceRepository::class)),
                $this->get(ServiceRepository::class),
            ),
            DashboardController::class => new DashboardController($this->get(DashboardRepository::class)),
            AuthApiController::class => new AuthApiController($this->authService()),
            RoomApiController::class => new RoomApiController(
                $this->availabilityService(),
                $this->roomManagementService(),
                $this->get(RoomRepository::class),
                $this->get(RoomTypeRepository::class),
            ),
            ReservationApiController::class => new ReservationApiController(
                $this->reservationService(),
                $this->invoiceService(),
            ),
            DashboardApiController::class => new DashboardApiController($this->get(DashboardRepository::class)),
        };
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
        return $this->instances[AuthService::class] ??= new AuthService(
            $this->pdo(),
            $this->get(UserRepository::class),
            $this->get(GuestRepository::class),
        );
    }

    private function availabilityService(): AvailabilityService
    {
        return $this->availability ??= new AvailabilityService($this->availabilityRepository());
    }

    private function roomManagementService(): RoomManagementService
    {
        return $this->instances[RoomManagementService::class] ??= new RoomManagementService(
            $this->pdo(),
            $this->get(RoomRepository::class),
            $this->get(RoomTypeRepository::class),
        );
    }

    private function availabilityService(): AvailabilityService
    {
        return $this->instances[AvailabilityService::class] ??= new AvailabilityService($this->get(RoomRepository::class));
    }

    private function reservationService(): ReservationService
    {
        return $this->instances[ReservationService::class] ??= new ReservationService(
            $this->pdo(),
            $this->get(ReservationRepository::class),
            $this->get(RoomRepository::class),
            $this->get(GuestRepository::class),
            $this->get(ServiceRepository::class),
        );
    }

    private function availabilityRepository(): AvailabilityRepository
    {
        return $this->availabilityRepository ??= new AvailabilityRepository($this->pdo());
    }

    private function roomRepository(): RoomRepository
    {
        return $this->instances[InvoiceService::class] ??= new InvoiceService($this->get(ReservationRepository::class));
    }

    /**
     * Repository-t kanë vetëm PDO si varësi.
     *
     * @template T of object
     * @param class-string<T> $repository
     * @return T
     */
    private function get(string $repository): object
    {
        return $this->instances[$repository] ??= new $repository($this->pdo());
    }

    private function pdo(): PDO
    {
        return $this->pdo ??= Database::connect($this->databaseConfig);
    }
}
