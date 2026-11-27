<?php
// Единая точка входа в приложение.
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

// Переход к клиентской части приложения.
if ($path === '') {
    header('Location: /client/');
    exit;
}

// Закрываем старые серверные страницы,
// чтобы через них нельзя было обойти авторизацию.
if (!str_starts_with($path, '/api/')) {
    json_error('Страница не найдена', 404);
}

// Вход в систему доступен без токена.
$isLogin = $method === 'POST' && $path === '/api/login';

// Проверка авторизации.
if (!$isLogin) {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer ([a-f0-9]{64})$/i', $header, $matches)) {
        json_error('Требуется авторизация', 401);
    }

    $user = \App\Core\Auth::user($matches[1]);

    if ($user === null) {
        json_error('Недействительный или просроченный токен', 401);
    }

    define('AUTH_USER', $user);

    // Управление пользователями доступно администратору.
    $usersRoute = $path === '/api/users' ||
        str_starts_with($path, '/api/users/');

    // Изменение комнат доступно администратору.
    $roomsRoute = $path === '/api/rooms' ||
        str_starts_with($path, '/api/rooms/');

    $adminRequired = $usersRoute ||
        ($roomsRoute && $method !== 'GET');

    if ($adminRequired && $user['role'] !== 'admin') {
        json_error('Недостаточно прав доступа', 403);
    }
}

// Подключение маршрутов.
$router = new App\Core\Router();

require BASE_PATH . '/routes/web.php';

$router->dispatch($method, $uri);