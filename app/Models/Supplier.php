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
                   c.iso_alpha2 AS country_iso,
                   COALESCE(s.state, st.name) AS state_name,
                   COALESCE(s.city, ct.name) AS city_name,
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
                   c.iso_alpha2 AS country_iso,
                   COALESCE(s.state, st.name) AS state_name,
                   COALESCE(s.city, ct.name) AS city_name
            FROM suppliers s
            LEFT JOIN business_types bt ON s.business_type_id = bt.id
            LEFT JOIN countries c ON s.country_id = c.id
            LEFT JOIN states st ON s.state_id = st.id
            LEFT JOIN cities ct ON s.city_id = ct.id
            WHERE s.user_id = :user_id
            LIMIT 1
        ", [':user_id' => $userId]);
    }

    public static function currentSupplierId(): int
    {
        $authUser = \App\Helpers\Session::get('auth_user');
        if (!$authUser || ($authUser['role'] ?? '') !== 'SUPPLIER' || empty($authUser['id'])) {
            return 0;
        }

        $authSupplier = \App\Helpers\Session::get('auth_supplier');
        if (!empty($authSupplier['id'])) {
            return (int)$authSupplier['id'];
        }

        $supplier = self::findByUserId((int)$authUser['id']);
        if ($supplier && !empty($supplier['id'])) {
            \App\Helpers\Session::set('auth_supplier', $supplier);
            return (int)$supplier['id'];
        }

        return 0;
    }

    public static function isOwnedByCurrentSupplier(mixed $productOrSupplier): bool
    {
        $mySupplierId = self::currentSupplierId();
        if ($mySupplierId <= 0) {
            return false;
        }

        if (is_array($productOrSupplier)) {
            $targetSupplierId = (int)($productOrSupplier['supplier_id'] ?? 0);
            if ($targetSupplierId > 0) {
                return $targetSupplierId === $mySupplierId;
            }
            if (isset($productOrSupplier['id']) && (!isset($productOrSupplier['title']) && !isset($productOrSupplier['min_price']))) {
                return (int)$productOrSupplier['id'] === $mySupplierId;
            }
            return false;
        }

        if (is_numeric($productOrSupplier)) {
            return (int)$productOrSupplier === $mySupplierId;
        }

        return false;
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
                state, city, logo, description,
                company_name, contact_person, email, phone, country_code,
                address, postal_code, status, approval_status, terms_accepted_at,
                created_at, updated_at
            ) VALUES (
                :user_id, :business_type_id, :country_id, :state_id, :city_id,
                :state, :city, :logo, :description,
                :company_name, :contact_person, :email, :phone, :country_code,
                :address, :postal_code, :status, :approval_status, NOW(),
                NOW(), NOW()
            )
        ", [
            ':user_id'          => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            ':business_type_id' => (int)$data['business_type_id'],
            ':country_id'       => (int)$data['country_id'],
            ':state_id'         => !empty($data['state_id']) ? (int)$data['state_id'] : null,
            ':city_id'          => !empty($data['city_id']) ? (int)$data['city_id'] : null,
            ':state'            => !empty($data['state']) ? trim($data['state']) : null,
            ':city'             => !empty($data['city']) ? trim($data['city']) : null,
            ':logo'             => !empty($data['logo']) ? trim($data['logo']) : null,
            ':description'      => !empty($data['description']) ? trim($data['description']) : null,
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
            $where[] = "(s.company_name LIKE :s1 OR s.contact_person LIKE :s2 OR s.email LIKE :s3 OR s.phone LIKE :s4 OR bt.name LIKE :s5)";
            $term = "%{$searchValue}%";
            for ($i = 1; $i <= 5; $i++) {
                $bindings[":s{$i}"] = $term;
            }
        }

        if ($statusFilter !== '') {
            if ($statusFilter === 'INACTIVE') {
                $where[] = "s.status = 'INACTIVE'";
            } elseif ($statusFilter === 'ACTIVE') {
                $where[] = "s.status = 'ACTIVE'";
            } else {
                $where[] = "s.approval_status = :status_filter";
                $bindings[':status_filter'] = $statusFilter;
            }
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
                   c.iso_alpha2 AS country_iso,
                   COALESCE(s.state, st.name) AS state_name,
                   COALESCE(s.city, ct.name) AS city_name,
                   u.email_verified_at,
                   (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.id) AS product_count,
                   (SELECT pcr.id FROM profile_change_requests pcr WHERE pcr.supplier_id = s.id AND pcr.status = 'PENDING' LIMIT 1) AS pending_profile_change_id
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
            'inactive'  => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE status = 'INACTIVE'"),
            'rejected'  => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE approval_status = 'REJECTED'"),
            'suspended' => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE approval_status = 'SUSPENDED'"),
        ];
    }

    public static function setStatus(int $id, string $status): bool
    {
        $supplier = self::findById($id);
        if (!$supplier) {
            return false;
        }

        $status = in_array(strtoupper($status), ['ACTIVE', 'INACTIVE'], true) ? strtoupper($status) : 'ACTIVE';

        self::beginTransaction();
        try {
            self::execute("
                UPDATE suppliers
                SET status = :status, updated_at = NOW()
                WHERE id = :id
            ", [
                ':status' => $status,
                ':id'     => $id,
            ]);

            if (!empty($supplier['user_id'])) {
                self::execute("
                    UPDATE users
                    SET status = :status, updated_at = NOW()
                    WHERE id = :user_id
                ", [
                    ':status'  => $status,
                    ':user_id' => (int)$supplier['user_id'],
                ]);
            }

            self::commit();
            return true;
        } catch (\Throwable $e) {
            self::rollBack();
            return false;
        }
    }

    public static function updateProfile(int $id, array $data): bool
    {
        $supplier = self::findById($id);
        if (!$supplier) {
            return false;
        }

        $companyName    = trim((string)($data['company_name'] ?? ($supplier['company_name'] ?? '')));
        $contactPerson  = trim((string)($data['contact_person'] ?? ($supplier['contact_person'] ?? '')));
        $email          = !empty($data['email']) ? strtolower(trim((string)$data['email'])) : strtolower(trim((string)($supplier['email'] ?? '')));
        $phone          = trim((string)($data['phone'] ?? ($supplier['phone'] ?? '')));
        $countryCode    = trim((string)($data['country_code'] ?? ($supplier['country_code'] ?? '+91')));
        $businessTypeId = (int)($data['business_type_id'] ?? ($supplier['business_type_id'] ?? 1));
        $address        = trim((string)($data['address'] ?? ($supplier['address'] ?? '')));
        $countryId      = (int)($data['country_id'] ?? ($supplier['country_id'] ?? 1));
        $stateId        = !empty($data['state_id']) ? (int)$data['state_id'] : ($supplier['state_id'] ?? null);
        $cityId         = !empty($data['city_id']) ? (int)$data['city_id'] : ($supplier['city_id'] ?? null);
        $state          = !empty($data['state']) ? trim((string)$data['state']) : ($supplier['state'] ?? null);
        $city           = !empty($data['city']) ? trim((string)$data['city']) : ($supplier['city'] ?? null);
        $description    = isset($data['description']) ? trim((string)$data['description']) : ($supplier['description'] ?? null);
        $postalCode     = trim((string)($data['postal_code'] ?? ($supplier['postal_code'] ?? '')));

        $statusSql = "";
        $logoSql = !empty($data['logo']) ? ", logo = :logo" : "";
        $bindings = [
            ':company_name'     => $companyName,
            ':contact_person'   => $contactPerson,
            ':email'            => $email,
            ':phone'            => $phone,
            ':country_code'     => $countryCode,
            ':business_type_id' => $businessTypeId,
            ':address'          => $address,
            ':country_id'       => $countryId,
            ':state_id'         => $stateId,
            ':city_id'          => $cityId,
            ':state'            => $state,
            ':city'             => $city,
            ':description'      => $description,
            ':postal_code'      => $postalCode,
            ':id'               => $id,
        ];
        if (!empty($data['logo'])) {
            $bindings[':logo'] = trim((string)$data['logo']);
        }
        if (!empty($data['status']) && in_array($data['status'], ['ACTIVE', 'INACTIVE'], true)) {
            $statusSql = ", status = :status";
            $bindings[':status'] = $data['status'];
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
                state            = :state,
                city             = :city,
                description      = :description,
                postal_code      = :postal_code{$logoSql}{$statusSql},
                updated_at       = NOW()
            WHERE id = :id
        ", $bindings);

        // Keep associated user updated
        if (!empty($supplier['user_id'])) {
            $userUpdate = [
                ':name'         => $contactPerson,
                ':company_name' => $companyName,
                ':email'        => $email,
                ':phone'        => $phone,
                ':country_code' => $countryCode,
                ':country_id'   => $countryId,
                ':state'        => $state,
                ':city'         => $city,
                ':user_id'      => (int)$supplier['user_id'],
            ];
            
            // If email changed, require reverification on user account
            $emailChanged = !empty($data['email']) && strtolower(trim((string)$supplier['email'])) !== strtolower(trim((string)$data['email']));
            $emailReverifySql = $emailChanged ? ", email_verified_at = NULL" : "";

            self::execute("
                UPDATE users SET
                    name = :name,
                    company_name = :company_name,
                    email = :email,
                    phone = :phone,
                    country_code = :country_code,
                    country_id = :country_id,
                    state = :state,
                    city = :city{$emailReverifySql},
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

