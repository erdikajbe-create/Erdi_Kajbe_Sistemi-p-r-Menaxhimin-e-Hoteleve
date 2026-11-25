<?php

declare(strict_types=1);

namespace HotelHub\Controllers;

use HotelHub\Core\Auth;
use HotelHub\Core\Response;
use HotelHub\Core\View;
use HotelHub\Repositories\DashboardRepository;

/**
 * KF21, UC8: pasqyra e mëngjesit për menaxherin.
 */
final readonly class DashboardController
{
    public function __construct(private DashboardRepository $dashboard)
    {
    }

    public function index(): void
    {
        Auth::requireRole('admin');
        $today = date('Y-m-d');

        Response::html(View::render('dashboard/index', [
            'title' => 'Dashboard',
            'today' => $today,
            'stats' => $this->dashboard->stats($today, date('Y-m-01'), date('Y-m-01', strtotime('first day of next month'))),
            'arrivals' => $this->dashboard->arrivals($today),
            'departures' => $this->dashboard->departures($today),
            'revenue' => $this->dashboard->revenueByMonth(date('Y-m-01', strtotime('first day of -5 months'))),
        ]));
    }
}
