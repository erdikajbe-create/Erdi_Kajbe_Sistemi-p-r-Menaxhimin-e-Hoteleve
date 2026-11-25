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
use HotelHub\Repositories\ServiceRepository;
use HotelHub\Services\ServiceCatalogService;

final readonly class ServiceController
{
    public function __construct(
        private ServiceCatalogService $catalog,
        private ServiceRepository $services,
    ) {
    }

    public function index(): void
    {
        Auth::requireRole('admin');
        $this->render([], [], null, 200);
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        Csrf::verifyRequest();
        try {
            $this->catalog->create($_POST);
        } catch (ValidationException $exception) {
            $this->render($exception->errors(), Input::safeValues($_POST), null, 422);
        } catch (AppException $exception) {
            $this->render(['name' => $exception->getMessage()], Input::safeValues($_POST), null, $exception->getStatusCode());
        }
        Flash::add('success', 'Shërbimi u shtua.');
        Response::redirect('/admin/services');
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verifyRequest();
        try {
            $this->catalog->update((int) $id, $_POST);
        } catch (ValidationException $exception) {
            $this->render($exception->errors(), Input::safeValues($_POST), (int) $id, 422);
        } catch (AppException $exception) {
            if ($exception->getStatusCode() === 404) {
                throw $exception;
            }
            $this->render(['name' => $exception->getMessage()], Input::safeValues($_POST), (int) $id, $exception->getStatusCode());
        }
        Flash::add('success', 'Shërbimi u përditësua. Faturat ekzistuese mbajnë çmimin e vjetër.');
        Response::redirect('/admin/services');
    }

    private function render(array $errors, array $old, ?int $editing, int $status): never
    {
        Response::html(View::render('services/index', [
            'title' => 'Shërbimet shtesë',
            'services' => $this->services->all(),
            'errors' => $errors,
            'old' => $old,
            'editing' => $editing,
            'scripts' => ['/js/collection-search.js'],
        ]), $status);
    }
}
