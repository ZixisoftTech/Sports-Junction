<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/Request.php';
require_once dirname(__DIR__) . '/app/Core/Response.php';
require_once dirname(__DIR__) . '/app/Core/Controller.php';
require_once dirname(__DIR__) . '/app/Helpers/Session.php';
require_once dirname(__DIR__) . '/app/Helpers/AuditLogger.php';
require_once dirname(__DIR__) . '/app/Helpers/CSRF.php';
require_once dirname(__DIR__) . '/app/Helpers/Mailer.php';
require_once dirname(__DIR__) . '/app/Helpers/Uploader.php';
require_once dirname(__DIR__) . '/app/Models/ProductCertificate.php';
require_once dirname(__DIR__) . '/app/Middleware/SupplierAuthMiddleware.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Models/Supplier.php';
require_once dirname(__DIR__) . '/app/Models/Product.php';
require_once dirname(__DIR__) . '/app/Models/ProfileChangeRequest.php';
require_once dirname(__DIR__) . '/app/Controllers/SupplierPortalController.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminProfileChangeController.php';
require_once dirname(__DIR__) . '/app/Controllers/ProductApprovalController.php';
require_once dirname(__DIR__) . '/app/Controllers/SupplierApprovalController.php';

use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProfileChangeRequest;
use App\Middleware\SupplierAuthMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Session;

class MockResponse extends Response {
    public int $mockStatusCode = 200;
    public array $mockJsonData = [];
    public ?string $mockRedirectUrl = null;

    public function json(array $data, int $statusCode = 200): void {
        $this->mockStatusCode = $statusCode;
        $this->mockJsonData = $data;
    }

    public function redirect(string $path): void {
        $this->mockRedirectUrl = $path;
    }
}

Session::start();

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$message}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$message}\n";
    }
}

echo "========================================================================\n";
echo "END-TO-END VERIFICATION: SUPPLIER, PROFILE & PRODUCT APPROVAL WORKFLOWS\n";
echo "========================================================================\n\n";

$testTimestamp = time();
$supplierEmail = "test_factory_{$testTimestamp}@sportsjunction.test";
$adminUserId = 1;

// -----------------------------------------------------------------------------
// STEP 1: SUPPLIER REGISTRATION & PENDING APPROVAL LOCKDOWN
// -----------------------------------------------------------------------------
echo "--- STEP 1: Supplier Registration & Access Lockdown ---\n";

$userId = User::create([
    'name'              => 'John Factory Owner',
    'company_name'      => 'Apex Sports Gear Ltd',
    'email'             => $supplierEmail,
    'password'          => 'Password@123',
    'role'              => 'SUPPLIER',
    'status'            => 'INACTIVE',
    'phone'             => '9876543210',
    'country_code'      => '+91',
    'country_id'        => 1,
    'state'             => 'Punjab',
    'city'              => 'Jalandhar',
    'email_verified_at' => date('Y-m-d H:i:s'),
]);

$supplierId = Supplier::create([
    'user_id'          => $userId,
    'business_type_id' => 1,
    'country_id'       => 1,
    'state'            => 'Punjab',
    'city'             => 'Jalandhar',
    'company_name'     => 'Apex Sports Gear Ltd',
    'contact_person'   => 'John Factory Owner',
    'email'            => $supplierEmail,
    'phone'            => '9876543210',
    'country_code'     => '+91',
    'address'          => '12 Industrial Area Phase 2',
    'postal_code'      => '144001',
    'status'           => 'INACTIVE',
    'approval_status'  => 'PENDING_APPROVAL',
]);

$supRecord = Supplier::findById($supplierId);
assertTest($supRecord !== null, "Supplier record created with ID #{$supplierId}");
assertTest($supRecord['approval_status'] === 'PENDING_APPROVAL', "Supplier initial approval_status is 'PENDING_APPROVAL'");
assertTest($supRecord['status'] === 'INACTIVE', "Supplier initial status is 'INACTIVE'");

// Set session as this pending supplier
Session::set('auth_user', [
    'id'     => $userId,
    'name'   => 'John Factory Owner',
    'email'  => $supplierEmail,
    'role'   => 'SUPPLIER',
    'status' => 'INACTIVE',
]);

