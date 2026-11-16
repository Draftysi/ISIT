<?php
declare(strict_types=1);

namespace App\Controllers;

final class UserApiController
{
    private string $file;

    public function __construct()
    {
        $this->file = BASE_PATH . '/storage/users.json';

        if (!file_exists($this->file)) {
            file_put_contents($this->file, '[]');
        }
    }

    // GET /api/users
    public function index(): void
    {
        json_response(['data' => $this->all()]);
    }

    // GET /api/users/{id}
    public function show(string $id): void
    {
        $user = $this->find($this->checkId($id));

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

        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $users = $this->all();
        $ids = array_column($users, 'id');

        $user = [
            'id' => $ids ? max($ids) + 1 : 1,
            'name' => trim($input['name']),
            'email' => trim($input['email'])
        ];

        $users[] = $user;
        $this->save($users);

        header('Location: /api/users/' . $user['id']);
        json_response(['data' => $user], 201);
    }

    // PUT/PATCH /api/users/{id}
    public function update(string $id): void
    {
        $userId = $this->checkId($id);
        $users = $this->all();

        foreach ($users as $index => $user) {
            if ((int) $user['id'] !== $userId) {
                continue;
            }

            $input = json_input();

            $updated = [
                'id' => $userId,
                'name' => array_key_exists('name', $input)
                    ? $input['name']
                    : $user['name'],
                'email' => array_key_exists('email', $input)
                    ? $input['email']
                    : $user['email']
            ];

            $errors = $this->validate($updated);

            if ($errors !== []) {
                json_error('Ошибка проверки данных', 422, $errors);
            }

            $updated['name'] = trim($updated['name']);
            $updated['email'] = trim($updated['email']);

            $users[$index] = $updated;
            $this->save($users);

            json_response(['data' => $updated]);
            return;
        }

        json_error('Пользователь не найден', 404);
    }

    // DELETE /api/users/{id}
    public function destroy(string $id): void
    {
        $userId = $this->checkId($id);
        $users = $this->all();

        $filtered = array_values(array_filter(
            $users,
            fn(array $user): bool =>
                (int) $user['id'] !== $userId
        ));

        if (count($filtered) === count($users)) {
            json_error('Пользователь не найден', 404);
        }

        $this->save($filtered);
        no_content();
    }

    // Получение всех пользователей.
    private function all(): array
    {
        $content = file_get_contents($this->file);

        return json_decode($content ?: '[]', true) ?? [];
    }

    // Поиск пользователя по ID.
    private function find(int $id): ?array
    {
        foreach ($this->all() as $user) {
            if ((int) $user['id'] === $id) {
                return $user;
            }
        }

        return null;
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
            mb_strlen(trim($name)) < 2
        ) {
            $errors['name'] =
                'Имя должно содержать не менее 2 символов';
        }

        if (
            !is_string($email) ||
            !filter_var(trim($email), FILTER_VALIDATE_EMAIL)
        ) {
            $errors['email'] =
                'Укажите корректный адрес электронной почты';
        }

        return $errors;
    }

    // Сохранение данных.
    private function save(array $users): void
    {
        file_put_contents(
            $this->file,
            json_encode(
                $users,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ),
            LOCK_EX
        );
    }
}
