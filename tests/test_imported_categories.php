<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — COMPREHENSIVE CATEGORY & SUBCATEGORY VERIFICATION TEST
 * Validates all 10 mandatory requirements for master data import.
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/Request.php';
require_once dirname(__DIR__) . '/app/Helpers/Uploader.php';
require_once dirname(__DIR__) . '/app/Helpers/Session.php';
require_once dirname(__DIR__) . '/app/Helpers/AuditLogger.php';
require_once dirname(__DIR__) . '/app/Models/Category.php';
require_once dirname(__DIR__) . '/app/Models/Subcategory.php';

use App\Core\Database;
use App\Models\Category;
use App\Models\Subcategory;

$db = Database::getConnection();

echo "=======================================================================\n";
echo "SPORTS JUNCTION — 10-POINT IMPORT & INTEGRATION VERIFICATION TEST\n";
echo "=======================================================================\n";

$sourceFile = __DIR__ . '/../categories_master_source.json';
if (!file_exists($sourceFile)) {
    echo "FAIL: categories_master_source.json not found!\n";
    exit(1);
}

$sourceData = json_decode(file_get_contents($sourceFile), true);
$passedTests = 0;
$failedTests = 0;

function assertTest(bool $condition, string $testName, string $details = ''): void {
    global $passedTests, $failedTests;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$testName}\n";
        if (!empty($details)) {
            echo "         {$details}\n";
        }
    } else {
        $failedTests++;
        echo "  [FAIL] {$testName}\n";
        if (!empty($details)) {
            echo "         {$details}\n";
        }
    }
}

// ---------------------------------------------------------------------------
// TEST 1: Compare every category name against source data (Exact matching)
// ---------------------------------------------------------------------------
echo "\n--- TEST 1: Authoritative Category Names (Exact Match) ---\n";
$catMissing = [];
$catStmt = $db->prepare("SELECT id, name, slug, status FROM categories WHERE BINARY name = :name LIMIT 1");
$sourceCatMap = [];

foreach ($sourceData as $cat) {
    $catName = $cat['name'];
    $catStmt->execute([':name' => $catName]);
    $row = $catStmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $catMissing[] = $catName;
    } else {
        $sourceCatMap[$catName] = $row;
    }
}
assertTest(
    empty($catMissing) && count($sourceCatMap) === 44,
    "All 44 categories exist with exact names, punctuation, and casing",
    empty($catMissing) ? "Verified 44/44 categories in DB." : "Missing: " . implode(', ', $catMissing)
);

// ---------------------------------------------------------------------------
// TEST 2: Compare every subcategory name and its parent against source data
// ---------------------------------------------------------------------------
echo "\n--- TEST 2: Subcategory Names & Parent Foreign Key Relationships ---\n";
$subMissing = [];
$subWrongParent = [];
$subStmt = $db->prepare("SELECT id, category_id, name, slug, status FROM subcategories WHERE BINARY name = :name AND category_id = :cid LIMIT 1");
$totalVerifiedSubs = 0;

foreach ($sourceData as $cat) {
    $catName = $cat['name'];
    $parentRow = $sourceCatMap[$catName] ?? null;
    if (!$parentRow) continue;
    $parentId = (int)$parentRow['id'];

    foreach ($cat['subcategories'] as $subName) {
        $subStmt->execute([':name' => $subName, ':cid' => $parentId]);
        $subRow = $subStmt->fetch(PDO::FETCH_ASSOC);
        if (!$subRow) {
            // Check if it exists under wrong parent
            $wrongParentStmt = $db->prepare("SELECT id, category_id FROM subcategories WHERE BINARY name = :name LIMIT 1");
            $wrongParentStmt->execute([':name' => $subName]);
            $wrongRow = $wrongParentStmt->fetch(PDO::FETCH_ASSOC);
            if ($wrongRow) {
                $subWrongParent[] = "{$subName} (found in cat {$wrongRow['category_id']}, expected in {$parentId} [{$catName}])";
            } else {
                $subMissing[] = "{$subName} (in {$catName})";
            }
        } else {
            $totalVerifiedSubs++;
        }
    }
}
assertTest(
    empty($subMissing) && empty($subWrongParent) && $totalVerifiedSubs === 132,
    "All 132 subcategories correctly linked to their designated parent category",
    "Verified: {$totalVerifiedSubs}/132. Missing: " . count($subMissing) . ", Wrong Parent: " . count($subWrongParent)
);

