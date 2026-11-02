<?php
// Контроллер главной страницы.

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProjectRepository;
use App\Models\TaskRepository;

final class HomeController
{
    // GET /
    public function index(): void
    {
        // Получаем все бронирования и переговорные комнаты.
        $tasks = (new TaskRepository())->all();
        $projects = (new ProjectRepository())->all();

        // Подсчитываем бронирования по статусам.
        $byStatus = [
            'active' => 0,
            'done' => 0,
            'cancelled' => 0,
        ];

        foreach ($tasks as $task) {
            $status = $task['status'] ?? '';

            if (in_array($status, ['new', 'in_progress', 'active'], true)) {
                $byStatus['active']++;
            } elseif ($status === 'done') {
                $byStatus['done']++;
            } elseif ($status === 'cancelled') {
                $byStatus['cancelled']++;
            }
        }

        // Передаём данные в шаблон главной страницы.
        view('home', [
            'title' => 'Главная',
            'total' => count($tasks),
            'byStatus' => $byStatus,
            'projectsCount' => count($projects),
        ]);
    }
}
