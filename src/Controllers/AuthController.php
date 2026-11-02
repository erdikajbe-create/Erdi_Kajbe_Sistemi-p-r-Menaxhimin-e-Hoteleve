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
use HotelHub\Repositories\UserRepository;
use HotelHub\Services\AuthService;

final readonly class AuthController
{
    public function __construct(
        private AuthService $auth,
        private UserRepository $users,
    ) {
    }

    public function registerForm(): void
    {
        Response::html(View::render('auth/register', [
            'title' => 'Regjistrimi',
            'errors' => [],
            'old' => [],
        ]));
    }

    public function register(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        try {
            $this->auth->registerGuest($_POST);
        } catch (ValidationException $exception) {
            Response::html(View::render('auth/register', [
                'title' => 'Regjistrimi',
                'errors' => $exception->errors(),
                'old' => Input::safeValues($_POST),
            ]), 422);
        } catch (AppException $exception) {
            Response::html(View::render('auth/register', [
                'title' => 'Regjistrimi',
                'errors' => ['email' => $exception->getMessage()],
                'old' => Input::safeValues($_POST),
            ]), $exception->getStatusCode());
        }

        Flash::add('success', 'Llogaria u krijua me sukses. Tani mund të hyni.');
        Response::redirect('/login');
    }

    public function loginForm(): void
    {
        Response::html(View::render('auth/login', [
            'title' => 'Hyrja',
            'error' => null,
            'old' => [],
        ]));
    }

    public function login(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);

        try {
            $this->auth->authenticate(
                Input::string($_POST, 'email'),
                Input::string($_POST, 'password'),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            );
        } catch (AppException $exception) {
            Response::html(View::render('auth/login', [
                'title' => 'Hyrja',
                'error' => $exception->getMessage(),
                'old' => Input::safeValues($_POST),
            ]), $exception->getStatusCode());
        }

        $role = Auth::user()['role'];
        Response::redirect($role === 'guest' ? '/' : '/rooms');
    }

    public function logout(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        Auth::requireLogin();
        Auth::logout();
        Response::redirect('/');
    }

    public function staffIndex(): void
    {
        Auth::requireRole('admin');
        Response::html(View::render('auth/staff', [
            'title' => 'Menaxhimi i stafit',
            'staff' => $this->users->listStaff(),
            'errors' => [],
            'old' => [],
        ]));
    }

    public function createStaff(): void
    {
        Auth::requireRole('admin');
        Csrf::verify($_POST['_csrf'] ?? null);

        try {
            $this->auth->createReceptionist($_POST);
        } catch (ValidationException $exception) {
            $this->renderStaffForm($exception->errors(), $_POST, 422);
        } catch (AppException $exception) {
            $this->renderStaffForm(['email' => $exception->getMessage()], $_POST, $exception->getStatusCode());
        }

        Flash::add('success', 'Llogaria e recepsionistit u krijua.');
        Response::redirect('/admin/staff');
    }

    private function renderStaffForm(array $errors, array $input, int $status): never
    {
        Response::html(View::render('auth/staff', [
            'title' => 'Menaxhimi i stafit',
            'staff' => $this->users->listStaff(),
            'errors' => $errors,
            'old' => Input::safeValues($input),
        ]), $status);
    }
}
