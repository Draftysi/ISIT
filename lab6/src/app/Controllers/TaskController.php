<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProjectRepository;
use App\Models\TaskRepository;

final class TaskController
{
    private TaskRepository $tasks;
    private ProjectRepository $projects;

    public function __construct()
    {
        $this->tasks = new TaskRepository();
        $this->projects = new ProjectRepository();
    }

    // GET /tasks - список бронирований.
    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $tasks = $this->tasks->all();

        // Фильтрация бронирований по статусу.
        if (in_array($status, ['active', 'done', 'cancelled'], true)) {
            $tasks = array_values(array_filter(
                $tasks,
                fn(array $task): bool =>
                    ($task['status'] ?? '') === $status
            ));
        }

        view('tasks/index', [
            'title' => 'Бронирования',
            'tasks' => $tasks,
            'status' => $status,
        ]);
    }

    // GET /tasks/{id} - подробности бронирования.
    public function show(string $id): void
    {
        $task = $this->tasks->find((int) $id);

        if ($task === null) {
            notFound();
            return;
        }

        view('tasks/show', [
            'title' => $task['title'],
            'task' => $task,
            'project' => $this->projects->find(
                (int) $task['project_id']
            ),
        ]);
    }

    // GET /tasks/create - форма нового бронирования.
    public function create(): void
    {
        view('tasks/create', [
            'title' => 'Новое бронирование',
            'projects' => $this->projects->all(),
            'errors' => [],
            'old' => [],
        ]);
    }

    // POST /tasks - создание бронирования.
    public function store(): void
    {
        $title = trim($_POST['title'] ?? '');
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $dueDate = $_POST['due_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';

        $participants = filter_var(
            $_POST['participants'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $errors = [];

        // Проверка темы встречи.
        if (mb_strlen($title, 'UTF-8') < 3) {
            $errors['title'] =
                'Укажите тему встречи (не менее 3 символов)';
        }

        // Проверка выбранной переговорной комнаты.
        $room = $this->projects->find($projectId);

        if ($room === null) {
            $errors['project_id'] =
                'Выберите переговорную комнату';
        }

        // Проверка даты встречи.
        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dueDate
        );

        if (
            $date === false ||
            $date->format('Y-m-d') !== $dueDate
        ) {
            $errors['due_date'] =
                'Укажите корректную дату встречи';
        }

        // Проверка времени начала и окончания.
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
        }

        // Проверка пересечения с другими бронированиями.
        if (
            !isset($errors['project_id']) &&
            !isset($errors['due_date']) &&
            !isset($errors['start_time']) &&
            !isset($errors['end_time'])
        ) {
            foreach ($this->tasks->all() as $booking) {

                // Проверяем только ту же комнату и дату.
                if (
                    (int) ($booking['project_id'] ?? 0) !== $projectId ||
                    ($booking['due_date'] ?? '') !== $dueDate
                ) {
                    continue;
                }

                // Завершённые и отменённые бронирования
                // не препятствуют созданию нового.
                if (
                    in_array(
                        $booking['status'] ?? '',
                        ['done', 'cancelled'],
                        true
                    )
                ) {
                    continue;
                }

                // Записи без времени пропускаем.
                if (
                    empty($booking['start_time']) ||
                    empty($booking['end_time'])
                ) {
                    continue;
                }

                // Проверяем пересечение временных интервалов.
                if (
                    $startTime < $booking['end_time'] &&
                    $endTime > $booking['start_time']
                ) {
                    $errors['start_time'] =
                        'Переговорная уже занята в выбранное время';

                    break;
                }
            }
        }

        // Если есть ошибки, снова показываем форму.
        if ($errors) {
            http_response_code(422);

            view('tasks/create', [
                'title' => 'Новое бронирование',
                'projects' => $this->projects->all(),
                'errors' => $errors,
                'old' => $_POST,
            ]);

            return;
        }

        // Сохраняем новое активное бронирование.
        $this->tasks->add([
            'project_id' => $projectId,
            'title' => $title,
            'status' => 'active',
            'priority' => 'medium',
            'due_date' => $dueDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'participants' => $participants,
        ]);

        // Возвращаемся к списку бронирований.
        redirect('/tasks');
    }
}
