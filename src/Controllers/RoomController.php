<?php

declare(strict_types=1);

namespace HotelHub\Controllers;

use HotelHub\Core\Auth;
use HotelHub\Core\Csrf;
use HotelHub\Core\Flash;
use HotelHub\Core\Input;
use HotelHub\Core\Response;
use HotelHub\Core\View;
use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Services\RoomManagementService;

final readonly class RoomController
{
    public function __construct(
        private RoomManagementService $management,
        private RoomRepository $rooms,
    ) {
    }

    public function index(): void
    {
        Auth::requireRole('admin', 'receptionist');
        Response::html(View::render('rooms/index', [
            'title' => 'Dhomat',
            'rooms' => $this->rooms->all(),
            'types' => $this->rooms->roomTypes(),
            'errors' => [],
            'old' => [],
            'isAdmin' => Auth::user()['role'] === 'admin',
        ]));
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        try {
            $this->management->createRoom($_POST);
        } catch (ValidationException $exception) {
            $this->renderForm($exception->errors(), $_POST, 422);
        } catch (AppException $exception) {
            $this->renderForm(['number' => $exception->getMessage()], $_POST, $exception->getStatusCode());
        }
        Flash::add('success', 'Dhoma u shtua.');
        Response::redirect('/rooms');
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        try {
            $this->management->updateRoom((int) $id, $_POST);
        } catch (ValidationException $exception) {
            $input = $_POST;
            $input['id'] = $id;
            $this->renderForm($exception->errors(), $input, 422);
        } catch (AppException $exception) {
            $input = $_POST;
            $input['id'] = $id;
            $this->renderForm(['number' => $exception->getMessage()], $input, $exception->getStatusCode());
        }
        Flash::add('success', 'Dhoma u përditësua.');
        Response::redirect('/rooms');
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        $this->management->deleteRoom((int) $id);
        Flash::add('success', 'Dhoma u fshi.');
        Response::redirect('/rooms');
    }

    public function updateStatus(string $id): void
    {
        Auth::requireRole('admin', 'receptionist');
        Csrf::verify($_POST['_csrf'] ?? null);
        $this->management->changeRoomStatus((int) $id, Input::string($_POST, 'status'));
        Flash::add('success', 'Statusi i dhomës u ndryshua.');
        Response::redirect('/rooms');
    }

    private function renderForm(array $errors, array $input, int $status): never
    {
        Response::html(View::render('rooms/index', [
            'title' => 'Dhomat',
            'rooms' => $this->rooms->all(),
            'types' => $this->rooms->roomTypes(),
            'errors' => $errors,
            'old' => \HotelHub\Core\Input::safeValues($input),
            'isAdmin' => true,
        ]), $status);
    }
}