// Test SupplierAuthMiddleware blocking pending supplier
$middleware = new SupplierAuthMiddleware();
$simulatedAjaxReq = new Request([
    'server' => [
        'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
        'REQUEST_METHOD'        => 'POST',
        'REQUEST_URI'           => '/supplier/products/add',
    ]
]);
$resp = new MockResponse();

// Middleware should return false and block request
$allowed = $middleware->handle($simulatedAjaxReq, $resp);
assertTest($allowed === false, "SupplierAuthMiddleware blocks pending supplier from adding products");

// -----------------------------------------------------------------------------
// STEP 2: SUPER ADMIN APPROVES SUPPLIER
// -----------------------------------------------------------------------------
echo "\n--- STEP 2: Super Admin Approves Supplier ---\n";

$approved = Supplier::updateApprovalStatus($supplierId, 'APPROVED', null, $adminUserId);
assertTest($approved === true, "Supplier::updateApprovalStatus to APPROVED succeeded");

$supRecordAfter = Supplier::findById($supplierId);
$userRecordAfter = User::findById($userId);
assertTest($supRecordAfter['approval_status'] === 'APPROVED', "Supplier approval_status is now 'APPROVED'");
assertTest($supRecordAfter['status'] === 'ACTIVE', "Supplier status is now 'ACTIVE'");
assertTest($userRecordAfter['status'] === 'ACTIVE', "Associated User status is synchronized to 'ACTIVE'");

// Update session with approved status
Session::set('auth_user', [
    'id'     => $userId,
    'name'   => 'John Factory Owner',
    'email'  => $supplierEmail,
    'role'   => 'SUPPLIER',
    'status' => 'ACTIVE',
]);

// Middleware should now allow access
$allowedNow = $middleware->handle($simulatedAjaxReq, $resp);
assertTest($allowedNow === true, "SupplierAuthMiddleware allows approved supplier to access portal features");

// -----------------------------------------------------------------------------
// STEP 3: PRODUCT SUBMISSION & PENDING PRODUCT APPROVAL
// -----------------------------------------------------------------------------
echo "\n--- STEP 3: Product Submission & Pending State ---\n";

$prodSlug = "apex-test-cricket-bat-{$testTimestamp}";
$productId = Product::submitSupplierProduct([
    'name'               => 'Apex Pro Cricket Bat Grade 1',
    'slug'               => $prodSlug,
    'category_id'        => 1,
    'subcategory_id'     => 1,
    'min_price'          => 120.00,
    'max_price'          => 150.00,
    'price_unit_id'      => 1,
    'moq'                => 10,
    'moq_unit_id'        => 1,
    'country_id'         => 1,
    'description'        => 'Finest English Willow cricket bat crafted for professional B2B buyers.',
    'short_description'  => 'Grade 1 English Willow cricket bat.',
    'brand_name'         => 'Apex',
    'material'           => 'English Willow',
], $supplierId);

$createdProduct = Product::findById($productId);
assertTest($createdProduct !== null, "Product #{$productId} created");
assertTest($createdProduct['approval_status'] === 'PENDING_APPROVAL', "New product approval_status is 'PENDING_APPROVAL'");
assertTest($createdProduct['status'] === 'DRAFT', "New product initial publishing status is 'DRAFT'");

// Verify product is NOT visible publicly
$publicDetail = Product::findBySlug($prodSlug);
assertTest($publicDetail === null, "Pending product is INVISIBLE on public detail page");

$catalogList = Product::getFilteredProducts(['q' => 'Apex Pro Cricket Bat Grade 1']);
$foundInPublic = false;
foreach ($catalogList['products'] ?? [] as $p) {
    if ((int)$p['id'] === $productId) {
        $foundInPublic = true;
        break;
    }
}
assertTest($foundInPublic === false, "Pending product is INVISIBLE in public marketplace search/catalog");

// -----------------------------------------------------------------------------
// STEP 4: SUPER ADMIN APPROVES PRODUCT
// -----------------------------------------------------------------------------
echo "\n--- STEP 4: Super Admin Approves Product ---\n";

$prodApproved = Product::approveProduct($productId, $adminUserId);
assertTest($prodApproved === true, "Product::approveProduct succeeded");

$approvedProd = Product::findById($productId);
assertTest($approvedProd['approval_status'] === 'APPROVED', "Product approval_status is 'APPROVED'");
assertTest($approvedProd['status'] === 'PUBLISHED', "Product status is 'PUBLISHED'");

