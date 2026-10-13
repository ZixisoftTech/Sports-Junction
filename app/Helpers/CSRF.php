<?php
declare(strict_types=1);

namespace App\Helpers;

class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    public static function generateToken(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_KEY);
        if (!$token || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public static function getToken(): string
    {
        return self::generateToken();
    }

    public static function token(): string
    {
        return self::generateToken();
    }

    public static function validate(?string $token): bool
    {
        Session::start();
        $stored = Session::get(self::SESSION_KEY);
        if (!$stored || !is_string($stored) || empty($token)) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function validateOrFail(?string $token): void
    {
        if (!self::validate($token)) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Security token mismatch or session expired. Please refresh the page and try again.'
            ]);
            exit;
        }
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
