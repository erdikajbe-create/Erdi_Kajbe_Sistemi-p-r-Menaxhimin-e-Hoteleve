<?php

declare(strict_types=1);

namespace HotelHub\Services;

use HotelHub\Core\Input;
use HotelHub\Core\Money;
use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\ServiceRepository;
use PDOException;

/**
 * Admini menaxhon katalogun e shërbimeve. Ndryshimi i çmimit nuk prek faturat ekzistuese (RB09),
 * dhe një shërbim i përdorur nuk fshihet — çaktivizohet.
 */
final readonly class ServiceCatalogService
{
    public function __construct(private ServiceRepository $services)
    {
    }

    public function create(array $input): int
    {
        [$name, $price] = $this->validate($input);
        try {
            return $this->services->create($name, $price);
        } catch (PDOException $exception) {
            throw self::duplicate($exception);
        }
    }

    public function update(int $id, array $input): void
    {
        if ($this->services->find($id) === null) {
            throw new AppException('Shërbimi nuk u gjet.', 404);
        }
        [$name, $price] = $this->validate($input);
        $active = in_array(Input::string($input, 'active'), ['1', 'true', 'on'], true);
        try {
            $this->services->update($id, $name, $price, $active);
        } catch (PDOException $exception) {
            throw self::duplicate($exception);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function validate(array $input): array
    {
        $errors = [];
        $name = trim(Input::string($input, 'name'));
        $price = str_replace(',', '.', trim(Input::string($input, 'price')));
        if ($name === '' || mb_strlen($name) > 80) {
            $errors['name'] = 'Emri është i detyrueshëm (deri në 80 karaktere).';
        }
        if (!Money::isValid($price)) {
            $errors['price'] = 'Shkruani një çmim me deri në dy shifra dhjetore.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [$name, Money::normalize($price)];
    }

    private static function duplicate(PDOException $exception): \Throwable
    {
        return $exception->getCode() === '23000'
            ? new AppException('Ky shërbim ekziston tashmë.', 409, $exception)
            : $exception;
    }
}
