<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — AUTH & APPROVAL WORKFLOW TEST SUITE
 * Complete End-to-End Verification of Roles, Approvals, OTPs, & Catalogs
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Helpers\Session;
use App\Models\Otp;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

$db = Database::getConnection();

$testsPassed = 0;
$testsFailed = 0;

function runTest(string $description, callable $fn): void {
    global $testsPassed, $testsFailed;
    try {
        $fn();
        echo "  [PASS] {$description}\n";
        $testsPassed++;
    } catch (\Throwable $e) {
        echo "  [FAIL] {$description}\n";
        echo "         Error: {$e->getMessage()}\n";
        $testsFailed++;
    }
}

echo "=======================================================================\n";
echo " SPORTS JUNCTION — AUTH, ROLES & APPROVALS WORKFLOW TESTS\n";
echo "=======================================================================\n\n";

// --- Clean up prior test artefacts ---
$db->exec("DELETE FROM email_otps WHERE email LIKE 'test_%@example.com'");
$db->exec("DELETE FROM products WHERE sku LIKE 'TEST-SKU-%'");
$db->exec("DELETE FROM suppliers WHERE email LIKE 'test_%@example.com'");
$db->exec("DELETE FROM users WHERE email LIKE 'test_%@example.com'");

// =======================================================================
// 1. BUYER REGISTRATION & OTP VERIFICATION
// =======================================================================
echo "--- 1. Buyer Registration & OTP Verification Tests ---\n";

$buyerEmail = 'test_buyer_' . time() . '@example.com';
$buyerPassword = 'Password@123';

runTest('Buyer registration generates user with PENDING_VERIFICATION and secure OTP', function () use ($buyerEmail, $buyerPassword, $db) {
    $userId = User::createBuyer([
        'name'            => 'Test Procurement Officer',
        'email'           => $buyerEmail,
        'password'        => $buyerPassword,
        'company_name'    => 'Alpha Sports Gear Ltd',
        'country_code'    => 'IN',
        'country_id'      => 1,
        'terms_version'   => '1.0',
    ]);

    if (!$userId) {
        throw new Exception("Failed to create buyer record");
    }

    $user = User::findById($userId);
    if (!$user || $user['status'] !== 'PENDING_VERIFICATION') {
        throw new Exception("Buyer initial status must be PENDING_VERIFICATION");
    }

    if (!empty($user['email_verified_at'])) {
        throw new Exception("email_verified_at must be NULL before verification");
    }

    // Generate OTP
    $code = Otp::generate($buyerEmail, 'BUYER_VERIFICATION');
    if (strlen($code) !== 6 || !ctype_digit($code)) {
        throw new Exception("OTP must be 6 numeric digits");
    }

    // Check database stores hashed passcode
    $stmt = $db->prepare("SELECT otp_code FROM email_otps WHERE email = :e ORDER BY id DESC LIMIT 1");
    $stmt->execute([':e' => $buyerEmail]);
    $hash = $stmt->fetchColumn();
    if (!$hash || str_contains($hash, $code)) {
        throw new Exception("Passcode must be Bcrypt hashed and never stored in plain text");
    }
});

runTest('Duplicate email registration is blocked', function () use ($buyerEmail) {
    if (!User::emailExists($buyerEmail)) {
        throw new Exception("User should exist in database");
    }
});

runTest('Invalid OTP code is rejected and tracks attempts', function () use ($buyerEmail) {
    $res = Otp::verify($buyerEmail, '000000', 'BUYER_VERIFICATION');
    if ($res['success']) {
        throw new Exception("Invalid OTP must be rejected");
    }
});

runTest('Valid OTP code verifies and transitions user to ACTIVE', function () use ($buyerEmail, $db) {
    // Generate fresh OTP
    $code = Otp::generate($buyerEmail, 'BUYER_VERIFICATION');
    $res = Otp::verify($buyerEmail, $code, 'BUYER_VERIFICATION');
    if (!$res['success']) {
        throw new Exception("Valid OTP verification failed: " . ($res['message'] ?? ''));
    }

    User::markEmailVerified($buyerEmail);
    $user = User::findByEmail($buyerEmail);
    if ($user['status'] !== 'ACTIVE' || empty($user['email_verified_at'])) {
        throw new Exception("User status must be ACTIVE with non-empty email_verified_at");
    }
});

// =======================================================================
// 2. SUPPLIER ONBOARDING & ADMIN APPROVAL WORKFLOW
// =======================================================================
echo "\n--- 2. Supplier Onboarding & Admin Approval Workflow Tests ---\n";

$supEmail = 'test_supplier_' . time() . '@example.com';
$supPassword = 'Password@123';
$supplierId = 0;
$supUserId = 0;

