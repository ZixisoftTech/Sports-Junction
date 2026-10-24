<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\CmsSeedData;
use PDO;
use Throwable;

class CmsPage extends Model
{
    public const COL_ABOUT = 'ABOUT';
    public const COL_PROTECTION = 'PROTECTION';
    public const COL_HELP = 'HELP';
    public const COL_SOURCING = 'SOURCING';
    public const COL_LEGAL = 'LEGAL';

    /**
     * Official 5 Footer Column Categories
     */
    public const CATEGORIES = [
        self::COL_ABOUT => 'About Sports Junction',
        self::COL_PROTECTION => 'Buyer & Seller Protection',
        self::COL_HELP => 'Help & Support',
        self::COL_SOURCING => 'Sourcing & Logistics',
        self::COL_LEGAL => 'Legal & Platform Policies'
    ];

    public const FOOTER_COLUMNS = self::CATEGORIES;

    /**
     * Self-healing: Ensure schema and all columns exist in database
     */
    public static function ensureSchema(): void
    {
        static $schemaChecked = false;
        if ($schemaChecked) return;
        $schemaChecked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `cms_pages` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `footer_label` VARCHAR(255) NULL,
                    `slug` VARCHAR(255) NOT NULL UNIQUE,
                    `column_category` VARCHAR(50) NOT NULL DEFAULT 'ABOUT',
                    `content` LONGTEXT NULL,
                    `featured_image` VARCHAR(255) NULL,
                    `external_url` VARCHAR(255) NULL,
                    `meta_title` VARCHAR(255) NULL,
                    `meta_description` TEXT NULL,
                    `sort_order` INT DEFAULT 0,
                    `show_in_footer` TINYINT(1) DEFAULT 1,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_slug (`slug`),
                    INDEX idx_category (`column_category`),
                    INDEX idx_active (`is_active`),
                    INDEX idx_footer (`show_in_footer`),
                    INDEX idx_sort (`sort_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $cols = self::fetchAll("DESCRIBE `cms_pages`");
            $colNames = array_column($cols, 'Field');

            if (!in_array('column_category', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `column_category` VARCHAR(50) NOT NULL DEFAULT 'ABOUT' AFTER `slug`");
            }
            if (!in_array('footer_label', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `footer_label` VARCHAR(255) NULL AFTER `title`");
            }
            if (!in_array('show_in_footer', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `show_in_footer` TINYINT(1) DEFAULT 1 AFTER `sort_order`");
            }
            if (!in_array('featured_image', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `featured_image` VARCHAR(255) NULL AFTER `content`");
            }
            if (!in_array('meta_title', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `meta_title` VARCHAR(255) NULL AFTER `external_url`");
            }
            if (!in_array('meta_description', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `meta_description` TEXT NULL AFTER `meta_title`");
            }
            if (!in_array('sort_order', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `sort_order` INT DEFAULT 0 AFTER `external_url`");
            }
            if (!in_array('is_active', $colNames, true)) {
                self::execute("ALTER TABLE `cms_pages` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `show_in_footer`");
            }
        } catch (\Throwable $e) {
            @error_log("CmsPage::ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Find page by slug (with automatic alias resolution)
     */
    public static function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        self::ensureSchema();
        self::ensureSeeded();

        try {
            $sql = "SELECT * FROM `cms_pages` WHERE `slug` = :s LIMIT 1";
            $page = self::fetch($sql, [':s' => $slug]);

            if (!$page) {
                // Check alias mappings
                $aliases = [
                    'manufacturing-sla'             => 'terms-manufacturing-sla',
                    'terms-manufacturing-sla'       => 'manufacturing-sla',
                    'seller-manufacturer-agreement' => 'seller-agreement',
                    'seller-agreement'              => 'seller-manufacturer-agreement',
                    'sitemap'                       => 'xml-sitemap',
                    'xml-sitemap'                   => 'sitemap',
                    'contact-us'                    => 'contact-us-support',
                    'contact-us-support'            => 'contact-us',
                ];
                if (isset($aliases[$slug])) {
                    $page = self::fetch($sql, [':s' => $aliases[$slug]]);
                }
            }

            return $page ?: null;
        } catch (\Throwable $e) {
            @error_log("CmsPage::findBySlug error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find page by ID
     */
    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        try {
            $sql = "SELECT * FROM `cms_pages` WHERE `id` = :id LIMIT 1";
            return self::fetch($sql, [':id' => $id]);
        } catch (\Throwable $e) {
            @error_log("CmsPage::findById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all pages
     */
    public static function all(bool $activeOnly = false): array
    {
        self::ensureSchema();
        self::ensureSeeded();
        try {
            $sql = "SELECT * FROM `cms_pages`";
            if ($activeOnly) {
                $sql .= " WHERE `is_active` = 1";
            }
            $sql .= " ORDER BY FIELD(column_category, 'ABOUT', 'PROTECTION', 'HELP', 'SOURCING', 'LEGAL'), `sort_order` ASC, `id` ASC";
            return self::fetchAll($sql);
        } catch (\Throwable $e) {
            @error_log("CmsPage::all error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get pages by category
     */
    public static function getByCategory(string $category, bool $activeOnly = true): array
    {
        self::ensureSchema();
        self::ensureSeeded();
        try {
            $sql = "SELECT * FROM `cms_pages` WHERE `column_category` = :cat";
            if ($activeOnly) {
                $sql .= " AND `is_active` = 1";
            }
            $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
            return self::fetchAll($sql, [':cat' => $category]);
        } catch (\Throwable $e) {
            @error_log("CmsPage::getByCategory error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get pages grouped by the 5 official footer columns
     */
    public static function getByColumns(bool $activeOnly = true, bool $footerOnly = true): array
    {
        self::ensureSchema();
        self::ensureSeeded();

        $grouped = [
            self::COL_ABOUT => [],
            self::COL_PROTECTION => [],
            self::COL_HELP => [],
            self::COL_SOURCING => [],
            self::COL_LEGAL => []
        ];

        try {
            $where = [];
            if ($activeOnly) {
                $where[] = "`is_active` = 1";
            }
            if ($footerOnly) {
                $where[] = "`show_in_footer` = 1";
            }

            $sql = "SELECT * FROM `cms_pages`";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
            $rows = self::fetchAll($sql);

            foreach ($rows as $row) {
                $cat = strtoupper(trim((string)($row['column_category'] ?? '')));
                if (isset($grouped[$cat])) {
                    $grouped[$cat][] = $row;
                }
            }
        } catch (\Throwable $e) {
            @error_log("CmsPage::getByColumns error: " . $e->getMessage());
        }

        return $grouped;
    }

    /**
     * Self-healing: Ensure all 21 requested footer pages are seeded in database
     */
    public static function ensureSeeded(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        self::ensureSchema();

        try {
            $count = (int) self::fetchColumn("SELECT COUNT(*) FROM cms_pages WHERE column_category IN ('ABOUT', 'PROTECTION', 'HELP', 'SOURCING', 'LEGAL')");
            if ($count < 21) {
                self::seedAll21Pages();
            }
        } catch (Throwable $e) {
            error_log("CmsPage::ensureSeeded error: " . $e->getMessage());
        }
    }

    /**
     * Seed or update all 21 requested pages across the 5 footer sections
     */
    public static function seedAll21Pages(): int
    {
        $seededCount = 0;
        try {
            $allSeeds = CmsSeedData::getAll();

            // Alias map to prevent duplicate records
            $aliases = [
                'manufacturing-sla'             => ['terms-manufacturing-sla'],
                'seller-manufacturer-agreement' => ['seller-agreement'],
                'sitemap'                       => ['xml-sitemap'],
                'contact-us'                    => ['contact-us-support'],
            ];

            foreach ($allSeeds as $slug => $data) {
                // Check if page exists by slug or aliases
                $checkSlugs = array_merge([$slug], $aliases[$slug] ?? []);
                $placeholders = implode(',', array_fill(0, count($checkSlugs), '?'));
                $existing = self::fetch("SELECT id, title, content, is_active FROM cms_pages WHERE slug IN ({$placeholders}) LIMIT 1", $checkSlugs);

                if ($existing) {
                    // Update existing record with column_category, sort_order, footer_label, and content if empty
                    $existingContent = trim((string)($existing['content'] ?? ''));
                    $useContent = (empty($existingContent) || strlen($existingContent) < 150) ? $data['content'] : $existingContent;

                    self::execute("
                        UPDATE cms_pages 
                        SET title = :title,
                            footer_label = :footer_label,
                            column_category = :cat,
                            content = :content,
                            meta_title = COALESCE(meta_title, :meta_title),
                            meta_description = COALESCE(meta_description, :meta_desc),
                            sort_order = :sort_order,
                            show_in_footer = 1,
                            is_active = 1,
                            updated_at = NOW()
                        WHERE id = :id
                    ", [
                        ':id'           => $existing['id'],
                        ':title'        => $data['title'],
                        ':footer_label' => $data['footer_label'] ?? $data['title'],
                        ':cat'          => $data['column_category'],
                        ':content'      => $useContent,
                        ':meta_title'   => $data['meta_title'],
                        ':meta_desc'    => $data['meta_description'],
                        ':sort_order'   => (int) $data['sort_order'],
                    ]);
                    $seededCount++;
                } else {
                    // Insert new record
                    self::execute("
                        INSERT INTO cms_pages (
                            title, footer_label, slug, column_category, content, 
                            meta_title, meta_description, sort_order, show_in_footer, 
                            is_active, created_at, updated_at
                        ) VALUES (
                            :title, :footer_label, :slug, :cat, :content, 
                            :meta_title, :meta_desc, :sort_order, 1, 
                            1, NOW(), NOW()
                        )
                    ", [
                        ':title'        => $data['title'],
                        ':footer_label' => $data['footer_label'] ?? $data['title'],
                        ':slug'         => $slug,
                        ':cat'          => $data['column_category'],
                        ':content'      => $data['content'],
                        ':meta_title'   => $data['meta_title'],
                        ':meta_desc'    => $data['meta_description'],
                        ':sort_order'   => (int) $data['sort_order'],
                    ]);
                    $seededCount++;
                }
            }

            // Hide old deprecated category sample pages from footer
            self::execute("UPDATE cms_pages SET show_in_footer = 0 WHERE column_category = 'TOP_SECTORS' OR column_category = 'PLATFORM_ADMIN'");
        } catch (Throwable $e) {
            error_log("CmsPage::seedAll21Pages error: " . $e->getMessage());
        }

        return $seededCount;
    }

    /**
     * Create new CMS page
     */
    public static function create(array $data): int
    {
        $cat = $data['column_category'] ?? $data['footer_column'] ?? self::COL_ABOUT;
        if (!array_key_exists($cat, self::CATEGORIES)) {
            $cat = self::COL_ABOUT;
        }

        $title = trim((string)($data['title'] ?? ''));
        $footerLabel = !empty($data['footer_label']) ? trim((string)$data['footer_label']) : $title;
        $slug = trim((string)($data['slug'] ?? ''));
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        $content = isset($data['content']) ? self::sanitizeContent((string)$data['content']) : null;

        $sql = "
            INSERT INTO `cms_pages` (
                `title`, `footer_label`, `slug`, `column_category`, `content`, 
                `featured_image`, `external_url`, `meta_title`, `meta_description`, 
                `sort_order`, `show_in_footer`, `is_active`, `created_at`, `updated_at`
            ) VALUES (
                :title, :footer_label, :slug, :cat, :content, 
                :featured_image, :url, :meta_title, :meta_description, 
                :order, :show_in_footer, :active, NOW(), NOW()
            )
        ";

        self::execute($sql, [
            ':title'          => $title,
            ':footer_label'   => $footerLabel,
            ':slug'           => $slug,
            ':cat'            => $cat,
            ':content'        => $content,
            ':featured_image' => !empty($data['featured_image']) ? trim((string)$data['featured_image']) : null,
            ':url'            => !empty($data['external_url']) ? trim((string)$data['external_url']) : null,
            ':meta_title'     => !empty($data['meta_title']) ? trim((string)$data['meta_title']) : null,
            ':meta_description' => !empty($data['meta_description']) ? trim((string)$data['meta_description']) : null,
            ':order'          => (int) ($data['sort_order'] ?? 0),
            ':show_in_footer' => !empty($data['show_in_footer']) ? 1 : 0,
            ':active'         => !empty($data['is_active']) ? 1 : 0
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
        $cat = $merged['column_category'] ?? $merged['footer_column'] ?? self::COL_ABOUT;
        if (!array_key_exists($cat, self::CATEGORIES)) {
            $cat = self::COL_ABOUT;
        }

        $content = isset($merged['content']) ? self::sanitizeContent((string)$merged['content']) : null;
        $footerLabel = !empty($merged['footer_label']) ? trim((string)$merged['footer_label']) : trim((string)$merged['title']);

        $sql = "
            UPDATE `cms_pages`
            SET 
                `title` = :title,
                `footer_label` = :footer_label,
                `slug` = :slug,
                `column_category` = :cat,
                `content` = :content,
                `featured_image` = :featured_image,
                `external_url` = :url,
                `meta_title` = :meta_title,
                `meta_description` = :meta_description,
                `sort_order` = :order,
                `show_in_footer` = :show_in_footer,
                `is_active` = :active,
                `updated_at` = NOW()
            WHERE `id` = :id
        ";

        self::execute($sql, [
            ':id'             => $id,
            ':title'          => trim((string)$merged['title']),
            ':footer_label'   => $footerLabel,
            ':slug'           => trim((string)$merged['slug']),
            ':cat'            => $cat,
            ':content'        => $content,
            ':featured_image' => !empty($merged['featured_image']) ? trim((string)$merged['featured_image']) : null,
            ':url'            => !empty($merged['external_url']) ? trim((string)$merged['external_url']) : null,
            ':meta_title'     => !empty($merged['meta_title']) ? trim((string)$merged['meta_title']) : null,
            ':meta_description' => !empty($merged['meta_description']) ? trim((string)$merged['meta_description']) : null,
            ':order'          => (int) ($merged['sort_order'] ?? 0),
            ':show_in_footer' => !empty($merged['show_in_footer']) ? 1 : 0,
            ':active'         => !empty($merged['is_active']) ? 1 : 0
        ]);

        return true;
    }

    /**
     * Toggle active/published status
     */
    public static function toggleStatus(int $id): bool
    {
        $existing = self::findById($id);
        if (!$existing) return false;

        $newVal = empty($existing['is_active']) ? 1 : 0;
        self::execute("UPDATE `cms_pages` SET `is_active` = :val, `updated_at` = NOW() WHERE `id` = :id", [
            ':val' => $newVal,
            ':id'  => $id
        ]);
        return true;
    }

    /**
     * Toggle footer visibility status
     */
    public static function toggleFooterVisibility(int $id): bool
    {
        $existing = self::findById($id);
        if (!$existing) return false;

        $newVal = empty($existing['show_in_footer']) ? 1 : 0;
        self::execute("UPDATE `cms_pages` SET `show_in_footer` = :val, `updated_at` = NOW() WHERE `id` = :id", [
            ':val' => $newVal,
            ':id'  => $id
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
        $clean = preg_replace('/\s+on[a-zA-Z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', (string)$clean);

        // 3. Remove javascript: or vbscript: or data: in href/src
        $clean = preg_replace('/(href|src)\s*=\s*(["\'])\s*(javascript|vbscript|data):.*?\2/i', '$1="#"', (string)$clean);

        // 4. Strip tags except safe semantic formatting elements
        $allowedTags = '<p><br><hr><b><strong><i><em><u><s><del><h2><h3><h4><h5><h6><ul><ol><li><a><table><thead><tbody><tfoot><tr><th><td><blockquote><code><pre><span><div><img>';
        return strip_tags((string)$clean, $allowedTags);
    }
}
