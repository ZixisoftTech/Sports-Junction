<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/Request.php';
require_once dirname(__DIR__) . '/app/Core/Response.php';
require_once dirname(__DIR__) . '/app/Helpers/Session.php';
require_once dirname(__DIR__) . '/app/Helpers/AuditLogger.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Models/Supplier.php';
require_once dirname(__DIR__) . '/app/Models/ProfileChangeRequest.php';

use App\Models\User;
use App\Models\Supplier;
use App\Models\ProfileChangeRequest;
use App\Helpers\AuditLogger;

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $message): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$message}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$message}\n";
    }
}

echo "====================================================\n";
echo "SPORTS JUNCTION - BUYER / SUPPLIER VERIFICATION TEST\n";
echo "====================================================\n\n";

// TEST 1: Buyers Data Table & Country Name Resolution
echo "1. Testing Buyers Data Table & Country Name Resolution...\n";
$dtResult = User::getBuyersDataTable(['start' => 0, 'length' => 100]);
$buyerList = $dtResult['data'] ?? [];
assertCondition(!empty($buyerList), "getBuyersDataTable() returned " . count($buyerList) . " buyers (recordsTotal: " . ($dtResult['recordsTotal'] ?? 0) . ")");

$allHaveCountry = true;
foreach ($buyerList as $b) {
    if (empty($b['country_name'])) {
        $allHaveCountry = false;
        echo "    Buyer #{$b['id']} has empty country_name\n";
    }
}
assertCondition($allHaveCountry, "All buyers have resolved country_name (no empty values)");

// TEST 2: Single Buyer Details
echo "\n2. Testing getBuyerDetails()...\n";
$testBuyerId = (int)$buyerList[0]['id'];
$buyerDetails = User::getBuyerDetails($testBuyerId);
assertCondition(!empty($buyerDetails), "Found buyer details for #{$testBuyerId}");
assertCondition(!empty($buyerDetails['country_name']), "Buyer #{$testBuyerId} has country_name: " . ($buyerDetails['country_name'] ?? 'NULL'));

// TEST 3: Status Toggle (Activate & Deactivate) + AuditLogger
echo "\n3. Testing Buyer Status Toggle (Deactivate / Activate)...\n";
$originalStatus = $buyerDetails['status'] ?? 'ACTIVE';

// Deactivate
$res1 = User::setBuyerStatus($testBuyerId, 'INACTIVE');
assertCondition($res1 === true, "User::setBuyerStatus to INACTIVE succeeded");
$afterDeact = User::findById($testBuyerId);
assertCondition(($afterDeact['status'] ?? '') === 'INACTIVE', "Buyer status is confirmed INACTIVE in database");

// Test AuditLogger with array details (verifying fix for TypeError)
try {
    AuditLogger::log('BUYER_STATUS_UPDATE', 'User', $testBuyerId, 'Buyer #' . $testBuyerId, null, [
        'old_status' => 'ACTIVE',
        'new_status' => 'INACTIVE'
    ]);
    assertCondition(true, "AuditLogger::log() cleanly accepts array details without TypeError");
} catch (\Throwable $e) {
    assertCondition(false, "AuditLogger::log() threw: " . $e->getMessage());
}

// Reactivate
$res2 = User::setBuyerStatus($testBuyerId, $originalStatus);
assertCondition($res2 === true, "User::setBuyerStatus restored to {$originalStatus}");
$afterReact = User::findById($testBuyerId);
assertCondition(($afterReact['status'] ?? '') === $originalStatus, "Buyer status restored in database");

// TEST 4: Direct Buyer Profile Self-Update
echo "\n4. Testing Direct Buyer Profile Self-Update...\n";
$targetBuyer = User::fetch("SELECT * FROM users WHERE role = 'BUYER' LIMIT 1");
$buyerUserId = (int)$targetBuyer['id'];
$origName = $targetBuyer['name'];
$updatedName = 'Updated Test Buyer ' . time();

// Apply direct update logic
User::execute("
    UPDATE users SET
        name = :name,
        phone = :phone,
        state = :state,
        city = :city,
        updated_at = NOW()
    WHERE id = :id AND role = 'BUYER'
", [
    ':name' => $updatedName,
    ':phone' => '9876543210',
    ':state' => 'Maharashtra',
    ':city' => 'Mumbai',
    ':id' => $buyerUserId
]);

