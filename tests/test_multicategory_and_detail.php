<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

// Register PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Core\Response;

echo "=== SPORTS JUNCTION MULTI-CATEGORY & PRODUCT DETAIL VERIFICATION ===\n\n";

$allPassed = true;

// TEST 1: Response::formatImageUrl
echo "[TEST 1] Testing Response::formatImageUrl()...\n";
$testImg = 'public/uploads/products/sample.jpg';
$formatted = Response::formatImageUrl($testImg);
if (strpos($formatted, 'sample.jpg') !== false) {
    echo "  PASS: Response::formatImageUrl() returned {$formatted}\n";
} else {
    echo "  FAIL: Response::formatImageUrl() returned invalid result\n";
    $allPassed = false;
}

// TEST 2: Product Detail Page rendering for Adidas ball
echo "\n[TEST 2] Testing Product Detail page rendering (slug: adidas-fifa-world-cup-26-league-football)...\n";
$prod = Product::findBySlug('adidas-fifa-world-cup-26-league-football');
if (!$prod) {
    echo "  FAIL: Product with slug 'adidas-fifa-world-cup-26-league-football' not found!\n";
    $allPassed = false;
} else {
    echo "  PASS: Found product ID {$prod['id']}: '{$prod['name']}'\n";
    echo "        Primary Category: {$prod['category_name']} (ID: {$prod['category_id']})\n";
    echo "        All Categories: {$prod['all_category_names']}\n";
    echo "        All Subcategories: {$prod['all_subcategory_names']}\n";

    // Simulate ShopController::productDetail
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/product/adidas-fifa-world-cup-26-league-football';
    $_SERVER['HTTP_HOST'] = 'localhost';
    
    ob_start();
    try {
        $controller = new \App\Controllers\ShopController(new \App\Core\Request(), new \App\Core\Response());
        $controller->productDetail(['slug' => 'adidas-fifa-world-cup-26-league-football']);
        $html = ob_get_clean();
        
        $hasTailwind = strpos($html, 'tailwindcss.com') !== false || strpos($html, 'bg-surface') !== false;
        $hasDocType = strpos($html, '<!DOCTYPE html>') !== false;
        $hasFooter = strpos($html, 'Sports Junction') !== false;
        $hasBadges = true;
        foreach ($prod['categories'] as $c) {
            if (strpos($html, htmlspecialchars($c['name'])) === false && strpos($html, $c['name']) === false) {
                $hasBadges = false;
                break;
            }
        }
        
        if ($hasDocType && $hasTailwind && $hasFooter && $hasBadges) {
            echo "  PASS: Product detail page rendered full HTML layout (" . strlen($html) . " bytes) with all assigned categories!\n";
        } else {
            echo "  FAIL: Product detail page rendering incomplete. DocType: " . ($hasDocType ? 'yes' : 'no') 
                 . ", Tailwind: " . ($hasTailwind ? 'yes' : 'no')
                 . ", Footer: " . ($hasFooter ? 'yes' : 'no')
                 . ", Badges: " . ($hasBadges ? 'yes' : 'no') . "\n";
            $allPassed = false;
        }
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "  FAIL: Product detail threw exception: " . $e->getMessage() . "\n";
        $allPassed = false;
    }
}

// TEST 3: Primary Category Filter (ID 4: Football & Inflatables)
echo "\n[TEST 3] Testing primary category filtering (ID 4 / slug 'football-and-inflatables')...\n";
$resPrimarySlug = Product::getFilteredProducts(['category_slug' => 'football-and-inflatables']);
$foundPrimary = false;
foreach ($resPrimarySlug['products'] as $p) {
    if ((int)$p['id'] === (int)$prod['id']) {
        $foundPrimary = true;
        break;
    }
}
if ($foundPrimary) {
    echo "  PASS: Product {$prod['id']} found in getFilteredProducts by primary category slug\n";
} else {
    echo "  FAIL: Product {$prod['id']} NOT found in getFilteredProducts by primary category slug\n";
    $allPassed = false;
}

// TEST 4: Assigned Secondary Category Filter
$secCat = $prod['categories'][1] ?? $prod['categories'][0];
$secCatId = (int)$secCat['id'];
$secCatSlug = $secCat['slug'];
$secCatName = $secCat['name'];

echo "\n[TEST 4] Testing assigned secondary category filtering (ID {$secCatId} / slug '{$secCatSlug}')...\n";
$resSecSlug = Product::getFilteredProducts(['category_slug' => $secCatSlug]);
$foundSecSlug = false;
foreach ($resSecSlug['products'] as $p) {
    if ((int)$p['id'] === (int)$prod['id']) {
        $foundSecSlug = true;
        break;
    }
}
if ($foundSecSlug) {
    echo "  PASS: Product {$prod['id']} found in getFilteredProducts by secondary category slug '{$secCatSlug}'!\n";
} else {
    echo "  FAIL: Product {$prod['id']} NOT found in getFilteredProducts by secondary category slug '{$secCatSlug}'!\n";
    $allPassed = false;
}

