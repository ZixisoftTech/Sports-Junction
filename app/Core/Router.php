<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private Request $request;
    private Response $response;
    private array $routes = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function patch(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        // Convert route pattern with {param} into regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $normalizedPath);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'      => $method,
            'path'        => $normalizedPath,
            'regex'       => $regex,
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        $currentMethod = $this->request->getMethod();
        $currentPath = $this->request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $currentMethod) {
                continue;
            }

            if (preg_match($route['regex'], $currentPath, $matches)) {
                // Filter out numeric keys to keep named params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run Middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $middlewareResult = $middleware->handle($this->request, $this->response);
                    if ($middlewareResult === false) {
                        return; // Stopped by middleware
                    }
                }

                // Call Handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func($handler, $this->request, $this->response, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $method] = $handler;
                    $controller = new $class($this->request, $this->response);
                    call_user_func_array([$controller, $method], [$params]);
                    return;
                }
            }
        }

        // 404 Not Found
        if ($this->request->isAjax()) {
            $this->response->json(['success' => false, 'message' => 'Route not found'], 404);
            return;
        }

        $this->response->setStatusCode(404)->send(
            '<!DOCTYPE html><html><head><title>404 Not Found</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body class="bg-light d-flex align-items-center justify-content-center vh-100"><div class="text-center"><h1 class="display-1 fw-bold text-danger">404</h1><h3 class="mb-3">Page Not Found</h3><p class="text-muted">The requested URL was not found on this server.</p><a href="' . htmlspecialchars(Response::baseUrl()) . '/admin/dashboard" class="btn btn-primary">Return to Dashboard</a></div></body></html>',
            404
        );
    }
}
