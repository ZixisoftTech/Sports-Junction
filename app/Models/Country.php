<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;
use PDO;

class Country extends Model
{
    public static function findById(int $id): ?array
    {
        return self::fetch("SELECT * FROM countries WHERE id = :id LIMIT 1", [':id' => $id]);
    }
    public static function all(): array
    {
        return self::fetchAll("SELECT id, name, iso_alpha2, iso_alpha3, phone_code, status FROM countries ORDER BY name ASC");
    }

    public static function allActive(): array
    {
        return self::fetchAll("SELECT id, name, iso_alpha2, phone_code FROM countries WHERE status = 'ACTIVE' ORDER BY name ASC");
    }

    public static function create(array $data): int
    {
        self::execute("
            INSERT INTO countries (name, iso_alpha2, iso_alpha3, phone_code, status, created_at, updated_at)
            VALUES (:name, :iso_alpha2, :iso_alpha3, :phone_code, :status, NOW(), NOW())
        ", [
            ':name'        => trim($data['name']),
            ':iso_alpha2'  => strtoupper(trim($data['iso_alpha2'])),
            ':iso_alpha3'  => !empty($data['iso_alpha3']) ? strtoupper(trim($data['iso_alpha3'])) : null,
            ':phone_code'  => !empty($data['phone_code']) ? trim($data['phone_code']) : null,
            ':status'      => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'Country', $id, trim($data['name']), null, 'Created country record');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        self::execute("
            UPDATE countries 
            SET name = :name, iso_alpha2 = :iso_alpha2, iso_alpha3 = :iso_alpha3, phone_code = :phone_code, status = :status, updated_at = NOW()
            WHERE id = :id
        ", [
            ':name'        => trim($data['name']),
            ':iso_alpha2'  => strtoupper(trim($data['iso_alpha2'])),
            ':iso_alpha3'  => !empty($data['iso_alpha3']) ? strtoupper(trim($data['iso_alpha3'])) : null,
            ':phone_code'  => !empty($data['phone_code']) ? trim($data['phone_code']) : null,
            ':status'      => $data['status'] ?? 'ACTIVE',
            ':id'          => $id,
        ]);

        AuditLogger::log('UPDATE', 'Country', $id, trim($data['name']), null, 'Updated country details');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $country = self::findById($id);
        if (!$country) {
            return false;
        }

        self::execute("UPDATE countries SET status = :status, updated_at = NOW() WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'Country', $id, $country['name'], null, "Status changed to {$status}");
        return true;
    }