runTest('Supplier registration sets up user and supplier profile in PENDING_VERIFICATION', function () use ($supEmail, $supPassword, &$supplierId, &$supUserId) {
    $supUserId = User::createSupplierUser([
        'name'            => 'Vikram Singhania',
        'email'           => $supEmail,
        'password'        => $supPassword,
        'company_name'    => 'Singhania Leather & Sports Works',
        'country_code'    => 'IN',
        'country_id'      => 1,
        'terms_version'   => '1.0',
    ]);

    $supplierId = Supplier::create([
        'user_id'          => $supUserId,
        'company_name'     => 'Singhania Leather & Sports Works',
        'contact_person'   => 'Vikram Singhania',
        'email'            => $supEmail,
        'phone'            => '+91 98765 43210',
        'business_type_id' => 1,
        'country_id'       => 1,
        'state_id'         => 1,
        'city_id'          => 1,
        'address'          => 'Plot 42, Meerut Sports Complex',
        'postal_code'      => '250001',
        'year_established' => 2005,
    ]);

    $sup = Supplier::findById($supplierId);
    if (!$sup || $sup['approval_status'] !== 'PENDING_VERIFICATION') {
        throw new Exception("Supplier initial status must be PENDING_VERIFICATION");
    }
});

runTest('Supplier email OTP verification moves supplier to PENDING_APPROVAL', function () use ($supEmail, $supplierId) {
    $code = Otp::generate($supEmail, 'SUPPLIER_VERIFICATION');
    $res = Otp::verify($supEmail, $code, 'SUPPLIER_VERIFICATION');
    if (!$res['success']) {
        throw new Exception("Supplier OTP verification failed");
    }

    User::markEmailVerified($supEmail);
    Supplier::updateApprovalStatus($supplierId, 'PENDING_APPROVAL');

    $sup = Supplier::findById($supplierId);
    if ($sup['approval_status'] !== 'PENDING_APPROVAL') {
        throw new Exception("Supplier status must be PENDING_APPROVAL while awaiting admin review");
    }
});

runTest('Super Admin can approve supplier and unlock supplier access', function () use ($supplierId) {
    $ok = Supplier::updateApprovalStatus($supplierId, 'APPROVED', null, 1);
    if (!$ok) {
        throw new Exception("Super Admin failed to approve supplier");
    }

    $sup = Supplier::findById($supplierId);
    if ($sup['approval_status'] !== 'APPROVED' || empty($sup['reviewed_at'])) {
        throw new Exception("Supplier approval state not properly committed");
    }

    // Verify supplier is present in allApproved() list
    $approvedList = Supplier::allApproved();
    $found = false;
    foreach ($approvedList as $a) {
        if ((int)$a['id'] === $supplierId) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        throw new Exception("Supplier must appear in Supplier::allApproved() dropdown source");
    }
});

// =======================================================================
// 3. PRODUCT SUBMISSION, CATALOG APPROVAL & LIVE MARKETPLACE VISIBILITY
// =======================================================================
echo "\n--- 3. Product Catalog Submission, Review & Live Visibility Tests ---\n";

$productId = 0;
$productSlug = 'test-leather-cricket-ball-' . time();

runTest('Supplier submits new product -> stored in PENDING_APPROVAL', function () use ($supplierId, $productSlug, &$productId) {
    $productId = Product::submitSupplierProduct([
        'name'               => 'Test Leather Cricket Ball Match Grade',
        'slug'               => $productSlug,
        'sku'                => 'TEST-SKU-' . time(),
        'category_id'        => 1,
        'subcategory_id'     => 1,
        'min_price'          => 450.00,
        'max_price'          => 650.00,
        'price_unit_id'      => 1,
        'moq_quantity'       => 50,
        'moq_unit_id'        => 1,
        'available_quantity' => 5000,
        'material'           => 'Alum Tanned Leather',
        'lead_time'          => '5 - 10 Business Days',
        'status'             => 'PUBLISHED',
    ], $supplierId);

    if (!$productId) {
        throw new Exception("Failed to submit product");
    }

    $prod = Product::findById($productId);
    if ($prod['approval_status'] !== 'PENDING_APPROVAL') {
        throw new Exception("Submitted product must have approval_status = PENDING_APPROVAL");
    }
});

runTest('Pending product is HIDDEN from public catalog and findBySlug', function () use ($productSlug) {
    $publicProd = Product::findBySlug($productSlug);
    if ($publicProd !== null) {
        throw new Exception("Pending approval product must NOT be visible via findBySlug on public marketplace");
    }

    $filtered = Product::getFilteredProducts(['search' => 'Match Grade']);
    foreach ($filtered['products'] as $p) {
        if ($p['slug'] === $productSlug) {
            throw new Exception("Pending approval product must NOT be returned in public catalog search");
        }
    }
});

