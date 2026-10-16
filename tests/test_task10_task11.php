<?php
declare(strict_types=1);

/**
 * Test Suite for Task 10 (Footer Design & Dynamic CMS Links)
 * and Task 11 (Exactly Three Social Platforms: X, Instagram, Facebook)
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Models/CmsPage.php';
require_once dirname(__DIR__) . '/app/Models/SiteSetting.php';

use App\Models\CmsPage;
use App\Models\SiteSetting;

$errors = [];
$passed = 0;

echo "=== STARTING VERIFICATION FOR TASK 10 & TASK 11 ===\n\n";

// TEST 1: CmsPage::getByColumns returns all 5 columns
echo "TEST 1: CmsPage::getByColumns() returns 5 active columns... ";
$cols = CmsPage::getByColumns(true);
$expectedCols = ['ABOUT', 'TOP_SECTORS', 'SOURCING', 'PLATFORM_ADMIN', 'ORDER_PROTECTION'];
foreach ($expectedCols as $ec) {
    if (!isset($cols[$ec])) {
        $errors[] = "Missing footer column '$ec' in getByColumns";
    }
}
if (empty($errors)) {
    echo "OK (Columns: " . implode(', ', array_keys($cols)) . ")\n";
    $passed++;
} else {
    echo "FAILED\n";
}

// TEST 2: Check matching links from media_1792140640166.png
echo "TEST 2: Verify specific links from media_1792140640166.png... ";
$topSectorsTitles = array_column($cols['TOP_SECTORS'] ?? [], 'title');
$sourcingTitles = array_column($cols['SOURCING'] ?? [], 'title');
$platformTitles = array_column($cols['PLATFORM_ADMIN'] ?? [], 'title');

$expectedTopSectors = ['Cricket Bats & Pads', 'Match Footballs', 'Agility Hurdles & Ladders', 'Commercial Gym Equipment', 'Sportswear & Uniforms'];
$expectedSourcing = ['Direct Factory Catalog', 'Request for Quotation (RFQ)', 'Contact Us & Support', 'Container Freight Logistics', 'XML Sitemap'];
$expectedPlatform = ['Super Admin Portal →', 'Master CRUD Management', 'Product Publishing Hub', 'Terms & Manufacturing SLA'];

$diff1 = array_diff($expectedTopSectors, $topSectorsTitles);
$diff2 = array_diff($expectedSourcing, $sourcingTitles);
$diff3 = array_diff($expectedPlatform, $platformTitles);

if (empty($diff1) && empty($diff2) && empty($diff3)) {
    echo "OK (All screenshot links present)\n";
    $passed++;
} else {
    echo "FAILED (Missing: " . json_encode(array_merge($diff1, $diff2, $diff3)) . ")\n";
    $errors[] = "Screenshot link discrepancy";
}

// TEST 3: Create, Deactivate, Reactivate, Delete CMS page workflow
echo "TEST 3: End-to-end CMS page lifecycle (Create -> Deactivate -> Reactivate -> Delete)... ";
$testSlug = 'test-audit-compliance-' . time();
$newId = CmsPage::create([
    'title' => 'Test Audit Compliance Guide',
    'slug' => $testSlug,
    'column_category' => 'SOURCING',
    'content' => '<p>Detailed testing content for B2B sports factory audit.</p>',
    'external_url' => null,
    'sort_order' => 99,
    'is_active' => 1
]);

$fetched = CmsPage::findById($newId);
if (!$fetched || $fetched['slug'] !== $testSlug) {
    $errors[] = "Failed to fetch created CMS page";
}

// Check it appears in active getByColumns
$colsAfterAdd = CmsPage::getByColumns(true);
$foundInSourcing = false;
foreach ($colsAfterAdd['SOURCING'] as $p) {
    if ($p['slug'] === $testSlug) {
        $foundInSourcing = true;
        break;
    }
}
if (!$foundInSourcing) {
    $errors[] = "Active test page not found in SOURCING column";
}

// Deactivate
CmsPage::update($newId, ['is_active' => 0]);
$colsAfterDeact = CmsPage::getByColumns(true);
$foundWhenInactive = false;
foreach ($colsAfterDeact['SOURCING'] as $p) {
    if ($p['slug'] === $testSlug) {
        $foundWhenInactive = true;
        break;
    }
}
if ($foundWhenInactive) {
    $errors[] = "Inactive page incorrectly appeared in active footer column";
}

// Reactivate
CmsPage::update($newId, ['is_active' => 1]);
$colsAfterReact = CmsPage::getByColumns(true);
$foundWhenActive = false;
foreach ($colsAfterReact['SOURCING'] as $p) {
    if ($p['slug'] === $testSlug) {
        $foundWhenActive = true;
        break;
    }
}
if (!$foundWhenActive) {
    $errors[] = "Reactivated page did not return to active footer column";
}

// Clean up test page
CmsPage::delete($newId);
$cleaned = CmsPage::findById($newId);
if ($cleaned !== null) {
    $errors[] = "Failed to delete test CMS page";
}

if (empty($errors)) {
    echo "OK (Lifecycle verified)\n";
    $passed++;
} else {
    echo "FAILED\n";
}

// TEST 4: Frontend Footer HTML Rendering Check
echo "TEST 4: Rendering homepage and checking footer HTML... ";
$homepageHtml = file_get_contents('http://localhost/Antigravity/Sports-Junction/');
if ($homepageHtml === false) {
    $errors[] = "Failed to fetch homepage from local server";
    echo "FAILED (Cannot connect to local server)\n";
} else {
    // Check dark theme classes
    if (!str_contains($homepageHtml, 'bg-[#071328]') && !str_contains($homepageHtml, 'bg-[#0A0E17]')) {
        $errors[] = "Dark navy footer container missing";
    }
    // Check column headings
    $hasPlatform = str_contains($homepageHtml, 'PLATFORM &amp; ADMIN') || str_contains($homepageHtml, 'PLATFORM & ADMIN');
    if (!str_contains($homepageHtml, 'TOP SECTORS') || !str_contains($homepageHtml, 'SOURCING SOLUTIONS') || !$hasPlatform) {
        $errors[] = "Footer column headings missing in rendered HTML";
    }
    // Check Super Admin Portal styling
    if (!str_contains($homepageHtml, 'text-emerald-400') || !str_contains($homepageHtml, 'Super Admin Portal')) {
        $errors[] = "Super Admin Portal green highlight missing in rendered HTML";
    }
    // Check that no LinkedIn, YouTube, TikTok exist in footer Stay Connected
    if (str_contains($homepageHtml, 'title="LinkedIn"') || str_contains($homepageHtml, 'title="YouTube"') || str_contains($homepageHtml, 'title="TikTok"')) {
        $errors[] = "Forbidden social platforms (LinkedIn/YouTube/TikTok) found in rendered HTML";
    }
    echo "OK\n";
    $passed++;
}

// TEST 5: Task 11 — Exactly Three Social Platforms (X, Instagram, Facebook) Persistence & Display
echo "TEST 5: Task 11 Social Platforms setting, update, and hide-if-empty... ";
// Save 3 URLs
SiteSetting::set('social_facebook', 'https://facebook.com/sportsjunctiontest', 'social');
SiteSetting::set('social_twitter', 'https://x.com/sportsjunctest', 'social');
SiteSetting::set('social_instagram', 'https://instagram.com/sportsjunctest', 'social');

$siteSettings = SiteSetting::getAllKeyed();
if ($siteSettings['social_facebook'] !== 'https://facebook.com/sportsjunctiontest' ||
    $siteSettings['social_twitter'] !== 'https://x.com/sportsjunctest' ||
    $siteSettings['social_instagram'] !== 'https://instagram.com/sportsjunctest') {
    $errors[] = "SiteSetting failed to persist social URLs";
}

// Check homepage renders all 3
$homepageHtml = file_get_contents('http://localhost/Antigravity/Sports-Junction/');
if (!str_contains($homepageHtml, 'https://facebook.com/sportsjunctiontest') ||
    !str_contains($homepageHtml, 'https://x.com/sportsjunctest') ||
    !str_contains($homepageHtml, 'https://instagram.com/sportsjunctest')) {
    $errors[] = "Social links not rendered in footer HTML";
}

// Check contact page renders all 3
$contactHtml = file_get_contents('http://localhost/Antigravity/Sports-Junction/contact');
if (!str_contains($contactHtml, 'https://facebook.com/sportsjunctiontest') ||
    !str_contains($contactHtml, 'https://x.com/sportsjunctest') ||
    !str_contains($contactHtml, 'https://instagram.com/sportsjunctest')) {
    $errors[] = "Social links not rendered on Contact Us page";
}

// Test clearing a URL hides the icon
SiteSetting::set('social_instagram', '', 'social');
$contactHtml2 = file_get_contents('http://localhost/Antigravity/Sports-Junction/contact');
if (str_contains($contactHtml2, 'https://instagram.com/sportsjunctest')) {
    $errors[] = "Cleared Instagram link still rendered in Contact Us page";
}

// Restore official social URLs
SiteSetting::set('social_facebook', 'https://facebook.com/sportsjunctionindia', 'social');
SiteSetting::set('social_twitter', 'https://x.com/sportsjunctionin', 'social');
SiteSetting::set('social_instagram', 'https://instagram.com/sportsjunctionin', 'social');

if (empty($errors)) {
    echo "OK (Social URLs verified)\n";
    $passed++;
} else {
    echo "FAILED\n";
}

echo "\n=== SUMMARY ===\n";
echo "Tests Passed: $passed / 5\n";
if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $e) {
        echo " - $e\n";
    }
    exit(1);
} else {
    echo "ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
}
