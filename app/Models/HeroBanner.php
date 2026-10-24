<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuditLogger;
use PDO;

class HeroBanner extends Model
{
    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `hero_banners` (
                  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  `banner_type` ENUM('WITH_TEXT', 'IMAGE_ONLY') NOT NULL DEFAULT 'WITH_TEXT',
                  `badge_text` VARCHAR(100) NULL DEFAULT 'Factory Direct Sourcing Hub',
                  `title` VARCHAR(255) NULL,
                  `subtitle` TEXT NULL,
                  `image` VARCHAR(255) NOT NULL,
                  `cta_text` VARCHAR(100) NULL DEFAULT 'Source Catalog',
                  `cta_url` VARCHAR(255) NULL DEFAULT '/products',
                  `secondary_cta_text` VARCHAR(100) NULL DEFAULT 'Request Quotation',
                  `secondary_cta_url` VARCHAR(255) NULL DEFAULT 'rfq_modal',
                  `display_order` INT NOT NULL DEFAULT 0,
                  `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  INDEX idx_hero_banners_status (`status`),
                  INDEX idx_hero_banners_order (`display_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Check and add missing columns if upgrading existing table
            $cols = self::fetchAll("DESCRIBE `hero_banners`");
            $colNames = array_column($cols, 'Field');

            if (!in_array('banner_type', $colNames, true)) {
                self::execute("ALTER TABLE `hero_banners` ADD COLUMN `banner_type` ENUM('WITH_TEXT', 'IMAGE_ONLY') NOT NULL DEFAULT 'WITH_TEXT' AFTER `id`");
            }

            // Ensure title column allows NULL for image-only banners
            self::execute("ALTER TABLE `hero_banners` MODIFY COLUMN `title` VARCHAR(255) NULL");

            // Seed initial hero banner if empty
            $count = (int) self::fetchColumn("SELECT COUNT(*) FROM `hero_banners`");
            if ($count === 0) {
                self::execute("
                    INSERT INTO `hero_banners` 
                    (`banner_type`, `badge_text`, `title`, `subtitle`, `image`, `cta_text`, `cta_url`, `secondary_cta_text`, `secondary_cta_url`, `display_order`, `status`, `created_at`, `updated_at`)
                    VALUES 
                    (
                      'WITH_TEXT',
                      'Factory Direct Sourcing Hub',
                      'Direct From Verified Indian Sports & Fitness Manufacturers',
                      'Factory-Direct Wholesale Pricing • Guaranteed MOQs • ISO 9001 & BSCI Audited Production Facilities in Meerut & Jalandhar with Fast Sample Turnaround.',
                      'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=1600&q=80',
                      'Source Catalog',
                      '/products',
                      'Request Quotation',
                      'rfq_modal',
                      1,
                      'ACTIVE',
                      NOW(),
                      NOW()
                    )
                ");
            }
        } catch (\Throwable $e) {
            @error_log("HeroBanner::ensureSchema error: " . $e->getMessage());
        }
    }

    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        return self::fetch("SELECT * FROM `hero_banners` WHERE `id` = :id LIMIT 1", [':id' => $id]);
    }

    public static function allActive(): array
    {
        self::ensureSchema();
        return self::fetchAll("
            SELECT * FROM `hero_banners` 
            WHERE `status` = 'ACTIVE' 
            ORDER BY `display_order` ASC, `id` ASC
        ");
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $bannerType = in_array($data['banner_type'] ?? '', ['WITH_TEXT', 'IMAGE_ONLY'], true) ? $data['banner_type'] : 'WITH_TEXT';

        if ($bannerType === 'IMAGE_ONLY') {
            $badgeText = null;
            $title = !empty($data['title']) ? trim((string)$data['title']) : 'Image Banner';
            $subtitle = null;
            $ctaText = null;
            $ctaUrl = !empty($data['cta_url']) ? trim((string)$data['cta_url']) : null;
            $secCtaText = null;
            $secCtaUrl = null;
        } else {
            $badgeText = !empty($data['badge_text']) ? trim((string)$data['badge_text']) : 'Factory Direct Sourcing Hub';
            $title = trim((string)($data['title'] ?? ''));
            $subtitle = !empty($data['subtitle']) ? trim((string)$data['subtitle']) : null;
            $ctaText = !empty($data['cta_text']) ? trim((string)$data['cta_text']) : 'Source Catalog';
            $ctaUrl = !empty($data['cta_url']) ? trim((string)$data['cta_url']) : '/products';
            $secCtaText = !empty($data['secondary_cta_text']) ? trim((string)$data['secondary_cta_text']) : 'Request Quotation';
            $secCtaUrl = !empty($data['secondary_cta_url']) ? trim((string)$data['secondary_cta_url']) : 'rfq_modal';
        }

        $image = trim((string)($data['image'] ?? ''));
        $displayOrder = (int)($data['display_order'] ?? 0);
        $status = in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : 'ACTIVE';

        self::execute("
            INSERT INTO `hero_banners` 
            (`banner_type`, `badge_text`, `title`, `subtitle`, `image`, `cta_text`, `cta_url`, `secondary_cta_text`, `secondary_cta_url`, `display_order`, `status`, `created_at`, `updated_at`)
            VALUES 
            (:banner_type, :badge_text, :title, :subtitle, :image, :cta_text, :cta_url, :sec_cta_text, :sec_cta_url, :display_order, :status, NOW(), NOW())
        ", [
            ':banner_type'  => $bannerType,
            ':badge_text'   => $badgeText,
            ':title'        => $title,
            ':subtitle'     => $subtitle,
            ':image'        => $image,
            ':cta_text'     => $ctaText,
            ':cta_url'      => $ctaUrl,
            ':sec_cta_text' => $secCtaText,
            ':sec_cta_url'  => $secCtaUrl,
            ':display_order'=> $displayOrder,
            ':status'       => $status,
        ]);

        $id = (int) self::lastInsertId();
        AuditLogger::log('CREATE', 'HeroBanner', $id, $title, null, "Created new hero banner ({$bannerType})");
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        self::ensureSchema();

        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        $bannerType = in_array($data['banner_type'] ?? '', ['WITH_TEXT', 'IMAGE_ONLY'], true) 
            ? $data['banner_type'] 
            : ($existing['banner_type'] ?? 'WITH_TEXT');

        if ($bannerType === 'IMAGE_ONLY') {
            $badgeText = null;
            $title = !empty($data['title']) ? trim((string)$data['title']) : ($existing['title'] ?: 'Image Banner');
            $subtitle = null;
            $ctaText = null;
            $ctaUrl = isset($data['cta_url']) && trim((string)$data['cta_url']) !== '' ? trim((string)$data['cta_url']) : null;
            $secCtaText = null;
            $secCtaUrl = null;
        } else {
            $badgeText = isset($data['badge_text']) ? trim((string)$data['badge_text']) : $existing['badge_text'];
            $title = !empty($data['title']) ? trim((string)$data['title']) : $existing['title'];
            $subtitle = isset($data['subtitle']) ? trim((string)$data['subtitle']) : $existing['subtitle'];
            $ctaText = isset($data['cta_text']) ? trim((string)$data['cta_text']) : $existing['cta_text'];
            $ctaUrl = isset($data['cta_url']) ? trim((string)$data['cta_url']) : $existing['cta_url'];
            $secCtaText = isset($data['secondary_cta_text']) ? trim((string)$data['secondary_cta_text']) : $existing['secondary_cta_text'];
            $secCtaUrl = isset($data['secondary_cta_url']) ? trim((string)$data['secondary_cta_url']) : $existing['secondary_cta_url'];
        }

        $image = !empty($data['image']) ? trim((string)$data['image']) : $existing['image'];
        $displayOrder = isset($data['display_order']) ? (int)$data['display_order'] : (int)$existing['display_order'];
        $status = in_array($data['status'] ?? '', ['ACTIVE', 'INACTIVE'], true) ? $data['status'] : $existing['status'];

        self::execute("
            UPDATE `hero_banners`
            SET `banner_type` = :banner_type,
                `badge_text` = :badge_text,
                `title` = :title,
                `subtitle` = :subtitle,
                `image` = :image,
                `cta_text` = :cta_text,
                `cta_url` = :cta_url,
                `secondary_cta_text` = :sec_cta_text,
                `secondary_cta_url` = :sec_cta_url,
                `display_order` = :display_order,
                `status` = :status,
                `updated_at` = NOW()
            WHERE `id` = :id
        ", [
            ':banner_type'  => $bannerType,
            ':badge_text'   => $badgeText,
            ':title'        => $title,
            ':subtitle'     => $subtitle,
            ':image'        => $image,
            ':cta_text'     => $ctaText,
            ':cta_url'      => $ctaUrl,
            ':sec_cta_text' => $secCtaText,
            ':sec_cta_url'  => $secCtaUrl,
            ':display_order'=> $displayOrder,
            ':status'       => $status,
            ':id'           => $id,
        ]);

        AuditLogger::log('UPDATE', 'HeroBanner', $id, $title, null, "Updated hero banner ({$bannerType})");
        return true;
    }

    public static function delete(int $id): bool
    {
        self::ensureSchema();
        $banner = self::findById($id);
        if (!$banner) {
            return false;
        }

        self::execute("DELETE FROM `hero_banners` WHERE `id` = :id", [':id' => $id]);
        AuditLogger::log('DELETE', 'HeroBanner', $id, $banner['title'], null, 'Deleted hero banner');
        return true;
    }

    public static function setStatus(int $id, string $status): bool
    {
        self::ensureSchema();
        $banner = self::findById($id);
        if (!$banner) {
            return false;
        }

        self::execute("UPDATE `hero_banners` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id", [
            ':status' => $status,
            ':id'     => $id,
        ]);

        AuditLogger::log('STATUS_CHANGE', 'HeroBanner', $id, $banner['title'], null, "Status changed to {$status}");
        return true;
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
        // 0: id, 1: image, 2: title, 3: badge_text, 4: cta_text, 5: display_order, 6: status, 7: actions
        $columnMap = [
            0 => 'id',
            1 => 'id',
            2 => 'title',
            3 => 'badge_text',
            4 => 'cta_text',
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
            $where[] = "(title LIKE :s1 OR subtitle LIKE :s2 OR badge_text LIKE :s3 OR cta_text LIKE :s4 OR cta_url LIKE :s5)";
            $bindings[':s1'] = "%{$search}%";
            $bindings[':s2'] = "%{$search}%";
            $bindings[':s3'] = "%{$search}%";
            $bindings[':s4'] = "%{$search}%";
            $bindings[':s5'] = "%{$search}%";
        }

        $typeFilter = trim((string)($params['banner_type'] ?? ''));
        if ($typeFilter !== '' && in_array($typeFilter, ['WITH_TEXT', 'IMAGE_ONLY'], true)) {
            $where[] = "banner_type = :banner_type";
            $bindings[':banner_type'] = $typeFilter;
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = "status = :status";
            $bindings[':status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM `hero_banners`");
        $filteredRecords = (int) self::fetchColumn("SELECT COUNT(*) FROM `hero_banners` {$whereClause}", $bindings);

        $limitClause = $length > 0 ? "LIMIT {$start}, {$length}" : "";
        $sql = "SELECT * FROM `hero_banners` 
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
