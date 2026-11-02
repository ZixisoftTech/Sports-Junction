<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class SupportQuery extends Model
{
    public const CATEGORIES = [
        'Order Related',
        'Payment Related',
        'Product Related',
        'Delivery & Shipping',
        'Account & Registration',
        'Technical Issue',
        'Seller / Buyer Related',
        'Other',
    ];

    public const STATUSES = [
        'NEW'                        => 'New',
        'IN_PROGRESS'                => 'In Progress',
        'AWAITING_CUSTOMER_RESPONSE' => 'Awaiting Customer Response',
        'RESOLVED'                   => 'Resolved',
        'CLOSED'                     => 'Closed',
    ];

    public const SOURCE_TYPES = ['BUYER', 'SELLER'];

    /**
     * Ensure database tables exist
     */
    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS support_queries (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ticket_id VARCHAR(50) NOT NULL UNIQUE,
                    source_type ENUM('BUYER', 'SELLER') NOT NULL,
                    name VARCHAR(150) NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    phone VARCHAR(50) NOT NULL,
                    category VARCHAR(100) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    order_id VARCHAR(100) NULL,
                    existing_ticket_id VARCHAR(100) NULL,
                    description TEXT NOT NULL,
                    attachment_path VARCHAR(255) NULL,
                    attachment_name VARCHAR(255) NULL,
                    attachment_size INT UNSIGNED NULL,
                    attachment_mime VARCHAR(100) NULL,
                    assigned_to INT UNSIGNED NULL,
                    status ENUM('NEW', 'IN_PROGRESS', 'AWAITING_CUSTOMER_RESPONSE', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'NEW',
                    internal_notes TEXT NULL,
                    user_id INT UNSIGNED NULL,
                    ip_address VARCHAR(45) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_source_type (source_type),
                    INDEX idx_ticket_id (ticket_id),
                    INDEX idx_status (status),
                    INDEX idx_category (category),
                    INDEX idx_assigned_to (assigned_to),
                    INDEX idx_order_id (order_id),
                    INDEX idx_existing_ticket_id (existing_ticket_id),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            self::execute("
                CREATE TABLE IF NOT EXISTS support_query_activities (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    query_id INT UNSIGNED NOT NULL,
                    user_id INT UNSIGNED NULL,
                    action_type VARCHAR(50) NOT NULL,
                    description TEXT NOT NULL,
                    old_value VARCHAR(255) NULL,
                    new_value VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_query_id (query_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_action_type (action_type),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (query_id) REFERENCES support_queries(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Throwable $e) {
            @error_log("SupportQuery ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Generate unique sequential Query / Ticket ID:
     * e.g. SJ-BUY-2026-00001 or SJ-SEL-2026-00001
     */
    public static function generateTicketId(string $sourceType): string
    {
        self::ensureSchema();
        $sourceType = strtoupper(trim($sourceType)) === 'SELLER' ? 'SELLER' : 'BUYER';
        $prefixCode = ($sourceType === 'SELLER') ? 'SEL' : 'BUY';
        $year = date('Y');
        $basePrefix = "SJ-{$prefixCode}-{$year}-";

        // Query the latest ticket for this year and source
        $row = self::fetch("
            SELECT ticket_id FROM support_queries 
            WHERE ticket_id LIKE :prefix 
            ORDER BY id DESC LIMIT 1
        ", [':prefix' => $basePrefix . '%']);

        $nextNum = 1;
        if (!empty($row['ticket_id'])) {
            $parts = explode('-', $row['ticket_id']);
            $lastNum = (int) end($parts);
            if ($lastNum > 0) {
                $nextNum = $lastNum + 1;
            }
        }

        // Loop safety check to guarantee uniqueness
        do {
            $candidate = sprintf('%s%05d', $basePrefix, $nextNum);
            $exists = self::fetch("SELECT id FROM support_queries WHERE ticket_id = :tid", [':tid' => $candidate]);
            if (!$exists) {
                return $candidate;
            }
            $nextNum++;
        } while ($nextNum < 999999);

        return $candidate;
    }

    /**
     * Create a new support query
     */
    public static function create(array $data): array
    {
        self::ensureSchema();

        $sourceType = strtoupper(trim((string)($data['source_type'] ?? 'BUYER'))) === 'SELLER' ? 'SELLER' : 'BUYER';
        $ticketId = !empty($data['ticket_id']) ? trim((string)$data['ticket_id']) : self::generateTicketId($sourceType);

        $name = trim((string)($data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $category = trim((string)($data['category'] ?? 'Other'));
        $subject = trim((string)($data['subject'] ?? ''));
        $orderId = !empty($data['order_id']) ? trim((string)$data['order_id']) : null;
        $existingTicketId = !empty($data['existing_ticket_id']) ? trim((string)$data['existing_ticket_id']) : null;
        $description = trim((string)($data['description'] ?? ''));

        $attachmentPath = !empty($data['attachment_path']) ? trim((string)$data['attachment_path']) : null;
        $attachmentName = !empty($data['attachment_name']) ? trim((string)$data['attachment_name']) : null;
        $attachmentSize = !empty($data['attachment_size']) ? (int)$data['attachment_size'] : null;
        $attachmentMime = !empty($data['attachment_mime']) ? trim((string)$data['attachment_mime']) : null;

        $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $ipAddress = !empty($data['ip_address']) ? trim((string)$data['ip_address']) : ($_SERVER['REMOTE_ADDR'] ?? null);

        self::execute("
            INSERT INTO support_queries (
                ticket_id, source_type, name, email, phone, category, subject,
                order_id, existing_ticket_id, description,
                attachment_path, attachment_name, attachment_size, attachment_mime,
                user_id, ip_address, status, created_at, updated_at
            ) VALUES (
                :ticket_id, :source_type, :name, :email, :phone, :category, :subject,
                :order_id, :existing_ticket_id, :description,
                :attachment_path, :attachment_name, :attachment_size, :attachment_mime,
                :user_id, :ip_address, 'NEW', NOW(), NOW()
            )
        ", [
            ':ticket_id'          => $ticketId,
            ':source_type'        => $sourceType,
            ':name'               => $name,
            ':email'              => $email,
            ':phone'              => $phone,
            ':category'           => $category,
            ':subject'            => $subject,
            ':order_id'           => $orderId,
            ':existing_ticket_id' => $existingTicketId,
            ':description'        => $description,
            ':attachment_path'    => $attachmentPath,
            ':attachment_name'    => $attachmentName,
            ':attachment_size'    => $attachmentSize,
            ':attachment_mime'    => $attachmentMime,
            ':user_id'            => $userId,
            ':ip_address'         => $ipAddress,
        ]);

        $queryId = (int) self::lastInsertId();

        // Initial creation activity
        self::addActivity(
            $queryId,
            $userId,
            'CREATED',
            "Support ticket #{$ticketId} submitted via " . ($sourceType === 'SELLER' ? 'Seller' : 'Buyer') . " Help Centre."
        );

        // If existing ticket ID was provided, log reciprocal linkage
        if ($existingTicketId) {
            $prevTicket = self::findByTicketId($existingTicketId);
            if ($prevTicket) {
                self::addActivity(
                    $queryId,
                    $userId,
                    'LINKED',
                    "User indicated follow-up to prior Ticket #{$existingTicketId}.",
                    null,
                    $existingTicketId
                );
                self::addActivity(
                    (int) $prevTicket['id'],
                    $userId,
                    'LINKED',
                    "Follow-up request received under new Ticket #{$ticketId}.",
                    null,
                    $ticketId
                );
            }
        }

        return self::findById($queryId);
    }

    /**
     * Find query by ID with joined staff assignee
     */
    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        $row = self::fetch("
            SELECT sq.*, 
                   u.name AS assigned_user_name,
                   u.email AS assigned_user_email,
                   creator.name AS creator_name,
                   creator.role AS creator_role
            FROM support_queries sq
            LEFT JOIN users u ON sq.assigned_to = u.id
            LEFT JOIN users creator ON sq.user_id = creator.id
            WHERE sq.id = :id
        ", [':id' => $id]);

        return $row ?: null;
    }

    /**
     * Find query by ticket ID
     */
    public static function findByTicketId(string $ticketId): ?array
    {
        self::ensureSchema();
        $row = self::fetch("
            SELECT sq.*, 
                   u.name AS assigned_user_name,
                   u.email AS assigned_user_email
            FROM support_queries sq
            LEFT JOIN users u ON sq.assigned_to = u.id
            WHERE sq.ticket_id = :tid
        ", [':tid' => trim($ticketId)]);

        return $row ?: null;
    }

    /**
     * Add activity / history log entry
     */
    public static function addActivity(
        int $queryId,
        ?int $userId,
        string $actionType,
        string $description,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        self::ensureSchema();
        try {
            self::execute("
                INSERT INTO support_query_activities (
                    query_id, user_id, action_type, description, old_value, new_value, created_at
                ) VALUES (
                    :query_id, :user_id, :action_type, :description, :old_value, :new_value, NOW()
                )
            ", [
                ':query_id'    => $queryId,
                ':user_id'     => $userId,
                ':action_type' => $actionType,
                ':description' => $description,
                ':old_value'   => $oldValue,
                ':new_value'   => $newValue,
            ]);
        } catch (Throwable $e) {
            @error_log("Failed to add support activity: " . $e->getMessage());
        }
    }

    /**
     * Get activities for a query
     */
    public static function getActivities(int $queryId): array
    {
        self::ensureSchema();
        return self::fetchAll("
            SELECT sqa.*, u.name AS actor_name, u.email AS actor_email, u.role AS actor_role
            FROM support_query_activities sqa
            LEFT JOIN users u ON sqa.user_id = u.id
            WHERE sqa.query_id = :qid
            ORDER BY sqa.created_at DESC, sqa.id DESC
        ", [':qid' => $queryId]);
    }

    /**
     * Update query status
     */
    public static function updateStatus(int $id, string $status, ?int $userId, ?string $internalNotes = null): bool
    {
        self::ensureSchema();
        $query = self::findById($id);
        if (!$query) return false;

        $status = strtoupper(trim($status));
        if (!array_key_exists($status, self::STATUSES)) {
            return false;
        }

        $oldStatus = $query['status'];
        $oldLabel = self::STATUSES[$oldStatus] ?? $oldStatus;
        $newLabel = self::STATUSES[$status];

        $updateSql = "UPDATE support_queries SET status = :st, updated_at = NOW()";
        $params = [':st' => $status, ':id' => $id];

        if ($internalNotes !== null && trim($internalNotes) !== '') {
            $notesToAppend = date('d M Y H:i') . " (" . ($status) . "): " . trim($internalNotes);
            $combinedNotes = !empty($query['internal_notes']) 
                ? $query['internal_notes'] . "\n\n" . $notesToAppend 
                : $notesToAppend;

            $updateSql .= ", internal_notes = :notes";
            $params[':notes'] = $combinedNotes;
        }

        $updateSql .= " WHERE id = :id";
        self::execute($updateSql, $params);

        if ($oldStatus !== $status) {
            self::addActivity(
                $id,
                $userId,
                'STATUS_CHANGE',
                "Status changed from {$oldLabel} to {$newLabel}" . ($internalNotes ? " with remarks: " . trim($internalNotes) : ""),
                $oldStatus,
                $status
            );
        } elseif ($internalNotes) {
            self::addActivity(
                $id,
                $userId,
                'NOTE_ADDED',
                "Internal remark added: " . trim($internalNotes)
            );
        }

        return true;
    }

    /**
     * Assign query to team member
     */
    public static function assignTo(int $id, ?int $assignedTo, ?int $userId): bool
    {
        self::ensureSchema();
        $query = self::findById($id);
        if (!$query) return false;

        $oldAssignee = $query['assigned_to'];
        $oldName = $query['assigned_user_name'] ?? 'Unassigned';

        $newName = 'Unassigned';
        if ($assignedTo) {
            $assignee = self::fetch("SELECT name FROM users WHERE id = :uid", [':uid' => $assignedTo]);
            if ($assignee) {
                $newName = $assignee['name'];
            } else {
                $assignedTo = null;
            }
        }

        self::execute("
            UPDATE support_queries 
            SET assigned_to = :assigned_to, updated_at = NOW() 
            WHERE id = :id
        ", [
            ':assigned_to' => $assignedTo,
            ':id'          => $id,
        ]);

        self::addActivity(
            $id,
            $userId,
            'ASSIGNED',
            "Ticket reassigned from {$oldName} to {$newName}.",
            $oldAssignee ? (string)$oldAssignee : null,
            $assignedTo ? (string)$assignedTo : null
        );

        return true;
    }

    /**
     * Append internal note
     */
    public static function addInternalNote(int $id, string $note, ?int $userId): bool
    {
        self::ensureSchema();
        $query = self::findById($id);
        if (!$query || trim($note) === '') return false;

        $note = trim($note);
        $user = $userId ? self::fetch("SELECT name FROM users WHERE id = :id", [':id' => $userId]) : null;
        $authorName = $user['name'] ?? 'Admin';

        $timestampedNote = "[" . date('d M Y H:i') . " by {$authorName}]:\n" . $note;
        $combinedNotes = !empty($query['internal_notes']) 
            ? $query['internal_notes'] . "\n\n" . $timestampedNote 
            : $timestampedNote;

        self::execute("
            UPDATE support_queries 
            SET internal_notes = :notes, updated_at = NOW() 
            WHERE id = :id
        ", [
            ':notes' => $combinedNotes,
            ':id'    => $id,
        ]);

        self::addActivity(
            $id,
            $userId,
            'NOTE_ADDED',
            "Internal note added: {$note}"
        );

        return true;
    }

    /**
     * Get Server-Side DataTables Response
     */
    public static function getAdminDataTable(array $params, string $sourceType): array
    {
        self::ensureSchema();
        $sourceType = strtoupper(trim($sourceType)) === 'SELLER' ? 'SELLER' : 'BUYER';

        $draw   = (int) ($params['draw'] ?? 1);
        $start  = max(0, (int) ($params['start'] ?? 0));
        $length = min(100, max(1, (int) ($params['length'] ?? 15)));

        $where = ["sq.source_type = :src"];
        $bindings = [':src' => $sourceType];

        // Status Filter
        $status = trim((string)($params['status'] ?? 'ALL'));
        if ($status !== '' && $status !== 'ALL') {
            $where[] = "sq.status = :status";
            $bindings[':status'] = $status;
        }

        // Category Filter
        $category = trim((string)($params['category'] ?? 'ALL'));
        if ($category !== '' && $category !== 'ALL') {
            $where[] = "sq.category = :category";
            $bindings[':category'] = $category;
        }

        // Assigned To Filter
        $assignedTo = trim((string)($params['assigned_to'] ?? 'ALL'));
        if ($assignedTo !== '' && $assignedTo !== 'ALL') {
            if ($assignedTo === 'UNASSIGNED') {
                $where[] = "sq.assigned_to IS NULL";
            } else {
                $where[] = "sq.assigned_to = :assigned_to";
                $bindings[':assigned_to'] = (int)$assignedTo;
            }
        }

        // Date Range
        if (!empty($params['start_date'])) {
            $where[] = "sq.created_at >= :start_date";
            $bindings[':start_date'] = $params['start_date'] . ' 00:00:00';
        }
        if (!empty($params['end_date'])) {
            $where[] = "sq.created_at <= :end_date";
            $bindings[':end_date'] = $params['end_date'] . ' 23:59:59';
        }

        // Global Search
        $searchVal = trim((string)($params['search']['value'] ?? $params['search_query'] ?? ''));
        if ($searchVal !== '') {
            $where[] = "(
                sq.ticket_id LIKE :s1 OR
                sq.name LIKE :s2 OR
                sq.email LIKE :s3 OR
                sq.phone LIKE :s4 OR
                sq.subject LIKE :s5 OR
                sq.order_id LIKE :s6 OR
                sq.existing_ticket_id LIKE :s7 OR
                sq.description LIKE :s8 OR
                u.name LIKE :s9
            )";
            $term = '%' . $searchVal . '%';
            for ($i = 1; $i <= 9; $i++) {
                $bindings[":s{$i}"] = $term;
            }
        }

        $whereSql = implode(' AND ', $where);

        $totalRecords = (int)(self::fetch("SELECT COUNT(*) AS cnt FROM support_queries WHERE source_type = :src", [':src' => $sourceType])['cnt'] ?? 0);
        $filteredRecords = (int)(self::fetch("
            SELECT COUNT(*) AS cnt 
            FROM support_queries sq
            LEFT JOIN users u ON sq.assigned_to = u.id
            WHERE {$whereSql}
        ", $bindings)['cnt'] ?? 0);

        // Sorting
        $columns = [
            0 => 'sq.id',
            1 => 'sq.ticket_id',
            2 => 'sq.name',
            3 => 'sq.email',
            4 => 'sq.phone',
            5 => 'sq.category',
            6 => 'sq.subject',
            7 => 'sq.order_id',
            8 => 'sq.existing_ticket_id',
            9 => 'u.name',
            10 => 'sq.status',
            11 => 'sq.created_at',
        ];

        $orderColIdx = (int) ($params['order'][0]['column'] ?? 11);
        $orderCol    = $columns[$orderColIdx] ?? 'sq.created_at';
        $orderDir    = strtoupper($params['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $rows = self::fetchAll("
            SELECT sq.*, 
                   u.name AS assigned_user_name,
                   u.email AS assigned_user_email
            FROM support_queries sq
            LEFT JOIN users u ON sq.assigned_to = u.id
            WHERE {$whereSql}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$length} OFFSET {$start}
        ", $bindings);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $rows,
        ];
    }

    /**
     * Get summary KPI metrics
     */
    public static function getMetrics(string $sourceType): array
    {
        self::ensureSchema();
        $sourceType = strtoupper(trim($sourceType)) === 'SELLER' ? 'SELLER' : 'BUYER';

        $row = self::fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'NEW' THEN 1 ELSE 0 END) AS status_new,
                SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS status_in_progress,
                SUM(CASE WHEN status = 'AWAITING_CUSTOMER_RESPONSE' THEN 1 ELSE 0 END) AS status_awaiting,
                SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) AS status_resolved,
                SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) AS status_closed,
                SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) AS unassigned
            FROM support_queries
            WHERE source_type = :src
        ", [':src' => $sourceType]);

        return [
            'total'          => (int)($row['total'] ?? 0),
            'new'            => (int)($row['status_new'] ?? 0),
            'in_progress'    => (int)($row['status_in_progress'] ?? 0),
            'awaiting'       => (int)($row['status_awaiting'] ?? 0),
            'resolved'       => (int)($row['status_resolved'] ?? 0),
            'closed'         => (int)($row['status_closed'] ?? 0),
            'unassigned'     => (int)($row['unassigned'] ?? 0),
        ];
    }

    /**
     * Get available support staff members (Super Admins)
     */
    public static function getSupportStaff(): array
    {
        self::ensureSchema();
        return self::fetchAll("
            SELECT id, name, email, role 
            FROM users 
            WHERE role = 'SUPER_ADMIN' 
            ORDER BY name ASC
        ");
    }

    /**
     * Export queries for CSV / Excel
     */
    public static function getExportData(array $params, string $sourceType): array
    {
        $params['start'] = 0;
        $params['length'] = 10000;
        $res = self::getAdminDataTable($params, $sourceType);
        return $res['data'] ?? [];
    }
}
