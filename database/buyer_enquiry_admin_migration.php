<?php

require_once __DIR__ . '/../app/Helpers/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;
use App\Helpers\Env;

Env::load(__DIR__ . '/../.env');

$db = Database::getConnection();

echo "Running Buyer & Centralized Enquiry Management migration...\n";

$columns = $db->query("SHOW COLUMNS FROM product_inquiries")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('enquiry_source', $columns)) {
    $db->exec("ALTER TABLE product_inquiries ADD COLUMN enquiry_source ENUM('PRODUCT_PAGE', 'GENERAL', 'CONTACT_US') NOT NULL DEFAULT 'PRODUCT_PAGE' AFTER id");
    echo "Added column: enquiry_source\n";
}

if (!in_array('subject', $columns)) {
    $db->exec("ALTER TABLE product_inquiries ADD COLUMN subject VARCHAR(255) NULL AFTER company_name");
    echo "Added column: subject\n";
}

if (!in_array('target_price', $columns)) {
    $db->exec("ALTER TABLE product_inquiries ADD COLUMN target_price DECIMAL(12,2) NULL AFTER target_quantity");
    echo "Added column: target_price\n";
}

if (!in_array('delivery_location', $columns)) {
    $db->exec("ALTER TABLE product_inquiries ADD COLUMN delivery_location VARCHAR(255) NULL AFTER lead_time_req");
    echo "Added column: delivery_location\n";
}

if (!in_array('required_delivery_date', $columns)) {
    $db->exec("ALTER TABLE product_inquiries ADD COLUMN required_delivery_date DATE NULL AFTER delivery_location");
    echo "Added column: required_delivery_date\n";
}

if (!in_array('admin_notes', $columns)) {
    $db->exec("ALTER TABLE product_inquiries ADD COLUMN admin_notes TEXT NULL AFTER specifications");
    echo "Added column: admin_notes\n";
}

// Ensure status column supports all lifecycle values: PENDING, SUBMITTED, VIEWED, RESPONDED, IN_DISCUSSION, CLOSED, RESOLVED
$db->exec("ALTER TABLE product_inquiries MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'SUBMITTED'");
echo "Updated status column to VARCHAR(30) DEFAULT 'SUBMITTED'\n";

// Backfill enquiry_source for existing records where product_id is NULL
$db->exec("UPDATE product_inquiries SET enquiry_source = 'GENERAL' WHERE product_id IS NULL AND enquiry_source = 'PRODUCT_PAGE'");
$db->exec("UPDATE product_inquiries SET enquiry_source = 'PRODUCT_PAGE' WHERE product_id IS NOT NULL AND (enquiry_source IS NULL OR enquiry_source = '')");

// Add indexes for fast server-side filtering and global search
$existingIndexes = array_column($db->query("SHOW INDEX FROM product_inquiries")->fetchAll(PDO::FETCH_ASSOC), 'Key_name');

$indexesToCreate = [
    'idx_pi_source' => 'CREATE INDEX idx_pi_source ON product_inquiries(enquiry_source)',
    'idx_pi_supplier' => 'CREATE INDEX idx_pi_supplier ON product_inquiries(supplier_id)',
    'idx_pi_product' => 'CREATE INDEX idx_pi_product ON product_inquiries(product_id)',
    'idx_pi_buyer_user' => 'CREATE INDEX idx_pi_buyer_user ON product_inquiries(buyer_user_id)',
    'idx_pi_status' => 'CREATE INDEX idx_pi_status ON product_inquiries(status)',
    'idx_pi_created' => 'CREATE INDEX idx_pi_created ON product_inquiries(created_at)'
];

foreach ($indexesToCreate as $idxName => $sql) {
    if (!in_array($idxName, $existingIndexes)) {
        try {
            $db->exec($sql);
            echo "Created index: $idxName\n";
        } catch (\Throwable $e) {
            // Ignore if already exists
        }
    }
}

// Also ensure users table has helpful indexes for Buyer Management
$userIndexes = array_column($db->query("SHOW INDEX FROM users")->fetchAll(PDO::FETCH_ASSOC), 'Key_name');
if (!in_array('idx_users_role_status', $userIndexes)) {
    try {
        $db->exec("CREATE INDEX idx_users_role_status ON users(role, status)");
        echo "Created index: idx_users_role_status on users\n";
    } catch (\Throwable $e) {}
}

echo "Migration completed successfully!\n";
