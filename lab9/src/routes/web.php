<?php

// Маршруты системы бронирования переговорных комнат.

use App\Controllers\HomeController;
use App\Controllers\BookingController;
use App\Controllers\RoomController;

use App\Controllers\BookingApiController;
use App\Controllers\RoomApiController;
use App\Controllers\UserApiController;

// Обычные страницы сайта (лабораторная №6).

$router->get('/', [HomeController::class, 'index']);

$router->get('/bookings', [BookingController::class, 'index']);
$router->get('/bookings/create', [BookingController::class, 'create']);
$router->post('/bookings', [BookingController::class, 'store']);
$router->get('/bookings/{id}', [BookingController::class, 'show']);

$router->get('/rooms', [RoomController::class, 'index']);
$router->get('/rooms/{id}', [RoomController::class, 'show']);


// REST API (лабораторная №7).

// Бронирования.
$router->get('/api/bookings', [BookingApiController::class, 'index']);
$router->get('/api/bookings/{id}', [BookingApiController::class, 'show']);
$router->post('/api/bookings', [BookingApiController::class, 'store']);
$router->put('/api/bookings/{id}', [BookingApiController::class, 'update']);
$router->patch('/api/bookings/{id}', [BookingApiController::class, 'update']);
$router->delete('/api/bookings/{id}', [BookingApiController::class, 'destroy']);

// Переговорные комнаты.
$router->get('/api/rooms', [RoomApiController::class, 'index']);
$router->get('/api/rooms/{id}', [RoomApiController::class, 'show']);
$router->post('/api/rooms', [RoomApiController::class, 'store']);
$router->put('/api/rooms/{id}', [RoomApiController::class, 'update']);
$router->patch('/api/rooms/{id}', [RoomApiController::class, 'update']);
$router->delete('/api/rooms/{id}', [RoomApiController::class, 'destroy']);

// Пользователи.
$router->get('/api/users', [UserApiController::class, 'index']);
$router->get('/api/users/{id}', [UserApiController::class, 'show']);
$router->post('/api/users', [UserApiController::class, 'store']);
$router->put('/api/users/{id}', [UserApiController::class, 'update']);
$router->patch('/api/users/{id}', [UserApiController::class, 'update']);
$router->delete('/api/users/{id}', [UserApiController::class, 'destroy']);

// Авторизация (лабораторная №9).
$router->post('/api/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('/api/me', [\App\Controllers\AuthController::class, 'me']);
$router->post('/api/logout', [\App\Controllers\AuthController::class, 'logout']);
