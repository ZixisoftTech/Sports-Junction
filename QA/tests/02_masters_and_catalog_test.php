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

echo "=== TEST SUITE 2: MASTERS, CATEGORIES, PRODUCTS & APPROVALS ===\n";

// 1. Masters: Countries
$countries = \App\Models\Country::allActive();
assertTest("Active countries loaded", count($countries) > 0, "No active countries found");
$india = null;
foreach ($countries as $c) {
    if (($c['iso_alpha2'] ?? '') === 'IN') {
        $india = $c;
        break;
    }
}
assertTest("India country record found", $india !== null && ltrim($india['phone_code'], '+') === '91', "India ISO IN record missing or wrong code");

// 2. Masters: Price & MOQ Units
$priceUnits = \App\Models\PriceUnit::allActive();
assertTest("Price units exist", count($priceUnits) > 0, "Price units missing");
$moqUnits = \App\Models\MoqUnit::allActive();
assertTest("MOQ units exist", count($moqUnits) > 0, "MOQ units missing");

// 3. Category & Subcategory Management
$testCatName = "QA Test Sports Category " . time();
$testSlug = "qa-test-sports-" . time();
$catId = \App\Models\Category::create([
    'name' => $testCatName,
    'slug' => $testSlug,
    'description' => 'Automated QA testing category',
    'status' => 'ACTIVE',
    'sort_order' => 999
]);
assertTest("Category created successfully", $catId > 0, "Failed to create category");

$createdCat = \App\Models\Category::findById($catId);
assertTest("Created category exists in DB", $createdCat !== null && $createdCat['name'] === $testCatName);

// Create subcategory
$subId = \App\Models\Subcategory::create([
    'category_id' => $catId,
    'name' => 'QA Subcategory Cricket',
    'slug' => 'qa-sub-cricket-' . time(),
    'description' => 'QA Subcategory testing',
    'status' => 'ACTIVE'
]);
assertTest("Subcategory created linked to category", $subId > 0);

// Inactive filtering test
\App\Models\Category::setStatus($catId, 'INACTIVE');
$activeCats = \App\Models\Category::allActive();
$foundInactive = false;
foreach ($activeCats as $ac) {
    if ((int)$ac['id'] === $catId) {
        $foundInactive = true;
        break;
    }
}
assertTest("Inactive category is excluded from public allActive()", !$foundInactive, "Inactive category was returned by allActive()");

// Reactivate for further tests
\App\Models\Category::setStatus($catId, 'ACTIVE');

// 4. Product Creation & Approval Workflow
$pdo = \App\Core\Database::getConnection();
$supplier = $pdo->query("SELECT id FROM suppliers WHERE approval_status = 'APPROVED' AND status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$supplierId = $supplier ? (int)$supplier['id'] : 1;

$prodData = [
    'supplier_id' => $supplierId,
    'category_id' => $catId,
    'subcategory_id' => $subId,
    'title' => 'QA Match Grade English Willow Bat ' . time(),
    'slug' => 'qa-match-bat-' . time(),
    'description' => 'Handcrafted English willow bat for professional tournament cricket.',
    'price_min' => 4500.00,
    'price_max' => 6500.00,
    'price_unit_id' => $priceUnits[0]['id'] ?? 1,
    'moq' => 10,
    'moq_unit_id' => $moqUnits[0]['id'] ?? 1,
    'country_id' => $india['id'] ?? 1,
    'lead_time_days' => 14,
    'status' => 'DRAFT',
    'approval_status' => 'PENDING',
    'is_featured' => 0
];

$prodId = \App\Models\Product::create($prodData);
assertTest("Product created with initial PENDING status", $prodId > 0);

$prod = \App\Models\Product::findById($prodId);
assertTest("Pending product has PENDING_APPROVAL status", in_array($prod['approval_status'] ?? '', ['PENDING', 'PENDING_APPROVAL'], true));

// 5. Product Approval
$approverId = 1; // Super Admin ID
\App\Models\Product::approveProduct($prodId, $approverId);
$approvedProd = \App\Models\Product::findById($prodId);
assertTest("Product approval status changes to APPROVED", ($approvedProd['approval_status'] ?? '') === 'APPROVED');
assertTest("Approved product status set to PUBLISHED/ACTIVE", in_array($approvedProd['status'] ?? '', ['ACTIVE', 'PUBLISHED'], true));

// 6. Product Rejection with Mandatory Reason
\App\Models\Product::rejectProduct($prodId, $approverId, "Image resolution does not meet quality guidelines.");
$rejectedProd = \App\Models\Product::findById($prodId);
assertTest("Product status changes to REJECTED", ($rejectedProd['approval_status'] ?? '') === 'REJECTED');
assertTest("Rejection reason is stored in DB", !empty($rejectedProd['rejection_reason']), "Rejection reason was not saved");

// Cleanup test records
$pdo->query("DELETE FROM products WHERE id = {$prodId}");
$pdo->query("DELETE FROM subcategories WHERE id = {$subId}");
$pdo->query("DELETE FROM categories WHERE id = {$catId}");

echo "\nSuite 2 Results: $passedTests PASSED, $failedTests FAILED.\n";
if (!empty($bugs)) {
    echo "Bugs logged:\n";
    print_r($bugs);
}
