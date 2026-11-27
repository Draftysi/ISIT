-- 1. Просмотр всех переговорных комнат
SELECT *
FROM rooms
ORDER BY id;

-- 2. Список бронирований с комнатами и пользователями
SELECT
    bookings.id,
    bookings.subject,
    rooms.room_number,
    users.full_name,
    bookings.starts_at,
    bookings.status
FROM bookings
JOIN rooms ON bookings.room_id = rooms.id
LEFT JOIN users ON bookings.user_id = users.id
ORDER BY bookings.id;


-- 3. Добавление новой переговорной комнаты
INSERT INTO rooms (room_number, capacity, location)
VALUES ('601', 8, '6 этаж');


-- 4. Изменение вместимости переговорной комнаты
UPDATE rooms
SET capacity = 10
WHERE room_number = '601';


-- 5. Удаление переговорной комнаты
DELETE FROM rooms
WHERE room_number = '601';

-- 6. Просмотр оборудования в переговорных комнатах (связь N:M)
SELECT
    rooms.room_number,
    equipment.name
FROM room_equipment
JOIN rooms ON room_equipment.room_id = rooms.id
JOIN equipment ON room_equipment.equipment_id = equipment.id
ORDER BY rooms.room_number, equipment.name;

-- 7. Количество бронирований по переговорным комнатам
SELECT
    rooms.room_number,
    COUNT(bookings.id) AS booking_count
FROM rooms
LEFT JOIN bookings ON rooms.id = bookings.room_id
GROUP BY rooms.id, rooms.room_number
ORDER BY rooms.room_number;

-- 8. Список активных бронирований с сортировкой по времени
SELECT
    id,
    subject,
    room_id,
    starts_at,
    ends_at,
    status
FROM bookings
WHERE status = 'active'
ORDER BY starts_at;

-- 9. Создание нового бронирования
INSERT INTO bookings
    (room_id, subject, starts_at, ends_at, participants)
SELECT
    id,
    'Тестовое бронирование CRUD',
    '2026-12-03 10:00',
    '2026-12-03 11:00',
    2
FROM rooms
WHERE room_number = '101'
RETURNING id;

-- 10. Получение бронирования по ID
SELECT
    id,
    room_id,
    user_id,
    subject,
    starts_at,
    ends_at,
    participants,
    status
FROM bookings
WHERE id = 1;

-- 11. Изменение количества участников бронирования
UPDATE bookings
SET participants = 4
WHERE id = (
    SELECT id
    FROM bookings
    WHERE subject = 'Тестовое бронирование CRUD'
    ORDER BY id DESC
    LIMIT 1
)
RETURNING id, subject, participants;

-- 12. Удаление тестового бронирования
DELETE FROM bookings
WHERE id = (
    SELECT id
    FROM bookings
    WHERE subject = 'Тестовое бронирование CRUD'
    ORDER BY id DESC
    LIMIT 1
)
RETURNING id, subject;
