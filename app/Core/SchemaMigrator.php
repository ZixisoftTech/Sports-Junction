<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * =======================================================================
 * SPORTS JUNCTION — NON-DESTRUCTIVE SCHEMA MIGRATOR & PIPELINE
 * =======================================================================
 * Preserves 100% of live production data:
 * - NEVER runs DROP, TRUNCATE, DELETE, or REPLACE.
 * - Only adds missing tables (`CREATE TABLE IF NOT EXISTS`).
 * - Only adds missing columns (`ALTER TABLE ... ADD COLUMN ...`).
 * - Protects live banners, products, categories, suppliers, and inquiries.
 * =======================================================================
 */
class SchemaMigrator
{
    private static bool $hasRun = false;

    /**
     * Run all non-destructive, additive schema checks
     */
    public static function run(bool $force = false): array
    {
        if (self::$hasRun && !$force) {
            return ['status' => 'already_run'];
        }

        self::$hasRun = true;
        $db = Database::getConnection();
        $changes = [];

        try {
            // 1. Ensure Table Schemas via Models (CREATE TABLE IF NOT EXISTS)
            self::safeCall([\App\Models\SupportQuery::class, 'ensureSchema'], $changes, 'SupportQuery');
            self::safeCall([\App\Models\BulkProductEnquiry::class, 'ensureSchema'], $changes, 'BulkProductEnquiry');
            self::safeCall([\App\Models\JobOpening::class, 'ensureSchema'], $changes, 'JobOpening');
            self::safeCall([\App\Models\JobApplication::class, 'ensureSchema'], $changes, 'JobApplication');
            self::safeCall([\App\Models\SiteFeedback::class, 'ensureSchema'], $changes, 'SiteFeedback');
            self::safeCall([\App\Models\SitePopup::class, 'ensureSchema'], $changes, 'SitePopup');
            self::safeCall([\App\Models\HeroBanner::class, 'ensureSchema'], $changes, 'HeroBanner');
            self::safeCall([\App\Models\CmsPage::class, 'ensureSchema'], $changes, 'CmsPage');
            self::safeCall([\App\Models\Notification::class, 'ensureSchema'], $changes, 'Notification');
            self::safeCall([\App\Models\AuditLog::class, 'ensureSchema'], $changes, 'AuditLog');
            self::safeCall([\App\Models\ProductInquiry::class, 'ensureSchema'], $changes, 'ProductInquiry');
            self::safeCall([\App\Models\Category::class, 'ensureSchema'], $changes, 'Category');

            // 2. Additive Column Checks (Only add if column does not exist)
            // Users table
            self::ensureColumn($db, 'users', 'company_name', "VARCHAR(150) NULL AFTER name", $changes);
            self::ensureColumn($db, 'users', 'email_verified_at', "DATETIME NULL AFTER email", $changes);
            self::ensureColumn($db, 'users', 'pending_email', "VARCHAR(191) NULL AFTER email_verified_at", $changes);
            self::ensureColumn($db, 'users', 'vault_password', "TEXT NULL AFTER password", $changes);
            self::ensureColumn($db, 'users', 'terms_accepted_at', "DATETIME NULL AFTER status", $changes);
            self::ensureColumn($db, 'users', 'terms_version', "VARCHAR(20) NULL DEFAULT 'v1.0' AFTER terms_accepted_at", $changes);
            self::ensureColumn($db, 'users', 'country_code', "VARCHAR(10) NULL DEFAULT '+91' AFTER phone", $changes);
            self::ensureColumn($db, 'users', 'country_id', "INT UNSIGNED NULL AFTER country_code", $changes);
            self::ensureColumn($db, 'users', 'state', "VARCHAR(100) NULL AFTER country_id", $changes);
            self::ensureColumn($db, 'users', 'city', "VARCHAR(100) NULL AFTER state", $changes);

            // Suppliers table
            self::ensureColumn($db, 'suppliers', 'state_id', "INT UNSIGNED NULL AFTER state", $changes);
            self::ensureColumn($db, 'suppliers', 'city_id', "INT UNSIGNED NULL AFTER city", $changes);
            self::ensureColumn($db, 'suppliers', 'approval_status', "ENUM('PENDING_VERIFICATION','PENDING_APPROVAL','APPROVED','REJECTED','SUSPENDED') NOT NULL DEFAULT 'APPROVED' AFTER status", $changes);
            self::ensureColumn($db, 'suppliers', 'rejection_reason', "TEXT NULL AFTER approval_status", $changes);
            self::ensureColumn($db, 'suppliers', 'reviewed_by', "INT UNSIGNED NULL AFTER rejection_reason", $changes);
            self::ensureColumn($db, 'suppliers', 'reviewed_at', "DATETIME NULL AFTER reviewed_by", $changes);
            self::ensureColumn($db, 'suppliers', 'terms_accepted_at', "DATETIME NULL AFTER reviewed_at", $changes);
            self::ensureColumn($db, 'suppliers', 'created_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $changes);
            self::ensureColumn($db, 'suppliers', 'updated_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", $changes);

            // Products table
            self::ensureColumn($db, 'products', 'sku', "VARCHAR(100) NULL AFTER slug", $changes);
            self::ensureColumn($db, 'products', 'brand_name', "VARCHAR(150) NULL AFTER sku", $changes);
            self::ensureColumn($db, 'products', 'short_description', "VARCHAR(255) NULL AFTER brand_name", $changes);
            self::ensureColumn($db, 'products', 'price_tier', "VARCHAR(50) NULL AFTER description", $changes);
            self::ensureColumn($db, 'products', 'min_price', "DECIMAL(12,2) NULL AFTER price_tier", $changes);
            self::ensureColumn($db, 'products', 'max_price', "DECIMAL(12,2) NULL AFTER min_price", $changes);
            self::ensureColumn($db, 'products', 'currency', "VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER price", $changes);
            self::ensureColumn($db, 'products', 'original_currency', "VARCHAR(10) NULL DEFAULT 'USD' AFTER currency", $changes);
            self::ensureColumn($db, 'products', 'inr_price_backup', "DECIMAL(12,2) NULL AFTER original_currency", $changes);
            self::ensureColumn($db, 'products', 'sample_available', "TINYINT(1) NOT NULL DEFAULT 0 AFTER moq_unit_id", $changes);
            self::ensureColumn($db, 'products', 'sample_price', "DECIMAL(12,2) NULL AFTER sample_available", $changes);
            self::ensureColumn($db, 'products', 'custom_logo', "TINYINT(1) NOT NULL DEFAULT 0 AFTER customization_details", $changes);
            self::ensureColumn($db, 'products', 'custom_color', "TINYINT(1) NOT NULL DEFAULT 0 AFTER custom_logo", $changes);
            self::ensureColumn($db, 'products', 'custom_packaging', "TINYINT(1) NOT NULL DEFAULT 0 AFTER custom_color", $changes);
            self::ensureColumn($db, 'products', 'oem_available', "TINYINT(1) NOT NULL DEFAULT 0 AFTER custom_packaging", $changes);
            self::ensureColumn($db, 'products', 'odm_available', "TINYINT(1) NOT NULL DEFAULT 0 AFTER oem_available", $changes);
            self::ensureColumn($db, 'products', 'private_label', "TINYINT(1) NOT NULL DEFAULT 0 AFTER odm_available", $changes);
            self::ensureColumn($db, 'products', 'approval_status', "ENUM('DRAFT','PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'APPROVED' AFTER status", $changes);
            self::ensureColumn($db, 'products', 'is_featured', "TINYINT(1) NOT NULL DEFAULT 0 AFTER pending_changes", $changes);
            self::ensureColumn($db, 'products', 'view_count', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER featured_order", $changes);

            // Email OTPs table
            self::ensureTable($db, 'email_otps', "
                CREATE TABLE IF NOT EXISTS email_otps (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(191) NOT NULL,
                    otp_code VARCHAR(10) NOT NULL,
                    purpose VARCHAR(50) NOT NULL DEFAULT 'EMAIL_VERIFICATION',
                    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5,
                    resend_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    verified_at DATETIME NULL,
                    ip_address VARCHAR(45) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email_purpose (email, purpose),
                    INDEX idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ", $changes);

        } catch (Throwable $e) {
            @error_log("[SchemaMigrator] Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage(), 'changes' => $changes];
        }

        return ['status' => 'success', 'changes_applied' => count($changes), 'details' => $changes];
    }

    /**
     * Ensure a column exists on a table without overwriting data
     */
    private static function ensureColumn(PDO $db, string $table, string $column, string $definition, array &$changes): void
    {
        try {
            $stmt = $db->prepare("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = :col
                LIMIT 1
            ");
            $stmt->execute([':tbl' => $table, ':col' => $column]);
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
                $changes[] = "Added column {$table}.{$column}";
            }
        } catch (Throwable $e) {
            // Log quietly, do not break execution
            @error_log("[SchemaMigrator] Column check error ({$table}.{$column}): " . $e->getMessage());
        }
    }

    /**
     * Ensure a table exists
     */
    private static function ensureTable(PDO $db, string $table, string $createSql, array &$changes): void
    {
        try {
            $stmt = $db->prepare("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl
                LIMIT 1
            ");
            $stmt->execute([':tbl' => $table]);
            if (!$stmt->fetch()) {
                $db->exec($createSql);
                $changes[] = "Created table {$table}";
            }
        } catch (Throwable $e) {
            @error_log("[SchemaMigrator] Table check error ({$table}): " . $e->getMessage());
        }
    }

    /**
     * Helper to safely invoke model ensureSchema
     */
    private static function safeCall(callable $callable, array &$changes, string $label): void
    {
        try {
            call_user_func($callable);
        } catch (Throwable $e) {
            @error_log("[SchemaMigrator] {$label} error: " . $e->getMessage());
        }
    }
}
