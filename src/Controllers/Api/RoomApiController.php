<?php

declare(strict_types=1);

namespace HotelHub\Controllers\Api;

use HotelHub\Core\Auth;
use HotelHub\Core\Csrf;
use HotelHub\Core\Input;
use HotelHub\Core\Request;
use HotelHub\Core\Response;
use HotelHub\Exceptions\AppException;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\RoomTypeRepository;
use HotelHub\Services\AvailabilityService;
use HotelHub\Services\RoomManagementService;

final readonly class RoomApiController
{
    public function __construct(
        private AvailabilityService $availability,
        private RoomManagementService $management,
        private RoomRepository $rooms,
        private RoomTypeRepository $types,
    ) {
    }

    /** GET /api/availability?check_in=&check_out=&guests= (publik) */
    public function availability(): void
    {
        Response::json(['success' => true, 'data' => $this->availability->search(Request::query(), date('Y-m-d'))]);
    }

    /** GET /api/room-types (të gjithë) */
    public function types(): void
    {
        Response::json(['success' => true, 'data' => $this->types->all()]);
    }

    public function createType(): void
    {
        Auth::requireRole('admin');
        Csrf::verifyRequest();
        $this->management->createType(Request::input());
        Response::json(['success' => true, 'message' => 'Lloji i dhomës u krijua.'], 201);
    }

    public function updateType(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verifyRequest();
        $type = $this->types->find((int) $id) ?? throw new AppException('Lloji i dhomës nuk u gjet.', 404);
        // PATCH: fushat që mungojnë mbajnë vlerën ekzistuese
        $current = [
            'name' => $type['name'],
            'description' => (string) $type['description'],
            'base_price' => $type['base_price'],
            'capacity' => (string) $type['capacity'],
            'amenities' => array_map('strval', array_column($type['amenities'], 'id')),
        ];
        $this->management->updateType((int) $id, Request::input() + $current);
        Response::json(['success' => true, 'data' => $this->types->find((int) $id)]);
    }

    public function deleteType(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::verifyRequest();
        $this->management->deleteType((int) $id);
        http_response_code(204);
        exit;
    }

    /** GET /api/rooms (stafi) */
    public function rooms(): void
    {
        Auth::requireRole('admin', 'receptionist');
        Response::json(['success' => true, 'data' => $this->rooms->all()]);
    }

    public function createRoom(): void
    {
        Auth::requireRole('admin');
        Csrf::verifyRequest();
        $this->management->createRoom(Request::input());
        Response::json(['success' => true, 'message' => 'Dhoma u shtua.'], 201);
    }

    public function updateStatus(string $id): void
    {
        Auth::requireRole('admin', 'receptionist');
        Csrf::verifyRequest();
        $this->management->changeRoomStatus((int) $id, Input::string(Request::input(), 'status'));
        Response::json(['success' => true, 'data' => $this->rooms->find((int) $id)]);
    }
}
