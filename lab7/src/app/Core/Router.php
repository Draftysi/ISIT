<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, array $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, array $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $params = $this->match($route['path'], $path);

            if ($params === null) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            [$class, $action] = $route['handler'];

            $controller = new $class();
            $controller->$action(...$params);

            return;
        }

        if ($allowedMethods !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_unique($allowedMethods)));

            if ($this->isApi($path)) {
                $this->jsonError('Метод не поддерживается');
            } else {
                echo 'Метод не поддерживается';
            }

            return;
        }

        http_response_code(404);

        if ($this->isApi($path)) {
            $this->jsonError('Ресурс не найден');
            return;
        }

        view('errors/404', ['path' => $path]);
    }

    private function isApi(string $path): bool
    {
        return $path === '/api' || str_starts_with($path, '/api/');
    }

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            ['error' => $message, 'errors' => new \stdClass()],
            JSON_UNESCAPED_UNICODE
        );
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = '#^' . preg_replace(
            '#\{(\w+)\}#',
            '(?P<$1>[^/]+)',
            $pattern
        ) . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        return array_filter(
            $matches,
            'is_string',
            ARRAY_FILTER_USE_KEY
        );
    }
}
