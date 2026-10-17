<?php
declare(strict_types=1);

namespace App\Core;

use App\Helpers\Session;

abstract class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    protected function render(string $viewPath, array $data = [], ?string $layout = 'admin'): void
    {
        if (!isset($data['baseUrl'])) {
            $data['baseUrl'] = Response::baseUrl();
        }

        // Extract data to make variables accessible in view
        extract($data);

        $baseDir = dirname(__DIR__) . '/Views/';
        $viewFile = $baseDir . ltrim($viewPath, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file not found: {$viewFile}");
        }

        // Start output buffering for view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = $baseDir . 'layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout file not found: {$layoutFile}");
        }

        require $layoutFile;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        $this->response->json($data, $statusCode);
    }

    protected function redirect(string $path): void
    {
        $this->response->redirect($path);
    }

    protected function authUser(): ?array
    {
        return Session::get('auth_user');
    }
}
