<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Supplier extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("
            SELECT s.*, 
                   bt.name AS business_type_name,
                   c.name AS country_name,
                   st.name AS state_name,
                   ct.name AS city_name,
                   u.email_verified_at,
                   u.status AS user_status,
                   u.last_login_at
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            LEFT JOIN countries c ON s.country_id = c.id
            LEFT JOIN states st ON s.state_id = st.id
            LEFT JOIN cities ct ON s.city_id = ct.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = :id
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function findByUserId(int $userId): ?array
    {
        return self::fetch("
            SELECT s.*, 
                   bt.name AS business_type_name,
                   c.name AS country_name,
                   st.name AS state_name,
                   ct.name AS city_name
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            LEFT JOIN countries c ON s.country_id = c.id
            LEFT JOIN states st ON s.state_id = st.id
            LEFT JOIN cities ct ON s.city_id = ct.id
            WHERE s.user_id = :user_id
            LIMIT 1
        ", [':user_id' => $userId]);
    }

    public static function findByEmail(string $email): ?array
    {
        return self::fetch("
            SELECT * FROM suppliers WHERE LOWER(email) = :email LIMIT 1
        ", [':email' => strtolower(trim($email))]);
    }
    public static function all(): array
    {
        return self::fetchAll("
            SELECT s.id, s.company_name, s.contact_person, s.email, s.phone, s.approval_status, s.status, bt.name AS business_type_name
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            ORDER BY s.company_name ASC
        ");
    }

    public static function allApproved(): array
    {
        return self::fetchAll("
            SELECT s.id, s.company_name, s.contact_person, s.email, bt.name AS business_type_name
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            WHERE s.approval_status = 'APPROVED' AND s.status = 'ACTIVE'
            ORDER BY s.company_name ASC
        ");
    }

    public static function allActive(): array
    {
        return self::fetchAll("
            SELECT s.id, s.company_name, s.contact_person, s.email, s.approval_status, bt.name AS business_type_name
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            WHERE s.status = 'ACTIVE'
            ORDER BY s.company_name ASC
        ");
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO suppliers (
                user_id, business_type_id, country_id, state_id, city_id,
                company_name, contact_person, email, phone, country_code,
                address, postal_code, status, approval_status, terms_accepted_at,
                created_at, updated_at
            ) VALUES (
                :user_id, :business_type_id, :country_id, :state_id, :city_id,
                :company_name, :contact_person, :email, :phone, :country_code,
                :address, :postal_code, :status, :approval_status, NOW(),
                NOW(), NOW()
            )
        ", [
            ':user_id'          => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            ':business_type_id' => (int)$data['business_type_id'],
            ':country_id'       => (int)$data['country_id'],
            ':state_id'         => (int)$data['state_id'],
            ':city_id'          => (int)$data['city_id'],
            ':company_name'     => trim($data['company_name']),
            ':contact_person'   => trim($data['contact_person']),
            ':email'            => strtolower(trim($data['email'])),
            ':phone'            => trim($data['phone'] ?? ''),
            ':country_code'     => trim($data['country_code'] ?? '+91'),
            ':address'          => trim($data['address'] ?? ''),
            ':postal_code'      => trim($data['postal_code'] ?? ''),
            ':status'           => $data['status'] ?? 'ACTIVE',
            ':approval_status'  => $data['approval_status'] ?? 'PENDING_VERIFICATION',
        ]);

        return (int) self::lastInsertId();
    }

    public static function updateApprovalStatus(int $id, string $status, ?string $reason = null, ?int $adminId = null): bool
    {
        $supplier = self::findById($id);
        if (!$supplier) {
            return false;
        }

        self::beginTransaction();
        try {
            self::execute("
                UPDATE suppliers 
                SET approval_status = :status, 
                    rejection_reason = :reason,
                    reviewed_by = :admin_id,
                    reviewed_at = NOW(),
                    status = :active_status,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':status'        => $status,
                ':reason'        => $reason,
                ':admin_id'      => $adminId,
                ':active_status' => ($status === 'APPROVED') ? 'ACTIVE' : ($status === 'SUSPENDED' ? 'INACTIVE' : 'INACTIVE'),
                ':id'            => $id
            ]);

            // Sync user account status
            if (!empty($supplier['user_id'])) {
                $userStatus = ($status === 'APPROVED') ? 'ACTIVE' : 'INACTIVE';
                self::execute("
                    UPDATE users 
                    SET status = :status, updated_at = NOW() 
                    WHERE id = :user_id
                ", [
                    ':status'  => $userStatus,
                    ':user_id' => (int)$supplier['user_id']
                ]);
            }

            self::commit();
            return true;
        } catch (\Throwable $e) {
            self::rollBack();
            return false;
        }
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int) ($params['draw'] ?? 1);
        $start = (int) ($params['start'] ?? 0);
        $length = (int) ($params['length'] ?? 10);
        $searchValue = trim($params['search']['value'] ?? '');
        $statusFilter = trim($params['status_filter'] ?? '');

        $where = [];
        $bindings = [];

        if ($searchValue !== '') {
            $where[] = "(s.company_name LIKE :search OR s.contact_person LIKE :search OR s.email LIKE :search OR s.phone LIKE :search OR bt.name LIKE :search)";
            $bindings[':search'] = "%{$searchValue}%";
        }

        if ($statusFilter !== '') {
            $where[] = "s.approval_status = :status_filter";
            $bindings[':status_filter'] = $statusFilter;
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers");
        $filteredRecords = (int) self::fetchColumn("
            SELECT COUNT(*) 
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            {$whereSql}
        ", $bindings);

        $sql = "
            SELECT s.*, 
                   bt.name AS business_type_name,
                   c.name AS country_name,
                   st.name AS state_name,
                   ct.name AS city_name,
                   u.email_verified_at,
                   (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.id) AS product_count
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            LEFT JOIN countries c ON s.country_id = c.id
            LEFT JOIN states st ON s.state_id = st.id
            LEFT JOIN cities ct ON s.city_id = ct.id
            LEFT JOIN users u ON s.user_id = u.id
            {$whereSql}
            ORDER BY s.id DESC
            LIMIT {$length} OFFSET {$start}
        ";

        $data = self::fetchAll($sql, $bindings);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ];
    }

    public static function getMetrics(): array
    {
        return [
            'total'     => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers"),
            'pending'   => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE approval_status = 'PENDING_APPROVAL'"),
            'approved'  => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE approval_status = 'APPROVED'"),
            'rejected'  => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE approval_status = 'REJECTED'"),
            'suspended' => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE approval_status = 'SUSPENDED'"),
        ];
    }

    public static function updateProfile(int $id, array $data): bool
    {
        $supplier = self::findById($id);
        if (!$supplier) {
            return false;
        }

        self::execute("
            UPDATE suppliers SET
                company_name     = :company_name,
                contact_person   = :contact_person,
                email            = :email,
                phone            = :phone,
                country_code     = :country_code,
                business_type_id = :business_type_id,
                address          = :address,
                country_id       = :country_id,
                state_id         = :state_id,
                city_id          = :city_id,
                postal_code      = :postal_code,
                updated_at       = NOW()
            WHERE id = :id
        ", [
            ':company_name'     => trim($data['company_name']),
            ':contact_person'   => trim($data['contact_person']),
            ':email'            => strtolower(trim($data['email'])),
            ':phone'            => trim($data['phone'] ?? ''),
            ':country_code'     => trim($data['country_code'] ?? '+91'),
            ':business_type_id' => (int)$data['business_type_id'],
            ':address'          => trim($data['address'] ?? ''),
            ':country_id'       => (int)$data['country_id'],
            ':state_id'         => (int)$data['state_id'],
            ':city_id'          => (int)$data['city_id'],
            ':postal_code'      => trim($data['postal_code'] ?? ''),
            ':id'               => $id,
        ]);

        // Keep associated user updated
        if (!empty($supplier['user_id'])) {
            $userUpdate = [
                ':name'         => trim($data['contact_person']),
                ':company_name' => trim($data['company_name']),
                ':email'        => strtolower(trim($data['email'])),
                ':phone'        => trim($data['phone'] ?? ''),
                ':country_code' => trim($data['country_code'] ?? '+91'),
                ':country_id'   => (int)$data['country_id'],
                ':user_id'      => (int)$supplier['user_id'],
            ];
            
            // If email changed, require reverification on user account
            $emailChanged = strtolower(trim($supplier['email'])) !== strtolower(trim($data['email']));
            $emailReverifySql = $emailChanged ? ", email_verified_at = NULL" : "";

            self::execute("
                UPDATE users SET
                    name = :name,
                    company_name = :company_name,
                    email = :email,
                    phone = :phone,
                    country_code = :country_code,
                    country_id = :country_id{$emailReverifySql},
                    updated_at = NOW()
                WHERE id = :user_id
            ", $userUpdate);
        }

        return true;
    }

    public static function checkDependencies(int $id): array
    {
        $productCount = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE supplier_id = :id", [':id' => $id]);
        $inquiryCount = (int) self::fetchColumn("SELECT COUNT(*) FROM product_inquiries WHERE supplier_id = :id", [':id' => $id]);

        return [
            'products'  => $productCount,
            'inquiries' => $inquiryCount,
            'can_delete' => ($productCount === 0 && $inquiryCount === 0),
        ];
    }

    public static function delete(int $id): bool
    {
        $deps = self::checkDependencies($id);
        if (!$deps['can_delete']) {
            return false;
        }

        $supplier = self::findById($id);
        if (!$supplier) {
            return false;
        }

        self::beginTransaction();
        try {
            self::execute("DELETE FROM suppliers WHERE id = :id", [':id' => $id]);
            if (!empty($supplier['user_id'])) {
                self::execute("DELETE FROM users WHERE id = :user_id", [':user_id' => (int)$supplier['user_id']]);
            }
            self::commit();
            return true;
        } catch (\Throwable $e) {
            self::rollBack();
            return false;
        }
    }
}

