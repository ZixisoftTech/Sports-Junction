<?php
declare(strict_types=1);

namespace App\Core;

use App\Helpers\Env;

require_once __DIR__ . '/Request.php';

class Response
{
    private int $statusCode = 200;
    private array $headers = [];

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        $this->statusCode = $statusCode;
        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function redirect(string $path): void
    {
        $url = $path;
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
            $base = self::baseUrl();
            if ($base !== '' && $base !== '/') {
                if (str_starts_with($path, $base . '/') || $path === $base) {
                    $url = $path;
                } else {
                    $url = rtrim($base, '/') . '/' . ltrim($path, '/');
                }
            } else {
                $url = '/' . ltrim($path, '/');
            }
        }

        http_response_code(302);
        header("Location: {$url}");
        exit;
    }

    public static function baseUrl(): string
    {
        $base = Request::getBaseUrl();
        if ($base !== '') {
            return $base;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
            return '';
        }

        // If ends with /public, strip /public if accessed from parent
        if (str_ends_with($scriptDir, '/public')) {
            $parent = substr($scriptDir, 0, -7);
            if (!empty($_SERVER['REQUEST_URI']) && !str_contains($_SERVER['REQUEST_URI'], '/public')) {
                return $parent;
            }
        }

        return rtrim($scriptDir, '/');
    }

    public static function formatImageUrl(?string $path): string
    {
        return \App\Helpers\Uploader::imageUrl($path);
    }

    public function send(string $content, int $statusCode = 200): void
    {
        $this->statusCode = $statusCode;
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $content;
        exit;
    }
}
