<?php
// Шаблон списка бронирований переговорных комнат.
// $tasks — список бронирований.
// $status — выбранный фильтр.

$statusNames = [
    'active' => 'Активно',
    'done' => 'Завершено',
    'cancelled' => 'Отменено',
];

$filterNames = [
    'active' => 'Активные',
    'done' => 'Завершённые',
    'cancelled' => 'Отменённые',
];

$roomNames = [
    1 => '№101',
    2 => '№204',
    3 => '№305',
];
?>

<h1>Бронирования</h1>

<p class="filters">
  Показать:
  <a href="/tasks" class="<?= $status === '' ? 'active' : '' ?>">
    все
  </a>

  <?php foreach ($filterNames as $code => $name): ?>
    <a
      href="/tasks?status=<?= e($code) ?>"
      class="<?= $status === $code ? 'active' : '' ?>"
    >
      <?= e($name) ?>
    </a>
  <?php endforeach; ?>
</p>

<?php if (count($tasks) === 0): ?>

  <p class="empty">Бронирования не найдены.</p>

<?php else: ?>

  <table style="width: 100%; table-layout: auto;">
    <thead>
      <tr>
        <th>№</th>
        <th>Тема встречи</th>
        <th>Комната</th>
        <th>Дата</th>
        <th>Время</th>
        <th>Участники</th>
        <th>Статус</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($tasks as $task): ?>
        <?php
          $roomId = (int) (
              $task['room_id']
              ?? $task['project_id']
              ?? 0
          );

          $roomName = $roomNames[$roomId] ?? 'Не указана';

          $date = $task['meeting_date']
              ?? $task['date']
              ?? $task['due_date']
              ?? null;

          $startTime = $task['start_time'] ?? null;
          $endTime = $task['end_time'] ?? null;

          $time = ($startTime && $endTime)
              ? $startTime . '–' . $endTime
              : '—';

          $participants = $task['participants']
              ?? $task['participants_count']
              ?? '—';

          $taskStatus = $task['status'] ?? 'new';

          $statusName = $statusNames[$taskStatus]
              ?? 'Неизвестен';

          $badgeClass = $taskStatus;

          if ($taskStatus === 'cancelled') {
              $badgeClass = 'new';
          }
        ?>

        <tr>
          <td><?= e($task['id']) ?></td>

          <td>
            <a href="/tasks/<?= e($task['id']) ?>">
              <?= e($task['title']) ?>
            </a>
          </td>

          <td style="white-space: nowrap;">
            <?= e($roomName) ?>
          </td>

          <td style="white-space: nowrap;">
            <?= e($date ?? '—') ?>
          </td>

          <td style="white-space: nowrap;">
            <?= e($time) ?>
          </td>

          <td style="text-align: center;">
            <?= e($participants) ?>
          </td>

          <td>
            <span class="badge badge-<?= e($badgeClass) ?>">
              <?= e($statusName) ?>
            </span>
          </td>
        </tr>

      <?php endforeach; ?>
    </tbody>
  </table>

<?php endif; ?>
