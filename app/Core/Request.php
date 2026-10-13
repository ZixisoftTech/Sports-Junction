<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private static string $basePath = '';
    private string $method;
    private string $uri;
    private string $path;
    private array $queryParams;
    private array $postData;
    private array $jsonData;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Handle method override for forms (PUT, PATCH, DELETE)
        if ($this->method === 'POST') {
            if (isset($_POST['_method'])) {
                $this->method = strtoupper((string)$_POST['_method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $this->method = strtoupper((string)$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        $resolved = self::detectPathAndBase($requestUri, $scriptName);
        $this->path = $resolved['path'];
        self::$basePath = $resolved['base'];

        $this->uri = $requestUri;
        $this->queryParams = $_GET;
        $this->postData = $_POST;

        $rawBody = file_get_contents('php://input');
        $this->jsonData = [];
        if (!empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $this->jsonData = $decoded;
            }
        }

        $this->headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    }

    /**
     * Accurately extracts the route path and application base path
     * Handles root domains, XAMPP subdirectories, and rewrites flawlessly.
     */
    public static function detectPathAndBase(string $requestUri, string $scriptName): array
    {
        $parsed = parse_url($requestUri);
        $rawPath = $parsed['path'] ?? '/';

        // Strip index.php if explicitly in the URL
        if (str_contains($rawPath, 'index.php')) {
            $rawPath = preg_replace('#/index\.php/?#', '/', $rawPath);
        }

        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
            $scriptDir = '';
        }

        $parentDir = str_replace('\\', '/', dirname($scriptDir));
        if ($parentDir === '/' || $parentDir === '\\' || $parentDir === '.') {
            $parentDir = '';
        }

        $matchedBase = '';
        if ($scriptDir !== '' && str_starts_with($rawPath, $scriptDir)) {
            $matchedBase = $scriptDir;
            $rawPath = substr($rawPath, strlen($scriptDir));
        } elseif ($parentDir !== '' && str_starts_with($rawPath, $parentDir)) {
            $matchedBase = $parentDir;
            $rawPath = substr($rawPath, strlen($parentDir));
        }

        $path = '/' . trim($rawPath, '/');
        if ($path === '//') {
            $path = '/';
        }

        return [
            'path' => $path,
            'base' => rtrim($matchedBase, '/')
        ];
    }

    public static function getBaseUrl(): string
    {
        return self::$basePath;
    }

    public static function setBaseUrl(string $base): void
    {
        self::$basePath = rtrim($base, '/');
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postData[$key] ?? $this->jsonData[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->queryParams, $this->postData, $this->jsonData);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (isset($this->postData[$key])) {
            return $this->postData[$key];
        }
        if (isset($this->jsonData[$key])) {
            return $this->jsonData[$key];
        }
        if (isset($this->queryParams[$key])) {
            return $this->queryParams[$key];
        }
        return $default;
    }

    public function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($this->getHeader('Accept') ?? '', 'application/json'));
    }

    public function getHeader(string $name): ?string
    {
        $normalized = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return (string)$value;
            }
        }
        return null;
    }

    public function header(string $name): ?string
    {
        return $this->getHeader($name);
    }

    public function getIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
