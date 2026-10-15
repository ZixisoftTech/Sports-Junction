<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class CmsPage extends Model
{
    public const CATEGORIES = [
        'ABOUT' => 'About Sports Junction',
        'ORDER_PROTECTION' => 'Order protection',
        'SOURCE' => 'Source on Sports Junction',
        'HELP_CENTER' => 'Help Center',
        'SELL' => 'Sell on Sports Junction'
    ];

    /**
     * Find page by slug
     */
    public static function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM `cms_pages` WHERE `slug` = :s LIMIT 1";
        return self::fetch($sql, [':s' => $slug]);
    }

    /**
     * Find page by ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "SELECT * FROM `cms_pages` WHERE `id` = :id LIMIT 1";
        return self::fetch($sql, [':id' => $id]);
    }

    /**
     * Get all pages
     */
    public static function all(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM `cms_pages`";
        if ($activeOnly) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `column_category` ASC, `sort_order` ASC, `title` ASC";
        return self::fetchAll($sql);
    }

    /**
     * Get pages by category
     */
    public static function getByCategory(string $category, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM `cms_pages` WHERE `column_category` = :cat";
        if ($activeOnly) {
            $sql .= " AND `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        return self::fetchAll($sql, [':cat' => $category]);
    }

    /**
     * Get pages grouped by column category for footer display
     */
    public static function getByColumns(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM `cms_pages`";
        if ($activeOnly) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        $rows = self::fetchAll($sql);

        $grouped = [
            'ABOUT' => [],
            'ORDER_PROTECTION' => [],
            'SOURCE' => [],
            'HELP_CENTER' => [],
            'SELL' => []
        ];

        foreach ($rows as $row) {
            $cat = $row['column_category'] ?? 'ABOUT';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $row;
        }

        return $grouped;
    }

    /**
     * Create new CMS page
     */
    public static function create(array $data): int
    {
        $sql = "
            INSERT INTO `cms_pages` (`title`, `slug`, `column_category`, `content`, `external_url`, `sort_order`, `is_active`, `created_at`)
            VALUES (:title, :slug, :cat, :content, :url, :order, :active, NOW())
        ";

        self::execute($sql, [
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':cat' => $data['column_category'] ?? 'ABOUT',
            ':content' => $data['content'] ?? null,
            ':url' => !empty($data['external_url']) ? $data['external_url'] : null,
            ':order' => (int) ($data['sort_order'] ?? 0),
            ':active' => !empty($data['is_active']) ? 1 : 0
        ]);

        return (int) self::db()->lastInsertId();
    }

    /**
     * Update existing CMS page
     */
    public static function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE `cms_pages`
            SET 
                `title` = :title,
                `slug` = :slug,
                `column_category` = :cat,
                `content` = :content,
                `external_url` = :url,
                `sort_order` = :order,
                `is_active` = :active,
                `updated_at` = NOW()
            WHERE `id` = :id
        ";

        self::execute($sql, [
            ':id' => $id,
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':cat' => $data['column_category'] ?? 'ABOUT',
            ':content' => $data['content'] ?? null,
            ':url' => !empty($data['external_url']) ? $data['external_url'] : null,
            ':order' => (int) ($data['sort_order'] ?? 0),
            ':active' => !empty($data['is_active']) ? 1 : 0
        ]);

        return true;
    }

    /**
     * Delete CMS page
     */
    public static function delete(int $id): bool
    {
        $sql = "DELETE FROM `cms_pages` WHERE `id` = :id";
        self::execute($sql, [':id' => $id]);
        return true;
    }
}
