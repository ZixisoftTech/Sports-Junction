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
        return $product;
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
              AND c.status = 'ACTIVE'
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE')
            LIMIT 1
        ", [':slug' => $slug]);

        if (!$product) {
            return null;
        }

        $product['images'] = self::getImages((int) $product['id']);
        return $product;
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
                   c.name as category_name, c.slug as category_slug,
                   s.name as subcategory_name, s.slug as subcategory_slug,
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
              AND c.status = 'ACTIVE'
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE')
            ORDER BY p.featured_order ASC, p.id DESC
            LIMIT :lim
        ", [':lim' => $limit]);

        return $products;
    }

    public static function getRelatedProducts(int $productId, int $categoryId, int $limit = 4): array
    {
        return self::fetchAll("
            SELECT p.*,
                   p.title as name,
                   c.name as category_name, c.slug as category_slug,
                   pu.symbol as price_unit_symbol,
                   mu.name as moq_unit_name,
                   sp.company_name as supplier_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            WHERE p.id != :id 
              AND p.category_id = :cat_id 
              AND p.status IN ('PUBLISHED', 'ACTIVE')
              AND p.approval_status = 'APPROVED'
              AND (sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')
              AND c.status = 'ACTIVE'
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE')
            ORDER BY p.is_featured DESC, p.id DESC
            LIMIT :lim
        ", [':id' => $productId, ':cat_id' => $categoryId, ':lim' => $limit]);
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
            "c.status = 'ACTIVE'",
            "(p.subcategory_id IS NULL OR s.status = 'ACTIVE')"
        ];
        $bindings = [];

        // Search query
        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $where[] = "(p.title LIKE :s1 OR p.description LIKE :s2 OR p.brand_name LIKE :s3 OR p.material LIKE :s4)";
            $bindings[':s1'] = "%{$q}%";
            $bindings[':s2'] = "%{$q}%";
            $bindings[':s3'] = "%{$q}%";
            $bindings[':s4'] = "%{$q}%";
        }

        // Category slug or ID
        if (!empty($filters['category_slug'])) {
            $where[] = "c.slug = :c_slug";
            $bindings[':c_slug'] = trim((string) $filters['category_slug']);
        } elseif (!empty($filters['category_id'])) {
            $where[] = "p.category_id = :cat_id";
            $bindings[':cat_id'] = (int) $filters['category_id'];
        }

        // Subcategory slug or ID
        if (!empty($filters['subcategory_slug'])) {
            $where[] = "s.slug = :s_slug";
            $bindings[':s_slug'] = trim((string) $filters['subcategory_slug']);
        } elseif (!empty($filters['subcategory_id'])) {
            $where[] = "p.subcategory_id = :sub_id";
            $bindings[':sub_id'] = (int) $filters['subcategory_id'];
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
                   c.name as category_name, c.slug as category_slug,
                   s.name as subcategory_name, s.slug as subcategory_slug,
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
                    country_id, state_id, city_id, lead_time, shipping_info,
                    status, approval_status, is_featured, featured_order, created_at, updated_at
                ) VALUES (
                    :supplier_id, :category_id, :subcategory_id, :title, :slug, :sku, :brand_name,
                    :short_description, :description, :primary_image, :video_url,
                    :price, :min_price, :max_price, :price_unit_id, :moq_quantity, :moq_unit_id,
                    :available_quantity, :sample_available, :sample_price,
                    :material, :colors, :size, :weight, :customization_details,
                    :custom_logo, :custom_color, :custom_packaging, :oem_available, :odm_available, :private_label,
                    :country_id, :state_id, :city_id, :lead_time, :shipping_info,
                    :status, :approval_status, :is_featured, :featured_order, NOW(), NOW()
                )
            ", [
                ':supplier_id'          => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : 1,
                ':category_id'          => (int) $data['category_id'],
                ':subcategory_id'       => (int) $data['subcategory_id'],
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
                ':status'               => in_array($data['status'] ?? '', ['PUBLISHED', 'DRAFT', 'INACTIVE', 'ACTIVE'], true) ? $data['status'] : 'PUBLISHED',
                ':approval_status'      => in_array($data['approval_status'] ?? '', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED'], true) ? $data['approval_status'] : 'APPROVED',
                ':is_featured'          => !empty($data['is_featured']) ? 1 : 0,
                ':featured_order'       => (int) ($data['featured_order'] ?? 0),
            ]);

            $id = (int) self::lastInsertId();

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

        self::beginTransaction();
        try {
            self::execute("
                UPDATE products SET
                    supplier_id = :supplier_id,
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
                    status = :status,
                    approval_status = :approval_status,
                    is_featured = :is_featured,
                    featured_order = :featured_order,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':supplier_id'          => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : (int) ($existing['supplier_id'] ?? 1),
                ':category_id'          => (int) ($data['category_id'] ?? $existing['category_id']),
                ':subcategory_id'       => (int) ($data['subcategory_id'] ?? $existing['subcategory_id']),
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
                ':status'               => in_array($data['status'] ?? '', ['PUBLISHED', 'DRAFT', 'INACTIVE', 'ACTIVE'], true) ? $data['status'] : $existing['status'],
                ':approval_status'      => $approvalStatus,
                ':is_featured'          => isset($data['is_featured']) ? (!empty($data['is_featured']) ? 1 : 0) : $existing['is_featured'],
                ':featured_order'       => (int) ($data['featured_order'] ?? $existing['featured_order']),
                ':id'                   => $id,
            ]);

            if (!empty($data['clear_pending_changes']) || $approvalStatus === 'APPROVED') {
                self::execute("UPDATE products SET pending_changes = NULL WHERE id = :id", [':id' => $id]);
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
            $where[] = "(p.title LIKE :s1 OR p.sku LIKE :s2 OR p.brand_name LIKE :s3 OR c.name LIKE :s4)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
            $bindings[':s4'] = "%{$search}%";
        }

        if ($statusFilter !== '') {
            $where[] = "p.status = :status";
            $bindings[':status'] = $statusFilter;
        }

        if ($categoryFilter > 0) {
            $where[] = "p.category_id = :cat_filter";
            $bindings[':cat_filter'] = $categoryFilter;
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
                       c.name as category_name, s.name as subcategory_name,
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
                   c.name as category_name, s.name as subcategory_name,
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
                'subcategory_id'        => (int)($data['subcategory_id'] ?? $product['subcategory_id']),
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
                        'subcategory_id'        => $pending['subcategory_id'] ?? $product['subcategory_id'],
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

        self::execute("
            UPDATE products 
            SET approval_status = 'REJECTED',
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
                   c.name as category_name, s.name as subcategory_name,
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


