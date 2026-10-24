<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class AuditLog extends Model
{
    public static function getRecent(int $limit = 15): array
    {
        return self::fetchAll("
            SELECT a.*, u.name AS user_name, u.email AS user_email, u.role AS user_role
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC, a.id DESC 
            LIMIT {$limit}
        ");
    }

    public static function getCounts(): array
    {
        return [
            'total_countries'      => (int) self::fetchColumn("SELECT COUNT(*) FROM countries"),
            'active_countries'     => (int) self::fetchColumn("SELECT COUNT(*) FROM countries WHERE status = 'ACTIVE'"),
            'total_states'         => (int) self::fetchColumn("SELECT COUNT(*) FROM states"),
            'active_states'        => (int) self::fetchColumn("SELECT COUNT(*) FROM states WHERE status = 'ACTIVE'"),
            'total_cities'         => (int) self::fetchColumn("SELECT COUNT(*) FROM cities"),
            'active_cities'        => (int) self::fetchColumn("SELECT COUNT(*) FROM cities WHERE status = 'ACTIVE'"),
            'total_certificates'   => (int) self::fetchColumn("SELECT COUNT(*) FROM product_certificates"),
            'total_tags'           => (int) self::fetchColumn("SELECT COUNT(*) FROM supplier_certification_tags"),
            'total_business_types' => (int) self::fetchColumn("SELECT COUNT(*) FROM business_types"),
            'total_price_units'    => (int) self::fetchColumn("SELECT COUNT(*) FROM price_units"),
            'total_moq_units'      => (int) self::fetchColumn("SELECT COUNT(*) FROM moq_units"),
            'total_suppliers'      => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers"),
            'total_products'       => (int) self::fetchColumn("SELECT COUNT(*) FROM products"),
            'total_audit_logs'     => (int) self::fetchColumn("SELECT COUNT(*) FROM audit_logs"),
        ];
    }

    public static function getFilterOptions(): array
    {
        $actions = self::fetchAll("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL AND action != '' ORDER BY action ASC");
        $entities = self::fetchAll("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL AND entity_type != '' ORDER BY entity_type ASC");
        $results = self::fetchAll("SELECT DISTINCT result FROM audit_logs WHERE result IS NOT NULL AND result != '' ORDER BY result ASC");
        $roles = self::fetchAll("SELECT DISTINCT u.role FROM audit_logs a JOIN users u ON a.user_id = u.id WHERE u.role IS NOT NULL ORDER BY u.role ASC");

        return [
            'actions'  => array_column($actions, 'action'),
            'entities' => array_column($entities, 'entity_type'),
            'results'  => array_column($results, 'result'),
            'roles'    => array_column($roles, 'role'),
        ];
    }

    private static function buildFilterQuery(array $params): array
    {
        $where = ["1=1"];
        $bindings = [];

        // Global Search
        $searchValue = trim((string)($params['search']['value'] ?? $params['search'] ?? ''));
        if ($searchValue !== '') {
            $where[] = "(
                a.record_identifier LIKE :search1 
                OR a.details LIKE :search2 
                OR a.action LIKE :search3 
                OR a.entity_type LIKE :search4 
                OR a.ip_address LIKE :search5 
                OR u.name LIKE :search6 
                OR u.email LIKE :search7
            )";
            $term = '%' . $searchValue . '%';
            $bindings[':search1'] = $term;
            $bindings[':search2'] = $term;
            $bindings[':search3'] = $term;
            $bindings[':search4'] = $term;
            $bindings[':search5'] = $term;
            $bindings[':search6'] = $term;
            $bindings[':search7'] = $term;
        }

        // Date Range
        $dateFrom = trim((string)($params['date_from'] ?? $params['start_date'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = "a.created_at >= :date_from";
            $bindings[':date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($params['date_to'] ?? $params['end_date'] ?? ''));
        if ($dateTo !== '') {
            $where[] = "a.created_at <= :date_to";
            $bindings[':date_to'] = $dateTo . ' 23:59:59';
        }

        // Actor (User ID or search)
        $actor = trim((string)($params['actor'] ?? ''));
        if ($actor !== '') {
            if (ctype_digit($actor)) {
                $where[] = "a.user_id = :actor_id";
                $bindings[':actor_id'] = (int)$actor;
            } else {
                $where[] = "(u.name LIKE :actor1 OR u.email LIKE :actor2)";
                $bindings[':actor1'] = '%' . $actor . '%';
                $bindings[':actor2'] = '%' . $actor . '%';
            }
        }

        // Role
        $role = trim((string)($params['role'] ?? ''));
        if ($role !== '') {
            if (strtoupper($role) === 'SYSTEM') {
                $where[] = "(a.user_id IS NULL OR u.role IS NULL)";
            } else {
                $where[] = "u.role = :user_role";
                $bindings[':user_role'] = strtoupper($role);
            }
        }

        // Action
        $action = trim((string)($params['action_filter'] ?? $params['action'] ?? ''));
        if ($action !== '') {
            $where[] = "a.action = :action_filter";
            $bindings[':action_filter'] = strtoupper($action);
        }

        // Entity
        $entity = trim((string)($params['entity_filter'] ?? $params['entity'] ?? ''));
        if ($entity !== '') {
            $where[] = "a.entity_type = :entity_filter";
            $bindings[':entity_filter'] = $entity;
        }

        // Result
        $result = trim((string)($params['result_filter'] ?? $params['result'] ?? ''));
        if ($result !== '') {
            $where[] = "a.result = :result_filter";
            $bindings[':result_filter'] = strtoupper($result);
        }

        $whereClause = implode(' AND ', $where);
        return [$whereClause, $bindings];
    }

    public static function getDataTable(array $params): array
    {
        $draw = (int)($params['draw'] ?? 1);
        $start = max(0, (int)($params['start'] ?? 0));
        $length = min(100, max(10, (int)($params['length'] ?? 25)));

        // Column mapping for order
        $columns = [
            0 => 'a.created_at',
            1 => 'u.name',
            2 => 'u.role',
            3 => 'a.action',
            4 => 'a.entity_type',
            5 => 'a.record_identifier',
            6 => 'a.details',
            7 => 'a.ip_address',
            8 => 'a.result',
        ];

        $orderColIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtolower((string)($params['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy = $columns[$orderColIdx] ?? 'a.created_at';

        // Total records
        $recordsTotal = (int) self::fetchColumn("SELECT COUNT(*) FROM audit_logs");

        // Filtered records
        [$whereClause, $bindings] = self::buildFilterQuery($params);

        $filteredCountSql = "
            SELECT COUNT(*) 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE {$whereClause}
        ";
        $recordsFiltered = (int) self::fetchColumn($filteredCountSql, $bindings);

        // Fetch data
        $dataSql = "
            SELECT 
                a.id,
                a.user_id,
                a.action,
                a.entity_type,
                a.entity_id,
                a.record_identifier,
                a.affected_counts,
                a.details,
                a.result,
                a.ip_address,
                a.user_agent,
                a.created_at,
                u.name AS user_name,
                u.email AS user_email,
                u.role AS user_role
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE {$whereClause}
            ORDER BY {$orderBy} {$orderDir}, a.id DESC
            LIMIT {$length} OFFSET {$start}
        ";

        $rows = self::fetchAll($dataSql, $bindings);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ];
    }

    public static function getExportData(array $params): array
    {
        [$whereClause, $bindings] = self::buildFilterQuery($params);

        $dataSql = "
            SELECT 
                a.id,
                a.created_at,
                a.user_id,
                u.name AS user_name,
                u.email AS user_email,
                u.role AS user_role,
                a.action,
                a.entity_type,
                a.entity_id,
                a.record_identifier,
                a.details,
                a.result,
                a.ip_address
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE {$whereClause}
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT 5000
        ";

        return self::fetchAll($dataSql, $bindings);
    }
}
