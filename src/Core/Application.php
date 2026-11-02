<?php

declare(strict_types=1);

namespace HotelHub\Core;

use HotelHub\Controllers\AuthController;
use HotelHub\Controllers\RoomController;
use HotelHub\Controllers\RoomTypeController;
use HotelHub\Repositories\GuestRepository;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\RoomTypeRepository;
use HotelHub\Repositories\UserRepository;
use HotelHub\Services\AuthService;
use HotelHub\Services\RoomManagementService;
use PDO;

final class Application
{
    private ?PDO $pdo = null;
    private ?UserRepository $users = null;
    private ?GuestRepository $guests = null;
    private ?RoomRepository $rooms = null;
    private ?RoomTypeRepository $roomTypes = null;
    private ?AuthService $auth = null;
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

        return $router;
    }

    private function authController(): AuthController
    {
        return new AuthController($this->authService(), $this->userRepository());
    }

    private function roomController(): RoomController
    {
        return new RoomController($this->roomManagementService(), $this->roomRepository());
    }

    private function roomTypeController(): RoomTypeController
    {
        return new RoomTypeController($this->roomManagementService(), $this->roomTypeRepository());
    }

    private function authService(): AuthService
    {
        return $this->auth ??= new AuthService($this->pdo(), $this->userRepository(), $this->guestRepository());
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
