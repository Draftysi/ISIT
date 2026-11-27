<?php
// Настройки приложения.

return [
    'name' => 'Бронирование переговорных комнат',
    'debug' => true,

    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: 5433,
        'dbname' => getenv('DB_NAME') ?: 'room_booking_lab9',
        'user' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: 'secret',
    ],

    'token_ttl' => 7200,
];