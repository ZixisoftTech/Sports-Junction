<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class ProductInquiry extends Model
{
    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO product_inquiries (
                product_id, supplier_id, buyer_user_id, buyer_name, buyer_email, buyer_phone,
                company_name, target_quantity, lead_time_req, specifications,
                status, ip_address, created_at
            ) VALUES (
                :product_id, :supplier_id, :buyer_user_id, :buyer_name, :buyer_email, :buyer_phone,
                :company_name, :target_quantity, :lead_time_req, :specifications,
                'PENDING', :ip_address, NOW()
            )
        ", [
            ':product_id'       => !empty($data['product_id']) ? (int) $data['product_id'] : null,
            ':supplier_id'      => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            ':buyer_user_id'    => !empty($data['buyer_user_id']) ? (int) $data['buyer_user_id'] : null,
            ':buyer_name'       => trim($data['buyer_name'] ?? 'Guest Buyer'),
            ':buyer_email'      => trim($data['buyer_email'] ?? ''),
            ':buyer_phone'      => !empty($data['buyer_phone']) ? trim($data['buyer_phone']) : null,
            ':company_name'     => !empty($data['company_name']) ? trim($data['company_name']) : null,
            ':target_quantity'  => max(1, (int) ($data['target_quantity'] ?? 1)),
            ':lead_time_req'    => !empty($data['lead_time_req']) ? trim($data['lead_time_req']) : null,
            ':specifications'   => trim($data['specifications'] ?? ''),
            ':ip_address'       => !empty($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : null,
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'ProductInquiry', $id, trim($data['buyer_email'] ?? ''), null, 'Submitted product inquiry');
        return $id;
    }

    public static function findById(int $id): ?array
    {
        return self::fetch("
            SELECT i.*, 
                   p.title AS product_name, p.slug AS product_slug, p.primary_image,
                   p.min_price, p.max_price, pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   s.company_name AS supplier_name, s.email AS supplier_email, s.phone AS supplier_phone
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            WHERE i.id = :id
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function getBuyerEnquiries(int $buyerUserId, ?string $email = null): array
    {
        $where = ["(i.buyer_user_id = :uid" . (!empty($email) ? " OR LOWER(i.buyer_email) = :email" : "") . ")"];
        $bindings = [':uid' => $buyerUserId];
        if (!empty($email)) {
            $bindings[':email'] = strtolower(trim($email));
        }

        $whereClause = implode(' AND ', $where);

        return self::fetchAll("
            SELECT i.*, 
                   p.title AS product_name, p.slug AS product_slug, p.primary_image,
                   p.min_price, p.max_price, pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   s.company_name AS supplier_name, s.email AS supplier_email,
                   (SELECT COUNT(*) FROM inquiry_replies r WHERE r.inquiry_id = i.id) AS reply_count
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            WHERE {$whereClause}
            ORDER BY i.created_at DESC
        ", $bindings);
    }

    public static function getSupplierEnquiries(int $supplierId): array
    {
        return self::fetchAll("
            SELECT i.*, 
                   p.title AS product_name, p.slug AS product_slug, p.primary_image,
                   (SELECT COUNT(*) FROM inquiry_replies r WHERE r.inquiry_id = i.id) AS reply_count
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            WHERE i.supplier_id = :supplier_id
            ORDER BY i.created_at DESC
        ", [':supplier_id' => $supplierId]);
    }

    public static function getReplies(int $inquiryId): array
    {
        return self::fetchAll("
            SELECT r.*, u.name AS sender_name, u.role AS sender_user_role
            FROM inquiry_replies r
            LEFT JOIN users u ON r.sender_user_id = u.id
            WHERE r.inquiry_id = :inquiry_id
            ORDER BY r.created_at ASC
        ", [':inquiry_id' => $inquiryId]);
    }

    public static function addReply(int $inquiryId, int $senderUserId, string $senderRole, string $message): int
    {
        self::execute("
            INSERT INTO inquiry_replies (inquiry_id, sender_user_id, sender_role, message, created_at)
            VALUES (:inquiry_id, :sender_user_id, :sender_role, :message, NOW())
        ", [
            ':inquiry_id'      => $inquiryId,
            ':sender_user_id'  => $senderUserId,
            ':sender_role'     => $senderRole,
            ':message'         => trim($message),
        ]);

        // Update inquiry status to RESPONDED if sender is supplier/admin
        if ($senderRole === 'SUPPLIER' || $senderRole === 'SUPER_ADMIN') {
            self::execute("
                UPDATE product_inquiries SET status = 'RESPONDED' WHERE id = :id AND status = 'PENDING'
            ", [':id' => $inquiryId]);
        }

        return (int) self::lastInsertId();
    }

    public static function allRecent(int $limit = 20): array
    {
        return self::fetchAll("
            SELECT i.*, p.title as product_title, p.slug as product_slug, s.company_name as supplier_name
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            ORDER BY i.created_at DESC
            LIMIT :lim
        ", [':lim' => $limit]);
    }
}
