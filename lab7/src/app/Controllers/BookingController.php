<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\BookingRepository;
use App\Models\RoomRepository;

final class BookingController
{
    private BookingRepository $bookings;
    private RoomRepository $rooms;

    public function __construct()
    {
        $this->bookings = new BookingRepository();
        $this->rooms = new RoomRepository();
    }

    // GET /bookings - список бронирований.
    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $bookings = $this->bookings->all();

        // Фильтрация по статусу.
        if (in_array($status, ['active', 'done', 'cancelled'], true)) {
            $bookings = array_values(array_filter(
                $bookings,
                fn(array $booking): bool =>
                    ($booking['status'] ?? '') === $status
            ));
        }

        view('bookings/index', [
            'title' => 'Бронирования',
            'bookings' => $bookings,
            'rooms' => $this->rooms->all(),
            'status' => $status,
        ]);
    }

    // GET /bookings/{id} - просмотр бронирования.
    public function show(string $id): void
    {
        $booking = $this->bookings->find((int) $id);

        if ($booking === null) {
            notFound();
            return;
        }

        $room = $this->rooms->find(
            (int) $booking['room_id']
        );

        view('bookings/show', [
            'title' => $booking['subject'],
            'booking' => $booking,
            'room' => $room,
        ]);
    }

    // GET /bookings/create - форма создания.
    public function create(): void
    {
        view('bookings/create', [
            'title' => 'Новое бронирование',
            'rooms' => $this->rooms->all(),
            'errors' => [],
            'old' => [],
        ]);
    }

    // POST /bookings - сохранение бронирования.
    public function store(): void
    {
        $subject = trim($_POST['subject'] ?? '');
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $date = $_POST['date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';

        $participants = filter_var(
            $_POST['participants'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $errors = [];

        // Проверка темы встречи.
        if (!preg_match('/^.{3,}$/us', $subject)) {
            $errors['subject'] =
                'Укажите тему встречи (не менее 3 символов)';
        }

        // Проверка переговорной комнаты.
        $room = $this->rooms->find($roomId);

        if ($room === null) {
            $errors['room_id'] =
                'Выберите переговорную комнату';
        }

        // Проверка даты.
        $parsedDate = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if (
            $parsedDate === false ||
            $parsedDate->format('Y-m-d') !== $date
        ) {
            $errors['date'] =
                'Укажите корректную дату встречи';
        }

        // Проверка времени.
        $timePattern = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';

        if (
            !preg_match($timePattern, $startTime) ||
            !preg_match($timePattern, $endTime)
        ) {
            $errors['start_time'] =
                'Укажите корректное время встречи';
        } elseif ($startTime >= $endTime) {
            $errors['end_time'] =
                'Время окончания должно быть позже начала';
        }

        // Проверка количества участников.
        if ($participants === false || $participants === null) {
            $errors['participants'] =
                'Укажите количество участников';
        } elseif (
            $room !== null &&
            $participants > (int) $room['capacity']
        ) {
            $errors['participants'] =
                'Количество участников превышает вместимость комнаты';
        }

        // Проверка пересечения с другими бронированиями.
        if (
            !isset($errors['room_id']) &&
            !isset($errors['date']) &&
            !isset($errors['start_time']) &&
            !isset($errors['end_time'])
        ) {
            foreach ($this->bookings->all() as $existing) {
                // Сравниваем бронирования одной комнаты
                // на одну дату.
                if (
                    (int) $existing['room_id'] !== $roomId ||
                    $existing['date'] !== $date
                ) {
                    continue;
                }

                // Завершённые и отменённые не учитываем.
                if (in_array(
                    $existing['status'] ?? '',
                    ['done', 'cancelled'],
                    true
                )) {
                    continue;
                }

                // Проверяем пересечение интервалов.
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

        // При ошибках повторно показываем форму.
        if ($errors) {
            http_response_code(422);

            view('bookings/create', [
                'title' => 'Новое бронирование',
                'rooms' => $this->rooms->all(),
                'errors' => $errors,
                'old' => $_POST,
            ]);

            return;
        }

        // Сохраняем бронирование в JSON-файле.
        $this->bookings->add([
            'room_id' => $roomId,
            'subject' => $subject,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'participants' => $participants,
            'status' => 'active',
        ]);

        // После сохранения открываем список.
        redirect('/bookings');
    }
}
