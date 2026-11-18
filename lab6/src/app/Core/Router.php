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

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['path'], $path);

            if ($params === null) {
                continue;
            }

            [$class, $action] = $route['handler'];

            $controller = new $class();
            $controller->$action(...$params);

            return;
        }

        http_response_code(404);
        view('errors/404', ['path' => $path]);
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
