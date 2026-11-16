<?php
// Единая точка входа в приложение.
declare(strict_types=1);

// TODO 1.1. Пути к каталогам проекта.
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Подключаем автозагрузку классов и вспомогательные функции.
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

// TODO 1.2. Создаём маршрутизатор и подключаем маршруты.
$router = new App\Core\Router();

require BASE_PATH . '/routes/web.php';

// Передаём запрос маршрутизатору.
$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI']
);