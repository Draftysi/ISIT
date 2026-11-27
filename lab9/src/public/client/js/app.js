const form = document.getElementById("bookingForm");
const roomSelect = document.getElementById("room");
const loadButton = document.getElementById("loadButton");
const statusFilter = document.getElementById("statusFilter");
const searchInput = document.getElementById("searchInput");
const bookingTable = document.getElementById("bookingTable");
const message = document.getElementById("message");
const counter = document.getElementById("counter");

const loginForm = document.getElementById("loginForm");
const loginSection = document.getElementById("loginSection");
const loginMessage = document.getElementById("loginMessage");
const appSection = document.getElementById("appSection");
const userInfo = document.getElementById("userInfo");
const logoutButton = document.getElementById("logoutButton");

let token = sessionStorage.getItem("token");
let currentUser = null;
let rooms = [];
let requestNumber = 0;

// Вывод сообщений.
function showMessage(text, isError = false) {
    message.textContent = text;
    message.style.color = isError ? "red" : "#253b53";
}

// Запрос к REST API.
async function api(url, method = "GET", body = null) {
    const headers = {
        "Accept": "application/json"
    };

    const requestToken = token;

    if (requestToken) {
        headers.Authorization = "Bearer " + requestToken;
    }

    const options = { method, headers };

    if (body !== null) {
        headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(body);
    }

    let response;

    try {
        response = await fetch(url, options);
    } catch (error) {
        throw new Error(
            "Не удалось подключиться к серверу. Проверьте соединение и повторите попытку."
        );
    }

    if (response.status === 204) {
        return null;
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        if (
            response.status === 401 &&
            url !== "/api/login" &&
            requestToken === token
        ) {
            showLogin("Войдите в систему повторно");
        }

        const errors = Object.values(data?.errors || {});

        throw new Error(
            errors.length
                ? errors.join(". ")
                : data?.error || "Ошибка сервера: " + response.status
        );
    }

    if (data === null) {
        throw new Error("Некорректный ответ сервера");
    }

    return data;
}

// Отображение формы входа.
function showLogin(text = "") {
    token = null;
    currentUser = null;
    rooms = [];
    requestNumber++;

    sessionStorage.removeItem("token");

    bookingTable.replaceChildren();
    roomSelect.replaceChildren();
    form.reset();

    statusFilter.value = "";
    searchInput.value = "";
    loadButton.disabled = false;

    updateCounter();
    showMessage("");

    appSection.hidden = true;
    loginSection.hidden = false;

    loginMessage.textContent = text;
    loginMessage.style.color = "red";
}

// Отображение основного интерфейса.
async function showApp(user) {
    currentUser = user;

    const role = user.role === "admin"
        ? "Администратор"
        : "Пользователь";

    userInfo.textContent = user.name + " — " + role;

    loginSection.hidden = true;
    appSection.hidden = false;
    loginMessage.textContent = "";

    const activeToken = token;

    await loadRooms();

    if (token && token === activeToken) {
        await refreshList();
    }
}

// Вход в систему.
loginForm.addEventListener("submit", async event => {
    event.preventDefault();

    const button = loginForm.querySelector("button");
    button.disabled = true;

    try {
        const result = await api("/api/login", "POST", {
            email: document.getElementById("email").value.trim(),
            password: document.getElementById("password").value
        });

        token = result.token;
        sessionStorage.setItem("token", token);

        loginForm.reset();

        await showApp(result.user);

    } catch (error) {
        loginMessage.textContent = error.message;
        loginMessage.style.color = "red";
    } finally {
        button.disabled = false;
    }
});

// Выход из системы.
logoutButton.addEventListener("click", async () => {
    logoutButton.disabled = true;

    try {
        await api("/api/logout", "POST");
        showLogin();

    } catch (error) {
        if (token) {
            showMessage(error.message, true);
        }
    } finally {
        logoutButton.disabled = false;
    }
});

// Загрузка переговорных комнат.
async function loadRooms() {
    const activeToken = token;

    try {
        const result = await api("/api/rooms");

        if (activeToken !== token) {
            return;
        }

        rooms = result.data;

        roomSelect.replaceChildren();

        const firstOption = document.createElement("option");
        firstOption.value = "";
        firstOption.textContent = "Выберите комнату";
        roomSelect.appendChild(firstOption);

        rooms.forEach(room => {
            const option = document.createElement("option");

            option.value = room.id;
            option.textContent =
                room.name + " (до " + room.capacity + " чел.)";

            roomSelect.appendChild(option);
        });

    } catch (error) {
        if (token) {
            showMessage(
                "Не удалось загрузить комнаты: " + error.message,
                true
            );
        }
    }
}

