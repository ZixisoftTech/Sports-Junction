<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class SiteFeedback extends Model
{
    public const VALID_TYPES = [
        'General Feedback',
        'Website Experience',
        'Product & Catalogue Suggestions',
        'Buyer Experience',
        'Supplier/Manufacturer Experience',
        'Technical Issue / Bug Report',
        'Feature Request',
        'Complaint',
        'Other',
    ];

    public const VALID_STATUSES = [
        'NEW',
        'UNDER_REVIEW',
        'REVIEWED',
        'CLOSED',
    ];

    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `site_feedbacks` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `reference_id` VARCHAR(50) NULL UNIQUE,
                    `name` VARCHAR(150) NOT NULL,
                    `company_name` VARCHAR(150) NULL,
                    `email` VARCHAR(191) NOT NULL,
                    `phone` VARCHAR(50) NULL,
                    `country_id` INT NULL,
                    `country_name` VARCHAR(100) NULL,
                    `feedback_type` VARCHAR(100) NOT NULL DEFAULT 'General Feedback',
                    `user_role` VARCHAR(50) NOT NULL DEFAULT 'BUYER',
                    `category` VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
                    `rating` TINYINT NOT NULL DEFAULT 5,
                    `subject` VARCHAR(255) NOT NULL,
                    `message` TEXT NOT NULL,
                    `attachment_path` VARCHAR(255) NULL,
                    `attachment_name` VARCHAR(255) NULL,
                    `attachment_size` INT NULL,
                    `attachment_mime` VARCHAR(100) NULL,
                    `consent_contact` TINYINT(1) NOT NULL DEFAULT 1,
                    `ip_address` VARCHAR(45) NULL,
                    `status` ENUM('NEW', 'UNDER_REVIEW', 'REVIEWED', 'CLOSED') NOT NULL DEFAULT 'NEW',
                    `admin_notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_fb_status (`status`),
                    INDEX idx_fb_type (`feedback_type`),
                    INDEX idx_fb_created (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Check existing columns and alter table if needed
            $cols = self::fetchAll("DESCRIBE `site_feedbacks`");
            $existing = array_column($cols, 'Field');

            if (!in_array('reference_id', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `reference_id` VARCHAR(50) NULL UNIQUE AFTER `id`");
            }
            if (!in_array('company_name', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `company_name` VARCHAR(150) NULL AFTER `name`");
            }
            if (!in_array('phone', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `phone` VARCHAR(50) NULL AFTER `email`");
            }
            if (!in_array('country_id', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `country_id` INT NULL AFTER `phone`");
            }
            if (!in_array('country_name', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `country_name` VARCHAR(100) NULL AFTER `country_id`");
            }
            if (!in_array('feedback_type', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `feedback_type` VARCHAR(100) NOT NULL DEFAULT 'General Feedback' AFTER `country_name`");
            }
            if (!in_array('attachment_path', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `attachment_path` VARCHAR(255) NULL AFTER `message`");
            }
            if (!in_array('attachment_name', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `attachment_name` VARCHAR(255) NULL AFTER `attachment_path`");
            }
            if (!in_array('attachment_size', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `attachment_size` INT NULL AFTER `attachment_name`");
            }
            if (!in_array('attachment_mime', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `attachment_mime` VARCHAR(100) NULL AFTER `attachment_size`");
            }
            if (!in_array('consent_contact', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `consent_contact` TINYINT(1) NOT NULL DEFAULT 1 AFTER `attachment_mime`");
            }
            if (!in_array('admin_notes', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `admin_notes` TEXT NULL AFTER `status`");
            }
            if (!in_array('updated_at', $existing, true)) {
                self::execute("ALTER TABLE `site_feedbacks` ADD COLUMN `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
            }

            // Adjust status enum to include UNDER_REVIEW and CLOSED
            self::execute("ALTER TABLE `site_feedbacks` MODIFY COLUMN `status` ENUM('NEW', 'UNDER_REVIEW', 'REVIEWED', 'CLOSED') NOT NULL DEFAULT 'NEW'");

            // Populate reference_id for any existing records missing one
            $unref = self::fetchAll("SELECT id FROM `site_feedbacks` WHERE `reference_id` IS NULL OR `reference_id` = ''");
            foreach ($unref as $u) {
                $ref = 'SFB-' . date('Ym') . '-' . str_pad((string)$u['id'], 5, '0', STR_PAD_LEFT);
                self::execute("UPDATE `site_feedbacks` SET `reference_id` = :ref WHERE `id` = :id", [
                    ':ref' => $ref,
                    ':id'  => $u['id']
                ]);
            }
        } catch (Throwable $e) {
            @error_log("SiteFeedback::ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Create new user feedback submission
     */
    public static function create(array $data): int
    {
        self::ensureSchema();
        try {
            $name         = trim((string)($data['name'] ?? ''));
            $companyName  = !empty($data['company_name']) ? trim((string)$data['company_name']) : null;
            $email        = trim((string)($data['email'] ?? ''));
            $phone        = !empty($data['phone']) ? trim((string)$data['phone']) : null;
            $countryId    = !empty($data['country_id']) ? (int)$data['country_id'] : null;
            $countryName  = !empty($data['country_name']) ? trim((string)$data['country_name']) : null;
            $feedbackType = trim((string)($data['feedback_type'] ?? 'General Feedback'));
            if (!in_array($feedbackType, self::VALID_TYPES, true)) {
                $feedbackType = 'General Feedback';
            }

            $role         = strtoupper(trim((string)($data['user_role'] ?? 'BUYER')));
            $category     = strtoupper(trim((string)($data['category'] ?? 'GENERAL')));
            $rating       = max(1, min(5, (int)($data['rating'] ?? 5)));
            $subject      = trim((string)($data['subject'] ?? 'Website Suggestion & Feedback'));
            $message      = trim((string)($data['message'] ?? ''));
            $attachmentPath = !empty($data['attachment_path']) ? trim((string)$data['attachment_path']) : null;
            $attachmentName = !empty($data['attachment_name']) ? trim((string)$data['attachment_name']) : null;
            $attachmentSize = !empty($data['attachment_size']) ? (int)$data['attachment_size'] : null;
            $attachmentMime = !empty($data['attachment_mime']) ? trim((string)$data['attachment_mime']) : null;
            $consentContact = !empty($data['consent_contact']) ? 1 : 0;
            $ip           = (string)($data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

            self::execute("
                INSERT INTO `site_feedbacks` (
                    `name`, `company_name`, `email`, `phone`, `country_id`, `country_name`,
                    `feedback_type`, `user_role`, `category`, `rating`, `subject`, `message`,
                    `attachment_path`, `attachment_name`, `attachment_size`, `attachment_mime`,
                    `consent_contact`, `ip_address`, `status`, `created_at`
                ) VALUES (
                    :name, :company, :email, :phone, :country_id, :country_name,
                    :feedback_type, :role, :category, :rating, :subject, :message,
                    :attachment_path, :attachment_name, :attachment_size, :attachment_mime,
                    :consent, :ip, 'NEW', NOW()
                )
            ", [
                ':name'            => $name,
                ':company'         => $companyName,
                ':email'           => $email,
                ':phone'           => $phone,
                ':country_id'      => $countryId,
                ':country_name'    => $countryName,
                ':feedback_type'   => $feedbackType,
                ':role'            => $role,
                ':category'        => $category,
                ':rating'          => $rating,
                ':subject'         => $subject,
                ':message'         => $message,
                ':attachment_path' => $attachmentPath,
                ':attachment_name' => $attachmentName,
                ':attachment_size' => $attachmentSize,
                ':attachment_mime' => $attachmentMime,
                ':consent'         => $consentContact,
                ':ip'              => $ip
            ]);

            $feedbackId = (int) self::lastInsertId();

            // Set formatted reference_id
            $refId = 'SFB-' . date('Ym') . '-' . str_pad((string)$feedbackId, 5, '0', STR_PAD_LEFT);
            self::execute("UPDATE `site_feedbacks` SET `reference_id` = :ref WHERE `id` = :id", [
                ':ref' => $refId,
                ':id'  => $feedbackId
            ]);

            // Notify Super Admins
            try {
                Notification::notifySuperAdmins(
                    'FEEDBACK_RECEIVED',
                    "New Feedback {$refId} from {$name} ({$feedbackType})",
                    mb_substr($message, 0, 120),
                    '/admin/feedbacks',
                    'FEEDBACK',
                    $feedbackId
                );
            } catch (Throwable $notifErr) {
                @error_log("SiteFeedback notification error: " . $notifErr->getMessage());
            }

            return $feedbackId;
        } catch (Throwable $e) {
            error_log("SiteFeedback::create error: " . $e->getMessage());
            return 0;
        }
    }

    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        $row = self::fetch("SELECT * FROM `site_feedbacks` WHERE `id` = :id LIMIT 1", [':id' => $id]);
        return $row ?: null;
    }

    public static function findByReferenceId(string $refId): ?array
    {
        self::ensureSchema();
        $row = self::fetch("SELECT * FROM `site_feedbacks` WHERE `reference_id` = :ref LIMIT 1", [':ref' => trim($refId)]);
        return $row ?: null;
    }

    /**
     * Get Server-Side DataTables list for Super Admin
     */
    public static function getAdminDataTable(array $params): array
    {
        self::ensureSchema();

        $draw   = (int) ($params['draw'] ?? 1);
        $start  = max(0, (int) ($params['start'] ?? 0));
        $length = max(1, min(100, (int) ($params['length'] ?? 15)));

        $where = ['1=1'];
        $bindings = [];

        // Feedback Type filter
        if (!empty($params['feedback_type']) && $params['feedback_type'] !== 'ALL') {
            $where[] = "feedback_type = :f_type";
            $bindings[':f_type'] = $params['feedback_type'];
        }

        // Status filter
        if (!empty($params['status']) && $params['status'] !== 'ALL') {
            $where[] = "status = :f_status";
            $bindings[':f_status'] = strtoupper(trim($params['status']));
        }

        // Date Range
        if (!empty($params['start_date'])) {
            $where[] = "created_at >= :start_date";
            $bindings[':start_date'] = $params['start_date'] . ' 00:00:00';
        }
        if (!empty($params['end_date'])) {
            $where[] = "created_at <= :end_date";
            $bindings[':end_date'] = $params['end_date'] . ' 23:59:59';
        }

        // Global Search
        $searchVal = trim((string)($params['search']['value'] ?? $params['search_query'] ?? ''));
        if ($searchVal !== '') {
            $where[] = "(
                reference_id LIKE :fb1 OR
                name LIKE :fb2 OR
                company_name LIKE :fb3 OR
                email LIKE :fb4 OR
                phone LIKE :fb5 OR
                country_name LIKE :fb6 OR
                feedback_type LIKE :fb7 OR
                subject LIKE :fb8 OR
                message LIKE :fb9
            )";
            $term = '%' . $searchVal . '%';
            for ($i = 1; $i <= 9; $i++) {
                $bindings[":fb{$i}"] = $term;
            }
        }

        $whereSql = implode(' AND ', $where);

        $totalRecords = (int)(self::fetch("SELECT COUNT(*) AS cnt FROM `site_feedbacks`")['cnt'] ?? 0);
        $filteredRecords = (int)(self::fetch("SELECT COUNT(*) AS cnt FROM `site_feedbacks` WHERE {$whereSql}", $bindings)['cnt'] ?? 0);

        $columns = [
            0 => 'id',
            1 => 'created_at',
            2 => 'name',
            3 => 'company_name',
            4 => 'feedback_type',
            5 => 'subject',
            6 => 'status',
        ];

        $orderColIdx = (int) ($params['order'][0]['column'] ?? 1);
        $orderCol    = $columns[$orderColIdx] ?? 'created_at';
        $orderDir    = strtoupper($params['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $rows = self::fetchAll("
            SELECT * FROM `site_feedbacks`
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
    public static function getAdminMetrics(): array
    {
        self::ensureSchema();
        $row = self::fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'NEW' THEN 1 ELSE 0 END) AS status_new,
                SUM(CASE WHEN status = 'UNDER_REVIEW' THEN 1 ELSE 0 END) AS status_under_review,
                SUM(CASE WHEN status = 'REVIEWED' THEN 1 ELSE 0 END) AS status_reviewed,
                SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) AS status_closed
            FROM `site_feedbacks`
        ");

        return [
            'total'        => (int) ($row['total'] ?? 0),
            'new'          => (int) ($row['status_new'] ?? 0),
            'under_review' => (int) ($row['status_under_review'] ?? 0),
            'reviewed'     => (int) ($row['status_reviewed'] ?? 0),
            'closed'       => (int) ($row['status_closed'] ?? 0),
        ];
    }

    /**
     * Export feedback rows
     */
    public static function getAdminExportData(array $params): array
    {
        self::ensureSchema();

        $where = ['1=1'];
        $bindings = [];

        if (!empty($params['feedback_type']) && $params['feedback_type'] !== 'ALL') {
            $where[] = "feedback_type = :f_type";
            $bindings[':f_type'] = $params['feedback_type'];
        }
        if (!empty($params['status']) && $params['status'] !== 'ALL') {
            $where[] = "status = :f_status";
            $bindings[':f_status'] = strtoupper(trim($params['status']));
        }
        if (!empty($params['start_date'])) {
            $where[] = "created_at >= :start_date";
            $bindings[':start_date'] = $params['start_date'] . ' 00:00:00';
        }
        if (!empty($params['end_date'])) {
            $where[] = "created_at <= :end_date";
            $bindings[':end_date'] = $params['end_date'] . ' 23:59:59';
        }
        $searchVal = trim((string)($params['search_query'] ?? ''));
        if ($searchVal !== '') {
            $where[] = "(
                reference_id LIKE :efb1 OR
                name LIKE :efb2 OR
                company_name LIKE :efb3 OR
                email LIKE :efb4 OR
                phone LIKE :efb5 OR
                country_name LIKE :efb6 OR
                feedback_type LIKE :efb7 OR
                subject LIKE :efb8 OR
                message LIKE :efb9
            )";
            $term = '%' . $searchVal . '%';
            for ($i = 1; $i <= 9; $i++) {
                $bindings[":efb{$i}"] = $term;
            }
        }

        $whereSql = implode(' AND ', $where);

        return self::fetchAll("
            SELECT 
                COALESCE(reference_id, CONCAT('SFB-', LPAD(id, 5, '0'))) AS 'Reference ID',
                name AS 'Full Name',
                COALESCE(company_name, 'N/A') AS 'Company / Organization',
                email AS 'Email Address',
                COALESCE(phone, 'N/A') AS 'Phone / WhatsApp',
                COALESCE(country_name, 'N/A') AS 'Country',
                feedback_type AS 'Feedback Type',
                subject AS 'Subject',
                message AS 'Feedback / Suggestion',
                CASE WHEN attachment_path IS NOT NULL AND attachment_path != '' THEN 'Yes' ELSE 'No' END AS 'Has Attachment',
                status AS 'Status',
                created_at AS 'Submission Date'
            FROM `site_feedbacks`
            WHERE {$whereSql}
            ORDER BY created_at DESC
        ", $bindings);
    }

    public static function updateStatus(int $id, string $status): bool
    {
        self::ensureSchema();
        $status = strtoupper(trim($status));
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }
        self::execute("UPDATE `site_feedbacks` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id", [
            ':status' => $status,
            ':id'     => $id
        ]);
        return true;
    }

    public static function updateAdminNotes(int $id, string $notes): bool
    {
        self::ensureSchema();
        self::execute("UPDATE `site_feedbacks` SET `admin_notes` = :notes, `updated_at` = NOW() WHERE `id` = :id", [
            ':notes' => trim($notes),
            ':id'    => $id
        ]);
        return true;
    }
}
