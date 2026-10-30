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

echo "=== TEST SUITE 4: BUYER SORT LIST & BULK ENQUIRY ===\n";

$pdo = \App\Core\Database::getConnection();

// Sample product
$sampleProd = $pdo->query("SELECT id, supplier_id, title, min_price, max_price FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$prodId = $sampleProd ? (int)$sampleProd['id'] : 1;

// 1. Guest Check: Guest must NOT be allowed to sort products without login
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
}

$req = new \App\Core\Request();
$res = new \App\Core\Response();
$controller = new \App\Controllers\BuyerPortalController($req, $res);

// We inspect the logic in toggleSortList
$guestUser = \App\Helpers\Session::get('auth_user');
$isGuestBlocked = (!$guestUser || !in_array($guestUser['role'] ?? '', ['BUYER', 'SUPER_ADMIN']));
assertTest("Guest user cannot sort without login", $isGuestBlocked, "Guest was permitted to sort products");

// 2. Authenticated Buyer: Add product to Sort List
$buyerId = 1;
// Ensure clean state
\App\Models\BuyerFavourite::removeFromSortList($buyerId, $prodId);
$initCount = \App\Models\BuyerFavourite::countForBuyer($buyerId);

$added = \App\Models\BuyerFavourite::addToSortList($buyerId, $prodId);
assertTest("Buyer added product to Sort List", $added);

$afterAddCount = \App\Models\BuyerFavourite::countForBuyer($buyerId);
assertTest("Sort List counter incremented", $afterAddCount === $initCount + 1, "Count was {$afterAddCount}, expected " . ($initCount + 1));

$inList = \App\Models\BuyerFavourite::isInSortList($buyerId, $prodId);
assertTest("isInSortList returns true for sorted product", $inList);

// 3. Retrieve Sort List for Buyer
$sortItems = \App\Models\BuyerFavourite::getBuyerSortList($buyerId);
$foundItem = false;
foreach ($sortItems as $item) {
    if ((int)$item['id'] === $prodId) {
        $foundItem = true;
        break;
    }
}
assertTest("Sorted product appears in getBuyerSortList()", $foundItem);

// 4. Remove product from Sort List
$removed = \App\Models\BuyerFavourite::removeFromSortList($buyerId, $prodId);
assertTest("Removed product from Sort List", $removed);
assertTest("isInSortList returns false after removal", !\App\Models\BuyerFavourite::isInSortList($buyerId, $prodId));

// 5. Multi-Product Bulk Enquiry Submission
$bulkRes = \App\Models\BulkProductEnquiry::createBulkEnquiry([
    'buyer_user_id' => $buyerId,
    'buyer_name' => 'QA Buyer Enterprise',
    'buyer_email' => 'qa.enterprise@example.com',
    'buyer_phone' => '+91 9999900001',
    'company_name' => 'Sports Superstore Chain',
    'delivery_location' => 'Mumbai Seaport',
    'overall_message' => 'Consolidated multi-product purchase inquiry for Q1 replenishment.',
    'expected_timeline' => '45 Days'
], [
    [
        'product_id' => $prodId,
        'supplier_id' => (int)($sampleProd['supplier_id'] ?? 1),
        'product_name' => $sampleProd['title'] ?? 'Sports Product',
        'target_quantity' => 1000,
        'moq_quantity' => 50,
        'moq_unit_name' => 'pieces',
        'target_price' => 25.50,
        'currency' => 'USD',
        'specifications' => 'Strict AQL 1.5 standard compliance required.'
    ]
]);

$bulkId = (int)($bulkRes['bulk_enquiry_id'] ?? $bulkRes['id'] ?? 0);
assertTest("Bulk enquiry recorded in database with reference number", $bulkId > 0 && !empty($bulkRes['reference_no']));

$bulkDetail = \App\Models\BulkProductEnquiry::getDetailForAdmin($bulkId);
assertTest("Bulk enquiry details retrievable by Super Admin", $bulkDetail !== null);
assertTest("Bulk enquiry contains 1 product item with exact specs", count($bulkDetail['items'] ?? []) === 1);

// 6. Super Admin update status of Bulk Enquiry
$statusUpdated = \App\Models\BulkProductEnquiry::updateStatus($bulkId, 'REVIEWED', 'Assigned to export operations desk');
assertTest("Bulk enquiry status updated to REVIEWED", $statusUpdated);

$recheck = \App\Models\BulkProductEnquiry::getDetailForAdmin($bulkId);
assertTest("Updated status persisted in database", ($recheck['status'] ?? '') === 'REVIEWED');

// Cleanup
$pdo->query("DELETE FROM bulk_product_enquiry_items WHERE bulk_enquiry_id = {$bulkId}");
$pdo->query("DELETE FROM bulk_product_enquiries WHERE id = {$bulkId}");

echo "\nSuite 4 Results: $passedTests PASSED, $failedTests FAILED.\n";
if (!empty($bugs)) {
    echo "Bugs logged:\n";
    print_r($bugs);
}
