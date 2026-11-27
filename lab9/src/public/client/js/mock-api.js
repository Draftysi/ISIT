
const MockApi = {
    delay: 500,
    errorRate: 0,
    networkDown: false,

    setDelay(min, max) {
        this.delay = min;
    },

    setErrorRate(rate) {
        this.errorRate = rate;
    },

    setNetworkDown(value) {
        this.networkDown = value;
    }
};

// Сохраняем обычный fetch браузера
const originalFetch = window.fetch.bind(window);

// Создание ответа учебного сервера
function jsonResponse(data, status = 200) {
    return new Response(JSON.stringify(data), {
        status: status,
        headers: {
            "Content-Type": "application/json"
        }
    });
}

// Перехват запросов к учебному серверу
window.fetch = async function (url, options = {}) {
    const requestUrl = String(url);

    // Обычные запросы не перехватываем
    if (!requestUrl.startsWith("/api/")) {
        return originalFetch(url, options);
    }

    // Имитируем задержку сервера
    await new Promise(resolve =>
        setTimeout(resolve, MockApi.delay)
    );

    // Проверяем подключение к сети
    if (MockApi.networkDown) {
        throw new TypeError("Нет подключения к сети");
    }

    // Имитируем ошибку сервера
    if (Math.random() < MockApi.errorRate) {
        return jsonResponse({
            error: "Ошибка сервера"
        }, 500);
    }

    // Разбираем адрес запроса
    // Используем виртуальный адрес вместо file:///
    const address = new URL(requestUrl, "https://mock.local");
    const method = (options.method || "GET").toUpperCase();

    // Получение списка переговорных комнат
    if (address.pathname === "/api/rooms" && method === "GET") {
        return jsonResponse(rooms);
    }

    // Получение списка бронирований
    if (address.pathname === "/api/bookings" && method === "GET") {
        let result = [...bookings];

        const status = address.searchParams.get("status");
        const search = address.searchParams.get("q");

        // Фильтрация по статусу
        if (status) {
            result = result.filter(item =>
                item.status === status
            );
        }

        // Поиск по теме встречи
        if (search) {
            result = result.filter(item =>
                item.subject.toLowerCase().includes(
                    search.toLowerCase()
                )
            );
        }

        return jsonResponse(result);
    }

    // Неизвестный адрес
    return jsonResponse({
        error: "Адрес не найден"
    }, 404);
};
