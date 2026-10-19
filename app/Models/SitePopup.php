<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;
use PDO;

class SitePopup extends Model
{
    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `site_popups` (
                  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  `title` VARCHAR(255) NOT NULL,
                  `image` VARCHAR(255) NOT NULL,
                  `target_url` VARCHAR(255) NULL DEFAULT '',
                  `delay_seconds` INT UNSIGNED NOT NULL DEFAULT 2,
                  `display_order` INT NOT NULL DEFAULT 0,
                  `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
                  `start_date` DATE NULL,
                  `end_date` DATE NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  INDEX idx_site_popups_status (`status`),
                  INDEX idx_site_popups_order (`display_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Throwable $e) {
            @error_log("SitePopup::ensureSchema error: " . $e->getMessage());
        }
    }

    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        return self::fetch("SELECT * FROM `site_popups` WHERE `id` = :id LIMIT 1", [':id' => $id]);
    }

    public static function getActivePopup(): ?array
    {
        self::ensureSchema();
        return self::fetch("
            SELECT * FROM `site_popups`
            WHERE `status` = 'ACTIVE'
              AND (`start_date` IS NULL OR `start_date` <= CURDATE())
              AND (`end_date` IS NULL OR `end_date` >= CURDATE())
            ORDER BY `display_order` ASC, `id` ASC
            LIMIT 1
        ");
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $title = trim((string)($data['title'] ?? ''));
        $image = trim((string)($data['image'] ?? ''));
        $targetUrl = !empty($data['target_url']) ? trim((string)$data['target_url']) : '';
        $delaySeconds = (int)($data['delay_seconds'] ?? 2);
        if ($delaySeconds < 0) $delaySeconds = 0;
        $displayOrder = (int)($data['display_order'] ?? 0);
        $status = in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : 'ACTIVE';
        $startDate = !empty($data['start_date']) ? trim((string)$data['start_date']) : null;
        $endDate = !empty($data['end_date']) ? trim((string)$data['end_date']) : null;

        self::execute("
            INSERT INTO `site_popups` 
            (`title`, `image`, `target_url`, `delay_seconds`, `display_order`, `status`, `start_date`, `end_date`, `created_at`, `updated_at`)
            VALUES 
            (:title, :image, :target_url, :delay_seconds, :display_order, :status, :start_date, :end_date, NOW(), NOW())
        ", [
            ':title'        => $title,
            ':image'        => $image,
            ':target_url'   => $targetUrl,
            ':delay_seconds'=> $delaySeconds,
            ':display_order'=> $displayOrder,
            ':status'       => $status,
            ':start_date'   => $startDate,
            ':end_date'     => $endDate,
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'SitePopup', $id, $title, null, 'Created new site popup');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        self::ensureSchema();

        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        $title = !empty($data['title']) ? trim((string)$data['title']) : $existing['title'];
        $image = !empty($data['image']) ? trim((string)$data['image']) : $existing['image'];
        $targetUrl = isset($data['target_url']) ? trim((string)$data['target_url']) : $existing['target_url'];
        $delaySeconds = isset($data['delay_seconds']) ? max(0, (int)$data['delay_seconds']) : (int)$existing['delay_seconds'];
        $displayOrder = isset($data['display_order']) ? (int)$data['display_order'] : (int)$existing['display_order'];
        $status = in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : $existing['status'];
        $startDate = array_key_exists('start_date', $data) ? (!empty($data['start_date']) ? trim((string)$data['start_date']) : null) : $existing['start_date'];
        $endDate = array_key_exists('end_date', $data) ? (!empty($data['end_date']) ? trim((string)$data['end_date']) : null) : $existing['end_date'];

        self::execute("
            UPDATE `site_popups`
            SET `title` = :title,
                `image` = :image,
                `target_url` = :target_url,
                `delay_seconds` = :delay_seconds,
                `display_order` = :display_order,
                `status` = :status,
                `start_date` = :start_date,
                `end_date` = :end_date,
                `updated_at` = NOW()
            WHERE `id` = :id
        ", [
            ':title'        => $title,
            ':image'        => $image,
            ':target_url'   => $targetUrl,
            ':delay_seconds'=> $delaySeconds,
            ':display_order'=> $displayOrder,
            ':status'       => $status,
            ':start_date'   => $startDate,
            ':end_date'     => $endDate,
            ':id'           => $id,
        ]);

        AuditLogger::log('UPDATE', 'SitePopup', $id, $title, null, 'Updated site popup');
        return true;
    }

    public static function delete(int $id): bool
    {
        self::ensureSchema();
        $popup = self::findById($id);
        if (!$popup) {
            return false;
        }

        self::execute("DELETE FROM `site_popups` WHERE `id` = :id", [':id' => $id]);
        AuditLogger::log('DELETE', 'SitePopup', $id, $popup['title'], null, 'Deleted site popup');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        self::ensureSchema();
        $popup = self::findById($id);
        if (!$popup) {
            return false;
        }

        self::execute("UPDATE `site_popups` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'SitePopup', $id, $popup['title'], null, "Status changed to {$status}");
        return true;
    }

    public static function toggleStatus(int $id): string
    {
        self::ensureSchema();
        $popup = self::findById($id);
        if (!$popup) {
            throw new \RuntimeException("Site popup #{$id} not found.");
        }
        $newStatus = ($popup['status'] === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        self::setStatus($id, $newStatus);
        return $newStatus;
    }

    public static function getDataTable(array $params): array
    {
        self::ensureSchema();

        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ($params['search'] ?? '')));
        $statusFilter = trim((string)($params['status'] ?? ''));

        // Client columns:
        // 0: id, 1: image, 2: title, 3: target_url, 4: delay_seconds, 5: display_order, 6: status, 7: actions
        $columnMap = [
            0 => 'id',
            1 => 'id',
            2 => 'title',
            3 => 'target_url',
            4 => 'delay_seconds',
            5 => 'display_order',
            6 => 'status',
            7 => 'created_at',
        ];
        $orderColIdx = isset($params['order'][0]['column']) ? (int)$params['order'][0]['column'] : 5;
        $orderDir = strtoupper((string)($params['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderCol = $columnMap[$orderColIdx] ?? 'display_order';
        $orderBy = ($orderCol === 'id') ? "id {$orderDir}" : "{$orderCol} {$orderDir}, id ASC";

        $where = [];
        $bindings = [];

        if ($search !== '') {
            $where[] = "(title LIKE :s1 OR target_url LIKE :s2)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "status = :status";
            $bindings[':status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM `site_popups`");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM `site_popups` {$whereClause}", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT * FROM `site_popups` 
                {$whereClause} 
                ORDER BY {$orderBy} 
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
