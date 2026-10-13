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
}

