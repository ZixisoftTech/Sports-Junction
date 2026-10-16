<?php
declare(strict_types=1);

/**
 * Sports Junction – Comprehensive Module Fixes & Enhancements Migration
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::getConnection();
echo "Connected to database successfully.\n";

// Helper to check if column exists
function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return ((int)$stmt->fetchColumn()) > 0;
}

// Helper to check if table exists
function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return ((int)$stmt->fetchColumn()) > 0;
}

// 1. Update `users` table: add state, city
if (!columnExists($pdo, 'users', 'state')) {
    $pdo->exec("ALTER TABLE `users` ADD COLUMN `state` VARCHAR(100) NULL AFTER `country_id`");
    echo "Added `state` column to `users` table.\n";
}
if (!columnExists($pdo, 'users', 'city')) {
    $pdo->exec("ALTER TABLE `users` ADD COLUMN `city` VARCHAR(100) NULL AFTER `state`");
    echo "Added `city` column to `users` table.\n";
}

// 2. Update `suppliers` table: add logo, description, state, city
if (!columnExists($pdo, 'suppliers', 'logo')) {
    $pdo->exec("ALTER TABLE `suppliers` ADD COLUMN `logo` VARCHAR(255) NULL AFTER `company_name`");
    echo "Added `logo` column to `suppliers` table.\n";
}
if (!columnExists($pdo, 'suppliers', 'description')) {
    $pdo->exec("ALTER TABLE `suppliers` ADD COLUMN `description` TEXT NULL AFTER `postal_code`");
    echo "Added `description` column to `suppliers` table.\n";
}
if (!columnExists($pdo, 'suppliers', 'state')) {
    $pdo->exec("ALTER TABLE `suppliers` ADD COLUMN `state` VARCHAR(100) NULL AFTER `country_id`");
    echo "Added `state` column to `suppliers` table.\n";
}
if (!columnExists($pdo, 'suppliers', 'city')) {
    $pdo->exec("ALTER TABLE `suppliers` ADD COLUMN `city` VARCHAR(100) NULL AFTER `state`");
    echo "Added `city` column to `suppliers` table.\n";
}

// Make state_id and city_id nullable in suppliers if they were NOT NULL
try {
    $pdo->exec("ALTER TABLE `suppliers` MODIFY COLUMN `state_id` INT UNSIGNED NULL");
    $pdo->exec("ALTER TABLE `suppliers` MODIFY COLUMN `city_id` INT UNSIGNED NULL");
    echo "Updated `state_id` and `city_id` to nullable in `suppliers` table.\n";
} catch (\Throwable $e) {
    echo "Note on suppliers state_id/city_id: " . $e->getMessage() . "\n";
}

// Populate existing suppliers' state & city from states & cities tables if empty
$pdo->exec("
    UPDATE `suppliers` sup
    LEFT JOIN `states` st ON sup.state_id = st.id
    LEFT JOIN `cities` ci ON sup.city_id = ci.id
    SET sup.state = COALESCE(sup.state, st.name),
        sup.city = COALESCE(sup.city, ci.name)
    WHERE sup.state IS NULL OR sup.city IS NULL
");
echo "Populated existing supplier state and city names.\n";

// 3. Create `product_certificates` table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `product_certificates` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `code` VARCHAR(50) NULL,
        `issuing_authority` VARCHAR(150) NULL,
        `description` TEXT NULL,
        `icon` VARCHAR(255) NULL,
        `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cert_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Checked/created `product_certificates` table.\n";

// 4. Create `product_certificate_assignments` table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `product_certificate_assignments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT UNSIGNED NOT NULL,
        `certificate_id` INT UNSIGNED NOT NULL,
        `certificate_number` VARCHAR(100) NULL,
        `valid_until` DATE NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_prod_cert (`product_id`, `certificate_id`),
        INDEX idx_pca_prod (`product_id`),
        INDEX idx_pca_cert (`certificate_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Checked/created `product_certificate_assignments` table.\n";

// Seed initial product certificates if empty
$certCount = (int) $pdo->query("SELECT COUNT(*) FROM `product_certificates`")->fetchColumn();
if ($certCount === 0) {
    $initialCerts = [
        ['CE Compliance Mark', 'CE', 'European Conformity Body', 'Meets EU safety, health and environmental protection requirements for sporting gear.', 'verified', 'ACTIVE'],
        ['ISO 9001 Product Standard', 'ISO-9001', 'International Organization for Standardization', 'Certified manufacturing standard ensuring consistent athletic equipment quality.', 'verified_user', 'ACTIVE'],
        ['FIFA Quality Pro Standard', 'FIFA-PRO', 'Fédération Internationale de Football Association', 'Highest standard for competition balls with stringent circumference, roundness and rebound tests.', 'sports_soccer', 'ACTIVE'],
        ['BCCI Approved Spec', 'BCCI-STD', 'Board of Control for Cricket in India', 'Matches domestic first-class & international leather ball and willow cricket specs.', 'sports_cricket', 'ACTIVE'],
        ['EN 71 Safety Compliant', 'EN-71', 'European Toy Safety Directives', 'Tested free of toxic heavy metals, phthalates, and choking hazards for junior training gear.', 'health_and_safety', 'ACTIVE'],
        ['RoHS Environmental Certification', 'RoHS', 'Restriction of Hazardous Substances', 'Eco-friendly non-toxic sports electronics, digital stopwatches, and fitness timers.', 'eco', 'ACTIVE']
    ];
    $stmt = $pdo->prepare("INSERT INTO `product_certificates` (`name`, `code`, `issuing_authority`, `description`, `icon`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($initialCerts as $c) {
        $stmt->execute($c);
    }
    echo "Seeded " . count($initialCerts) . " standard product certificates.\n";
}

// 5. Create `supplier_certification_tags` table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `supplier_certification_tags` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `code` VARCHAR(50) NULL,
        `description` TEXT NULL,
        `badge_color` VARCHAR(30) NOT NULL DEFAULT 'teal',
        `icon` VARCHAR(255) NULL,
        `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sct_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Checked/created `supplier_certification_tags` table.\n";

// 6. Create `supplier_tag_assignments` table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `supplier_tag_assignments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `supplier_id` INT UNSIGNED NOT NULL,
        `tag_id` INT UNSIGNED NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_sup_tag (`supplier_id`, `tag_id`),
        INDEX idx_sta_sup (`supplier_id`),
        INDEX idx_sta_tag (`tag_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Checked/created `supplier_tag_assignments` table.\n";

// Seed initial supplier certification tags if empty
$tagCount = (int) $pdo->query("SELECT COUNT(*) FROM `supplier_certification_tags`")->fetchColumn();
if ($tagCount === 0) {
    $initialTags = [
        ['ISO 9001:2015 Certified Factory', 'ISO-9001:2015', 'Verified quality management system covering entire plant floor and raw material intake.', 'teal', 'verified', 'ACTIVE'],
        ['SEDEX SMETA Audited Facility', 'SEDEX-SMETA', 'Sedex Members Ethical Trade Audit for labor rights, health & safety, and business ethics.', 'blue', 'fact_check', 'ACTIVE'],
        ['BSCI Social Compliance Certified', 'BSCI', 'Business Social Compliance Initiative ensuring fair worker wages and safe workshop conditions.', 'indigo', 'groups', 'ACTIVE'],
        ['Government Verified Export House', 'GOVT-EXPORT', 'Recognized Star Export House by Directorate General of Foreign Trade (DGFT), India.', 'emerald', 'workspace_premium', 'ACTIVE'],
        ['Gold Verified Manufacturer', 'GOLD-VERIFIED', 'Top tier factory with verified physical factory inspection, capacity, and zero trade disputes.', 'amber', 'stars', 'ACTIVE'],
        ['OEKO-TEX Standard 100 Facility', 'OEKO-TEX', 'Textile & activewear manufacturing certified free from harmful chemical substances.', 'teal', 'eco', 'ACTIVE']
    ];
    $stmt = $pdo->prepare("INSERT INTO `supplier_certification_tags` (`name`, `code`, `description`, `badge_color`, `icon`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($initialTags as $t) {
        $stmt->execute($t);
    }
    echo "Seeded " . count($initialTags) . " standard supplier certification tags.\n";
}

// 7. Update `products` table: currency, inr_price_backup
if (!columnExists($pdo, 'products', 'currency')) {
    $pdo->exec("ALTER TABLE `products` ADD COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER `price`");
    echo "Added `currency` column to `products` table.\n";
}
if (!columnExists($pdo, 'products', 'original_currency')) {
    $pdo->exec("ALTER TABLE `products` ADD COLUMN `original_currency` VARCHAR(10) NULL AFTER `currency`");
    echo "Added `original_currency` column to `products` table.\n";
}
if (!columnExists($pdo, 'products', 'inr_price_backup')) {
    $pdo->exec("ALTER TABLE `products` ADD COLUMN `inr_price_backup` DECIMAL(12,2) NULL AFTER `original_currency`");
    echo "Added `inr_price_backup` column to `products` table.\n";
}

// Backup existing INR prices if not yet backed up, and normalize to USD
$checkBackup = $pdo->query("SELECT COUNT(*) FROM `products` WHERE `inr_price_backup` IS NOT NULL")->fetchColumn();
if ((int)$checkBackup === 0) {
    // Backup and convert
    $pdo->exec("
        UPDATE `products`
        SET `inr_price_backup` = `min_price`,
            `original_currency` = 'INR',
            `currency` = 'USD',
            `min_price` = CASE 
                WHEN `id` = 1 THEN 45.00
                WHEN `id` = 2 THEN 8.50
                WHEN `id` = 3 THEN 3.50
                WHEN `id` = 4 THEN 150.00
                WHEN `id` = 5 THEN 2.80
                WHEN `id` = 6 THEN 4.50
                WHEN `id` = 7 THEN 17.50
                WHEN `id` = 14 THEN 2.50
                WHEN `id` = 25 THEN 28.80
                WHEN `id` = 32 THEN 32.00
                ELSE ROUND(`min_price` / 84.0, 2)
            END,
            `max_price` = CASE 
                WHEN `id` = 1 THEN 55.00
                WHEN `id` = 2 THEN 10.00
                WHEN `id` = 3 THEN 4.50
                WHEN `id` = 4 THEN 175.00
                WHEN `id` = 5 THEN 3.70
                WHEN `id` = 6 THEN 5.50
                WHEN `id` = 7 THEN 22.00
                WHEN `id` = 14 THEN 3.20
                WHEN `id` = 25 THEN 32.50
                WHEN `id` = 32 THEN 35.00
                ELSE ROUND(`max_price` / 84.0, 2)
            END,
            `price` = CASE 
                WHEN `id` = 1 THEN 55.00
                WHEN `id` = 2 THEN 10.00
                WHEN `id` = 3 THEN 4.50
                WHEN `id` = 4 THEN 175.00
                WHEN `id` = 5 THEN 3.70
                WHEN `id` = 6 THEN 5.50
                WHEN `id` = 7 THEN 22.00
                WHEN `id` = 14 THEN 3.20
                WHEN `id` = 25 THEN 32.50
                WHEN `id` = 32 THEN 35.00
                ELSE ROUND(`price` / 84.0, 2)
            END,
            `sample_price` = CASE
                WHEN `sample_price` IS NOT NULL THEN ROUND(`sample_price` / 84.0, 2)
                ELSE NULL
            END
    ");
    echo "Backed up INR prices to `inr_price_backup` and converted existing products to USD.\n";
}

// 8. Update `cms_pages` table: meta_title, meta_description
if (!columnExists($pdo, 'cms_pages', 'meta_title')) {
    $pdo->exec("ALTER TABLE `cms_pages` ADD COLUMN `meta_title` VARCHAR(255) NULL AFTER `slug`");
    echo "Added `meta_title` column to `cms_pages` table.\n";
}
if (!columnExists($pdo, 'cms_pages', 'meta_description')) {
    $pdo->exec("ALTER TABLE `cms_pages` ADD COLUMN `meta_description` TEXT NULL AFTER `meta_title`");
    echo "Added `meta_description` column to `cms_pages` table.\n";
}

// Assign sample certificates to products and tags to suppliers for testing
$assignCertCount = (int) $pdo->query("SELECT COUNT(*) FROM `product_certificate_assignments`")->fetchColumn();
if ($assignCertCount === 0) {
    // Assign CE to Product 1, FIFA to Product 2, ISO to Product 4
    $pdo->exec("
        INSERT IGNORE INTO `product_certificate_assignments` (`product_id`, `certificate_id`, `certificate_number`, `valid_until`)
        VALUES 
            (1, 1, 'CE-IND-2026-9041', '2028-12-31'),
            (1, 4, 'BCCI-WILLOW-882', '2027-06-30'),
            (2, 3, 'FIFA-PRO-77401-IN', '2028-05-15'),
            (2, 1, 'CE-BALL-2026-119', '2027-11-20'),
            (4, 2, 'ISO-IRON-9001-A4', '2029-01-01');
    ");
    echo "Assigned sample product certificates.\n";
}

$assignTagCount = (int) $pdo->query("SELECT COUNT(*) FROM `supplier_tag_assignments`")->fetchColumn();
if ($assignTagCount === 0) {
    // Assign tags to supplier 1 & 2
    $firstSupplierId = (int)$pdo->query("SELECT id FROM suppliers ORDER BY id ASC LIMIT 1")->fetchColumn();
    if ($firstSupplierId > 0) {
        $pdo->exec("
            INSERT IGNORE INTO `supplier_tag_assignments` (`supplier_id`, `tag_id`)
            VALUES 
                ({$firstSupplierId}, 1),
                ({$firstSupplierId}, 2),
                ({$firstSupplierId}, 4),
                ({$firstSupplierId}, 5);
        ");
        echo "Assigned sample supplier certification tags to Supplier #{$firstSupplierId}.\n";
    }
}

echo "Migration completed successfully!\n";
