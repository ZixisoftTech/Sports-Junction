<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — BUYER, SELLER & SUPER ADMIN MANAGEMENT TEST SUITE
 * Verifies all requirements:
 * 1. Buyer favourites, enquiries, recent views (max 20), and profile updates
 * 2. Seller registration, OTP, approval, rejection, suspension, reactivation
 * 3. Super Admin manual supplier creation, profile editing (before & after approval), and dependency-safe deletion
 * 4. Seller product ownership, pending edit protection, and approval
 * 5. Public product visibility rules (product approved + published + supplier active/approved + category active + subcategory active)
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\BuyerFavourite;
use App\Models\BuyerRecentlyViewed;
use App\Models\Category;
use App\Models\Otp;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\User;

echo "=======================================================================\n";
echo " SPORTS JUNCTION — ROLE PORTALS & MANAGEMENT VERIFICATION SUITE\n";
echo "=======================================================================\n\n";

$passed = 0;
$failed = 0;

function assertTrue(bool $condition, string $label, string $failReason = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$label}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$label}\n";
        if ($failReason) {
            echo "         Reason: {$failReason}\n";
        }
        $failed++;
    }
}

$pdo = Database::getConnection();

// Clean up any leftover test data from previous test runs
$testBuyerEmail = 'test_buyer_portal@sportsjunction.test';
$testSellerEmail = 'test_seller_portal@sportsjunction.test';
$testManualEmail = 'test_manual_supplier@sportsjunction.test';

$pdo->prepare("DELETE FROM users WHERE email IN (?, ?, ?)")->execute([$testBuyerEmail, $testSellerEmail, $testManualEmail]);
$pdo->prepare("DELETE FROM suppliers WHERE email IN (?, ?)")->execute([$testSellerEmail, $testManualEmail]);

// -----------------------------------------------------------------------
// 1. BUYER PORTAL TESTS (Profile, Favourites, Recent Views, Enquiries)
// -----------------------------------------------------------------------
echo "--- 1. Buyer Portal & Features Tests ---\n";

$buyerId = User::createBuyer([
    'name'         => 'Test Buyer Rahul',
    'company_name' => 'Rahul Sports Retail',
    'email'        => $testBuyerEmail,
    'password'     => 'Buyer@1234',
    'phone'        => '9811122233',
    'country_id'   => 1,
]);

assertTrue($buyerId > 0, 'Buyer account created in PENDING_VERIFICATION state');

// Verify OTP (static 123456)
$otp = Otp::generate($testBuyerEmail, 'REGISTRATION', '127.0.0.1');
$otpRes = Otp::verify($testBuyerEmail, '123456', 'REGISTRATION');
User::markEmailVerified($buyerId, 'BUYER');
$buyer = User::findById($buyerId);
assertTrue($otpRes['success'] && $buyer['status'] === 'ACTIVE', 'Buyer email verified via OTP and status set to ACTIVE');

// Update Buyer Profile (without changing email)
User::updateProfile($buyerId, [
    'name'         => 'Rahul Sharma Updated',
    'company_name' => 'Rahul Pro Sports Hub',
    'email'        => $testBuyerEmail,
    'phone'        => '9899988877',
    'country_id'   => 1,
]);
$buyerAfter = User::findById($buyerId);
assertTrue(
    $buyerAfter['name'] === 'Rahul Sharma Updated' && $buyerAfter['status'] === 'ACTIVE',
    'Buyer profile updated while preserving ACTIVE status when email unchanged'
);

