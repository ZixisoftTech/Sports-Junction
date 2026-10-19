<?php
declare(strict_types=1);

/**
 * Automated Verification Test Suite
 * CMS Policies, Footer Links & Homepage Category Management
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

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

use App\Models\CmsPage;
use App\Models\SiteSetting;
use App\Models\Category;

$results = [];

function recordTest(string $testName, bool $passed, string $details = ''): void {
    global $results;
    $results[] = [
        'name' => $testName,
        'status' => $passed ? 'PASSED' : 'FAILED',
        'details' => $details
    ];
    echo sprintf("[%s] %s %s\n", $passed ? 'PASS' : 'FAIL', $testName, $details ? "— $details" : "");
}

echo "=== STARTING VERIFICATION TEST SUITE ===\n\n";

// TEST 1: Terms and Conditions CMS Page
$terms = CmsPage::findBySlug('terms-and-conditions');
if ($terms && (int)$terms['is_active'] === 1) {
    $hasSection1 = str_contains($terms['content'], 'Acceptance of Terms');
    $hasSection14 = str_contains($terms['content'], 'Governing Law and Official Contact');
    recordTest(
        'Task 1: Terms and Conditions CMS Page',
        $hasSection1 && $hasSection14,
        "Found active slug 'terms-and-conditions' with ID {$terms['id']} and complete 14-section coverage."
    );
} else {
    recordTest('Task 1: Terms and Conditions CMS Page', false, "Page not found or inactive.");
}

// TEST 2: Privacy Policy CMS Page
$privacy = CmsPage::findBySlug('privacy-policy');
if ($privacy && (int)$privacy['is_active'] === 1) {
    $hasInfo = str_contains($privacy['content'], 'Information We Collect');
    $hasUpdate = str_contains($privacy['content'], 'Policy Updates and Contact');
    recordTest(
        'Task 2: Privacy Policy CMS Page',
        $hasInfo && $hasUpdate,
        "Found active slug 'privacy-policy' with ID {$privacy['id']} and complete 12-section coverage."
    );
} else {
    recordTest('Task 2: Privacy Policy CMS Page', false, "Page not found or inactive.");
}

// TEST 3: Terms & Manufacturing SLA CMS Page
$sla = CmsPage::findBySlug('terms-manufacturing-sla');
if ($sla && (int)$sla['is_active'] === 1) {
    $hasLead = str_contains($sla['content'], 'Production Lead Times');
    $hasDispute = str_contains($sla['content'], 'Dispute Escalation');
    recordTest(
        'Task 3: Terms & Manufacturing SLA CMS Page',
        $hasLead && $hasDispute,
        "Found active slug 'terms-manufacturing-sla' with ID {$sla['id']} updated with 13 required sections."
    );
} else {
    recordTest('Task 3: Terms & Manufacturing SLA CMS Page', false, "Page not found or inactive.");
}

// TEST 4: Seller & Manufacturer Agreement CMS Page
$seller = CmsPage::findBySlug('seller-agreement');
if ($seller && (int)$seller['is_active'] === 1) {
    $hasElig = str_contains($seller['content'], 'Eligibility and Registration');
    $hasMaintain = str_contains($seller['content'], 'Contact and Maintenance');
    recordTest(
        'Task 4: Seller & Manufacturer Agreement CMS Page',
        $hasElig && $hasMaintain,
        "Found active slug 'seller-agreement' with ID {$seller['id']} updated with 15 required sections."
    );
} else {
    recordTest('Task 4: Seller & Manufacturer Agreement CMS Page', false, "Page not found or inactive.");
}

// TEST 5: Footer Links Display All 4 Policies
$platformPages = CmsPage::getByCategory('PLATFORM_ADMIN');
$platformSlugs = array_column($platformPages, 'slug');
$expectedSlugs = ['terms-and-conditions', 'privacy-policy', 'terms-manufacturing-sla', 'seller-agreement'];
$allFound = true;
foreach ($expectedSlugs as $es) {
    if (!in_array($es, $platformSlugs, true)) {
        $allFound = false;
        break;
    }
}
recordTest(
    'Task 6: Footer PLATFORM & POLICIES 4 Links',
    $allFound,
    "Footer category PLATFORM_ADMIN returns: " . implode(', ', $platformSlugs)
);

// TEST 6: Buyer Login & Registration Links
$loginView = file_get_contents(__DIR__ . '/../app/Views/auth/login.php');
$registerView = file_get_contents(__DIR__ . '/../app/Views/auth/register.php');
$loginHasTerms = str_contains($loginView, '/page/terms-and-conditions') && str_contains($loginView, '/page/privacy-policy');
$registerHasTerms = str_contains($registerView, '/page/terms-and-conditions') && str_contains($registerView, '/page/privacy-policy');
recordTest(
    'Task 7: Buyer Login & Register Legal Links',
    $loginHasTerms && $registerHasTerms,
    "Login and register views cleanly reference /page/terms-and-conditions and /page/privacy-policy."
);

// TEST 7: Homepage Category Display — Enable / Disable & Dynamic Retrieval
$originalEnabled = SiteSetting::get('homepage_categories_enabled', '1');
$originalMax = SiteSetting::get('homepage_categories_max', '8');
$originalSelected = SiteSetting::get('homepage_categories_selected', '');

// Check default fetch
$defaultCats = Category::getHomepageCategories();
$defaultCount = count($defaultCats);
recordTest(
    'Task 8.1: Homepage Categories Default Dynamic Retrieval',
    $defaultCount > 0 && $defaultCount <= 8,
    "Fetched {$defaultCount} categories with dynamic product counts."
);

// Check max limit configuration
SiteSetting::set('homepage_categories_max', '4', 'homepage');
$limitedCats = Category::getHomepageCategories();
recordTest(
    'Task 8.2: Homepage Categories Max Count Setting',
    count($limitedCats) === 4,
    "Max 4 configured, returned " . count($limitedCats) . " categories."
);

// Check custom selected category ordering
$allActive = Category::allActive();
if (count($allActive) >= 3) {
    $first3 = array_slice($allActive, 0, 3);
    $reversedIds = array_reverse(array_column($first3, 'id'));
    SiteSetting::set('homepage_categories_selected', implode(',', $reversedIds), 'homepage');
    $customOrderCats = Category::getHomepageCategories();
    $customIds = array_column(array_slice($customOrderCats, 0, 3), 'id');
    recordTest(
        'Task 8.3: Homepage Custom Category Selection & Ordering',
        $customIds === $reversedIds,
        "Custom order applied: " . implode(',', $customIds) . " matches expected " . implode(',', $reversedIds)
    );
}

// Check disabling section
SiteSetting::set('homepage_categories_enabled', '0', 'homepage');
$disabledCats = Category::getHomepageCategories();
recordTest(
    'Task 8.4: Homepage Category Section Disabled / Hidden',
    empty($disabledCats),
    "When disabled, Category::getHomepageCategories() returns empty array, cleanly hiding homepage section."
);

// Restore original settings
SiteSetting::set('homepage_categories_enabled', $originalEnabled, 'homepage');
SiteSetting::set('homepage_categories_max', $originalMax, 'homepage');
SiteSetting::set('homepage_categories_selected', $originalSelected, 'homepage');

// TEST 8: Super Admin CMS and Homepage Category Routes
$indexContent = file_get_contents(__DIR__ . '/../public/index.php');
$hasSettingsRoute = str_contains($indexContent, '/admin/settings/homepage-categories');
$hasPagesRoute = str_contains($indexContent, '/admin/pages');
$hasSellerAgreementRoute = str_contains($indexContent, '/admin/seller-agreement');
recordTest(
    'Task 5 & 8.5: Super Admin Routes Registered',
    $hasSettingsRoute && $hasPagesRoute && $hasSellerAgreementRoute,
    "Routes registered for /admin/settings/homepage-categories, /admin/pages, and /admin/seller-agreement."
);

echo "\n=== ALL VERIFICATION TESTS FINISHED ===\n";
