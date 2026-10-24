<?php
declare(strict_types=1);

/**
 * Migration Runner for V3 Updates:
 * - Ensures schemas for cms_pages, notifications, audit_logs, site_feedbacks, bulk_product_enquiries
 * - Seeds/updates all 21 official footer CMS pages
 *
 * Usage:
 * CLI: php database/migrate_v3.php
 * Web: (allowed via direct browser request if needed)
 */

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "=== Sports Junction V3 Migration & Schema Healing ===\n\n";

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

try {
    $pdo = \App\Core\Database::getConnection();
    echo "[OK] Connected to database: " . ($pdo->query("SELECT DATABASE()")->fetchColumn() ?: 'unknown') . "\n";
} catch (\Throwable $e) {
    echo "[FAIL] Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}

// 1. CmsPage Schema & Seeding
echo "\n--- 1. Checking cms_pages table ---\n";
try {
    \App\Models\CmsPage::ensureSchema();
    echo "[OK] cms_pages schema verified.\n";
    $count = \App\Models\CmsPage::seedAll21Pages();
    echo "[OK] Seeded/updated {$count} footer pages across 5 columns.\n";
} catch (\Throwable $e) {
    echo "[ERROR] cms_pages failed: " . $e->getMessage() . "\n";
}

// 2. Notifications Schema
echo "\n--- 2. Checking notifications table ---\n";
try {
    \App\Models\Notification::ensureSchema();
    echo "[OK] notifications schema verified.\n";
} catch (\Throwable $e) {
    echo "[ERROR] notifications failed: " . $e->getMessage() . "\n";
}

// 3. AuditLog Schema
echo "\n--- 3. Checking audit_logs table ---\n";
try {
    \App\Models\AuditLog::ensureSchema();
    echo "[OK] audit_logs schema verified.\n";
} catch (\Throwable $e) {
    echo "[ERROR] audit_logs failed: " . $e->getMessage() . "\n";
}

// 4. SiteFeedback Schema
echo "\n--- 4. Checking site_feedbacks table ---\n";
try {
    \App\Models\SiteFeedback::ensureSchema();
    echo "[OK] site_feedbacks schema verified.\n";
} catch (\Throwable $e) {
    echo "[ERROR] site_feedbacks failed: " . $e->getMessage() . "\n";
}

// 5. Bulk Product Enquiry Schema
echo "\n--- 5. Checking bulk_product_enquiries & items table ---\n";
try {
    \App\Models\BulkProductEnquiry::ensureSchema();
    echo "[OK] bulk_product_enquiries schema verified.\n";
} catch (\Throwable $e) {
    echo "[ERROR] bulk_product_enquiries failed: " . $e->getMessage() . "\n";
}

echo "\n=== V3 Migration Completed Successfully! ===\n";
