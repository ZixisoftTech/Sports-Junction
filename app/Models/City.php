<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class City extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("
            SELECT ci.*, s.name AS state_name, c.name AS country_name 
            FROM cities ci 
            JOIN states s ON ci.state_id = s.id 
            JOIN countries c ON ci.country_id = c.id 
            WHERE ci.id = :id 
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO cities (country_id, state_id, name, status, created_at, updated_at)
            VALUES (:country_id, :state_id, :name, :status, NOW(), NOW())
        ", [
            ':country_id' => (int)$data['country_id'],
            ':state_id'   => (int)$data['state_id'],
            ':name'       => trim($data['name']),
            ':status'     => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'City', $id, trim($data['name']), null, 'Created city record');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        self::execute("
            UPDATE cities 
            SET country_id = :country_id, state_id = :state_id, name = :name, status = :status, updated_at = NOW()
            WHERE id = :id
        ", [
            ':country_id' => (int)$data['country_id'],
            ':state_id'   => (int)$data['state_id'],
            ':name'       => trim($data['name']),
            ':status'     => $data['status'] ?? 'ACTIVE',
            ':id'         => $id,
        ]);

        AuditLogger::log('UPDATE', 'City', $id, trim($data['name']), null, 'Updated city details');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $city = self::findById($id);
        if (!$city) {
            return false;
        }

        self::execute("UPDATE cities SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'City', $id, $city['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function getDependencies(int $id): array
    {
        $suppliers = (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE city_id = :id", [':id' => $id]);
        $products = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE city_id = :id", [':id' => $id]);

        $total = $suppliers + $products;
        $hasProtected = ($suppliers > 0 || $products > 0);

        return [
            'total'             => $total,
            'suppliers'         => $suppliers,
            'products'          => $products,
            'has_active'        => $total > 0,
            'has_protected'     => $hasProtected,
            'can_cascade'       => false, // Cities do not cascade-delete business accounts!
            'can_direct_delete' => ($total === 0),
        ];
    }

    public static function hardDelete(int $id): array
    {
        $city = self::findById($id);
        if (!$city) {
            return ['success' => false, 'message' => 'City not found.'];
        }

        $deps = self::getDependencies($id);

        if ($deps['has_protected']) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$city['name']}' because protected business records exist ({$deps['suppliers']} Suppliers, {$deps['products']} Products). Deletion rejected to protect marketplace integrity."
            ];
        }

        self::beginTransaction();
        try {
            self::execute("DELETE FROM cities WHERE id = :id", [':id' => $id]);
            self::commit();

            AuditLogger::log('DELETE', 'City', $id, $city['name'], ['cities' => 1], 'Permanently deleted city');

            return [
                'success'        => true,
                'affectedCounts' => ['cities' => 1],
                'message'        => "City '{$city['name']}' permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete city: ' . $e->getMessage()];
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
        $stateFilter = (int)($params['state_id'] ?? 0);

        $columns = ['ci.id', 'ci.name', 's.name', 'c.name', 'ci.status', 'ci.created_at'];
        $orderColIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columns[$orderColIdx] ?? 'ci.id';

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(ci.name LIKE :s1 OR s.name LIKE :s2 OR c.name LIKE :s3)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "ci.status = :status";
            $bindings[':status'] = $statusFilter;
        }

        if ($countryFilter > 0) {
            $where[] = "ci.country_id = :country_id";
            $bindings[':country_id'] = $countryFilter;
        }

        if ($stateFilter > 0) {
            $where[] = "ci.state_id = :state_id";
            $bindings[':state_id'] = $stateFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM cities");
        $filteredRecords = (int) self::fetchColumn("
            SELECT COUNT(*) 
            FROM cities ci 
            JOIN states s ON ci.state_id = s.id 
            JOIN countries c ON ci.country_id = c.id 
            {$whereClause}
        ", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT ci.id, ci.name, ci.state_id, ci.country_id, s.name AS state_name, c.name AS country_name, ci.status, ci.created_at 
                FROM cities ci 
                JOIN states s ON ci.state_id = s.id 
                JOIN countries c ON ci.country_id = c.id 
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

    public static function getByState(int $stateId, bool $activeOnly = true): array
    {
        $sql = "SELECT id, name, status FROM cities WHERE state_id = :sid";
        if ($activeOnly) {
            $sql .= " AND status = 'ACTIVE'";
        }
        $sql .= " ORDER BY name ASC";
        return self::fetchAll($sql, [':sid' => $stateId]);
    }
}
