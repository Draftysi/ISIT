<?php
// Страница отдельной переговорной комнаты.
// $project - данные переговорной комнаты.
// $tasks - бронирования этой комнаты.

$statusNames = [
    'new' => 'Активно',
    'in_progress' => 'Активно',
    'active' => 'Активно',
    'done' => 'Завершено',
    'cancelled' => 'Отменено',
];

$statusClasses = [
    'new' => 'active',
    'in_progress' => 'active',
    'active' => 'active',
    'done' => 'done',
    'cancelled' => 'cancelled',
];
?>

<p>
  <a href="/projects">← Ко всем переговорным</a>
</p>

<div class="card">
  <h1><?= e($project['title']) ?></h1>

  <p><?= e($project['description'] ?? '') ?></p>
</div>

<h2>Бронирования комнаты (<?= count($tasks) ?>)</h2>

<?php if (count($tasks) === 0): ?>

  <p class="empty">
    Для этой переговорной пока нет бронирований.
  </p>

<?php else: ?>

  <ul class="list">
    <?php foreach ($tasks as $task): ?>
      <?php
        $status = $task['status'] ?? 'new';
        $statusName = $statusNames[$status] ?? 'Неизвестен';
        $statusClass = $statusClasses[$status] ?? '';
      ?>

      <li>
        <a href="/tasks/<?= e($task['id']) ?>">
          <?= e($task['title']) ?>
        </a>

        <span class="badge badge-<?= e($statusClass) ?>">
          <?= e($statusName) ?>
        </span>
      </li>

    <?php endforeach; ?>
  </ul>

<?php endif; ?>
