<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RoomRepository;
use App\Models\BookingRepository;

final class RoomApiController
{
    private RoomRepository $rooms;
    private BookingRepository $bookings;

    public function __construct()
    {
        $this->rooms = new RoomRepository();
        $this->bookings = new BookingRepository();
    }

    // GET /api/rooms
    public function index(): void
    {
        json_response([
            'data' => $this->rooms->all()
        ]);
    }

    // GET /api/rooms/{id}
    public function show(string $id): void
    {
        $roomId = $this->checkId($id);
        $room = $this->rooms->find($roomId);

        if ($room === null) {
            json_error('Переговорная комната не найдена', 404);
        }

        json_response(['data' => $room]);
    }

    // POST /api/rooms
    public function store(): void
    {
        $input = json_input();
        $errors = $this->validate($input);

        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $room = $this->rooms->add(
            $this->normalize($input)
        );

        header('Location: /api/rooms/' . $room['id']);

        json_response(['data' => $room], 201);
    }

    // PUT /api/rooms/{id}
    // PATCH /api/rooms/{id}
    public function update(string $id): void
    {
        $roomId = $this->checkId($id);
        $existing = $this->rooms->find($roomId);

        if ($existing === null) {
            json_error('Переговорная комната не найдена', 404);
        }

        $input = json_input();

        $fields = ['name', 'capacity', 'equipment'];

        $data = array_intersect_key(
            $existing,
            array_flip($fields)
        );

        foreach ($fields as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }

        $errors = $this->validate($data);

        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $room = $this->rooms->update(
            $roomId,
            $this->normalize($data)
        );

        json_response(['data' => $room]);
    }

    // DELETE /api/rooms/{id}
    public function destroy(string $id): void
    {
        $roomId = $this->checkId($id);

        if ($this->rooms->find($roomId) === null) {
            json_error('Переговорная комната не найдена', 404);
        }

        // Нельзя удалить комнату, на которую
        // ссылаются существующие бронирования.
        if ($this->bookings->byRoom($roomId) !== []) {
            json_error(
                'Нельзя удалить комнату, связанную с бронированиями',
                409
            );
        }

        $this->rooms->delete($roomId);

        no_content();
    }

    // Проверка идентификатора.
    private function checkId(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            json_error('Некорректный ID комнаты', 400);
        }

        return (int) $id;
    }

    // Проверка данных переговорной комнаты.
    private function validate(array $data): array
    {
        $errors = [];

        $name = $data['name'] ?? null;

        if (
            !is_string($name) ||
            !preg_match('/^.{3,}$/us', trim($name))
        ) {
            $errors['name'] =
                'Название должно содержать не менее 3 символов';
        }

        $capacity = $data['capacity'] ?? null;

        if (!is_int($capacity) || $capacity < 1) {
            $errors['capacity'] =
                'Вместимость должна быть положительным целым числом';
        }

        $equipment = $data['equipment'] ?? null;

        if (
            !is_string($equipment) ||
            trim($equipment) === ''
        ) {
            $errors['equipment'] =
                'Укажите оборудование переговорной комнаты';
        }

        return $errors;
    }

    // Подготовка данных для сохранения.
    private function normalize(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'capacity' => $data['capacity'],
            'equipment' => trim($data['equipment'])
        ];
    }
}
