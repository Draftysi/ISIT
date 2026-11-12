<?php
// Страница списка бронирований переговорных комнат.

$bookings = $bookings ?? $tasks ?? [];
$status = $status ?? '';

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

// Названия переговорных комнат.
$roomNames = [
    1 => '№101',
    2 => '№204',
    3 => '№305',
];
?>

<h1>Бронирования</h1>

<p class="filters">
    Показать:

    <a
        href="/bookings"
        class="<?= $status === '' ? 'active' : '' ?>"
    >
        все
    </a>

    <?php foreach ($filterNames as $code => $name): ?>
        <a
            href="/bookings?status=<?= e($code) ?>"
            class="<?= $status === $code ? 'active' : '' ?>"
        >
            <?= e($name) ?>
        </a>
    <?php endforeach; ?>
</p>

<?php if (count($bookings) === 0): ?>

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
            <?php foreach ($bookings as $booking): ?>
                <?php
                $roomId = (int) (
                    $booking['room_id']
                    ?? $booking['project_id']
                    ?? 0
                );

                $roomName = $roomNames[$roomId] ?? 'Не указана';

                $subject = $booking['subject']
                    ?? $booking['title']
                    ?? 'Без темы';

                $date = $booking['date']
                    ?? $booking['meeting_date']
                    ?? $booking['due_date']
                    ?? '—';

                $startTime = $booking['start_time'] ?? '';
                $endTime = $booking['end_time'] ?? '';

                $time = ($startTime !== '' && $endTime !== '')
                    ? $startTime . '–' . $endTime
                    : '—';

                $participants = $booking['participants']
                    ?? $booking['participants_count']
                    ?? '—';

                $bookingStatus = $booking['status'] ?? '';

                $statusName = $statusNames[$bookingStatus]
                    ?? 'Неизвестен';
                ?>

                <tr>
                    <td><?= e($booking['id']) ?></td>

                    <td>
                        <a href="/bookings/<?= e($booking['id']) ?>">
                            <?= e($subject) ?>
                        </a>
                    </td>

                    <td style="white-space: nowrap;">
                        <?= e($roomName) ?>
                    </td>

                    <td style="white-space: nowrap;">
                        <?= e($date) ?>
                    </td>

                    <td style="white-space: nowrap;">
                        <?= e($time) ?>
                    </td>

                    <td style="text-align: center;">
                        <?= e($participants) ?>
                    </td>

                    <td>
                        <span class="badge badge-<?= e($bookingStatus) ?>">
                            <?= e($statusName) ?>
                        </span>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>
