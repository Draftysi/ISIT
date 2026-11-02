<?php
// Шаблон просмотра одного бронирования.
// $task - данные бронирования.
// $project - выбранная переговорная комната.

$statusNames = [
    'active' => 'Активно',
    'done' => 'Завершено',
    'cancelled' => 'Отменено',
];

$status = $task['status'] ?? 'active';

$date = $task['due_date'] ?? '—';
$startTime = $task['start_time'] ?? null;
$endTime = $task['end_time'] ?? null;

$time = ($startTime && $endTime)
    ? $startTime . '–' . $endTime
    : '—';

$participants = $task['participants'] ?? '—';

$roomName = $project['title'] ?? 'Не указана';
$roomId = $project['id'] ?? null;
?>

<p>
  <a href="/tasks">← К списку бронирований</a>
</p>

<div class="card">
  <h1><?= e($task['title']) ?></h1>

  <dl class="meta">

    <dt>Номер бронирования</dt>
    <dd><?= e($task['id']) ?></dd>

    <dt>Переговорная комната</dt>
    <dd>
      <?php if ($roomId !== null): ?>
        <a href="/projects/<?= e($roomId) ?>">
          <?= e($roomName) ?>
        </a>
      <?php else: ?>
        <?= e($roomName) ?>
      <?php endif; ?>
    </dd>

    <dt>Дата встречи</dt>
    <dd><?= e($date) ?></dd>

    <dt>Время встречи</dt>
    <dd><?= e($time) ?></dd>

    <dt>Количество участников</dt>
    <dd><?= e($participants) ?></dd>

    <dt>Статус</dt>
    <dd>
      <span class="badge badge-<?= e($status) ?>">
        <?= e($statusNames[$status] ?? 'Неизвестен') ?>
      </span>
    </dd>

  </dl>
</div>
