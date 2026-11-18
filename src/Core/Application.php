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
use HotelHub\Controllers\ReservationController;
use HotelHub\Controllers\RoomController;
use HotelHub\Controllers\RoomTypeController;
use HotelHub\Controllers\ServiceController;
use HotelHub\Repositories\DashboardRepository;
use HotelHub\Repositories\GuestRepository;
use HotelHub\Repositories\ReservationRepository;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\RoomTypeRepository;
use HotelHub\Repositories\ServiceRepository;
use HotelHub\Repositories\UserRepository;
use HotelHub\Services\AuthService;
use HotelHub\Services\AvailabilityService;
use HotelHub\Services\InvoiceService;
use HotelHub\Services\ReservationService;
use HotelHub\Services\RoomManagementService;
use HotelHub\Services\ServiceCatalogService;
use PDO;

/**
 * Lidh rrugët me controller-ët dhe ndërton objektet (një container i vogël manual).
 * Objektet krijohen vetëm kur nevojiten, që faqet pa databazë të mos hapin lidhje.
 */
final class Application
{
    private ?PDO $pdo = null;

    /** @var array<class-string, object> */
    private array $instances = [];

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

    private function controller(string $class): object
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

    private function authService(): AuthService
    {
        return $this->instances[AuthService::class] ??= new AuthService(
            $this->pdo(),
            $this->get(UserRepository::class),
            $this->get(GuestRepository::class),
        );
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

    private function invoiceService(): InvoiceService
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
