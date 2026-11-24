-- Проверка 1. Вместимость комнаты должна быть больше 0
DO $$
BEGIN
    INSERT INTO rooms (room_number, capacity, location)
    VALUES ('999', 0, '9 этаж');

    RAISE EXCEPTION 'Ошибка: ограничение CHECK не сработало';
EXCEPTION
    WHEN check_violation THEN
        RAISE NOTICE 'Проверка 1 пройдена: CHECK работает';
END
$$;


-- Проверка 2. Нельзя забронировать несуществующую комнату
DO $$
BEGIN
    INSERT INTO bookings
        (room_id, subject, starts_at, ends_at, participants)
    VALUES
        (999999, 'Тестовое бронирование',
         '2026-12-01 10:00', '2026-12-01 11:00', 3);

    RAISE EXCEPTION 'Ошибка: внешний ключ не сработал';
EXCEPTION
    WHEN foreign_key_violation THEN
        RAISE NOTICE 'Проверка 2 пройдена: FOREIGN KEY работает';
END
$$;


-- Проверка 3. Номер переговорной комнаты должен быть уникальным
DO $$
BEGIN
    INSERT INTO rooms (room_number, capacity, location)
    VALUES ('101', 6, '1 этаж');

    RAISE EXCEPTION 'Ошибка: ограничение UNIQUE не сработало';
EXCEPTION
    WHEN unique_violation THEN
        RAISE NOTICE 'Проверка 3 пройдена: UNIQUE работает';
END
$$;


-- Проверка 4. Время окончания должно быть позже времени начала
DO $$
BEGIN
    INSERT INTO bookings
        (room_id, subject, starts_at, ends_at, participants)
    VALUES
        (1, 'Тестовое бронирование',
         '2026-12-01 12:00', '2026-12-01 11:00', 3);

    RAISE EXCEPTION 'Ошибка: ограничение CHECK не сработало';
EXCEPTION
    WHEN check_violation THEN
        RAISE NOTICE 'Проверка 4 пройдена: время бронирования проверяется';
END
$$;


-- Проверка 5. Статус бронирования должен быть допустимым
DO $$
BEGIN
    INSERT INTO bookings
        (room_id, subject, starts_at, ends_at, participants, status)
    VALUES
        (1, 'Тестовое бронирование',
         '2026-12-01 10:00', '2026-12-01 11:00', 3, 'unknown');

    RAISE EXCEPTION 'Ошибка: ограничение CHECK не сработало';
EXCEPTION
    WHEN check_violation THEN
        RAISE NOTICE 'Проверка 5 пройдена: статус бронирования проверяется';
END
$$;


-- Проверка 6. Количество участников должно быть больше 0
DO $$
BEGIN
    INSERT INTO bookings
        (room_id, subject, starts_at, ends_at, participants)
    VALUES
        (1, 'Тестовое бронирование',
         '2026-12-01 10:00', '2026-12-01 11:00', 0);

    RAISE EXCEPTION 'Ошибка: ограничение CHECK не сработало';
EXCEPTION
    WHEN check_violation THEN
        RAISE NOTICE 'Проверка 6 пройдена: количество участников проверяется';
END
$$;


-- Проверка 7. Удаление профиля вместе с пользователем (CASCADE)
DO $$
DECLARE
    test_user_id INTEGER;
BEGIN
    INSERT INTO users (full_name, email)
    VALUES ('Тестовый пользователь', 'cascade_test@example.com')
    RETURNING id INTO test_user_id;

    INSERT INTO user_profiles (user_id, phone, department)
    VALUES (test_user_id, '000', 'Тест');

    DELETE FROM users WHERE id = test_user_id;

    IF EXISTS (
        SELECT 1 FROM user_profiles
        WHERE user_id = test_user_id
    ) THEN
        RAISE EXCEPTION 'ON DELETE CASCADE не сработал';
    END IF;

    RAISE NOTICE 'Проверка 7 пройдена: ON DELETE CASCADE работает';
END
$$;


-- Проверка 8. Запрет удаления комнаты с бронированиями (RESTRICT)
DO $$
BEGIN
    DELETE FROM rooms
    WHERE id = (SELECT room_id FROM bookings LIMIT 1);

    RAISE EXCEPTION 'Ошибка: ON DELETE RESTRICT не сработал';
EXCEPTION
    WHEN restrict_violation THEN
        RAISE NOTICE 'Проверка 8 пройдена: ON DELETE RESTRICT работает';
END
$$;


-- Проверка 9. ON DELETE SET NULL
DO $$
DECLARE
    test_user_id INTEGER;
    test_booking_id INTEGER;
BEGIN
    INSERT INTO users (full_name, email)
    VALUES ('Тестовый пользователь', 'setnull_test@example.com')
    RETURNING id INTO test_user_id;

    INSERT INTO bookings
        (room_id, user_id, subject, starts_at, ends_at, participants)
    VALUES
        (1, test_user_id, 'Проверка SET NULL',
         '2026-12-02 10:00', '2026-12-02 11:00', 2)
    RETURNING id INTO test_booking_id;

    DELETE FROM users WHERE id = test_user_id;

    IF NOT EXISTS (
        SELECT 1 FROM bookings
        WHERE id = test_booking_id AND user_id IS NULL
    ) THEN
        RAISE EXCEPTION 'ON DELETE SET NULL не сработал';
    END IF;

    DELETE FROM bookings WHERE id = test_booking_id;

    RAISE NOTICE 'Проверка 9 пройдена: ON DELETE SET NULL работает';
END
$$;
