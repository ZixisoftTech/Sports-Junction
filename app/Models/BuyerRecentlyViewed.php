<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class BuyerRecentlyViewed extends Model
{
    public static function recordView(int $userId, int $productId): bool
    {
        // Check if product is published and approved
        $product = Product::findById($productId);
        if (!$product || !in_array($product['status'] ?? '', ['PUBLISHED', 'ACTIVE']) || ($product['approval_status'] ?? '') !== 'APPROVED') {
            return false;
        }

        // Upsert view
        self::execute("
            INSERT INTO buyer_recently_viewed (user_id, product_id, viewed_at)
            VALUES (:user_id, :product_id, NOW())
            ON DUPLICATE KEY UPDATE viewed_at = NOW()
        ", [
            ':user_id'    => $userId,
            ':product_id' => $productId,
        ]);

        // Keep only the last 20 unique viewed products
        $oldIds = self::fetchAll("
            SELECT id FROM buyer_recently_viewed
            WHERE user_id = :user_id
            ORDER BY viewed_at DESC
            LIMIT 100 OFFSET 20
        ", [':user_id' => $userId]);

        if (!empty($oldIds)) {
            $idsToDelete = array_column($oldIds, 'id');
            $inClause = implode(',', array_map('intval', $idsToDelete));
            self::execute("DELETE FROM buyer_recently_viewed WHERE id IN ({$inClause})");
        }

        return true;
    }

    public static function getRecent(int $userId, int $limit = 20): array
    {
        return self::fetchAll("
            SELECT brv.id AS view_record_id, brv.viewed_at,
                   p.id, p.title AS name, p.slug, p.primary_image, p.min_price, p.max_price,
                   p.moq_quantity, p.status, p.approval_status,
                   c.name AS category_name, c.slug AS category_slug,
                   pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   sp.company_name AS supplier_name
            FROM buyer_recently_viewed brv
            JOIN products p ON brv.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            WHERE brv.user_id = :user_id
              AND p.status IN ('PUBLISHED', 'ACTIVE')
              AND p.approval_status = 'APPROVED'
              AND (sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')
              AND c.status = 'ACTIVE'
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE')
            ORDER BY brv.viewed_at DESC
            LIMIT :lim
        ", [
            ':user_id' => $userId,
            ':lim'     => $limit,
        ]);
    }

    public static function clearHistory(int $userId): bool
    {
        self::execute("
            DELETE FROM buyer_recently_viewed
            WHERE user_id = :user_id
        ", [':user_id' => $userId]);

        return true;
    }
}
