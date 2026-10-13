<?php
declare(strict_types=1);

namespace App\Helpers;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (headers_sent()) {
            if (session_status() !== PHP_SESSION_ACTIVE && !isset($_SESSION)) {
                $_SESSION = [];
            }
            self::$started = true;
            return;
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_set_cookie_params([
            'lifetime' => (int) Env::get('SESSION_LIFETIME', 7200),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_name('SPORTS_JUNCTION_SESSID');
        @session_start();
        self::$started = true;

        // Session expiration / inactivity check
        $now = time();
        $lifetime = (int) Env::get('SESSION_LIFETIME', 7200);
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > $lifetime)) {
            self::destroy();
            session_start();
            self::$started = true;
        }
        $_SESSION['last_activity'] = $now;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate(bool $deleteOld = true): void
    {
        self::start();
        session_regenerate_id($deleteOld);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
            self::$started = false;
        }
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $flashValue = $_SESSION['_flash'][$key] ?? null;
        if (isset($_SESSION['_flash'][$key])) {
            unset($_SESSION['_flash'][$key]);
        }
        return $flashValue;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }
}
