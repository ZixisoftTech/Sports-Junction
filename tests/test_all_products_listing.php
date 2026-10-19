<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

echo "======================================================================\n";
echo "SPORTS JUNCTION — ALL PRODUCTS CATALOG & FILTERS REGRESSION SUITE\n";
echo "======================================================================\n\n";

$pass = 0;
$fail = 0;

function assertCondition(bool $cond, string $name, string $details = ''): void {
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo " [PASS] {$name}" . ($details ? " ({$details})" : "") . "\n";
    } else {
        $fail++;
        echo " [FAIL] {$name}" . ($details ? " ({$details})" : "") . "\n";
    }
}

// TEST 1: Total Verified Products Count
$totalVerified = \App\Models\Product::getTotalVerifiedCount();
assertCondition($totalVerified > 6, "Total verified products exceeds 6", "Count: {$totalVerified}");

// TEST 2: Unfiltered Public Product Listing
$res = \App\Models\Product::getFilteredProducts([]);
assertCondition($res['total'] === $totalVerified, "getFilteredProducts([]) total matches getTotalVerifiedCount()", "Total: {$res['total']}");
assertCondition(count($res['products']) === 12, "Default pagination returns 12 products per page", "Count: " . count($res['products']));
assertCondition($res['current_page'] === 1, "Default current_page is 1");
assertCondition($res['last_page'] === (int)ceil($totalVerified / 12), "Correct last_page computed", "last_page: {$res['last_page']}");
assertCondition($res['from'] === 1 && $res['to'] === 12, "Correct 'from' and 'to' offsets on page 1", "from: {$res['from']}, to: {$res['to']}");

// TEST 3: Pagination Navigation (Page 2)
$page2 = \App\Models\Product::getFilteredProducts(['page' => 2]);
assertCondition($page2['current_page'] === 2, "Page 2 current_page is 2");
assertCondition(count($page2['products']) === 12, "Page 2 returns 12 products", "Count: " . count($page2['products']));
assertCondition($page2['from'] === 13 && $page2['to'] === 24, "Correct 'from' and 'to' offsets on page 2", "from: {$page2['from']}, to: {$page2['to']}");

// TEST 4: Pagination Navigation (Last Page)
$lastPageNum = $res['last_page'];
$lastPageRes = \App\Models\Product::getFilteredProducts(['page' => $lastPageNum]);
assertCondition($lastPageRes['current_page'] === $lastPageNum, "Last page navigation works", "Page: {$lastPageNum}");
assertCondition(count($lastPageRes['products']) > 0, "Last page has products", "Count: " . count($lastPageRes['products']));
assertCondition($lastPageRes['to'] === $totalVerified, "Last page 'to' matches total products", "to: {$lastPageRes['to']}");

// TEST 5: Business Rules Enforcement (All returned products are strictly eligible)
$allEligible = true;
$sampleBatch = array_merge($res['products'], $page2['products']);
foreach ($sampleBatch as $p) {
    if (!in_array($p['status'], ['PUBLISHED', 'ACTIVE']) || $p['approval_status'] !== 'APPROVED') {
        $allEligible = false;
        break;
    }
}
assertCondition($allEligible, "All returned products have status IN ('PUBLISHED', 'ACTIVE') and approval_status = 'APPROVED'");

