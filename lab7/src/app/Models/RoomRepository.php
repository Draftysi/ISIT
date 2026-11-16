<?php
declare(strict_types=1);

namespace App\Models;

final class RoomRepository
{
    private string $file;

    public function __construct()
    {
        $this->file = BASE_PATH . '/storage/rooms.json';
    }

    // Получить все переговорные комнаты.
    public function all(): array
    {
        return json_decode(
            file_get_contents($this->file),
            true
        ) ?? [];
    }

    // Найти переговорную комнату по ID.
    public function find(int $id): ?array
    {
        foreach ($this->all() as $room) {
            if ((int) $room['id'] === $id) {
                return $room;
            }
        }

        return null;
    }

    // Создать новую переговорную комнату.
    public function add(array $data): array
    {
        $rooms = $this->all();

        $ids = array_column($rooms, 'id');
        $newId = $ids ? max($ids) + 1 : 1;

        $room = ['id' => $newId] + $data;

        $rooms[] = $room;

        $this->save($rooms);

        return $room;
    }

    // Изменить существующую переговорную комнату.
    public function update(int $id, array $data): ?array
    {
        $rooms = $this->all();

        foreach ($rooms as $index => $room) {
            if ((int) $room['id'] === $id) {
                $rooms[$index] = ['id' => $id] + $data;

                $this->save($rooms);

                return $rooms[$index];
            }
        }

        return null;
    }

    // Удалить переговорную комнату.
    public function delete(int $id): bool
    {
        $rooms = $this->all();

        $filtered = array_values(array_filter(
            $rooms,
            fn(array $room): bool =>
                (int) $room['id'] !== $id
        ));

        if (count($filtered) === count($rooms)) {
            return false;
        }

        $this->save($filtered);

        return true;
    }

    // Сохранить данные в JSON-файл.
    private function save(array $rooms): void
    {
        $result = file_put_contents(
            $this->file,
            json_encode(
                $rooms,
                JSON_UNESCAPED_UNICODE |
                JSON_PRETTY_PRINT |
                JSON_THROW_ON_ERROR
            ),
            LOCK_EX
        );

        if ($result === false) {
            throw new \RuntimeException(
                'Не удалось сохранить данные переговорных комнат'
            );
        }
    }
}
