-- Создание 100 000 тестовых бронирований
INSERT INTO bookings
    (room_id, subject, starts_at, ends_at, participants, status)
SELECT
    r.id,
    'Тестовая запись оптимизации ' || r.room_number || '-' || g.n,
    TIMESTAMP '2026-12-01 09:00:00' + g.n * INTERVAL '1 minute',
    TIMESTAMP '2026-12-01 10:00:00' + g.n * INTERVAL '1 minute',
    1 + (g.n % 4),
    CASE
        WHEN g.n % 100 = 0 THEN 'cancelled'
        WHEN g.n % 10 = 0 THEN 'done'
        ELSE 'active'
    END
FROM rooms r
CROSS JOIN generate_series(1, 20000) AS g(n)
WHERE r.room_number IN ('101', '204', '305', '402', '503')
  AND NOT EXISTS (
      SELECT 1
      FROM bookings
      WHERE subject LIKE 'Тестовая запись оптимизации %'
  );

-- Обновление статистики базы данных
ANALYZE bookings;
