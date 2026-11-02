<?php
declare(strict_types=1);

namespace App\Models;

final class TaskRepository
{
    private string $file;

    public function __construct()
    {
        $this->file = BASE_PATH . '/storage/tasks.json';
    }

    // Получить все бронирования.
    public function all(): array
    {
        return json_decode(file_get_contents($this->file), true) ?? [];
    }

    // Найти бронирование по номеру.
    public function find(int $id): ?array
    {
        foreach ($this->all() as $task) {
            if ((int) $task['id'] === $id) {
                return $task;
            }
        }

        return null;
    }

    // Получить бронирования выбранной переговорной.
    public function byProject(int $projectId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn(array $task): bool =>
                (int) $task['project_id'] === $projectId
        ));
    }

    // Создать бронирование и сохранить его в JSON.
    public function add(array $data): array
    {
        $tasks = $this->all();

        $ids = array_column($tasks, 'id');
        $newId = ($ids ? max($ids) : 0) + 1;

        $task = ['id' => $newId] + $data;
        $tasks[] = $task;

        file_put_contents(
            $this->file,
            json_encode(
                $tasks,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ),
            LOCK_EX
        );

        return $task;
    }
}
