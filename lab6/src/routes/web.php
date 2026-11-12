<?php

// Таблица маршрутов системы бронирования переговорных комнат.

use App\Controllers\HomeController;
use App\Controllers\BookingController;
use App\Controllers\RoomController;

// Главная страница.
$router->get('/', [HomeController::class, 'index']);

// Список бронирований.
$router->get('/bookings', [BookingController::class, 'index']);

// Форма создания нового бронирования.
$router->get('/bookings/create', [BookingController::class, 'create']);

// Сохранение нового бронирования.
$router->post('/bookings', [BookingController::class, 'store']);

// Просмотр отдельного бронирования.
$router->get('/bookings/{id}', [BookingController::class, 'show']);

// Список переговорных комнат.
$router->get('/rooms', [RoomController::class, 'index']);

// Просмотр отдельной переговорной комнаты.
$router->get('/rooms/{id}', [RoomController::class, 'show']);
