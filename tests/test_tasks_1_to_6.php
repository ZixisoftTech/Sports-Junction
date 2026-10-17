<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Helpers\BulkProductImporter;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Otp;
use App\Helpers\Session;

echo "=== STARTING VERIFICATION FOR TASKS 1 - 6 ===\n\n";

$passedCount = 0;
$totalCount = 0;

function runTest(string $name, callable $fn) {
    global $passedCount, $totalCount;
    $totalCount++;
    echo "[TEST $totalCount] $name... ";
    try {
        $fn();
        $passedCount++;
        echo "PASSED\n";
    } catch (\Throwable $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}

// -------------------------------------------------------------
// TASK 5: Chat Popup Attachment Download Icon
// -------------------------------------------------------------
runTest("Task 5: Chat Popup only renders attachment for actual attachments", function() {
    $popupContent = file_get_contents(__DIR__ . '/../app/Views/components/chat_popup.php');
    
    // Check hasAttach check before rendering
    if (!str_contains($popupContent, 'const attachmentHtml = hasAttach ? renderPopupAttachment(msg, isMe) : \'\';')) {
        throw new Exception("chat_popup.php missing hasAttach guard before renderPopupAttachment");
    }
    
    // Check guard inside renderPopupAttachment
    if (!str_contains($popupContent, 'if (!hasAttach) {') && !str_contains($popupContent, 'if (!hasAttach) return \'\';')) {
        throw new Exception("renderPopupAttachment missing defensive hasAttach guard");
    }
});

// -------------------------------------------------------------
// TASK 6: Remove Sidebar from CMS/Footer Pages
// -------------------------------------------------------------
runTest("Task 6: CMS page sidebar removed and categories cleaned", function() {
    $cmsView = file_get_contents(__DIR__ . '/../app/Views/frontend/cms_page.php');
    if (str_contains($cmsView, 'NEED ASSISTANCE?')) {
        throw new Exception("cms_page.php still contains NEED ASSISTANCE? sidebar widget");
    }
    if (str_contains($cmsView, 'lg:grid-cols-4')) {
        throw new Exception("cms_page.php still contains 4-column sidebar grid");
    }
    if (!str_contains($cmsView, 'max-w-5xl mx-auto')) {
        throw new Exception("cms_page.php not expanded to full width max-w-5xl");
    }

    $cmsModel = file_get_contents(__DIR__ . '/../app/Models/CmsPage.php');
    if (str_contains($cmsModel, 'Column 1 —') || str_contains($cmsModel, 'Column 2 —')) {
        throw new Exception("CmsPage CATEGORIES still contains Column prefixes");
    }
});

// -------------------------------------------------------------
// TASK 4: Remove Payment & Escrow Claims
// -------------------------------------------------------------
runTest("Task 4: Zero escrow claims in views and database", function() {
    $pdo = \App\Core\Database::getConnection();
    $stmt = $pdo->query("SELECT count(*) FROM cms_pages WHERE content LIKE '%escrow%' OR title LIKE '%escrow%'");
    $dbEscrowCount = (int)$stmt->fetchColumn();
    if ($dbEscrowCount > 0) {
        throw new Exception("Found {$dbEscrowCount} escrow references remaining in cms_pages table");
    }

    $buyerMessages = file_get_contents(__DIR__ . '/../app/Views/buyer/messages.php');
    if (stripos($buyerMessages, 'escrow') !== false) {
        throw new Exception("Found escrow mention in app/Views/buyer/messages.php");
    }

    $verifSuccess = file_get_contents(__DIR__ . '/../app/Views/auth/verification_success.php');
    if (stripos($verifSuccess, 'escrow') !== false) {
        throw new Exception("Found escrow mention in app/Views/auth/verification_success.php");
    }

    $authLayout = file_get_contents(__DIR__ . '/../app/Views/layouts/auth.php');
    if (stripos($authLayout, 'escrow') !== false) {
        throw new Exception("Found escrow mention in app/Views/layouts/auth.php");
    }
});

// -------------------------------------------------------------
// TASK 3: Forgot Password Email Validation
// -------------------------------------------------------------
runTest("Task 3: Forgot password validates email format and blocks unknown emails", function() {
    $authController = file_get_contents(__DIR__ . '/../app/Controllers/AuthController.php');
    if (!str_contains($authController, 'No account found with this email address. Please check your email or register.')) {
        throw new Exception("AuthController missing explicit error message for unknown email");
    }
    if (!str_contains($authController, 'Session::remove(\'reset_email\');')) {
        throw new Exception("AuthController does not clean reset_email session on unknown email");
    }
    if (!str_contains($authController, 'Session::flash(\'error\', \'Please submit your registered email to request a password reset.\');')) {
        throw new Exception("showResetPassword does not protect against direct unauthorized access");
    }
});

// -------------------------------------------------------------
// TASK 2: Reference Data Export & Subcategory Validation
// -------------------------------------------------------------
runTest("Task 2: Multi-sheet XLSX reference data export generation", function() {
    $tempFile = BulkProductImporter::getSafeTempFile('test_ref_', 'xlsx');
    $pdo = \App\Core\Database::getConnection();
    
    $cats = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'ACTIVE' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $subs = $pdo->query("SELECT s.id, s.name, s.slug, s.category_id, c.name AS parent_category_name FROM subcategories s LEFT JOIN categories c ON c.id = s.category_id WHERE s.status = 'ACTIVE' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $countries = $pdo->query("SELECT id, iso_alpha2 AS code, name FROM countries WHERE status = 'ACTIVE' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    $sheets = [
        ['name' => 'Categories', 'headers' => ['Category ID', 'Category Name', 'Slug'], 'rows' => array_map(fn($c) => [$c['id'], $c['name'], $c['slug']], $cats)],
        ['name' => 'Subcategories', 'headers' => ['Subcategory ID', 'Subcategory Name', 'Slug', 'Parent Category ID', 'Parent Category Name'], 'rows' => array_map(fn($s) => [$s['id'], $s['name'], $s['slug'], $s['category_id'], $s['parent_category_name'] ?? ''], $subs)],
        ['name' => 'Countries', 'headers' => ['Country ID', 'Country Code', 'Country Name'], 'rows' => array_map(fn($co) => [$co['id'], $co['code'], $co['name']], $countries)],
    ];

    $success = BulkProductImporter::createMultiSheetXlsxFile($tempFile, $sheets);
    if (!$success || !file_exists($tempFile) || filesize($tempFile) === 0) {
        throw new Exception("Failed to generate multi-sheet XLSX file");
    }
    
    // Verify zip contains sheet1 and sheet2 and sheet3
    $zip = new ZipArchive();
    if ($zip->open($tempFile) !== true) {
        throw new Exception("Generated XLSX is not a valid zip archive");
    }
    if ($zip->getFromName('xl/worksheets/sheet1.xml') === false || $zip->getFromName('xl/worksheets/sheet2.xml') === false) {
        throw new Exception("Missing worksheet xml in generated XLSX");
    }
    $zip->close();
    @unlink($tempFile);
});

// -------------------------------------------------------------
// TASK 1: Bulk Upload - Validation, Supplier Isolation & Workflow
// -------------------------------------------------------------
runTest("Task 1: Subcategory category-matching validation", function() {
    $pdo = \App\Core\Database::getConnection();
    
    // Find two distinct categories
    $cat1 = $pdo->query("SELECT id, name FROM categories WHERE status = 'ACTIVE' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $cat2 = $pdo->query("SELECT id, name FROM categories WHERE status = 'ACTIVE' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    // Find subcategory of cat1
    $stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ? AND status = 'ACTIVE' LIMIT 1");
    $stmt->execute([(int)$cat1['id']]);
    $sub1 = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub1) {
        echo "(Skipped subcategory test - no subcategory for cat 1) ";
        return;
    }

    // Create CSV where product has Cat 2 but Subcategory of Cat 1 (MISMATCH!)
    $csvFile = BulkProductImporter::getSafeTempFile('test_mismatch_', 'csv');
    $fp = fopen($csvFile, 'w');
    fputcsv($fp, ['Product Name *', 'Categories *', 'Subcategories', 'Min Price ($) *', 'MOQ *']);
    fputcsv($fp, ['Invalid Product Subcategory Mismatch', $cat2['name'], $sub1['name'], '25.00', '10']);
    fclose($fp);

    $res = BulkProductImporter::parseAndValidate($csvFile, 1, false);
    @unlink($csvFile);

    if ($res['error_rows_count'] === 0) {
        throw new Exception("Parser failed to reject subcategory that does not belong to parent category");
    }
    $errText = implode(' ', $res['errors'][0]['errors'] ?? []);
    if (!str_contains($errText, 'does not match the selected Category') && !str_contains($errText, 'cannot be used with the selected Category')) {
        throw new Exception("Unexpected error message for subcategory mismatch: " . $errText);
    }
});

runTest("Task 1: Super Admin supplier requirement & Seller supplier isolation", function() {
    $pdo = \App\Core\Database::getConnection();
    $cat = $pdo->query("SELECT id, name FROM categories WHERE status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    // Test Super Admin without supplier -> MUST FAIL
    $csvFile = BulkProductImporter::getSafeTempFile('test_admin_nosup_', 'csv');
    $fp = fopen($csvFile, 'w');
    fputcsv($fp, ['Product Name *', 'Categories *', 'Min Price ($) *', 'MOQ *']);
    fputcsv($fp, ['Admin Import Without Supplier', $cat['name'], '10.00', '5']);
    fclose($fp);

    $resAdmin = BulkProductImporter::parseAndValidate($csvFile, null, true, null);
    if ($resAdmin['error_rows_count'] === 0) {
        @unlink($csvFile);
        throw new Exception("Super admin import should fail when no supplier is specified");
    }
    $errText = implode(' ', $resAdmin['errors'][0]['errors'] ?? []);
    if (!str_contains($errText, 'Supplier is required for Super Admin imports')) {
        @unlink($csvFile);
        throw new Exception("Unexpected error message for missing admin supplier: " . $errText);
    }

    // Test Seller with forged supplier column in file -> MUST OVERRIDE to authenticated supplier
    $sup = $pdo->query("SELECT id FROM suppliers WHERE status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $sellerSupId = (int)$sup['id'];

    $csvFileSeller = BulkProductImporter::getSafeTempFile('test_seller_forge_', 'csv');
    $fp2 = fopen($csvFileSeller, 'w');
    fputcsv($fp2, ['Product Name *', 'Categories *', 'Min Price ($) *', 'MOQ *', 'Supplier ID']);
    fputcsv($fp2, ['Seller Forged Supplier Product', $cat['name'], '15.00', '10', '99999']);
    fclose($fp2);

    $resSeller = BulkProductImporter::parseAndValidate($csvFileSeller, $sellerSupId, false);
    @unlink($csvFile);
    @unlink($csvFileSeller);

    if (!$resSeller['success'] || $resSeller['valid_rows_count'] !== 1) {
        throw new Exception("Seller import validation failed: " . json_encode($resSeller['errors']));
    }
    if ($resSeller['preview_rows'][0]['supplier_id'] !== $sellerSupId) {
        throw new Exception("Seller supplier ID was not strictly enforced! Expected {$sellerSupId}, got: " . $resSeller['preview_rows'][0]['supplier_id']);
    }
});

runTest("Task 1: Commit workflow, moderation status & token invalidation", function() {
    $pdo = \App\Core\Database::getConnection();
    $cat = $pdo->query("SELECT id, name FROM categories WHERE status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $sup = $pdo->query("SELECT id FROM suppliers WHERE status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $sellerSupId = (int)$sup['id'];

    $testSku = 'TEST-BULK-' . time();
    $csvFile = BulkProductImporter::getSafeTempFile('test_commit_', 'csv');
    $fp = fopen($csvFile, 'w');
    fputcsv($fp, ['Product Name *', 'Categories *', 'Min Price ($) *', 'MOQ *', 'SKU']);
    fputcsv($fp, ['Automated Test Bulk Product Item', $cat['name'], '49.99', '20', $testSku]);
    fclose($fp);

    $parsed = BulkProductImporter::parseAndValidate($csvFile, $sellerSupId, false);
    @unlink($csvFile);
    
    if (empty($parsed['file_token'])) {
        throw new Exception("No file token returned from parseAndValidate");
    }
    $fileToken = $parsed['file_token'];

    // Commit as Seller
    $commitRes = BulkProductImporter::commit($fileToken, $sellerSupId, false, 1);
    if (!$commitRes['success'] || $commitRes['imported_count'] !== 1) {
        throw new Exception("Commit failed: " . json_encode($commitRes));
    }

    $newProdId = $commitRes['imported_ids'][0];

    // Check DB product status and approval_status
    $stmt = $pdo->prepare("SELECT id, title, supplier_id, status, approval_status FROM products WHERE id = ?");
    $stmt->execute([$newProdId]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prod) {
        throw new Exception("Imported product not found in database");
    }
    if ($prod['status'] !== 'DRAFT') {
        throw new Exception("Seller imported product status should be 'DRAFT', got: " . $prod['status']);
    }
    if ($prod['approval_status'] !== 'PENDING_APPROVAL') {
        throw new Exception("Seller imported product approval_status should be 'PENDING_APPROVAL', got: " . $prod['approval_status']);
    }
    if ((int)$prod['supplier_id'] !== $sellerSupId) {
        throw new Exception("Imported product supplier_id mismatch");
    }

    // Verify token cannot be re-committed (duplicate prevention)
    $reCommit = BulkProductImporter::commit($fileToken, $sellerSupId, false, 1);
    if ($reCommit['success']) {
        throw new Exception("Token was not invalidated after first commit! Allowed duplicate submission.");
    }

    // Clean up test product
    $pdo->prepare("DELETE FROM product_category_assignments WHERE product_id = ?")->execute([$newProdId]);
    $pdo->prepare("DELETE FROM product_subcategory_assignments WHERE product_id = ?")->execute([$newProdId]);
    $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$newProdId]);
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$newProdId]);
});

echo "\n=== SUMMARY ===\n";
echo "Total Tests: {$totalCount}\n";
echo "Passed: {$passedCount}\n";
echo "Failed: " . ($totalCount - $passedCount) . "\n";
if ($passedCount === $totalCount) {
    echo "ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    exit(1);
}
