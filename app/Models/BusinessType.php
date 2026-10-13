<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class BusinessType extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("SELECT * FROM business_types WHERE id = :id LIMIT 1", [':id' => $id]);
    }

    public static function allActive(): array
    {
        return self::fetchAll("SELECT id, name FROM business_types WHERE status = 'ACTIVE' ORDER BY name ASC");
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO business_types (name, description, status, created_at, updated_at)
            VALUES (:name, :description, :status, NOW(), NOW())
        ", [
            ':name'        => trim($data['name']),
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':status'      => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'BusinessType', $id, trim($data['name']), null, 'Created business type');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        self::execute("
            UPDATE business_types 
            SET name = :name, description = :description, status = :status, updated_at = NOW()
            WHERE id = :id
        ", [
            ':name'        => trim($data['name']),
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':status'      => $data['status'] ?? 'ACTIVE',
            ':id'          => $id,
        ]);

        AuditLogger::log('UPDATE', 'BusinessType', $id, trim($data['name']), null, 'Updated business type details');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $bt = self::findById($id);
        if (!$bt) {
            return false;
        }

        self::execute("UPDATE business_types SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'BusinessType', $id, $bt['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function getDependencies(int $id): array
    {
        $suppliers = (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE business_type_id = :id", [':id' => $id]);
        $activeSuppliers = (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE business_type_id = :id AND status = 'ACTIVE'", [':id' => $id]);

        return [
            'total'             => $suppliers,
            'suppliers'         => $suppliers,
            'active_suppliers'  => $activeSuppliers,
            'has_active'        => $activeSuppliers > 0,
            'has_protected'     => $suppliers > 0,
            'can_cascade'       => false,
            'can_direct_delete' => ($suppliers === 0),
        ];
    }

    public static function hardDelete(int $id): array
    {
        $bt = self::findById($id);
        if (!$bt) {
            return ['success' => false, 'message' => 'Business Type not found.'];
        }

        $deps = self::getDependencies($id);
        if ($deps['has_protected']) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$bt['name']}' because {$deps['suppliers']} supplier/vendor accounts are associated with it. Please reassign those accounts before deletion."
            ];
        }

        self::beginTransaction();
        try {
            self::execute("DELETE FROM business_types WHERE id = :id", [':id' => $id]);
            self::commit();

            AuditLogger::log('DELETE', 'BusinessType', $id, $bt['name'], ['business_types' => 1], 'Permanently deleted business type');

            return [
                'success'        => true,
                'affectedCounts' => ['business_types' => 1],
                'message'        => "Business Type '{$bt['name']}' permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete business type: ' . $e->getMessage()];
        }
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));

        $columns = ['id', 'name', 'description', 'status', 'created_at'];
        $orderColIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columns[$orderColIdx] ?? 'id';

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(name LIKE :s1 OR description LIKE :s2)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "status = :status";
            $bindings[':status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM business_types");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM business_types {$whereClause}", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT id, name, description, status, created_at 
                FROM business_types 
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
