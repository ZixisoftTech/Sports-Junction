<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;
use App\Helpers\Uploader;

class Category extends Model
{
    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        $cat = self::fetch("SELECT * FROM categories WHERE id = :id LIMIT 1", [':id' => $id]);
        if ($cat && !isset($cat['show_on_homepage'])) {
            $cat['show_on_homepage'] = 0;
        }
        return $cat;
    }

    public static function findBySlug(string $slug): ?array
    {
        self::ensureSchema();
        $cat = self::fetch("SELECT * FROM categories WHERE slug = :slug LIMIT 1", [':slug' => $slug]);
        if ($cat && !isset($cat['show_on_homepage'])) {
            $cat['show_on_homepage'] = 0;
        }
        return $cat;
    }

    public static function all(): array
    {
        return self::allActive();
    }

    public static function allActive(): array
    {
        self::ensureSchema();
        $categories = self::fetchAll("
            SELECT c.*, 
                   (
                       SELECT COUNT(DISTINCT p.id) 
                       FROM products p 
                       JOIN suppliers sp ON p.supplier_id = sp.id
                       LEFT JOIN product_category_assignments pca ON pca.product_id = p.id
                       WHERE (p.category_id = c.id OR pca.category_id = c.id) 
                         AND p.status IN ('PUBLISHED', 'ACTIVE')
                         AND p.approval_status = 'APPROVED'
                         AND sp.status = 'ACTIVE'
                         AND sp.approval_status = 'APPROVED'
                   ) as product_count
            FROM categories c 
            WHERE c.status = 'ACTIVE' 
            ORDER BY c.display_order ASC, c.name ASC
        ");

        foreach ($categories as &$cat) {
            $cat['subcategories'] = Subcategory::byCategoryActive((int) $cat['id']);
        }
        unset($cat);

        return $categories;
    }

    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            $col = self::fetch("SHOW COLUMNS FROM categories LIKE 'show_on_homepage'");
            if (!$col) {
                self::execute("ALTER TABLE categories ADD COLUMN show_on_homepage TINYINT(1) NOT NULL DEFAULT 0 AFTER status, ADD INDEX idx_show_on_homepage (show_on_homepage)");
                self::execute("UPDATE categories SET show_on_homepage = 1 WHERE status = 'ACTIVE' ORDER BY display_order ASC, name ASC LIMIT 8");
            }
        } catch (\Throwable $e) {
            // Fail gracefully
        }
    }

    /**
     * Get categories configured for the public Homepage 'Popular Sports Sourcing Categories' section
     */
    public static function getHomepageCategories(): array
    {
        self::ensureSchema();

        // 1. Check if section is enabled (default enabled)
        $enabled = SiteSetting::get('homepage_categories_enabled', '1');
        if ($enabled === '0' || strtolower((string)$enabled) === 'false') {
            return [];
        }

        // 2. Get maximum count (default 8)
        $maxCount = (int) SiteSetting::get('homepage_categories_max', '8');
        if ($maxCount <= 0) {
            $maxCount = 8;
        }

        // 3. Fetch active categories explicitly enabled for homepage display with product count
        $categories = self::fetchAll("
            SELECT c.*, 
                   (
                       SELECT COUNT(DISTINCT p.id) 
                       FROM products p 
                       JOIN suppliers sp ON p.supplier_id = sp.id
                       LEFT JOIN product_category_assignments pca ON pca.product_id = p.id
                       WHERE (p.category_id = c.id OR pca.category_id = c.id) 
                         AND p.status IN ('PUBLISHED', 'ACTIVE')
                         AND p.approval_status = 'APPROVED'
                         AND sp.status = 'ACTIVE'
                         AND sp.approval_status = 'APPROVED'
                   ) as product_count
            FROM categories c 
            WHERE c.status = 'ACTIVE' AND c.show_on_homepage = 1
            ORDER BY c.display_order ASC, c.name ASC
        ");

        if (empty($categories)) {
            return [];
        }

        $allById = [];
        foreach ($categories as $c) {
            $allById[(int)$c['id']] = $c;
        }

        // 4. Check custom selected categories
        $selectedRaw = SiteSetting::get('homepage_categories_selected', '');
        if (!empty($selectedRaw)) {
            $selectedIds = array_filter(array_map('intval', explode(',', $selectedRaw)));
            if (!empty($selectedIds)) {
                $ordered = [];
                foreach ($selectedIds as $cid) {
                    if (isset($allById[$cid])) {
                        $ordered[] = $allById[$cid];
                    }
                }
                if (!empty($ordered)) {
                    return array_slice($ordered, 0, $maxCount);
                }
            }
        }

        // Fallback: Default top categories ordered by display_order
        return array_slice($categories, 0, $maxCount);
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $name = trim($data['name']);
        $slug = !empty($data['slug']) ? Uploader::slugify($data['slug']) : Uploader::slugify($name);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (self::fetch("SELECT id FROM categories WHERE slug = :slug LIMIT 1", [':slug' => $slug])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $showOnHomepage = !empty($data['show_on_homepage']) && ($data['show_on_homepage'] === 1 || $data['show_on_homepage'] === '1' || $data['show_on_homepage'] === true || $data['show_on_homepage'] === 'on') ? 1 : 0;

        self::execute("
            INSERT INTO categories (name, slug, image, icon, description, status, show_on_homepage, display_order, created_at, updated_at)
            VALUES (:name, :slug, :image, :icon, :description, :status, :show_on_homepage, :display_order, NOW(), NOW())
        ", [
            ':name'             => $name,
            ':slug'             => $slug,
            ':image'            => !empty($data['image']) ? trim($data['image']) : null,
            ':icon'             => !empty($data['icon']) ? trim($data['icon']) : 'sports_tennis',
            ':description'      => !empty($data['description']) ? trim($data['description']) : null,
            ':status'           => in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : 'ACTIVE',
            ':show_on_homepage' => $showOnHomepage,
            ':display_order'    => (int) ($data['display_order'] ?? 0),
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'Category', $id, $name, null, 'Created category');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        self::ensureSchema();

        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        $name = trim($data['name']);
        $slug = !empty($data['slug']) ? Uploader::slugify($data['slug']) : Uploader::slugify($name);

        // Ensure unique slug except self
        $baseSlug = $slug;
        $counter = 1;
        while ($conflict = self::fetch("SELECT id FROM categories WHERE slug = :slug AND id != :id LIMIT 1", [':slug' => $slug, ':id' => $id])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $image = !empty($data['image']) ? trim($data['image']) : $existing['image'];
        $icon = !empty($data['icon']) ? trim($data['icon']) : ($existing['icon'] ?? 'sports_tennis');

        $showOnHomepage = isset($data['show_on_homepage'])
            ? (($data['show_on_homepage'] === 1 || $data['show_on_homepage'] === '1' || $data['show_on_homepage'] === true || $data['show_on_homepage'] === 'on') ? 1 : 0)
            : (int)($existing['show_on_homepage'] ?? 0);

        self::execute("
            UPDATE categories 
            SET name = :name, slug = :slug, image = :image, icon = :icon, 
                description = :description, status = :status, show_on_homepage = :show_on_homepage, 
                display_order = :display_order, updated_at = NOW()
            WHERE id = :id
        ", [
            ':name'             => $name,
            ':slug'             => $slug,
            ':image'            => $image,
            ':icon'             => $icon,
            ':description'      => !empty($data['description']) ? trim($data['description']) : null,
            ':status'           => in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : 'ACTIVE',
            ':show_on_homepage' => $showOnHomepage,
            ':display_order'    => (int) ($data['display_order'] ?? 0),
            ':id'               => $id,
        ]);

        AuditLogger::log('UPDATE', 'Category', $id, $name, null, 'Updated category');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $cat = self::findById($id);
        if (!$cat) {
            return false;
        }

        self::execute("UPDATE categories SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'Category', $id, $cat['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function getDependencies(int $id): array
    {
        $subcategories = (int) self::fetchColumn("SELECT COUNT(*) FROM subcategories WHERE category_id = :id", [':id' => $id]);
        $products = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE category_id = :id", [':id' => $id]);

        return [
            'total'             => $subcategories + $products,
            'subcategories'     => $subcategories,
            'products'          => $products,
            'has_active'        => ($subcategories > 0 || $products > 0),
            'has_protected'     => ($products > 0),
            'can_cascade'       => ($products === 0 && $subcategories > 0),
            'can_direct_delete' => ($subcategories === 0 && $products === 0),
        ];
    }

    public static function hardDelete(int $id, bool $cascade = false): array
    {
        $cat = self::findById($id);
        if (!$cat) {
            return ['success' => false, 'message' => 'Category not found.'];
        }

        $deps = self::getDependencies($id);
        if ($deps['has_protected'] && !$cascade) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$cat['name']}' because {$deps['products']} products belong to it. Please reassign or delete the products first."
            ];
        }

        if ($deps['subcategories'] > 0 && !$cascade) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$cat['name']}' because it has {$deps['subcategories']} subcategories. Confirm cascade delete or remove subcategories first."
            ];
        }

        self::beginTransaction();
        try {
            if ($cascade) {
                // Delete products under subcategories or category
                self::execute("DELETE FROM products WHERE category_id = :id", [':id' => $id]);
                self::execute("DELETE FROM subcategories WHERE category_id = :id", [':id' => $id]);
            }

            self::execute("DELETE FROM categories WHERE id = :id", [':id' => $id]);
            self::commit();

            AuditLogger::log('DELETE', 'Category', $id, $cat['name'], [
                'categories' => 1,
                'subcategories' => $deps['subcategories'],
                'products' => $deps['products']
            ], 'Permanently deleted category');

            return [
                'success' => true,
                'affectedCounts' => ['categories' => 1, 'subcategories' => $deps['subcategories']],
                'message' => "Category '{$cat['name']}' permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete category: ' . $e->getMessage()];
        }
    }

    public static function getDataTable(array $params): array
    {
        self::ensureSchema();

        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));

        // Client columns in categories.php:
        // 0: id, 1: image, 2: name, 3: slug, 4: display_order, 5: subcategories_count, 6: products_count, 7: status, 8: actions
        $columnMap = [
            0 => 'c.id',
            1 => 'c.id',
            2 => 'c.name',
            3 => 'c.slug',
            4 => 'c.display_order',
            5 => 'subcategories_count',
            6 => 'products_count',
            7 => 'c.status',
            8 => 'c.created_at',
        ];
        $orderColIdx = (int)($params['order'][0]['column'] ?? 4);
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columnMap[$orderColIdx] ?? 'c.display_order';

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(c.name LIKE :s1 OR c.slug LIKE :s2 OR c.description LIKE :s3)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "c.status = :status";
            $bindings[':status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM categories");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM categories c {$whereClause}", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM subcategories s WHERE s.category_id = c.id) as subcategories_count,
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as products_count
                FROM categories c 
                {$whereClause} 
                ORDER BY {$orderCol} {$orderDir} 
                {$limitClause}";

        $data = self::fetchAll($sql, $bindings);

        foreach ($data as &$row) {
            if (!isset($row['show_on_homepage'])) {
                $row['show_on_homepage'] = 0;
            }
        }
        unset($row);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ];
    }
}
