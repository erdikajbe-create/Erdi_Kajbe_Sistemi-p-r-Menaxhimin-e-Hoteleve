<?php

declare(strict_types=1);

namespace HotelHub\Controllers\Api;

use HotelHub\Core\Auth;
use HotelHub\Core\Response;
use HotelHub\Repositories\DashboardRepository;

final readonly class DashboardApiController
{
    public function __construct(private DashboardRepository $dashboard)
    {
    }

    public function stats(): void
    {
        Auth::requireRole('admin');
        $today = date('Y-m-d');
        Response::json(['success' => true, 'data' => [
            'date' => $today,
            'stats' => $this->dashboard->stats($today, date('Y-m-01'), date('Y-m-01', strtotime('first day of next month'))),
            'arrivals' => $this->dashboard->arrivals($today),
            'departures' => $this->dashboard->departures($today),
        ]]);
    }
}
