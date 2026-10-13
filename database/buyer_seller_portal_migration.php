<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — BUYER PORTAL, SELLER PORTAL & ENQUIRY THREADS
 * Database Migration
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$db = Database::getConnection();

echo "1. Updating product_inquiries table...\n";
$inqCols = $db->query("SHOW COLUMNS FROM product_inquiries")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('buyer_user_id', $inqCols, true)) {
    $db->exec("ALTER TABLE product_inquiries ADD COLUMN buyer_user_id INT UNSIGNED NULL AFTER supplier_id");
    $db->exec("ALTER TABLE product_inquiries ADD INDEX idx_inquiry_buyer (buyer_user_id)");
}

echo "2. Creating buyer_favourites table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS buyer_favourites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_product_fav (user_id, product_id),
    INDEX idx_fav_user (user_id),
    INDEX idx_fav_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "3. Creating buyer_recently_viewed table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS buyer_recently_viewed (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_product_view (user_id, product_id),
    INDEX idx_view_user (user_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "4. Creating inquiry_replies table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS inquiry_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT UNSIGNED NOT NULL,
    sender_user_id INT UNSIGNED NOT NULL,
    sender_role ENUM('BUYER', 'SUPPLIER', 'SUPER_ADMIN') NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reply_inquiry (inquiry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "Migration complete!\n";
