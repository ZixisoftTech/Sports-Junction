<?php
declare(strict_types=1);

/**
 * Migration: Support Queries & Activity Logs
 * Sports Junction Buyer & Seller Help Centre Support System
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';

try {
    $pdo = \App\Core\Database::getConnection();

    echo "Creating `support_queries` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS support_queries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id VARCHAR(50) NOT NULL UNIQUE,
            source_type ENUM('BUYER', 'SELLER') NOT NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            category VARCHAR(100) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            order_id VARCHAR(100) NULL,
            existing_ticket_id VARCHAR(100) NULL,
            description TEXT NOT NULL,
            attachment_path VARCHAR(255) NULL,
            attachment_name VARCHAR(255) NULL,
            attachment_size INT UNSIGNED NULL,
            attachment_mime VARCHAR(100) NULL,
            assigned_to INT UNSIGNED NULL,
            status ENUM('NEW', 'IN_PROGRESS', 'AWAITING_CUSTOMER_RESPONSE', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'NEW',
            internal_notes TEXT NULL,
            user_id INT UNSIGNED NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_source_type (source_type),
            INDEX idx_ticket_id (ticket_id),
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_order_id (order_id),
            INDEX idx_existing_ticket_id (existing_ticket_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Creating `support_query_activities` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS support_query_activities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            query_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            action_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            old_value VARCHAR(255) NULL,
            new_value VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_query_id (query_id),
            INDEX idx_user_id (user_id),
            INDEX idx_action_type (action_type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (query_id) REFERENCES support_queries(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Support queries migration completed successfully!\n";
} catch (\Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
