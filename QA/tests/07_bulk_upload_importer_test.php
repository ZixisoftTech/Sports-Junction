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

echo "=== TEST SUITE 7: BULK PRODUCT UPLOAD & ROW VALIDATION ===\n";

$pdo = \App\Core\Database::getConnection();
$supplier = $pdo->query("SELECT id FROM suppliers WHERE status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$supplierId = $supplier ? (int)$supplier['id'] : 1;
$category = $pdo->query("SELECT id, name FROM categories WHERE status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$catName = $category['name'] ?? 'Cricket';

$tmpDir = sys_get_temp_dir();

// 1. Test Valid CSV File Parsing
$validCsvPath = $tmpDir . '/valid_products_test.csv';
$csvContent = "Product Name,Category Name,Subcategory Name,SKU,Min Price,Max Price,Price Unit,MOQ,MOQ Unit,Lead Time Days,Description\n";
$csvContent .= "\"QA Test Tennis Racket\",\"{$catName}\",\"\",\"SKU-QA-001\",25.00,35.00,\"Piece\",50,\"Piece\",15,\"Professional graphite tennis racket\"\n";
$csvContent .= "\"QA Test Leather Match Ball\",\"{$catName}\",\"\",\"SKU-QA-002\",12.50,15.00,\"Piece\",100,\"Piece\",10,\"Hand stitched four piece cricket ball\"\n";
file_put_contents($validCsvPath, $csvContent);

$validResult = \App\Helpers\BulkProductImporter::parseAndValidate($validCsvPath, $supplierId, false);
assertTest("Valid CSV file parsed successfully", !empty($validResult['success']), "Parsing failed: " . ($validResult['message'] ?? ''));
assertTest("Valid rows count is 2", ($validResult['valid_rows_count'] ?? 0) === 2, "Valid count was: " . ($validResult['valid_rows_count'] ?? 0));
assertTest("Error rows count is 0", ($validResult['error_rows_count'] ?? 0) === 0);

// 2. Test Invalid CSV File (Missing required title, invalid price, invalid MOQ)
$invalidCsvPath = $tmpDir . '/invalid_products_test.csv';
$invContent = "Product Name,Category Name,Subcategory Name,SKU,Min Price,Max Price,Price Unit,MOQ,MOQ Unit,Lead Time Days,Description\n";
// Row 2: Empty product name
$invContent .= "\"\",\"{$catName}\",\"\",\"SKU-QA-ERR1\",10.00,20.00,\"Piece\",10,\"Piece\",5,\"Missing name\"\n";
// Row 3: Min price > Max price
$invContent .= "\"Negative Spread Product\",\"{$catName}\",\"\",\"SKU-QA-ERR2\",50.00,20.00,\"Piece\",10,\"Piece\",5,\"Bad price spread\"\n";
// Row 4: Non-existent category
$invContent .= "\"Unmapped Category Item\",\"NON_EXISTENT_CATEGORY_XYZ_999\",\"\",\"SKU-QA-ERR3\",15.00,25.00,\"Piece\",10,\"Piece\",5,\"Wrong category\"\n";
// Row 5: Zero/Negative MOQ
$invContent .= "\"Zero MOQ Item\",\"{$catName}\",\"\",\"SKU-QA-ERR4\",15.00,25.00,\"Piece\",0,\"Piece\",5,\"Invalid MOQ\"\n";
file_put_contents($invalidCsvPath, $invContent);

$invalidResult = \App\Helpers\BulkProductImporter::parseAndValidate($invalidCsvPath, $supplierId, false);
assertTest("Invalid CSV parsed with row-level error reporting", isset($invalidResult['error_rows_count']), "Error count missing in result");
assertTest("Detected invalid rows", ($invalidResult['error_rows_count'] ?? 0) >= 3, "Expected at least 3 invalid rows, got: " . ($invalidResult['error_rows_count'] ?? 0));
assertTest("Row error messages populated", !empty($invalidResult['errors']), "No errors array returned");

// Check specific error messages
$hasNameErr = false;
$hasPriceErr = false;
$hasCatErr = false;
foreach ($invalidResult['errors'] ?? [] as $ir) {
    $errs = implode(' ', $ir['errors'] ?? []);
    if (str_contains(strtolower($errs), 'name') || str_contains(strtolower($errs), 'title')) $hasNameErr = true;
    if (str_contains(strtolower($errs), 'price')) $hasPriceErr = true;
    if (str_contains(strtolower($errs), 'category')) $hasCatErr = true;
}
assertTest("Missing title flagged in row errors", $hasNameErr);
assertTest("Price spread mismatch flagged in row errors", $hasPriceErr);
assertTest("Invalid category flagged in row errors", $hasCatErr);

// 3. Test Missing Mandatory Columns (Corrupt header)
$corruptCsvPath = $tmpDir . '/corrupt_header_test.csv';
file_put_contents($corruptCsvPath, "RandomHeader1,RandomHeader2,SomeText\nVal1,Val2,Val3\n");
$corruptResult = \App\Helpers\BulkProductImporter::parseAndValidate($corruptCsvPath, $supplierId, false);
assertTest("Corrupt header results in 0 valid rows", ($corruptResult['valid_rows_count'] ?? -1) === 0 && ($corruptResult['error_rows_count'] ?? 0) > 0);

// Cleanup temp files
@unlink($validCsvPath);
@unlink($invalidCsvPath);
@unlink($corruptCsvPath);

echo "\nSuite 7 Results: $passedTests PASSED, $failedTests FAILED.\n";
if (!empty($bugs)) {
    echo "Bugs logged:\n";
    print_r($bugs);
}