// Verify product is now visible on public marketplace
$publicDetailLive = Product::findBySlug($prodSlug);
assertTest($publicDetailLive !== null, "Approved product is now VISIBLE on public detail page");
assertTest($publicDetailLive['name'] === 'Apex Pro Cricket Bat Grade 1', "Public detail page has correct product title");

$catalogListAfter = Product::getFilteredProducts(['q' => 'Apex Pro Cricket Bat Grade 1']);
$foundInPublicAfter = false;
foreach ($catalogListAfter['products'] ?? [] as $p) {
    if ((int)$p['id'] === $productId) {
        $foundInPublicAfter = true;
        break;
    }
}
assertTest($foundInPublicAfter === true, "Approved product is now VISIBLE in public marketplace catalog");

// -----------------------------------------------------------------------------
// STEP 5: SUPPLIER PROFILE EDITING & APPROVAL WORKFLOW
// -----------------------------------------------------------------------------
echo "\n--- STEP 5: Supplier Profile Editing & Pending Approval ---\n";

$oldCompanyName = $supRecordAfter['company_name'];
$proposedCompanyName = 'Apex Sports International Ltd';

// Supplier submits profile edit
$currentValues = [
    'company_name'   => $oldCompanyName,
    'contact_person' => $supRecordAfter['contact_person'],
    'phone'          => $supRecordAfter['phone'],
    'country_code'   => $supRecordAfter['country_code'],
    'country_id'     => (int)$supRecordAfter['country_id'],
    'state'          => $supRecordAfter['state'],
    'city'           => $supRecordAfter['city'],
];

$requestedChanges = $currentValues;
$requestedChanges['company_name'] = $proposedCompanyName;

$requestId = ProfileChangeRequest::createRequest($userId, $supplierId, 'SUPPLIER', $currentValues, $requestedChanges);
assertTest($requestId > 0, "Profile change request #{$requestId} created in PENDING status");

// Verify live supplier record is STILL UNCHANGED
$supWhilePending = Supplier::findById($supplierId);
assertTest($supWhilePending['company_name'] === $oldCompanyName, "Live supplier company_name remains '{$oldCompanyName}' while request is pending");

// Super Admin approves profile change
$adminApprovedProfile = ProfileChangeRequest::approve($requestId, $adminUserId);
assertTest($adminApprovedProfile === true, "Super Admin approved profile change request #{$requestId}");

$supAfterProfileApproved = Supplier::findById($supplierId);
assertTest($supAfterProfileApproved['company_name'] === $proposedCompanyName, "Live supplier company_name is now updated to '{$proposedCompanyName}'");

// Test Profile Rejection Workflow
$rejectChanges = $requestedChanges;
$rejectChanges['company_name'] = 'Bogus Name That Admin Will Reject';
$reqRejectId = ProfileChangeRequest::createRequest($userId, $supplierId, 'SUPPLIER', $requestedChanges, $rejectChanges);
assertTest($reqRejectId > 0, "Created second profile request #{$reqRejectId}");

$adminRejectedProfile = ProfileChangeRequest::reject($reqRejectId, $adminUserId, 'Business name does not match legal entity documentation.');
assertTest($adminRejectedProfile === true, "Super Admin rejected profile request #{$reqRejectId}");

$supAfterProfileRejected = Supplier::findById($supplierId);
assertTest($supAfterProfileRejected['company_name'] === $proposedCompanyName, "Live supplier retained previous approved company_name '{$proposedCompanyName}' after rejection");

$rejectedReqRow = ProfileChangeRequest::findById($reqRejectId);
assertTest($rejectedReqRow['status'] === 'REJECTED', "Profile request row status is 'REJECTED'");
assertTest(str_contains($rejectedReqRow['rejection_reason'], 'legal entity documentation'), "Rejection feedback reason correctly recorded");

// -----------------------------------------------------------------------------
// STEP 6: PRODUCT EDITING & PENDING REVISION WORKFLOW
// -----------------------------------------------------------------------------
echo "\n--- STEP 6: Product Editing & Pending Revision Lifecycle ---\n";

$originalTitle = $approvedProd['title'];
$proposedTitle = 'Apex Pro Limited Edition Bat (Proposed Revision)';
$proposedMinPrice = 175.00;

