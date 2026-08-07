<?php
declare(strict_types=1);

namespace App;

use App\Controllers\ActividadController;
use App\Controllers\AuthController;
use App\Controllers\SuscripcionController;
use App\Middleware\AuthMiddleware;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    public function dispatch(string $method, string $uri): void
    {
        Response::handleOptions();

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $matched = null;
        $params = [];
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $path, $m)) {
                $allowedMethods[] = $route['method'];
                if ($route['method'] === $method) {
                    $matched = $route;
                    foreach ($m as $k => $v) {
                        if (!is_int($k)) {
                            $params[$k] = $v;
                        }
                    }
                }
            }
        }

        if (!$matched) {
            if (!empty($allowedMethods)) {
                header('Allow: ' . implode(', ', array_unique($allowedMethods)));
                Response::error('Método no permitido', 405);
            }
            Response::error('Ruta no encontrada', 404);
        }

        foreach ($matched['middleware'] as $mw) {
            $mw();
        }

        try {
            $handler = $matched['handler'];
            $handler($params);
        } catch (\Throwable $e) {
            if (Config::getBool('APP_DEBUG', false)) {
                Response::error('Error interno: ' . $e->getMessage(), 500, [
                    'trace' => explode("\n", $e->getTraceAsString()),
                ]);
            }
            Response::error('Error interno del servidor', 500);
        }
    }

    private function add(string $method, string $path, callable $handler, array $middleware): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }
}