<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;
use App\Helpers\Uploader;

class Product extends Model
{
    public static function findById(int $id): ?array
    {
        $product = self::fetch("
            SELECT p.*,
                   p.title as name,
                   c.name as category_name, c.slug as category_slug,
                   s.name as subcategory_name, s.slug as subcategory_slug,
                   pu.name as price_unit_name, pu.symbol as price_unit_symbol,
                   mu.name as moq_unit_name, mu.symbol as moq_unit_symbol,
                   sp.company_name as supplier_name, sp.email as supplier_email, sp.phone as supplier_phone,
                   sp.address as supplier_address,
                   co.name as country_name, co.iso_alpha2 as country_code,
                   st.name as state_name,
                   ct.name as city_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN countries co ON p.country_id = co.id
            LEFT JOIN states st ON p.state_id = st.id
            LEFT JOIN cities ct ON p.city_id = ct.id
            WHERE p.id = :id 
            LIMIT 1
        ", [':id' => $id]);

        if (!$product) {
            return null;
        }

        $product['images'] = self::getImages($id);
        $product['category_ids'] = self::getCategoryIds($id);
        $product['subcategory_ids'] = self::getSubcategoryIds($id);
        $product['categories'] = self::getCategories($id);
        $product['subcategories'] = self::getSubcategories($id);
        if (empty($product['category_name']) && !empty($product['categories'][0]['name'])) {
            $product['category_name'] = $product['categories'][0]['name'];
            $product['category_slug'] = $product['categories'][0]['slug'];
        }
        if (empty($product['subcategory_name']) && !empty($product['subcategories'][0]['name'])) {
            $product['subcategory_name'] = $product['subcategories'][0]['name'];
            $product['subcategory_slug'] = $product['subcategories'][0]['slug'];
        }
        $product['all_category_names'] = !empty($product['categories']) 
            ? implode(', ', array_column($product['categories'], 'name')) 
            : ($product['category_name'] ?? '');
        $product['all_subcategory_names'] = !empty($product['subcategories']) 
            ? implode(', ', array_column($product['subcategories'], 'name')) 
            : ($product['subcategory_name'] ?? '');
        $product['certificates'] = ProductCertificate::getForProduct($id);
        return $product;
    }

    public static function isOwnedByCurrentSupplier(mixed $product): bool
    {
        return Supplier::isOwnedByCurrentSupplier($product);
    }

    public static function findBySlug(string $slug): ?array
    {
        $product = self::fetch("
            SELECT p.*,
                   p.title as name,
                   c.name as category_name, c.slug as category_slug,
                   s.name as subcategory_name, s.slug as subcategory_slug,
                   pu.name as price_unit_name, pu.symbol as price_unit_symbol,
                   mu.name as moq_unit_name, mu.symbol as moq_unit_symbol,
                   sp.company_name as supplier_name, sp.email as supplier_email, sp.phone as supplier_phone,
                   sp.address as supplier_address,
                   co.name as country_name, co.iso_alpha2 as country_code,
                   st.name as state_name,
                   ct.name as city_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN countries co ON p.country_id = co.id
            LEFT JOIN states st ON p.state_id = st.id
            LEFT JOIN cities ct ON p.city_id = ct.id
            WHERE p.slug = :slug 
              AND p.status IN ('PUBLISHED', 'ACTIVE')
              AND p.approval_status = 'APPROVED'
              AND (sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')
              AND (c.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_category_assignments pca JOIN categories c2 ON pca.category_id = c2.id WHERE pca.product_id = p.id AND c2.status = 'ACTIVE'))
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_subcategory_assignments psa JOIN subcategories s2 ON psa.subcategory_id = s2.id WHERE psa.product_id = p.id AND s2.status = 'ACTIVE'))
            LIMIT 1
        ", [':slug' => $slug]);

        if (!$product) {
            return null;
        }

        $prodId = (int)$product['id'];
        $product['images'] = self::getImages($prodId);
        $product['category_ids'] = self::getCategoryIds($prodId);
        $product['subcategory_ids'] = self::getSubcategoryIds($prodId);
        $product['categories'] = self::getCategories($prodId);
        $product['subcategories'] = self::getSubcategories($prodId);
        if (empty($product['category_name']) && !empty($product['categories'][0]['name'])) {
            $product['category_name'] = $product['categories'][0]['name'];
            $product['category_slug'] = $product['categories'][0]['slug'];
        }
        if (empty($product['subcategory_name']) && !empty($product['subcategories'][0]['name'])) {
            $product['subcategory_name'] = $product['subcategories'][0]['name'];
            $product['subcategory_slug'] = $product['subcategories'][0]['slug'];
        }
        $product['all_category_names'] = !empty($product['categories']) 
            ? implode(', ', array_column($product['categories'], 'name')) 
            : ($product['category_name'] ?? '');
        $product['all_subcategory_names'] = !empty($product['subcategories']) 
            ? implode(', ', array_column($product['subcategories'], 'name')) 
            : ($product['subcategory_name'] ?? '');
        $product['certificates'] = ProductCertificate::getForProduct($prodId);
        return $product;
    }

