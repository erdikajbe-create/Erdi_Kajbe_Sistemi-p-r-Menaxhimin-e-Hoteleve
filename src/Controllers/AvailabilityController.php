<?php

declare(strict_types=1);

namespace HotelHub\Controllers;

use HotelHub\Core\Input;
use HotelHub\Core\Response;
use HotelHub\Core\View;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Services\AvailabilityService;

/**
 * Faqja publike e kërkimit. Funksionon pa JavaScript (forma GET rendërohet nga serveri);
 * availability.js e përmirëson me fetch drejt /api/availability.
 */
final readonly class AvailabilityController
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    public function index(): void
    {
        $query = Input::safeValues($_GET);
        $result = null;
        $errors = [];
        $today = date('Y-m-d');

        if (($query['check_in'] ?? '') !== '' || ($query['check_out'] ?? '') !== '') {
            try {
                $result = $this->availability->search($query, $today);
            } catch (ValidationException $exception) {
                $errors = $exception->errors();
            }
        }

        Response::html(View::render('availability/index', [
            'title' => 'Kërko dhoma të lira',
            'query' => $query + [
                'check_in' => $today,
                'check_out' => date('Y-m-d', strtotime('+1 day')),
                'guests' => '2',
            ],
            'today' => $today,
            'result' => $result,
            'errors' => $errors,
        ]), $errors === [] ? 200 : 422);
    }
}
