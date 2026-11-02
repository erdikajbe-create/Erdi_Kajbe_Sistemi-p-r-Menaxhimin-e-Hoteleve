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
use HotelHub\Repositories\RoomTypeRepository;
use HotelHub\Services\RoomManagementService;

final readonly class RoomTypeController
{
    public function __construct(
        private RoomManagementService $rooms,
        private RoomTypeRepository $types,
    ) {
    }

    public function index(): void
    {
        Auth::requireRole('admin');
        Response::html(View::render('rooms/types', [
            'title' => 'Llojet e dhomave',
            'types' => $this->types->all(),
            'amenities' => $this->types->amenities(),
            'errors' => [],
            'old' => [],
            'formType' => null,
        ]));
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        try {
            $this->rooms->createType($_POST);
        } catch (ValidationException $exception) {
            $this->renderForm($exception->errors(), $_POST, 422);
        } catch (AppException $exception) {
            $this->renderForm(['name' => $exception->getMessage()], $_POST, $exception->getStatusCode());
        }
        Flash::add('success', 'Lloji i dhomës u krijua.');
        Response::redirect('/admin/room-types');
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        try {
            $this->rooms->updateType((int) $id, $_POST);
        } catch (ValidationException $exception) {
            $this->renderForm($exception->errors(), $_POST, 422);
        } catch (AppException $exception) {
            $this->renderForm(['name' => $exception->getMessage()], $_POST, $exception->getStatusCode());
        }
        Flash::add('success', 'Lloji i dhomës u përditësua.');
        Response::redirect('/admin/room-types');
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        $this->rooms->deleteType((int) $id);
        Flash::add('success', 'Lloji i dhomës u fshi.');
        Response::redirect('/admin/room-types');
    }

    public function createAmenity(): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        try {
            $this->rooms->createAmenity($_POST);
        } catch (ValidationException $exception) {
            $this->renderForm(['amenity_name' => $exception->errors()['name'] ?? 'Kontrolloni emrin e pajisjes.'], $_POST, 422);
        } catch (AppException $exception) {
            $this->renderForm(['amenity_name' => $exception->getMessage()], $_POST, $exception->getStatusCode());
        }
        Flash::add('success', 'Pajisja u shtua.');
        Response::redirect('/admin/room-types');
    }

    public function deleteAmenity(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        $this->rooms->deleteAmenity((int) $id);
        Flash::add('success', 'Pajisja u fshi.');
        Response::redirect('/admin/room-types');
    }

    public function updateAmenity(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);
        try {
            $this->rooms->updateAmenity((int) $id, $_POST);
        } catch (ValidationException $exception) {
            $this->renderForm(
                ['amenity_name_' . $id => $exception->errors()['name'] ?? 'Kontrolloni emrin e pajisjes.'],
                $_POST,
                422,
            );
        } catch (AppException $exception) {
            $this->renderForm(['amenity_name_' . $id => $exception->getMessage()], $_POST, $exception->getStatusCode());
        }
        Flash::add('success', 'Pajisja u përditësua.');
        Response::redirect('/admin/room-types');
    }

    private function renderForm(array $errors, array $input, int $status): never
    {
        Response::html(View::render('rooms/types', [
            'title' => 'Llojet e dhomave',
            'types' => $this->types->all(),
            'amenities' => $this->types->amenities(),
            'errors' => $errors,
            'old' => Input::safeValues($input),
            'formType' => Input::string($input, 'id') !== '' ? (int) Input::string($input, 'id') : null,
        ]), $status);
    }
}
