<?php
// Шаблон просмотра одного бронирования.
// $booking - данные бронирования.
// $room - выбранная переговорная комната.

$statusNames = [
    'active' => 'Активно',
    'done' => 'Завершено',
    'cancelled' => 'Отменено',
];

$status = $booking['status'] ?? 'active';

$date = $booking['date'] ?? '—';
$startTime = $booking['start_time'] ?? null;
$endTime = $booking['end_time'] ?? null;

$time = ($startTime && $endTime)
    ? $startTime . '–' . $endTime
    : '—';

$participants = $booking['participants'] ?? '—';

$roomName = $room['name']
    ?? $room['title']
    ?? 'Не указана';
?>

<p>
  <a href="/bookings">← К списку бронирований</a>
</p>

<div class="card">
  <h1><?= e($booking['subject']) ?></h1>

  <dl class="meta">

    <dt>Номер бронирования</dt>
    <dd><?= e($booking['id']) ?></dd>

    <dt>Переговорная комната</dt>
    <dd><?= e($roomName) ?></dd>

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
