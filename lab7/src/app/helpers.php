<?php
// app/helpers.php — небольшие вспомогательные функции для всего приложения.

declare(strict_types=1);

// Читает настройку из config/app.php: config('name'), config('debug')
function config(string $key): mixed
{
    static $config = null;                                 // файл читается один раз
    $config ??= require BASE_PATH . '/config/app.php';
    return $config[$key] ?? null;
}

// Безопасный вывод текста в HTML: заменяет символы < > & " на безопасные коды.
// Защита от XSS: данные пользователя в шаблонах всегда выводим через e(...), а не напрямую.
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Показывает страницу: шапка + шаблон + подвал.
// view('tasks/index', ['tasks' => $tasks]) подключает app/Views/tasks/index.php,
// а внутри шаблона будет доступна переменная $tasks.
function view(string $template, array $data = []): void
{
    extract($data);                                        // ключи массива становятся переменными
    require APP_PATH . '/Views/layout/header.php';
    require APP_PATH . '/Views/' . $template . '.php';
    require APP_PATH . '/Views/layout/footer.php';
}

// Перенаправляет браузер на другой адрес и завершает работу скрипта.
function redirect(string $path): never
{
    header('Location: ' . $path);                          // 302: «ваша страница теперь по этому адресу»
    exit;
}

// Показывает страницу «Не найдено» с кодом ответа 404 и завершает работу скрипта.
function notFound(): never
{
    http_response_code(404);
    view('errors/404', ['path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)]);
    exit;
}


 // Отправляет JSON-ответ с указанным HTTP-кодом.
function json_response(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

// Отправляет ошибку в едином JSON-формате.
function json_error(
    string $message,
    int $code = 400,
    array $errors = []
): never {
    json_response([
        'error' => $message,
        'errors' => $errors === [] ? new stdClass() : $errors
    ], $code);
}

// Отвечает кодом 204 после успешного удаления.
function no_content(): never
{
    http_response_code(204);
    exit;
}

// Читает JSON из тела HTTP-запроса.
function json_input(): array
{
    $raw = file_get_contents('php://input');

    try {
        $data = json_decode(
            $raw,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        json_error('Некорректный формат JSON', 400);
    }

    if (!is_array($data)) {
        json_error('Ожидается объект JSON', 400);
    }

    return $data;
}
