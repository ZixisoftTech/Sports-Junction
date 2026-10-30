<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;
use Throwable;

class Notification extends Model
{
    /**
     * Map notification types to categories and presentation metadata
     */
    public static function getMeta(string $type): array
    {
        return match ($type) {
            'ENQUIRY_SAMPLE' => [
                'category'   => 'enquiry',
                'label'      => 'Prototype Sample',
                'icon'       => 'bi-box-seam',
                'badgeClass' => 'bg-info-subtle text-info border-info',
                'dotClass'   => 'bg-info'
            ],
            'ENQUIRY_RFQ' => [
                'category'   => 'enquiry',
                'label'      => 'RFQ Submission',
                'icon'       => 'bi-clipboard2-check',
                'badgeClass' => 'bg-primary-subtle text-primary border-primary',
                'dotClass'   => 'bg-primary'
            ],
            'ENQUIRY_FAST_OEM' => [
                'category'   => 'enquiry',
                'label'      => 'Fast OEM Request',
                'icon'       => 'bi-lightning-charge',
                'badgeClass' => 'bg-warning-subtle text-warning border-warning',
                'dotClass'   => 'bg-warning'
            ],
            'ENQUIRY_CONTACT' => [
                'category'   => 'enquiry',
                'label'      => 'Contact Us Message',
                'icon'       => 'bi-envelope',
                'badgeClass' => 'bg-secondary-subtle text-secondary border-secondary',
                'dotClass'   => 'bg-secondary'
            ],
            'ENQUIRY_PRODUCT' => [
                'category'   => 'enquiry',
                'label'      => 'Product Enquiry',
                'icon'       => 'bi-chat-left-quote',
                'badgeClass' => 'bg-primary-subtle text-primary border-primary',
                'dotClass'   => 'bg-primary'
            ],
            'ENQUIRY_GENERAL' => [
                'category'   => 'enquiry',
                'label'      => 'General RFQ',
                'icon'       => 'bi-question-circle',
                'badgeClass' => 'bg-dark-subtle text-dark border-dark',
                'dotClass'   => 'bg-dark'
            ],
            'NEW_CHAT_MESSAGE' => [
                'category'   => 'message',
                'label'      => 'Chat Message',
                'icon'       => 'bi-chat-dots-fill',
                'badgeClass' => 'bg-success-subtle text-success border-success',
                'dotClass'   => 'bg-success'
            ],
            'PRODUCT_PENDING_APPROVAL' => [
                'category'   => 'product',
                'label'      => 'Product Pending Approval',
                'icon'       => 'bi-patch-question',
                'badgeClass' => 'bg-warning-subtle text-warning border-warning',
                'dotClass'   => 'bg-warning'
            ],
            'PRODUCT_APPROVAL_DECISION' => [
                'category'   => 'product',
                'label'      => 'Product Approval Decision',
                'icon'       => 'bi-patch-check',
                'badgeClass' => 'bg-info-subtle text-info border-info',
                'dotClass'   => 'bg-info'
            ],
            'SUPPLIER_REGISTRATION' => [
                'category'   => 'supplier',
                'label'      => 'New Supplier Registration',
                'icon'       => 'bi-person-plus',
                'badgeClass' => 'bg-primary-subtle text-primary border-primary',
                'dotClass'   => 'bg-primary'
            ],
            'SAFETY_REPORT' => [
                'category'   => 'report',
                'label'      => 'Safety & Misconduct Report',
                'icon'       => 'bi-shield-exclamation',
                'badgeClass' => 'bg-danger-subtle text-danger border-danger',
                'dotClass'   => 'bg-danger'
            ],
            default => [
                'category'   => 'other',
                'label'      => 'System Notification',
                'icon'       => 'bi-bell',
                'badgeClass' => 'bg-secondary-subtle text-secondary border-secondary',
                'dotClass'   => 'bg-secondary'
            ],
        };
    }

