<?php

declare(strict_types=1);

namespace HotelHub\Controllers;

use HotelHub\Core\Response;
use HotelHub\Core\View;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Services\AvailabilityService;

final readonly class AvailabilityController
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    public function index(): void
    {
        Response::html(View::render('availability/index', [
            'title' => 'Kërko dhoma të lira',
            'today' => date('Y-m-d'),
        ]));
    }

    public function search(): never
    {
        try {
            Response::json(['data' => $this->availability->search($_GET)]);
        } catch (ValidationException $exception) {
            Response::json(['errors' => $exception->errors()], 422);
        }
    }
}
