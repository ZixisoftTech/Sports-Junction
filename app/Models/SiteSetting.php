<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class SiteSetting extends Model
{
    private static ?array $cacheKeyed = null;

    /**
     * Get a setting by key
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::getAllKeyed();
        return $all[$key] ?? $default;
    }

    /**
     * Get all settings as key => value map
     */
    public static function getAllKeyed(): array
    {
        if (self::$cacheKeyed !== null) {
            return self::$cacheKeyed;
        }

        $sql = "SELECT `setting_key`, `setting_value` FROM `site_settings`";
        $rows = self::fetchAll($sql);
        $keyed = [];
        foreach ($rows as $r) {
            $keyed[$r['setting_key']] = $r['setting_value'];
        }
        self::$cacheKeyed = $keyed;
        return $keyed;
    }

    /**
     * Get all rows with groups
     */
    public static function getAll(): array
    {
        return self::fetchAll("SELECT * FROM `site_settings` ORDER BY `setting_group`, `id` ASC");
    }

    /**
     * Set a single key value
     */
    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        $sql = "
            INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`, `updated_at`)
            VALUES (:k, :v, :g, NOW())
            ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW()
        ";
        self::execute($sql, [':k' => $key, ':v' => $value, ':g' => $group]);
        self::$cacheKeyed = null;
    }

    /**
     * Resolves the site logo URL safely for browser and web rendering.
     * If the configured logo is empty or the referenced file is physically missing on disk,
     * it falls back to the guaranteed default brand asset (/assets/images/logo.png).
     */
    public static function getLogoUrl(): string
    {
        $all = self::getAllKeyed();
        $stored = $all['site_logo'] ?? '';
        $baseUrl = \App\Core\Response::baseUrl();
        $defaultLogo = $baseUrl . '/assets/images/logo.png';

        if (empty($stored) || trim($stored) === '') {
            return $defaultLogo;
        }

        $trimmed = trim($stored);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }

        $cleanPath = '/' . ltrim($trimmed, '/');
        
        // Multi-root physical file existence check (supports standard public/ and public_html setups)
        $publicDir = dirname(__DIR__, 2) . '/public';
        $filePath = $publicDir . $cleanPath;
        $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/') : '';
        $docPath = $docRoot !== '' ? $docRoot . $cleanPath : '';
        $parentPath = dirname(__DIR__, 2) . $cleanPath;

        $exists = file_exists($filePath) 
            || ($docPath !== '' && file_exists($docPath)) 
            || file_exists($parentPath);

        // If file doesn't exist and is not a dynamic upload, fallback to default
        if (!$exists && !str_starts_with($cleanPath, '/uploads/settings/')) {
            return $defaultLogo;
        }

        if (!empty($baseUrl) && str_starts_with($cleanPath, $baseUrl . '/')) {
            return $cleanPath;
        }

        return $baseUrl . $cleanPath;
    }

    /**
     * Resolves an absolute, publicly accessible HTTPS logo URL specifically for emails.
     * Email clients (Gmail, Outlook, iOS Mail) require full HTTPS URLs and cannot access
     * localhost, relative paths, or private network addresses.
     * Always dynamically picks from the active 'site_logo' in Site Settings.
     */
    public static function getEmailLogoUrl(): string
    {
        $publicDomain = 'https://sportsjunction.in';
        $appUrl = trim((string) \App\Helpers\Env::get('APP_URL', ''));

        if (!empty($appUrl) && !str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1')) {
            $publicDomain = rtrim($appUrl, '/');
            if (str_starts_with($publicDomain, 'http://')) {
                $publicDomain = 'https://' . substr($publicDomain, 7);
            }
        } elseif (!empty($_SERVER['HTTP_HOST']) && !str_contains($_SERVER['HTTP_HOST'], 'localhost') && !str_contains($_SERVER['HTTP_HOST'], '127.0.0.1')) {
            $publicDomain = 'https://' . preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']);
        }

        $defaultLogo = $publicDomain . '/assets/images/logo.png';

        try {
            $all = self::getAllKeyed();
            $stored = trim((string)($all['site_logo'] ?? ''));

            if (empty($stored)) {
                return $defaultLogo . '?v=2';
            }

            $cacheBuster = 'v=' . substr(md5($stored), 0, 8);

            // If stored as a full URL
            if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
                $url = $stored;
                // If it points to localhost/127.0.0.1, swap out the domain
                if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
                    $path = parse_url($url, PHP_URL_PATH) ?? '';
                    $url = $publicDomain . '/' . ltrim($path, '/');
                }
                // Enforce HTTPS for email clients (Gmail blocks mixed HTTP content)
                if (str_starts_with($url, 'http://')) {
                    $url = 'https://' . substr($url, 7);
                }
                return str_contains($url, '?') ? $url : ($url . '?' . $cacheBuster);
            }

            // Relative path (e.g. /uploads/settings/logo_xxx.png or /assets/images/logo.png)
            $cleanPath = '/' . ltrim($stored, '/');
            $fullUrl = $publicDomain . $cleanPath;
            return str_contains($fullUrl, '?') ? $fullUrl : ($fullUrl . '?' . $cacheBuster);
        } catch (\Throwable $e) {
            return $defaultLogo . '?v=2';
        }
    }
}

