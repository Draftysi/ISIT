<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProjectRepository;
use App\Models\TaskRepository;

final class ProjectController
{
    // GET /projects — список всех проектов.
    public function index(): void
    {
        $projects = (new ProjectRepository())->all();

        view('projects/index', [
            'title' => 'Проекты',
            'projects' => $projects,
        ]);
    }

    // GET /projects/2 — информация о проекте и его задачах.
    public function show(string $id): void
    {
        $project = (new ProjectRepository())->find((int) $id);

        if ($project === null) {
            notFound();
            return;
        }

        $tasks = (new TaskRepository())->byProject($project['id']);

        view('projects/show', [
            'title' => $project['title'],
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }
}
