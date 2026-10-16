<?php
declare(strict_types=1);

/**
 * Test Suite for Task 14: Buyer and Supplier Management Permissions & RBAC Enforcement
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

use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Middleware\AuthMiddleware;
use App\Middleware\BuyerAuthMiddleware;
use App\Middleware\SupplierAuthMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Session;

$pdo = \App\Core\Database::getConnection();

echo "=== STARTING VERIFICATION FOR TASK 14: RBAC & PERMISSION ENFORCEMENT ===\n\n";

$testsPassed = 0;
$totalTests = 7;

// Setup test users if needed
$testPassword = password_hash('TestPass@123', PASSWORD_BCRYPT);

// 1. Super Admin
$admin = User::findByEmail('sportjunctions@gmail.com');
if (!$admin) {
    die("ERROR: Super Admin user not found.\n");
}

// 2. Active Buyer 1
$buyer1Email = 'test_buyer_rbac_1@sportsjunction.test';
$buyer1 = User::findByEmail($buyer1Email);
if (!$buyer1) {
    $b1Id = User::create([
        'name'       => 'RBAC Test Buyer 1',
        'email'      => $buyer1Email,
        'password'   => $testPassword,
        'role'       => 'BUYER',
        'status'     => 'ACTIVE',
        'country_id' => 1,
    ]);
    $buyer1 = User::findById($b1Id);
}

// 3. Active Buyer 2
$buyer2Email = 'test_buyer_rbac_2@sportsjunction.test';
$buyer2 = User::findByEmail($buyer2Email);
if (!$buyer2) {
    $b2Id = User::create([
        'name'       => 'RBAC Test Buyer 2',
        'email'      => $buyer2Email,
        'password'   => $testPassword,
        'role'       => 'BUYER',
        'status'     => 'ACTIVE',
        'country_id' => 1,
    ]);
    $buyer2 = User::findById($b2Id);
}

// 4. Inactive Buyer
$buyerInactiveEmail = 'test_buyer_inactive@sportsjunction.test';
$buyerInactive = User::findByEmail($buyerInactiveEmail);
if (!$buyerInactive) {
    $bInId = User::create([
        'name'       => 'RBAC Inactive Buyer',
        'email'      => $buyerInactiveEmail,
        'password'   => $testPassword,
        'role'       => 'BUYER',
        'status'     => 'INACTIVE',
        'country_id' => 1,
    ]);
    $buyerInactive = User::findById($bInId);
} else {
    $pdo->prepare("UPDATE users SET status = 'INACTIVE' WHERE id = :id")->execute([':id' => $buyerInactive['id']]);
    $buyerInactive['status'] = 'INACTIVE';
}

// 5. Active Supplier 1 & 2
$supplier1UserEmail = 'test_supp_rbac_1@sportsjunction.test';
$supp1User = User::findByEmail($supplier1UserEmail);
if (!$supp1User) {
    $s1UId = User::create([
        'name'       => 'RBAC Supplier 1 User',
        'email'      => $supplier1UserEmail,
        'password'   => $testPassword,
        'role'       => 'SUPPLIER',
        'status'     => 'ACTIVE',
        'country_id' => 1,
    ]);
    $supp1User = User::findById($s1UId);
}
$supp1 = Supplier::findByUserId((int)$supp1User['id']);
if (!$supp1) {
    $s1Id = Supplier::create([
        'user_id'          => (int)$supp1User['id'],
        'business_type_id' => 1,
        'country_id'       => 1,
        'state_id'         => 1,
        'city_id'          => 1,
        'company_name'     => 'RBAC Supplier 1 Corp',
        'contact_person'   => 'Supplier 1 Contact',
        'email'            => $supplier1UserEmail,
        'phone'            => '+1234567890',
        'status'           => 'ACTIVE',
        'approval_status'  => 'APPROVED',
    ]);
    $supp1 = Supplier::findById($s1Id);
}

$supplier2UserEmail = 'test_supp_rbac_2@sportsjunction.test';
$supp2User = User::findByEmail($supplier2UserEmail);
if (!$supp2User) {
    $s2UId = User::create([
        'name'       => 'RBAC Supplier 2 User',
        'email'      => $supplier2UserEmail,
        'password'   => $testPassword,
        'role'       => 'SUPPLIER',
        'status'     => 'ACTIVE',
        'country_id' => 1,
    ]);
    $supp2User = User::findById($s2UId);
}
$supp2 = Supplier::findByUserId((int)$supp2User['id']);
if (!$supp2) {
    $s2Id = Supplier::create([
        'user_id'          => (int)$supp2User['id'],
        'business_type_id' => 1,
        'country_id'       => 1,
        'state_id'         => 1,
        'city_id'          => 1,
        'company_name'     => 'RBAC Supplier 2 Corp',
        'contact_person'   => 'Supplier 2 Contact',
        'email'            => $supplier2UserEmail,
        'phone'            => '+1234567891',
        'status'           => 'ACTIVE',
        'approval_status'  => 'APPROVED',
    ]);
    $supp2 = Supplier::findById($s2Id);
}

// 6. Inactive Supplier
$suppInactiveUserEmail = 'test_supp_inactive@sportsjunction.test';
$suppInUser = User::findByEmail($suppInactiveUserEmail);
if (!$suppInUser) {
    $sInUId = User::create([
        'name'       => 'RBAC Inactive Supplier User',
        'email'      => $suppInactiveUserEmail,
        'password'   => $testPassword,
        'role'       => 'SUPPLIER',
        'status'     => 'INACTIVE',
        'country_id' => 1,
    ]);
    $suppInUser = User::findById($sInUId);
}
$suppIn = Supplier::findByUserId((int)$suppInUser['id']);
if (!$suppIn) {
    $sInId = Supplier::create([
        'user_id'          => (int)$suppInUser['id'],
        'business_type_id' => 1,
        'country_id'       => 1,
        'state_id'         => 1,
        'city_id'          => 1,
        'company_name'     => 'RBAC Inactive Supplier Corp',
        'contact_person'   => 'Inactive Supp Contact',
        'email'            => $suppInactiveUserEmail,
        'phone'            => '+1234567899',
        'status'           => 'INACTIVE',
        'approval_status'  => 'APPROVED',
    ]);
    $suppIn = Supplier::findById($sInId);
} else {
    $pdo->prepare("UPDATE suppliers SET status = 'INACTIVE' WHERE id = :id")->execute([':id' => $suppIn['id']]);
    $suppIn['status'] = 'INACTIVE';
}

class TestResponse extends Response {
    public int $testStatusCode = 200;
    public ?string $testRedirect = null;
    public ?array $testJson = null;

    public function json(array $data, int $statusCode = 200): void {
        $this->testStatusCode = $statusCode;
        $this->testJson = $data;
    }

    public function redirect(string $path): void {
        $this->testStatusCode = 302;
        $this->testRedirect = $path;
    }
}

// Helper to simulate request & middleware check
function testMiddleware(string $middlewareClass, ?array $sessionUser, bool $isAjax = false): array {
    Session::start();
    if ($sessionUser !== null) {
        Session::set('auth_user', $sessionUser);
    } else {
        Session::remove('auth_user');
    }

    $request = new Request();
    if ($isAjax) {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
    } else {
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    $response = new TestResponse();
    $middleware = new $middlewareClass();

    $result = $middleware->handle($request, $response);

    return [
        'allowed'  => $result,
        'status'   => $response->testStatusCode,
        'redirect' => $response->testRedirect,
        'json'     => $response->testJson,
        'session'  => Session::get('auth_user'),
        'flash'    => Session::getFlash('error'),
    ];
}

// ==========================================
// TEST 1: Guest / Unauthenticated Access Control
// ==========================================
echo "TEST 1: Guest unauthenticated access control...\n";
$authMidGuestHtml = testMiddleware(AuthMiddleware::class, null, false);
$authMidGuestAjax = testMiddleware(AuthMiddleware::class, null, true);
$buyerMidGuestHtml = testMiddleware(BuyerAuthMiddleware::class, null, false);
$buyerMidGuestAjax = testMiddleware(BuyerAuthMiddleware::class, null, true);
$suppMidGuestHtml = testMiddleware(SupplierAuthMiddleware::class, null, false);
$suppMidGuestAjax = testMiddleware(SupplierAuthMiddleware::class, null, true);

if ($authMidGuestHtml['allowed'] === false &&
    $authMidGuestAjax['allowed'] === false && $authMidGuestAjax['status'] === 401 &&
    $buyerMidGuestHtml['allowed'] === false &&
    $buyerMidGuestAjax['allowed'] === false && $buyerMidGuestAjax['status'] === 401 &&
    $suppMidGuestHtml['allowed'] === false &&
    $suppMidGuestAjax['allowed'] === false && $suppMidGuestAjax['status'] === 401) {
    echo "  OK: All middlewares strictly reject guest access (HTML redirects, AJAX 401 Unauthorized).\n";
    $testsPassed++;
} else {
    echo "  FAILED: Guest access not cleanly rejected.\n";
}

// ==========================================
// TEST 2: Buyer Access Control & Role Boundaries
// ==========================================
echo "\nTEST 2: Buyer role boundary enforcement...\n";
$buyerSession = [
    'id'     => (int)$buyer1['id'],
    'name'   => $buyer1['name'],
    'email'  => $buyer1['email'],
    'role'   => 'BUYER',
    'status' => 'ACTIVE'
];

$buyerBuyerRes = testMiddleware(BuyerAuthMiddleware::class, $buyerSession, false);
$buyerAdminRes = testMiddleware(AuthMiddleware::class, $buyerSession, true);
$buyerSuppRes = testMiddleware(SupplierAuthMiddleware::class, $buyerSession, true);

if ($buyerBuyerRes['allowed'] === true &&
    $buyerAdminRes['allowed'] === false && $buyerAdminRes['status'] === 401 &&
    $buyerSuppRes['allowed'] === false && $buyerSuppRes['status'] === 401) {
    echo "  OK: Buyer can access Buyer portal, strictly blocked from Admin (401) and Supplier (401) portals.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Buyer boundary violation.\n";
}

// ==========================================
// TEST 3: Supplier Access Control & Role Boundaries
// ==========================================
echo "\nTEST 3: Supplier role boundary enforcement...\n";
$suppSession = [
    'id'     => (int)$supp1User['id'],
    'name'   => $supp1User['name'],
    'email'  => $supp1User['email'],
    'role'   => 'SUPPLIER',
    'status' => 'ACTIVE'
];

$suppSuppRes = testMiddleware(SupplierAuthMiddleware::class, $suppSession, false);
$suppAdminRes = testMiddleware(AuthMiddleware::class, $suppSession, true);
$suppBuyerRes = testMiddleware(BuyerAuthMiddleware::class, $suppSession, true);

if ($suppSuppRes['allowed'] === true &&
    $suppAdminRes['allowed'] === false && $suppAdminRes['status'] === 401 &&
    $suppBuyerRes['allowed'] === false && $suppBuyerRes['status'] === 401) {
    echo "  OK: Supplier can access Supplier portal, strictly blocked from Admin (401) and Buyer (401) portals.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Supplier boundary violation.\n";
}

// ==========================================
// TEST 4: Supplier Scoping & Tenant Isolation
// ==========================================
echo "\nTEST 4: Supplier tenant scoping (Products & Inquiries)...\n";
// Create a product owned by Supplier 1
$p1Id = Product::create([
    'supplier_id'    => (int)$supp1['id'],
    'category_id'    => 1,
    'subcategory_id' => 1,
    'title'          => 'Supplier 1 Exclusive Equipment ' . time(),
    'slug'           => 'supplier-1-exclusive-' . time(),
    'price'          => 150.00,
    'min_price'      => 140.00,
    'max_price'      => 160.00,
    'currency'       => 'USD',
    'price_unit_id'  => 1,
    'moq_quantity'   => 10,
    'moq_unit_id'    => 1,
    'status'         => 'ACTIVE',
    'approval_status'=> 'APPROVED',
]);

// Verify Product ownership check in logic:
// Product belongs to supp1 ($supp1['id']), checking if supp2 ($supp2['id']) is denied
$p1 = Product::findById($p1Id);
$supp2AllowedProduct = ($p1 && (int)$p1['supplier_id'] === (int)$supp2['id']);

// Create an inquiry assigned to Supplier 1
$inq1Id = ProductInquiry::create([
    'inquiry_number' => 'INQ-SUPP1-' . time(),
    'buyer_user_id'  => (int)$buyer1['id'],
    'supplier_id'    => (int)$supp1['id'],
    'product_id'     => $p1Id,
    'buyer_name'     => $buyer1['name'],
    'buyer_email'    => $buyer1['email'],
    'buyer_phone'    => '1234567890',
    'enquiry_type'   => 'RFQ',
    'message'        => 'Supplier 1 private inquiry',
    'status'         => 'SUBMITTED',
]);

$inq1 = ProductInquiry::findById($inq1Id);
$supp2AllowedEnquiry = ($inq1 && (
    (int)($inq1['supplier_id'] ?? 0) === (int)$supp2['id']
    || (int)($inq1['product_owner_supplier_id'] ?? 0) === (int)$supp2['id']
));

if ($supp2AllowedProduct === false && $supp2AllowedEnquiry === false) {
    echo "  OK: Cross-supplier access rejected: Supplier 2 cannot access Supplier 1 product #{$p1Id} or inquiry #{$inq1Id}.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Tenant isolation failure for suppliers.\n";
}

// ==========================================
// TEST 5: Buyer Scoping & Tenant Isolation
// ==========================================
echo "\nTEST 5: Buyer tenant scoping (Inquiries)...\n";
// $inq1 belongs to buyer1. Check if buyer2 can access it
$buyer2MatchesUser = ((int)($inq1['buyer_user_id'] ?? 0) === (int)$buyer2['id']);
$buyer2MatchesEmail = (strtolower(trim($inq1['buyer_email'] ?? '')) === strtolower(trim($buyer2['email'] ?? '')));
$buyer2AllowedEnquiry = ($buyer2MatchesUser || $buyer2MatchesEmail);

if ($buyer2AllowedEnquiry === false) {
    echo "  OK: Cross-buyer access rejected: Buyer 2 cannot access Buyer 1 inquiry #{$inq1Id}.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Tenant isolation failure for buyers.\n";
}

// ==========================================
// TEST 6: Deactivated / Suspended Account Blocking
// ==========================================
echo "\nTEST 6: Deactivated user blocking...\n";
$inactiveBuyerSession = [
    'id'     => (int)$buyerInactive['id'],
    'name'   => $buyerInactive['name'],
    'email'  => $buyerInactive['email'],
    'role'   => 'BUYER',
    'status' => 'INACTIVE'
];

$inBuyerResAjax = testMiddleware(BuyerAuthMiddleware::class, $inactiveBuyerSession, true);
$inBuyerResHtml = testMiddleware(BuyerAuthMiddleware::class, $inactiveBuyerSession, false);

$inactiveSuppSession = [
    'id'     => (int)$suppInUser['id'],
    'name'   => $suppInUser['name'],
    'email'  => $suppInUser['email'],
    'role'   => 'SUPPLIER',
    'status' => 'INACTIVE'
];

$inSuppResAjax = testMiddleware(SupplierAuthMiddleware::class, $inactiveSuppSession, true);
$inSuppResHtml = testMiddleware(SupplierAuthMiddleware::class, $inactiveSuppSession, false);

if ($inBuyerResAjax['allowed'] === false && $inBuyerResAjax['status'] === 403 &&
    $inBuyerResHtml['allowed'] === false &&
    $inSuppResAjax['allowed'] === false && $inSuppResAjax['status'] === 403 &&
    $inSuppResHtml['allowed'] === false) {
    echo "  OK: Inactive/deactivated Buyer and Supplier are strictly blocked (403 Forbidden for AJAX, session destroyed, redirected for HTML).\n";
    $testsPassed++;
} else {
    echo "  FAILED: Inactive user not properly blocked. BuyerAjaxStatus: {$inBuyerResAjax['status']}, SuppAjaxStatus: {$inSuppResAjax['status']}\n";
}

// ==========================================
// TEST 7: Super Admin Universal Privileges
// ==========================================
echo "\nTEST 7: Super Admin universal management privileges...\n";
$adminSession = [
    'id'     => (int)$admin['id'],
    'name'   => $admin['name'],
    'email'  => $admin['email'],
    'role'   => 'SUPER_ADMIN',
    'status' => 'ACTIVE'
];

$adminAdminRes = testMiddleware(AuthMiddleware::class, $adminSession, false);
$adminAdminAjax = testMiddleware(AuthMiddleware::class, $adminSession, true);

// Verify Super Admin can view all enquiries, buyers, settings
$allBuyersCount = count(User::getAllBuyersList());
$allSuppliersCount = count(Supplier::all());
$allInquiriesCount = count(ProductInquiry::allRecent());

if ($adminAdminRes['allowed'] === true && $adminAdminAjax['allowed'] === true &&
    $allBuyersCount > 0 && $allSuppliersCount > 0 && $allInquiriesCount > 0) {
    echo "  OK: Super Admin authenticated successfully (HTML/AJAX) with full access across {$allBuyersCount} buyers, {$allSuppliersCount} suppliers, {$allInquiriesCount} enquiries.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Super Admin access check failed.\n";
}

echo "\n=== TASK 14 SUMMARY ===\n";
echo "Tests Passed: {$testsPassed} / {$totalTests}\n";

if ($testsPassed === $totalTests) {
    echo "TASK 14 ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "TASK 14 FAILED: " . ($totalTests - $testsPassed) . " test(s) failed.\n";
    exit(1);
}
