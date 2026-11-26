<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\BookingRepository;
use App\Models\RoomRepository;

final class HomeController
{
    // GET /
    public function index(): void
    {
        // Получаем бронирования и переговорные комнаты.
        $bookings = (new BookingRepository())->all();
        $rooms = (new RoomRepository())->all();

        // Подсчитываем бронирования по статусам.
        $byStatus = [
            'active' => 0,
            'done' => 0,
            'cancelled' => 0,
        ];

        foreach ($bookings as $booking) {
            $status = $booking['status'] ?? '';

            if ($status === 'active') {
                $byStatus['active']++;
            } elseif ($status === 'done' || $status === 'completed') {
                $byStatus['done']++;
            } elseif ($status === 'cancelled') {
                $byStatus['cancelled']++;
            }
        }

        // Передаём данные в шаблон главной страницы.
        view('home', [
            'title' => 'Главная',
            'total' => count($bookings),
            'byStatus' => $byStatus,
            'roomsCount' => count($rooms),

            // Временно сохраняем старое имя переменной,
            // пока не обновим шаблон home.php.
            'projectsCount' => count($rooms),
        ]);
    }
}