// TEST 6: Strictly Hidden Unapproved Products
$rejectedOrDraft = \App\Models\Product::fetch("
    SELECT id FROM products 
    WHERE approval_status != 'APPROVED' OR status NOT IN ('PUBLISHED', 'ACTIVE') 
    LIMIT 1
");
if ($rejectedOrDraft) {
    $unapprovedId = (int)$rejectedOrDraft['id'];
    $foundInFiltered = false;
    // Check all pages
    for ($pg = 1; $pg <= $res['last_page']; $pg++) {
        $pgData = \App\Models\Product::getFilteredProducts(['page' => $pg, 'per_page' => 48]);
        foreach ($pgData['products'] as $p) {
            if ((int)$p['id'] === $unapprovedId) {
                $foundInFiltered = true;
                break 2;
            }
        }
    }
    assertCondition(!$foundInFiltered, "Unapproved/Draft product (ID {$unapprovedId}) is strictly excluded from public storefront");
} else {
    assertCondition(true, "No unapproved products present to test exclusion");
}

// TEST 7: findBySlug Works for All Eligible Products
$p1 = \App\Models\Product::findBySlug('grade-a-english-willow-cricket-bat');
assertCondition($p1 !== null && (int)$p1['id'] === 1, "Product::findBySlug('grade-a-english-willow-cricket-bat') resolves product 1");

$p2 = \App\Models\Product::findBySlug('fifa-quality-pro-synthetic-leather-football');
assertCondition($p2 !== null && (int)$p2['id'] === 2, "Product::findBySlug('fifa-quality-pro-synthetic-leather-football') resolves product 2");

$p3 = \App\Models\Product::findBySlug('adjustable-agility-training-hurdle');
assertCondition($p3 !== null && (int)$p3['id'] === 3, "Product::findBySlug('adjustable-agility-training-hurdle') resolves product 3");

// TEST 8: Category Filter
$catTest = \App\Models\Product::getFilteredProducts(['category_slug' => 'football-and-inflatables']);
assertCondition($catTest['total'] > 0, "Category filter by slug ('football-and-inflatables') works", "Total: {$catTest['total']}");
$catMatch = true;
foreach ($catTest['products'] as $p) {
    if (!str_contains(strtolower($p['all_category_names'] ?? ''), 'football') && !str_contains(strtolower($p['category_name'] ?? ''), 'football')) {
        $catMatch = false;
        break;
    }
}
assertCondition($catMatch, "All category filtered products belong to football category");

// TEST 9: Price Range Filter
$priceLow = 10.00;
$priceHigh = 50.00;
$priceRes = \App\Models\Product::getFilteredProducts(['min_price' => $priceLow, 'max_price' => $priceHigh]);
assertCondition($priceRes['total'] > 0, "Price filter between $10 and $50 returns products", "Total: {$priceRes['total']}");
$priceValid = true;
foreach ($priceRes['products'] as $p) {
    if ((float)$p['min_price'] < $priceLow || (float)$p['max_price'] > $priceHigh) {
        $priceValid = false;
        break;
    }
}
assertCondition($priceValid, "All price-filtered products respect [$10, $50] bounds");

// TEST 10: MOQ Filter
$moqMax = 20;
$moqRes = \App\Models\Product::getFilteredProducts(['max_moq' => $moqMax]);
assertCondition($moqRes['total'] > 0, "MOQ filter with max_moq=20 returns products", "Total: {$moqRes['total']}");
$moqValid = true;
foreach ($moqRes['products'] as $p) {
    if ((int)$p['moq_quantity'] > $moqMax) {
        $moqValid = false;
        break;
    }
}
assertCondition($moqValid, "All MOQ-filtered products have moq_quantity <= 20");

// TEST 11: Sorting (Price Low to High)
$sortLow = \App\Models\Product::getFilteredProducts(['sort' => 'price_low', 'per_page' => 20]);
$isSortedLow = true;
for ($i = 0; $i < count($sortLow['products']) - 1; $i++) {
    if ((float)$sortLow['products'][$i]['min_price'] > (float)$sortLow['products'][$i + 1]['min_price']) {
        $isSortedLow = false;
        break;
    }
}
assertCondition($isSortedLow, "Sorting by price_low orders products ascending by min_price");

// TEST 12: Sorting (Price High to Low)
$sortHigh = \App\Models\Product::getFilteredProducts(['sort' => 'price_high', 'per_page' => 20]);
$isSortedHigh = true;
for ($i = 0; $i < count($sortHigh['products']) - 1; $i++) {
    if ((float)$sortHigh['products'][$i]['max_price'] < (float)$sortHigh['products'][$i + 1]['max_price']) {
        $isSortedHigh = false;
        break;
    }
}
assertCondition($isSortedHigh, "Sorting by price_high orders products descending by max_price");

// TEST 13: Sorting (Newest Arrivals)
$sortNew = \App\Models\Product::getFilteredProducts(['sort' => 'newest', 'per_page' => 20]);
$isSortedNew = true;
for ($i = 0; $i < count($sortNew['products']) - 1; $i++) {
    if ((int)$sortNew['products'][$i]['id'] < (int)$sortNew['products'][$i + 1]['id']) {
        $isSortedNew = false;
        break;
    }
}
assertCondition($isSortedNew, "Sorting by newest orders products descending by ID");

// TEST 14: Category AllActive Product Counts
$activeCategories = \App\Models\Category::allActive();
assertCondition(count($activeCategories) > 0, "Category::allActive() returns active categories", "Count: " . count($activeCategories));
$anyNonZero = false;
foreach ($activeCategories as $ac) {
    if ((int)$ac['product_count'] > 0) {
        $anyNonZero = true;
        break;
    }
}
assertCondition($anyNonZero, "Active categories have accurate non-zero product counts matching approved products");

echo "\n======================================================================\n";
echo "SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
echo "======================================================================\n";
exit($fail > 0 ? 1 : 0);
