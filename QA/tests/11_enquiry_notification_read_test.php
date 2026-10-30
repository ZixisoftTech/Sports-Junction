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
\App\Helpers\Session::start();

echo "=== TEST: ENQUIRY VIEW AUTO-MARKS NOTIFICATION AS READ ===\n";

$db = \App\Core\Database::getConnection();

// 1. Create a test enquiry
$enqId = \App\Models\ProductInquiry::create([
    'buyer_name' => 'Notification Auto-Read Buyer',
    'buyer_email' => 'auto.read@example.com',
    'buyer_phone' => '+91 9999988888',
    'company_name' => 'Auto Read Sports Corp',
    'subject' => 'Prototype Sample Order: Evaluation Unit',
    'message' => 'Please courier 1 test unit for evaluation.',
    'enquiry_source' => 'SAMPLE',
    'quantity' => 1
]);

echo "Created test enquiry ID: $enqId\n";

// 2. Create notification for Super Admin
$notifId = \App\Models\Notification::createNotification(
    1,
    'ENQUIRY_SAMPLE',
    'Prototype Sample Request from Notification Auto-Read Buyer',
    'New sample submission',
    "/admin/enquiries/{$enqId}",
    'ENQUIRY',
    $enqId
);

echo "Created notification ID: $notifId\n";

$rowBefore = $db->query("SELECT is_read, read_at FROM notifications WHERE id = {$notifId}")->fetch(PDO::FETCH_ASSOC);
echo "Initial notification state: is_read = " . (int)$rowBefore['is_read'] . " (unseen, colored in dropdown)\n";
assert((int)$rowBefore['is_read'] === 0, "Notification should initially be unread");

// 3. Super Admin clicks and views the enquiry (triggers EnquiryManagementController::show)
$req = new \App\Core\Request();
$res = new \App\Core\Response();
\App\Helpers\Session::set('auth_user', ['id' => 1, 'role' => 'SUPER_ADMIN', 'name' => 'Super Administrator']);
$ctrl = new \App\Controllers\EnquiryManagementController($req, $res);

// Simulate viewing enquiry
ob_start();
$ctrl->show(['id' => $enqId]);
ob_end_clean();

// 4. Verify notification is now marked as read in database
$rowAfter = $db->query("SELECT is_read, read_at FROM notifications WHERE id = {$notifId}")->fetch(PDO::FETCH_ASSOC);
echo "Post-view notification state: is_read = " . (int)$rowAfter['is_read'] . ", read_at = " . $rowAfter['read_at'] . "\n";

if ((int)$rowAfter['is_read'] === 1 && !empty($rowAfter['read_at'])) {
    echo "[PASS] Notification automatically marked as read upon viewing enquiry!\n";
} else {
    echo "[FAIL] Notification was NOT marked as read!\n";
    exit(1);
}

// 5. Test dropdown rendering format: is_read = 1 must yield bg-white (NO COLOR!)
$recent = \App\Models\Notification::getRecentForAdmin(1, 10);
$found = null;
foreach ($recent as $n) {
    if ((int)$n['id'] === $notifId) {
        $found = $n;
        break;
    }
}
$isReadFlag = ((int)($found['is_read'] ?? 0) === 1);
$bgClass = $isReadFlag ? 'bg-white' : 'bg-primary-subtle bg-opacity-10';
echo "Dropdown background class for viewed enquiry notification: $bgClass\n";
if ($bgClass === 'bg-white') {
    echo "[PASS] Viewed notification renders clean white with zero unread tint!\n";
} else {
    echo "[FAIL] Notification still renders with unread color!\n";
    exit(1);
}

// 6. Test Mark All As Read
$affected = \App\Models\Notification::markAllAsRead(1);
echo "Mark All As Read affected: $affected\n";
$unreadCount = \App\Models\Notification::getUnreadCount(1);
echo "Remaining unread count: $unreadCount\n";
if ($unreadCount === 0) {
    echo "[PASS] All notifications cleanly marked as read!\n";
} else {
    echo "[FAIL] Unread count is not 0!\n";
    exit(1);
}

// Cleanup
$db->query("DELETE FROM notifications WHERE id = {$notifId}");
$db->query("DELETE FROM product_inquiries WHERE id = {$enqId}");

echo "=== ALL NOTIFICATION READ STATUS TESTS PASSED ===\n";
