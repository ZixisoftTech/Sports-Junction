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
     * Resolves the site logo URL safely.
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
        // Check physical file existence in public directory
        $publicDir = dirname(__DIR__, 2) . '/public';
        $filePath = $publicDir . $cleanPath;
        if (!file_exists($filePath)) {
            return $defaultLogo;
        }

        if (!empty($baseUrl) && str_starts_with($cleanPath, $baseUrl . '/')) {
            return $cleanPath;
        }

        return $baseUrl . $cleanPath;
    }
}
