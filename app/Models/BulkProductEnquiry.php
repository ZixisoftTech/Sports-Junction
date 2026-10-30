<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;
use PDO;
use Throwable;

class BulkProductEnquiry extends Model
{
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_REVIEWED = 'REVIEWED';
    public const STATUS_PROCESSED = 'PROCESSED';
    public const STATUS_ARCHIVED = 'ARCHIVED';
    public const STATUS_CLOSED = 'CLOSED';

    public const STATUSES = [
        self::STATUS_SUBMITTED => 'Submitted / New',
        self::STATUS_REVIEWED  => 'Reviewed',
        self::STATUS_PROCESSED => 'Processed',
        self::STATUS_ARCHIVED  => 'Archived',
        self::STATUS_CLOSED    => 'Closed'
    ];

    /**
     * Self-healing: Ensure schema exists
     */
    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS bulk_product_enquiries (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    reference_no VARCHAR(50) NOT NULL UNIQUE,
                    buyer_user_id INT UNSIGNED NULL,
                    buyer_name VARCHAR(150) NOT NULL,
                    buyer_email VARCHAR(150) NOT NULL,
                    buyer_phone VARCHAR(50) NULL,
                    company_name VARCHAR(150) NULL,
                    country_id INT UNSIGNED NULL,
                    country_name VARCHAR(100) NULL,
                    delivery_location VARCHAR(255) NULL,
                    subject VARCHAR(255) NULL,
                    overall_message TEXT NULL,
                    preferred_destination VARCHAR(255) NULL,
                    expected_timeline VARCHAR(100) NULL,
                    additional_requirements TEXT NULL,
                    attachment_path VARCHAR(255) NULL,
                    attachment_name VARCHAR(255) NULL,
                    attachment_size INT UNSIGNED NULL,
                    attachment_mime VARCHAR(100) NULL,
                    product_count INT UNSIGNED NOT NULL DEFAULT 1,
                    status VARCHAR(30) NOT NULL DEFAULT 'SUBMITTED',
                    admin_notes TEXT NULL,
                    ip_address VARCHAR(45) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_bpe_user (buyer_user_id),
                    INDEX idx_bpe_status (status),
                    INDEX idx_bpe_created (created_at),
                    INDEX idx_bpe_ref (reference_no)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            self::execute("
                CREATE TABLE IF NOT EXISTS bulk_product_enquiry_items (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    bulk_enquiry_id INT UNSIGNED NOT NULL,
                    product_id INT UNSIGNED NOT NULL,
                    supplier_id INT UNSIGNED NOT NULL,
                    product_name VARCHAR(255) NOT NULL,
                    supplier_name VARCHAR(255) NULL,
                    target_quantity INT NOT NULL DEFAULT 1,
                    moq_quantity INT NOT NULL DEFAULT 1,
                    moq_unit_name VARCHAR(50) NULL,
                    target_price DECIMAL(12,2) NULL,
                    currency VARCHAR(10) DEFAULT 'USD',
                    specifications TEXT NOT NULL,
                    attachment_path VARCHAR(255) NULL,
                    attachment_name VARCHAR(255) NULL,
                    attachment_size INT UNSIGNED NULL,
                    attachment_mime VARCHAR(100) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_bpei_enquiry (bulk_enquiry_id),
                    INDEX idx_bpei_product (product_id),
                    INDEX idx_bpei_supplier (supplier_id),
                    CONSTRAINT fk_bpei_bulk_enquiry FOREIGN KEY (bulk_enquiry_id) 
                        REFERENCES bulk_product_enquiries(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Throwable $e) {
            error_log("BulkProductEnquiry::ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Generate unique reference number: BENQ-YYYYMMDD-XXXXX
     */
    public static function generateReferenceNo(): string
    {
        self::ensureSchema();
        $datePrefix = 'BENQ-' . date('Ymd') . '-';
        for ($i = 0; $i < 10; $i++) {
            $randomCode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $ref = $datePrefix . $randomCode;
            $exists = self::fetchColumn("SELECT COUNT(*) FROM bulk_product_enquiries WHERE reference_no = :ref", [':ref' => $ref]);
            if ((int)$exists === 0) {
                return $ref;
            }
        }
        return $datePrefix . time();
    }

    /**
     * Create one bulk enquiry with linked product items in an atomic transaction
     */
    public static function createBulkEnquiry(array $buyerData, array $itemsData, ?array $sharedAttachment = null): array
    {
        self::ensureSchema();
        $pdo = self::db();
        $pdo->beginTransaction();

        try {
            $refNo = self::generateReferenceNo();
            $buyerUserId = !empty($buyerData['buyer_user_id']) ? (int)$buyerData['buyer_user_id'] : null;
            $buyerName = trim((string)($buyerData['buyer_name'] ?? ''));
            $buyerEmail = trim((string)($buyerData['buyer_email'] ?? ''));
            $buyerPhone = !empty($buyerData['buyer_phone']) ? trim((string)$buyerData['buyer_phone']) : null;
            $companyName = !empty($buyerData['company_name']) ? trim((string)$buyerData['company_name']) : null;
            $countryId = !empty($buyerData['country_id']) ? (int)$buyerData['country_id'] : null;
            $countryName = !empty($buyerData['country_name']) ? trim((string)$buyerData['country_name']) : null;
            $deliveryLocation = !empty($buyerData['delivery_location']) ? trim((string)$buyerData['delivery_location']) : null;
            $subject = !empty($buyerData['subject']) ? trim((string)$buyerData['subject']) : "Bulk Sourcing Enquiry ({$refNo})";
            $overallMessage = !empty($buyerData['overall_message']) ? trim((string)$buyerData['overall_message']) : null;
            $preferredDestination = !empty($buyerData['preferred_destination']) ? trim((string)$buyerData['preferred_destination']) : $deliveryLocation;
            $expectedTimeline = !empty($buyerData['expected_timeline']) ? trim((string)$buyerData['expected_timeline']) : null;
            $additionalReqs = !empty($buyerData['additional_requirements']) ? trim((string)$buyerData['additional_requirements']) : null;
            $ipAddress = !empty($buyerData['ip_address']) ? (string)$buyerData['ip_address'] : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

            $attachmentPath = $sharedAttachment['path'] ?? null;
            $attachmentName = $sharedAttachment['name'] ?? null;
            $attachmentSize = isset($sharedAttachment['size']) ? (int)$sharedAttachment['size'] : null;
            $attachmentMime = $sharedAttachment['mime'] ?? null;

            $productCount = count($itemsData);

            // Insert parent record
            $stmt = $pdo->prepare("
                INSERT INTO bulk_product_enquiries (
                    reference_no, buyer_user_id, buyer_name, buyer_email, buyer_phone,
                    company_name, country_id, country_name, delivery_location, subject,
                    overall_message, preferred_destination, expected_timeline, additional_requirements,
                    attachment_path, attachment_name, attachment_size, attachment_mime,
                    product_count, status, ip_address, created_at
                ) VALUES (
                    :ref, :buyer_uid, :name, :email, :phone,
                    :company, :country_id, :country_name, :location, :subject,
                    :message, :destination, :timeline, :add_req,
                    :att_path, :att_name, :att_size, :att_mime,
                    :prod_count, 'SUBMITTED', :ip, NOW()
                )
            ");

            $stmt->execute([
                ':ref'          => $refNo,
                ':buyer_uid'    => $buyerUserId,
                ':name'         => $buyerName,
                ':email'        => $buyerEmail,
                ':phone'        => $buyerPhone,
                ':company'      => $companyName,
                ':country_id'   => $countryId,
                ':country_name' => $countryName,
                ':location'     => $deliveryLocation,
                ':subject'      => $subject,
                ':message'      => $overallMessage,
                ':destination'  => $preferredDestination,
                ':timeline'     => $expectedTimeline,
                ':add_req'      => $additionalReqs,
                ':att_path'     => $attachmentPath,
                ':att_name'     => $attachmentName,
                ':att_size'     => $attachmentSize,
                ':att_mime'     => $attachmentMime,
                ':prod_count'   => $productCount,
                ':ip'           => $ipAddress
            ]);

            $bulkEnquiryId = (int) $pdo->lastInsertId();

            // Insert each product item
            $itemStmt = $pdo->prepare("
                INSERT INTO bulk_product_enquiry_items (
                    bulk_enquiry_id, product_id, supplier_id, product_name, supplier_name,
                    target_quantity, moq_quantity, moq_unit_name, target_price, currency,
                    specifications, attachment_path, attachment_name, attachment_size, attachment_mime, created_at
                ) VALUES (
                    :bulk_id, :product_id, :supplier_id, :prod_name, :supp_name,
                    :qty, :moq_qty, :moq_unit, :target_price, :currency,
                    :specs, :att_path, :att_name, :att_size, :att_mime, NOW()
                )
            ");

            foreach ($itemsData as $item) {
                $itemStmt->execute([
                    ':bulk_id'      => $bulkEnquiryId,
                    ':product_id'   => (int)$item['product_id'],
                    ':supplier_id'  => (int)$item['supplier_id'],
                    ':prod_name'    => (string)($item['product_name'] ?? $item['title'] ?? 'Sports Product'),
                    ':supp_name'    => (string)($item['supplier_name'] ?? 'Verified Manufacturer'),
                    ':qty'          => max(1, (int)($item['target_quantity'] ?? $item['quantity'] ?? 1)),
                    ':moq_qty'      => max(1, (int)($item['moq_quantity'] ?? 1)),
                    ':moq_unit'     => (string)($item['moq_unit_name'] ?? 'units'),
                    ':target_price' => !empty($item['target_price']) ? (float)$item['target_price'] : null,
                    ':currency'     => (string)($item['currency'] ?? 'USD'),
                    ':specs'        => (string)($item['specifications'] ?? ''),
                    ':att_path'     => $item['attachment_path'] ?? null,
                    ':att_name'     => $item['attachment_name'] ?? null,
                    ':att_size'     => isset($item['attachment_size']) ? (int)$item['attachment_size'] : null,
                    ':att_mime'     => $item['attachment_mime'] ?? null,
                ]);
            }

            $pdo->commit();

            // Audit log
            AuditLogger::log(
                'CREATE', 
                'BulkProductEnquiry', 
                $bulkEnquiryId, 
                $refNo, 
                null, 
                "Buyer '{$buyerName}' submitted bulk enquiry '{$refNo}' for {$productCount} products"
            );

            // Notify Super Admin (Note: NO buyer/supplier messaging or chat created)
            try {
                Notification::notifySuperAdmins(
                    'BULK_ENQUIRY_RECEIVED',
                    "New Bulk Enquiry: {$refNo} ({$productCount} items)",
                    "Submitted by {$buyerName}" . ($companyName ? " ({$companyName})" : "") . " covering {$productCount} products.",
                    '/admin/bulk-enquiries',
                    'BULK_ENQUIRY',
                    $bulkEnquiryId
                );
            } catch (Throwable $e) {
                error_log("Failed to notify super admin for bulk enquiry: " . $e->getMessage());
            }

            return [
                'success'         => true,
                'id'              => $bulkEnquiryId,
                'bulk_enquiry_id' => $bulkEnquiryId,
                'reference_no'    => $refNo,
                'item_count'      => $productCount
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log("BulkProductEnquiry::createBulkEnquiry error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get parent enquiry by ID with items (ActiveRecord standard alias)
     */
    public static function findById(int $id): ?array
    {
        return self::getDetailForAdmin($id);
    }

    /**
     * Get parent enquiry by ID
     */
    public static function getById(int $id): ?array
    {
        self::ensureSchema();
        $row = self::fetch("
            SELECT bpe.*, c.name AS resolved_country_name
            FROM bulk_product_enquiries bpe
            LEFT JOIN countries c ON bpe.country_id = c.id
            WHERE bpe.id = :id
            LIMIT 1
        ", [':id' => $id]);

        return $row ?: null;
    }

    /**
     * Get items for a bulk enquiry
     */
    public static function getItems(int $bulkEnquiryId): array
    {
        self::ensureSchema();
        return self::fetchAll("
            SELECT i.*, 
                   p.title AS latest_product_title, p.slug AS product_slug, p.primary_image, p.sku,
                   p.min_price, p.max_price,
                   sp.company_name AS latest_supplier_name, sp.status AS supplier_status
            FROM bulk_product_enquiry_items i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN suppliers sp ON i.supplier_id = sp.id
            WHERE i.bulk_enquiry_id = :bid
            ORDER BY i.id ASC
        ", [':bid' => $bulkEnquiryId]);
    }

    /**
     * Get full details for Super Admin review
     */
    public static function getDetailForAdmin(int $id): ?array
    {
        $enquiry = self::getById($id);
        if (!$enquiry) {
            return null;
        }
        $enquiry['items'] = self::getItems($id);
        return $enquiry;
    }

    /**
     * Get full details for Buyer view (enforcing buyer ownership)
     */
    public static function getDetailForBuyer(int $id, int $buyerUserId): ?array
    {
        self::ensureSchema();
        $enquiry = self::fetch("
            SELECT bpe.*, c.name AS resolved_country_name
            FROM bulk_product_enquiries bpe
            LEFT JOIN countries c ON bpe.country_id = c.id
            WHERE bpe.id = :id AND bpe.buyer_user_id = :buid
            LIMIT 1
        ", [':id' => $id, ':buid' => $buyerUserId]);

        if (!$enquiry) {
            return null;
        }

        $enquiry['items'] = self::getItems($id);
        return $enquiry;
    }

    /**
     * Get buyer's submitted bulk enquiries with pagination
     */
    public static function getByBuyer(int $buyerUserId, int $limit = 20, int $offset = 0): array
    {
        self::ensureSchema();
        $rows = self::fetchAll("
            SELECT bpe.*,
                   (
                       SELECT GROUP_CONCAT(DISTINCT i.product_name SEPARATOR ' • ')
                       FROM bulk_product_enquiry_items i
                       WHERE i.bulk_enquiry_id = bpe.id
                   ) AS products_summary,
                   (
                       SELECT GROUP_CONCAT(DISTINCT i.supplier_name SEPARATOR ', ')
                       FROM bulk_product_enquiry_items i
                       WHERE i.bulk_enquiry_id = bpe.id
                   ) AS suppliers_summary
            FROM bulk_product_enquiries bpe
            WHERE bpe.buyer_user_id = :buid
            ORDER BY bpe.created_at DESC
            LIMIT :limit OFFSET :offset
        ", [':buid' => $buyerUserId, ':limit' => $limit, ':offset' => $offset]);

        $total = (int) self::fetchColumn("
            SELECT COUNT(*) FROM bulk_product_enquiries WHERE buyer_user_id = :buid
        ", [':buid' => $buyerUserId]);

        return [
            'records' => $rows,
            'total'   => $total
        ];
    }

    /**
     * Paginated list for Super Admin with search & date/status filters
     */
    public static function getAllPaginated(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        self::ensureSchema();
        $where = [];
        $params = [];

        // Search
        if (!empty($filters['search'])) {
            $q = trim((string)$filters['search']);
            $where[] = "(bpe.reference_no LIKE :q1 OR bpe.buyer_name LIKE :q2 OR bpe.buyer_email LIKE :q3 OR bpe.company_name LIKE :q4 OR bpe.delivery_location LIKE :q5)";
            $params[':q1'] = "%{$q}%";
            $params[':q2'] = "%{$q}%";
            $params[':q3'] = "%{$q}%";
            $params[':q4'] = "%{$q}%";
            $params[':q5'] = "%{$q}%";
        }

        // Status
        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $where[] = "bpe.status = :status";
            $params[':status'] = trim((string)$filters['status']);
        }

        // Date range
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(bpe.created_at) >= :date_from";
            $params[':date_from'] = trim((string)$filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(bpe.created_at) <= :date_to";
            $params[':date_to'] = trim((string)$filters['date_to']);
        }

        $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "
            SELECT bpe.*,
                   c.name AS country_title,
                   (
                       SELECT GROUP_CONCAT(DISTINCT i.product_name SEPARATOR ' • ')
                       FROM bulk_product_enquiry_items i
                       WHERE i.bulk_enquiry_id = bpe.id
                   ) AS products_summary,
                   (
                       SELECT GROUP_CONCAT(DISTINCT i.supplier_name SEPARATOR ', ')
                       FROM bulk_product_enquiry_items i
                       WHERE i.bulk_enquiry_id = bpe.id
                   ) AS suppliers_summary
            FROM bulk_product_enquiries bpe
            LEFT JOIN countries c ON bpe.country_id = c.id
            {$whereClause}
            ORDER BY bpe.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $countSql = "SELECT COUNT(*) FROM bulk_product_enquiries bpe {$whereClause}";

        $total = (int) self::fetchColumn($countSql, $params);

        // Bind limit & offset as integers
        $db = self::db();
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'records'     => $records,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => (int) ceil($total / max(1, $perPage))
        ];
    }

    /**
     * Update administrative status
     */
    public static function updateStatus(int $id, string $status, ?string $adminNotes = null): bool
    {
        self::ensureSchema();
        $status = strtoupper(trim($status));
        if (!array_key_exists($status, self::STATUSES)) {
            return false;
        }

        $params = [
            ':id'     => $id,
            ':status' => $status
        ];

        $sql = "UPDATE bulk_product_enquiries SET status = :status";
        if ($adminNotes !== null) {
            $sql .= ", admin_notes = :notes";
            $params[':notes'] = trim($adminNotes);
        }
        $sql .= " WHERE id = :id";

        self::execute($sql, $params);

        AuditLogger::log('UPDATE', 'BulkProductEnquiry', $id, $status, null, "Updated bulk enquiry #{$id} status to '{$status}'");
        return true;
    }
}
