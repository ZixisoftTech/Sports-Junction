<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class ProductInquiry extends Model
{
    public const VALID_STATUSES = [
        'PENDING',
        'SUBMITTED',
        'VIEWED',
        'RESPONDED',
        'IN_DISCUSSION',
        'IN_PRODUCTION',
        'SAMPLE_SHIPPED',
        'DELIVERED',
        'CLOSED',
        'RESOLVED',
        'REJECTED',
    ];

    public const VALID_SOURCES = [
        'PRODUCT_PAGE',
        'GENERAL',
        'CONTACT_US',
        'RFQ',
        'FAST_OEM',
        'SAMPLE',
    ];

    public static function create(array $data): int
    {
        $productId = !empty($data['product_id']) ? (int) $data['product_id'] : null;
        $supplierId = !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;

        // Automatically resolve supplier_id from product if not passed explicitly
        if ($productId && !$supplierId) {
            $prod = self::fetch("SELECT supplier_id FROM products WHERE id = :id LIMIT 1", [':id' => $productId]);
            if (!empty($prod['supplier_id'])) {
                $supplierId = (int) $prod['supplier_id'];
            }
        }

        $source = strtoupper(trim($data['enquiry_source'] ?? ($productId ? 'PRODUCT_PAGE' : 'GENERAL')));
        if (!in_array($source, self::VALID_SOURCES, true)) {
            $source = $productId ? 'PRODUCT_PAGE' : 'GENERAL';
        }

        $status = strtoupper(trim($data['status'] ?? 'SUBMITTED'));
        if (!in_array($status, self::VALID_STATUSES, true)) {
            $status = 'SUBMITTED';
        }

        $targetPrice = isset($data['target_price']) && $data['target_price'] !== '' ? (float) $data['target_price'] : null;
        $reqDate = !empty($data['required_delivery_date']) ? trim($data['required_delivery_date']) : null;
        if ($reqDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reqDate)) {
            $reqDate = null;
        }

        $countryId = !empty($data['country_id']) ? (int) $data['country_id'] : null;
        $countryName = !empty($data['country_name']) ? trim($data['country_name']) : null;
        if ($countryId && empty($countryName)) {
            $c = Country::findById($countryId);
            if ($c) {
                $countryName = $c['name'];
            }
        } elseif (!$countryId && !empty($countryName)) {
            $c = self::fetch("SELECT id, name FROM countries WHERE LOWER(name) = LOWER(:name) LIMIT 1", [':name' => $countryName]);
            if ($c) {
                $countryId = (int) $c['id'];
                $countryName = $c['name'];
            }
        }

        self::execute("
            INSERT INTO product_inquiries (
                enquiry_source, product_id, supplier_id, buyer_user_id, buyer_name, buyer_email, buyer_phone,
                country_id, country_name,
                company_name, subject, target_quantity, target_price, lead_time_req, delivery_location,
                required_delivery_date, specifications, attachment_path, attachment_name, attachment_size,
                attachment_mime, status, ip_address, created_at
            ) VALUES (
                :enquiry_source, :product_id, :supplier_id, :buyer_user_id, :buyer_name, :buyer_email, :buyer_phone,
                :country_id, :country_name,
                :company_name, :subject, :target_quantity, :target_price, :lead_time_req, :delivery_location,
                :required_delivery_date, :specifications, :attachment_path, :attachment_name, :attachment_size,
                :attachment_mime, :status, :ip_address, NOW()
            )
        ", [
            ':enquiry_source'         => $source,
            ':product_id'             => $productId,
            ':supplier_id'            => $supplierId,
            ':buyer_user_id'          => !empty($data['buyer_user_id']) ? (int) $data['buyer_user_id'] : null,
            ':buyer_name'             => trim($data['buyer_name'] ?? 'Guest Buyer'),
            ':buyer_email'            => trim($data['buyer_email'] ?? ''),
            ':buyer_phone'            => !empty($data['buyer_phone']) ? trim($data['buyer_phone']) : null,
            ':country_id'             => $countryId,
            ':country_name'           => $countryName,
            ':company_name'           => !empty($data['company_name']) ? trim($data['company_name']) : null,
            ':subject'                => !empty($data['subject']) ? trim($data['subject']) : null,
            ':target_quantity'        => max(1, (int) ($data['target_quantity'] ?? 1)),
            ':target_price'           => $targetPrice,
            ':lead_time_req'          => !empty($data['lead_time_req']) ? trim($data['lead_time_req']) : null,
            ':delivery_location'      => !empty($data['delivery_location']) ? trim($data['delivery_location']) : null,
            ':required_delivery_date' => $reqDate,
            ':specifications'         => trim($data['specifications'] ?? ''),
            ':attachment_path'        => !empty($data['attachment_path']) ? trim($data['attachment_path']) : null,
            ':attachment_name'        => !empty($data['attachment_name']) ? trim($data['attachment_name']) : null,
            ':attachment_size'        => !empty($data['attachment_size']) ? (int) $data['attachment_size'] : null,
            ':attachment_mime'        => !empty($data['attachment_mime']) ? trim($data['attachment_mime']) : null,
            ':status'                 => $status,
            ':ip_address'             => !empty($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : null,
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'ProductInquiry', $id, trim($data['buyer_email'] ?? ''), null, "Submitted {$source} inquiry");

        // Super Admin Notification Dispatch
        try {
            $notifType = match ($source) {
                'SAMPLE'     => 'ENQUIRY_SAMPLE',
                'RFQ'        => 'ENQUIRY_RFQ',
                'FAST_OEM'   => 'ENQUIRY_FAST_OEM',
                'CONTACT_US' => 'ENQUIRY_CONTACT',
                'GENERAL'    => 'ENQUIRY_GENERAL',
                default      => 'ENQUIRY_PRODUCT'
            };

            $notifTitlePrefix = match ($source) {
                'SAMPLE'     => 'Prototype Sample Request',
                'RFQ'        => 'Request for Quotation (RFQ)',
                'FAST_OEM'   => 'Fast OEM Customization Request',
                'CONTACT_US' => 'Contact Us Enquiry',
                'GENERAL'    => 'General Sourcing Requirement',
                default      => 'Product Page Enquiry'
            };

            $buyerDisplay = trim($data['buyer_name'] ?? 'Buyer');
            if (!empty($data['company_name'])) {
                $buyerDisplay .= ' (' . trim($data['company_name']) . ')';
            }

            $notifTitle = "{$notifTitlePrefix} from {$buyerDisplay}";
            $notifMsg = !empty($data['subject']) 
                ? trim($data['subject']) 
                : "New enquiry submission (#ENQ-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . ") — Status: {$status}";

            \App\Models\Notification::notifySuperAdmins(
                $notifType,
                $notifTitle,
                $notifMsg,
                "/admin/enquiries/{$id}",
                'ENQUIRY',
                $id
            );
        } catch (\Throwable $e) {
            error_log("Failed to notify super admins of new inquiry #{$id}: " . $e->getMessage());
        }

        return $id;
    }

    public static function findById(int $id, bool $includeAdminNotes = false): ?array
    {
        $row = self::fetch("
            SELECT i.*, 
                   p.title AS product_name, p.slug AS product_slug, p.primary_image,
                   p.min_price, p.max_price, p.category_id, p.subcategory_id, p.supplier_id AS product_owner_supplier_id,
                   c.name AS category_name, sc.name AS subcategory_name,
                   pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   COALESCE(s.company_name, ps.company_name) AS supplier_name,
                   COALESCE(s.email, ps.email) AS supplier_email,
                   COALESCE(s.phone, ps.phone) AS supplier_phone,
                   COALESCE(i.supplier_id, p.supplier_id) AS effective_supplier_id,
                   bu.name AS registered_buyer_name, bu.company_name AS registered_buyer_company, bu.status AS registered_buyer_status
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN suppliers ps ON p.supplier_id = ps.id
            LEFT JOIN users bu ON i.buyer_user_id = bu.id
            WHERE i.id = :id
            LIMIT 1
        ", [':id' => $id]);

        if ($row && !$includeAdminNotes) {
            unset($row['admin_notes']);
        }
        return $row;
    }

    public static function getBuyerEnquiries(int $buyerUserId, ?string $email = null): array
    {
        // Contact Us messages must only appear in Buyer My Enquiries if submitted while logged in (buyer_user_id = :uid)
        $where = [
            "(i.buyer_user_id = :uid" . (!empty($email) ? " OR (i.enquiry_source != 'CONTACT_US' AND LOWER(i.buyer_email) = :email)" : "") . ")"
        ];
        $bindings = [':uid' => $buyerUserId];
        if (!empty($email)) {
            $bindings[':email'] = strtolower(trim($email));
        }

        $whereClause = implode(' AND ', $where);

        $rows = self::fetchAll("
            SELECT i.id, i.enquiry_source, i.product_id, i.supplier_id, i.buyer_user_id,
                   i.buyer_name, i.buyer_email, i.buyer_phone, i.company_name, i.subject,
                   i.target_quantity, i.target_price, i.lead_time_req, i.delivery_location,
                   i.required_delivery_date, i.specifications, i.status, i.created_at,
                   p.title AS product_name, p.slug AS product_slug, p.primary_image,
                   p.min_price, p.max_price, pu.symbol AS price_unit_symbol, mu.name AS moq_unit_name,
                   COALESCE(s.company_name, ps.company_name) AS supplier_name,
                   COALESCE(s.email, ps.email) AS supplier_email,
                   (SELECT COUNT(*) FROM inquiry_replies r WHERE r.inquiry_id = i.id) AS reply_count
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN price_units pu ON p.price_unit_id = pu.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN suppliers ps ON p.supplier_id = ps.id
            WHERE {$whereClause}
            ORDER BY i.created_at DESC, i.id DESC
        ", $bindings);

        return $rows;
    }

    public static function getSupplierEnquiries(int $supplierId): array
    {
        // Suppliers see enquiries explicitly assigned to them OR related to products owned by them.
        // Never expose admin_notes or unassigned general/contact enquiries.
        return self::fetchAll("
            SELECT i.id, i.enquiry_source, i.product_id, i.supplier_id, i.buyer_user_id,
                   i.buyer_name, i.buyer_email, i.buyer_phone, i.company_name, i.subject,
                   i.target_quantity, i.target_price, i.lead_time_req, i.delivery_location,
                   i.required_delivery_date, i.specifications, i.status, i.created_at,
                   p.title AS product_name, p.slug AS product_slug, p.primary_image,
                   mu.name AS moq_unit_name,
                   (SELECT COUNT(*) FROM inquiry_replies r WHERE r.inquiry_id = i.id) AS reply_count
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            WHERE (i.supplier_id = :s1 OR p.supplier_id = :s2)
            ORDER BY i.created_at DESC, i.id DESC
        ", [
            ':s1' => $supplierId,
            ':s2' => $supplierId,
        ]);
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

        // Update inquiry status to RESPONDED if sender is supplier/admin and currently PENDING or SUBMITTED or VIEWED
        if ($senderRole === 'SUPPLIER' || $senderRole === 'SUPER_ADMIN') {
            self::execute("
                UPDATE product_inquiries 
                SET status = 'RESPONDED' 
                WHERE id = :id AND status IN ('PENDING', 'SUBMITTED', 'VIEWED')
            ", [':id' => $inquiryId]);
        }

        return (int) self::lastInsertId();
    }

    public static function allRecent(int $limit = 20): array
    {
        return self::fetchAll("
            SELECT i.*, p.title as product_title, p.slug as product_slug, COALESCE(s.company_name, ps.company_name) as supplier_name
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN suppliers ps ON p.supplier_id = ps.id
            ORDER BY i.created_at DESC, i.id DESC
            LIMIT :lim
        ", [':lim' => $limit]);
    }

    // ─── Super Admin: Centralized Enquiry Management ──────────────────────

    private static function buildAdminFilterClauses(array $params, array &$bindings): array
    {
        $where = ['1=1'];

        // Supplier Filter: assigned to that supplier OR related to products owned by that supplier
        if (!empty($params['supplier_id']) && $params['supplier_id'] !== 'ALL') {
            if ($params['supplier_id'] === 'UNASSIGNED') {
                $where[] = "(i.supplier_id IS NULL AND (p.supplier_id IS NULL OR i.product_id IS NULL))";
            } else {
                $where[] = "(i.supplier_id = :f_sup1 OR p.supplier_id = :f_sup2)";
                $bindings[':f_sup1'] = (int) $params['supplier_id'];
                $bindings[':f_sup2'] = (int) $params['supplier_id'];
            }
        }

        // Enquiry Source Filter
        if (!empty($params['sample_only'])) {
            $where[] = "i.enquiry_source = 'SAMPLE'";
        } elseif (!empty($params['rfq_oem_only'])) {
            if (!empty($params['enquiry_source']) && in_array($params['enquiry_source'], ['RFQ', 'FAST_OEM'], true)) {
                $where[] = "i.enquiry_source = :f_rfq_source";
                $bindings[':f_rfq_source'] = $params['enquiry_source'];
            } else {
                $where[] = "i.enquiry_source IN ('RFQ', 'FAST_OEM')";
            }
        } elseif (!empty($params['enquiry_source']) && $params['enquiry_source'] !== 'ALL') {
            $where[] = "i.enquiry_source = :f_source";
            $bindings[':f_source'] = $params['enquiry_source'];
        }

        // Enquiry Status Filter
        if (!empty($params['status']) && $params['status'] !== 'ALL') {
            if ($params['status'] === 'SUBMITTED' || $params['status'] === 'PENDING') {
                $where[] = "i.status IN ('SUBMITTED', 'PENDING')";
            } elseif ($params['status'] === 'RESOLVED' || $params['status'] === 'CLOSED' || $params['status'] === 'RESOLVED_CLOSED') {
                $where[] = "i.status IN ('RESOLVED', 'CLOSED')";
            } elseif ($params['status'] === 'IN_DISCUSSION' || $params['status'] === 'RESPONDED' || $params['status'] === 'RESPONDED_DISCUSSION') {
                $where[] = "i.status IN ('RESPONDED', 'IN_DISCUSSION')";
            } else {
                $where[] = "i.status = :f_status";
                $bindings[':f_status'] = $params['status'];
            }
        }

        // Product Category Filter
        if (!empty($params['category_id']) && $params['category_id'] !== 'ALL') {
            $where[] = "p.category_id = :f_cat";
            $bindings[':f_cat'] = (int) $params['category_id'];
        }

        // Buyer Filter (from Buyer Management "View Enquiries")
        if (!empty($params['buyer_id'])) {
            $buyer = User::findById((int) $params['buyer_id']);
            if ($buyer) {
                $where[] = "(i.buyer_user_id = :f_buyer_id OR (i.enquiry_source != 'CONTACT_US' AND LOWER(i.buyer_email) = :f_buyer_email))";
                $bindings[':f_buyer_id'] = (int) $buyer['id'];
                $bindings[':f_buyer_email'] = strtolower(trim($buyer['email']));
            } else {
                $where[] = "i.buyer_user_id = :f_buyer_id";
                $bindings[':f_buyer_id'] = (int) $params['buyer_id'];
            }
        }

        // Date Range Filter
        if (!empty($params['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['start_date'])) {
            $where[] = "DATE(i.created_at) >= :f_start_date";
            $bindings[':f_start_date'] = $params['start_date'];
        }
        if (!empty($params['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['end_date'])) {
            $where[] = "DATE(i.created_at) <= :f_end_date";
            $bindings[':f_end_date'] = $params['end_date'];
        }

        // Global Search across all 14 required fields
        $search = trim($params['search']['value'] ?? ($params['search_query'] ?? ''));
        if ($search !== '') {
            $cleanIdSearch = preg_replace('/^#?ENQ-0*/i', '', $search);
            $where[] = "(
                CAST(i.id AS CHAR) LIKE :gs1
                OR CAST(i.id AS CHAR) = :gs_exact_id
                OR i.buyer_name LIKE :gs2
                OR i.company_name LIKE :gs3
                OR i.buyer_email LIKE :gs4
                OR i.buyer_phone LIKE :gs5
                OR p.title LIKE :gs6
                OR CAST(i.product_id AS CHAR) LIKE :gs7
                OR s.company_name LIKE :gs8
                OR s.contact_person LIKE :gs9
                OR ps.company_name LIKE :gs10
                OR ps.contact_person LIKE :gs11
                OR i.subject LIKE :gs12
                OR i.specifications LIKE :gs13
                OR i.delivery_location LIKE :gs14
                OR i.lead_time_req LIKE :gs15
                OR i.enquiry_source LIKE :gs16
                OR i.status LIKE :gs17
                OR i.country_name LIKE :gs18
            )";
            $like = '%' . $search . '%';
            for ($k = 1; $k <= 18; $k++) {
                $bindings[":gs{$k}"] = $like;
            }
            $bindings[':gs_exact_id'] = $cleanIdSearch;
        }

        return $where;
    }

    public static function getAdminDataTable(array $params): array
    {
        $draw   = (int) ($params['draw'] ?? 1);
        $start  = max(0, (int) ($params['start'] ?? 0));
        $length = max(1, min(100, (int) ($params['length'] ?? 15)));

        if (!empty($params['sample_only'])) {
            $totalRecords = (int) (self::fetch("SELECT COUNT(*) AS cnt FROM product_inquiries WHERE enquiry_source = 'SAMPLE'")['cnt'] ?? 0);
        } elseif (!empty($params['rfq_oem_only'])) {
            $totalRecords = (int) (self::fetch("SELECT COUNT(*) AS cnt FROM product_inquiries WHERE enquiry_source IN ('RFQ', 'FAST_OEM')")['cnt'] ?? 0);
        } elseif (!empty($params['buyer_id'])) {
            $buyer = User::findById((int) $params['buyer_id']);
            if ($buyer) {
                $totalRecords = (int) (self::fetch("SELECT COUNT(*) AS cnt FROM product_inquiries WHERE buyer_user_id = :buid OR (enquiry_source != 'CONTACT_US' AND LOWER(buyer_email) = :bemail)", [
                    ':buid' => (int) $buyer['id'],
                    ':bemail' => strtolower(trim($buyer['email']))
                ])['cnt'] ?? 0);
            } else {
                $totalRecords = (int) (self::fetch("SELECT COUNT(*) AS cnt FROM product_inquiries WHERE buyer_user_id = :buid", [':buid' => (int)$params['buyer_id']])['cnt'] ?? 0);
            }
        } else {
            $totalRecords = (int) (self::fetch("SELECT COUNT(*) AS cnt FROM product_inquiries")['cnt'] ?? 0);
        }

        $bindings = [];
        $where = self::buildAdminFilterClauses($params, $bindings);
        $whereSql = implode(' AND ', $where);

        $fromJoins = "
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN suppliers ps ON p.supplier_id = ps.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
        ";

        $filteredRecords = (int) (self::fetch("SELECT COUNT(*) AS cnt {$fromJoins} WHERE {$whereSql}", $bindings)['cnt'] ?? 0);

        if (!empty($params['sample_only'])) {
            $columns = [
                0 => 'i.id',
                1 => 'i.created_at',
                2 => 'i.buyer_name',
                3 => 'p.title',
                4 => 's.company_name',
                5 => 'i.target_quantity',
                6 => 'i.delivery_location',
                7 => 'i.status',
            ];
            $orderColIdx = (int) ($params['order'][0]['column'] ?? 1);
        } elseif (!empty($params['rfq_oem_only'])) {
            $columns = [
                0  => 'i.id',
                1  => 'i.enquiry_source',
                2  => 'i.buyer_name',
                3  => 'i.company_name',
                4  => 'i.buyer_email',
                5  => 'p.title',
                6  => 'i.target_quantity',
                7  => 'i.target_price',
                8  => 'i.delivery_location',
                9  => 's.company_name',
                10 => 'i.created_at',
                11 => 'i.status',
            ];
            $orderColIdx = (int) ($params['order'][0]['column'] ?? 10);
        } else {
            $columns = [
                0  => 'i.id',
                1  => 'i.enquiry_source',
                2  => 'i.buyer_name',
                3  => 'i.company_name',
                4  => 'i.buyer_email',
                5  => 'i.buyer_phone',
                6  => 'p.title',
                7  => 's.company_name',
                8  => 'i.target_quantity',
                9  => 'i.delivery_location',
                10 => 'i.subject',
                11 => 'i.created_at',
                12 => 'i.status',
            ];
            $orderColIdx = (int) ($params['order'][0]['column'] ?? 11);
        }
        $orderCol    = $columns[$orderColIdx] ?? 'i.created_at';
        $orderDir    = strtoupper($params['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $rows = self::fetchAll("
            SELECT i.*,
                   p.title AS product_name, p.slug AS product_slug, p.primary_image,
                   c.name AS category_name,
                   COALESCE(s.company_name, ps.company_name) AS supplier_name,
                   COALESCE(i.supplier_id, p.supplier_id) AS effective_supplier_id,
                   mu.name AS moq_unit_name,
                   (SELECT COUNT(*) FROM inquiry_replies r WHERE r.inquiry_id = i.id) AS reply_count
            {$fromJoins}
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

    public static function getAdminExportData(array $params = []): array
    {
        $bindings = [];
        $where = self::buildAdminFilterClauses($params, $bindings);
        $whereSql = implode(' AND ', $where);

        // Internal admin_notes is strictly excluded from exports
        return self::fetchAll("
            SELECT 
                CONCAT('ENQ-', LPAD(i.id, 5, '0')) AS 'Enquiry ID',
                CASE i.enquiry_source
                    WHEN 'PRODUCT_PAGE' THEN 'Product Page Enquiry'
                    WHEN 'GENERAL' THEN 'General Enquiry'
                    WHEN 'CONTACT_US' THEN 'Contact Us Enquiry'
                    WHEN 'RFQ' THEN 'Request For Quotation (RFQ)'
                    WHEN 'FAST_OEM' THEN 'Fast OEM Customization'
                    WHEN 'SAMPLE' THEN 'Order Prototype Sample'
                    ELSE i.enquiry_source
                END AS 'Enquiry Source',
                i.buyer_name AS 'Buyer Name',
                COALESCE(i.company_name, 'N/A') AS 'Buyer Company',
                i.buyer_email AS 'Buyer Email',
                COALESCE(i.buyer_phone, 'N/A') AS 'Buyer Phone',
                COALESCE(p.title, i.subject, 'General Requirement') AS 'Product Name / Requirement',
                COALESCE(s.company_name, ps.company_name, 'Unassigned') AS 'Supplier Name',
                i.target_quantity AS 'Quantity Required',
                COALESCE(i.country_name, 'N/A') AS 'Country',
                COALESCE(i.delivery_location, i.lead_time_req, 'N/A') AS 'Delivery Location',
                COALESCE(i.subject, p.title, 'B2B Enquiry') AS 'Enquiry Subject',
                i.specifications AS 'Message / Specifications',
                i.status AS 'Enquiry Status',
                i.created_at AS 'Enquiry Date'
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN suppliers ps ON p.supplier_id = ps.id
            WHERE {$whereSql}
            ORDER BY i.created_at DESC
        ", $bindings);
    }

    public static function getAdminMetrics(): array
    {
        $row = self::fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN enquiry_source = 'PRODUCT_PAGE' THEN 1 ELSE 0 END) AS product_page,
                SUM(CASE WHEN enquiry_source = 'GENERAL' THEN 1 ELSE 0 END) AS general_rfq,
                SUM(CASE WHEN enquiry_source = 'CONTACT_US' THEN 1 ELSE 0 END) AS contact_us,
                SUM(CASE WHEN enquiry_source = 'RFQ' THEN 1 ELSE 0 END) AS rfq_count,
                SUM(CASE WHEN enquiry_source = 'FAST_OEM' THEN 1 ELSE 0 END) AS fast_oem_count,
                SUM(CASE WHEN enquiry_source = 'SAMPLE' THEN 1 ELSE 0 END) AS sample_count,
                SUM(CASE WHEN status IN ('PENDING', 'SUBMITTED') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status IN ('RESPONDED', 'IN_DISCUSSION') THEN 1 ELSE 0 END) AS responded,
                SUM(CASE WHEN status IN ('CLOSED', 'RESOLVED') THEN 1 ELSE 0 END) AS resolved
            FROM product_inquiries
        ");

        return [
            'total'          => (int) ($row['total'] ?? 0),
            'product_page'   => (int) ($row['product_page'] ?? 0),
            'general_rfq'    => (int) ($row['general_rfq'] ?? 0),
            'contact_us'     => (int) ($row['contact_us'] ?? 0),
            'rfq_count'      => (int) ($row['rfq_count'] ?? 0),
            'fast_oem_count' => (int) ($row['fast_oem_count'] ?? 0),
            'sample_count'   => (int) ($row['sample_count'] ?? 0),
            'pending'        => (int) ($row['pending'] ?? 0),
            'responded'      => (int) ($row['responded'] ?? 0),
            'resolved'       => (int) ($row['resolved'] ?? 0),
        ];
    }

    public static function getRfqOemMetrics(): array
    {
        $row = self::fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN enquiry_source = 'RFQ' THEN 1 ELSE 0 END) AS rfq_count,
                SUM(CASE WHEN enquiry_source = 'FAST_OEM' THEN 1 ELSE 0 END) AS fast_oem_count,
                SUM(CASE WHEN status IN ('PENDING', 'SUBMITTED') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status IN ('RESPONDED', 'IN_DISCUSSION') THEN 1 ELSE 0 END) AS responded,
                SUM(CASE WHEN status IN ('CLOSED', 'RESOLVED') THEN 1 ELSE 0 END) AS resolved
            FROM product_inquiries
            WHERE enquiry_source IN ('RFQ', 'FAST_OEM')
        ");

        return [
            'total'          => (int) ($row['total'] ?? 0),
            'rfq_count'      => (int) ($row['rfq_count'] ?? 0),
            'fast_oem_count' => (int) ($row['fast_oem_count'] ?? 0),
            'pending'        => (int) ($row['pending'] ?? 0),
            'responded'      => (int) ($row['responded'] ?? 0),
            'resolved'       => (int) ($row['resolved'] ?? 0),
        ];
    }

    public static function assignSupplier(int $id, ?int $supplierId): bool
    {
        self::execute("
            UPDATE product_inquiries SET supplier_id = :supplier_id WHERE id = :id
        ", [
            ':supplier_id' => $supplierId ?: null,
            ':id'          => $id,
        ]);
        return true;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }
        self::execute("
            UPDATE product_inquiries SET status = :status WHERE id = :id
        ", [
            ':status' => $status,
            ':id'     => $id,
        ]);
        return true;
    }

    public static function updateAdminNotes(int $id, ?string $notes): bool
    {
        self::execute("
            UPDATE product_inquiries SET admin_notes = :notes WHERE id = :id
        ", [
            ':notes' => $notes !== null ? trim($notes) : null,
            ':id'    => $id,
        ]);
        return true;
    }

    public static function getSampleMetrics(): array
    {
        $row = self::fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status IN ('PENDING', 'SUBMITTED') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'IN_PRODUCTION' THEN 1 ELSE 0 END) AS in_production,
                SUM(CASE WHEN status = 'SAMPLE_SHIPPED' THEN 1 ELSE 0 END) AS shipped,
                SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) AS rejected
            FROM product_inquiries
            WHERE enquiry_source = 'SAMPLE'
        ");

        return [
            'total'         => (int) ($row['total'] ?? 0),
            'pending'       => (int) ($row['pending'] ?? 0),
            'in_production' => (int) ($row['in_production'] ?? 0),
            'shipped'       => (int) ($row['shipped'] ?? 0),
            'delivered'     => (int) ($row['delivered'] ?? 0),
            'rejected'      => (int) ($row['rejected'] ?? 0),
        ];
    }

    public static function getSupplierSampleEnquiries(int $supplierId): array
    {
        return self::fetchAll("
            SELECT i.*,
                   p.title AS product_name, p.slug AS product_slug, p.primary_image, p.sample_price,
                   mu.name AS moq_unit_name,
                   (SELECT COUNT(*) FROM inquiry_replies r WHERE r.inquiry_id = i.id) AS reply_count
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN moq_units mu ON p.moq_unit_id = mu.id
            WHERE i.enquiry_source = 'SAMPLE'
              AND (i.supplier_id = :s1 OR p.supplier_id = :s2)
            ORDER BY i.created_at DESC, i.id DESC
        ", [
            ':s1' => $supplierId,
            ':s2' => $supplierId,
        ]);
    }

    public static function getBuyerSampleEnquiries(int $buyerUserId, ?string $email = null): array
    {
        $where = [
            "i.enquiry_source = 'SAMPLE'",
            "(i.buyer_user_id = :uid" . (!empty($email) ? " OR LOWER(i.buyer_email) = :email" : "") . ")"
        ];
        $bindings = [':uid' => $buyerUserId];
        if (!empty($email)) {
            $bindings[':email'] = strtolower(trim($email));
        }

        $whereClause = implode(' AND ', $where);

        return self::fetchAll("
            SELECT i.*,
                   p.title AS product_name, p.slug AS product_slug, p.primary_image, p.sample_price,
                   COALESCE(s.company_name, ps.company_name) AS supplier_name,
                   COALESCE(s.email, ps.email) AS supplier_email,
                   (SELECT COUNT(*) FROM inquiry_replies r WHERE r.inquiry_id = i.id) AS reply_count
            FROM product_inquiries i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN suppliers ps ON p.supplier_id = ps.id
            WHERE {$whereClause}
            ORDER BY i.created_at DESC, i.id DESC
        ", $bindings);
    }

    public static function updateSampleStatus(int $id, string $status, ?string $notes = null): bool
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }

        if ($notes !== null && $notes !== '') {
            self::execute("
                UPDATE product_inquiries 
                SET status = :status, admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[Status Update: ', :status_note, ' - ', NOW(), ']: ', :notes)
                WHERE id = :id
            ", [
                ':status'      => $status,
                ':status_note' => $status,
                ':notes'       => trim($notes),
                ':id'          => $id,
            ]);
        } else {
            self::execute("
                UPDATE product_inquiries SET status = :status WHERE id = :id
            ", [
                ':status' => $status,
                ':id'     => $id,
            ]);
        }
        return true;
    }
}
