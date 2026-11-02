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

    // GET /tasks — список задач с возможностью фильтрации.
    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $tasks = $this->tasks->all();

        if ($status !== '') {
            $tasks = array_values(array_filter(
                $tasks,
                fn(array $t): bool => $t['status'] === $status
            ));
        }

        view('tasks/index', [
            'title' => 'Задачи',
            'tasks' => $tasks,
            'status' => $status,
        ]);
    }

    // GET /tasks/{id} — просмотр отдельной задачи.
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
            'project' => $this->projects->find($task['project_id']),
        ]);
    }

    // GET /tasks/create — форма создания задачи.
    public function create(): void
    {
        view('tasks/create', [
            'title' => 'Новая задача',
            'projects' => $this->projects->all(),
            'errors' => [],
            'old' => [],
        ]);
    }

    // POST /tasks — обработка формы.
    public function store(): void
    {
        $title = trim($_POST['title'] ?? '');
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $priority = $_POST['priority'] ?? 'medium';
        $dueDate = $_POST['due_date'] ?? '';

        $errors = [];

        if (mb_strlen($title, 'UTF-8') < 3) {
            $errors['title'] = 'Название — не короче 3 символов';
        }

        if ($this->projects->find($projectId) === null) {
            $errors['project_id'] = 'Выберите проект из списка';
        }

        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            $errors['priority'] = 'Недопустимый приоритет';
        }

        if ($errors) {
            http_response_code(422);

            view('tasks/create', [
                'title' => 'Новая задача',
                'projects' => $this->projects->all(),
                'errors' => $errors,
                'old' => $_POST,
            ]);

            return;
        }

        $this->tasks->add([
            'project_id' => $projectId,
            'title' => $title,
            'status' => 'new',
            'priority' => $priority,
            'due_date' => $dueDate !== '' ? $dueDate : null,
        ]);

        redirect('/tasks');
    }
}
