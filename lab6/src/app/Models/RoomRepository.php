<?php
declare(strict_types=1);

namespace App\Models;

// Модель переговорных комнат.
final class RoomRepository
{
    private string $file;

    public function __construct()
    {
        $this->file = BASE_PATH . '/storage/rooms.json';
    }

    // Получить список всех переговорных комнат.
    public function all(): array
    {
        return json_decode(file_get_contents($this->file), true) ?? [];
    }

    // Найти переговорную комнату по номеру записи.
    public function find(int $id): ?array
    {
        foreach ($this->all() as $room) {
            if ((int) $room['id'] === $id) {
                return $room;
            }
        }

        return null;
    }
}
