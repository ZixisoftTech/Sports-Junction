<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;
use App\Helpers\Uploader;

class Subcategory extends Model
{
    public static function all(): array
    {
        return self::fetchAll("
            SELECT s.*, c.name as category_name, c.slug as category_slug
            FROM subcategories s
            LEFT JOIN categories c ON s.category_id = c.id
            ORDER BY s.display_order ASC, s.name ASC
        ");
    }

    public static function allActive(): array
    {
        return self::fetchAll("
            SELECT s.*, c.name as category_name, c.slug as category_slug
            FROM subcategories s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.status = 'ACTIVE'
            ORDER BY s.display_order ASC, s.name ASC
        ");
    }

    public static function findById(int $id): ?array
    {
        return self::fetch("
            SELECT s.*, COALESCE(c.name, 'Unassigned') as category_name, c.slug as category_slug
            FROM subcategories s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.id = :id 
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::fetch("
            SELECT s.*, COALESCE(c.name, 'Unassigned') as category_name, c.slug as category_slug
            FROM subcategories s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.slug = :slug 
            LIMIT 1
        ", [':slug' => $slug]);
    }

    public static function byCategory(int $categoryId): array
    {
        return self::fetchAll("
            SELECT * FROM subcategories 
            WHERE category_id = :cat_id 
            ORDER BY display_order ASC, name ASC
        ", [':cat_id' => $categoryId]);
    }

    public static function byCategoryActive(int $categoryId): array
    {
        return self::fetchAll("
            SELECT s.*,
                   (
                       SELECT COUNT(DISTINCT p.id) 
                       FROM products p 
                       JOIN suppliers sp ON p.supplier_id = sp.id
                       LEFT JOIN product_subcategory_assignments psa ON psa.product_id = p.id
                       WHERE (p.subcategory_id = s.id OR psa.subcategory_id = s.id) 
                         AND p.status IN ('PUBLISHED', 'ACTIVE')
                         AND p.approval_status = 'APPROVED'
                         AND sp.status = 'ACTIVE'
                         AND sp.approval_status = 'APPROVED'
                   ) as product_count
            FROM subcategories s
            WHERE s.category_id = :cat_id AND s.status = 'ACTIVE'
            ORDER BY s.display_order ASC, s.name ASC
        ", [':cat_id' => $categoryId]);
    }

    public static function byCategoriesActive(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (empty($categoryIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        return self::fetchAll("
            SELECT s.*, c.name as category_name,
                   (
                       SELECT COUNT(DISTINCT p.id) 
                       FROM products p 
                       LEFT JOIN product_subcategory_assignments psa ON psa.product_id = p.id
                       WHERE (p.subcategory_id = s.id OR psa.subcategory_id = s.id) 
                         AND p.status IN ('PUBLISHED', 'ACTIVE')
                   ) as product_count
            FROM subcategories s
            JOIN categories c ON s.category_id = c.id
            WHERE s.category_id IN ({$placeholders}) AND s.status = 'ACTIVE'
            ORDER BY c.name ASC, s.display_order ASC, s.name ASC
        ", $categoryIds);
    }

    public static function create(array $data): int
    {
        $name = trim($data['name']);
        $slug = !empty($data['slug']) ? Uploader::slugify($data['slug']) : Uploader::slugify($name);

        $baseSlug = $slug;
        $counter = 1;
        while (self::fetch("SELECT id FROM subcategories WHERE slug = :slug LIMIT 1", [':slug' => $slug])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        self::execute("
            INSERT INTO subcategories (category_id, name, slug, image, description, status, display_order, created_at, updated_at)
            VALUES (:category_id, :name, :slug, :image, :description, :status, :display_order, NOW(), NOW())
        ", [
            ':category_id'   => (int) $data['category_id'],
            ':name'          => $name,
            ':slug'          => $slug,
            ':image'         => !empty($data['image']) ? trim($data['image']) : null,
            ':description'   => !empty($data['description']) ? trim($data['description']) : null,
            ':status'        => in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : 'ACTIVE',
            ':display_order' => (int) ($data['display_order'] ?? 0),
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'Subcategory', $id, $name, null, 'Created subcategory');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        $name = isset($data['name']) ? trim($data['name']) : $existing['name'];
        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : (int) $existing['category_id'];
        $slug = !empty($data['slug']) ? Uploader::slugify($data['slug']) : (!empty($data['name']) ? Uploader::slugify($name) : $existing['slug']);

        $baseSlug = $slug;
        $counter = 1;
        while (self::fetch("SELECT id FROM subcategories WHERE slug = :slug AND id != :id LIMIT 1", [':slug' => $slug, ':id' => $id])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $image = !empty($data['image']) ? trim($data['image']) : $existing['image'];
        $description = array_key_exists('description', $data) ? (!empty($data['description']) ? trim($data['description']) : null) : $existing['description'];
        $status = in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : ($existing['status'] ?? 'ACTIVE');
        $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : (int) ($existing['display_order'] ?? 0);

        self::execute("
            UPDATE subcategories 
            SET category_id = :category_id, name = :name, slug = :slug, image = :image, 
                description = :description, status = :status, display_order = :display_order, updated_at = NOW()
            WHERE id = :id
        ", [
            ':category_id'   => $categoryId,
            ':name'          => $name,
            ':slug'          => $slug,
            ':image'         => $image,
            ':description'   => $description,
            ':status'        => $status,
            ':display_order' => $displayOrder,
            ':id'            => $id,
        ]);

        AuditLogger::log('UPDATE', 'Subcategory', $id, $name, null, 'Updated subcategory');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $sub = self::findById($id);
        if (!$sub) {
            return false;
        }

        self::execute("UPDATE subcategories SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'Subcategory', $id, $sub['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function getDependencies(int $id): array
    {
        $products = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE subcategory_id = :id", [':id' => $id]);

        return [
            'total'             => $products,
            'products'          => $products,
            'has_active'        => ($products > 0),
            'has_protected'     => ($products > 0),
            'can_cascade'       => false,
            'can_direct_delete' => ($products === 0),
        ];
    }

    public static function hardDelete(int $id): array
    {
        $sub = self::findById($id);
        if (!$sub) {
            return ['success' => false, 'message' => 'Subcategory not found.'];
        }

        $deps = self::getDependencies($id);
        if ($deps['has_protected']) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$sub['name']}' because {$deps['products']} products are assigned to it. Please reassign those products first."
            ];
        }

        self::beginTransaction();
        try {
            self::execute("DELETE FROM subcategories WHERE id = :id", [':id' => $id]);
            self::commit();

            AuditLogger::log('DELETE', 'Subcategory', $id, $sub['name'], ['subcategories' => 1], 'Permanently deleted subcategory');

            return [
                'success'        => true,
                'affectedCounts' => ['subcategories' => 1],
                'message'        => "Subcategory '{$sub['name']}' permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete subcategory: ' . $e->getMessage()];
        }
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));
        $categoryFilter = (int)($params['category_id'] ?? 0);

        // Client columns in subcategories.php:
        // 0: id, 1: image, 2: name, 3: category_name, 4: slug, 5: display_order, 6: products_count, 7: status, 8: actions
        $columnMap = [
            0 => 's.id',
            1 => 's.id',
            2 => 's.name',
            3 => 'category_name',
            4 => 's.slug',
            5 => 's.display_order',
            6 => 'products_count',
            7 => 's.status',
            8 => 's.created_at',
        ];
        $orderColIdx = isset($params['order'][0]['column']) ? (int)$params['order'][0]['column'] : 5;
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columnMap[$orderColIdx] ?? 's.display_order';
        $orderBy = ($orderCol === 's.id') ? "s.id {$orderDir}" : "{$orderCol} {$orderDir}, s.id ASC";

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(s.name LIKE :s1 OR s.slug LIKE :s2 OR c.name LIKE :s3)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "s.status = :status";
            $bindings[':status'] = $statusFilter;
        }

        if ($categoryFilter > 0) {
            $where[] = "s.category_id = :cat_filter";
            $bindings[':cat_filter'] = $categoryFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM subcategories");
        $filteredRecords = (int) self::fetchColumn("
            SELECT COUNT(*) FROM subcategories s 
            LEFT JOIN categories c ON s.category_id = c.id 
            {$whereClause}
        ", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT s.id, s.name, s.slug, s.image, s.description, s.display_order, s.status, s.created_at,
                       c.id as category_id, COALESCE(c.name, 'Unassigned') as category_name,
                       (SELECT COUNT(*) FROM products p WHERE p.subcategory_id = s.id) as products_count
                FROM subcategories s 
                LEFT JOIN categories c ON s.category_id = c.id
                {$whereClause} 
                ORDER BY {$orderBy} 
                {$limitClause}";

        $data = self::fetchAll($sql, $bindings);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ];
    }
}
