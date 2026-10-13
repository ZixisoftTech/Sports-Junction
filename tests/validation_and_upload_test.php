<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

// Register PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Helpers\CSRF;
use App\Helpers\Session;
use App\Helpers\Uploader;
use App\Helpers\Validator;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;

echo "=======================================================================\n";
echo " SPORTS JUNCTION — VALIDATION, UPLOAD & 422 ERROR HANDLING TESTS\n";
echo "=======================================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $title, ?string $error = null): void {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$title}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$title}\n";
        if ($error) echo "         Error: {$error}\n";
        $failed++;
    }
}

// 1. Test Validator for numeric and integer
echo "--- 1. Validator Integer & Numeric Rules ---\n";
$valTest1 = Validator::make([
    'category_id' => 'abc',
    'min_price'   => 'not-a-number',
    'moq'         => '15',
]);
$rules1 = [
    'category_id' => 'required|integer',
    'min_price'   => 'required|numeric',
    'moq'         => 'required|integer',
];
$isValid1 = $valTest1->validate($rules1);
assertTest(!$isValid1 && count($valTest1->errors()) === 2, "Rejects non-integer and non-numeric values correctly");

$valTest2 = Validator::make([
    'category_id' => '12',
    'min_price'   => '499.50',
    'moq'         => '10',
]);
$isValid2 = $valTest2->validate($rules1);
assertTest($isValid2 && empty($valTest2->errors()), "Accepts valid integer strings and decimal numbers");

// 2. Test CSRF validateOrFail
echo "\n--- 2. CSRF validateOrFail Method ---\n";
Session::start();
$token = CSRF::getToken();
assertTest(CSRF::validate($token), "Validates active CSRF token");
assertTest(!CSRF::validate('invalid-token-12345'), "Rejects spoofed CSRF token");

// 3. Test Uploader with dummy image
echo "\n--- 3. Uploader File Handling & MIME Detection ---\n";
// Create a small 1x1 GIF test image
$testImgPath = dirname(__DIR__) . '/tests/test_pixel.gif';
file_put_contents($testImgPath, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));

try {
    $uploadedPath = Uploader::uploadImage([
        'name'     => 'test_pixel.gif',
        'type'     => 'image/gif',
        'tmp_name' => $testImgPath,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($testImgPath),
    ], 'categories');

    $fullUploadPath = dirname(__DIR__) . '/public' . $uploadedPath;
    assertTest(file_exists($fullUploadPath) && str_contains($uploadedPath, '/uploads/categories/'), "Successfully uploads verified image to /uploads/categories/");
    @unlink($fullUploadPath);
} catch (\Throwable $e) {
    assertTest(false, "Failed to upload valid image", $e->getMessage());
}

// Subcategory upload
try {
    file_put_contents($testImgPath, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
    $uploadedSubPath = Uploader::uploadImage([
        'name'     => 'test_pixel.gif',
        'type'     => 'image/gif',
        'tmp_name' => $testImgPath,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($testImgPath),
    ], 'subcategories');

    $fullSubUploadPath = dirname(__DIR__) . '/public' . $uploadedSubPath;
    assertTest(file_exists($fullSubUploadPath) && str_contains($uploadedSubPath, '/uploads/subcategories/'), "Successfully uploads verified image to /uploads/subcategories/");
    @unlink($fullSubUploadPath);
} catch (\Throwable $e) {
    assertTest(false, "Failed to upload subcategory image", $e->getMessage());
}

// Invalid MIME / non-image
$badFilePath = dirname(__DIR__) . '/tests/test_bad.txt';
file_put_contents($badFilePath, "Hello world this is not an image");
try {
    Uploader::uploadImage([
        'name'     => 'test_bad.txt',
        'type'     => 'text/plain',
        'tmp_name' => $badFilePath,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($badFilePath),
    ], 'categories');
    assertTest(false, "Should have rejected text file as image upload");
} catch (\Throwable $e) {
    assertTest(str_contains($e->getMessage(), 'Invalid image format'), "Correctly rejects non-image text file");
}
@unlink($badFilePath);
@unlink($testImgPath);

// 4. Test Category Uniqueness & 422 Prevention
echo "\n--- 4. Category & Subcategory Uniqueness Validation ---\n";
$uniqueCatName = "Test Category " . time();
$catId = Category::create([
    'name' => $uniqueCatName,
    'status' => 'ACTIVE',
]);

$dupValidator = Validator::make([
    'name' => $uniqueCatName,
    'status' => 'ACTIVE',
]);
$dupValid = $dupValidator->validate([
    'name' => 'required|max:150|unique:categories,name',
    'status' => 'required|in:ACTIVE,INACTIVE',
]);
assertTest(!$dupValid && isset($dupValidator->errors()['name']), "Validator catches duplicate category name for 422 handling");

// Update same category allows self ID ignore
$updateValidator = Validator::make([
    'name' => $uniqueCatName,
    'status' => 'ACTIVE',
]);
$updateValid = $updateValidator->validate([
    'name' => "required|max:150|unique:categories,name,{$catId}",
    'status' => 'required|in:ACTIVE,INACTIVE',
]);
assertTest($updateValid, "Validator allows keeping same name during update (unique ignore self)");

// Clean up test category
Category::hardDelete($catId, true);

echo "\n=======================================================================\n";
echo " TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================================\n";

if ($failed > 0) exit(1);
exit(0);