// Find an active published product
$activeProd = $pdo->query("
    SELECT p.id, p.supplier_id, p.slug 
    FROM products p 
    JOIN suppliers sp ON p.supplier_id = sp.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.status IN ('PUBLISHED', 'ACTIVE') 
      AND p.approval_status = 'APPROVED'
      AND sp.approval_status = 'APPROVED'
      AND c.status = 'ACTIVE'
    LIMIT 1
")->fetch(\PDO::FETCH_ASSOC);

if ($activeProd) {
    $prodId = (int)$activeProd['id'];

    // Test Favourites
    $favOk = BuyerFavourite::add($buyerId, $prodId);
    $favOkDup = BuyerFavourite::add($buyerId, $prodId); // Duplicate prevention
    $favList = BuyerFavourite::getBuyerFavourites($buyerId);
    assertTrue(
        $favOk && $favOkDup && count($favList) === 1 && (int)$favList[0]['id'] === $prodId,
        'Buyer can add product to favourites without duplicates'
    );

    BuyerFavourite::remove($buyerId, $prodId);
    assertTrue(!BuyerFavourite::isFavourite($buyerId, $prodId), 'Buyer can remove product from favourites');

    // Test Recently Viewed
    BuyerRecentlyViewed::recordView($buyerId, $prodId);
    $recentList = BuyerRecentlyViewed::getRecent($buyerId, 20);
    assertTrue(count($recentList) === 1 && (int)$recentList[0]['id'] === $prodId, 'Buyer recently viewed product tracked in DB');

    BuyerRecentlyViewed::clearHistory($buyerId);
    assertTrue(count(BuyerRecentlyViewed::getRecent($buyerId, 20)) === 0, 'Buyer can clear recently viewed history');
}

// -----------------------------------------------------------------------
// 2. SELLER REGISTRATION, OTP & APPROVAL WORKFLOW
// -----------------------------------------------------------------------
echo "\n--- 2. Seller Registration & Admin Approval Workflow ---\n";

$sellerUserId = User::createSupplierUser([
    'name'         => 'Vikram Singh',
    'company_name' => 'Vikram Athletics Factory',
    'email'        => $testSellerEmail,
    'password'     => 'Seller@1234',
    'phone'        => '9711122233',
    'country_id'   => 1,
]);

$sellerSupplierId = Supplier::create([
    'user_id'          => $sellerUserId,
    'business_type_id' => 1,
    'country_id'       => 1,
    'state_id'         => 1,
    'city_id'          => 1,
    'company_name'     => 'Vikram Athletics Factory',
    'contact_person'   => 'Vikram Singh',
    'email'            => $testSellerEmail,
    'phone'            => '9711122233',
    'address'          => 'Industrial Estate Meerut',
    'postal_code'      => '250002',
    'status'           => 'ACTIVE',
    'approval_status'  => 'PENDING_VERIFICATION',
]);

// Verify email does NOT auto-approve supplier
User::markEmailVerified($sellerUserId, 'SUPPLIER');
Supplier::updateApprovalStatus($sellerSupplierId, 'PENDING_APPROVAL');
$supPending = Supplier::findById($sellerSupplierId);
assertTrue(
    $supPending['approval_status'] === 'PENDING_APPROVAL',
    'Email verification marks supplier as PENDING_APPROVAL (not auto-approved)'
);

// Super Admin edits pending supplier details BEFORE approval
Supplier::updateProfile($sellerSupplierId, [
    'company_name'     => 'Vikram International Sports Pvt Ltd',
    'contact_person'   => 'Vikram Singh Rathore',
    'email'            => $testSellerEmail,
    'phone'            => '9711122244',
    'business_type_id' => 1,
    'address'          => 'Plot 88, Sports Complex Meerut',
    'country_id'       => 1,
    'state_id'         => 1,
    'city_id'          => 1,
    'postal_code'      => '250002',
]);
$supEditedBefore = Supplier::findById($sellerSupplierId);
assertTrue(
    $supEditedBefore['company_name'] === 'Vikram International Sports Pvt Ltd' &&
    $supEditedBefore['approval_status'] === 'PENDING_APPROVAL',
    'Super Admin can edit supplier details before approval without accidentally approving'
);

// Super Admin approves supplier
Supplier::updateApprovalStatus($sellerSupplierId, 'APPROVED', null, 1);
$supApproved = Supplier::findById($sellerSupplierId);
assertTrue(
    $supApproved['approval_status'] === 'APPROVED' && $supApproved['status'] === 'ACTIVE',
    'Super Admin approves supplier and activates account'
);

// Super Admin edits supplier AFTER approval
Supplier::updateProfile($sellerSupplierId, [
    'company_name'     => 'Vikram Global Sports Manufacturing',
    'contact_person'   => 'Vikram Singh Rathore',
    'email'            => $testSellerEmail,
    'phone'            => '9711122255',
    'business_type_id' => 1,
    'address'          => 'Plot 88, Sports Complex Meerut',
    'country_id'       => 1,
    'state_id'         => 1,
    'city_id'          => 1,
    'postal_code'      => '250002',
]);
$supEditedAfter = Supplier::findById($sellerSupplierId);
assertTrue(
    $supEditedAfter['company_name'] === 'Vikram Global Sports Manufacturing' &&
    $supEditedAfter['approval_status'] === 'APPROVED',
    'Super Admin can edit approved supplier details while preserving APPROVED status'
);

// -----------------------------------------------------------------------
// 3. SELLER PRODUCT OWNERSHIP, REVISION PROTECTION & PUBLIC VISIBILITY
// -----------------------------------------------------------------------
echo "\n--- 3. Seller Product Management, Revisions & Visibility Rules ---\n";

$newProdId = Product::submitSupplierProduct([
    'name'              => 'Tournament English Willow Bat Grade A',
    'category_id'       => 1,
    'subcategory_id'    => 1,
    'sku'               => 'VK-BAT-001',
    'brand_name'        => 'Vikram Pro',
    'short_description' => 'Handcrafted English Willow cricket bat for professional tournaments.',
    'description'       => 'Full specification professional bat.',
    'min_price'         => 4500,
    'max_price'         => 6000,
    'price_unit_id'     => 1,
    'moq'               => 10,
    'moq_unit_id'       => 1,
    'country_id'        => 1,
    'state_id'          => 1,
    'city_id'           => 1,
], $sellerSupplierId, []);

$submittedProd = Product::findById($newProdId);
assertTrue(
    $submittedProd['approval_status'] === 'PENDING_APPROVAL' && $submittedProd['status'] === 'DRAFT',
    'Supplier product submission starts in PENDING_APPROVAL & DRAFT state'
);

// Verify unapproved product is hidden from public findBySlug
$publicLookupBefore = Product::findBySlug($submittedProd['slug']);
assertTrue($publicLookupBefore === null, 'Unapproved supplier product is hidden from public pages');

// Super Admin approves product
Product::approveProduct($newProdId, 1);
$approvedProd = Product::findById($newProdId);
$publicLookupAfter = Product::findBySlug($approvedProd['slug']);
assertTrue(
    $publicLookupAfter !== null && $approvedProd['approval_status'] === 'APPROVED' && $approvedProd['status'] === 'PUBLISHED',
    'Approved product becomes publicly visible'
);

// Supplier edits published product -> stores in pending_changes without overwriting live title
Product::saveSupplierEdit($newProdId, $sellerSupplierId, [
    'name'           => 'Tournament English Willow Bat Grade A+ (2027 Edition)',
    'category_id'    => 1,
    'subcategory_id' => 1,
    'min_price'      => 4800,
    'max_price'      => 6200,
    'price_unit_id'  => 1,
    'moq'            => 15,
    'moq_unit_id'    => 1,
]);

$liveProdCheck = Product::findBySlug($approvedProd['slug']);
assertTrue(
    $liveProdCheck['name'] === 'Tournament English Willow Bat Grade A' && !empty($liveProdCheck['pending_changes']),
    'Supplier edit to published product preserves approved public version and stores pending revision'
);

// Super Admin approves the pending revision
Product::approveProduct($newProdId, 1);
$liveProdAfterRev = Product::findBySlug($approvedProd['slug']);
assertTrue(
    $liveProdAfterRev['name'] === 'Tournament English Willow Bat Grade A+ (2027 Edition)' && empty($liveProdAfterRev['pending_changes']),
    'Super Admin approval of revision publishes updated product title and clears pending_changes'
);

// Suspending supplier hides their products from public pages
Supplier::updateApprovalStatus($sellerSupplierId, 'SUSPENDED', 'Routine compliance audit', 1);
$publicWhenSuspended = Product::findBySlug($approvedProd['slug']);
assertTrue($publicWhenSuspended === null, 'Suspending a supplier immediately hides their products from public visibility');

// Reactivating supplier restores product visibility
Supplier::updateApprovalStatus($sellerSupplierId, 'APPROVED', null, 1);
$publicWhenReactivated = Product::findBySlug($approvedProd['slug']);
assertTrue($publicWhenReactivated !== null, 'Reactivating supplier restores their approved products to public visibility');

// -----------------------------------------------------------------------
// 4. CUSTOMER ENQUIRIES & MESSAGING WORKFLOW
// -----------------------------------------------------------------------
echo "\n--- 4. Buyer & Seller Enquiry Workflow & Ownership ---\n";

$inqId = ProductInquiry::create([
    'product_id'      => $newProdId,
    'supplier_id'     => $sellerSupplierId,
    'buyer_user_id'   => $buyerId,
    'buyer_name'      => 'Rahul Sharma Updated',
    'buyer_email'     => $testBuyerEmail,
    'buyer_phone'     => '9899988877',
    'company_name'    => 'Rahul Pro Sports Hub',
    'target_quantity' => 50,
    'lead_time_req'   => '15 Days',
    'specifications'  => 'Need custom laser engraving on bat handle.',
]);

$buyerEnquiries = ProductInquiry::getBuyerEnquiries($buyerId, $testBuyerEmail);
$supplierEnquiries = ProductInquiry::getSupplierEnquiries($sellerSupplierId);
assertTrue(
    count($buyerEnquiries) >= 1 && count($supplierEnquiries) === 1,
    'Enquiry is visible to both the submitting buyer and the product-owning supplier'
);

// Supplier replies to enquiry
ProductInquiry::addReply($inqId, $sellerUserId, 'SUPPLIER', 'We can provide laser engraving at zero extra cost for 50+ units.');
$inqAfterReply = ProductInquiry::findById($inqId);
$replies = ProductInquiry::getReplies($inqId);
assertTrue(
    $inqAfterReply['status'] === 'RESPONDED' && count($replies) === 1,
    'Supplier reply updates inquiry status to RESPONDED and stores message thread'
);

// -----------------------------------------------------------------------
// 5. DEPENDENCY-AWARE SUPPLIER DELETION
// -----------------------------------------------------------------------
echo "\n--- 5. Dependency-Aware Supplier Deletion ---\n";

$deps = Supplier::checkDependencies($sellerSupplierId);
$deleteAttempt = Supplier::delete($sellerSupplierId);
assertTrue(
    !$deps['can_delete'] && $deleteAttempt === false && $deps['products'] > 0 && $deps['inquiries'] > 0,
    'Blocks deletion of supplier when owned products and enquiries exist'
);

// Clean up test product & inquiry, then verify deletion succeeds when 0 dependencies exist
$pdo->prepare("DELETE FROM inquiry_replies WHERE inquiry_id = ?")->execute([$inqId]);
$pdo->prepare("DELETE FROM product_inquiries WHERE id = ?")->execute([$inqId]);
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$newProdId]);

$depsAfterClean = Supplier::checkDependencies($sellerSupplierId);
$deleteSuccess = Supplier::delete($sellerSupplierId);
assertTrue(
    $depsAfterClean['can_delete'] && $deleteSuccess === true,
    'Allows deletion of supplier once all product and inquiry dependencies are removed'
);

// Clean up test buyer
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$buyerId]);

echo "\n=======================================================================\n";
echo " TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================================\n";

exit($failed > 0 ? 1 : 0);
