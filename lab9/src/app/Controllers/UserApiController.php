<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserRepository;
use PDOException;

final class UserApiController
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    // GET /api/users
    public function index(): void
    {
        json_response(['data' => $this->users->all()]);
    }

    // GET /api/users/{id}
    public function show(string $id): void
    {
        $user = $this->users->find($this->checkId($id));

        if ($user === null) {
            json_error('Пользователь не найден', 404);
        }

        json_response(['data' => $user]);
    }

    // POST /api/users
    public function store(): void
    {
        $input = json_input();
        $errors = $this->validate($input);

        $password = $input['password'] ?? null;

        if (
            !is_string($password) ||
            strlen($password) < 6 ||
            strlen($password) > 72 ||
            str_contains($password, "\0")
        ) {
            $errors['password'] =
                'Пароль должен содержать от 6 до 72 байт';
        }

        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        try {
            $user = $this->users->add([
                'name' => trim($input['name']),
                'email' => trim($input['email']),
                'password' => $password,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                json_error('Электронная почта уже используется', 409);
            }

            throw $e;
        }

        header('Location: /api/users/' . $user['id']);
        json_response(['data' => $user], 201);
    }

    // PUT/PATCH /api/users/{id}
    public function update(string $id): void
    {
        $userId = $this->checkId($id);
        $existing = $this->users->find($userId);

        if ($existing === null) {
            json_error('Пользователь не найден', 404);
        }

        $input = json_input();

        $data = array_replace(
            $existing,
            array_intersect_key(
                $input,
                array_flip(['name', 'email'])
            )
        );

        $errors = $this->validate($data);

        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        try {
            $user = $this->users->update($userId, [
                'name' => trim($data['name']),
                'email' => trim($data['email']),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                json_error('Электронная почта уже используется', 409);
            }

            throw $e;
        }

        json_response(['data' => $user]);
    }

    // DELETE /api/users/{id}
    public function destroy(string $id): void
    {
        $userId = $this->checkId($id);

        if (!$this->users->delete($userId)) {
            json_error('Пользователь не найден', 404);
        }

        no_content();
    }

    // Проверка ID.
    private function checkId(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            json_error('Некорректный ID пользователя', 400);
        }

        return (int) $id;
    }

    // Проверка данных пользователя.
    private function validate(array $data): array
    {
        $errors = [];

        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;

        if (
            !is_string($name) ||
            !preg_match('/^.{2,100}$/us', trim($name))
        ) {
            $errors['name'] =
                'Имя должно содержать от 2 до 100 символов';
        }

        if (
            !is_string($email) ||
            strlen(trim($email)) > 150 ||
            !filter_var(trim($email), FILTER_VALIDATE_EMAIL)
        ) {
            $errors['email'] =
                'Укажите корректный адрес электронной почты';
        }

        return $errors;
    }
}