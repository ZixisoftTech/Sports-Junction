<?php
declare(strict_types=1);

namespace App\Helpers;

class Env
{
    private static bool $loaded = false;

    public static function load(?string $path = null, bool $force = false): void
    {
        if (self::$loaded && !$force) {
            return;
        }

        if ($path === null || !file_exists($path)) {
            $candidate = dirname(__DIR__, 2) . '/.env';
            if (file_exists($candidate)) {
                $path = $candidate;
            } elseif ($path === null || !file_exists($path)) {
                return;
            }
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip surrounding quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                } else {
                    // Strip inline comment if unquoted
                    if (str_contains($value, ' #')) {
                        [$value] = explode(' #', $value, 2);
                        $value = trim($value);
                    }
                }

                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }
        return match (strtolower((string)$val)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $val,
        };
    }
}
