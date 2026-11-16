<?php
// Форма создания бронирования переговорной комнаты.
// $rooms - список переговорных комнат.
// $errors - ошибки проверки.
// $old - ранее введённые значения.

$errors = $errors ?? [];
$old = $old ?? [];
$rooms = $rooms ?? [];
?>

<h1>Новое бронирование</h1>

<form action="/bookings" method="post" class="card form">

  <label for="subject">Тема встречи</label>
  <input
    id="subject"
    type="text"
    name="subject"
    value="<?= e($old['subject'] ?? '') ?>"
    required
  >

  <?php if (isset($errors['subject'])): ?>
    <div class="error">
      <?= e($errors['subject']) ?>
    </div>
  <?php endif; ?>

  <label for="room_id">Переговорная комната</label>
  <select id="room_id" name="room_id" required>
    <option value="">Выберите переговорную…</option>

    <?php foreach ($rooms as $room): ?>
      <option
        value="<?= e($room['id']) ?>"
        <?= (int) ($old['room_id'] ?? 0) === (int) $room['id']
            ? 'selected'
            : '' ?>
      >
        <?= e($room['name']) ?>
        (<?= e($room['capacity']) ?> мест)
      </option>
    <?php endforeach; ?>
  </select>

  <?php if (isset($errors['room_id'])): ?>
    <div class="error">
      <?= e($errors['room_id']) ?>
    </div>
  <?php endif; ?>

  <label for="date">Дата встречи</label>
  <input
    id="date"
    type="date"
    name="date"
    value="<?= e($old['date'] ?? '') ?>"
    required
  >

  <?php if (isset($errors['date'])): ?>
    <div class="error">
      <?= e($errors['date']) ?>
    </div>
  <?php endif; ?>

  <label for="start_time">Время начала</label>
  <input
    id="start_time"
    type="time"
    name="start_time"
    value="<?= e($old['start_time'] ?? '') ?>"
    required
  >

  <?php if (isset($errors['start_time'])): ?>
    <div class="error">
      <?= e($errors['start_time']) ?>
    </div>
  <?php endif; ?>

  <label for="end_time">Время окончания</label>
  <input
    id="end_time"
    type="time"
    name="end_time"
    value="<?= e($old['end_time'] ?? '') ?>"
    required
  >

  <?php if (isset($errors['end_time'])): ?>
    <div class="error">
      <?= e($errors['end_time']) ?>
    </div>
  <?php endif; ?>

  <label for="participants">Количество участников</label>
  <input
    id="participants"
    type="number"
    name="participants"
    min="1"
    value="<?= e($old['participants'] ?? '1') ?>"
    required
  >

  <?php if (isset($errors['participants'])): ?>
    <div class="error">
      <?= e($errors['participants']) ?>
    </div>
  <?php endif; ?>

  <p>
    <button type="submit" class="btn">
      Создать бронирование
    </button>

    <a href="/bookings">Отмена</a>
  </p>

</form>
