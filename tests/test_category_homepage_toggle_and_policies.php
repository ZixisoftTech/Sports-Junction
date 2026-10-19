<?php
declare(strict_types=1);

/**
 * End-to-End Test Suite:
 * 1. Policy Pages & 404 Verification (Zero 500 Errors)
 * 2. Category "Show on Homepage" Toggle Feature
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\CmsPage;
use App\Models\Category;
use App\Controllers\CmsPageController;
use App\Core\Request;
use App\Core\Response;

function assertTest(string $title, bool $condition, string $msg = ''): void {
    if ($condition) {
        echo "[PASS] $title" . ($msg ? " — $msg" : "") . PHP_EOL;
    } else {
        echo "[FAIL] $title" . ($msg ? " — $msg" : "") . PHP_EOL;
        throw new \RuntimeException("Test failed: $title - $msg");
    }
}

echo "=== STARTING VERIFICATION SUITE ===\n\n";

// SECTION 1: Policy Pages HTTP Rendering & Self-Healing
$req = new Request();
$res = new Response();
$ctrl = new CmsPageController($req, $res);

// Test 1: terms-and-conditions
ob_start();
$ctrl->show(['slug' => 'terms-and-conditions']);
$htmlTerms = ob_get_clean();
assertTest('Terms and Conditions HTTP 200', strlen($htmlTerms) > 1000 && str_contains($htmlTerms, 'Acceptance of Terms'), 'Rendered ' . strlen($htmlTerms) . ' bytes');

// Test 2: privacy-policy
ob_start();
$ctrl->show(['slug' => 'privacy-policy']);
$htmlPrivacy = ob_get_clean();
assertTest('Privacy Policy HTTP 200', strlen($htmlPrivacy) > 1000 && str_contains($htmlPrivacy, 'Information We Collect'), 'Rendered ' . strlen($htmlPrivacy) . ' bytes');

// Test 3: terms-manufacturing-sla
ob_start();
$ctrl->show(['slug' => 'terms-manufacturing-sla']);
$htmlSla = ob_get_clean();
assertTest('Terms & Manufacturing SLA HTTP 200', strlen($htmlSla) > 1000 && str_contains($htmlSla, 'Production Lead Times'), 'Rendered ' . strlen($htmlSla) . ' bytes');

// Test 4: seller-agreement
ob_start();
$ctrl->show(['slug' => 'seller-agreement']);
$htmlSeller = ob_get_clean();
assertTest('Seller & Manufacturer Agreement HTTP 200', strlen($htmlSeller) > 1000 && str_contains($htmlSeller, 'Eligibility and Registration'), 'Rendered ' . strlen($htmlSeller) . ' bytes');

// Test 5: Missing page returns clean 404 (No HTTP 500)
ob_start();
$ctrl->show(['slug' => 'missing-random-slug-' . time()]);
$html404 = ob_get_clean();
assertTest('Missing page returns 404 without 500', str_contains($html404, 'Page Not Found'), 'Clean 404 error page rendered successfully');

// Test 6: Footer links order
$footerPages = CmsPage::getByCategory('PLATFORM_ADMIN', true);
$footerSlugs = array_column($footerPages, 'slug');
assertTest('Footer PLATFORM & POLICIES links', 
    count($footerSlugs) >= 4 && in_array('terms-and-conditions', $footerSlugs) && in_array('privacy-policy', $footerSlugs),
    'Links: ' . implode(', ', $footerSlugs)
);

echo "\n--- SECTION 2: Category Show on Homepage Toggle ---\n";

// Ensure schema is active
Category::ensureSchema();

// Test 7: Create a category with toggle OFF (default)
$testCatName = 'QA Toggle Category ' . time();
$catId = Category::create([
    'name' => $testCatName,
    'slug' => 'qa-toggle-' . time(),
    'status' => 'ACTIVE',
    'display_order' => 1,
    'show_on_homepage' => 0
]);
assertTest('Create category with toggle OFF', $catId > 0, "Created ID $catId with show_on_homepage = 0");

// Verify it does NOT appear on homepage
$homepageCats = Category::getHomepageCategories();
$homepageCatIds = array_column($homepageCats, 'id');
assertTest('Category with toggle OFF does not appear on homepage', !in_array($catId, $homepageCatIds), "Verified ID $catId is omitted from homepage query");

// Test 8: Enable toggle and save
Category::update($catId, [
    'name' => $testCatName,
    'slug' => 'qa-toggle-' . time(),
    'status' => 'ACTIVE',
    'display_order' => 1,
    'show_on_homepage' => 1
]);
$loaded = Category::findById($catId);
assertTest('Category update persists show_on_homepage = 1', (int)$loaded['show_on_homepage'] === 1, "Database column equals 1");

$homepageCats = Category::getHomepageCategories();
$homepageCatIds = array_column($homepageCats, 'id');
assertTest('Active category with toggle ON appears on homepage', in_array($catId, $homepageCatIds), "Verified ID $catId is included in homepage query");

// Test 9: Set status to INACTIVE while toggle is ON: must NOT appear
Category::update($catId, [
    'name' => $testCatName,
    'slug' => 'qa-toggle-' . time(),
    'status' => 'INACTIVE',
    'display_order' => 1,
    'show_on_homepage' => 1
]);
$homepageCats = Category::getHomepageCategories();
$homepageCatIds = array_column($homepageCats, 'id');
assertTest('Inactive category with toggle ON never appears on homepage', !in_array($catId, $homepageCatIds), "Verified ID $catId is omitted when status = INACTIVE");

// Test 10: Toggle OFF while status is ACTIVE
Category::update($catId, [
    'name' => $testCatName,
    'slug' => 'qa-toggle-' . time(),
    'status' => 'ACTIVE',
    'display_order' => 1,
    'show_on_homepage' => 0
]);
$homepageCats = Category::getHomepageCategories();
$homepageCatIds = array_column($homepageCats, 'id');
assertTest('Active category with toggle OFF disappears from homepage', !in_array($catId, $homepageCatIds), "Verified ID $catId disappears when show_on_homepage = 0");

// Clean up test category
\App\Core\Database::getConnection()->exec("DELETE FROM categories WHERE id = $catId");
echo "Cleaned up test category ID $catId.\n";

echo "\n=== ALL TESTS COMPLETED SUCCESSFULLY! ===\n";
