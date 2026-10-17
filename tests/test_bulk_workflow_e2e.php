<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

echo "======================================================\n";
echo "TESTING TASK 11: BULK PRODUCT UPLOAD WORKFLOWS\n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $name\n";
        if ($details) echo "        $details\n";
        $passCount++;
    } else {
        echo " [FAIL] $name\n";
        if ($details) echo "        $details\n";
        $failCount++;
    }
}

// 1. Test Categories & Subcategories models
$cats = \App\Models\Category::allActive();
assertTest("Category::allActive() returns active categories", count($cats) > 0, "Found " . count($cats) . " active categories.");

$subs = \App\Models\Subcategory::allActive();
assertTest("Subcategory::allActive() returns active subcategories", count($subs) > 0, "Found " . count($subs) . " active subcategories.");

$subsAll = \App\Models\Subcategory::all();
assertTest("Subcategory::all() returns all subcategories", count($subsAll) >= count($subs), "Found " . count($subsAll) . " subcategories.");

// 2. Test safe temp directory resolution
$tempDir = \App\Helpers\BulkProductImporter::getTempDir();
assertTest("BulkProductImporter::getTempDir() is writable", is_dir($tempDir) && is_writable($tempDir), "Temp dir: $tempDir");

// 3. Test XLSX creation with Export Packaging
$xlsxFile = \App\Helpers\BulkProductImporter::getSafeTempFile('e2e_bulk_', 'xlsx');
$catName = $cats[0]['name'];
$subName = !empty($subs[0]['name']) ? $subs[0]['name'] : '';
$headers = [
    'Product Name *',
    'Categories * (comma-separated names or IDs)',
    'Subcategories (comma-separated names or IDs)',
    'SKU',
    'Brand Name',
    'Min Price ($) *',
    'Max Price ($) *',
    'Price Unit (e.g. Piece, Set, Box)',
    'MOQ *',
    'MOQ Unit (e.g. Pieces, Sets, Boxes)',
    'Available Quantity',
    'Short Description',
    'Detailed Description',
    'Material',
    'Colors (comma-separated)',
    'Sizes (comma-separated)',
    'Weight',
    'Customization Details',
    'Custom Logo (Yes/No)',
    'Custom Color (Yes/No)',
    'Custom Packaging (Yes/No)',
    'OEM Available (Yes/No)',
    'Lead Time',
    'Export Packaging',
    'Sample Available (Yes/No)',
    'Sample Price ($)',
    'Primary Image (URL or image filename)',
    'Gallery Images (comma-separated URLs)',
];

$uniqueSku1 = 'TEST-BULK-' . time() . '-1';
$uniqueSku2 = 'TEST-BULK-' . time() . '-2';

$rows = [
    [
        'E2E Bulk Test Product Alpha',
        $catName,
        $subName,
        $uniqueSku1,
        'BrandAlpha',
        '25.00',
        '35.00',
        'Piece',
        '50',
        'Pieces',
        '2000',
        'Short description for alpha product',
        'Detailed specifications and material overview for alpha product.',
        'Carbon Composite',
        'Matte Black, Electric Blue',
        'Standard, Large',
        '450g',
        'Laser engraving available',
        'Yes',
        'Yes',
        'Yes',
        'Yes',
        '14 Days',
        '5-ply export carton with bubble wrap (60x40x30cm, 15kg)',
        'Yes',
        '45.00',
        'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=800',
        'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=800',
    ],
    [
        'E2E Bulk Test Product Beta',
        $catName,
        '',
        $uniqueSku2,
        'BrandBeta',
        '15.50',
        '22.00',
        'Piece',
        '100',
        'Pieces',
        '5000',
        'Short description for beta product',
        'Detailed specifications for beta product.',
        'High density EVA foam',
        'Red, Yellow',
        'Medium',
        '300g',
        'Silk screen printing',
        'Yes',
        'No',
        'Yes',
        'No',
        '10 Days',
        'Individual blister pack, 100 packs per sea-worthy wooden crate',
        'No',
        '',
        'https://images.unsplash.com/photo-1614632537423-1e6c2e7e0aab?w=800',
        '',
    ],
];

$createdXlsx = \App\Helpers\BulkProductImporter::createXlsxFile($xlsxFile, $headers, $rows);
assertTest("BulkProductImporter::createXlsxFile() creates valid file", $createdXlsx && file_exists($xlsxFile) && filesize($xlsxFile) > 0, "File size: " . filesize($xlsxFile) . " bytes");

// 4. Test parseAndValidate for Supplier
$supplierId = 1;
$parseResult = \App\Helpers\BulkProductImporter::parseAndValidate($xlsxFile, $supplierId, false);
assertTest("parseAndValidate() succeeds", ($parseResult['success'] ?? false) === true, "Message: " . ($parseResult['message'] ?? 'OK'));
assertTest("parseAndValidate() validates 2 rows with 0 errors", ($parseResult['valid_rows_count'] ?? 0) === 2 && ($parseResult['error_rows_count'] ?? 0) === 0, "Valid: " . ($parseResult['valid_rows_count'] ?? 0) . ", Errors: " . ($parseResult['error_rows_count'] ?? 0));
assertTest("parseAndValidate() generates a valid file_token", !empty($parseResult['file_token']), "Token: " . ($parseResult['file_token'] ?? ''));

// 5. Test Supplier commit workflow
$fileToken = $parseResult['file_token'];
$commitResult = \App\Helpers\BulkProductImporter::commit($fileToken, $supplierId, false, 1);
assertTest("commit() succeeds for Supplier", ($commitResult['success'] ?? false) === true, "Imported: " . ($commitResult['imported_count'] ?? 0) . " products.");

$importedIds = $commitResult['imported_ids'] ?? [];
assertTest("commit() returned imported product IDs", count($importedIds) === 2, "IDs: " . implode(', ', $importedIds));

// 6. Verify Supplier imported product attributes in DB
if (!empty($importedIds)) {
    $firstId = (int)$importedIds[0];
    $prod1 = \App\Models\Product::findById($firstId);
    assertTest("Supplier product is in PENDING_APPROVAL status", ($prod1['approval_status'] ?? '') === 'PENDING_APPROVAL', "Status: " . ($prod1['approval_status'] ?? ''));
    assertTest("Supplier product status is DRAFT", ($prod1['status'] ?? '') === 'DRAFT', "Status: " . ($prod1['status'] ?? ''));
    assertTest("Supplier product assigned to Supplier #$supplierId", (int)($prod1['supplier_id'] ?? 0) === $supplierId, "Supplier ID: " . ($prod1['supplier_id'] ?? 0));
    assertTest("Supplier product preserves entered Export Packaging", !empty($prod1['export_packaging']) && str_contains($prod1['export_packaging'], '5-ply export carton'), "Export Packaging: " . ($prod1['export_packaging'] ?? 'EMPTY'));
    assertTest("Supplier product wholesale price is USD", (float)($prod1['min_price'] ?? 0) === 25.00 && (float)($prod1['max_price'] ?? 0) === 35.00, "Price: $" . $prod1['min_price'] . " - $" . $prod1['max_price']);

    // Clean up test products
    foreach ($importedIds as $pid) {
        \App\Models\Product::hardDelete((int)$pid);
    }
    echo " [INFO] Cleaned up temporary test products.\n";
}

@unlink($xlsxFile);

echo "\n======================================================\n";
echo "TASK 11 RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "======================================================\n";

if ($failCount > 0) exit(1);
