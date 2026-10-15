<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — REAL-TIME CHAT, NEGOTIATION & DEAL MANAGEMENT MIGRATION
 * Creates tables for:
 *   - chat_conversations
 *   - chat_messages
 *   - chat_attachments
 *   - chat_offers
 *   - chat_blocks
 *   - chat_reports
 *   - notifications
 * =======================================================================
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';

\App\Helpers\Env::load(__DIR__ . '/../.env');

use App\Core\Database;

try {
    $db = Database::getConnection();
    echo "Connected to database successfully.\n";

    // 1. chat_conversations table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_conversations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `buyer_id` INT NOT NULL,
            `supplier_id` INT NOT NULL,
            `product_id` INT NULL,
            `enquiry_id` INT NULL,
            `status` ENUM('ACTIVE', 'NEGOTIATION_ONGOING', 'OFFER_PENDING', 'DEAL_AGREED', 'DEAL_COMPLETED', 'BLOCKED', 'UNDER_REVIEW', 'ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
            `deal_status` VARCHAR(50) NOT NULL DEFAULT 'INITIAL_CONTACT',
            `last_message_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_buyer` (`buyer_id`),
            INDEX `idx_supplier` (`supplier_id`),
            INDEX `idx_product` (`product_id`),
            INDEX `idx_enquiry` (`enquiry_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_last_message` (`last_message_at`),
            INDEX `idx_buyer_supplier_product` (`buyer_id`, `supplier_id`, `product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table `chat_conversations` checked/created.\n";

    // 2. chat_messages table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `conversation_id` INT NOT NULL,
            `sender_user_id` INT NOT NULL,
            `sender_role` ENUM('BUYER', 'SUPPLIER', 'SUPER_ADMIN', 'SYSTEM') NOT NULL,
            `sender_admin_id` INT NULL,
            `is_on_behalf_of_supplier` TINYINT(1) NOT NULL DEFAULT 0,
            `is_internal_admin_note` TINYINT(1) NOT NULL DEFAULT 0,
            `message_type` ENUM('TEXT', 'ATTACHMENT', 'OFFER', 'SYSTEM') NOT NULL DEFAULT 'TEXT',
            `message_body` TEXT NULL,
            `delivery_status` ENUM('SENT', 'DELIVERED', 'READ') NOT NULL DEFAULT 'SENT',
            `read_at` DATETIME NULL,
            `has_restricted_contact` TINYINT(1) NOT NULL DEFAULT 0,
            `metadata` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_conversation` (`conversation_id`),
            INDEX `idx_sender_user` (`sender_user_id`),
            INDEX `idx_delivery_status` (`delivery_status`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_internal_note` (`is_internal_admin_note`),
            CONSTRAINT `fk_chat_messages_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table `chat_messages` checked/created.\n";

    // 3. chat_attachments table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_attachments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `message_id` INT NULL,
            `conversation_id` INT NOT NULL,
            `uploader_user_id` INT NOT NULL,
            `file_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_type` VARCHAR(50) NOT NULL,
            `file_size` INT NOT NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_message` (`message_id`),
            INDEX `idx_conv_attach` (`conversation_id`),
            CONSTRAINT `fk_chat_attach_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table `chat_attachments` checked/created.\n";

    // 4. chat_offers table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_offers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `conversation_id` INT NOT NULL,
            `product_id` INT NULL,
            `created_by_user_id` INT NOT NULL,
            `created_by_role` ENUM('BUYER', 'SUPPLIER', 'SUPER_ADMIN') NOT NULL,
            `parent_offer_id` INT NULL,
            `offer_version` INT NOT NULL DEFAULT 1,
            `quantity` INT NOT NULL,
            `unit` VARCHAR(50) NOT NULL DEFAULT 'Pieces',
            `unit_price` DECIMAL(12, 2) NOT NULL,
            `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
            `total_amount` DECIMAL(14, 2) NOT NULL,
            `delivery_location` VARCHAR(255) NULL,
            `lead_time` VARCHAR(100) NULL,
            `customization_terms` TEXT NULL,
            `additional_terms` TEXT NULL,
            `status` ENUM('DRAFT', 'PENDING_ACCEPTANCE', 'ACCEPTED', 'REJECTED', 'COUNTERED', 'WITHDRAWN', 'EXPIRED') NOT NULL DEFAULT 'PENDING_ACCEPTANCE',
            `expiry_date` DATE NULL,
            `accepted_by_user_id` INT NULL,
            `accepted_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_offer_conv` (`conversation_id`),
            INDEX `idx_offer_status` (`status`),
            INDEX `idx_parent_offer` (`parent_offer_id`),
            CONSTRAINT `fk_chat_offers_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table `chat_offers` checked/created.\n";

    // 5. chat_blocks table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_blocks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `blocker_user_id` INT NOT NULL,
            `blocked_user_id` INT NOT NULL,
            `status` ENUM('BLOCKED', 'UNBLOCKED') NOT NULL DEFAULT 'BLOCKED',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_blocker_blocked` (`blocker_user_id`, `blocked_user_id`),
            INDEX `idx_blocker` (`blocker_user_id`),
            INDEX `idx_blocked` (`blocked_user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table `chat_blocks` checked/created.\n";

    // 6. chat_reports table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_reports` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `conversation_id` INT NOT NULL,
            `reporter_user_id` INT NOT NULL,
            `reported_user_id` INT NOT NULL,
            `reason` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `status` ENUM('PENDING', 'UNDER_REVIEW', 'RESOLVED', 'DISMISSED') NOT NULL DEFAULT 'PENDING',
            `admin_notes` TEXT NULL,
            `resolved_by` INT NULL,
            `resolved_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_report_conv` (`conversation_id`),
            INDEX `idx_report_status` (`status`),
            CONSTRAINT `fk_chat_reports_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table `chat_reports` checked/created.\n";

    // 7. notifications table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `link` VARCHAR(255) NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_notif_user` (`user_id`),
            INDEX `idx_notif_read` (`is_read`),
            INDEX `idx_notif_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table `notifications` checked/created.\n";

    echo "All Chat & Negotiation database migrations completed successfully!\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
