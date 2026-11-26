-- Удаление индексов для повторной проверки
DROP INDEX IF EXISTS idx_bookings_starts_at;
DROP INDEX IF EXISTS idx_bookings_subject;
DROP INDEX IF EXISTS idx_bookings_ends_at;

-- 1. Поиск по дате начала бронирования

-- До оптимизации
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, room_id, starts_at
FROM bookings
WHERE starts_at >= '2026-12-03 10:00:00'
  AND starts_at < '2026-12-03 11:00:00';

CREATE INDEX idx_bookings_starts_at
ON bookings (starts_at);

-- После оптимизации
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, room_id, starts_at
FROM bookings
WHERE starts_at >= '2026-12-03 10:00:00'
  AND starts_at < '2026-12-03 11:00:00';


-- 2. Поиск по названию бронирования

-- До оптимизации
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, room_id, subject, starts_at
FROM bookings
WHERE subject = 'Тестовая запись оптимизации 101-1000';

CREATE INDEX idx_bookings_subject
ON bookings (subject);

-- После оптимизации
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, room_id, subject, starts_at
FROM bookings
WHERE subject = 'Тестовая запись оптимизации 101-1000';


-- 3. Сортировка по дате окончания

-- До оптимизации
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, room_id, subject, ends_at
FROM bookings
ORDER BY ends_at DESC
LIMIT 20;

CREATE INDEX idx_bookings_ends_at
ON bookings (ends_at DESC);

-- После оптимизации
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, room_id, subject, ends_at
FROM bookings
ORDER BY ends_at DESC
LIMIT 20;
