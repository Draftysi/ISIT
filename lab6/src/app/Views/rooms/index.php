<?php
// Шаблон списка переговорных комнат.
// $rooms — список переговорных комнат.

$rooms = $rooms ?? [];
?>

<h1>Переговорные комнаты</h1>

<?php if (count($rooms) === 0): ?>

  <p class="empty">Переговорные комнаты не найдены.</p>

<?php else: ?>

  <div class="grid">
    <?php foreach ($rooms as $room): ?>
      <div class="card">

        <h3>
          <?= e(
              $room['name']
              ?? $room['title']
              ?? ('Переговорная №' . ($room['number'] ?? $room['id']))
          ) ?>
        </h3>

        <p>
          Вместимость:
          <?= e($room['capacity'] ?? 'Не указана') ?>
          мест
        </p>

        <?php if (!empty($room['description'])): ?>
          <p><?= e($room['description']) ?></p>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>
