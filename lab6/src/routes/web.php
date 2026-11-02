<?php

// Таблица маршрутов приложения.

use App\Controllers\HomeController;
use App\Controllers\ProjectController;
use App\Controllers\TaskController;

// Главная страница.
$router->get('/', [HomeController::class, 'index']);

// Список задач и создание новой задачи.
$router->get('/tasks', [TaskController::class, 'index']);
$router->get('/tasks/create', [TaskController::class, 'create']);
$router->post('/tasks', [TaskController::class, 'store']);

// Просмотр отдельной задачи.
$router->get('/tasks/{id}', [TaskController::class, 'show']);

// Список проектов и просмотр проекта.
$router->get('/projects', [ProjectController::class, 'index']);
$router->get('/projects/{id}', [ProjectController::class, 'show']);