    public static function getCategories(int $productId): array
    {
        return self::fetchAll("
            SELECT c.*, pca.is_primary
            FROM categories c
            JOIN product_category_assignments pca ON c.id = pca.category_id
            WHERE pca.product_id = :pid
            ORDER BY pca.is_primary DESC, c.name ASC
        ", [':pid' => $productId]);
    }

    public static function getCategoryIds(int $productId): array
    {
        $rows = self::fetchAll("
            SELECT category_id FROM product_category_assignments 
            WHERE product_id = :pid 
            ORDER BY is_primary DESC, category_id ASC
        ", [':pid' => $productId]);
        return array_map(fn($r) => (int)$r['category_id'], $rows);
    }

    public static function getSubcategories(int $productId): array
    {
        return self::fetchAll("
            SELECT s.*, psa.is_primary, c.name as category_name
            FROM subcategories s
            JOIN product_subcategory_assignments psa ON s.id = psa.subcategory_id
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE psa.product_id = :pid
            ORDER BY psa.is_primary DESC, s.name ASC
        ", [':pid' => $productId]);
    }

    public static function getSubcategoryIds(int $productId): array
    {
        $rows = self::fetchAll("
            SELECT subcategory_id FROM product_subcategory_assignments 
            WHERE product_id = :pid 
            ORDER BY is_primary DESC, subcategory_id ASC
        ", [':pid' => $productId]);
        return array_map(fn($r) => (int)$r['subcategory_id'], $rows);
    }

    public static function syncCategories(int $productId, array $categoryIds, ?int $primaryCategoryId = null): void
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (empty($categoryIds)) {
            return;
        }
        if ($primaryCategoryId === null || !in_array($primaryCategoryId, $categoryIds, true)) {
            $primaryCategoryId = $categoryIds[0];
        }

        self::execute("DELETE FROM product_category_assignments WHERE product_id = :pid", [':pid' => $productId]);
        foreach ($categoryIds as $catId) {
            self::execute("
                INSERT INTO product_category_assignments (product_id, category_id, is_primary)
                VALUES (:pid, :cid, :is_prim)
            ", [
                ':pid'     => $productId,
                ':cid'     => $catId,
                ':is_prim' => ($catId === $primaryCategoryId ? 1 : 0),
            ]);
        }
        self::execute("UPDATE products SET category_id = :cid WHERE id = :pid", [
            ':cid' => $primaryCategoryId,
            ':pid' => $productId,
        ]);
    }

    public static function syncSubcategories(int $productId, array $subcategoryIds, ?int $primarySubcategoryId = null): void
    {
        $subcategoryIds = array_values(array_unique(array_filter(array_map('intval', $subcategoryIds))));
        self::execute("DELETE FROM product_subcategory_assignments WHERE product_id = :pid", [':pid' => $productId]);
        if (empty($subcategoryIds)) {
            self::execute("UPDATE products SET subcategory_id = NULL WHERE id = :pid", [':pid' => $productId]);
            return;
        }
        if ($primarySubcategoryId === null || !in_array($primarySubcategoryId, $subcategoryIds, true)) {
            $primarySubcategoryId = $subcategoryIds[0];
        }

        foreach ($subcategoryIds as $subId) {
            self::execute("
                INSERT INTO product_subcategory_assignments (product_id, subcategory_id, is_primary)
                VALUES (:pid, :sid, :is_prim)
            ", [
                ':pid'     => $productId,
                ':sid'     => $subId,
                ':is_prim' => ($subId === $primarySubcategoryId ? 1 : 0),
            ]);
        }
        self::execute("UPDATE products SET subcategory_id = :sid WHERE id = :pid", [
            ':sid' => $primarySubcategoryId,
            ':pid' => $productId,
        ]);
    }

    public static function getImages(int $productId): array
    {
        $rows = self::fetchAll("
            SELECT * FROM product_images 
            WHERE product_id = :id 
            ORDER BY is_primary DESC, display_order ASC, id ASC
        ", [':id' => $productId]);

        foreach ($rows as &$r) {
            $r['image_url'] = Uploader::imageUrl($r['image_path'] ?? '');
        }
        return $rows;
    }

    public static function featuredProducts(int $limit = 8): array
    {
        $products = self::fetchAll("
            SELECT p.*,
                   p.title as name,
                   COALESCE(c.name, (SELECT c_sub.name FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_name,
                   COALESCE(c.slug, (SELECT c_sub.slug FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_slug,
                   (SELECT GROUP_CONCAT(c_all.name ORDER BY pca_all.is_primary DESC, c_all.name ASC SEPARATOR ', ') FROM product_category_assignments pca_all JOIN categories c_all ON pca_all.category_id = c_all.id WHERE pca_all.product_id = p.id) as all_category_names,
                   s.name as subcategory_name, s.slug as subcategory_slug,
                   (SELECT GROUP_CONCAT(s_all.name ORDER BY psa_all.is_primary DESC, s_all.name ASC SEPARATOR ', ') FROM product_subcategory_assignments psa_all JOIN subcategories s_all ON psa_all.subcategory_id = s_all.id WHERE psa_all.product_id = p.id) as all_subcategory_names,
                   pu.symbol as price_unit_symbol,
                   mu.name as moq_unit_name,
                   sp.company_name as supplier_name,
                   co.name as country_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN countries co ON p.country_id = co.id
            WHERE p.status IN ('PUBLISHED', 'ACTIVE') 
              AND p.approval_status = 'APPROVED'
              AND p.is_featured = 1
              AND (sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')
              AND (c.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_category_assignments pca JOIN categories c2 ON pca.category_id = c2.id WHERE pca.product_id = p.id AND c2.status = 'ACTIVE'))
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_subcategory_assignments psa JOIN subcategories s2 ON psa.subcategory_id = s2.id WHERE psa.product_id = p.id AND s2.status = 'ACTIVE'))
            ORDER BY p.featured_order ASC, p.id DESC
            LIMIT :lim
        ", [':lim' => $limit]);

        self::attachCertificates($products);
        return $products;
    }

    public static function getRelatedProducts(int $productId, int $categoryId, int $limit = 4): array
    {
        $products = self::fetchAll("
            SELECT p.*,
                   p.title as name,
                   COALESCE(c.name, (SELECT c_sub.name FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_name,
                   COALESCE(c.slug, (SELECT c_sub.slug FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_slug,
                   (SELECT GROUP_CONCAT(c_all.name ORDER BY pca_all.is_primary DESC, c_all.name ASC SEPARATOR ', ') FROM product_category_assignments pca_all JOIN categories c_all ON pca_all.category_id = c_all.id WHERE pca_all.product_id = p.id) as all_category_names,
                   s.name as subcategory_name, s.slug as subcategory_slug,
                   (SELECT GROUP_CONCAT(s_all.name ORDER BY psa_all.is_primary DESC, s_all.name ASC SEPARATOR ', ') FROM product_subcategory_assignments psa_all JOIN subcategories s_all ON psa_all.subcategory_id = s_all.id WHERE psa_all.product_id = p.id) as all_subcategory_names,
                   pu.symbol as price_unit_symbol,
                   mu.name as moq_unit_name,
                   sp.company_name as supplier_name,
                   co.name as country_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN countries co ON p.country_id = co.id
            WHERE p.id != :id 
              AND (p.category_id = :cat_id OR EXISTS (SELECT 1 FROM product_category_assignments pca WHERE pca.product_id = p.id AND pca.category_id = :cat_id_pca))
              AND p.status IN ('PUBLISHED', 'ACTIVE')
              AND p.approval_status = 'APPROVED'
              AND (sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')
              AND (c.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_category_assignments pca2 JOIN categories c2 ON pca2.category_id = c2.id WHERE pca2.product_id = p.id AND c2.status = 'ACTIVE'))
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_subcategory_assignments psa JOIN subcategories s2 ON psa.subcategory_id = s2.id WHERE psa.product_id = p.id AND s2.status = 'ACTIVE'))
            ORDER BY p.is_featured DESC, p.id DESC
            LIMIT :lim
        ", [':id' => $productId, ':cat_id' => $categoryId, ':cat_id_pca' => $categoryId, ':lim' => $limit]);

        self::attachCertificates($products);
        return $products;
    }

    public static function attachCertificates(array &$products): void
    {
        if (empty($products)) {
            return;
        }
        $pids = array_values(array_filter(array_map(fn($p) => (int)($p['id'] ?? 0), $products)));
        if (empty($pids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $certRows = self::fetchAll("
            SELECT pca.product_id, pc.id as certificate_id, pc.name, pc.code, pc.icon
            FROM product_certificate_assignments pca
            JOIN product_certificates pc ON pca.certificate_id = pc.id
            WHERE pca.product_id IN ({$placeholders}) AND pc.status = 'ACTIVE'
            ORDER BY pc.name ASC
        ", $pids);

        $certsByPid = [];
        foreach ($certRows as $cr) {
            $certsByPid[(int)$cr['product_id']][] = $cr;
        }
        foreach ($products as &$p) {
            $p['certificates'] = $certsByPid[(int)($p['id'] ?? 0)] ?? [];
        }
        unset($p);
    }

    public static function getFilteredProducts(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(48, (int) ($filters['per_page'] ?? 12)));
        $offset = ($page - 1) * $perPage;

        $where = [
            "p.status IN ('PUBLISHED', 'ACTIVE')",
            "p.approval_status = 'APPROVED'",
            "(sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')",
            "(c.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_category_assignments pca_act JOIN categories c_act ON pca_act.category_id = c_act.id WHERE pca_act.product_id = p.id AND c_act.status = 'ACTIVE'))",
            "(p.subcategory_id IS NULL OR s.status = 'ACTIVE' OR EXISTS (SELECT 1 FROM product_subcategory_assignments psa_act JOIN subcategories s_act ON psa_act.subcategory_id = s_act.id WHERE psa_act.product_id = p.id AND s_act.status = 'ACTIVE'))"
        ];
        $bindings = [];

        // Search query
        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $where[] = "(p.title LIKE :s1 OR p.description LIKE :s2 OR p.brand_name LIKE :s3 OR p.material LIKE :s4 OR c.name LIKE :s5 OR EXISTS (SELECT 1 FROM product_category_assignments pca_s JOIN categories c_s ON pca_s.category_id = c_s.id WHERE pca_s.product_id = p.id AND c_s.name LIKE :s6))";
            $bindings[':s1'] = "%{$q}%";
            $bindings[':s2'] = "%{$q}%";
            $bindings[':s3'] = "%{$q}%";
            $bindings[':s4'] = "%{$q}%";
            $bindings[':s5'] = "%{$q}%";
            $bindings[':s6'] = "%{$q}%";
        }

        // Category slug or ID (matches primary category OR any assigned category)
        if (!empty($filters['category_slug'])) {
            $where[] = "(c.slug = :c_slug OR EXISTS (SELECT 1 FROM product_category_assignments pca JOIN categories c2 ON pca.category_id = c2.id WHERE pca.product_id = p.id AND c2.slug = :c_slug_pca))";
            $bindings[':c_slug'] = trim((string) $filters['category_slug']);
            $bindings[':c_slug_pca'] = trim((string) $filters['category_slug']);
        } elseif (!empty($filters['category_id'])) {
            $where[] = "(p.category_id = :cat_id OR EXISTS (SELECT 1 FROM product_category_assignments pca WHERE pca.product_id = p.id AND pca.category_id = :cat_id_pca))";
            $bindings[':cat_id'] = (int) $filters['category_id'];
            $bindings[':cat_id_pca'] = (int) $filters['category_id'];
        }

        // Subcategory slug or ID (matches primary subcategory OR any assigned subcategory)
        if (!empty($filters['subcategory_slug'])) {
            $where[] = "(s.slug = :s_slug OR EXISTS (SELECT 1 FROM product_subcategory_assignments psa JOIN subcategories s2 ON psa.subcategory_id = s2.id WHERE psa.product_id = p.id AND s2.slug = :s_slug_psa))";
            $bindings[':s_slug'] = trim((string) $filters['subcategory_slug']);
            $bindings[':s_slug_psa'] = trim((string) $filters['subcategory_slug']);
        } elseif (!empty($filters['subcategory_id'])) {
            $where[] = "(p.subcategory_id = :sub_id OR EXISTS (SELECT 1 FROM product_subcategory_assignments psa WHERE psa.product_id = p.id AND psa.subcategory_id = :sub_id_psa))";
            $bindings[':sub_id'] = (int) $filters['subcategory_id'];
            $bindings[':sub_id_psa'] = (int) $filters['subcategory_id'];
        }

        // Min Price
        if (isset($filters['min_price']) && is_numeric($filters['min_price']) && (float)$filters['min_price'] > 0) {
            $where[] = "p.min_price >= :min_p";
            $bindings[':min_p'] = (float) $filters['min_price'];
        }

        // Max Price
        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (float)$filters['max_price'] > 0) {
            $where[] = "p.max_price <= :max_p";
            $bindings[':max_p'] = (float) $filters['max_price'];
        }

        // Max MOQ
        if (isset($filters['max_moq']) && is_numeric($filters['max_moq']) && (int)$filters['max_moq'] > 0) {
            $where[] = "p.moq_quantity <= :max_moq";
            $bindings[':max_moq'] = (int) $filters['max_moq'];
        }

        // OEM / Customization filters
        if (!empty($filters['oem'])) {
            $where[] = "p.oem_available = 1";
        }
        if (!empty($filters['custom_logo'])) {
            $where[] = "p.custom_logo = 1";
        }
        if (!empty($filters['sample_available'])) {
            $where[] = "p.sample_available = 1";
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Sorting
        $sort = $filters['sort'] ?? 'featured';
        switch ($sort) {
            case 'price_low':
                $orderBy = "p.min_price ASC, p.id DESC";
                break;
            case 'price_high':
                $orderBy = "p.max_price DESC, p.id DESC";
                break;
            case 'moq_low':
                $orderBy = "p.moq_quantity ASC, p.id DESC";
                break;
            case 'newest':
                $orderBy = "p.id DESC";
                break;
            case 'featured':
            default:
                $orderBy = "p.is_featured DESC, p.featured_order ASC, p.id DESC";
                break;
        }

        $countSql = "
            SELECT COUNT(*) 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            {$whereClause}
        ";
        $total = (int) self::fetchColumn($countSql, $bindings);

        $limitClause = "LIMIT {$offset}, {$perPage}";
        $dataSql = "
            SELECT p.*,
                   p.title as name,
                   COALESCE(c.name, (SELECT c_sub.name FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_name,
                   COALESCE(c.slug, (SELECT c_sub.slug FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_slug,
                   (SELECT GROUP_CONCAT(c_all.name ORDER BY pca_all.is_primary DESC, c_all.name ASC SEPARATOR ', ') FROM product_category_assignments pca_all JOIN categories c_all ON pca_all.category_id = c_all.id WHERE pca_all.product_id = p.id) as all_category_names,
                   s.name as subcategory_name, s.slug as subcategory_slug,
                   (SELECT GROUP_CONCAT(s_all.name ORDER BY psa_all.is_primary DESC, s_all.name ASC SEPARATOR ', ') FROM product_subcategory_assignments psa_all JOIN subcategories s_all ON psa_all.subcategory_id = s_all.id WHERE psa_all.product_id = p.id) as all_subcategory_names,
                   pu.symbol as price_unit_symbol,
                   mu.name as moq_unit_name,
                   sp.company_name as supplier_name,
                   co.name as country_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN countries co ON p.country_id = co.id
            {$whereClause}
            ORDER BY {$orderBy}
            {$limitClause}
        ";
        $products = self::fetchAll($dataSql, $bindings);
        self::attachCertificates($products);

        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'products'     => $products,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => $lastPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public static function create(array $data, array $images = []): int
    {
        $name = trim($data['name'] ?? $data['title'] ?? '');
        $slug = !empty($data['slug']) ? Uploader::slugify($data['slug']) : Uploader::slugify($name);

        $baseSlug = $slug;
        $counter = 1;
        while (self::fetch("SELECT id FROM products WHERE slug = :slug LIMIT 1", [':slug' => $slug])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $minPrice = (float) ($data['min_price'] ?? 0.00);
        $maxPrice = (float) ($data['max_price'] ?? $minPrice);
        if ($maxPrice < $minPrice) {
            $maxPrice = $minPrice;
        }

        $primaryImage = !empty($data['primary_image']) ? trim($data['primary_image']) : null;
        if (empty($primaryImage) && !empty($images[0])) {
            $primaryImage = $images[0];
        }

        $categoryIds = !empty($data['category_ids']) 
            ? (is_array($data['category_ids']) ? $data['category_ids'] : explode(',', (string)$data['category_ids'])) 
            : (!empty($data['category_id']) ? [(int)$data['category_id']] : []);
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        $primaryCatId = !empty($data['category_id']) ? (int)$data['category_id'] : ($categoryIds[0] ?? 1);

        $subcategoryIds = !empty($data['subcategory_ids']) 
            ? (is_array($data['subcategory_ids']) ? $data['subcategory_ids'] : explode(',', (string)$data['subcategory_ids'])) 
            : (!empty($data['subcategory_id']) ? [(int)$data['subcategory_id']] : []);
        $subcategoryIds = array_values(array_unique(array_filter(array_map('intval', $subcategoryIds))));
        $primarySubId = !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : ($subcategoryIds[0] ?? null);

        self::beginTransaction();
        try {
            self::execute("
                INSERT INTO products (
                    supplier_id, category_id, subcategory_id, title, slug, sku, brand_name,
                    short_description, description, primary_image, video_url,
                    price, min_price, max_price, price_unit_id, moq_quantity, moq_unit_id,
                    available_quantity, sample_available, sample_price,
                    material, colors, size, weight, customization_details,
                    custom_logo, custom_color, custom_packaging, oem_available, odm_available, private_label,
                    country_id, state_id, city_id, lead_time, shipping_info, export_packaging,
                    status, approval_status, is_featured, featured_order, created_at, updated_at
                ) VALUES (
                    :supplier_id, :category_id, :subcategory_id, :title, :slug, :sku, :brand_name,
                    :short_description, :description, :primary_image, :video_url,
                    :price, :min_price, :max_price, :price_unit_id, :moq_quantity, :moq_unit_id,
                    :available_quantity, :sample_available, :sample_price,
                    :material, :colors, :size, :weight, :customization_details,
                    :custom_logo, :custom_color, :custom_packaging, :oem_available, :odm_available, :private_label,
                    :country_id, :state_id, :city_id, :lead_time, :shipping_info, :export_packaging,
                    :status, :approval_status, :is_featured, :featured_order, NOW(), NOW()
                )
            ", [
                ':supplier_id'          => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : 1,
                ':category_id'          => $primaryCatId,
                ':subcategory_id'       => $primarySubId,
                ':title'                => $name,
                ':slug'                 => $slug,
                ':sku'                  => !empty($data['sku']) ? trim($data['sku']) : null,
                ':brand_name'           => !empty($data['brand_name']) ? trim($data['brand_name']) : null,
                ':short_description'    => !empty($data['short_description']) ? trim($data['short_description']) : null,
                ':description'          => !empty($data['description']) ? trim($data['description']) : null,
                ':primary_image'        => $primaryImage,
                ':video_url'            => !empty($data['video_url']) ? trim($data['video_url']) : null,
                ':price'                => $minPrice,
                ':min_price'            => $minPrice,
                ':max_price'            => $maxPrice,
                ':price_unit_id'        => (int) $data['price_unit_id'],
                ':moq_quantity'         => max(1, (int) ($data['moq'] ?? $data['moq_quantity'] ?? 1)),
                ':moq_unit_id'          => (int) $data['moq_unit_id'],
                ':available_quantity'   => max(0, (int) ($data['available_quantity'] ?? 0)),
                ':sample_available'     => !empty($data['sample_available']) ? 1 : 0,
                ':sample_price'         => !empty($data['sample_price']) ? (float) $data['sample_price'] : null,
                ':material'             => !empty($data['material']) ? trim($data['material']) : null,
                ':colors'               => !empty($data['colors']) ? trim($data['colors']) : null,
                ':size'                 => !empty($data['size']) ? trim($data['size']) : null,
                ':weight'               => !empty($data['weight']) ? trim($data['weight']) : null,
                ':customization_details'=> !empty($data['customization_details']) ? trim($data['customization_details']) : null,
                ':custom_logo'          => !empty($data['custom_logo']) ? 1 : 0,
                ':custom_color'         => !empty($data['custom_color']) ? 1 : 0,
                ':custom_packaging'     => !empty($data['custom_packaging']) ? 1 : 0,
                ':oem_available'        => !empty($data['oem_available']) ? 1 : 0,
                ':odm_available'        => !empty($data['odm_available']) ? 1 : 0,
                ':private_label'        => !empty($data['private_label']) ? 1 : 0,
                ':country_id'           => !empty($data['country_id']) ? (int) $data['country_id'] : 1,
                ':state_id'             => !empty($data['state_id']) ? (int) $data['state_id'] : 1,
                ':city_id'              => !empty($data['city_id']) ? (int) $data['city_id'] : 1,
                ':lead_time'            => !empty($data['lead_time']) ? trim($data['lead_time']) : '10 - 20 Business Days',
                ':shipping_info'        => !empty($data['shipping_info']) ? trim($data['shipping_info']) : null,
                ':export_packaging'     => !empty($data['export_packaging']) ? trim($data['export_packaging']) : null,
                ':status'               => in_array($data['status'] ?? '', ['PUBLISHED', 'DRAFT', 'INACTIVE', 'ACTIVE'], true) ? $data['status'] : 'PUBLISHED',
                ':approval_status'      => in_array($data['approval_status'] ?? '', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED'], true) ? $data['approval_status'] : 'APPROVED',
                ':is_featured'          => !empty($data['is_featured']) ? 1 : 0,
                ':featured_order'       => (int) ($data['featured_order'] ?? 0),
            ]);

            $id = (int) self::lastInsertId();

            // Sync category assignments
            if (!empty($categoryIds)) {
                self::syncCategories($id, $categoryIds, $primaryCatId);
            }
            if (!empty($subcategoryIds)) {
                self::syncSubcategories($id, $subcategoryIds, $primarySubId);
            }

            // Insert gallery images
            foreach ($images as $index => $imgPath) {
                self::execute("
                    INSERT INTO product_images (product_id, image_path, is_primary, display_order)
                    VALUES (:pid, :path, :is_primary, :ord)
                ", [
                    ':pid'        => $id,
                    ':path'       => $imgPath,
                    ':is_primary' => ($index === 0 && empty($primaryImage)) ? 1 : ($imgPath === $primaryImage ? 1 : 0),
                    ':ord'        => $index + 1,
                ]);
            }

            self::commit();
            AuditLogger::log('CREATE', 'Product', $id, $name, null, 'Created product');
            return $id;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data, array $newImages = [], array $deletedImageIds = []): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        $name = trim($data['name'] ?? $data['title'] ?? $existing['title']);
        $slug = !empty($data['slug']) ? Uploader::slugify($data['slug']) : ($existing['slug'] ?? Uploader::slugify($name));

        $baseSlug = $slug;
        $counter = 1;
        while (self::fetch("SELECT id FROM products WHERE slug = :slug AND id != :id LIMIT 1", [':slug' => $slug, ':id' => $id])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $minPrice = isset($data['min_price']) ? (float) $data['min_price'] : (float) $existing['min_price'];
        $maxPrice = isset($data['max_price']) ? (float) $data['max_price'] : (float) $existing['max_price'];
        if ($maxPrice < $minPrice) {
            $maxPrice = $minPrice;
        }

        $primaryImage = !empty($data['primary_image']) ? trim($data['primary_image']) : $existing['primary_image'];
        if (empty($primaryImage) && !empty($newImages[0])) {
            $primaryImage = $newImages[0];
        }

        $approvalStatus = in_array($data['approval_status'] ?? '', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED'], true)
            ? $data['approval_status']
            : ($existing['approval_status'] ?? 'APPROVED');

        $categoryIds = null;
        if (isset($data['category_ids'])) {
            $categoryIds = is_array($data['category_ids']) ? $data['category_ids'] : explode(',', (string)$data['category_ids']);
            $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        }
        $primaryCatId = (int)($data['category_id'] ?? ($categoryIds[0] ?? $existing['category_id']));

        $subcategoryIds = null;
        if (isset($data['subcategory_ids'])) {
            $subcategoryIds = is_array($data['subcategory_ids']) ? $data['subcategory_ids'] : explode(',', (string)$data['subcategory_ids']);
            $subcategoryIds = array_values(array_unique(array_filter(array_map('intval', $subcategoryIds))));
        }
        $primarySubId = !empty($data['subcategory_id']) 
            ? (int)$data['subcategory_id'] 
            : (!empty($subcategoryIds) ? $subcategoryIds[0] : (isset($data['subcategory_ids']) ? null : $existing['subcategory_id']));

        self::beginTransaction();
        try {
            self::execute("
                UPDATE products SET
                    supplier_id = :supplier_id,
                    submitted_by = :submitted_by,
                    category_id = :category_id,
                    subcategory_id = :subcategory_id,
                    title = :title,
                    slug = :slug,
                    sku = :sku,
                    brand_name = :brand_name,
                    short_description = :short_description,
                    description = :description,
                    primary_image = :primary_image,
                    video_url = :video_url,
                    price = :price,
                    min_price = :min_price,
                    max_price = :max_price,
                    price_unit_id = :price_unit_id,
                    moq_quantity = :moq_quantity,
                    moq_unit_id = :moq_unit_id,
                    available_quantity = :available_quantity,
                    sample_available = :sample_available,
                    sample_price = :sample_price,
                    material = :material,
                    colors = :colors,
                    size = :size,
                    weight = :weight,
                    customization_details = :customization_details,
                    custom_logo = :custom_logo,
                    custom_color = :custom_color,
                    custom_packaging = :custom_packaging,
                    oem_available = :oem_available,
                    odm_available = :odm_available,
                    private_label = :private_label,
                    country_id = :country_id,
                    lead_time = :lead_time,
                    shipping_info = :shipping_info,
                    export_packaging = :export_packaging,
                    status = :status,
                    approval_status = :approval_status,
                    is_featured = :is_featured,
                    featured_order = :featured_order,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':supplier_id'          => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : (int) ($existing['supplier_id'] ?? 1),
                ':submitted_by'         => !empty($data['submitted_by']) ? (int) $data['submitted_by'] : ($existing['submitted_by'] ?? null),
                ':category_id'          => $primaryCatId,
                ':subcategory_id'       => $primarySubId,
                ':title'                => $name,
                ':slug'                 => $slug,
                ':sku'                  => !empty($data['sku']) ? trim($data['sku']) : null,
                ':brand_name'           => !empty($data['brand_name']) ? trim($data['brand_name']) : null,
                ':short_description'    => !empty($data['short_description']) ? trim($data['short_description']) : null,
                ':description'          => !empty($data['description']) ? trim($data['description']) : null,
                ':primary_image'        => $primaryImage,
                ':video_url'            => !empty($data['video_url']) ? trim($data['video_url']) : null,
                ':price'                => $minPrice,
                ':min_price'            => $minPrice,
                ':max_price'            => $maxPrice,
                ':price_unit_id'        => (int) ($data['price_unit_id'] ?? $existing['price_unit_id']),
                ':moq_quantity'         => max(1, (int) ($data['moq'] ?? $data['moq_quantity'] ?? $existing['moq_quantity'])),
                ':moq_unit_id'          => (int) ($data['moq_unit_id'] ?? $existing['moq_unit_id']),
                ':available_quantity'   => max(0, (int) ($data['available_quantity'] ?? $existing['available_quantity'])),
                ':sample_available'     => isset($data['sample_available']) ? (!empty($data['sample_available']) ? 1 : 0) : $existing['sample_available'],
                ':sample_price'         => !empty($data['sample_price']) ? (float) $data['sample_price'] : null,
                ':material'             => !empty($data['material']) ? trim($data['material']) : null,
                ':colors'               => !empty($data['colors']) ? trim($data['colors']) : null,
                ':size'                 => !empty($data['size']) ? trim($data['size']) : null,
                ':weight'               => !empty($data['weight']) ? trim($data['weight']) : null,
                ':customization_details'=> !empty($data['customization_details']) ? trim($data['customization_details']) : null,
                ':custom_logo'          => isset($data['custom_logo']) ? (!empty($data['custom_logo']) ? 1 : 0) : $existing['custom_logo'],
                ':custom_color'         => isset($data['custom_color']) ? (!empty($data['custom_color']) ? 1 : 0) : $existing['custom_color'],
                ':custom_packaging'     => isset($data['custom_packaging']) ? (!empty($data['custom_packaging']) ? 1 : 0) : $existing['custom_packaging'],
                ':oem_available'        => isset($data['oem_available']) ? (!empty($data['oem_available']) ? 1 : 0) : $existing['oem_available'],
                ':odm_available'        => isset($data['odm_available']) ? (!empty($data['odm_available']) ? 1 : 0) : $existing['odm_available'],
                ':private_label'        => isset($data['private_label']) ? (!empty($data['private_label']) ? 1 : 0) : $existing['private_label'],
                ':country_id'           => !empty($data['country_id']) ? (int) $data['country_id'] : $existing['country_id'],
                ':lead_time'            => !empty($data['lead_time']) ? trim($data['lead_time']) : $existing['lead_time'],
                ':shipping_info'        => !empty($data['shipping_info']) ? trim($data['shipping_info']) : $existing['shipping_info'],
                ':export_packaging'     => isset($data['export_packaging']) ? (!empty($data['export_packaging']) ? trim($data['export_packaging']) : null) : ($existing['export_packaging'] ?? null),
                ':status'               => in_array($data['status'] ?? '', ['PUBLISHED', 'DRAFT', 'INACTIVE', 'ACTIVE'], true) ? $data['status'] : $existing['status'],
                ':approval_status'      => $approvalStatus,
                ':is_featured'          => isset($data['is_featured']) ? (!empty($data['is_featured']) ? 1 : 0) : $existing['is_featured'],
                ':featured_order'       => (int) ($data['featured_order'] ?? $existing['featured_order']),
                ':id'                   => $id,
            ]);

            if (!empty($data['clear_pending_changes']) || $approvalStatus === 'APPROVED') {
                self::execute("UPDATE products SET pending_changes = NULL WHERE id = :id", [':id' => $id]);
            }

            // Sync category and subcategory assignments
            if ($categoryIds !== null && !empty($categoryIds)) {
                self::syncCategories($id, $categoryIds, $primaryCatId);
            }
            if ($subcategoryIds !== null) {
                self::syncSubcategories($id, $subcategoryIds, $primarySubId);
            }

            // Delete requested images
            if (!empty($deletedImageIds)) {
                $placeholders = implode(',', array_fill(0, count($deletedImageIds), '?'));
                $stmt = self::execute("DELETE FROM product_images WHERE id IN ({$placeholders}) AND product_id = ?", array_merge($deletedImageIds, [$id]));
            }

            // Insert new images
            foreach ($newImages as $index => $imgPath) {
                self::execute("
                    INSERT INTO product_images (product_id, image_path, is_primary, display_order)
                    VALUES (:pid, :path, 0, :ord)
                ", [
                    ':pid'  => $id,
                    ':path' => $imgPath,
                    ':ord'  => 10 + $index,
                ]);
            }

            self::commit();
            AuditLogger::log('UPDATE', 'Product', $id, $name, null, 'Updated product');
            return true;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }

    public static function setStatus(int $id, string $status): bool
    {
        $prod = self::findById($id);
        if (!$prod) {
            return false;
        }

        self::execute("UPDATE products SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'Product', $id, $prod['title'], null, "Status changed to {$status}");
        return true;
    }

    public static function setFeatured(int $id, bool $isFeatured, int $order = 0): bool
    {
        $prod = self::findById($id);
        if (!$prod) {
            return false;
        }

        self::execute("UPDATE products SET is_featured = :feat, featured_order = :ord, updated_at = NOW() WHERE id = :id", [
            ':feat' => $isFeatured ? 1 : 0,
            ':ord'  => $order,
            ':id'   => $id,
        ]);

        AuditLogger::log('UPDATE', 'Product', $id, $prod['title'], null, "Featured flag toggled to " . ($isFeatured ? 'YES' : 'NO'));
        return true;
    }

    public static function incrementViewCount(int $id): void
    {
        self::execute("UPDATE products SET view_count = view_count + 1 WHERE id = :id", [':id' => $id]);
    }

    public static function hardDelete(int $id): array
    {
        $prod = self::findById($id);
        if (!$prod) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        self::beginTransaction();
        try {
            self::execute("DELETE FROM product_category_assignments WHERE product_id = :id", [':id' => $id]);
            self::execute("DELETE FROM product_subcategory_assignments WHERE product_id = :id", [':id' => $id]);
            self::execute("DELETE FROM product_images WHERE product_id = :id", [':id' => $id]);
            self::execute("DELETE FROM product_inquiries WHERE product_id = :id", [':id' => $id]);
            self::execute("DELETE FROM products WHERE id = :id", [':id' => $id]);
            self::commit();

            AuditLogger::log('DELETE', 'Product', $id, $prod['title'], ['products' => 1], 'Permanently deleted product');

            return [
                'success' => true,
                'message' => "Product '{$prod['title']}' permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete product: ' . $e->getMessage()];
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

        $columns = ['id', 'title', 'category_name', 'min_price', 'moq_quantity', 'status', 'is_featured', 'created_at'];
        $orderColIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $orderCol = $columns[$orderColIdx] ?? 'p.id';
        if ($orderCol === 'category_name') {
            $orderCol = 'c.name';
        } else if ($orderCol !== 'id') {
            $orderCol = 'p.' . $orderCol;
        } else {
            $orderCol = 'p.id';
        }

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(p.title LIKE :s1 OR p.sku LIKE :s2 OR p.brand_name LIKE :s3 OR c.name LIKE :s4 OR EXISTS (SELECT 1 FROM product_category_assignments pca_s JOIN categories c_s ON pca_s.category_id = c_s.id WHERE pca_s.product_id = p.id AND c_s.name LIKE :s5))";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
            $bindings[':s4'] = "%{$search}%";
            $bindings[':s5'] = "%{$search}%";
        }

        if ($statusFilter !== '') {
            $where[] = "p.status = :status";
            $bindings[':status'] = $statusFilter;
        }

        if ($categoryFilter > 0) {
            $where[] = "(p.category_id = :cat_filter OR EXISTS (SELECT 1 FROM product_category_assignments pca WHERE pca.product_id = p.id AND pca.category_id = :cat_filter_pca))";
            $bindings[':cat_filter'] = $categoryFilter;
            $bindings[':cat_filter_pca'] = $categoryFilter;
        }

        if (isset($params['is_featured']) && $params['is_featured'] !== '') {
            $where[] = "p.is_featured = :is_featured";
            $bindings[':is_featured'] = (int)$params['is_featured'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM products");
        $filteredRecords = (int) self::fetchColumn("
            SELECT COUNT(*) FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            {$whereClause}
        ", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT p.id, p.title as name, p.title, p.slug, p.primary_image, p.min_price, p.max_price, 
                       p.moq_quantity, p.status, p.approval_status, p.rejection_reason, p.is_featured, p.featured_order, p.created_at,
                       COALESCE(c.name, (SELECT c_sub.name FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_name,
                       (SELECT GROUP_CONCAT(c_all.name ORDER BY pca_all.is_primary DESC, c_all.name ASC SEPARATOR ', ') FROM product_category_assignments pca_all JOIN categories c_all ON pca_all.category_id = c_all.id WHERE pca_all.product_id = p.id) as all_category_names,
                       s.name as subcategory_name,
                       (SELECT GROUP_CONCAT(s_all.name ORDER BY psa_all.is_primary DESC, s_all.name ASC SEPARATOR ', ') FROM product_subcategory_assignments psa_all JOIN subcategories s_all ON psa_all.subcategory_id = s_all.id WHERE psa_all.product_id = p.id) as all_subcategory_names,
                       pu.symbol as price_unit_symbol, mu.symbol as moq_unit_symbol,
                       sp.company_name as supplier_name, p.pending_changes
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN subcategories s ON p.subcategory_id = s.id
                LEFT JOIN price_units pu ON p.price_unit_id = pu.id
                LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
                LEFT JOIN suppliers sp ON p.supplier_id = sp.id
                {$whereClause} 
                ORDER BY {$orderCol} {$orderDir} 
                {$limitClause}";

        $data = self::fetchAll($sql, $bindings);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ];
    }

    public static function getSupplierProducts(int $supplierId): array
    {
        return self::fetchAll("
            SELECT p.*, p.title as name,
                   COALESCE(c.name, (SELECT c_sub.name FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_name,
                   (SELECT GROUP_CONCAT(c_all.name ORDER BY pca_all.is_primary DESC, c_all.name ASC SEPARATOR ', ') FROM product_category_assignments pca_all JOIN categories c_all ON pca_all.category_id = c_all.id WHERE pca_all.product_id = p.id) as all_category_names,
                   s.name as subcategory_name,
                   (SELECT GROUP_CONCAT(s_all.name ORDER BY psa_all.is_primary DESC, s_all.name ASC SEPARATOR ', ') FROM product_subcategory_assignments psa_all JOIN subcategories s_all ON psa_all.subcategory_id = s_all.id WHERE psa_all.product_id = p.id) as all_subcategory_names,
                   pu.symbol as price_unit_symbol, mu.name as moq_unit_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            WHERE p.supplier_id = :supplier_id
            ORDER BY p.id DESC
        ", [':supplier_id' => $supplierId]);
    }

    public static function submitSupplierProduct(array $data, int $supplierId, array $images = []): int
    {
        $data['supplier_id'] = $supplierId;
        $data['status'] = 'DRAFT';
        $data['approval_status'] = 'PENDING_APPROVAL';
        $data['is_featured'] = 0;
        $data['featured_order'] = 0;
        return self::create($data, $images);
    }

    public static function saveSupplierEdit(int $id, int $supplierId, array $data, ?string $primaryImage = null, array $galleryImages = [], array $deletedImageIds = []): bool
    {
        $product = self::findById($id);
        if (!$product || (int)$product['supplier_id'] !== $supplierId) {
            return false;
        }

        // Suppliers cannot modify featured status
        unset($data['is_featured'], $data['featured_order']);

        $finalPrimary = $primaryImage ?: (!empty($data['primary_image']) ? trim((string)$data['primary_image']) : $product['primary_image']);

        // If product is already APPROVED, save modifications as pending_changes to prevent unapproved changes from going live
        if ($product['approval_status'] === 'APPROVED') {
            $pendingData = [
                'name'                  => trim($data['name'] ?? $data['title'] ?? $product['title']),
                'slug'                  => trim($data['slug'] ?? $product['slug'] ?? ''),
                'category_id'           => (int)($data['category_id'] ?? $product['category_id']),
                'category_ids'          => !empty($data['category_ids']) ? (is_array($data['category_ids']) ? $data['category_ids'] : explode(',', (string)$data['category_ids'])) : ($product['category_ids'] ?? [$product['category_id']]),
                'subcategory_id'        => (int)($data['subcategory_id'] ?? $product['subcategory_id']),
                'subcategory_ids'       => !empty($data['subcategory_ids']) ? (is_array($data['subcategory_ids']) ? $data['subcategory_ids'] : explode(',', (string)$data['subcategory_ids'])) : ($product['subcategory_ids'] ?? []),
                'sku'                   => trim($data['sku'] ?? $product['sku'] ?? ''),
                'brand_name'            => trim($data['brand_name'] ?? $product['brand_name'] ?? ''),
                'short_description'     => trim($data['short_description'] ?? $product['short_description'] ?? ''),
                'description'           => trim($data['description'] ?? $product['description'] ?? ''),
                'min_price'             => (float)($data['min_price'] ?? $product['min_price']),
                'max_price'             => (float)($data['max_price'] ?? $product['max_price']),
                'price_unit_id'         => (int)($data['price_unit_id'] ?? $product['price_unit_id']),
                'moq'                   => (int)($data['moq'] ?? $data['moq_quantity'] ?? $product['moq_quantity']),
                'moq_unit_id'           => (int)($data['moq_unit_id'] ?? $product['moq_unit_id']),
                'available_quantity'    => (int)($data['available_quantity'] ?? $product['available_quantity'] ?? 0),
                'sample_available'      => !empty($data['sample_available']) ? 1 : 0,
                'sample_price'          => !empty($data['sample_price']) ? (float)$data['sample_price'] : null,
                'material'              => trim($data['material'] ?? $product['material'] ?? ''),
                'colors'                => trim($data['colors'] ?? $product['colors'] ?? ''),
                'size'                  => trim($data['size'] ?? $product['size'] ?? ''),
                'weight'                => trim($data['weight'] ?? $product['weight'] ?? ''),
                'customization_details' => trim($data['customization_details'] ?? $product['customization_details'] ?? ''),
                'custom_logo'           => !empty($data['custom_logo']) ? 1 : 0,
                'custom_color'          => !empty($data['custom_color']) ? 1 : 0,
                'custom_packaging'      => !empty($data['custom_packaging']) ? 1 : 0,
                'oem_available'         => !empty($data['oem_available']) ? 1 : 0,
                'odm_available'         => !empty($data['odm_available']) ? 1 : 0,
                'private_label'         => !empty($data['private_label']) ? 1 : 0,
                'country_id'            => (int)($data['country_id'] ?? $product['country_id'] ?? 1),
                'lead_time'             => trim($data['lead_time'] ?? $product['lead_time'] ?? ''),
                'shipping_info'         => trim($data['shipping_info'] ?? $product['shipping_info'] ?? ''),
                'export_packaging'      => trim($data['export_packaging'] ?? $product['export_packaging'] ?? ''),
                'status'                => in_array($data['status'] ?? '', ['PUBLISHED', 'DRAFT', 'INACTIVE'], true) ? $data['status'] : ($product['status'] ?? 'PUBLISHED'),
                'video_url'             => trim($data['video_url'] ?? $product['video_url'] ?? ''),
                'primary_image'         => $finalPrimary,
                'gallery_images'        => $galleryImages,
                'deleted_image_ids'     => $deletedImageIds,
                'submitted_at'          => date('Y-m-d H:i:s'),
            ];

            self::execute("
                UPDATE products 
                SET pending_changes = :pending_changes,
                    updated_at = NOW()
                WHERE id = :id AND supplier_id = :supplier_id
            ", [
                ':pending_changes' => json_encode($pendingData, JSON_UNESCAPED_UNICODE),
                ':id'              => $id,
                ':supplier_id'     => $supplierId
            ]);

            return true;
        }

        // If product was in DRAFT, PENDING_APPROVAL, or REJECTED, update columns directly and re-submit
        $data['supplier_id'] = $supplierId;
        $data['status'] = 'DRAFT';
        if ($finalPrimary) {
            $data['primary_image'] = $finalPrimary;
        }
        // Normalize checkbox flags when unchecked in form submission
        foreach (['sample_available', 'custom_logo', 'custom_color', 'custom_packaging', 'oem_available', 'odm_available', 'private_label'] as $flag) {
            $data[$flag] = !empty($data[$flag]) ? 1 : 0;
        }
        self::update($id, $data, $galleryImages, $deletedImageIds);
        self::execute("
            UPDATE products 
            SET approval_status = 'PENDING_APPROVAL', 
                rejection_reason = NULL,
                pending_changes = NULL,
                updated_at = NOW() 
            WHERE id = :id
        ", [':id' => $id]);

        return true;
    }

    public static function approveProduct(int $id, int $adminId): bool
    {
        $product = self::findById($id);
        if (!$product) {
            return false;
        }

        self::beginTransaction();
        try {
            // If there were pending_changes (versioned edit), apply them to main columns
            if (!empty($product['pending_changes'])) {
                $pending = json_decode($product['pending_changes'], true);
                if (is_array($pending)) {
                    $upData = [
                        'name'                  => $pending['name'] ?? $product['title'],
                        'slug'                  => !empty($pending['slug']) ? $pending['slug'] : $product['slug'],
                        'category_id'           => $pending['category_id'] ?? $product['category_id'],
                        'category_ids'          => $pending['category_ids'] ?? null,
                        'subcategory_id'        => $pending['subcategory_id'] ?? $product['subcategory_id'],
                        'subcategory_ids'       => $pending['subcategory_ids'] ?? null,
                        'sku'                   => $pending['sku'] ?? $product['sku'],
                        'brand_name'            => $pending['brand_name'] ?? $product['brand_name'],
                        'short_description'     => $pending['short_description'] ?? $product['short_description'],
                        'description'           => $pending['description'] ?? $product['description'],
                        'min_price'             => $pending['min_price'] ?? $product['min_price'],
                        'max_price'             => $pending['max_price'] ?? $product['max_price'],
                        'price_unit_id'         => $pending['price_unit_id'] ?? $product['price_unit_id'],
                        'moq'                   => $pending['moq'] ?? $product['moq_quantity'],
                        'moq_unit_id'           => $pending['moq_unit_id'] ?? $product['moq_unit_id'],
                        'available_quantity'    => $pending['available_quantity'] ?? $product['available_quantity'],
                        'sample_available'      => $pending['sample_available'] ?? $product['sample_available'],
                        'sample_price'          => $pending['sample_price'] ?? $product['sample_price'],
                        'material'              => $pending['material'] ?? $product['material'],
                        'colors'                => $pending['colors'] ?? $product['colors'],
                        'size'                  => $pending['size'] ?? $product['size'],
                        'weight'                => $pending['weight'] ?? $product['weight'],
                        'customization_details' => $pending['customization_details'] ?? $product['customization_details'],
                        'custom_logo'           => $pending['custom_logo'] ?? $product['custom_logo'],
                        'custom_color'          => $pending['custom_color'] ?? $product['custom_color'],
                        'custom_packaging'      => $pending['custom_packaging'] ?? $product['custom_packaging'],
                        'oem_available'         => $pending['oem_available'] ?? $product['oem_available'],
                        'odm_available'         => $pending['odm_available'] ?? $product['odm_available'],
                        'private_label'         => $pending['private_label'] ?? $product['private_label'],
                        'country_id'            => $pending['country_id'] ?? $product['country_id'],
                        'lead_time'             => $pending['lead_time'] ?? $product['lead_time'],
                        'shipping_info'         => $pending['shipping_info'] ?? $product['shipping_info'],
                        'export_packaging'      => $pending['export_packaging'] ?? $product['export_packaging'],
                        'video_url'             => $pending['video_url'] ?? $product['video_url'],
                        'primary_image'         => $pending['primary_image'] ?? $product['primary_image'],
                        'is_featured'           => $product['is_featured'],
                        'featured_order'        => $product['featured_order'],
                        'supplier_id'           => $product['supplier_id'],
                        'status'                => 'PUBLISHED',
                    ];
                    self::update(
                        $id,
                        $upData,
                        $pending['gallery_images'] ?? [],
                        $pending['deleted_image_ids'] ?? []
                    );
                }
            }

            self::execute("
                UPDATE products 
                SET approval_status = 'APPROVED',
                    status = 'PUBLISHED',
                    rejection_reason = NULL,
                    pending_changes = NULL,
                    reviewed_by = :admin_id,
                    reviewed_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':admin_id' => $adminId,
                ':id'       => $id
            ]);

            self::commit();
            return true;
        } catch (\Throwable $e) {
            self::rollBack();
            return false;
        }
    }

    public static function rejectProduct(int $id, int $adminId, string $reason): bool
    {
        $product = self::findById($id);
        if (!$product) {
            return false;
        }

        // If product was already APPROVED and a pending revision was under review:
        // Retain the existing approved product live, clear the pending revision, and record rejection feedback
        if ($product['approval_status'] === 'APPROVED') {
            self::execute("
                UPDATE products 
                SET pending_changes = NULL,
                    rejection_reason = :reason,
                    reviewed_by = :admin_id,
                    reviewed_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':reason'   => trim($reason),
                ':admin_id' => $adminId,
                ':id'       => $id
            ]);

            return true;
        }

        // Initial submission or non-approved product: mark approval_status as REJECTED
        self::execute("
            UPDATE products 
            SET approval_status = 'REJECTED',
                rejection_reason = :reason,
                pending_changes = NULL,
                reviewed_by = :admin_id,
                reviewed_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ", [
            ':reason'   => trim($reason),
            ':admin_id' => $adminId,
            ':id'       => $id
        ]);

        return true;
    }

    public static function getPendingApprovals(string $filter = 'PENDING'): array
    {
        $filter = strtoupper(trim($filter));
        if ($filter === 'APPROVED') {
            $where = "WHERE p.approval_status = 'APPROVED' AND p.pending_changes IS NULL";
        } elseif ($filter === 'REJECTED') {
            $where = "WHERE p.approval_status = 'REJECTED'";
        } elseif ($filter === 'ALL') {
            $where = "";
        } else {
            $where = "WHERE p.approval_status = 'PENDING_APPROVAL' OR p.pending_changes IS NOT NULL";
        }

        return self::fetchAll("
            SELECT p.*, p.title as name,
                   COALESCE(c.name, (SELECT c_sub.name FROM product_category_assignments pca_sub JOIN categories c_sub ON pca_sub.category_id = c_sub.id WHERE pca_sub.product_id = p.id ORDER BY pca_sub.is_primary DESC, pca_sub.category_id ASC LIMIT 1)) as category_name,
                   (SELECT GROUP_CONCAT(c_all.name ORDER BY pca_all.is_primary DESC, c_all.name ASC SEPARATOR ', ') FROM product_category_assignments pca_all JOIN categories c_all ON pca_all.category_id = c_all.id WHERE pca_all.product_id = p.id) as all_category_names,
                   s.name as subcategory_name,
                   (SELECT GROUP_CONCAT(s_all.name ORDER BY psa_all.is_primary DESC, s_all.name ASC SEPARATOR ', ') FROM product_subcategory_assignments psa_all JOIN subcategories s_all ON psa_all.subcategory_id = s_all.id WHERE psa_all.product_id = p.id) as all_subcategory_names,
                   pu.symbol as price_unit_symbol, mu.name as moq_unit_name,
                   sp.company_name as supplier_name, sp.email as supplier_email,
                   sp.phone as supplier_phone
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            {$where}
            ORDER BY p.updated_at DESC, p.id DESC
        ");
    }

    public static function getApprovalMetrics(): array
    {
        return [
            'total'     => (int) self::fetchColumn("SELECT COUNT(*) FROM products"),
            'pending'   => (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE approval_status = 'PENDING_APPROVAL' OR pending_changes IS NOT NULL"),
            'approved'  => (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE approval_status = 'APPROVED' AND pending_changes IS NULL"),
            'rejected'  => (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE approval_status = 'REJECTED'"),
            'featured'  => (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE is_featured = 1"),
        ];
    }

    public static function getSupplierPublishedProducts(int $supplierId): array
    {
        return self::fetchAll("
            SELECT p.*,
                   p.title as name,
                   c.name as category_name, c.slug as category_slug,
                   s.name as subcategory_name, s.slug as subcategory_slug,
                   pu.name as price_unit_name, pu.symbol as price_unit_symbol,
                   mu.name as moq_unit_name, mu.symbol as moq_unit_symbol,
                   co.name as country_name,
                   ct.name as city_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN countries co ON p.country_id = co.id
            LEFT JOIN cities ct ON p.city_id = ct.id
            WHERE p.supplier_id = :supplier_id
              AND (p.status = 'PUBLISHED' OR p.status = 'ACTIVE')
              AND p.approval_status = 'APPROVED'
            ORDER BY p.is_featured DESC, p.id DESC
        ", [':supplier_id' => $supplierId]);
    }
}


