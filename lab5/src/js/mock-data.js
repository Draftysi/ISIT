
const rooms = [
    {
        id: 1,
        name: "Переговорная 101",
        location: "1 этаж",
        capacity: 6,
        equipment: "Проектор"
    },
    {
        id: 2,
        name: "Переговорная 204",
        location: "2 этаж",
        capacity: 8,
        equipment: "Экран"
    },
    {
        id: 3,
        name: "Переговорная 305",
        location: "3 этаж",
        capacity: 12,
        equipment: "Проектор, доска"
    }
];

const bookings = [
    {
        id: 1,
        roomId: 1,
        date: "2026-10-20",
        startTime: "10:00",
        endTime: "11:00",
        subject: "Обсуждение проекта",
        participants: 6,
        status: "active"
    },
    {
        id: 2,
        roomId: 2,
        date: "2026-10-21",
        startTime: "14:00",
        endTime: "15:00",
        subject: "Встреча команды",
        participants: 5,
        status: "active"
    },
    {
        id: 3,
        roomId: 3,
        date: "2026-10-22",
        startTime: "12:00",
        endTime: "13:00",
        subject: "Презентация",
        participants: 10,
        status: "cancelled"
    }
];
const ROUTES = {
    'GET /api/rooms': function () {
        return rooms;
    },

    'GET /api/bookings': function (query) {
        return filterList(bookings, query);
    }
};