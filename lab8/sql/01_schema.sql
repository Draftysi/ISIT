-- 1. Пользователи
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE
);

-- 2. Профили пользователей (связь 1:1)
CREATE TABLE user_profiles (
    user_id INTEGER PRIMARY KEY
        REFERENCES users(id) ON DELETE CASCADE,
    phone VARCHAR(20),
    department VARCHAR(100)
);

-- 3. Переговорные комнаты
CREATE TABLE rooms (
    id SERIAL PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    capacity INTEGER NOT NULL CHECK (capacity > 0),
    location VARCHAR(100) NOT NULL
);

-- 4. Оборудование
CREATE TABLE equipment (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- 5. Оборудование в комнатах (связь M:N)
CREATE TABLE room_equipment (
    room_id INTEGER NOT NULL
        REFERENCES rooms(id) ON DELETE CASCADE,
    equipment_id INTEGER NOT NULL
        REFERENCES equipment(id) ON DELETE RESTRICT,
    PRIMARY KEY (room_id, equipment_id)
);

-- 6. Бронирования (связи 1:N)
CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    room_id INTEGER NOT NULL
        REFERENCES rooms(id) ON DELETE RESTRICT,
    user_id INTEGER
        REFERENCES users(id) ON DELETE SET NULL,
    subject VARCHAR(200) NOT NULL,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,
    participants INTEGER NOT NULL CHECK (participants > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    CHECK (ends_at > starts_at),
    CHECK (status IN ('active', 'done', 'cancelled'))
);
