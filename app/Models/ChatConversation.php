<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ChatConversation extends Model
{
    /**
     * Find existing conversation between buyer and supplier (optionally specific product) or create new one
     */
    public static function findOrCreate(int $buyerId, int $supplierId, ?int $productId = null, ?int $enquiryId = null): array
    {
        $sql = "
            SELECT * FROM `chat_conversations`
            WHERE `buyer_id` = :buyer_id 
              AND `supplier_id` = :supplier_id
        ";
        $params = [
            ':buyer_id' => $buyerId,
            ':supplier_id' => $supplierId
        ];

        if ($productId !== null) {
            $sql .= " AND `product_id` = :product_id";
            $params[':product_id'] = $productId;
        } else {
            $sql .= " AND `product_id` IS NULL";
        }

        $sql .= " ORDER BY `id` DESC LIMIT 1";

        $existing = self::fetch($sql, $params);
        if ($existing) {
            return $existing;
        }

        // Create new conversation
        $insertSql = "
            INSERT INTO `chat_conversations` (
                `buyer_id`, `supplier_id`, `product_id`, `enquiry_id`, `status`, `deal_status`, `last_message_at`, `created_at`
            ) VALUES (
                :buyer_id, :supplier_id, :product_id, :enquiry_id, 'ACTIVE', 'INITIAL_CONTACT', NOW(), NOW()
            )
        ";
        self::execute($insertSql, [
            ':buyer_id' => $buyerId,
            ':supplier_id' => $supplierId,
            ':product_id' => $productId,
            ':enquiry_id' => $enquiryId
        ]);

        $id = (int) self::db()->lastInsertId();
        return self::findById($id) ?? [];
    }

    /**
     * Find conversation by ID with detailed buyer, supplier, and product attributes
     */
    public static function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                c.*,
                b.name AS buyer_first_name,
                '' AS buyer_last_name,
                b.email AS buyer_email,
                b.company_name AS buyer_company,
                b.phone AS buyer_phone,
                s.company_name AS supplier_company_name,
                s.contact_person AS supplier_contact_person,
                s.phone AS supplier_phone,
                s.user_id AS supplier_user_id,
                su.email AS supplier_email,
                p.title AS product_title,
                p.slug AS product_slug,
                p.price AS product_price,
                p.moq_quantity AS product_moq,
                p.primary_image AS product_image
            FROM `chat_conversations` c
            LEFT JOIN `users` b ON c.buyer_id = b.id
            LEFT JOIN `suppliers` s ON c.supplier_id = s.id
            LEFT JOIN `users` su ON s.user_id = su.id
            LEFT JOIN `products` p ON c.product_id = p.id
            WHERE c.id = :id
            LIMIT 1
        ";
        return self::fetch($sql, [':id' => $id]);
    }

    /**
     * Get conversations for buyer with last message snippet, unread count, and product info
     */
    public static function getForBuyer(int $buyerId, array $filters = []): array
    {
        $sql = "
            SELECT 
                c.*,
                s.company_name AS supplier_company_name,
                s.contact_person AS supplier_contact_person,
                s.user_id AS supplier_user_id,
                p.title AS product_title,
                p.slug AS product_slug,
                p.price AS product_price,
                p.moq_quantity AS product_moq,
                p.primary_image AS product_image,
                (
                    SELECT m.message_body 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id AND m.is_internal_admin_note = 0
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_message_body,
                (
                    SELECT m.message_type 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id AND m.is_internal_admin_note = 0
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_message_type,
                (
                    SELECT COUNT(*) 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id 
                      AND m.sender_user_id != :buyer_id_count
                      AND m.delivery_status != 'READ'
                      AND m.is_internal_admin_note = 0
                ) AS unread_count,
                (
                    SELECT COUNT(*) 
                    FROM `chat_blocks` bl 
                    WHERE bl.blocker_user_id = :buyer_id_blk AND bl.blocked_user_id = s.user_id AND bl.status = 'BLOCKED'
                ) AS is_blocked_by_me,
                (
                    SELECT COUNT(*) 
                    FROM `chat_blocks` bl 
                    WHERE bl.blocker_user_id = s.user_id AND bl.blocked_user_id = :buyer_id_blked AND bl.status = 'BLOCKED'
                ) AS is_blocked_by_other
            FROM `chat_conversations` c
            LEFT JOIN `suppliers` s ON c.supplier_id = s.id
            LEFT JOIN `products` p ON c.product_id = p.id
            WHERE c.buyer_id = :buyer_id
        ";

        $params = [
            ':buyer_id' => $buyerId,
            ':buyer_id_count' => $buyerId,
            ':buyer_id_blk' => $buyerId,
            ':buyer_id_blked' => $buyerId
        ];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND c.status = :status";
            $params[':status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.company_name LIKE :search OR p.title LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY COALESCE(c.last_message_at, c.created_at) DESC";

        return self::fetchAll($sql, $params);
    }

    /**
     * Get conversations for supplier with last message snippet, unread count, and product info
     */
    public static function getForSupplier(int $supplierId, array $filters = []): array
    {
        $supplier = Supplier::findById($supplierId);
        $supplierUserId = $supplier ? (int) $supplier['user_id'] : 0;

        $sql = "
            SELECT 
                c.*,
                b.name AS buyer_first_name,
                '' AS buyer_last_name,
                b.company_name AS buyer_company,
                b.email AS buyer_email,
                p.title AS product_title,
                p.slug AS product_slug,
                p.price AS product_price,
                p.moq_quantity AS product_moq,
                p.primary_image AS product_image,
                (
                    SELECT m.message_body 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id AND m.is_internal_admin_note = 0
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_message_body,
                (
                    SELECT m.message_type 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id AND m.is_internal_admin_note = 0
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_message_type,
                (
                    SELECT m.sender_role
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id AND m.is_internal_admin_note = 0
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_sender_role,
                (
                    SELECT COUNT(*) 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id 
                      AND m.sender_user_id != :supplier_uid_count
                      AND m.delivery_status != 'READ'
                      AND m.is_internal_admin_note = 0
                ) AS unread_count,
                (
                    SELECT COUNT(*) 
                    FROM `chat_offers` o 
                    WHERE o.conversation_id = c.id AND o.status = 'PENDING_ACCEPTANCE'
                ) AS pending_offers_count,
                (
                    SELECT COUNT(*) 
                    FROM `chat_blocks` bl 
                    WHERE bl.blocker_user_id = :supplier_uid_blk AND bl.blocked_user_id = c.buyer_id AND bl.status = 'BLOCKED'
                ) AS is_blocked_by_me,
                (
                    SELECT COUNT(*) 
                    FROM `chat_blocks` bl 
                    WHERE bl.blocker_user_id = c.buyer_id AND bl.blocked_user_id = :supplier_uid_blked AND bl.status = 'BLOCKED'
                ) AS is_blocked_by_other
            FROM `chat_conversations` c
            LEFT JOIN `users` b ON c.buyer_id = b.id
            LEFT JOIN `products` p ON c.product_id = p.id
            WHERE c.supplier_id = :supplier_id
        ";

        $params = [
            ':supplier_id' => $supplierId,
            ':supplier_uid_count' => $supplierUserId,
            ':supplier_uid_blk' => $supplierUserId,
            ':supplier_uid_blked' => $supplierUserId
        ];

        // Specific tab filters
        $tab = $filters['tab'] ?? 'all';
        if ($tab === 'unread') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM `chat_messages` m 
                WHERE m.conversation_id = c.id 
                  AND m.sender_user_id != :tab_uid 
                  AND m.delivery_status != 'READ'
                  AND m.is_internal_admin_note = 0
            )";
            $params[':tab_uid'] = $supplierUserId;
        } elseif ($tab === 'awaiting_reply') {
            $sql .= " AND (
                SELECT m.sender_role 
                FROM `chat_messages` m 
                WHERE m.conversation_id = c.id AND m.is_internal_admin_note = 0
                ORDER BY m.id DESC LIMIT 1
            ) = 'BUYER'";
        } elseif ($tab === 'negotiations') {
            $sql .= " AND c.status = 'NEGOTIATION_ONGOING'";
        } elseif ($tab === 'offers_pending') {
            $sql .= " AND c.status = 'OFFER_PENDING'";
        } elseif ($tab === 'deals_accepted') {
            $sql .= " AND c.status IN ('DEAL_AGREED', 'DEAL_COMPLETED')";
        } elseif ($tab === 'blocked') {
            $sql .= " AND (c.status = 'BLOCKED' OR EXISTS (
                SELECT 1 FROM `chat_blocks` bl 
                WHERE (bl.blocker_user_id = :tab_blk_me AND bl.blocked_user_id = c.buyer_id AND bl.status = 'BLOCKED')
                   OR (bl.blocker_user_id = c.buyer_id AND bl.blocked_user_id = :tab_blk_other AND bl.status = 'BLOCKED')
            ))";
            $params[':tab_blk_me'] = $supplierUserId;
            $params[':tab_blk_other'] = $supplierUserId;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (b.company_name LIKE :search OR b.name LIKE :search OR p.title LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY COALESCE(c.last_message_at, c.created_at) DESC";

        return self::fetchAll($sql, $params);
    }

    /**
     * Get all conversations across platform for Super Admin
     */
    public static function getAllForAdmin(array $filters = []): array
    {
        $sql = "
            SELECT 
                c.*,
                b.name AS buyer_first_name,
                '' AS buyer_last_name,
                b.company_name AS buyer_company,
                b.email AS buyer_email,
                b.phone AS buyer_phone,
                s.company_name AS supplier_company_name,
                s.contact_person AS supplier_contact_person,
                s.phone AS supplier_phone,
                s.user_id AS supplier_user_id,
                su.email AS supplier_email,
                p.title AS product_title,
                p.slug AS product_slug,
                p.price AS product_price,
                p.moq_quantity AS product_moq,
                p.primary_image AS product_image,
                (
                    SELECT m.message_body 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_message_body,
                (
                    SELECT COUNT(*) 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id
                ) AS total_messages_count,
                (
                    SELECT COUNT(*) 
                    FROM `chat_messages` m 
                    WHERE m.conversation_id = c.id AND m.has_restricted_contact = 1
                ) AS flagged_messages_count,
                (
                    SELECT COUNT(*) 
                    FROM `chat_reports` rep 
                    WHERE rep.conversation_id = c.id AND rep.status = 'PENDING'
                ) AS active_reports_count
            FROM `chat_conversations` c
            LEFT JOIN `users` b ON c.buyer_id = b.id
            LEFT JOIN `suppliers` s ON c.supplier_id = s.id
            LEFT JOIN `users` su ON s.user_id = su.id
            LEFT JOIN `products` p ON c.product_id = p.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND c.status = :status";
            $params[':status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['supplier_id'])) {
            $sql .= " AND c.supplier_id = :supplier_id";
            $params[':supplier_id'] = (int) $filters['supplier_id'];
        }

        if (!empty($filters['flagged']) && $filters['flagged'] === '1') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM `chat_messages` m 
                WHERE m.conversation_id = c.id AND m.has_restricted_contact = 1
            )";
        }

        if (!empty($filters['reported']) && $filters['reported'] === '1') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM `chat_reports` rep 
                WHERE rep.conversation_id = c.id AND rep.status IN ('PENDING', 'UNDER_REVIEW')
            )";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (
                b.company_name LIKE :search 
                OR b.first_name LIKE :search 
                OR b.last_name LIKE :search 
                OR b.email LIKE :search 
                OR s.company_name LIKE :search 
                OR p.title LIKE :search
            )";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY COALESCE(c.last_message_at, c.created_at) DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int) $filters['offset'];
            }
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Get platform-wide overview statistics for Super Admin Communication Center
     */
    public static function getStats(): array
    {
        $db = self::db();
        $totalConversations = (int) $db->query("SELECT COUNT(*) FROM `chat_conversations`")->fetchColumn();
        $activeNegotiations = (int) $db->query("SELECT COUNT(*) FROM `chat_conversations` WHERE `status` = 'NEGOTIATION_ONGOING'")->fetchColumn();
        $pendingOffers = (int) $db->query("SELECT COUNT(*) FROM `chat_offers` WHERE `status` = 'PENDING_ACCEPTANCE'")->fetchColumn();
        $dealsAgreed = (int) $db->query("SELECT COUNT(*) FROM `chat_conversations` WHERE `status` IN ('DEAL_AGREED', 'DEAL_COMPLETED')")->fetchColumn();
        $flaggedMessages = (int) $db->query("SELECT COUNT(*) FROM `chat_messages` WHERE `has_restricted_contact` = 1")->fetchColumn();
        $pendingReports = (int) $db->query("SELECT COUNT(*) FROM `chat_reports` WHERE `status` = 'PENDING'")->fetchColumn();

        return [
            'total_conversations' => $totalConversations,
            'active_negotiations' => $activeNegotiations,
            'pending_offers' => $pendingOffers,
            'deals_agreed' => $dealsAgreed,
            'flagged_messages' => $flaggedMessages,
            'pending_reports' => $pendingReports
        ];
    }

    /**
     * Update conversation status and optional deal status
     */
    public static function updateStatus(int $id, string $status, ?string $dealStatus = null): bool
    {
        $sql = "UPDATE `chat_conversations` SET `status` = :status";
        $params = [':status' => $status, ':id' => $id];

        if ($dealStatus !== null) {
            $sql .= ", `deal_status` = :deal_status";
            $params[':deal_status'] = $dealStatus;
        }

        $sql .= ", `updated_at` = NOW() WHERE `id` = :id";
        $stmt = self::execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Touch last_message_at timestamp
     */
    public static function touchLastMessage(int $id): bool
    {
        $stmt = self::execute("UPDATE `chat_conversations` SET `last_message_at` = NOW() WHERE `id` = :id", [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get total unread message count for a given user based on their role
     */
    public static function getTotalUnreadCountForUser(int $userId, string $role): int
    {
        $db = self::db();
        if ($role === 'BUYER') {
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM `chat_messages` m
                JOIN `chat_conversations` c ON m.conversation_id = c.id
                WHERE c.buyer_id = :uid 
                  AND m.sender_user_id != :uid2 
                  AND m.delivery_status != 'READ'
                  AND m.is_internal_admin_note = 0
            ");
            $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
            return (int) $stmt->fetchColumn();
        } elseif ($role === 'SUPPLIER') {
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM `chat_messages` m
                JOIN `chat_conversations` c ON m.conversation_id = c.id
                JOIN `suppliers` s ON c.supplier_id = s.id
                WHERE s.user_id = :uid 
                  AND m.sender_user_id != :uid2 
                  AND m.delivery_status != 'READ'
                  AND m.is_internal_admin_note = 0
            ");
            $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
            return (int) $stmt->fetchColumn();
        }

        return 0;
    }
}
