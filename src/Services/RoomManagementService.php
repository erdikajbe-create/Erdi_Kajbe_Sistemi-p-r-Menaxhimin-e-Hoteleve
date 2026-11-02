<?php

declare(strict_types=1);

namespace HotelHub\Services;

use HotelHub\Core\Input;
use HotelHub\Enums\RoomStatus;
use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\RoomRepository;
use HotelHub\Repositories\RoomTypeRepository;
use PDO;
use PDOException;

final readonly class RoomManagementService
{
    public function __construct(
        private PDO $pdo,
        private RoomRepository $rooms,
        private RoomTypeRepository $types,
    ) {
    }

    public function createType(array $input): void
    {
        $data = $this->validateType($input);
        $amenityIds = $this->amenityIds($input);
        $this->pdo->beginTransaction();
        try {
            $id = $this->types->create(
                $data['name'],
                $data['description'] ?: null,
                $data['base_price'],
                (int) $data['capacity'],
            );
            $this->types->setAmenities($id, $amenityIds);
            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->rollback();
            $this->translateConstraint($exception, 'Ky emër i llojit të dhomës ekziston tashmë.');
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function updateType(int $id, array $input): void
    {
        $this->requireType($id);
        $data = $this->validateType($input);
        $amenityIds = $this->amenityIds($input);
        $this->pdo->beginTransaction();
        try {
            $this->types->update(
                $id,
                $data['name'],
                $data['description'] ?: null,
                $data['base_price'],
                (int) $data['capacity'],
            );
            $this->types->setAmenities($id, $amenityIds);
            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->rollback();
            $this->translateConstraint($exception, 'Ky emër i llojit të dhomës ekziston tashmë.');
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function deleteType(int $id): void
    {
        $this->requireType($id);

        try {
            $this->types->delete($id);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new AppException(
                    'Ky lloj dhome ka dhoma ose rezervime. Caktoni dhomat në maintenance në vend që ta fshini.',
                    409,
                    $exception,
                );
            }

            throw $exception;
        }
    }

    public function createRoom(array $input): void
    {
        $data = $this->validateRoom($input);
        try {
            $this->rooms->create((int) $data['room_type_id'], $data['number'], (int) $data['floor']);
        } catch (PDOException $exception) {
            $this->translateConstraint($exception, 'Numri i dhomës ekziston ose lloji nuk është i vlefshëm.');
        }
    }

    public function updateRoom(int $id, array $input): void
    {
        $this->requireRoom($id);
        $data = $this->validateRoom($input);
        try {
            $this->rooms->update(
                $id,
                (int) $data['room_type_id'],
                $data['number'],
                (int) $data['floor'],
            );
        } catch (PDOException $exception) {
            $this->translateConstraint($exception, 'Numri i dhomës ekziston ose lloji nuk është i vlefshëm.');
        }
    }

    public function deleteRoom(int $id): void
    {
        $this->requireRoom($id);
        if ($this->rooms->hasReservations($id)) {
            throw new AppException(
                'Kjo dhomë ka rezervime. Ndryshoni statusin në maintenance në vend që ta fshini.',
                409,
            );
        }

        try {
            $this->rooms->delete($id);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new AppException('Dhoma nuk mund të fshihet për shkak të të dhënave të lidhura.', 409, $exception);
            }

            throw $exception;
        }
    }

    public function changeRoomStatus(int $id, string $status): void
    {
        $this->requireRoom($id);
        if (RoomStatus::tryFrom($status) === null) {
            throw new ValidationException(['status' => 'Zgjidhni një status të vlefshëm.']);
        }

        $this->rooms->updateStatus($id, $status);
    }

    public function createAmenity(array $input): void
    {
        $name = trim(Input::string($input, 'name'));
        if ($name === '' || mb_strlen($name) > 80) {
            throw new ValidationException(['name' => 'Shkruani emrin e pajisjes (deri në 80 karaktere).']);
        }
        try {
            $this->types->createAmenity($name);
        } catch (PDOException $exception) {
            $this->translateConstraint($exception, 'Kjo pajisje ekziston tashmë.');
        }
    }

    public function deleteAmenity(int $id): void
    {
        try {
            $this->types->deleteAmenity($id);
        } catch (PDOException $exception) {
            throw new AppException('Pajisja nuk mund të fshihet.', 409, $exception);
        }
    }

    public function updateAmenity(int $id, array $input): void
    {
        $name = trim(Input::string($input, 'name'));
        if ($name === '' || mb_strlen($name) > 80) {
            throw new ValidationException(['name' => 'Shkruani emrin e pajisjes (deri në 80 karaktere).']);
        }

        try {
            $this->types->updateAmenity($id, $name);
        } catch (PDOException $exception) {
            $this->translateConstraint($exception, 'Kjo pajisje ekziston tashmë.');
        }
    }

    private function requireType(int $id): array
    {
        $type = $this->types->find($id);
        if ($type === null) {
            throw new AppException('Lloji i dhomës nuk u gjet.', 404);
        }

        return $type;
    }

    private function requireRoom(int $id): array
    {
        $room = $this->rooms->find($id);
        if ($room === null) {
            throw new AppException('Dhoma nuk u gjet.', 404);
        }

        return $room;
    }

    /**
     * @return array{name: string, description: string, base_price: string, capacity: string}
     */
    private function validateType(array $input): array
    {
        $errors = [];
        $name = trim(Input::string($input, 'name'));
        $description = trim(Input::string($input, 'description'));
        $price = trim(Input::string($input, 'base_price'));
        $capacity = trim(Input::string($input, 'capacity'));

        if ($name === '' || mb_strlen($name) > 80) {
            $errors['name'] = 'Emri është i detyrueshëm (deri në 80 karaktere).';
        }
        if ($description !== '' && mb_strlen($description) > 5000) {
            $errors['description'] = 'Përshkrimi është shumë i gjatë.';
        }
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price) || self::isZeroAmount($price)) {
            $errors['base_price'] = 'Shkruani një çmim pozitiv me deri në dy shifra dhjetore.';
        }
        if (filter_var($capacity, FILTER_VALIDATE_INT) === false || (int) $capacity < 1 || (int) $capacity > 6) {
            $errors['capacity'] = 'Kapaciteti duhet të jetë nga 1 deri në 6.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['name' => $name, 'description' => $description, 'base_price' => $price, 'capacity' => $capacity];
    }

    /**
     * @return array{room_type_id: string, number: string, floor: string}
     */
    private function validateRoom(array $input): array
    {
        $errors = [];
        $typeId = trim(Input::string($input, 'room_type_id'));
        $number = trim(Input::string($input, 'number'));
        $floor = trim(Input::string($input, 'floor'));

        if (filter_var($typeId, FILTER_VALIDATE_INT) === false || (int) $typeId < 1) {
            $errors['room_type_id'] = 'Zgjidhni llojin e dhomës.';
        }
        if ($number === '' || mb_strlen($number) > 10) {
            $errors['number'] = 'Numri i dhomës është i detyrueshëm (deri në 10 karaktere).';
        }
        if (filter_var($floor, FILTER_VALIDATE_INT) === false || (int) $floor < -128 || (int) $floor > 127) {
            $errors['floor'] = 'Kati duhet të jetë numër i plotë nga -128 deri në 127.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['room_type_id' => $typeId, 'number' => $number, 'floor' => $floor];
    }

    private function amenityIds(array $input): array
    {
        $ids = $input['amenities'] ?? [];
        if (!is_array($ids)) {
            throw new ValidationException(['amenities' => 'Përzgjedhja e pajisjeve nuk është e vlefshme.']);
        }

        foreach ($ids as $id) {
            if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
                throw new ValidationException(['amenities' => 'Përzgjedhja e pajisjeve nuk është e vlefshme.']);
            }
        }

        return array_map('intval', $ids);
    }

    private static function isZeroAmount(string $amount): bool
    {
        return trim(str_replace('.', '', $amount), '0') === '';
    }

    private function translateConstraint(PDOException $exception, string $message): never
    {
        if ($exception->getCode() === '23000') {
            throw new AppException($message, 409, $exception);
        }

        throw $exception;
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
