<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\UserRepository;

final class AuthController
{
    // POST /api/login
    public function login(): void
    {
        $input = json_input();

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (
            !is_string($email) ||
            !is_string($password) ||
            trim($email) === '' ||
            $password === ''
        ) {
            $this->logLogin($email, 'invalid_input');
            json_error('Укажите почту и пароль', 422);
        }

        $users = new UserRepository();
        $user = $users->findByEmail(trim($email));

        if (
            $user === null ||
            !password_verify($password, $user['password_hash'])
        ) {
            $this->logLogin($email, 'failed');
            json_error('Неверная почта или пароль', 401);
        }

        $token = Auth::issue((int) $user['id']);

        $this->logLogin($email, 'success');

        unset($user['password_hash']);

        json_response([
            'token' => $token,
            'user' => $user,
        ]);
    }

    // GET /api/me
    public function me(): void
    {
        $token = $this->getToken();
        $user = Auth::user($token);

        if ($user === null) {
            json_error('Требуется авторизация', 401);
        }

        json_response(['data' => $user]);
    }

    // POST /api/logout
    public function logout(): void
    {
        $token = $this->getToken();

        if (Auth::user($token) === null) {
            json_error('Требуется авторизация', 401);
        }

        Auth::revoke($token);

        no_content();
    }

    // Запись попытки входа в журнал.
    private function logLogin(mixed $email, string $result): void
    {
        $entry = [
            'time' => date(DATE_ATOM),
            'email' => is_string($email) ? trim($email) : '',
            'result' => $result,
        ];

        $line = json_encode(
            $entry,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if (file_put_contents(
            BASE_PATH . '/storage/login.log',
            $line . PHP_EOL,
            FILE_APPEND | LOCK_EX
        ) === false) {
            error_log('Cannot write login journal');
        }
    }

    // Получить токен из заголовка запроса.
    private function getToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer ([a-f0-9]{64})$/i', $header, $matches)) {
            json_error('Требуется авторизация', 401);
        }

        return $matches[1];
    }
}