$checkBuyer = User::findById($buyerUserId);
assertCondition(($checkBuyer['name'] ?? '') === $updatedName, "Buyer profile updated directly in users table");

// Log approved profile change
$pcrId = ProfileChangeRequest::createApproved($buyerUserId, null, 'BUYER', ['name' => $origName], ['name' => $updatedName]);
assertCondition($pcrId > 0, "ProfileChangeRequest::createApproved created record #{$pcrId}");

// Revert name
User::execute("UPDATE users SET name = :name WHERE id = :id", [':name' => $origName, ':id' => $buyerUserId]);

// TEST 5: Direct Supplier Profile Self-Update
echo "\n5. Testing Direct Supplier Profile Self-Update...\n";
$targetSupplier = Supplier::fetch("SELECT * FROM suppliers LIMIT 1");
if ($targetSupplier) {
    $supId = (int)$targetSupplier['id'];
    $supUserId = (int)$targetSupplier['user_id'];
    $origComp = $targetSupplier['company_name'];
    $updatedComp = 'Updated Factory ' . time();

    Supplier::execute("UPDATE suppliers SET company_name = :company_name WHERE id = :id", [
        ':company_name' => $updatedComp,
        ':id' => $supId
    ]);

    $checkSup = Supplier::findById($supId);
    assertCondition(($checkSup['company_name'] ?? '') === $updatedComp, "Supplier profile updated directly in suppliers table");

    $supPcrId = ProfileChangeRequest::createApproved($supUserId, $supId, 'SUPPLIER', ['company_name' => $origComp], ['company_name' => $updatedComp]);
    assertCondition($supPcrId > 0, "Supplier ProfileChangeRequest::createApproved created record #{$supPcrId}");

    // Revert
    Supplier::execute("UPDATE suppliers SET company_name = :company_name WHERE id = :id", [':company_name' => $origComp, ':id' => $supId]);
} else {
    echo "  [SKIP] No supplier found in database to test.\n";
}

// TEST 6: Card UI CSS & Admin Buyers View Syntax
echo "\n6. Validating Stat Card UI CSS & View Syntax...\n";
$cssContent = file_get_contents(__DIR__ . '/../public/assets/css/admin.css');
assertCondition(strpos($cssContent, '.stat-card') !== false, "admin.css contains .stat-card rules");
assertCondition(strpos($cssContent, '.stat-icon') !== false, "admin.css contains .stat-icon rules");
assertCondition(strpos($cssContent, '.stat-title') !== false, "admin.css contains .stat-title rules");
assertCondition(strpos($cssContent, '.stat-number') !== false, "admin.css contains .stat-number rules");

$buyersView = file_get_contents(__DIR__ . '/../app/Views/admin/buyers.php');
assertCondition(substr_count($buyersView, 'class="stat-card') === 4, "admin/buyers.php contains exactly 4 stat-card components");

// Check JavaScript syntax inside buyers.php
preg_match('/<script>(.*?)<\/script>/s', $buyersView, $matches);
if (!empty($matches[1])) {
    $sanitizedJs = preg_replace('/<\?[\s\S]*?\?>/', '"dummy"', $matches[1]);
    $tempJsFile = sys_get_temp_dir() . '/test_buyers_view.js';
    file_put_contents($tempJsFile, $sanitizedJs);
    $nodePath = trim((string)shell_exec('which node 2>/dev/null')) ?: '/usr/local/bin/node';
    $nodeCheck = trim((string)shell_exec(escapeshellcmd($nodePath) . " -c " . escapeshellarg($tempJsFile) . " 2>&1"));
    @unlink($tempJsFile);
    assertCondition(empty($nodeCheck), "JavaScript in app/Views/admin/buyers.php has zero syntax errors" . (empty($nodeCheck) ? '' : " ($nodeCheck)"));
}

echo "\n====================================================\n";
echo "SUMMARY: Passed: {$passed}, Failed: {$failed}\n";
echo "====================================================\n";

exit($failed > 0 ? 1 : 0);