// ---------------------------------------------------------------------------
// TEST 3: Standalone categories integrity (No invented children)
// ---------------------------------------------------------------------------
echo "\n--- TEST 3: Standalone Categories (Zero Invented Children) ---\n";
$standaloneWithSubs = [];
$standaloneCount = 0;
$countSubsStmt = $db->prepare("SELECT COUNT(*) FROM subcategories WHERE category_id = :cid");

foreach ($sourceData as $cat) {
    if (empty($cat['subcategories'])) {
        $standaloneCount++;
        $catName = $cat['name'];
        $parentRow = $sourceCatMap[$catName] ?? null;
        if ($parentRow) {
            $countSubsStmt->execute([':cid' => $parentRow['id']]);
            $subCount = (int)$countSubsStmt->fetchColumn();
            if ($subCount > 0) {
                $standaloneWithSubs[] = "{$catName} has {$subCount} subcategories (expected 0)";
            }
        }
    }
}
assertTest(
    $standaloneCount === 23 && empty($standaloneWithSubs),
    "All 23 standalone categories have strictly 0 subcategories",
    "Standalone count: {$standaloneCount}. Violations: " . (empty($standaloneWithSubs) ? "None" : implode(', ', $standaloneWithSubs))
);

// ---------------------------------------------------------------------------
// TEST 4: Total Counts against source list
// ---------------------------------------------------------------------------
echo "\n--- TEST 4: Quantity Counts Verification ---\n";
$importedCatIds = array_column($sourceCatMap, 'id');
$placeholders = implode(',', array_fill(0, count($importedCatIds), '?'));
$totalImportedSubsInDb = (int)$db->prepare("SELECT COUNT(*) FROM subcategories WHERE category_id IN ({$placeholders})")->execute($importedCatIds);
$totalImportedSubsInDb = (int)$db->query("
    SELECT COUNT(*) FROM subcategories 
    WHERE category_id IN (" . implode(',', $importedCatIds) . ")
")->fetchColumn();

assertTest(
    count($sourceCatMap) === 44 && $totalImportedSubsInDb === 132,
    "Authoritative counts match exactly: 44 categories and 132 subcategories",
    "Database records for imported master: Categories = " . count($sourceCatMap) . ", Subcategories = {$totalImportedSubsInDb}"
);

// ---------------------------------------------------------------------------
// TEST 5: Idempotency (Zero duplicate rows upon re-execution)
// ---------------------------------------------------------------------------
echo "\n--- TEST 5: Seeder Idempotency Verification ---\n";
$catsBefore = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$subsBefore = (int)$db->query("SELECT COUNT(*) FROM subcategories")->fetchColumn();

// Include or execute seeder in silent check mode
$seederOutput = shell_exec('/Applications/XAMPP/xamppfiles/bin/php ' . escapeshellarg(__DIR__ . '/../database/seed_imported_categories.php'));

$catsAfter = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$subsAfter = (int)$db->query("SELECT COUNT(*) FROM subcategories")->fetchColumn();

$noDuplicates = ($catsBefore === $catsAfter) && ($subsBefore === $subsAfter);
assertTest(
    $noDuplicates,
    "Import script is strictly idempotent (0 duplicate categories, 0 duplicate subcategories)",
    "Categories before/after: {$catsBefore}/{$catsAfter}, Subcategories before/after: {$subsBefore}/{$subsAfter}"
);

// ---------------------------------------------------------------------------
// TEST 6: Category and Subcategory Admin CRUD Operations
// ---------------------------------------------------------------------------
echo "\n--- TEST 6: Admin CRUD Operations (Create, Read, Update, Delete) ---\n";
try {
    // 1. Create temporary category
    $testCatId = Category::create([
        'name'          => 'Test QA Category ' . time(),
        'icon'          => 'sports_soccer',
        'image'         => 'https://images.unsplash.com/test.jpg',
        'description'   => 'Automated test category',
        'status'        => 'ACTIVE',
        'display_order' => 9999
    ]);
    $catCreated = Category::findById($testCatId);
    $createPassed = ($testCatId > 0 && !empty($catCreated['slug']));

    // 2. Update category
    $updateResult = Category::update($testCatId, [
        'name'        => $catCreated['name'] . ' (Updated)',
        'description' => 'Updated automated description'
    ]);
    $catUpdated = Category::findById($testCatId);
    $updatePassed = ($updateResult && str_ends_with($catUpdated['name'], '(Updated)'));

    // 3. Create subcategory under test category
    $testSubId = Subcategory::create([
        'category_id'   => $testCatId,
        'name'          => 'Test QA Subcategory ' . time(),
        'description'   => 'Automated test subcategory',
        'status'        => 'ACTIVE',
        'display_order' => 1
    ]);
    $subCreated = Subcategory::findById($testSubId);
    $subCreatePassed = ($testSubId > 0 && (int)$subCreated['category_id'] === $testCatId);

    // 4. Update subcategory
    $subUpdateResult = Subcategory::update($testSubId, [
        'name' => $subCreated['name'] . ' (Updated)'
    ]);
    $subUpdated = Subcategory::findById($testSubId);
    $subUpdatePassed = ($subUpdateResult && str_ends_with($subUpdated['name'], '(Updated)'));

    // 5. Delete subcategory then category
    $subDeleteResult = Subcategory::hardDelete($testSubId);
    $catDeleteResult = Category::hardDelete($testCatId, true);
    $deletePassed = (($subDeleteResult['success'] ?? false) && ($catDeleteResult['success'] ?? false));

    $crudPassed = $createPassed && $updatePassed && $subCreatePassed && $subUpdatePassed && $deletePassed;
    assertTest(
        $crudPassed,
        "Category and Subcategory CRUD operations function flawlessly",
        "Create Cat: " . ($createPassed ? 'OK' : 'FAIL') .
        ", Update Cat: " . ($updatePassed ? 'OK' : 'FAIL') .
        ", Create Sub: " . ($subCreatePassed ? 'OK' : 'FAIL') .
        ", Update Sub: " . ($subUpdatePassed ? 'OK' : 'FAIL') .
        ", Delete Both: " . ($deletePassed ? 'OK' : 'FAIL')
    );
} catch (\Throwable $e) {
    assertTest(false, "CRUD Operations Exception", $e->getMessage());
}

// ---------------------------------------------------------------------------
// TEST 7: Public Category Dropdown & Navigation Hierarchy
// ---------------------------------------------------------------------------
echo "\n--- TEST 7: Public Navigation & URL Generation ---\n";
$allActiveCats = Category::allActive();
$activeCount = count($allActiveCats);
$foundAgility = false;
$foundBoardGames = false;
$agilitySubsCount = 0;

foreach ($allActiveCats as $c) {
    if ($c['name'] === 'Agility & Speed Training Equipment') {
        $foundAgility = true;
        $agilitySubsCount = count($c['subcategories']);
    }
    if ($c['name'] === 'Board Games') {
        $foundBoardGames = true;
    }
}

// Test findBySlug for public routes
$catBySlug = Category::findBySlug('board-games');
$subBySlug = Subcategory::findBySlug('chess-boards');
$slugRoutePassed = ($catBySlug !== null && (int)$catBySlug['id'] === (int)$sourceCatMap['Board Games']['id'])
                && ($subBySlug !== null && (int)$subBySlug['category_id'] === (int)$sourceCatMap['Board Games']['id']);

assertTest(
    $foundAgility && $foundBoardGames && $agilitySubsCount === 12 && $slugRoutePassed,
    "Public Category Dropdown and Flyout URL generation verified",
    "Active Categories loaded: {$activeCount}. Agility subcategories: {$agilitySubsCount}/12. Slug lookup verified."
);

// ---------------------------------------------------------------------------
// TEST 8: Product Create/Edit Dropdowns & Listing Filters
// ---------------------------------------------------------------------------
echo "\n--- TEST 8: Product Form Dropdowns & Category Filters ---\n";
// Subcategory::byCategoryActive
$athleticsCatId = (int)$sourceCatMap['Athletics Track & Field Equipment']['id'];
$athleticsSubs = Subcategory::byCategoryActive($athleticsCatId);

$carromCatId = (int)$sourceCatMap['Carrom Board']['id'];
$carromSubs = Subcategory::byCategoryActive($carromCatId);

assertTest(
    count($athleticsSubs) === 19 && count($carromSubs) === 0,
    "Product form subcategory dynamic loading returns accurate options",
    "Athletics Track & Field dropdown options: " . count($athleticsSubs) . "/19. Carrom Board dropdown options: " . count($carromSubs) . "/0."
);

// ---------------------------------------------------------------------------
// TEST 9: Existing Product Relationships Preservation
// ---------------------------------------------------------------------------
echo "\n--- TEST 9: Existing Product Relationships Preservation ---\n";
$totalProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$productsWithCategory = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id IS NOT NULL")->fetchColumn();
$catAssignments = (int)$db->query("SELECT COUNT(*) FROM product_category_assignments")->fetchColumn();
$subAssignments = (int)$db->query("SELECT COUNT(*) FROM product_subcategory_assignments")->fetchColumn();

// Check that products link to valid existing categories
$orphanedProducts = (int)$db->query("
    SELECT COUNT(*) FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id IS NOT NULL AND c.id IS NULL
")->fetchColumn();

assertTest(
    $totalProducts === 23 && $catAssignments >= 27 && $subAssignments >= 25 && $orphanedProducts === 0,
    "All existing products, category assignments, and subcategory assignments intact",
    "Total products: {$totalProducts}, Cat assignments: {$catAssignments}, Subcat assignments: {$subAssignments}, Orphaned: {$orphanedProducts}"
);

// ---------------------------------------------------------------------------
// TEST 10: DataTables API Verification
// ---------------------------------------------------------------------------
echo "\n--- TEST 10: Admin DataTables Master APIs ---\n";
$catDt = Category::getDataTable(['start' => 0, 'length' => 10, 'search' => ['value' => 'Equipment']]);
$subDt = Subcategory::getDataTable(['start' => 0, 'length' => 10, 'search' => ['value' => 'Poles']]);

$catDtOk = isset($catDt['recordsTotal'], $catDt['data']) && count($catDt['data']) > 0;
$subDtOk = isset($subDt['recordsTotal'], $subDt['data']) && count($subDt['data']) > 0;

assertTest(
    $catDtOk && $subDtOk,
    "Admin Category and Subcategory DataTables APIs return filtered results correctly",
    "Category DT search 'Equipment': {$catDt['recordsFiltered']} found. Subcategory DT search 'Poles': {$subDt['recordsFiltered']} found."
);

// ---------------------------------------------------------------------------
// SUMMARY
// ---------------------------------------------------------------------------
echo "\n=======================================================================\n";
echo "TEST RESULTS SUMMARY: {$passedTests} PASSED, {$failedTests} FAILED\n";
echo "=======================================================================\n";

if ($failedTests > 0) {
    exit(1);
}
echo "ALL 10 TESTS PASSED SUCCESSFULLY!\n";
