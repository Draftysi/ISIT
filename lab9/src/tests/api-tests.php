<?php
declare(strict_types=1);

const BASE_URL = 'http://127.0.0.1:8000';

$passed = 0;
$failed = 0;

// Отправка HTTP-запроса.
function request(
    string $method,
    string $path,
    ?array $body = null,
    ?string $token = null
): array {
    $headers = ['Accept: application/json'];

    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    $options = [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 10
    ];

    if ($body !== null) {
        $options['content'] = json_encode(
            $body,
            JSON_UNESCAPED_UNICODE
        );
    }

    $context = stream_context_create(['http' => $options]);

    $response = @file_get_contents(
        BASE_URL . $path,
        false,
        $context
    );

    if ($response === false) {
        throw new RuntimeException(
            'Не удалось подключиться к серверу'
        );
    }

    $responseHeaders = function_exists('http_get_last_response_headers')
        ? http_get_last_response_headers()
        : ($http_response_header ?? []);

    preg_match(
        '/^HTTP\/\S+\s+(\d{3})/',
        $responseHeaders[0] ?? '',
        $matches
    );

    return [
        'status' => (int) ($matches[1] ?? 0),
        'body' => json_decode($response, true)
    ];
}

// Проверка результата теста.
function check(string $name, bool $condition): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[PASS] $name\n";
    } else {
        $failed++;
        echo "[FAIL] $name\n";
    }
}

// Проверка кода HTTP-ответа.
function expect(
    string $name,
    array $response,
    int $expected
): void {
    check(
        $name . ' (HTTP ' . $response['status'] . ')',
        $response['status'] === $expected
    );
}

$adminToken = null;
$userToken = null;
$createdIds = [];

echo "API TESTS\n";
echo "==============================\n\n";