    /**
     * Relative human-readable time calculation
     */
    public static function timeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        if (!$time) {
            return 'Just now';
        }
        $diff = time() - $time;
        if ($diff < 45) {
            return 'Just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        if ($diff < 172800) {
            return 'Yesterday';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        }
        return date('d M Y', $time);
    }

    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `type` VARCHAR(50) NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `message` TEXT NOT NULL,
                    `link` VARCHAR(255) NULL,
                    `related_type` VARCHAR(50) NULL,
                    `related_id` INT NULL,
                    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_notif_user (`user_id`),
                    INDEX idx_notif_read (`is_read`),
                    INDEX idx_notif_created (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $cols = self::fetchAll("DESCRIBE `notifications`");
            $colNames = array_column($cols, 'Field');

            if (!in_array('related_type', $colNames, true)) {
                self::execute("ALTER TABLE `notifications` ADD COLUMN `related_type` VARCHAR(50) NULL AFTER `link`");
            }
            if (!in_array('related_id', $colNames, true)) {
                self::execute("ALTER TABLE `notifications` ADD COLUMN `related_id` INT NULL AFTER `related_type`");
            }
        } catch (\Throwable $e) {
            @error_log("Notification::ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Create a notification. Avoids duplicates if related_type and related_id match.
     */
    public static function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): int {
        self::ensureSchema();
        try {
            // Check for existing duplicate if related reference exists
            if (!empty($relatedType) && !empty($relatedId)) {
                $existingId = self::fetchColumn("
                    SELECT id FROM notifications 
                    WHERE user_id = :uid 
                      AND type = :type 
                      AND related_type = :rtype 
                      AND related_id = :rid 
                    LIMIT 1
                ", [
                    ':uid'   => $userId,
                    ':type'  => $type,
                    ':rtype' => $relatedType,
                    ':rid'   => $relatedId,
                ]);

                if ($existingId) {
                    return (int) $existingId;
                }
            }

            self::execute("
                INSERT INTO `notifications` (
                    `user_id`, `type`, `title`, `message`, `link`, 
                    `related_type`, `related_id`, `is_read`, `created_at`
                ) VALUES (
                    :user_id, :type, :title, :message, :link, 
                    :related_type, :related_id, 0, NOW()
                )
            ", [
                ':user_id'      => $userId,
                ':type'         => $type,
                ':title'        => $title,
                ':message'      => $message,
                ':link'         => $link,
                ':related_type' => $relatedType,
                ':related_id'   => $relatedId,
            ]);

            return (int) self::lastInsertId();
        } catch (Throwable $e) {
            error_log("Notification::createNotification error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Dispatch notification to all active Super Admins
     */
    public static function notifySuperAdmins(
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): array {
        $createdIds = [];
        try {
            $admins = self::fetchAll("
                SELECT id FROM users 
                WHERE role = 'SUPER_ADMIN' 
                  AND status = 'ACTIVE'
            ");

            if (empty($admins)) {
                $admins = [['id' => 1]];
            }

            foreach ($admins as $admin) {
                $adminId = (int) $admin['id'];
                $id = self::createNotification($adminId, $type, $title, $message, $link, $relatedType, $relatedId);
                if ($id > 0) {
                    $createdIds[] = $id;
                }
            }
        } catch (Throwable $e) {
            error_log("Notification::notifySuperAdmins error: " . $e->getMessage());
        }

        return $createdIds;
    }

    /**
     * Unread notification count for an admin
     */
    public static function getUnreadCount(int $userId): int
    {
        self::ensureSchema();
        try {
            return (int) self::fetchColumn("
                SELECT COUNT(*) FROM `notifications` 
                WHERE `user_id` = :uid AND `is_read` = 0
            ", [':uid' => $userId]);
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Recent notifications for admin topbar dropdown (latest first)
     */
    public static function getRecentForAdmin(int $userId, int $limit = 8): array
    {
        self::ensureSchema();
        try {
            $rows = self::fetchAll("
                SELECT * FROM `notifications` 
                WHERE `user_id` = :uid 
                ORDER BY `created_at` DESC, `id` DESC 
                LIMIT :lim
            ", [
                ':uid' => $userId,
                ':lim' => $limit,
            ]);

            return array_map([self::class, 'formatRow'], $rows);
        } catch (Throwable $e) {
            error_log("Notification::getRecentForAdmin error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detailed query for Notification Center with filtering, search, and pagination
     */
    public static function getAdminNotifications(int $userId, array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ["user_id = :uid"];
        $bindings = [':uid' => $userId];

        // Filter: Tab / Category
        $tab = strtolower(trim($filters['tab'] ?? 'all'));
        if ($tab === 'unread') {
            $where[] = "is_read = 0";
        } elseif ($tab === 'enquiries' || $tab === 'enquiry') {
            $where[] = "type LIKE 'ENQUIRY_%'";
        } elseif ($tab === 'messages' || $tab === 'message') {
            $where[] = "type = 'NEW_CHAT_MESSAGE'";
        } elseif ($tab === 'products' || $tab === 'product') {
            $where[] = "type LIKE 'PRODUCT_%'";
        } elseif ($tab === 'suppliers' || $tab === 'supplier') {
            $where[] = "type LIKE 'SUPPLIER_%'";
        } elseif ($tab === 'reports' || $tab === 'report') {
            $where[] = "type = 'SAFETY_REPORT'";
        }

        // Filter: Search Keyword
        if (!empty($filters['search'])) {
            $kw = '%' . trim($filters['search']) . '%';
            $where[] = "(title LIKE :kw OR message LIKE :kw)";
            $bindings[':kw'] = $kw;
        }

        // Filter: Date Range
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= :date_from";
            $bindings[':date_from'] = trim($filters['date_from']) . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= :date_to";
            $bindings[':date_to'] = trim($filters['date_to']) . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) self::fetchColumn("SELECT COUNT(*) FROM `notifications` WHERE {$whereSql}", $bindings);

        $rows = self::fetchAll("
            SELECT * FROM `notifications` 
            WHERE {$whereSql} 
            ORDER BY `created_at` DESC, `id` DESC 
            LIMIT {$perPage} OFFSET {$offset}
        ", $bindings);

        $formatted = array_map([self::class, 'formatRow'], $rows);
        $totalPages = (int) ceil($total / $perPage);

        return [
            'data'         => $formatted,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => max(1, $totalPages),
            'counts'       => self::getCategoryCounts($userId),
        ];
    }

    /**
     * Category counts for tab badges
     */
    public static function getCategoryCounts(int $userId): array
    {
        try {
            $row = self::fetch("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread,
                    SUM(CASE WHEN type LIKE 'ENQUIRY_%' THEN 1 ELSE 0 END) AS enquiries,
                    SUM(CASE WHEN type = 'NEW_CHAT_MESSAGE' THEN 1 ELSE 0 END) AS messages,
                    SUM(CASE WHEN type LIKE 'PRODUCT_%' THEN 1 ELSE 0 END) AS products,
                    SUM(CASE WHEN type LIKE 'SUPPLIER_%' THEN 1 ELSE 0 END) AS suppliers,
                    SUM(CASE WHEN type = 'SAFETY_REPORT' THEN 1 ELSE 0 END) AS reports
                FROM `notifications`
                WHERE `user_id` = :uid
            ", [':uid' => $userId]);

            return [
                'total'     => (int) ($row['total'] ?? 0),
                'unread'    => (int) ($row['unread'] ?? 0),
                'enquiries' => (int) ($row['enquiries'] ?? 0),
                'messages'  => (int) ($row['messages'] ?? 0),
                'products'  => (int) ($row['products'] ?? 0),
                'suppliers' => (int) ($row['suppliers'] ?? 0),
                'reports'   => (int) ($row['reports'] ?? 0),
            ];
        } catch (Throwable $e) {
            return [
                'total'     => 0,
                'unread'    => 0,
                'enquiries' => 0,
                'messages'  => 0,
                'products'  => 0,
                'suppliers' => 0,
                'reports'   => 0,
            ];
        }
    }

    /**
     * Mark single notification as read
     */
    public static function markAsRead(int $userId, int $notificationId): bool
    {
        try {
            // If user is Super Admin, allow marking any notification as read
            $isSuperAdmin = false;
            if ($userId > 0) {
                $role = self::fetchColumn("SELECT role FROM users WHERE id = :uid LIMIT 1", [':uid' => $userId]);
                if (strtoupper((string)$role) === 'SUPER_ADMIN') {
                    $isSuperAdmin = true;
                }
            }

            if ($isSuperAdmin) {
                self::execute("
                    UPDATE `notifications` 
                    SET `is_read` = 1, `read_at` = NOW() 
                    WHERE `id` = :id
                ", [':id' => $notificationId]);
            } else {
                self::execute("
                    UPDATE `notifications` 
                    SET `is_read` = 1, `read_at` = NOW() 
                    WHERE `id` = :id AND `user_id` = :uid
                ", [
                    ':id'  => $notificationId,
                    ':uid' => $userId,
                ]);
            }
            return true;
        } catch (Throwable $e) {
            error_log("Notification::markAsRead error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark single notification as unread
     */
    public static function markAsUnread(int $userId, int $notificationId): bool
    {
        try {
            $isSuperAdmin = false;
            if ($userId > 0) {
                $role = self::fetchColumn("SELECT role FROM users WHERE id = :uid LIMIT 1", [':uid' => $userId]);
                if (strtoupper((string)$role) === 'SUPER_ADMIN') {
                    $isSuperAdmin = true;
                }
            }

            if ($isSuperAdmin) {
                self::execute("
                    UPDATE `notifications` 
                    SET `is_read` = 0, `read_at` = NULL 
                    WHERE `id` = :id
                ", [':id' => $notificationId]);
            } else {
                self::execute("
                    UPDATE `notifications` 
                    SET `is_read` = 0, `read_at` = NULL 
                    WHERE `id` = :id AND `user_id` = :uid
                ", [
                    ':id'  => $notificationId,
                    ':uid' => $userId,
                ]);
            }
            return true;
        } catch (Throwable $e) {
            error_log("Notification::markAsUnread error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for user (optionally scoped to a tab/category)
     */
    public static function markAllAsRead(int $userId, ?string $tab = null): int
    {
        try {
            $where = ["user_id = :uid", "is_read = 0"];
            $bindings = [':uid' => $userId];

            if ($tab === 'enquiries' || $tab === 'enquiry') {
                $where[] = "type LIKE 'ENQUIRY_%'";
            } elseif ($tab === 'messages' || $tab === 'message') {
                $where[] = "type = 'NEW_CHAT_MESSAGE'";
            } elseif ($tab === 'products' || $tab === 'product') {
                $where[] = "type LIKE 'PRODUCT_%'";
            } elseif ($tab === 'suppliers' || $tab === 'supplier') {
                $where[] = "type LIKE 'SUPPLIER_%'";
            } elseif ($tab === 'reports' || $tab === 'report') {
                $where[] = "type = 'SAFETY_REPORT'";
            }

            $whereSql = implode(' AND ', $where);

            $stmt = self::execute("
                UPDATE `notifications` 
                SET `is_read` = 1, `read_at` = NOW() 
                WHERE {$whereSql}
            ", $bindings);
            return (int) $stmt->rowCount();
        } catch (Throwable $e) {
            error_log("Notification::markAllAsRead error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Find single notification by ID
     */
    public static function findById(int $id, int $userId): ?array
    {
        $row = self::fetch("
            SELECT * FROM `notifications` 
            WHERE `id` = :id AND `user_id` = :uid 
            LIMIT 1
        ", [
            ':id'  => $id,
            ':uid' => $userId,
        ]);

        return $row ? self::formatRow($row) : null;
    }

    /**
     * Resolve the canonical Super Admin URL for any notification so that admin clicks
     * never land on buyer/supplier portal pages or trigger auth redirect loops.
     */
    public static function resolveAdminLink(array $notif): string
    {
        $link = trim((string)($notif['link'] ?? ''));
        $type = strtoupper(trim((string)($notif['type'] ?? '')));
        $relatedType = strtoupper(trim((string)($notif['related_type'] ?? '')));
        $relatedId = (int)($notif['related_id'] ?? 0);

        // 1. If link has an explicit conversation query parameter or path
        if (preg_match('/(?:conversation_id|conv)[=\/](\d+)/i', $link, $matches)) {
            return "/admin/communication/" . (int)$matches[1];
        }

        // 2. Chat message / Conversation notifications
        if ($type === 'NEW_CHAT_MESSAGE' || $relatedType === 'CONVERSATION' || str_contains($link, '/messages') || str_contains($link, 'communication')) {
            if ($relatedType === 'CONVERSATION' && $relatedId > 0) {
                // Check if relatedId is a valid conversation id
                $convExists = self::fetchColumn("SELECT id FROM chat_conversations WHERE id = :id LIMIT 1", [':id' => $relatedId]);
                if ($convExists) {
                    return "/admin/communication/{$relatedId}";
                }
                // Check if relatedId is a chat_message id
                $msgConvId = self::fetchColumn("SELECT conversation_id FROM chat_messages WHERE id = :id LIMIT 1", [':id' => $relatedId]);
                if ($msgConvId) {
                    return "/admin/communication/{$msgConvId}";
                }
            }
            if (preg_match('/\/admin\/communication\/(\d+)/', $link, $m)) {
                return "/admin/communication/" . (int)$m[1];
            }
            return "/admin/communication";
        }

        // 3. Enquiries
        if (str_starts_with($type, 'ENQUIRY_') || $relatedType === 'ENQUIRY' || str_contains($link, 'enquir')) {
            if ($relatedId > 0) {
                return "/admin/enquiries/{$relatedId}";
            }
            if (preg_match('/\/enquiries\/(\d+)/', $link, $matches)) {
                return "/admin/enquiries/" . (int)$matches[1];
            }
            if ($type === 'ENQUIRY_RFQ' || $type === 'ENQUIRY_FAST_OEM') {
                return "/admin/rfq-oem";
            }
            if ($type === 'ENQUIRY_SAMPLE') {
                return "/admin/sample-enquiries";
            }
            return "/admin/enquiries";
        }

        // 4. Products / Approvals
        if (str_starts_with($type, 'PRODUCT_') || $relatedType === 'PRODUCT' || str_contains($link, 'product')) {
            if ($relatedId > 0 && (str_contains($type, 'APPROVAL') || str_contains($link, 'approval'))) {
                return "/admin/product-approvals/{$relatedId}";
            }
            if (str_contains($type, 'APPROVAL') || str_contains($link, 'approval')) {
                return "/admin/product-approvals";
            }
            return "/admin/products";
        }

        // 5. Suppliers
        if (str_starts_with($type, 'SUPPLIER_') || $relatedType === 'SUPPLIER' || str_contains($link, 'supplier')) {
            if ($relatedId > 0) {
                return "/admin/suppliers/{$relatedId}";
            }
            return "/admin/suppliers";
        }

        // 6. Safety Reports
        if ($type === 'SAFETY_REPORT' || $relatedType === 'REPORT') {
            return "/admin/communication";
        }

        // 7. Fallback: If link already starts with /admin/, use it
        if (!empty($link) && str_starts_with($link, '/admin/')) {
            return $link;
        }

        // 8. Never let an admin link navigate to /buyer or /supplier
        if (str_starts_with($link, '/buyer') || str_starts_with($link, '/supplier')) {
            return "/admin/dashboard";
        }

        return !empty($link) ? $link : "/admin/notifications";
    }

    /**
     * Format a raw DB row with presentation metadata
     */
    private static function formatRow(array $row): array
    {
        $meta = self::getMeta($row['type'] ?? '');
        $row['time_ago']     = self::timeAgo($row['created_at'] ?? 'now');
        $row['category']     = $meta['category'];
        $row['type_label']   = $meta['label'];
        $row['icon']         = $meta['icon'];
        $row['badge_class']  = $meta['badgeClass'];
        $row['dot_class']    = $meta['dotClass'];
        $row['is_read']      = (int) ($row['is_read'] ?? 0);
        $row['link']         = self::resolveAdminLink($row);
        return $row;
    }

    /**
     * Synchronize actual recent business records (enquiries, messages, pending products/suppliers)
     * so that the Super Admin immediately has real historical records without fake data.
     */
    public static function syncExistingRecentEvents(int $adminUserId): void
    {
        try {
            // 1. Sync recent enquiries
            $recentEnquiries = self::fetchAll("
                SELECT id, enquiry_source, buyer_name, company_name, subject, status, created_at 
                FROM product_inquiries 
                ORDER BY id DESC 
                LIMIT 25
            ");

            foreach ($recentEnquiries as $enq) {
                $enqId = (int) $enq['id'];
                $src = strtoupper(trim($enq['enquiry_source'] ?? 'PRODUCT_PAGE'));
                $type = match ($src) {
                    'SAMPLE'     => 'ENQUIRY_SAMPLE',
                    'RFQ'        => 'ENQUIRY_RFQ',
                    'FAST_OEM'   => 'ENQUIRY_FAST_OEM',
                    'CONTACT_US' => 'ENQUIRY_CONTACT',
                    'GENERAL'    => 'ENQUIRY_GENERAL',
                    default      => 'ENQUIRY_PRODUCT'
                };

                $srcTitle = match ($src) {
                    'SAMPLE'     => 'Prototype Sample Request',
                    'RFQ'        => 'Request for Quotation (RFQ)',
                    'FAST_OEM'   => 'Fast OEM Customization Request',
                    'CONTACT_US' => 'Contact Us Enquiry',
                    'GENERAL'    => 'General Sourcing Requirement',
                    default      => 'Product Page Enquiry'
                };

                $buyerInfo = htmlspecialchars($enq['buyer_name'] ?: 'Guest Buyer');
                if (!empty($enq['company_name'])) {
                    $buyerInfo .= ' (' . htmlspecialchars($enq['company_name']) . ')';
                }

                $title = "{$srcTitle} from {$buyerInfo}";
                $message = $enq['subject'] ? $enq['subject'] : "New enquiry submission (#ENQ-" . str_pad((string)$enqId, 5, '0', STR_PAD_LEFT) . ") — Status: {$enq['status']}";
                $link = "/admin/enquiries/{$enqId}";

                // Insert with original creation timestamp if not exists
                $exists = self::fetchColumn("
                    SELECT id FROM notifications 
                    WHERE user_id = :uid AND related_type = 'ENQUIRY' AND related_id = :rid 
                    LIMIT 1
                ", [':uid' => $adminUserId, ':rid' => $enqId]);

                if (!$exists) {
                    self::execute("
                        INSERT INTO `notifications` (
                            `user_id`, `type`, `title`, `message`, `link`, 
                            `related_type`, `related_id`, `is_read`, `created_at`
                        ) VALUES (
                            :user_id, :type, :title, :message, :link, 
                            'ENQUIRY', :related_id, :is_read, :created_at
                        )
                    ", [
                        ':user_id'    => $adminUserId,
                        ':type'       => $type,
                        ':title'      => $title,
                        ':message'    => $message,
                        ':link'       => $link,
                        ':related_id' => $enqId,
                        ':is_read'    => in_array($enq['status'], ['RESOLVED', 'CLOSED', 'RESPONDED']) ? 1 : 0,
                        ':created_at' => $enq['created_at'],
                    ]);
                }
            }

            // 2. Sync recent chat messages sent by buyers or suppliers
            $recentMsgs = self::fetchAll("
                SELECT m.id, m.conversation_id, m.sender_role, m.message_body, m.created_at,
                       c.buyer_id, c.supplier_id,
                       b.name AS buyer_name, b.company_name AS buyer_company,
                       s.company_name AS supplier_name,
                       p.title AS product_title
                FROM chat_messages m
                JOIN chat_conversations c ON m.conversation_id = c.id
                LEFT JOIN users b ON c.buyer_id = b.id
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                LEFT JOIN products p ON c.product_id = p.id
                WHERE m.sender_role IN ('BUYER', 'SUPPLIER')
                ORDER BY m.id DESC
                LIMIT 20
            ");

            foreach ($recentMsgs as $m) {
                $msgId = (int) $m['id'];
                $convId = (int) $m['conversation_id'];
                $senderName = $m['sender_role'] === 'BUYER' 
                    ? ($m['buyer_name'] . (!empty($m['buyer_company']) ? " ({$m['buyer_company']})" : ''))
                    : ($m['supplier_name'] ?: 'Supplier');
                
                $title = "New Message from {$senderName} [{$m['sender_role']}]";
                $snippet = mb_substr(strip_tags((string)$m['message_body']), 0, 120);
                if (!empty($m['product_title'])) {
                    $snippet = "[Product: {$m['product_title']}] " . $snippet;
                }
                $link = "/admin/communication/{$convId}";

                $exists = self::fetchColumn("
                    SELECT id FROM notifications 
                    WHERE user_id = :uid AND related_type = 'CONVERSATION' AND related_id = :rid 
                    LIMIT 1
                ", [':uid' => $adminUserId, ':rid' => $msgId]);

                if (!$exists) {
                    self::execute("
                        INSERT INTO `notifications` (
                            `user_id`, `type`, `title`, `message`, `link`, 
                            `related_type`, `related_id`, `is_read`, `created_at`
                        ) VALUES (
                            :user_id, 'NEW_CHAT_MESSAGE', :title, :message, :link, 
                            'CONVERSATION', :related_id, 0, :created_at
                        )
                    ", [
                        ':user_id'    => $adminUserId,
                        ':title'      => $title,
                        ':message'    => $snippet,
                        ':link'       => $link,
                        ':related_id' => $msgId,
                        ':created_at' => $m['created_at'],
                    ]);
                }
            }

            // 3. Sync pending product submissions
            $pendingProducts = self::fetchAll("
                SELECT p.id, p.title, p.created_at, s.company_name AS supplier_name
                FROM products p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.approval_status = 'PENDING_APPROVAL'
                ORDER BY p.id DESC
                LIMIT 10
            ");

            foreach ($pendingProducts as $prod) {
                $pId = (int) $prod['id'];
                $suppName = $prod['supplier_name'] ?: 'Verified Manufacturer';
                $title = "Product Awaiting Approval: {$prod['title']}";
                $message = "Submitted by {$suppName}. Review product specifications and safety compliance.";
                $link = "/admin/product-approvals";

                $exists = self::fetchColumn("
                    SELECT id FROM notifications 
                    WHERE user_id = :uid AND related_type = 'PRODUCT' AND related_id = :rid 
                    LIMIT 1
                ", [':uid' => $adminUserId, ':rid' => $pId]);

                if (!$exists) {
                    self::execute("
                        INSERT INTO `notifications` (
                            `user_id`, `type`, `title`, `message`, `link`, 
                            `related_type`, `related_id`, `is_read`, `created_at`
                        ) VALUES (
                            :user_id, 'PRODUCT_PENDING_APPROVAL', :title, :message, :link, 
                            'PRODUCT', :related_id, 0, :created_at
                        )
                    ", [
                        ':user_id'    => $adminUserId,
                        ':title'      => $title,
                        ':message'    => $message,
                        ':link'       => $link,
                        ':related_id' => $pId,
                        ':created_at' => $prod['created_at'],
                    ]);
                }
            }

            // 4. Sync pending supplier registrations
            $pendingSuppliers = self::fetchAll("
                SELECT id, company_name, contact_person, email, created_at 
                FROM suppliers 
                WHERE approval_status IN ('PENDING_APPROVAL', 'PENDING_VERIFICATION')
                ORDER BY id DESC 
                LIMIT 10
            ");

            foreach ($pendingSuppliers as $supp) {
                $sId = (int) $supp['id'];
                $title = "New Supplier Registration: {$supp['company_name']}";
                $message = "Contact: {$supp['contact_person']} ({$supp['email']}). Awaiting verification.";
                $link = "/admin/suppliers";

                $exists = self::fetchColumn("
                    SELECT id FROM notifications 
                    WHERE user_id = :uid AND related_type = 'SUPPLIER' AND related_id = :rid 
                    LIMIT 1
                ", [':uid' => $adminUserId, ':rid' => $sId]);

                if (!$exists) {
                    self::execute("
                        INSERT INTO `notifications` (
                            `user_id`, `type`, `title`, `message`, `link`, 
                            `related_type`, `related_id`, `is_read`, `created_at`
                        ) VALUES (
                            :user_id, 'SUPPLIER_REGISTRATION', :title, :message, :link, 
                            'SUPPLIER', :related_id, 0, :created_at
                        )
                    ", [
                        ':user_id'    => $adminUserId,
                        ':title'      => $title,
                        ':message'    => $message,
                        ':link'       => $link,
                        ':related_id' => $sId,
                        ':created_at' => $supp['created_at'],
                    ]);
                }
            }
        } catch (Throwable $e) {
            error_log("Notification::syncExistingRecentEvents error: " . $e->getMessage());
        }
    }
}
