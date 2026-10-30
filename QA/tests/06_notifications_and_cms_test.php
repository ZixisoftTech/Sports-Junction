<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../../.env');
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

date_default_timezone_set('Asia/Kolkata');

$passedTests = 0;
$failedTests = 0;
$bugs = [];

function assertTest(string $desc, bool $condition, string $failMsg = '') {
    global $passedTests, $failedTests, $bugs;
    if ($condition) {
        echo "[PASS] $desc\n";
        $passedTests++;
    } else {
        echo "[FAIL] $desc -> $failMsg\n";
        $failedTests++;
        $bugs[] = ['description' => $desc, 'message' => $failMsg];
    }
}

echo "=== TEST SUITE 6: NOTIFICATIONS, CMS & ASIA/KOLKATA TIMEZONE ===\n";

$pdo = \App\Core\Database::getConnection();

// 1. In-App Notifications & Read Status Persistence
$adminUser = $pdo->query("SELECT id FROM users WHERE role = 'SUPER_ADMIN' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$adminId = $adminUser ? (int)$adminUser['id'] : 1;

$notifId = \App\Models\Notification::createNotification(
    $adminId,
    'ENQUIRY_RECEIVED',
    'QA Test Notification',
    'New test inquiry has been received for validation.',
    '/admin/product-enquiries',
    'ENQUIRY',
    12345
);
assertTest("Notification created successfully", $notifId > 0);

$row1 = $pdo->query("SELECT is_read, read_at FROM notifications WHERE id = {$notifId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Initial notification state is unread (is_read = 0)", (int)($row1['is_read'] ?? 1) === 0 && empty($row1['read_at']));

// Mark as read
$marked = \App\Models\Notification::markAsRead($adminId, $notifId);
assertTest("Notification marked as read", $marked);

// Re-query database to ensure read state persisted
$row2 = $pdo->query("SELECT is_read, read_at FROM notifications WHERE id = {$notifId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Read status persisted in database (is_read = 1)", (int)($row2['is_read'] ?? 0) === 1);
assertTest("Read timestamp is populated", !empty($row2['read_at']));

// Cleanup test notification
$pdo->query("DELETE FROM notifications WHERE id = {$notifId}");

// 2. Dynamic Website Logo
$originalLogo = \App\Models\SiteSetting::get('site_logo', '');
$testLogoPath = 'uploads/settings/qa_dynamic_logo_' . time() . '.png';

\App\Models\SiteSetting::set('site_logo', $testLogoPath);
$updatedUrl = \App\Models\SiteSetting::getLogoUrl();
assertTest("Dynamic logo URL reflects updated site setting", str_contains($updatedUrl, $testLogoPath), "Logo URL does not contain updated path: {$updatedUrl}");

// Revert to original logo
\App\Models\SiteSetting::set('site_logo', $originalLogo);
assertTest("Reverted logo cleanly", !str_contains(\App\Models\SiteSetting::getLogoUrl(), $testLogoPath));

// 3. Asia/Kolkata (IST) Timezone Enforced in Audit Trail
$nowPhpIst = date('Y-m-d H:i:s');
\App\Helpers\AuditLogger::log(
    'QA_TIMEZONE_CHECK',
    'SystemTest',
    99999,
    'Audit entry to verify Asia/Kolkata IST timestamp integrity'
);

$auditRow = $pdo->query("SELECT id, created_at FROM audit_logs WHERE action = 'QA_TIMEZONE_CHECK' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest("Audit log entry created", !empty($auditRow['id']));

$loggedAt = $auditRow['created_at'] ?? '';
$diffSeconds = abs(strtotime($nowPhpIst) - strtotime($loggedAt));
assertTest("Audit log timestamp matches Asia/Kolkata IST (diff <= 5s)", $diffSeconds <= 5, "Time difference was {$diffSeconds}s (Logged: {$loggedAt}, PHP IST: {$nowPhpIst})");

// Verify MySQL session timezone returns +05:30
$sessionTz = $pdo->query("SELECT @@session.time_zone")->fetchColumn();
assertTest("MySQL session timezone is +05:30 (IST)", $sessionTz === '+05:30', "Session timezone was {$sessionTz}");

// Cleanup audit test row
if (!empty($auditRow['id'])) {
    $pdo->query("DELETE FROM audit_logs WHERE id = {$auditRow['id']}");
}

echo "\nSuite 6 Results: $passedTests PASSED, $failedTests FAILED.\n";
if (!empty($bugs)) {
    echo "Bugs logged:\n";
    print_r($bugs);
}