// Создание строки таблицы.
function createBookingRow(booking) {
    const row = document.createElement("tr");
    row.dataset.id = booking.id;

    const room = rooms.find(
        item => Number(item.id) === Number(booking.room_id)
    );

    const statuses = {
        active: "Активно",
        done: "Завершено",
        cancelled: "Отменено"
    };

    const values = [
        booking.id,
        room ? room.name : "Неизвестная комната",
        booking.date,
        booking.start_time + " - " + booking.end_time,
        booking.subject,
        booking.participants,
        statuses[booking.status] || booking.status
    ];

    values.forEach(value => {
        const cell = document.createElement("td");
        cell.textContent = value;
        row.appendChild(cell);
    });

    const actionCell = document.createElement("td");
    const button = document.createElement("button");

    const canEdit =
        currentUser.role === "admin" ||
        Number(booking.user_id) === Number(currentUser.id);

    if (booking.status !== "active") {
        button.textContent = statuses[booking.status];
        button.disabled = true;
        row.style.color = "gray";

    } else if (canEdit) {
        button.textContent = "Отменить";

        button.addEventListener("click", () => {
            cancelBooking(booking.id, button);
        });

    } else {
        button.textContent = "Нет доступа";
        button.disabled = true;
    }

    actionCell.appendChild(button);
    row.appendChild(actionCell);
    bookingTable.appendChild(row);
}

// Обновление счётчика.
function updateCounter() {
    counter.textContent =
        "Бронирований: " + bookingTable.rows.length;
}

// Загрузка бронирований.
async function refreshList() {
    if (!token) {
        return;
    }

    const currentRequest = ++requestNumber;

    loadButton.disabled = true;
    showMessage("Загрузка...");

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

        const result = await api(
            "/api/bookings?" + params.toString()
        );

        if (currentRequest !== requestNumber) {
            return;
        }

                const uniqueBookings = [
            ...new Map(
                result.data.map(booking => [booking.id, booking])
            ).values()
        ];

        bookingTable.replaceChildren();

        uniqueBookings.forEach(createBookingRow);

        showMessage(
            uniqueBookings.length
                ? "Данные успешно загружены"
                : "Бронирования не найдены"
        );

        updateCounter();

    } catch (error) {
        if (currentRequest !== requestNumber) {
            return;
        }

        bookingTable.replaceChildren();
        updateCounter();

        showMessage(error.message, true);

    } finally {
        if (currentRequest === requestNumber) {
            loadButton.disabled = false;
        }
    }
}

// Создание бронирования.
async function onFormSubmit(event) {
    event.preventDefault();

    const roomId = Number(roomSelect.value);
    const date = document.getElementById("date").value;
    const startTime = document.getElementById("startTime").value;
    const endTime = document.getElementById("endTime").value;
    const subject = document.getElementById("subject").value.trim();
    const participants = Number(
        document.getElementById("participants").value
    );

    const room = rooms.find(
        item => Number(item.id) === roomId
    );

    if (subject.length < 3) {
        showMessage(
            "Тема встречи должна содержать не менее 3 символов",
            true
        );
        return;
    }

    if (startTime >= endTime) {
        showMessage(
            "Время окончания должно быть позже начала",
            true
        );
        return;
    }

    if (
        !room ||
        !Number.isInteger(participants) ||
        participants < 1 ||
        participants > room.capacity
    ) {
        showMessage(
            "Проверьте количество участников и вместимость комнаты",
            true
        );
        return;
    }

    const button = form.querySelector("button");
    button.disabled = true;

    const activeToken = token;

    try {
        await api("/api/bookings", "POST", {
            room_id: roomId,
            date: date,
            start_time: startTime,
            end_time: endTime,
            subject: subject,
            participants: participants,
            status: "active"
        });

        if (activeToken !== token) {
            return;
        }

        form.reset();
        statusFilter.value = "";
        searchInput.value = "";

        await refreshList();

    } catch (error) {
        if (activeToken === token) {
            showMessage(error.message, true);
        }
    } finally {
        button.disabled = false;
    }
}

// Отмена бронирования.
async function cancelBooking(id, button) {
    button.disabled = true;

    const activeToken = token;

    try {
        await api("/api/bookings/" + id, "PATCH", {
            status: "cancelled"
        });

        if (activeToken === token) {
            await refreshList();
        }

    } catch (error) {
        if (activeToken === token) {
            showMessage(error.message, true);
        }
    } finally {
        button.disabled = false;
    }
}

// Обработчики событий.
form.addEventListener("submit", onFormSubmit);
loadButton.addEventListener("click", refreshList);
statusFilter.addEventListener("change", refreshList);
searchInput.addEventListener("input", refreshList);

// Восстановление авторизации после обновления страницы.
async function init() {
    if (!token) {
        showLogin();
        return;
    }

    try {
        const result = await api("/api/me");
        await showApp(result.data);

    } catch (error) {
        showLogin(error.message);
    }
}

init();