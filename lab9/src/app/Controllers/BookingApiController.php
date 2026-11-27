<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\BookingRepository;
use App\Models\RoomRepository;

final class BookingApiController
{
    private BookingRepository $bookings;
    private RoomRepository $rooms;

    public function __construct()
    {
        $this->bookings = new BookingRepository();
        $this->rooms = new RoomRepository();
    }

    // GET /api/bookings
    public function index(): void
    {
                $query = $_GET['q'] ?? '';

        if (!is_string($query)) {
            json_error('Некорректный поисковый запрос', 422);
        }

        $query = trim($query);

        $bookings = $query !== ''
            ? $this->bookings->search($query)
            : $this->bookings->all();

        // Фильтрация по статусу.
        if (isset($_GET['status'])) {
            $status = $_GET['status'];

            if (
                !is_string($status) ||
                !in_array($status, ['active', 'done', 'cancelled'], true)
            ) {
                json_error('Некорректный статус', 422);
            }

            $bookings = array_values(array_filter(
                $bookings,
                fn(array $booking): bool =>
                    $booking['status'] === $status
            ));
        }

        // Фильтрация по комнате.
        if (isset($_GET['room_id'])) {
            $roomId = $_GET['room_id'];

            if (
                !is_string($roomId) ||
                !ctype_digit($roomId) ||
                (int) $roomId < 1
            ) {
                json_error('Некорректный ID комнаты', 422);
            }

            $bookings = array_values(array_filter(
                $bookings,
                fn(array $booking): bool =>
                    (int) $booking['room_id'] === (int) $roomId
            ));
        }

        json_response(['data' => $bookings]);
    }

    // GET /api/bookings/{id}
    public function show(string $id): void
    {
        $bookingId = $this->checkId($id);
        $booking = $this->bookings->find($bookingId);

        if ($booking === null) {
            json_error('Бронирование не найдено', 404);
        }

        json_response(['data' => $booking]);
    }

    // POST /api/bookings
    public function store(): void
    {
        $input = json_input();
        $data = $this->prepare($input);

        $errors = $this->validate($data);

        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $data['subject'] = trim($data['subject']);

        $data['user_id'] = (int) AUTH_USER['id'];
        $booking = $this->bookings->add($data);

        header('Location: /api/bookings/' . $booking['id']);

        json_response(['data' => $booking], 201);
    }

    // PUT/PATCH /api/bookings/{id}
    public function update(string $id): void
    {
        $bookingId = $this->checkId($id);
        $existing = $this->bookings->find($bookingId);

        if ($existing === null) {
            json_error('Бронирование не найдено', 404);
        }

        if (
            AUTH_USER['role'] !== 'admin' &&
            (int) $existing['user_id'] !== (int) AUTH_USER['id']
        ) {
            json_error('Нельзя изменять чужое бронирование', 403);
        }
        $input = json_input();
        $data = $this->prepare($input, $existing);

        $errors = $this->validate($data, $bookingId);

        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $data['subject'] = trim($data['subject']);

        $booking = $this->bookings->update($bookingId, $data);

        json_response(['data' => $booking]);
    }

       // DELETE /api/bookings/{id}
    public function destroy(string $id): void
    {
        $bookingId = $this->checkId($id);
        $existing = $this->bookings->find($bookingId);

        if ($existing === null) {
            json_error('Бронирование не найдено', 404);
        }

        if (
            AUTH_USER['role'] !== 'admin' &&
            (int) $existing['user_id'] !== (int) AUTH_USER['id']
        ) {
            json_error('Нельзя удалять чужое бронирование', 403);
        }

        if (!$this->bookings->delete($bookingId)) {
            json_error('Бронирование не найдено', 404);
        }

        no_content();
    }

    // Проверка ID из адреса.
    private function checkId(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            json_error('Некорректный ID бронирования', 400);
        }

        return (int) $id;
    }

    // Подготовка разрешённых полей.
    private function prepare(
        array $input,
        ?array $existing = null
    ): array {
        $fields = [
            'room_id',
            'subject',
            'date',
            'start_time',
            'end_time',
            'participants',
            'status'
        ];

        $data = $existing === null
            ? ['status' => 'active']
            : array_intersect_key(
                $existing,
                array_flip($fields)
            );

        foreach ($fields as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }

        return $data;
    }

    // Проверка данных перед сохранением.
    private function validate(
        array $data,
        ?int $excludeId = null
    ): array {
        $errors = [];

        // Тема встречи.
        $subject = $data['subject'] ?? null;

        if (
            !is_string($subject) ||
            !preg_match('/^.{3,}$/us', trim($subject))
        ) {
            $errors['subject'] =
                'Тема встречи должна содержать не менее 3 символов';
        }

        // Переговорная комната.
        $roomId = $data['room_id'] ?? null;

        $room = is_int($roomId) && $roomId > 0
            ? $this->rooms->find($roomId)
            : null;

        if ($room === null) {
            $errors['room_id'] =
                'Укажите существующую переговорную комнату';
        }

        // Дата встречи.
        $date = $data['date'] ?? null;

        $parsedDate = is_string($date)
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date)
            : false;

        if (
            $parsedDate === false ||
            $parsedDate->format('Y-m-d') !== $date
        ) {
            $errors['date'] = 'Некорректная дата встречи';
        }

        // Время встречи.
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;

        $timePattern = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';

        if (
            !is_string($startTime) ||
            !preg_match($timePattern, $startTime)
        ) {
            $errors['start_time'] =
                'Некорректное время начала';
        }

        if (
            !is_string($endTime) ||
            !preg_match($timePattern, $endTime)
        ) {
            $errors['end_time'] =
                'Некорректное время окончания';
        }

        if (
            !isset($errors['start_time']) &&
            !isset($errors['end_time']) &&
            $startTime >= $endTime
        ) {
            $errors['end_time'] =
                'Время окончания должно быть позже начала';
        }

        // Количество участников.
        $participants = $data['participants'] ?? null;

        if (!is_int($participants) || $participants < 1) {
            $errors['participants'] =
                'Количество участников должно быть положительным числом';
        } elseif (
            $room !== null &&
            $participants > (int) $room['capacity']
        ) {
            $errors['participants'] =
                'Количество участников превышает вместимость комнаты';
        }

        // Статус бронирования.
        $status = $data['status'] ?? null;

        if (
            !is_string($status) ||
            !in_array(
                $status,
                ['active', 'done', 'cancelled'],
                true
            )
        ) {
            $errors['status'] =
                'Допустимые статусы: active, done, cancelled';
        }

        // Проверка пересечения бронирований.
        if (
            $status === 'active' &&
            !isset($errors['room_id']) &&
            !isset($errors['date']) &&
            !isset($errors['start_time']) &&
            !isset($errors['end_time'])
        ) {
            foreach ($this->bookings->all() as $existing) {
                // При изменении не сравниваем запись с самой собой.
                if (
                    $excludeId !== null &&
                    (int) $existing['id'] === $excludeId
                ) {
                    continue;
                }

                if (
                    (int) $existing['room_id'] !== $roomId ||
                    $existing['date'] !== $date
                ) {
                    continue;
                }

                // Завершённые и отменённые встречи не мешают.
                if (in_array(
                    $existing['status'] ?? '',
                    ['done', 'cancelled'],
                    true
                )) {
                    continue;
                }

                if (
                    $startTime < $existing['end_time'] &&
                    $endTime > $existing['start_time']
                ) {
                    $errors['start_time'] =
                        'Переговорная уже занята в выбранное время';
                    break;
                }
            }
        }

        return $errors;
    }
}