    public static function setStatusWithCascade(int $id, string $status): array
    {
        $country = self::findById($id);
        if (!$country) {
            return ['success' => false, 'message' => 'Country not found.'];
        }

        self::beginTransaction();
        try {
            self::execute("UPDATE countries SET status = :status, updated_at = NOW() WHERE id = :id", [
                ':status' => $status,
                ':id'     => $id
            ]);

            $stmtState = self::execute("UPDATE states SET status = :status, updated_at = NOW() WHERE country_id = :id", [
                ':status' => $status,
                ':id'     => $id
            ]);
            $statesAffected = $stmtState->rowCount();

            $stmtCity = self::execute("UPDATE cities SET status = :status, updated_at = NOW() WHERE country_id = :id", [
                ':status' => $status,
                ':id'     => $id
            ]);
            $citiesAffected = $stmtCity->rowCount();

            self::commit();

            AuditLogger::log(
                'STATUS_CASCADE',
                'Country',
                $id,
                $country['name'],
                ['states' => $statesAffected, 'cities' => $citiesAffected],
                "Cascade status updated to {$status} ({$statesAffected} states, {$citiesAffected} cities)"
            );

            return [
                'success' => true,
                'affected' => ['states' => $statesAffected, 'cities' => $citiesAffected],
                'message' => "Country '{$country['name']}' and its {$statesAffected} state(s) and {$citiesAffected} city(ies) marked as {$status}."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to cascade status: ' . $e->getMessage()];
        }
    }

    public static function getDependencies(int $id): array
    {
        $states = (int) self::fetchColumn("SELECT COUNT(*) FROM states WHERE country_id = :id", [':id' => $id]);
        $cities = (int) self::fetchColumn("SELECT COUNT(*) FROM cities WHERE country_id = :id", [':id' => $id]);
        $suppliers = (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE country_id = :id", [':id' => $id]);
        $products = (int) self::fetchColumn("SELECT COUNT(*) FROM products WHERE country_id = :id", [':id' => $id]);

        $activeStates = (int) self::fetchColumn("SELECT COUNT(*) FROM states WHERE country_id = :id AND status = 'ACTIVE'", [':id' => $id]);
        $activeCities = (int) self::fetchColumn("SELECT COUNT(*) FROM cities WHERE country_id = :id AND status = 'ACTIVE'", [':id' => $id]);

        $total = $states + $cities + $suppliers + $products;
        $hasProtected = ($suppliers > 0 || $products > 0);

        return [
            'total'               => $total,
            'states'              => $states,
            'cities'              => $cities,
            'suppliers'           => $suppliers,
            'products'            => $products,
            'active_states'       => $activeStates,
            'active_cities'       => $activeCities,
            'has_active'          => ($activeStates > 0 || $activeCities > 0 || $suppliers > 0 || $products > 0),
            'has_protected'       => $hasProtected,
            'can_cascade'         => (!$hasProtected && ($states > 0 || $cities > 0)),
            'can_direct_delete'   => ($total === 0),
        ];
    }

    public static function hardDelete(int $id, bool $cascade = false): array
    {
        $country = self::findById($id);
        if (!$country) {
            return ['success' => false, 'message' => 'Country not found.'];
        }

        $deps = self::getDependencies($id);

        if ($deps['has_protected']) {
            return [
                'success' => false,
                'message' => "Cannot delete '{$country['name']}' because protected business records exist ({$deps['suppliers']} Suppliers, {$deps['products']} Products). Deletion blocked to protect marketplace data integrity."
            ];
        }

        if ($deps['total'] > 0 && !$cascade) {
            return [
                'success'      => false,
                'dependencies' => $deps,
                'message'      => "Dependent records exist ({$deps['states']} states, {$deps['cities']} cities). Explicit cascade confirmation required."
            ];
        }

        self::beginTransaction();
        try {
            $deletedCounts = [
                'cities'    => 0,
                'states'    => 0,
                'countries' => 1
            ];

            if ($cascade) {
                // Delete child cities first
                $stmtCity = self::execute("DELETE FROM cities WHERE country_id = :id", [':id' => $id]);
                $deletedCounts['cities'] = $stmtCity->rowCount();

                // Delete child states
                $stmtState = self::execute("DELETE FROM states WHERE country_id = :id", [':id' => $id]);
                $deletedCounts['states'] = $stmtState->rowCount();
            }

            // Delete country
            self::execute("DELETE FROM countries WHERE id = :id", [':id' => $id]);

            self::commit();

            AuditLogger::log(
                $cascade ? 'CASCADE_DELETE' : 'DELETE',
                'Country',
                $id,
                $country['name'],
                $deletedCounts,
                $cascade ? "Permanently deleted country with {$deletedCounts['states']} states and {$deletedCounts['cities']} cities" : "Permanently deleted country"
            );

            return [
                'success'        => true,
                'affectedCounts' => $deletedCounts,
                'message'        => "Country '{$country['name']}' and its related records permanently deleted."
            ];
        } catch (\Throwable $e) {
            self::rollBack();
            return ['success' => false, 'message' => 'Failed to delete country: ' . $e->getMessage()];
        }
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));

        $columns = ['id', 'name', 'iso_alpha2', 'iso_alpha3', 'phone_code', 'status', 'created_at'];
        $orderColIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columns[$orderColIdx] ?? 'id';

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(name LIKE :s1 OR iso_alpha2 LIKE :s2 OR iso_alpha3 LIKE :s3 OR phone_code LIKE :s4)";
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

        // Total records
        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM countries");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM countries {$whereClause}", $bindings);

        // Fetch page records
        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT id, name, iso_alpha2, iso_alpha3, phone_code, status, created_at 
                FROM countries 
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