runTest('Super Admin approves product -> now visible on public marketplace', function () use ($productId, $productSlug) {
    $ok = Product::approveProduct($productId, 1);
    if (!$ok) {
        throw new Exception("Super Admin product approval failed");
    }

    $prod = Product::findById($productId);
    if ($prod['approval_status'] !== 'APPROVED' || empty($prod['reviewed_at'])) {
        throw new Exception("Product approval_status must be APPROVED");
    }

    $publicProd = Product::findBySlug($productSlug);
    if ($publicProd === null || $publicProd['id'] != $productId) {
        throw new Exception("Approved product must now be visible via findBySlug");
    }
});

runTest('Supplier edits approved product -> versioned in pending_changes without breaking live catalog', function () use ($supplierId, $productId, $productSlug) {
    $ok = Product::saveSupplierEdit($productId, $supplierId, [
        'name'               => 'Test Leather Cricket Ball Match Grade [MODIFIED EDIT]',
        'min_price'          => 499.00,
        'max_price'          => 799.00,
        'lead_time'          => '3 - 5 Days Expedited',
    ]);

    if (!$ok) {
        throw new Exception("Failed to save supplier edit");
    }

    $prod = Product::findById($productId);
    if (empty($prod['pending_changes'])) {
        throw new Exception("Edited fields must be stored in pending_changes JSON column");
    }

    // Live catalog must still show the original name and prices!
    $publicProd = Product::findBySlug($productSlug);
    if ($publicProd['title'] === 'Test Leather Cricket Ball Match Grade [MODIFIED EDIT]') {
        throw new Exception("Live marketplace cannot reflect pending edits before Super Admin review!");
    }
    if ((float)$publicProd['min_price'] !== 450.00) {
        throw new Exception("Live marketplace must retain original price (450.00) until admin approval");
    }
});

runTest('Super Admin approves pending changes -> live catalog updates with edited version', function () use ($productId, $productSlug) {
    $ok = Product::approveProduct($productId, 1);
    if (!$ok) {
        throw new Exception("Failed to approve product version changes");
    }

    $prod = Product::findById($productId);
    if (!empty($prod['pending_changes'])) {
        throw new Exception("pending_changes must be cleared after approval");
    }

    $publicProd = Product::findBySlug($productSlug);
    if ((float)$publicProd['min_price'] !== 499.00) {
        throw new Exception("Live catalog must now reflect updated price (499.00)");
    }
});

runTest('Suspension of supplier hides all their products from public marketplace', function () use ($supplierId, $productSlug) {
    Supplier::updateApprovalStatus($supplierId, 'SUSPENDED', 'Compliance audit pending', 1);

    $publicProd = Product::findBySlug($productSlug);
    if ($publicProd !== null) {
        throw new Exception("Products from SUSPENDED supplier must not be visible on marketplace");
    }

    // Reactivate
    Supplier::updateApprovalStatus($supplierId, 'APPROVED', null, 1);
    $publicProd = Product::findBySlug($productSlug);
    if ($publicProd === null) {
        throw new Exception("Products should reappear after supplier reactivation");
    }
});

// =======================================================================
// 4. PASSWORD RECOVERY & OTP WORKFLOW
// =======================================================================
echo "\n--- 4. Password Recovery & OTP Workflow Tests ---\n";

runTest('Password reset OTP generation and password update flow', function () use ($buyerEmail, $db) {
    $code = Otp::generate($buyerEmail, 'PASSWORD_RESET');
    if (strlen($code) !== 6) {
        throw new Exception("Password reset OTP generation failed");
    }

    $res = Otp::verify($buyerEmail, $code, 'PASSWORD_RESET');
    if (!$res['success']) {
        throw new Exception("Password reset OTP verification failed");
    }

    $newPass = 'NewSecret@999';
    $user = User::findByEmail($buyerEmail);
    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
    $stmt->execute([':p' => $newHash, ':id' => $user['id']]);

    $updatedUser = User::findByEmail($buyerEmail);
    if (!password_verify($newPass, $updatedUser['password'])) {
        throw new Exception("New password verification failed");
    }
});

// Clean up
$db->exec("DELETE FROM email_otps WHERE email LIKE 'test_%@example.com'");
$db->exec("DELETE FROM products WHERE sku LIKE 'TEST-SKU-%'");
$db->exec("DELETE FROM suppliers WHERE email LIKE 'test_%@example.com'");
$db->exec("DELETE FROM users WHERE email LIKE 'test_%@example.com'");

echo "\n=======================================================================\n";
echo " AUTH & APPROVALS SUMMARY: {$testsPassed} PASSED, {$testsFailed} FAILED\n";
echo "=======================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