// Supplier saves edit to an already approved product
$editInput = $approvedProd;
$editInput['name'] = $proposedTitle;
$editInput['min_price'] = $proposedMinPrice;

$editSaved = Product::saveSupplierEdit($productId, $supplierId, $editInput);
assertTest($editSaved === true, "Product::saveSupplierEdit succeeded for approved product");

// Verify DB table columns: live product still has original title & price
$prodWhileRevisionPending = Product::findById($productId);
assertTest($prodWhileRevisionPending['title'] === $originalTitle, "Live DB column 'title' retained original value '{$originalTitle}'");
assertTest((float)$prodWhileRevisionPending['min_price'] === (float)$approvedProd['min_price'], "Live DB column 'min_price' retained original price");
assertTest(!empty($prodWhileRevisionPending['pending_changes']), "Column 'pending_changes' is populated with proposed revision JSON");

// Verify public storefront STILL shows original approved title & price
$publicWhilePending = Product::findBySlug($prodSlug);
assertTest($publicWhilePending !== null, "Product remains live on public detail page");
assertTest($publicWhilePending['name'] === $originalTitle, "Public detail page continues displaying original approved title");
assertTest((float)$publicWhilePending['min_price'] === (float)$approvedProd['min_price'], "Public detail page continues displaying original price");

// Super Admin approves the revision
$revisionApproved = Product::approveProduct($productId, $adminUserId);
assertTest($revisionApproved === true, "Super Admin approved the product revision");

$prodAfterRevisionApproved = Product::findById($productId);
assertTest($prodAfterRevisionApproved['title'] === $proposedTitle, "Live DB column 'title' is now published with revised title '{$proposedTitle}'");
assertTest((float)$prodAfterRevisionApproved['min_price'] === $proposedMinPrice, "Live DB column 'min_price' is updated to {$proposedMinPrice}");
assertTest(empty($prodAfterRevisionApproved['pending_changes']), "Column 'pending_changes' is cleared back to NULL");

// Verify public storefront NOW shows updated revised title & price
$publicAfterRevision = Product::findBySlug($prodSlug);
assertTest($publicAfterRevision['name'] === $proposedTitle, "Public storefront now displays the newly approved revision title");
assertTest((float)$publicAfterRevision['min_price'] === $proposedMinPrice, "Public storefront now displays the newly approved revision price");

// -----------------------------------------------------------------------------
// STEP 7: PRODUCT REVISION REJECTION (LIVE APPROVED PRODUCT RETAINED)
// -----------------------------------------------------------------------------
echo "\n--- STEP 7: Product Revision Rejection (Retain Approved Product Live) ---\n";

$currentLiveTitle = $prodAfterRevisionApproved['title'];
$badRevisionTitle = 'Invalid Product Update Reject Me';

$badEditInput = $prodAfterRevisionApproved;
$badEditInput['name'] = $badRevisionTitle;
Product::saveSupplierEdit($productId, $supplierId, $badEditInput);

$prodWithBadRevision = Product::findById($productId);
assertTest(!empty($prodWithBadRevision['pending_changes']), "Pending revision saved to pending_changes");

// Super Admin rejects this revision
$revisionRejected = Product::rejectProduct($productId, $adminUserId, 'Specifications do not comply with ISO sports standards.');
assertTest($revisionRejected === true, "Product::rejectProduct on pending revision succeeded");

$prodAfterRevisionRejected = Product::findById($productId);
assertTest($prodAfterRevisionRejected['approval_status'] === 'APPROVED', "Product approval_status REMAINS 'APPROVED' (product was not unpublished)");
assertTest($prodAfterRevisionRejected['status'] === 'PUBLISHED', "Product status REMAINS 'PUBLISHED'");
assertTest($prodAfterRevisionRejected['title'] === $currentLiveTitle, "Product title RETAINS previous approved title '{$currentLiveTitle}'");
assertTest(empty($prodAfterRevisionRejected['pending_changes']), "Column 'pending_changes' cleared back to NULL");
assertTest(str_contains($prodAfterRevisionRejected['rejection_reason'], 'ISO sports standards'), "Rejection feedback reason recorded in rejection_reason column");

