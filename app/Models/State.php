<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class State extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("
            SELECT s.*, c.name AS country_name 
            FROM states s 
            JOIN countries c ON s.country_id = c.id 
            WHERE s.id = :id 
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function getByCountry(int $countryId, bool $activeOnly = true): array
    {
        $sql = "SELECT id, name, code FROM states WHERE country_id = :country_id";
        if ($activeOnly) {
            $sql .= " AND status = 'ACTIVE'";
        }
        $sql .= " ORDER BY name ASC";

        return self::fetchAll($sql, [':country_id' => $countryId]);
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO states (country_id, name, code, status, created_at, updated_at)
            VALUES (:country_id, :name, :code, :status, NOW(), NOW())
        ", [
            ':country_id' => (int)$data['country_id'],
            ':name'       => trim($data['name']),
            ':code'       => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            ':status'     => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'State', $id, trim($data['name']), null, 'Created state record');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        self::execute("
            UPDATE states 
            SET country_id = :country_id, name = :name, code = :code, status = :status, updated_at = NOW()
            WHERE id = :id
        ", [
            ':country_id' => (int)$data['country_id'],
            ':name'       => trim($data['name']),
            ':code'       => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            ':status'     => $data['status'] ?? 'ACTIVE',
            ':id'         => $id,
        ]);

        AuditLogger::log('UPDATE', 'State', $id, trim($data['name']), null, 'Updated state details');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $state = self::findById($id);
        if (!$state) {
            return false;
        }

        self::execute("UPDATE states SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'State', $id, $state['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function setStatusWithCascade(int $id, string $status): array
    {
        $state = self::findById($id);
        if (!$state) {
            return ['success' => false, 'message' => 'State not found.'];
        }

        self::beginTransaction();
        try {
            self::execute("UPDATE states SET status = :status, updated_at = NOW() WHERE id = :id", [
                ':status' => $status,
                ':id'     => $id
            ]);

            $stmtCity = self::execute("UPDATE cities SET status = :status, updated_at = NOW() WHERE state_id = :id", [
                ':status' => $status,
                ':id'     => $id
            ]);
            $citiesAffected = $stmtCity->rowCount();

            self::commit();

            AuditLogger::log(
                'STATUS_CASCADE',
                'State',
                $id,
                $state['name'],
                ['cities' => $citiesAffected],
                "Cascade status updated to {$status} ({$citiesAffected} cities)"
            );

            return [
                'success' => true,
                'affected' => ['cities' => $citiesAffected],
                'message' => "State '{$state['name']}' and its {$citiesAffected} city(ies) marked as {$status}."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to cascade status: ' . $e->getMessage()];
        }
    }

    public static function getDependencies(int $id): array
    {
        $cities = (int) self::fetchColumn("SELECT COUNT(*) FROM cities WHERE state_id = :id", [':id' => $id]);
        $suppliers = (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE state_id = :id", [':id' => $id]);
        $products = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE state_id = :id", [':id' => $id]);
        $activeCities = (int) self::fetchColumn("SELECT COUNT(*) FROM cities WHERE state_id = :id AND status = 'ACTIVE'", [':id' => $id]);

        $total = $cities + $suppliers + $products;
        $hasProtected = ($suppliers > 0 || $products > 0);

        return [
            'total'             => $total,
            'cities'            => $cities,
            'suppliers'         => $suppliers,
            'products'          => $products,
            'active_cities'     => $activeCities,
            'has_active'        => ($activeCities > 0 || $suppliers > 0 || $products > 0),
            'has_protected'     => $hasProtected,
            'can_cascade'       => (!$hasProtected && $cities > 0),
            'can_direct_delete' => ($total === 0),
        ];
    }

    public static function hardDelete(int $id, bool $cascade = false): array
    {
        $state = self::findById($id);
        if (!$state) {
            return ['success' => false, 'message' => 'State not found.'];
        }

        $deps = self::getDependencies($id);

        if ($deps['has_protected']) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$state['name']}' because protected business records exist ({$deps['suppliers']} Suppliers, {$deps['products']} Products). Deletion rejected to protect marketplace integrity."
            ];
        }

        if ($deps['total'] > 0 && !$cascade) {
            return [
                'success'      => false,
                'dependencies' => $deps,
                'message'      => "Dependent records exist ({$deps['cities']} cities). Explicit cascade confirmation required."
            ];
        }

        self::beginTransaction();
        try {
            $deletedCounts = [
                'cities' => 0,
                'states' => 1
            ];

            if ($cascade) {
                $stmtCity = self::execute("DELETE FROM cities WHERE state_id = :id", [':id' => $id]);
                $deletedCounts['cities'] = $stmtCity->rowCount();
            }

            self::execute("DELETE FROM states WHERE id = :id", [':id' => $id]);

            self::commit();

            AuditLogger::log(
                $cascade ? 'CASCADE_DELETE' : 'DELETE',
                'State',
                $id,
                $state['name'],
                $deletedCounts,
                $cascade ? "Permanently deleted state with {$deletedCounts['cities']} cities" : "Permanently deleted state"
            );

            return [
                'success'        => true,
                'affectedCounts' => $deletedCounts,
                'message'        => "State '{$state['name']}' and its related records permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete state: ' . $e->getMessage()];
        }
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));
        $countryFilter = (int)($params['country_id'] ?? 0);

        $columns = ['s.id', 's.name', 's.code', 'c.name', 's.status', 's.created_at'];
        $orderColIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columns[$orderColIdx] ?? 's.id';

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(s.name LIKE :s1 OR s.code LIKE :s2 OR c.name LIKE :s3)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "s.status = :status";
            $bindings[':status'] = $statusFilter;
        }

        if ($countryFilter > 0) {
            $where[] = "s.country_id = :country_id";
            $bindings[':country_id'] = $countryFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM states");
        $filteredRecords = (int) self::fetchColumn("
            SELECT COUNT(*) 
            FROM states s 
            JOIN countries c ON s.country_id = c.id 
            {$whereClause}
        ", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT s.id, s.name, s.code, s.country_id, c.name AS country_name, s.status, s.created_at 
                FROM states s 
                JOIN countries c ON s.country_id = c.id 
                {$whereClause} 
                ORDER BY {$orderCol} {$orderDir} 
                {$limitClause}";

        $data = self::fetchAll($sql, $bindings);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ];
    }
}