try {
    // Доступ без авторизации.
    expect(
        'Access without token',
        request('GET', '/api/bookings'),
        401
    );

    // Вход с неверным паролем.
    expect(
        'Invalid password',
        request('POST', '/api/login', [
            'email' => 'ivan@example.com',
            'password' => '111111'
        ]),
        401
    );

    // Вход администратора.
    $admin = request('POST', '/api/login', [
        'email' => 'anna@example.com',
        'password' => '123456'
    ]);

    expect('Admin login', $admin, 200);

    $adminToken = $admin['body']['token'] ?? null;

    check(
        'Admin role',
        ($admin['body']['user']['role'] ?? '') === 'admin'
    );

    // Вход обычного пользователя.
    $user = request('POST', '/api/login', [
        'email' => 'ivan@example.com',
        'password' => '123456'
    ]);

    expect('User login', $user, 200);

    $userToken = $user['body']['token'] ?? null;

    check(
        'User role',
        ($user['body']['user']['role'] ?? '') === 'user'
    );

    if (!$adminToken || !$userToken) {
        throw new RuntimeException('Ошибка авторизации');
    }

    // Получение данных.
    expect(
        'Current user',
        request('GET', '/api/me', null, $userToken),
        200
    );

    expect(
        'Rooms list',
        request('GET', '/api/rooms', null, $userToken),
        200
    );

    $bookings = request(
        'GET',
        '/api/bookings',
        null,
        $userToken
    );

    expect('Bookings list', $bookings, 200);

    // Проверка прав доступа.
    expect(
        'Admin access to users',
        request('GET', '/api/users', null, $adminToken),
        200
    );

    expect(
        'User access to users denied',
        request('GET', '/api/users', null, $userToken),
        403
    );

    // Поиск бронирований.
    $search = request(
        'GET',
        '/api/bookings?q=IT',
        null,
        $userToken
    );

    check(
        'Search by subject',
        $search['status'] === 200 &&
        count($search['body']['data'] ?? []) === 1 &&
        (int) ($search['body']['data'][0]['id'] ?? 0) === 4
    );

    // Фильтрация по статусу.
    $filter = request(
        'GET',
        '/api/bookings?status=active',
        null,
        $userToken
    );

    $items = $filter['body']['data'] ?? [];

    check(
        'Filter by status',
        $filter['status'] === 200 &&
        count($items) > 0 &&
        count(array_filter(
            $items,
            fn($item) => $item['status'] !== 'active'
        )) === 0
    );

    // Выбор свободной даты для тестирования.
    $usedDates = array_column(
        $bookings['body']['data'] ?? [],
        'date'
    );

    $date = new DateTimeImmutable('tomorrow');

    while (in_array($date->format('Y-m-d'), $usedDates, true)) {
        $date = $date->modify('+1 day');
    }

    // Создание тестового бронирования.
    $data = [
        'room_id' => 1,
        'date' => $date->format('Y-m-d'),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'subject' => 'Автоматический тест',
        'participants' => 3,
        'status' => 'active'
    ];

    $created = request(
        'POST',
        '/api/bookings',
        $data,
        $userToken
    );

    expect('Create booking', $created, 201);

    $id = (int) ($created['body']['data']['id'] ?? 0);

    if ($id < 1) {
        throw new RuntimeException(
            'Тестовое бронирование не создано'
        );
    }

    $createdIds[] = $id;

    check(
        'Booking owner',
        (int) ($created['body']['data']['user_id'] ?? 0) ===
        (int) ($user['body']['user']['id'] ?? 0)
    );

    // Получение созданного бронирования.
    expect(
        'Get booking by ID',
        request('GET', "/api/bookings/$id", null, $userToken),
        200
    );

    // Проверка пересечения бронирований.
    $overlap = request(
        'POST',
        '/api/bookings',
        $data,
        $userToken
    );

    expect('Overlapping booking', $overlap, 422);

    if (isset($overlap['body']['data']['id'])) {
        $createdIds[] = (int) $overlap['body']['data']['id'];
    }

    // Проверка вместимости комнаты.
    $invalid = $data;
    $invalid['participants'] = 100;

    expect(
        'Invalid participants count',
        request('POST', '/api/bookings', $invalid, $userToken),
        422
    );

    // Создание бронирования администратора.
    $adminData = $data;
    $adminData['start_time'] = '14:00';
    $adminData['end_time'] = '15:00';

    $adminBooking = request(
        'POST',
        '/api/bookings',
        $adminData,
        $adminToken
    );

    expect('Admin creates booking', $adminBooking, 201);

    $adminId = (int) ($adminBooking['body']['data']['id'] ?? 0);

    if ($adminId < 1) {
        throw new RuntimeException(
            'Бронирование администратора не создано'
        );
    }

    $createdIds[] = $adminId;

    // Запрет изменения чужого бронирования.
    expect(
        'Foreign booking update denied',
        request(
            'PATCH',
            "/api/bookings/$adminId",
            ['status' => 'cancelled'],
            $userToken
        ),
        403
    );

    // Запрет удаления чужого бронирования.
    expect(
        'Foreign booking deletion denied',
        request(
            'DELETE',
            "/api/bookings/$adminId",
            null,
            $userToken
        ),
        403
    );

    // Отмена собственного бронирования.
    $cancelled = request(
        'PATCH',
        "/api/bookings/$id",
        ['status' => 'cancelled'],
        $userToken
    );

    check(
        'Cancel own booking',
        $cancelled['status'] === 200 &&
        ($cancelled['body']['data']['status'] ?? '') === 'cancelled'
    );

    // Удаление собственного бронирования.
    expect(
        'Delete own booking',
        request('DELETE', "/api/bookings/$id", null, $userToken),
        204
    );

    // Проверка отсутствия удалённой записи.
    expect(
        'Deleted booking not found',
        request('GET', "/api/bookings/$id", null, $userToken),
        404
    );

    // Выход из системы.
    expect(
        'Logout',
        request('POST', '/api/logout', null, $userToken),
        204
    );

    // Проверка отозванного токена.
    expect(
        'Revoked token rejected',
        request('GET', '/api/bookings', null, $userToken),
        401
    );

} catch (Throwable $error) {
    $failed++;
    echo '[ERROR] ' . $error->getMessage() . "\n";

} finally {
    // Удаление тестовых записей.
    if ($adminToken !== null) {
        foreach ($createdIds as $createdId) {
            try {
                $cleanup = request(
                    'DELETE',
                    "/api/bookings/$createdId",
                    null,
                    $adminToken
                );

                if (!in_array($cleanup['status'], [204, 404], true)) {
                    check('Cleanup booking ' . $createdId, false);
                }
            } catch (Throwable $error) {
                check('Cleanup booking ' . $createdId, false);
            }
        }

        try {
            request('POST', '/api/logout', null, $adminToken);
        } catch (Throwable $error) {
            check('Admin logout', false);
        }
    }
}

echo "\n==============================\n";
echo "Total:  " . ($passed + $failed) . "\n";
echo "Passed: " . $passed . "\n";
echo "Failed: " . $failed . "\n";

exit($failed > 0 ? 1 : 0);