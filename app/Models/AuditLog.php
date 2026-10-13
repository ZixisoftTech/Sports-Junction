<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    public static function getRecent(int $limit = 15): array
    {
        return self::fetchAll("
            SELECT a.*, u.name AS user_name, u.email AS user_email 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC 
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
            'total_business_types' => (int) self::fetchColumn("SELECT COUNT(*) FROM business_types"),
            'total_price_units'    => (int) self::fetchColumn("SELECT COUNT(*) FROM price_units"),
            'total_moq_units'      => (int) self::fetchColumn("SELECT COUNT(*) FROM moq_units"),
            'total_suppliers'      => (int) self::fetchColumn("SELECT COUNT(*) FROM suppliers"),
            'total_products'       => (int) self::fetchColumn("SELECT COUNT(*) FROM products"),
        ];
    }
}
