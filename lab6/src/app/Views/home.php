<?php
// Главная страница системы бронирования переговорных комнат.
?>

<h1>Главная</h1>

<div class="stats">
  <div class="card">
    <div class="stat-value"><?= e($total) ?></div>
    <div class="stat-label">Всего бронирований</div>
  </div>

  <div class="card">
    <div class="stat-value"><?= e($byStatus['active']) ?></div>
    <div class="stat-label">Активных</div>
  </div>

  <div class="card">
    <div class="stat-value"><?= e($byStatus['done']) ?></div>
    <div class="stat-label">Завершённых</div>
  </div>

  <div class="card">
    <div class="stat-value"><?= e($byStatus['cancelled']) ?></div>
    <div class="stat-label">Отменённых</div>
  </div>
</div>

<p>
  В системе доступно переговорных комнат:
  <strong><?= e($projectsCount) ?></strong>.
</p>

<p>
  Перейти к
  <a href="/tasks">списку бронирований</a>
  или
  <a href="/projects">переговорным комнатам</a>.
</p>
