<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;

class ProductCertificate extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("SELECT * FROM product_certificates WHERE id = :id LIMIT 1", [':id' => $id]);
    }

    public static function all(): array
    {
        return self::fetchAll("SELECT * FROM product_certificates ORDER BY name ASC");
    }

    public static function allActive(): array
    {
        return self::fetchAll("SELECT * FROM product_certificates WHERE status = 'ACTIVE' ORDER BY name ASC");
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO product_certificates (name, code, issuing_authority, description, icon, status, created_at, updated_at)
            VALUES (:name, :code, :issuing_authority, :description, :icon, :status, NOW(), NOW())
        ", [
            ':name'              => trim($data['name']),
            ':code'              => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            ':issuing_authority' => !empty($data['issuing_authority']) ? trim($data['issuing_authority']) : null,
            ':description'       => !empty($data['description']) ? trim($data['description']) : null,
            ':icon'              => !empty($data['icon']) ? trim($data['icon']) : 'verified',
            ':status'            => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'ProductCertificate', $id, trim($data['name']), null, 'Created product certificate');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        self::execute("
            UPDATE product_certificates 
            SET name = :name, code = :code, issuing_authority = :issuing_authority, description = :description, 
                icon = :icon, status = :status, updated_at = NOW()
            WHERE id = :id
        ", [
            ':name'              => trim($data['name']),
            ':code'              => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            ':issuing_authority' => !empty($data['issuing_authority']) ? trim($data['issuing_authority']) : null,
            ':description'       => !empty($data['description']) ? trim($data['description']) : null,
            ':icon'              => !empty($data['icon']) ? trim($data['icon']) : ($existing['icon'] ?? 'verified'),
            ':status'            => $data['status'] ?? 'ACTIVE',
            ':id'                => $id,
        ]);

        AuditLogger::log('UPDATE', 'ProductCertificate', $id, trim($data['name']), null, 'Updated product certificate');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $cert = self::findById($id);
        if (!$cert) {
            return false;
        }

        self::execute("UPDATE product_certificates SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'ProductCertificate', $id, $cert['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function delete(int $id): bool
    {
        $cert = self::findById($id);
        if (!$cert) {
            return false;
        }

        self::execute("DELETE FROM product_certificate_assignments WHERE certificate_id = :id", [':id' => $id]);
        self::execute("DELETE FROM product_certificates WHERE id = :id", [':id' => $id]);

        AuditLogger::log('DELETE', 'ProductCertificate', $id, $cert['name'], null, 'Deleted product certificate');
        return true;
    }

    /**
     * Get active certificates assigned to a product
     */
    public static function getForProduct(int $productId): array
    {
        return self::fetchAll("
            SELECT pc.*, pca.certificate_number, pca.valid_until
            FROM product_certificate_assignments pca
            JOIN product_certificates pc ON pca.certificate_id = pc.id
            WHERE pca.product_id = :product_id AND pc.status = 'ACTIVE'
            ORDER BY pc.name ASC
        ", [':product_id' => $productId]);
    }

    /**
     * Get assigned certificate IDs for a product
     */
    public static function getAssignedIds(int $productId): array
    {
        $rows = self::fetchAll("
            SELECT certificate_id FROM product_certificate_assignments WHERE product_id = :pid
        ", [':pid' => $productId]);
        return array_map(fn($r) => (int)$r['certificate_id'], $rows);
    }

    /**
     * Sync assigned certificates for a product
     */
    public static function syncForProduct(int $productId, array $certificateIds): void
    {
        self::execute("DELETE FROM product_certificate_assignments WHERE product_id = :pid", [':pid' => $productId]);
        if (empty($certificateIds)) {
            return;
        }

        $stmt = self::getConnection()->prepare("
            INSERT IGNORE INTO product_certificate_assignments (product_id, certificate_id, created_at)
            VALUES (?, ?, NOW())
        ");
        foreach ($certificateIds as $cId) {
            $cId = (int)$cId;
            if ($cId > 0) {
                $stmt->execute([$productId, $cId]);
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
            $where[] = "(name LIKE :s1 OR code LIKE :s2 OR issuing_authority LIKE :s3 OR description LIKE :s4)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
            $bindings[':s4'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "status = :status";
            $bindings[':status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM product_certificates");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM product_certificates {$whereClause}", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT pc.*, 
                (SELECT COUNT(*) FROM product_certificate_assignments pca WHERE pca.certificate_id = pc.id) as assigned_products_count
                FROM product_certificates pc
                {$whereClause} 
                ORDER BY pc.id DESC 
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
