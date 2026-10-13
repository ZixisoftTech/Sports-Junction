<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — AUTHENTICATION, SUPPLIER APPROVAL & PRODUCT APPROVAL
 * Database Migration
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$db = Database::getConnection();

echo "1. Checking & updating users table...\n";
// Add status ENUM with PENDING_VERIFICATION if not present, and additional fields
$userCols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

// Modify status enum in users
$db->exec("ALTER TABLE users MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE', 'PENDING_VERIFICATION') NOT NULL DEFAULT 'ACTIVE'");
$db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('SUPER_ADMIN', 'ADMIN', 'SUPPLIER', 'BUYER') NOT NULL DEFAULT 'BUYER'");

if (!in_array('company_name', $userCols, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN company_name VARCHAR(150) NULL AFTER name");
}
if (!in_array('country_code', $userCols, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN country_code VARCHAR(10) NULL DEFAULT '+91' AFTER phone");
}
if (!in_array('country_id', $userCols, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN country_id INT UNSIGNED NULL AFTER country_code");
}
if (!in_array('email_verified_at', $userCols, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email");
}
if (!in_array('terms_accepted_at', $userCols, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN terms_accepted_at DATETIME NULL AFTER status");
}
if (!in_array('terms_version', $userCols, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN terms_version VARCHAR(20) NULL DEFAULT 'v1.0' AFTER terms_accepted_at");
}

echo "2. Creating email_otps table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS email_otps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL,
    otp_code VARCHAR(255) NOT NULL,
    purpose ENUM('REGISTRATION', 'PASSWORD_RESET', 'SUPPLIER_REGISTRATION') NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 5,
    resend_count INT NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_email_purpose (email, purpose),
    INDEX idx_otp_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "3. Updating suppliers table...\n";
$supCols = $db->query("SHOW COLUMNS FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('user_id', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN user_id INT UNSIGNED NULL AFTER id");
    $db->exec("ALTER TABLE suppliers ADD INDEX idx_supplier_user (user_id)");
}
if (!in_array('postal_code', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN postal_code VARCHAR(20) NULL AFTER address");
}
if (!in_array('country_code', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN country_code VARCHAR(10) NULL DEFAULT '+91' AFTER phone");
}
if (!in_array('approval_status', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN approval_status ENUM('PENDING_VERIFICATION', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'SUSPENDED') NOT NULL DEFAULT 'APPROVED' AFTER status");
    $db->exec("ALTER TABLE suppliers ADD INDEX idx_supplier_approval (approval_status)");
}
if (!in_array('rejection_reason', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN rejection_reason TEXT NULL AFTER approval_status");
}
if (!in_array('reviewed_by', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER rejection_reason");
}
if (!in_array('reviewed_at', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by");
}
if (!in_array('terms_accepted_at', $supCols, true)) {
    $db->exec("ALTER TABLE suppliers ADD COLUMN terms_accepted_at DATETIME NULL AFTER reviewed_at");
}

echo "4. Updating products table...\n";
$prodCols = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('approval_status', $prodCols, true)) {
    $db->exec("ALTER TABLE products ADD COLUMN approval_status ENUM('DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'APPROVED' AFTER status");
    $db->exec("ALTER TABLE products ADD INDEX idx_prod_approval (approval_status)");
}
if (!in_array('rejection_reason', $prodCols, true)) {
    $db->exec("ALTER TABLE products ADD COLUMN rejection_reason TEXT NULL AFTER approval_status");
}
if (!in_array('reviewed_by', $prodCols, true)) {
    $db->exec("ALTER TABLE products ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER rejection_reason");
}
if (!in_array('reviewed_at', $prodCols, true)) {
    $db->exec("ALTER TABLE products ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by");
}
if (!in_array('submitted_by', $prodCols, true)) {
    $db->exec("ALTER TABLE products ADD COLUMN submitted_by INT UNSIGNED NULL AFTER reviewed_at");
}
if (!in_array('pending_changes', $prodCols, true)) {
    $db->exec("ALTER TABLE products ADD COLUMN pending_changes LONGTEXT NULL AFTER submitted_by");
}

echo "5. Synchronizing existing records...\n";
// Ensure super admin has email_verified_at set
$db->exec("UPDATE users SET email_verified_at = NOW(), status = 'ACTIVE' WHERE role = 'SUPER_ADMIN' AND email_verified_at IS NULL");
// Ensure existing seeded suppliers are APPROVED
$db->exec("UPDATE suppliers SET approval_status = 'APPROVED' WHERE approval_status IS NULL OR approval_status = ''");
// Ensure existing seeded products are APPROVED
$db->exec("UPDATE products SET approval_status = 'APPROVED' WHERE approval_status IS NULL OR approval_status = ''");

echo "[✓] Authentication & Approval migration finished successfully!\n";
