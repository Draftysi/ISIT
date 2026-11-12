
const form = document.getElementById("bookingForm");
const roomSelect = document.getElementById("room");
const loadButton = document.getElementById("loadButton");
const statusFilter = document.getElementById("statusFilter");
const searchInput = document.getElementById("searchInput");
const bookingTable = document.getElementById("bookingTable");
const message = document.getElementById("message");
const counter = document.getElementById("counter");

// Номер последнего запроса для защиты от дублирования
let requestNumber = 0;

// Вывод сообщений
function showMessage(text, isError = false) {
    message.textContent = text;
    message.style.color = isError ? "red" : "#253b53";
}

// Загрузка списка переговорных комнат
async function loadRooms() {
    try {
        const response = await fetch("/api/rooms");

        if (!response.ok) {
            throw new Error("Ошибка сервера: " + response.status);
        }

        const data = await response.json();

        roomSelect.replaceChildren();

        const firstOption = document.createElement("option");
        firstOption.value = "";
        firstOption.textContent = "Выберите комнату";
        roomSelect.appendChild(firstOption);

        data.forEach(room => {
            const option = document.createElement("option");
            option.value = room.id;
            option.textContent = room.name;
            roomSelect.appendChild(option);
        });

    } catch (error) {
        showMessage(
            "Не удалось загрузить комнаты: " + error.message,
            true
        );
    }
}

// Создание строки таблицы
function createBookingRow(booking) {
    const row = document.createElement("tr");
    row.dataset.id = booking.id;

    const room = rooms.find(item => item.id === booking.roomId);

    const values = [
        booking.id,
        room ? room.name : "Неизвестная комната",
        booking.date,
        booking.startTime + " - " + booking.endTime,
        booking.subject,
        booking.participants,
        booking.status === "active" ? "Активно" : "Отменено"
    ];

    values.forEach(value => {
        const cell = document.createElement("td");
        cell.textContent = value;
        row.appendChild(cell);
    });

    const actionCell = document.createElement("td");
    const button = document.createElement("button");

    if (booking.status === "active") {
        button.textContent = "Отменить";
        button.title = "Отменить бронирование";

        button.addEventListener("click", () => {
            cancelBooking(booking.id);
        });
    } else {
        button.textContent = "Отменено";
        button.disabled = true;
        row.style.color = "gray";
    }

    actionCell.appendChild(button);
    row.appendChild(actionCell);

    bookingTable.appendChild(row);
}

// Обновление счётчика
function updateCounter() {
    counter.textContent =
        "Бронирований: " + bookingTable.rows.length;
}

// Загрузка бронирований с учебного сервера
async function refreshList() {
    // Каждый новый запрос получает свой номер
    const currentRequest = ++requestNumber;

    loadButton.disabled = true;
    showMessage("Загрузка...");

    // Очищаем таблицу перед загрузкой
    bookingTable.replaceChildren();
    updateCounter();

    try {
        const params = new URLSearchParams();

        if (statusFilter.value) {
            params.set("status", statusFilter.value);
        }

        if (searchInput.value.trim()) {
            params.set("q", searchInput.value.trim());
        }

        const url = "/api/bookings?" + params.toString();
        const response = await fetch(url);

        // Если уже начался более новый запрос,
        // результат старого больше не используем
        if (currentRequest !== requestNumber) {
            return;
        }

        if (!response.ok) {
            throw new Error("Ошибка сервера: " + response.status);
        }

        const data = await response.json();

        if (currentRequest !== requestNumber) {
            return;
        }

        // Убираем старые строки
        bookingTable.replaceChildren();

        // Убираем возможные повторения по ID
        const uniqueBookings = [
            ...new Map(
                data.map(booking => [booking.id, booking])
            ).values()
        ];

        if (uniqueBookings.length === 0) {
            showMessage(
                "Бронирования не найдены. Измените фильтр или поиск."
            );
        } else {
            uniqueBookings.forEach(createBookingRow);
            showMessage("Данные успешно загружены");
        }

        updateCounter();

    } catch (error) {
        if (currentRequest !== requestNumber) {
            return;
        }

        bookingTable.replaceChildren();
        updateCounter();

        showMessage(
            "Не удалось загрузить данные: " + error.message,
            true
        );

    } finally {
        if (currentRequest === requestNumber) {
            loadButton.disabled = false;
        }
    }
}

// Создание нового бронирования
function onFormSubmit(event) {
    event.preventDefault();

    const roomId = Number(roomSelect.value);
    const date = document.getElementById("date").value;
    const startTime = document.getElementById("startTime").value;
    const endTime = document.getElementById("endTime").value;
    const subject = document.getElementById("subject").value.trim();
    const participants = Number(
        document.getElementById("participants").value
    );

    const room = rooms.find(item => item.id === roomId);

    // Проверка заполнения формы
    if (
        !roomSelect.value ||
        !date ||
        !startTime ||
        !endTime ||
        !subject ||
        !participants
    ) {
        showMessage("Заполните все поля формы", true);
        return;
    }

    // Проверка темы встречи
    if (subject.length < 3) {
        showMessage(
            "Тема встречи должна содержать не менее 3 символов",
            true
        );
        return;
    }

    // Проверка времени
    if (startTime >= endTime) {
        showMessage(
            "Время окончания должно быть позже начала",
            true
        );
        return;
    }

    // Проверка количества участников
    if (!room || participants < 1 || participants > room.capacity) {
        showMessage(
            "Проверьте количество участников и вместимость комнаты",
            true
        );
        return;
    }

    // Проверка пересечения с другими бронированиями
    const occupied = bookings.some(item =>
        item.roomId === roomId &&
        item.date === date &&
        item.status === "active" &&
        startTime < item.endTime &&
        endTime > item.startTime
    );

    if (occupied) {
        showMessage(
            "Комната уже забронирована на это время",
            true
        );
        return;
    }

    // Создание нового бронирования
    const newBooking = {
        id: Math.max(0, ...bookings.map(item => item.id)) + 1,
        roomId: roomId,
        date: date,
        startTime: startTime,
        endTime: endTime,
        subject: subject,
        participants: participants,
        status: "active"
    };

    bookings.push(newBooking);

    // Очистка формы и фильтров
    form.reset();
    statusFilter.value = "";
    searchInput.value = "";

    // Обновление таблицы
    refreshList();
}

// Отмена бронирования
function cancelBooking(id) {
    const booking = bookings.find(item => item.id === id);

    if (booking && booking.status === "active") {
        booking.status = "cancelled";
        refreshList();
    }
}

// Обработчики событий
form.addEventListener("submit", onFormSubmit);
loadButton.addEventListener("click", refreshList);
statusFilter.addEventListener("change", refreshList);
searchInput.addEventListener("input", refreshList);

// Загрузка данных при открытии страницы
loadRooms();
refreshList();
