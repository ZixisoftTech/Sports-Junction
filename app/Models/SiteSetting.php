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
     * Set multiple key values at once
     */
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $val) {
            self::set((string) $key, is_null($val) ? null : (string) $val, $group);
        }
    }
}
