<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\Session;
use Throwable;

class BuyerFavourite extends Model
{
    /**
     * Add product to buyer's Sort List
     */
    public static function add(int $userId, int $productId): bool
    {
        return self::addToSortList($userId, $productId);
    }

    public static function addToSortList(int $userId, int $productId): bool
    {
        // Check if product exists, is published and approved
        $product = Product::findById($productId);
        if (!$product || !in_array($product['status'] ?? '', ['PUBLISHED', 'ACTIVE']) || ($product['approval_status'] ?? '') !== 'APPROVED') {
            return false;
        }

        self::execute("
            INSERT INTO buyer_favourites (user_id, product_id, created_at)
            VALUES (:user_id, :product_id, NOW())
            ON DUPLICATE KEY UPDATE created_at = NOW()
        ", [
            ':user_id'    => $userId,
            ':product_id' => $productId,
        ]);

        return true;
    }

    /**
     * Remove product from buyer's Sort List
     */
    public static function remove(int $userId, int $productId): bool
    {
        return self::removeFromSortList($userId, $productId);
    }

    public static function removeFromSortList(int $userId, int $productId): bool
    {
        self::execute("
            DELETE FROM buyer_favourites
            WHERE user_id = :user_id AND product_id = :product_id
        ", [
            ':user_id'    => $userId,
            ':product_id' => $productId,
        ]);

        return true;
    }

    /**
     * Remove multiple products from buyer's Sort List
     */
    public static function removeMultiple(int $userId, array $productIds): bool
    {
        if (empty($productIds)) {
            return true;
        }

        $cleanIds = array_filter(array_map('intval', $productIds), fn($id) => $id > 0);
        if (empty($cleanIds)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $params = array_merge([$userId], array_values($cleanIds));

        self::execute("
            DELETE FROM buyer_favourites
            WHERE user_id = ? AND product_id IN ($placeholders)
        ", $params);

        return true;
    }

    /**
     * Clear all products from buyer's Sort List
     */
    public static function clearSortList(int $userId): bool
    {
        self::execute("
            DELETE FROM buyer_favourites
            WHERE user_id = :user_id
        ", [':user_id' => $userId]);

        return true;
    }

    /**
     * Check if product is in buyer's Sort List
     */
    public static function isFavourite(int $userId, int $productId): bool
    {
        return self::isInSortList($userId, $productId);
    }

    public static function isInSortList(int $userId, int $productId): bool
    {
        $res = self::fetch("
            SELECT id FROM buyer_favourites
            WHERE user_id = :user_id AND product_id = :product_id
            LIMIT 1
        ", [
            ':user_id'    => $userId,
            ':product_id' => $productId,
        ]);

        return (bool) $res;
    }

    /**
     * Get all product IDs in buyer's Sort List (for fast batch lookup in product grids)
     */
    public static function getBuyerSortProductIds(int $userId): array
    {
        $rows = self::fetchAll("
            SELECT product_id FROM buyer_favourites WHERE user_id = :user_id
        ", [':user_id' => $userId]);

        return array_map(fn($r) => (int)$r['product_id'], $rows);
    }

    /**
     * Get all products in buyer's Sort List with full status & availability audit
     */
    public static function getBuyerSortList(int $userId): array
    {
        return self::fetchAll("
            SELECT bf.id AS favourite_id, bf.created_at AS favourited_at,
                   p.id, p.title AS name, p.slug, p.primary_image, p.min_price, p.max_price,
                   p.moq_quantity, p.status, p.approval_status, p.sku,
                   c.name AS category_name, c.slug AS category_slug,
                   s.name AS subcategory_name,
                   pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   sp.id AS supplier_id, sp.company_name AS supplier_name, sp.status AS supplier_status, sp.approval_status AS supplier_approval_status,
                   COALESCE(ct.name, 'India') AS country_name,
                   CASE 
                       WHEN p.id IS NULL THEN 0
                       WHEN p.status NOT IN ('PUBLISHED', 'ACTIVE') OR p.approval_status != 'APPROVED' THEN 0
                       WHEN sp.id IS NULL OR sp.status != 'ACTIVE' OR sp.approval_status != 'APPROVED' THEN 0
                       ELSE 1
                   END AS is_available,
                   CASE
                       WHEN p.id IS NULL THEN 'Product no longer exists'
                       WHEN p.status NOT IN ('PUBLISHED', 'ACTIVE') OR p.approval_status != 'APPROVED' THEN 'Product is unlisted or pending audit'
                       WHEN sp.id IS NULL OR sp.status != 'ACTIVE' OR sp.approval_status != 'APPROVED' THEN 'Supplier is currently inactive'
                       ELSE ''
                   END AS unavailable_reason
            FROM buyer_favourites bf
            LEFT JOIN products p ON bf.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN countries ct ON sp.country_id = ct.id
            WHERE bf.user_id = :user_id
            ORDER BY bf.created_at DESC
        ", [':user_id' => $userId]);
    }

    /**
     * Alias for backward compatibility
     */
    public static function getBuyerFavourites(int $userId): array
    {
        return self::getBuyerSortList($userId);
    }

    /**
     * Count valid products currently in buyer's Sort List
     */
    public static function countForBuyer(int $userId): int
    {
        return (int) self::fetchColumn("
            SELECT COUNT(*) 
            FROM buyer_favourites bf
            JOIN products p ON bf.product_id = p.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            WHERE bf.user_id = :user_id
              AND p.status IN ('PUBLISHED', 'ACTIVE')
              AND p.approval_status = 'APPROVED'
              AND (sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')
        ", [':user_id' => $userId]);
    }

    /**
     * Fetch specific eligible products for Bulk Enquiry submission
     */
    public static function getEligibleProductsForBulk(int $userId, array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $cleanIds = array_filter(array_map('intval', $productIds), fn($id) => $id > 0);
        if (empty($cleanIds)) {
            return [];
        }

        if ($userId <= 0) {
            $guestIds = self::getGuestSortIds();
            $validIds = array_values(array_intersect($cleanIds, $guestIds));
            if (empty($validIds)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($validIds), '?'));
            return self::fetchAll("
                SELECT p.id, p.title AS name, p.slug, p.primary_image, p.min_price, p.max_price,
                       p.moq_quantity, p.sku,
                       mu.name AS moq_unit_name,
                       pu.symbol AS price_unit_symbol,
                       sp.id AS supplier_id, sp.company_name AS supplier_name,
                       COALESCE(ct.name, 'India') AS country_name
                FROM products p
                JOIN suppliers sp ON p.supplier_id = sp.id
                LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
                LEFT JOIN price_units pu ON p.price_unit_id = pu.id
                LEFT JOIN countries ct ON sp.country_id = ct.id
                WHERE p.id IN ($placeholders)
                  AND p.status IN ('PUBLISHED', 'ACTIVE')
                  AND p.approval_status = 'APPROVED'
                  AND sp.status = 'ACTIVE'
                  AND sp.approval_status = 'APPROVED'
                ORDER BY FIELD(p.id, " . implode(',', $validIds) . ")
            ", $validIds);
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $params = array_merge([$userId], array_values($cleanIds));

        return self::fetchAll("
            SELECT p.id, p.title AS name, p.slug, p.primary_image, p.min_price, p.max_price,
                   p.moq_quantity, p.sku,
                   mu.name AS moq_unit_name,
                   pu.symbol AS price_unit_symbol,
                   sp.id AS supplier_id, sp.company_name AS supplier_name,
                   COALESCE(ct.name, 'India') AS country_name
            FROM buyer_favourites bf
            JOIN products p ON bf.product_id = p.id
            JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN countries ct ON sp.country_id = ct.id
            WHERE bf.user_id = ?
              AND p.id IN ($placeholders)
              AND p.status IN ('PUBLISHED', 'ACTIVE')
              AND p.approval_status = 'APPROVED'
              AND sp.status = 'ACTIVE'
              AND sp.approval_status = 'APPROVED'
            ORDER BY FIELD(p.id, " . implode(',', array_values($cleanIds)) . ")
        ", $params);
    }

    /**
     * Session-based Guest Sort List Management (works seamlessly without login)
     */
    public static function getGuestSortIds(): array
    {
        Session::start();
        $list = Session::get('guest_sort_list', []);
        return is_array($list) ? array_map('intval', array_unique($list)) : [];
    }

    public static function addToGuestSort(int $productId): bool
    {
        Session::start();
        $product = Product::findById($productId);
        if (!$product || !in_array($product['status'] ?? '', ['PUBLISHED', 'ACTIVE']) || ($product['approval_status'] ?? '') !== 'APPROVED') {
            return false;
        }

        $list = self::getGuestSortIds();
        if (!in_array($productId, $list, true)) {
            $list[] = $productId;
            Session::set('guest_sort_list', $list);
        }
        return true;
    }

    public static function removeFromGuestSort(int $productId): bool
    {
        Session::start();
        $list = self::getGuestSortIds();
        $list = array_values(array_filter($list, fn($id) => $id !== $productId));
        Session::set('guest_sort_list', $list);
        return true;
    }

    public static function removeMultipleFromGuestSort(array $productIds): bool
    {
        Session::start();
        $toRemove = array_map('intval', $productIds);
        $list = self::getGuestSortIds();
        $list = array_values(array_filter($list, fn($id) => !in_array($id, $toRemove, true)));
        Session::set('guest_sort_list', $list);
        return true;
    }

    public static function clearGuestSort(): bool
    {
        Session::start();
        Session::set('guest_sort_list', []);
        return true;
    }

    public static function isInGuestSort(int $productId): bool
    {
        return in_array($productId, self::getGuestSortIds(), true);
    }

    public static function countGuestSort(): int
    {
        return count(self::getGuestSortIds());
    }

    /**
     * Get product records for guest sort list (matches format of getBuyerSortList)
     */
    public static function getGuestSortProducts(): array
    {
        $ids = self::getGuestSortIds();
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return self::fetchAll("
            SELECT 0 AS favourite_id, NOW() AS favourited_at,
                   p.id, p.title AS name, p.slug, p.primary_image, p.min_price, p.max_price,
                   p.moq_quantity, p.status, p.approval_status, p.sku,
                   c.name AS category_name, c.slug AS category_slug,
                   s.name AS subcategory_name,
                   pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   sp.id AS supplier_id, sp.company_name AS supplier_name, sp.status AS supplier_status, sp.approval_status AS supplier_approval_status,
                   COALESCE(ct.name, 'India') AS country_name,
                   CASE 
                       WHEN p.id IS NULL THEN 0
                       WHEN p.status NOT IN ('PUBLISHED', 'ACTIVE') OR p.approval_status != 'APPROVED' THEN 0
                       WHEN sp.id IS NULL OR sp.status != 'ACTIVE' OR sp.approval_status != 'APPROVED' THEN 0
                       ELSE 1
                   END AS is_available,
                   CASE
                       WHEN p.id IS NULL THEN 'Product no longer exists'
                       WHEN p.status NOT IN ('PUBLISHED', 'ACTIVE') OR p.approval_status != 'APPROVED' THEN 'Product is unlisted or pending audit'
                       WHEN sp.id IS NULL OR sp.status != 'ACTIVE' OR sp.approval_status != 'APPROVED' THEN 'Supplier is currently inactive'
                       ELSE ''
                   END AS unavailable_reason
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            LEFT JOIN countries ct ON sp.country_id = ct.id
            WHERE p.id IN ($placeholders)
            ORDER BY FIELD(p.id, " . implode(',', $ids) . ")
        ", $ids);
    }

    /**
     * Merge guest sort list items into buyer's database record upon login
     */
    public static function mergeGuestSortIntoBuyer(int $buyerUserId): void
    {
        $guestIds = self::getGuestSortIds();
        if (empty($guestIds)) {
            return;
        }

        foreach ($guestIds as $productId) {
            self::addToSortList($buyerUserId, (int)$productId);
        }

        self::clearGuestSort();
    }
}
