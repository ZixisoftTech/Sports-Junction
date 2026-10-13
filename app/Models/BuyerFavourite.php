<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class BuyerFavourite extends Model
{
    public static function add(int $userId, int $productId): bool
    {
        // Check if product is published and approved
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

    public static function remove(int $userId, int $productId): bool
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

    public static function isFavourite(int $userId, int $productId): bool
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

    public static function getBuyerFavourites(int $userId): array
    {
        return self::fetchAll("
            SELECT bf.id AS favourite_id, bf.created_at AS favourited_at,
                   p.id, p.title AS name, p.slug, p.primary_image, p.min_price, p.max_price,
                   p.moq_quantity, p.status, p.approval_status,
                   c.name AS category_name, c.slug AS category_slug,
                   pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   sp.company_name AS supplier_name
            FROM buyer_favourites bf
            JOIN products p ON bf.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers sp ON p.supplier_id = sp.id
            WHERE bf.user_id = :user_id
              AND p.status IN ('PUBLISHED', 'ACTIVE')
              AND p.approval_status = 'APPROVED'
              AND (sp.approval_status = 'APPROVED' AND sp.status = 'ACTIVE')
              AND c.status = 'ACTIVE'
              AND (p.subcategory_id IS NULL OR s.status = 'ACTIVE')
            ORDER BY bf.created_at DESC
        ", [':user_id' => $userId]);
    }
}
