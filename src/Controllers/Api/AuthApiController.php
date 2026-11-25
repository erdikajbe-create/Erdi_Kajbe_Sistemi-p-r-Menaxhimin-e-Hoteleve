<?php

declare(strict_types=1);

namespace HotelHub\Controllers\Api;

use HotelHub\Core\Auth;
use HotelHub\Core\Csrf;
use HotelHub\Core\Input;
use HotelHub\Core\Request;
use HotelHub\Core\Response;
use HotelHub\Services\AuthService;

/**
 * Autentikimi me sesion edhe për /api. Klienti (Postman) merr fillimisht token-in CSRF
 * me GET /api/auth/csrf dhe e dërgon në header-in X-CSRF-Token për çdo POST/PATCH/DELETE.
 */
final readonly class AuthApiController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function csrf(): void
    {
        Response::json(['success' => true, 'data' => ['csrf_token' => Csrf::token()]]);
    }

    public function register(): void
    {
        Csrf::verifyRequest();
        $this->auth->registerGuest(Request::input());
        Response::json(['success' => true, 'message' => 'Llogaria u krijua.'], 201);
    }

    public function login(): void
    {
        Csrf::verifyRequest();
        $input = Request::input();
        $this->auth->authenticate(Input::string($input, 'email'), Input::string($input, 'password'), Request::ip());

        // sesioni u rigjenerua; token-i i ri i kthehet klientit
        Response::json(['success' => true, 'data' => ['user' => Auth::user(), 'csrf_token' => Csrf::token()]]);
    }

    public function logout(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();
        Auth::logout();
        http_response_code(204);
        exit;
    }

    public function me(): void
    {
        Response::json(['success' => true, 'data' => ['user' => Auth::requireLogin()]]);
    }
}
