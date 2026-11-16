<?php
declare(strict_types=1);

namespace App\Models;

final class BookingRepository
{
    private string $file;

    public function __construct()
    {
        $this->file = BASE_PATH . '/storage/bookings.json';
    }

    // Получить все бронирования.
    public function all(): array
    {
        return json_decode(file_get_contents($this->file), true) ?? [];
    }

    // Найти бронирование по ID.
    public function find(int $id): ?array
    {
        foreach ($this->all() as $booking) {
            if ((int) $booking['id'] === $id) {
                return $booking;
            }
        }

        return null;
    }

    // Получить бронирования выбранной комнаты.
    public function byRoom(int $roomId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn(array $booking): bool =>
                (int) $booking['room_id'] === $roomId
        ));
    }

    // Создать новое бронирование.
    public function add(array $data): array
    {
        $bookings = $this->all();

        $ids = array_column($bookings, 'id');
        $newId = ($ids ? max($ids) : 0) + 1;

        $booking = ['id' => $newId] + $data;
        $bookings[] = $booking;

        $this->save($bookings);

        return $booking;
    }

    // Изменить существующее бронирование.
    public function update(int $id, array $data): ?array
    {
        $bookings = $this->all();

        foreach ($bookings as $index => $booking) {
            if ((int) $booking['id'] !== $id) {
                continue;
            }

            // Идентификатор изменять нельзя.
            unset($data['id']);

            $bookings[$index] = array_replace(
                $booking,
                $data
            );

            $this->save($bookings);

            return $bookings[$index];
        }

        return null;
    }

    // Удалить бронирование.
    public function delete(int $id): bool
    {
        $bookings = $this->all();

        foreach ($bookings as $index => $booking) {
            if ((int) $booking['id'] !== $id) {
                continue;
            }

            array_splice($bookings, $index, 1);

            $this->save($bookings);

            return true;
        }

        return false;
    }

    // Сохранить данные в JSON-файл.
    private function save(array $bookings): void
    {
        file_put_contents(
            $this->file,
            json_encode(
                $bookings,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ),
            LOCK_EX
        );
    }
}
