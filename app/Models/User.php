<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public static function findByEmail(string $email): ?array
    {
        return self::fetch(
            "SELECT * FROM users WHERE LOWER(email) = :email LIMIT 1",
            [':email' => strtolower(trim($email))]
        );
    }

    public static function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = "SELECT id FROM users WHERE LOWER(email) = :email";
        $params = [':email' => strtolower(trim($email))];
        if ($ignoreId !== null) {
            $sql .= " AND id != :ignore_id";
            $params[':ignore_id'] = $ignoreId;
        }
        $sql .= " LIMIT 1";
        return (bool) self::fetch($sql, $params);
    }

    public static function findById(int $id): ?array
    {
        return self::fetch(
            "SELECT * FROM users WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
    }

    public static function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        self::execute("
            INSERT INTO users (
                name, company_name, email, password, role, status, 
                must_change_password, phone, country_code, country_id, 
                email_verified_at, terms_accepted_at, terms_version, created_at, updated_at
            )
            VALUES (
                :name, :company_name, :email, :password, :role, :status, 
                :must_change_password, :phone, :country_code, :country_id,
                :email_verified_at, :terms_accepted_at, :terms_version, NOW(), NOW()
            )
        ", [
            ':name'                 => trim($data['name']),
            ':company_name'         => !empty($data['company_name']) ? trim($data['company_name']) : null,
            ':email'                => strtolower(trim($data['email'])),
            ':password'             => $hash,
            ':role'                 => $data['role'] ?? 'ADMIN',
            ':status'               => $data['status'] ?? 'ACTIVE',
            ':must_change_password' => $data['must_change_password'] ?? 0,
            ':phone'                => $data['phone'] ?? null,
            ':country_code'         => $data['country_code'] ?? '+91',
            ':country_id'           => !empty($data['country_id']) ? (int) $data['country_id'] : null,
            ':email_verified_at'    => $data['email_verified_at'] ?? null,
            ':terms_accepted_at'    => !empty($data['terms_accepted']) ? date('Y-m-d H:i:s') : null,
            ':terms_version'        => $data['terms_version'] ?? 'v1.0',
        ]);

        return (int) self::lastInsertId();
    }

    public static function createBuyer(array $data): int
    {
        $data['role'] = 'BUYER';
        $data['status'] = 'PENDING_VERIFICATION';
        $data['terms_accepted'] = true;
        return self::create($data);
    }

    public static function createSupplierUser(array $data): int
    {
        $data['role'] = 'SUPPLIER';
        $data['status'] = 'PENDING_VERIFICATION';
        $data['terms_accepted'] = true;
        return self::create($data);
    }

    public static function markEmailVerified(int|string $identifier, string $role = 'BUYER'): void
    {
        $newStatus = ($role === 'BUYER' || $role === 'SUPER_ADMIN' || $role === 'ADMIN') ? 'ACTIVE' : 'PENDING_VERIFICATION';
        if (is_int($identifier) || ctype_digit((string)$identifier)) {
            self::execute("
                UPDATE users 
                SET email_verified_at = NOW(), status = :status, updated_at = NOW() 
                WHERE id = :id
            ", [
                ':status' => $newStatus,
                ':id'     => (int)$identifier
            ]);
        } else {
            self::execute("
                UPDATE users 
                SET email_verified_at = NOW(), status = :status, updated_at = NOW() 
                WHERE email = :email
            ", [
                ':status' => $newStatus,
                ':email'  => strtolower(trim((string)$identifier))
            ]);
        }
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function updateLastLogin(int $id): void
    {
        self::execute(
            "UPDATE users SET last_login_at = NOW() WHERE id = :id",
            [':id' => $id]
        );
    }

    public static function updatePassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        self::execute(
            "UPDATE users SET password = :password, must_change_password = 0, updated_at = NOW() WHERE id = :id",
            [':password' => $hash, ':id' => $id]
        );
    }

    public static function setRememberToken(int $id, ?string $token): void
    {
        self::execute("
            UPDATE users SET remember_token = :token WHERE id = :id
        ", [
            ':token' => $token,
            ':id'    => $id
        ]);
    }

    public static function findByRememberToken(string $token): ?array
    {
        return self::fetch("
            SELECT * FROM users WHERE remember_token = :token LIMIT 1
        ", [':token' => $token]);
    }

    public static function updateProfile(int $id, array $data): bool
    {
        $user = self::findById($id);
        if (!$user) {
            return false;
        }

        $emailChanged = strtolower(trim($user['email'])) !== strtolower(trim($data['email']));
        $emailReverifySql = $emailChanged ? ", email_verified_at = NULL, status = 'PENDING_VERIFICATION'" : "";

        self::execute("
            UPDATE users SET
                name         = :name,
                company_name = :company_name,
                email        = :email,
                phone        = :phone,
                country_code = :country_code,
                country_id   = :country_id{$emailReverifySql},
                updated_at   = NOW()
            WHERE id = :id
        ", [
            ':name'         => trim($data['name']),
            ':company_name' => !empty($data['company_name']) ? trim($data['company_name']) : null,
            ':email'        => strtolower(trim($data['email'])),
            ':phone'        => !empty($data['phone']) ? trim($data['phone']) : null,
            ':country_code' => $data['country_code'] ?? '+91',
            ':country_id'   => !empty($data['country_id']) ? (int)$data['country_id'] : null,
            ':id'           => $id,
        ]);

        return true;
    }

    // ─── Super Admin: Buyer Management ──────────────────────────────────

    public static function getBuyersDataTable(array $params): array
    {
        $draw   = (int) ($params['draw'] ?? 1);
        $start  = max(0, (int) ($params['start'] ?? 0));
        $length = max(1, min(100, (int) ($params['length'] ?? 10)));
        $search = trim($params['search']['value'] ?? ($params['search_query'] ?? ''));

        $where = ["u.role = 'BUYER'"];
        $bindings = [];

        $totalRecords = (int) (self::fetch("SELECT COUNT(*) AS cnt FROM users u WHERE u.role = 'BUYER'")['cnt'] ?? 0);

        if (!empty($params['account_status']) && $params['account_status'] !== 'ALL') {
            $where[] = "u.status = :account_status";
            $bindings[':account_status'] = $params['account_status'];
        }

        if (!empty($params['verification_status']) && $params['verification_status'] !== 'ALL') {
            if ($params['verification_status'] === 'VERIFIED') {
                $where[] = "u.email_verified_at IS NOT NULL";
            } elseif ($params['verification_status'] === 'UNVERIFIED') {
                $where[] = "u.email_verified_at IS NULL";
            }
        }

        if (!empty($params['country_id']) && $params['country_id'] !== 'ALL') {
            $where[] = "u.country_id = :country_id";
            $bindings[':country_id'] = (int) $params['country_id'];
        }

        if ($search !== '') {
            $where[] = "(
                CAST(u.id AS CHAR) LIKE :s1
                OR u.name LIKE :s2
                OR u.company_name LIKE :s3
                OR u.email LIKE :s4
                OR u.phone LIKE :s5
                OR CONCAT(COALESCE(u.country_code, ''), ' ', COALESCE(u.phone, '')) LIKE :s6
                OR c.name LIKE :s7
                OR u.status LIKE :s8
            )";
            $like = '%' . $search . '%';
            for ($i = 1; $i <= 8; $i++) {
                $bindings[":s{$i}"] = $like;
            }
        }

        $whereSql = implode(' AND ', $where);

        $filteredRecords = (int) (self::fetch("
            SELECT COUNT(*) AS cnt
            FROM users u
            LEFT JOIN countries c ON u.country_id = c.id
            WHERE {$whereSql}
        ", $bindings)['cnt'] ?? 0);

        $columns = [
            0 => 'u.id',
            1 => 'u.name',
            2 => 'u.company_name',
            3 => 'u.email',
            4 => 'u.phone',
            5 => 'c.name',
            6 => 'u.email_verified_at',
            7 => 'u.status',
            8 => 'u.created_at',
        ];
        $orderColIdx = (int) ($params['order'][0]['column'] ?? 8);
        $orderCol    = $columns[$orderColIdx] ?? 'u.created_at';
        $orderDir    = strtoupper($params['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $rows = self::fetchAll("
            SELECT u.id, u.name, u.company_name, u.email, u.phone, u.country_code, u.country_id,
                   u.status, u.email_verified_at, u.last_login_at, u.created_at, u.updated_at,
                   c.name AS country_name,
                   (SELECT COUNT(*) FROM product_inquiries pi WHERE pi.buyer_user_id = u.id OR (pi.enquiry_source != 'CONTACT_US' AND LOWER(pi.buyer_email) = LOWER(u.email))) AS total_enquiries
            FROM users u
            LEFT JOIN countries c ON u.country_id = c.id
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

    public static function getBuyerDetails(int $id): ?array
    {
        return self::fetch("
            SELECT u.id, u.name, u.company_name, u.email, u.phone, u.country_code, u.country_id,
                   u.role, u.status, u.email_verified_at, u.terms_accepted_at, u.terms_version,
                   u.last_login_at, u.created_at, u.updated_at,
                   c.name AS country_name,
                   (SELECT COUNT(*) FROM product_inquiries pi WHERE pi.buyer_user_id = u.id OR (pi.enquiry_source != 'CONTACT_US' AND LOWER(pi.buyer_email) = LOWER(u.email))) AS total_enquiries
            FROM users u
            LEFT JOIN countries c ON u.country_id = c.id
            WHERE u.id = :id AND u.role = 'BUYER'
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function updateBuyerByAdmin(int $id, array $data): array
    {
        $buyer = self::getBuyerDetails($id);
        if (!$buyer) {
            return ['success' => false, 'message' => 'Buyer account not found.'];
        }

        $newEmail = strtolower(trim($data['email'] ?? $buyer['email']));
        if (self::emailExists($newEmail, $id)) {
            return ['success' => false, 'message' => 'This email address is already registered to another account.'];
        }

        $emailChanged = strtolower(trim($buyer['email'])) !== $newEmail;
        $status = $data['status'] ?? $buyer['status'];
        if (!in_array($status, ['ACTIVE', 'INACTIVE', 'PENDING_VERIFICATION'], true)) {
            $status = $buyer['status'];
        }

        if ($emailChanged) {
            $status = 'PENDING_VERIFICATION';
            $emailVerifiedSql = ", email_verified_at = NULL";
        } else {
            $emailVerifiedSql = "";
            if (!empty($data['mark_email_verified']) && empty($buyer['email_verified_at'])) {
                $emailVerifiedSql = ", email_verified_at = NOW()";
            }
        }

        self::execute("
            UPDATE users SET
                name         = :name,
                company_name = :company_name,
                email        = :email,
                phone        = :phone,
                country_code = :country_code,
                country_id   = :country_id,
                status       = :status{$emailVerifiedSql},
                updated_at   = NOW()
            WHERE id = :id AND role = 'BUYER'
        ", [
            ':name'         => trim($data['name']),
            ':company_name' => !empty($data['company_name']) ? trim($data['company_name']) : null,
            ':email'        => $newEmail,
            ':phone'        => !empty($data['phone']) ? trim($data['phone']) : null,
            ':country_code' => !empty($data['country_code']) ? trim($data['country_code']) : ($buyer['country_code'] ?? '+91'),
            ':country_id'   => !empty($data['country_id']) ? (int) $data['country_id'] : null,
            ':status'       => $status,
            ':id'           => $id,
        ]);

        return [
            'success'       => true,
            'email_changed' => $emailChanged,
            'new_status'    => $status,
        ];
    }

    public static function setBuyerStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['ACTIVE', 'INACTIVE', 'PENDING_VERIFICATION'], true)) {
            return false;
        }
        $buyer = self::getBuyerDetails($id);
        if (!$buyer) {
            return false;
        }
        self::execute("
            UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id AND role = 'BUYER'
        ", [
            ':status' => $status,
            ':id'     => $id,
        ]);
        return true;
    }

    public static function getBuyerMetrics(): array
    {
        $row = self::fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'INACTIVE' THEN 1 ELSE 0 END) AS inactive,
                SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) AS verified,
                SUM(CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END) AS unverified
            FROM users
            WHERE role = 'BUYER'
        ");

        return [
            'total'      => (int) ($row['total'] ?? 0),
            'active'     => (int) ($row['active'] ?? 0),
            'inactive'   => (int) ($row['inactive'] ?? 0),
            'verified'   => (int) ($row['verified'] ?? 0),
            'unverified' => (int) ($row['unverified'] ?? 0),
        ];
    }

    public static function getBuyersForExport(array $params = []): array
    {
        $where = ["u.role = 'BUYER'"];
        $bindings = [];

        if (!empty($params['account_status']) && $params['account_status'] !== 'ALL') {
            $where[] = "u.status = :account_status";
            $bindings[':account_status'] = $params['account_status'];
        }
        if (!empty($params['verification_status']) && $params['verification_status'] !== 'ALL') {
            if ($params['verification_status'] === 'VERIFIED') {
                $where[] = "u.email_verified_at IS NOT NULL";
            } elseif ($params['verification_status'] === 'UNVERIFIED') {
                $where[] = "u.email_verified_at IS NULL";
            }
        }
        if (!empty($params['country_id']) && $params['country_id'] !== 'ALL') {
            $where[] = "u.country_id = :country_id";
            $bindings[':country_id'] = (int) $params['country_id'];
        }
        $search = trim($params['search'] ?? '');
        if ($search !== '') {
            $where[] = "(
                CAST(u.id AS CHAR) LIKE :s1
                OR u.name LIKE :s2
                OR u.company_name LIKE :s3
                OR u.email LIKE :s4
                OR u.phone LIKE :s5
                OR c.name LIKE :s6
            )";
            $like = '%' . $search . '%';
            for ($i = 1; $i <= 6; $i++) {
                $bindings[":s{$i}"] = $like;
            }
        }

        $whereSql = implode(' AND ', $where);
        return self::fetchAll("
            SELECT 
                u.id AS 'Buyer ID',
                u.name AS 'Full Name',
                COALESCE(u.company_name, 'N/A') AS 'Company Name',
                u.email AS 'Business Email',
                CONCAT(COALESCE(u.country_code, '+91'), ' ', COALESCE(u.phone, '')) AS 'Mobile Number',
                COALESCE(c.name, 'N/A') AS 'Country',
                CASE WHEN u.email_verified_at IS NOT NULL THEN 'Verified' ELSE 'Unverified' END AS 'Email Verification Status',
                u.status AS 'Account Status',
                u.created_at AS 'Registration Date'
            FROM users u
            LEFT JOIN countries c ON u.country_id = c.id
            WHERE {$whereSql}
            ORDER BY u.created_at DESC
        ", $bindings);
    }

    public static function getAllBuyersList(): array
    {
        return self::fetchAll("
            SELECT id, name, company_name, email
            FROM users
            WHERE role = 'BUYER'
            ORDER BY name ASC
        ");
    }
}


