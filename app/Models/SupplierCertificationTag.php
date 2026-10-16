<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class SupplierCertificationTag extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("SELECT * FROM supplier_certification_tags WHERE id = :id LIMIT 1", [':id' => $id]);
    }

    public static function all(): array
    {
        return self::fetchAll("SELECT * FROM supplier_certification_tags ORDER BY name ASC");
    }

    public static function allActive(): array
    {
        return self::fetchAll("SELECT * FROM supplier_certification_tags WHERE status = 'ACTIVE' ORDER BY name ASC");
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO supplier_certification_tags (name, code, description, badge_color, icon, status, created_at, updated_at)
            VALUES (:name, :code, :description, :badge_color, :icon, :status, NOW(), NOW())
        ", [
            ':name'        => trim($data['name']),
            ':code'        => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':badge_color' => !empty($data['badge_color']) ? trim($data['badge_color']) : 'teal',
            ':icon'        => !empty($data['icon']) ? trim($data['icon']) : 'verified',
            ':status'      => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'SupplierCertificationTag', $id, trim($data['name']), null, 'Created supplier certification tag');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        self::execute("
            UPDATE supplier_certification_tags 
            SET name = :name, code = :code, description = :description, 
                badge_color = :badge_color, icon = :icon, status = :status, updated_at = NOW()
            WHERE id = :id
        ", [
            ':name'        => trim($data['name']),
            ':code'        => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':badge_color' => !empty($data['badge_color']) ? trim($data['badge_color']) : ($existing['badge_color'] ?? 'teal'),
            ':icon'        => !empty($data['icon']) ? trim($data['icon']) : ($existing['icon'] ?? 'verified'),
            ':status'      => $data['status'] ?? 'ACTIVE',
            ':id'          => $id,
        ]);

        AuditLogger::log('UPDATE', 'SupplierCertificationTag', $id, trim($data['name']), null, 'Updated supplier certification tag');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $tag = self::findById($id);
        if (!$tag) {
            return false;
        }

        self::execute("UPDATE supplier_certification_tags SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'SupplierCertificationTag', $id, $tag['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function delete(int $id): bool
    {
        $tag = self::findById($id);
        if (!$tag) {
            return false;
        }

        self::execute("DELETE FROM supplier_tag_assignments WHERE tag_id = :id", [':id' => $id]);
        self::execute("DELETE FROM supplier_certification_tags WHERE id = :id", [':id' => $id]);

        AuditLogger::log('DELETE', 'SupplierCertificationTag', $id, $tag['name'], null, 'Deleted supplier certification tag');
        return true;
    }

    /**
     * Get active tags assigned to a supplier
     */
    public static function getForSupplier(int $supplierId): array
    {
        return self::fetchAll("
            SELECT sct.*
            FROM supplier_tag_assignments sta
            JOIN supplier_certification_tags sct ON sta.tag_id = sct.id
            WHERE sta.supplier_id = :supplier_id AND sct.status = 'ACTIVE'
            ORDER BY sct.name ASC
        ", [':supplier_id' => $supplierId]);
    }

    /**
     * Get assigned tag IDs for a supplier
     */
    public static function getAssignedIds(int $supplierId): array
    {
        $rows = self::fetchAll("
            SELECT tag_id FROM supplier_tag_assignments WHERE supplier_id = :sid
        ", [':sid' => $supplierId]);
        return array_map(fn($r) => (int)$r['tag_id'], $rows);
    }

    /**
     * Sync assigned tags for a supplier
     */
    public static function syncForSupplier(int $supplierId, array $tagIds): void
    {
        self::execute("DELETE FROM supplier_tag_assignments WHERE supplier_id = :sid", [':sid' => $supplierId]);
        if (empty($tagIds)) {
            return;
        }

        $stmt = self::getConnection()->prepare("
            INSERT IGNORE INTO supplier_tag_assignments (supplier_id, tag_id, created_at)
            VALUES (?, ?, NOW())
        ");
        foreach ($tagIds as $tId) {
            $tId = (int)$tId;
            if ($tId > 0) {
                $stmt->execute([$supplierId, $tId]);
            }
        }
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(name LIKE :s1 OR code LIKE :s2 OR description LIKE :s3)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "status = :status";
            $bindings[':status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM supplier_certification_tags");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM supplier_certification_tags {$whereClause}", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT sct.*, 
                (SELECT COUNT(*) FROM supplier_tag_assignments sta WHERE sta.tag_id = sct.id) as assigned_suppliers_count
                FROM supplier_certification_tags sct
                {$whereClause} 
                ORDER BY sct.id DESC 
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
