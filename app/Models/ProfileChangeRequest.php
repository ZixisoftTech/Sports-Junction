<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class ProfileChangeRequest extends Model
{
    public static function createRequest(int $userId, ?int $supplierId, string $role, array $currentValues, array $requestedChanges): int
    {
        // Cancel or archive any older pending requests from this user to maintain single active revision
        self::execute("
            UPDATE profile_change_requests
            SET status = 'REJECTED', rejection_reason = 'Superseded by a newer profile update request.', updated_at = NOW()
            WHERE user_id = :user_id AND status = 'PENDING'
        ", [':user_id' => $userId]);

        self::execute("
            INSERT INTO profile_change_requests (
                user_id, supplier_id, role, current_values, requested_changes, status, created_at, updated_at
            ) VALUES (
                :user_id, :supplier_id, :role, :current_values, :requested_changes, 'PENDING', NOW(), NOW()
            )
        ", [
            ':user_id'           => $userId,
            ':supplier_id'       => $supplierId,
            ':role'              => strtoupper($role),
            ':current_values'    => json_encode($currentValues, JSON_UNESCAPED_UNICODE),
            ':requested_changes' => json_encode($requestedChanges, JSON_UNESCAPED_UNICODE),
        ]);

        $id = (int) self::lastInsertId();

        AuditLogger::log(
            'PROFILE_CHANGE_SUBMIT',
            'User',
            $userId,
            "User #{$userId} ({$role})",
            null,
            "Submitted profile update request #{$id} awaiting Super Admin approval."
        );

        return $id;
    }

    public static function createApproved(int $userId, ?int $supplierId, string $role, array $currentValues, array $requestedChanges): int
    {
        self::execute("
            INSERT INTO profile_change_requests (
                user_id, supplier_id, role, current_values, requested_changes, status, created_at, updated_at
            ) VALUES (
                :user_id, :supplier_id, :role, :current_values, :requested_changes, 'APPROVED', NOW(), NOW()
            )
        ", [
            ':user_id'           => $userId,
            ':supplier_id'       => $supplierId,
            ':role'              => strtoupper($role),
            ':current_values'    => json_encode($currentValues, JSON_UNESCAPED_UNICODE),
            ':requested_changes' => json_encode($requestedChanges, JSON_UNESCAPED_UNICODE),
        ]);

        $id = (int) self::lastInsertId();

        AuditLogger::log(
            'PROFILE_UPDATE',
            'User',
            $userId,
            "User #{$userId} ({$role})",
            null,
            "Direct profile update applied successfully."
        );

        return $id;
    }

    public static function findById(int $id): ?array
    {
        $row = self::fetch("
            SELECT pcr.*, u.name as user_name, u.email as user_email, sp.company_name as supplier_company
            FROM profile_change_requests pcr
            LEFT JOIN users u ON pcr.user_id = u.id
            LEFT JOIN suppliers sp ON pcr.supplier_id = sp.id
            WHERE pcr.id = :id
            LIMIT 1
        ", [':id' => $id]);

        if ($row) {
            $row['decoded_current'] = json_decode($row['current_values'] ?? '{}', true) ?: [];
            $row['decoded_requested'] = json_decode($row['requested_changes'] ?? '{}', true) ?: [];
        }

        return $row;
    }

    public static function getPendingForUser(int $userId): ?array
    {
        $row = self::fetch("
            SELECT * FROM profile_change_requests
            WHERE user_id = :user_id AND status = 'PENDING'
            ORDER BY id DESC LIMIT 1
        ", [':user_id' => $userId]);

        if ($row) {
            $row['decoded_current'] = json_decode($row['current_values'] ?? '{}', true) ?: [];
            $row['decoded_requested'] = json_decode($row['requested_changes'] ?? '{}', true) ?: [];
        }

        return $row;
    }

    public static function getLatestForUser(int $userId): ?array
    {
        $row = self::fetch("
            SELECT * FROM profile_change_requests
            WHERE user_id = :user_id
            ORDER BY id DESC LIMIT 1
        ", [':user_id' => $userId]);

        if ($row) {
            $row['decoded_current'] = json_decode($row['current_values'] ?? '{}', true) ?: [];
            $row['decoded_requested'] = json_decode($row['requested_changes'] ?? '{}', true) ?: [];
        }

        return $row;
    }

    public static function getPendingRequests(?string $role = null): array
    {
        $where = "WHERE pcr.status = 'PENDING'";
        $params = [];
        if (!empty($role)) {
            $where .= " AND pcr.role = :role";
            $params[':role'] = strtoupper($role);
        }

        $rows = self::fetchAll("
            SELECT pcr.*, u.name as user_name, u.email as user_email, u.company_name as user_company,
                   sp.company_name as supplier_company
            FROM profile_change_requests pcr
            LEFT JOIN users u ON pcr.user_id = u.id
            LEFT JOIN suppliers sp ON pcr.supplier_id = sp.id
            {$where}
            ORDER BY pcr.id DESC
        ", $params);

        foreach ($rows as &$r) {
            $r['decoded_current'] = json_decode($r['current_values'] ?? '{}', true) ?: [];
            $r['decoded_requested'] = json_decode($r['requested_changes'] ?? '{}', true) ?: [];
        }

        return $rows;
    }

    public static function approve(int $id, int $adminId): bool
    {
        $req = self::findById($id);
        if (!$req || $req['status'] !== 'PENDING') {
            return false;
        }

        $changes = $req['decoded_requested'];
        $role = $req['role'];
        $userId = (int) $req['user_id'];
        $supplierId = !empty($req['supplier_id']) ? (int) $req['supplier_id'] : null;

        self::beginTransaction();
        try {
            if ($role === 'BUYER') {
                // Update Buyer user record
                $updateFields = [
                    'name'         => $changes['name'] ?? null,
                    'company_name' => $changes['company_name'] ?? null,
                    'phone'        => $changes['phone'] ?? null,
                    'country_code' => $changes['country_code'] ?? '+91',
                    'country_id'   => !empty($changes['country_id']) ? (int) $changes['country_id'] : null,
                    'state'        => $changes['state'] ?? null,
                    'city'         => $changes['city'] ?? null,
                ];

                self::execute("
                    UPDATE users SET
                        name         = COALESCE(:name, name),
                        company_name = COALESCE(:company_name, company_name),
                        phone        = COALESCE(:phone, phone),
                        country_code = COALESCE(:country_code, country_code),
                        country_id   = COALESCE(:country_id, country_id),
                        state        = COALESCE(:state, state),
                        city         = COALESCE(:city, city),
                        updated_at   = NOW()
                    WHERE id = :id AND role = 'BUYER'
                ", array_merge($updateFields, [':id' => $userId]));
            } elseif ($role === 'SUPPLIER' && $supplierId) {
                // Update Supplier profile and associated user
                Supplier::updateProfile($supplierId, array_merge($changes, [
                    'id' => $supplierId
                ]));
            }

            // Mark request approved
            self::execute("
                UPDATE profile_change_requests
                SET status = 'APPROVED', reviewed_by = :admin_id, reviewed_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ", [
                ':admin_id' => $adminId,
                ':id'       => $id,
            ]);

            self::commit();

            AuditLogger::log(
                'APPROVE_PROFILE_CHANGE',
                'ProfileChangeRequest',
                $id,
                "Request #{$id} ({$role})",
                $req['decoded_current'],
                "Super Admin approved profile change request #{$id}"
            );

            return true;
        } catch (\Throwable $e) {
            self::rollBack();
            error_log("Failed to approve profile change request #{$id}: " . $e->getMessage());
            return false;
        }
    }

    public static function reject(int $id, int $adminId, string $reason): bool
    {
        $req = self::findById($id);
        if (!$req || $req['status'] !== 'PENDING') {
            return false;
        }

        self::execute("
            UPDATE profile_change_requests
            SET status = 'REJECTED', rejection_reason = :reason, reviewed_by = :admin_id, reviewed_at = NOW(), updated_at = NOW()
            WHERE id = :id
        ", [
            ':reason'   => trim($reason),
            ':admin_id' => $adminId,
            ':id'       => $id,
        ]);

        AuditLogger::log(
            'REJECT_PROFILE_CHANGE',
            'ProfileChangeRequest',
            $id,
            "Request #{$id} ({$req['role']})",
            ['reason' => $reason],
            "Super Admin rejected profile change request #{$id}"
        );

        return true;
    }
}
