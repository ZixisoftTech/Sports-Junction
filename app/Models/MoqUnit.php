<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class MoqUnit extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("SELECT * FROM moq_units WHERE id = :id LIMIT 1", [':id' => $id]);
    }

    public static function allActive(): array
    {
        return self::fetchAll("SELECT id, name, symbol FROM moq_units WHERE status = 'ACTIVE' ORDER BY name ASC");
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO moq_units (name, symbol, description, status, created_at, updated_at)
            VALUES (:name, :symbol, :description, :status, NOW(), NOW())
        ", [
            ':name'        => trim($data['name']),
            ':symbol'      => !empty($data['symbol']) ? trim($data['symbol']) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':status'      => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'MoqUnit', $id, trim($data['name']), null, 'Created MOQ unit');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        self::execute("
            UPDATE moq_units 
            SET name = :name, symbol = :symbol, description = :description, status = :status, updated_at = NOW()
            WHERE id = :id
        ", [
            ':name'        => trim($data['name']),
            ':symbol'      => !empty($data['symbol']) ? trim($data['symbol']) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':status'      => $data['status'] ?? 'ACTIVE',
            ':id'          => $id,
        ]);

        AuditLogger::log('UPDATE', 'MoqUnit', $id, trim($data['name']), null, 'Updated MOQ unit details');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $unit = self::findById($id);
        if (!$unit) {
            return false;
        }

        self::execute("UPDATE moq_units SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'MoqUnit', $id, $unit['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function getDependencies(int $id): array
    {
        $products = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE moq_unit_id = :id", [':id' => $id]);
        $activeProducts = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE moq_unit_id = :id AND status = 'ACTIVE'", [':id' => $id]);

        return [
            'total'             => $products,
            'products'          => $products,
            'active_products'   => $activeProducts,
            'has_active'        => $activeProducts > 0,
            'has_protected'     => $products > 0,
            'can_cascade'       => false,
            'can_direct_delete' => ($products === 0),
        ];
    }

    public static function hardDelete(int $id): array
    {
        $unit = self::findById($id);
        if (!$unit) {
            return ['success' => false, 'message' => 'MOQ Unit not found.'];
        }

        $deps = self::getDependencies($id);
        if ($deps['has_protected']) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$unit['name']}' because {$deps['products']} products specify this MOQ unit. Please update those products before deletion."
            ];
        }

        self::beginTransaction();
        try {
            self::execute("DELETE FROM moq_units WHERE id = :id", [':id' => $id]);
            self::commit();

            AuditLogger::log('DELETE', 'MoqUnit', $id, $unit['name'], ['moq_units' => 1], 'Permanently deleted MOQ unit');

            return [
                'success'        => true,
                'affectedCounts' => ['moq_units' => 1],
                'message'        => "MOQ Unit '{$unit['name']}' permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete MOQ unit: ' . $e->getMessage()];
        }
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));

        $columns = ['id', 'name', 'symbol', 'description', 'status', 'created_at'];
        $orderColIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columns[$orderColIdx] ?? 'id';

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(name LIKE :s1 OR symbol LIKE :s2 OR description LIKE :s3)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "status = :status";
            $bindings[':status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM moq_units");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM moq_units {$whereClause}", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT id, name, symbol, description, status, created_at 
                FROM moq_units 
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