$resSecId = Product::getFilteredProducts(['category_id' => $secCatId]);
$foundSecId = false;
foreach ($resSecId['products'] as $p) {
    if ((int)$p['id'] === (int)$prod['id']) {
        $foundSecId = true;
        break;
    }
}
if ($foundSecId) {
    echo "  PASS: Product {$prod['id']} found in getFilteredProducts by secondary category_id {$secCatId}!\n";
} else {
    echo "  FAIL: Product {$prod['id']} NOT found in getFilteredProducts by secondary category_id {$secCatId}!\n";
    $allPassed = false;
}

// TEST 5: Admin DataTable Category Filter (category_id = $secCatId)
echo "\n[TEST 5] Testing Admin DataTable category filter (category_id = {$secCatId})...\n";
$dtRes = Product::getDataTable(['category_id' => $secCatId]);
$foundDt = false;
foreach ($dtRes['data'] as $p) {
    if ((int)$p['id'] === (int)$prod['id']) {
        $foundDt = true;
        echo "        Row category_name: '{$p['category_name']}'\n";
        echo "        Row all_category_names: '{$p['all_category_names']}'\n";
        break;
    }
}
if ($foundDt) {
    echo "  PASS: Product {$prod['id']} found in Admin DataTable when filtering by category_id {$secCatId}!\n";
} else {
    echo "  FAIL: Product {$prod['id']} NOT found in Admin DataTable when filtering by category_id {$secCatId}!\n";
    $allPassed = false;
}

// TEST 6: Assigned Subcategory Filter
$secSub = $prod['subcategories'][1] ?? $prod['subcategories'][0];
$secSubId = (int)$secSub['id'];
$secSubSlug = $secSub['slug'];
$secSubName = $secSub['name'];
$secSubCatId = (int)($secSub['category_id'] ?? $secCatId);

echo "\n[TEST 6] Testing assigned subcategory filter (ID {$secSubId} / slug '{$secSubSlug}')...\n";
$resSub = Product::getFilteredProducts(['subcategory_slug' => $secSubSlug]);
$foundSub = false;
foreach ($resSub['products'] as $p) {
    if ((int)$p['id'] === (int)$prod['id']) {
        $foundSub = true;
        break;
    }
}
if ($foundSub) {
    echo "  PASS: Product {$prod['id']} found in getFilteredProducts by subcategory '{$secSubSlug}'!\n";
} else {
    echo "  FAIL: Product {$prod['id']} NOT found in getFilteredProducts by subcategory '{$secSubSlug}'!\n";
    $allPassed = false;
}

// TEST 7: Subcategory product count
echo "\n[TEST 7] Testing Subcategory::byCategoryActive({$secSubCatId}) product counts...\n";
$subs = Subcategory::byCategoryActive($secSubCatId);
$targetSub = null;
foreach ($subs as $s) {
    if ((int)$s['id'] === $secSubId) {
        $targetSub = $s;
        break;
    }
}
if ($targetSub && (int)$targetSub['product_count'] >= 1) {
    echo "  PASS: Subcategory {$secSubId} ('{$secSubName}') has product_count = {$targetSub['product_count']} (>= 1)\n";
} else {
    echo "  FAIL: Subcategory {$secSubId} product_count = " . ($targetSub['product_count'] ?? 'null') . " (expected >= 1)\n";
    $allPassed = false;
}

// TEST 8: Category product counts in Category::allActive()
echo "\n[TEST 8] Testing Category::allActive() product counts for Category 4 and {$secCatId}...\n";
$allCats = Category::allActive();
$cat4 = null;
$catSec = null;
foreach ($allCats as $c) {
    if ((int)$c['id'] === 4) $cat4 = $c;
    if ((int)$c['id'] === $secCatId) $catSec = $c;
}
if ($cat4 && (int)$cat4['product_count'] >= 1 && $catSec && (int)$catSec['product_count'] >= 1) {
    echo "  PASS: Category 4 product_count = {$cat4['product_count']}, Category {$secCatId} product_count = {$catSec['product_count']} (both >= 1)\n";
} else {
    echo "  FAIL: Category product counts: Cat 4 = " . ($cat4['product_count'] ?? 'null') . ", Cat {$secCatId} = " . ($catSec['product_count'] ?? 'null') . "\n";
    $allPassed = false;
}

echo "\n======================================================\n";
if ($allPassed) {
    echo "ALL TESTS PASSED SUCCESSFULLY! BOTH ISSUES VERIFIED FIXED!\n";
} else {
    echo "SOME TESTS FAILED! REVIEW OUTPUT ABOVE.\n";
}
echo "======================================================\n";
exit($allPassed ? 0 : 1);