// Verify public storefront STILL serves the product
$publicAfterReject = Product::findBySlug($prodSlug);
assertTest($publicAfterReject !== null, "Product remains live and searchable on public storefront");
assertTest($publicAfterReject['name'] === $currentLiveTitle, "Public storefront still displays the approved title");

// -----------------------------------------------------------------------------
// STEP 8: NEW PRODUCT REJECTION LIFECYCLE
// -----------------------------------------------------------------------------
echo "\n--- STEP 8: New Product Rejection (Must Not Go Live) ---\n";

$unapprovedSlug = "unapproved-item-{$testTimestamp}";
$unapprovedProdId = Product::submitSupplierProduct([
    'name'               => 'Unapproved Test Item',
    'slug'               => $unapprovedSlug,
    'category_id'        => 1,
    'subcategory_id'     => 1,
    'min_price'          => 50.00,
    'price_unit_id'      => 1,
    'moq'                => 100,
    'moq_unit_id'        => 1,
    'country_id'         => 1,
], $supplierId);

$unapprovedRejected = Product::rejectProduct($unapprovedProdId, $adminUserId, 'Counterfeit risk detected.');
assertTest($unapprovedRejected === true, "Super Admin rejected new unapproved product");

$unapprovedRow = Product::findById($unapprovedProdId);
assertTest($unapprovedRow['approval_status'] === 'REJECTED', "Unapproved product approval_status is 'REJECTED'");

$unapprovedPublic = Product::findBySlug($unapprovedSlug);
assertTest($unapprovedPublic === null, "Rejected product is NEVER accessible on public storefront");

// -----------------------------------------------------------------------------
// STEP 9: SUPPLIER REJECTION LIFECYCLE
// -----------------------------------------------------------------------------
echo "\n--- STEP 9: Supplier Account Rejection Lifecycle ---\n";

$supRejectTimestamp = time() + 1;
$rejectedSupEmail = "rejected_factory_{$supRejectTimestamp}@sportsjunction.test";
$rejUserId = User::create([
    'name'              => 'Fake Factory Owner',
    'company_name'      => 'Fraudulent Sports Co',
    'email'             => $rejectedSupEmail,
    'password'          => 'Password@123',
    'role'              => 'SUPPLIER',
    'status'            => 'INACTIVE',
    'phone'             => '9999999999',
    'country_code'      => '+91',
    'country_id'        => 1,
    'state'             => 'Delhi',
    'city'              => 'New Delhi',
    'email_verified_at' => date('Y-m-d H:i:s'),
]);

$rejSupplierId = Supplier::create([
    'user_id'          => $rejUserId,
    'business_type_id' => 1,
    'country_id'       => 1,
    'state'            => 'Delhi',
    'city'             => 'New Delhi',
    'company_name'     => 'Fraudulent Sports Co',
    'contact_person'   => 'Fake Factory Owner',
    'email'            => $rejectedSupEmail,
    'phone'            => '9999999999',
    'country_code'     => '+91',
    'address'          => 'Unknown',
    'postal_code'      => '110001',
    'status'           => 'INACTIVE',
    'approval_status'  => 'PENDING_APPROVAL',
]);

$adminRejectedSupplier = Supplier::updateApprovalStatus($rejSupplierId, 'REJECTED', 'Failed manufacturing audit verification.', $adminUserId);
assertTest($adminRejectedSupplier === true, "Super Admin rejected supplier application");

$rejSupplierRecord = Supplier::findById($rejSupplierId);
assertTest($rejSupplierRecord['approval_status'] === 'REJECTED', "Supplier approval_status is 'REJECTED'");
assertTest($rejSupplierRecord['status'] === 'INACTIVE', "Supplier status is 'INACTIVE'");
assertTest(str_contains($rejSupplierRecord['rejection_reason'], 'manufacturing audit'), "Supplier rejection reason recorded");

// Test access for rejected supplier
Session::set('auth_user', [
    'id'     => $rejUserId,
    'name'   => 'Fake Factory Owner',
    'email'  => $rejectedSupEmail,
    'role'   => 'SUPPLIER',
    'status' => 'INACTIVE',
]);
$rejBlocked = $middleware->handle($simulatedAjaxReq, $resp);
assertTest($rejBlocked === false, "SupplierAuthMiddleware blocks rejected supplier");

echo "\n========================================================================\n";
echo "SUMMARY: Passed: {$passed}, Failed: {$failed}\n";
echo "========================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
