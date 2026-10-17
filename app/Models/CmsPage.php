<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class CmsPage extends Model
{
    public const CATEGORIES = [
        'TOP_SECTORS' => 'Top Sectors',
        'SOURCING' => 'Sourcing Solutions',
        'PLATFORM_ADMIN' => 'Platform & Admin'
    ];

    /**
     * Standard 3 footer columns in order matching official design
     */
    public const FOOTER_COLUMNS = [
        'TOP_SECTORS' => 'Top Sectors',
        'SOURCING' => 'Sourcing Solutions',
        'PLATFORM_ADMIN' => 'Platform & Admin'
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
            'TOP_SECTORS' => [],
            'SOURCING' => [],
            'PLATFORM_ADMIN' => []
        ];

        foreach ($rows as $row) {
            $cat = $row['column_category'] ?? '';
            if (isset($grouped[$cat])) {
                $grouped[$cat][] = $row;
            }
        }

        return $grouped;
    }

    /**
     * Create new CMS page
     */
    public static function create(array $data): int
    {
        $cat = $data['column_category'] ?? $data['footer_column'] ?? $data['category'] ?? 'TOP_SECTORS';
        if (!array_key_exists($cat, self::CATEGORIES)) {
            $cat = 'TOP_SECTORS';
        }

        $sql = "
            INSERT INTO `cms_pages` (`title`, `slug`, `column_category`, `content`, `external_url`, `meta_title`, `meta_description`, `sort_order`, `is_active`, `created_at`)
            VALUES (:title, :slug, :cat, :content, :url, :meta_title, :meta_description, :order, :active, NOW())
        ";

        self::execute($sql, [
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':cat' => $cat,
            ':content' => $data['content'] ?? null,
            ':url' => !empty($data['external_url']) ? $data['external_url'] : null,
            ':meta_title' => !empty($data['meta_title']) ? trim((string)$data['meta_title']) : null,
            ':meta_description' => !empty($data['meta_description']) ? trim((string)$data['meta_description']) : null,
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
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        $merged = array_merge($existing, $data);
        $cat = $merged['column_category'] ?? $merged['footer_column'] ?? $merged['category'] ?? 'TOP_SECTORS';
        if (!array_key_exists($cat, self::CATEGORIES)) {
            $cat = 'TOP_SECTORS';
        }

        $sql = "
            UPDATE `cms_pages`
            SET 
                `title` = :title,
                `slug` = :slug,
                `column_category` = :cat,
                `content` = :content,
                `external_url` = :url,
                `meta_title` = :meta_title,
                `meta_description` = :meta_description,
                `sort_order` = :order,
                `is_active` = :active,
                `updated_at` = NOW()
            WHERE `id` = :id
        ";

        self::execute($sql, [
            ':id' => $id,
            ':title' => $merged['title'],
            ':slug' => $merged['slug'],
            ':cat' => $cat,
            ':content' => $merged['content'] ?? null,
            ':url' => !empty($merged['external_url']) ? $merged['external_url'] : null,
            ':meta_title' => !empty($merged['meta_title']) ? trim((string)$merged['meta_title']) : null,
            ':meta_description' => !empty($merged['meta_description']) ? trim((string)$merged['meta_description']) : null,
            ':order' => (int) ($merged['sort_order'] ?? 0),
            ':active' => !empty($merged['is_active']) ? 1 : 0
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

    /**
     * Sanitize HTML content against stored XSS while preserving safe formatting
     */
    public static function sanitizeContent(string $html): string
    {
        // 1. Remove dangerous blocks: script, iframe, object, embed, applet, style
        $clean = preg_replace('#<(script|iframe|object|embed|applet|style)[^>]*>.*?</\1>#is', '', $html);

        // 2. Remove inline event handlers (onload, onclick, onerror, onmouseover, etc.)
        $clean = preg_replace('/\s+on[a-zA-Z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', $clean);

        // 3. Remove javascript: or vbscript: or data: in href/src
        $clean = preg_replace('/(href|src)\s*=\s*(["\'])\s*(javascript|vbscript|data):.*?\2/i', '$1="#"', $clean);

        // 4. Strip tags except safe semantic formatting elements
        $allowedTags = '<p><br><hr><b><strong><i><em><u><s><del><h2><h3><h4><h5><h6><ul><ol><li><a><table><thead><tbody><tfoot><tr><th><td><blockquote><code><pre><span><div>';
        return strip_tags($clean, $allowedTags);
    }
}
