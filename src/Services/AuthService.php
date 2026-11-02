<?php

declare(strict_types=1);

namespace HotelHub\Services;

use HotelHub\Core\Auth;
use HotelHub\Core\Input;
use HotelHub\Enums\UserRole;
use HotelHub\Exceptions\AppException;
use HotelHub\Exceptions\ValidationException;
use HotelHub\Repositories\GuestRepository;
use HotelHub\Repositories\UserRepository;
use PDO;
use PDOException;

final readonly class AuthService
{
    public function __construct(
        private PDO $pdo,
        private UserRepository $users,
        private GuestRepository $guests,
    ) {
    }

    public function registerGuest(array $input): void
    {
        $validated = $this->validateUser($input);
        $nameParts = preg_split('/\s+/', trim($validated['name']), 2);

        if ($nameParts === false || count($nameParts) < 2) {
            throw new ValidationException(['name' => 'Shkruani emrin dhe mbiemrin.']);
        }

        $this->pdo->beginTransaction();
        try {
            $userId = $this->users->create(
                $validated['name'],
                strtolower($validated['email']),
                password_hash($validated['password'], PASSWORD_DEFAULT),
                UserRole::Guest->value,
            );
            $this->guests->createForUser(
                $userId,
                $nameParts[0],
                $nameParts[1],
                strtolower($validated['email']),
            );
            $this->pdo->commit();
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception->getCode() === '23000') {
                throw new AppException('Kjo adresë email është regjistruar tashmë.', 409, $exception);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function createReceptionist(array $input): void
    {
        $validated = $this->validateUser($input);

        try {
            $this->users->create(
                $validated['name'],
                strtolower($validated['email']),
                password_hash($validated['password'], PASSWORD_DEFAULT),
                UserRole::Receptionist->value,
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new AppException('Kjo adresë email është regjistruar tashmë.', 409, $exception);
            }

            throw $exception;
        }
    }

    public function authenticate(string $email, string $password, string $ip): void
    {
        $this->removeExpiredAttempts();

        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts '
            . 'WHERE ip_address = :ip AND attempted_at > CURRENT_TIMESTAMP - INTERVAL 1 MINUTE',
        );
        $count->execute(['ip' => $ip]);

        if ((int) $count->fetchColumn() >= 5) {
            throw new AppException('Keni bërë shumë tentativa. Provoni përsëri pas një minute.', 429);
        }

        $user = $this->users->findByEmail(strtolower(trim($email)));
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            $attempt = $this->pdo->prepare(
                'INSERT INTO login_attempts (ip_address, email) VALUES (:ip, :email)',
            );
            $attempt->execute([
                'ip' => $ip,
                'email' => strtolower(trim($email)),
            ]);

            throw new AppException('Email ose fjalëkalim i pasaktë.', 422);
        }

        $clear = $this->pdo->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
        $clear->execute(['ip' => $ip]);

        Auth::login([
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]);
    }

    private function removeExpiredAttempts(): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM login_attempts WHERE attempted_at < CURRENT_TIMESTAMP - INTERVAL 1 DAY',
        );
        $statement->execute();
    }

    /**
     * @return array{name: string, email: string, password: string}
     */
    private function validateUser(array $input): array
    {
        $errors = [];
        $name = trim(Input::string($input, 'name'));
        $email = strtolower(trim(Input::string($input, 'email')));
        $password = Input::string($input, 'password');
        $confirmation = Input::string($input, 'password_confirmation');

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = $name === '' ? 'Shkruani emrin dhe mbiemrin.' : 'Emri nuk mund të kalojë 100 karaktere.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 150) {
            $errors['email'] = 'Shkruani një adresë email të vlefshme.';
        }
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Fjalëkalimi duhet të ketë të paktën 8 karaktere.';
        }
        if ($password !== $confirmation) {
            $errors['password_confirmation'] = 'Fjalëkalimet nuk përputhen.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['name' => $name, 'email' => $email, 'password' => $password];
    }
}
