<?php
// Форма создания бронирования переговорной комнаты.
// $projects - список переговорных комнат.
// $errors - ошибки проверки.
// $old - ранее введённые значения.

$errors = $errors ?? [];
$old = $old ?? [];
$projects = $projects ?? [];
?>

<h1>Новое бронирование</h1>

<form action="/tasks" method="post" class="card form">

  <label for="title">Тема встречи</label>
  <input
    id="title"
    type="text"
    name="title"
    value="<?= e($old['title'] ?? '') ?>"
    required
  >
  <?php if (isset($errors['title'])): ?>
    <div class="error"><?= e($errors['title']) ?></div>
  <?php endif; ?>

  <label for="project_id">Переговорная комната</label>
  <select id="project_id" name="project_id" required>
    <option value="">Выберите переговорную…</option>

    <?php foreach ($projects as $project): ?>
      <option
        value="<?= e($project['id']) ?>"
        <?= (int) ($old['project_id'] ?? 0) === (int) $project['id'] ? 'selected' : '' ?>
      >
        <?= e($project['title']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <?php if (isset($errors['project_id'])): ?>
    <div class="error"><?= e($errors['project_id']) ?></div>
  <?php endif; ?>

  <label for="due_date">Дата встречи</label>
  <input
    id="due_date"
    type="date"
    name="due_date"
    value="<?= e($old['due_date'] ?? '') ?>"
    required
  >

  <?php if (isset($errors['due_date'])): ?>
    <div class="error"><?= e($errors['due_date']) ?></div>
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
    <div class="error"><?= e($errors['start_time']) ?></div>
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
    <div class="error"><?= e($errors['end_time']) ?></div>
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
    <div class="error"><?= e($errors['participants']) ?></div>
  <?php endif; ?>

  <p>
    <button type="submit" class="btn">
      Создать бронирование
    </button>

    <a href="/tasks">Отмена</a>
  </p>

</form>
