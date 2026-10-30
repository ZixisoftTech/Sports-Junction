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

echo "=== TEST SUITE 10: 9 CRITICAL END-TO-END BUSINESS WORKFLOWS ===\n\n";

$pdo = \App\Core\Database::getConnection();

// =========================================================================
// WORKFLOW 1: New Supplier registration -> OTP -> Admin approval -> Product creation -> Admin review -> Public Catalog publishing
// =========================================================================
echo "--- WORKFLOW 1: Supplier Lifecycle & Catalog Publishing ---\n";
$uniq1 = time() . '_' . rand(1000, 9999);
$suppEmail = "supp_flow1_{$uniq1}@example.com";
$suppPassword = 'Password@123';
$suppHashed = password_hash($suppPassword, PASSWORD_BCRYPT);

// 1. User creation
$pdo->prepare("INSERT INTO users (role, email, password, name, phone, status, created_at) VALUES ('SUPPLIER', :email, :pass, :name, '9876543210', 'PENDING_VERIFICATION', NOW())")
    ->execute([':email' => $suppEmail, ':pass' => $suppHashed, ':name' => "Supplier Flow $uniq1"]);
$suppUserId = (int)$pdo->lastInsertId();

// 2. Supplier profile
$suppId = \App\Models\Supplier::create([
    'user_id' => $suppUserId,
    'business_type_id' => 1,
    'country_id' => 1,
    'company_name' => "Apex Sports $uniq1",
    'contact_person' => "Apex Manager $uniq1",
    'email' => $suppEmail,
    'phone' => '9876543210',
    'status' => 'INACTIVE',
    'approval_status' => 'PENDING_APPROVAL'
]);
assertTest("WF1: Supplier registration record created", $suppId > 0, "Failed to create supplier");

// 3. OTP verification
$otpCode = \App\Models\Otp::generate($suppEmail, 'REGISTRATION');
$otpVerify = \App\Models\Otp::verify($suppEmail, $otpCode, 'REGISTRATION');
assertTest("WF1: Supplier OTP verified via Otp::verify()", ($otpVerify['success'] ?? false) === true, "OTP verification failed");

$pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = :id")->execute([':id' => $suppUserId]);
$verifiedUser = $pdo->query("SELECT email_verified_at FROM users WHERE id = {$suppUserId}")->fetch(PDO::FETCH_ASSOC);
assertTest("WF1: Supplier user marked verified in database", !empty($verifiedUser['email_verified_at']), "Supplier email not verified");

// 4. Super Admin reviews & approves supplier
$adminUser = $pdo->query("SELECT id FROM users WHERE role = 'SUPER_ADMIN' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$adminId = $adminUser ? (int)$adminUser['id'] : 1;
\App\Models\Supplier::updateApprovalStatus($suppId, 'APPROVED', 'All KYC documents verified by Super Admin QA', $adminId);
$verifiedSupplier = \App\Models\Supplier::findById($suppId);
$syncUser = $pdo->query("SELECT status FROM users WHERE id = {$suppUserId}")->fetch(PDO::FETCH_ASSOC);
assertTest("WF1: Admin approved supplier and user synced to ACTIVE",
    ($verifiedSupplier['approval_status'] ?? '') === 'APPROVED' &&
    ($verifiedSupplier['status'] ?? '') === 'ACTIVE' &&
    ($syncUser['status'] ?? '') === 'ACTIVE',
    "Supplier or linked user status not ACTIVE/APPROVED"
);

// 5. Supplier creates product
$cat = $pdo->query("SELECT id FROM categories WHERE status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$catId = (int)($cat['id'] ?? 1);
$subcat = $pdo->query("SELECT id FROM subcategories WHERE category_id = {$catId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$subId = (int)($subcat['id'] ?? 1);
$prodData = [
    'supplier_id' => $suppId,
    'category_id' => $catId,
    'subcategory_id' => $subId,
    'title' => "E2E Pro Match Ball $uniq1",
    'slug' => "e2e-pro-match-ball-{$uniq1}",
    'description' => "Pro level FIFA certified ball $uniq1 with high grade polyurethane composite material",
    'price_min' => 1200.00,
    'price_max' => 1800.00,
    'price_unit_id' => 1,
    'moq' => 50,
    'moq_unit_id' => 1,
    'country_id' => 1,
    'status' => 'DRAFT',
    'approval_status' => 'PENDING',
    'is_featured' => 0
];
$prodId = \App\Models\Product::create($prodData);
$createdProd = \App\Models\Product::findById($prodId);
assertTest("WF1: Product created with PENDING_APPROVAL status",
    in_array($createdProd['approval_status'] ?? '', ['PENDING', 'PENDING_APPROVAL'], true),
    "Product not pending approval"
);

// 6. Public catalog check: Must NOT appear in public catalog before approval
$publicCheck = $pdo->query("SELECT id FROM products WHERE id = {$prodId} AND status IN ('ACTIVE', 'PUBLISHED') AND approval_status = 'APPROVED'")->fetch(PDO::FETCH_ASSOC);
assertTest("WF1: Unapproved product excluded from public catalog", empty($publicCheck), "Unapproved product leaked to public");

// 7. Admin reviews and approves product
\App\Models\Product::approveProduct($prodId, $adminId);
$approvedProd = \App\Models\Product::findById($prodId);
assertTest("WF1: Admin approves product -> status changes to APPROVED & ACTIVE",
    ($approvedProd['approval_status'] ?? '') === 'APPROVED' &&
    in_array($approvedProd['status'] ?? '', ['ACTIVE', 'PUBLISHED'], true),
    "Product not approved/active"
);


// =========================================================================
// WORKFLOW 2: Bulk Product Import -> Validation error report -> Admin approval -> Catalog publishing
// =========================================================================
echo "\n--- WORKFLOW 2: Bulk CSV Import & Catalog Approval ---\n";
$catName = $cat['name'] ?? 'Cricket';
$csvContent = "Product Name,Category Name,Subcategory Name,SKU,Min Price,Max Price,Price Unit,MOQ,MOQ Unit,Lead Time Days,Description\n" .
              "\"Valid Bat {$uniq1}\",\"{$catName}\",\"\",\"SKU-V-{$uniq1}\",1500,2500,\"Piece\",10,\"Piece\",10,\"English Willow Bat\"\n" .
              "\"Bad Spread Bat {$uniq1}\",\"{$catName}\",\"\",\"SKU-B-{$uniq1}\",3000,1000,\"Piece\",5,\"Piece\",5,\"Invalid spread\"\n";
$tempCsv = sys_get_temp_dir() . "/e2e_bulk_{$uniq1}.csv";
file_put_contents($tempCsv, $csvContent);

$parseRes = \App\Helpers\BulkProductImporter::parseAndValidate($tempCsv, $suppId, false);
assertTest("WF2: Bulk importer correctly parsed 1 valid row and detected 1 error row",
    ($parseRes['valid_rows_count'] ?? 0) === 1 && ($parseRes['error_rows_count'] ?? 0) === 1,
    "Expected 1 valid and 1 error row, got: " . json_encode($parseRes)
);

$commitRes = \App\Helpers\BulkProductImporter::commit($parseRes['file_token'], $suppId, false, $suppUserId);
if (file_exists($tempCsv)) unlink($tempCsv);

assertTest("WF2: Valid imported row committed to database", ($commitRes['success'] ?? false) === true && ($commitRes['imported_count'] ?? 0) === 1, "Commit failed");

$importedRow = $pdo->query("SELECT * FROM products WHERE supplier_id = {$suppId} AND title LIKE 'Valid Bat {$uniq1}%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$importedProdId = (int)($importedRow['id'] ?? 0);
assertTest("WF2: Valid imported row saved with PENDING approval status",
    $importedProdId > 0 && in_array($importedRow['approval_status'] ?? '', ['PENDING', 'PENDING_APPROVAL'], true),
    "Imported product not saved or not pending approval"
);

// Admin approves bulk imported product
\App\Models\Product::approveProduct($importedProdId, $adminId);
$approvedBulk = \App\Models\Product::findById($importedProdId);
assertTest("WF2: Bulk imported product published after admin approval",
    ($approvedBulk['approval_status'] ?? '') === 'APPROVED' &&
    in_array($approvedBulk['status'] ?? '', ['ACTIVE', 'PUBLISHED'], true),
    "Bulk product not approved/active"
);


// =========================================================================
// WORKFLOW 3: Buyer direct product enquiry -> Admin Product Enquiries page -> Supplier portal notification
// =========================================================================
echo "\n--- WORKFLOW 3: Direct Product Page Inquiry ---\n";
$directEnqId = \App\Models\ProductInquiry::create([
    'product_id' => $prodId,
    'supplier_id' => $suppId,
    'buyer_name' => "Buyer Flow $uniq1",
    'buyer_email' => "buyer_flow_{$uniq1}@example.com",
    'buyer_phone' => '+91 9123456780',
    'company_name' => "Sports Chain $uniq1",
    'subject' => "Wholesale pricing request $uniq1",
    'message' => "Need 500 units shipped to Mumbai within 3 weeks",
    'enquiry_source' => 'PRODUCT_PAGE',
    'quantity' => 500,
    'country_name' => 'India'
]);
assertTest("WF3: Direct product inquiry created with source PRODUCT_PAGE", $directEnqId > 0, "Inquiry creation failed");

// Super Admin dedicated Product Enquiries page query
$adminProductEnqs = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'PRODUCT_PAGE', 'length' => 100]);
$prodEnqIds = array_column($adminProductEnqs['data'] ?? [], 'id');
$foundInAdminPage = in_array($directEnqId, $prodEnqIds);
assertTest("WF3: Super Admin dedicated Product Enquiries page lists inquiry", $foundInAdminPage, "Inquiry missing from admin product page");

// Supplier receives in-app notification
$suppNotifId = \App\Models\Notification::createNotification(
    $suppUserId,
    'ENQUIRY_RECEIVED',
    'New Direct Product Inquiry',
    "Buyer Flow $uniq1 sent an enquiry for your product",
    "/supplier/enquiries",
    'INQUIRY',
    $directEnqId
);
$suppNotifRow = $pdo->query("SELECT id, is_read FROM notifications WHERE id = {$suppNotifId}")->fetch(PDO::FETCH_ASSOC);
assertTest("WF3: Supplier portal receives unread in-app notification",
    $suppNotifId > 0 && (int)($suppNotifRow['is_read'] ?? 1) === 0,
    "Supplier notification not created or already read"
);


// =========================================================================
// WORKFLOW 4: Buyer Sort List multi-product bulk enquiry -> Admin Bulk Product Enquiries page
// =========================================================================
echo "\n--- WORKFLOW 4: Buyer Sort List & Bulk Enquiry ---\n";
// Create Buyer User
$buyerEmail = "buyer_sort_{$uniq1}@example.com";
$pdo->prepare("INSERT INTO users (role, email, password, name, phone, status, email_verified_at, created_at) VALUES ('BUYER', :email, :pass, :name, '9123456789', 'ACTIVE', NOW(), NOW())")
    ->execute([':email' => $buyerEmail, ':pass' => $suppHashed, ':name' => "Buyer Sort $uniq1"]);
$buyerUserId = (int)$pdo->lastInsertId();

// Add both products to Sort List
\App\Models\BuyerFavourite::addToSortList($buyerUserId, $prodId);
\App\Models\BuyerFavourite::addToSortList($buyerUserId, $importedProdId);
$sortCount = \App\Models\BuyerFavourite::countForBuyer($buyerUserId);
assertTest("WF4: Buyer added 2 products to Sort List", $sortCount === 2, "Sort count was {$sortCount}, expected 2");

// Submit multi-product Bulk Enquiry from Sort List
$bulkRes = \App\Models\BulkProductEnquiry::createBulkEnquiry([
    'buyer_user_id' => $buyerUserId,
    'buyer_name' => "Buyer Sort $uniq1",
    'buyer_email' => $buyerEmail,
    'buyer_phone' => '+91 9123456789',
    'company_name' => 'Pro Retail Corp',
    'delivery_location' => 'Mumbai Seaport',
    'overall_message' => "Multi-product consolidated purchase $uniq1",
    'expected_timeline' => '30 Days'
], [
    [
        'product_id' => $prodId,
        'supplier_id' => $suppId,
        'product_name' => "E2E Pro Match Ball $uniq1",
        'target_quantity' => 200,
        'moq_quantity' => 50,
        'moq_unit_name' => 'pieces',
        'target_price' => 1100.0,
        'currency' => 'INR',
        'specifications' => 'Official tournament grade'
    ],
    [
        'product_id' => $importedProdId,
        'supplier_id' => $suppId,
        'product_name' => "Valid Bat $uniq1",
        'target_quantity' => 100,
        'moq_quantity' => 10,
        'moq_unit_name' => 'pieces',
        'target_price' => 1800.0,
        'currency' => 'INR',
        'specifications' => 'English willow grade A'
    ]
]);
$bulkEnqId = (int)($bulkRes['bulk_enquiry_id'] ?? $bulkRes['id'] ?? 0);
assertTest("WF4: Multi-product bulk enquiry created from Sort List", $bulkEnqId > 0, "Bulk enquiry creation failed");

// Super Admin Bulk Product Enquiries listing
$adminBulkDetail = \App\Models\BulkProductEnquiry::getDetailForAdmin($bulkEnqId);
assertTest("WF4: Super Admin dedicated Bulk Product Enquiries page lists submission with items",
    $adminBulkDetail !== null && count($adminBulkDetail['items'] ?? []) === 2,
    "Bulk enquiry missing or items count mismatch from admin view"
);


// =========================================================================
// WORKFLOW 5: RFQ & Fast OEM independent form routing
// =========================================================================
echo "\n--- WORKFLOW 5: RFQ & Fast OEM Form Routing ---\n";
$rfqId = \App\Models\ProductInquiry::create([
    'buyer_name' => "RFQ Buyer $uniq1",
    'buyer_email' => "rfq_{$uniq1}@example.com",
    'buyer_phone' => '+91 9888877777',
    'company_name' => 'National Sports Academy',
    'subject' => "Custom Cricket Kitbags $uniq1",
    'message' => "Need 1000 kitbags with heavy duty zippers",
    'enquiry_source' => 'RFQ',
    'quantity' => 1000,
    'country_name' => 'India'
]);
$fastOemId = \App\Models\ProductInquiry::create([
    'buyer_name' => "OEM Buyer $uniq1",
    'buyer_email' => "oem_{$uniq1}@example.com",
    'buyer_phone' => '+91 9888866666',
    'company_name' => 'Brand Armor International',
    'subject' => "OEM Branded Shin Guards $uniq1",
    'message' => "Custom TPU outer shell with EVA foam lining",
    'enquiry_source' => 'FAST_OEM',
    'quantity' => 3000,
    'country_name' => 'United States'
]);

$rfqCheck = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'RFQ', 'length' => 100]);
$rfqIds = array_column($rfqCheck['data'] ?? [], 'id');
assertTest("WF5: RFQ stored with source RFQ and appears in RFQ page", in_array($rfqId, $rfqIds), "RFQ missing");
assertTest("WF5: Fast OEM does NOT leak into RFQ page", !in_array($fastOemId, $rfqIds), "Fast OEM leaked into RFQ list");

$oemCheck = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'FAST_OEM', 'length' => 100]);
$oemIds = array_column($oemCheck['data'] ?? [], 'id');
assertTest("WF5: Fast OEM appears in dedicated OEM page", in_array($fastOemId, $oemIds), "Fast OEM missing");
assertTest("WF5: RFQ does NOT leak into Fast OEM page", !in_array($rfqId, $oemIds), "RFQ leaked into Fast OEM list");


// =========================================================================
// WORKFLOW 6: Prototype Sample request routing
// =========================================================================
echo "\n--- WORKFLOW 6: Prototype Sample Request Routing ---\n";
$sampleId = \App\Models\ProductInquiry::create([
    'product_id' => $prodId,
    'supplier_id' => $suppId,
    'buyer_name' => "Sample Tester $uniq1",
    'buyer_email' => "sample_{$uniq1}@example.com",
    'buyer_phone' => '+91 9888855555',
    'company_name' => 'Aussie Sports Wholesale',
    'subject' => "Order Pre-production Sample $uniq1",
    'message' => "Need 2 physical prototypes before placing 5000 unit order",
    'enquiry_source' => 'SAMPLE',
    'quantity' => 2,
    'country_name' => 'Australia'
]);

$sampleCheck = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'SAMPLE', 'length' => 100]);
$sampleIds = array_column($sampleCheck['data'] ?? [], 'id');
assertTest("WF6: Prototype sample request appears in dedicated Prototype Sample page", in_array($sampleId, $sampleIds), "Sample missing");

$generalCheck = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'GENERAL', 'length' => 100]);
$generalIds = array_column($generalCheck['data'] ?? [], 'id');
assertTest("WF6: Prototype sample does NOT leak into General Enquiries", !in_array($sampleId, $generalIds), "Sample leaked into General Enquiries");


// =========================================================================
// WORKFLOW 7: Real-time buyer-supplier chat & offer acceptance -> DEAL_AGREED
// =========================================================================
echo "\n--- WORKFLOW 7: Buyer-Supplier Chat & Deal Agreement ---\n";
$conv = \App\Models\ChatConversation::findOrCreate($buyerUserId, $suppId, $prodId);
$convId = (int)($conv['id'] ?? 0);
assertTest("WF7: Conversation thread created between Buyer and Supplier", $convId > 0, "Failed to create conversation");

// Buyer sends message
$msg1 = \App\Models\ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $buyerUserId,
    'sender_role' => 'BUYER',
    'message' => "Can you do Rs. 1050 per ball for 500 pieces?",
    'message_type' => 'TEXT'
]);
assertTest("WF7: Buyer text message delivered", ($msg1['id'] ?? 0) > 0, "Buyer message failed");

// Supplier sends official offer
$msg2 = \App\Models\ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $suppUserId,
    'sender_role' => 'SUPPLIER',
    'message' => "Official Offer: Rs. 1080 per ball with free transit insurance",
    'message_type' => 'OFFER',
    'offer_data' => [
        'unit_price' => 1080.00,
        'quantity' => 500,
        'total_amount' => 540000.00,
        'currency' => 'INR',
        'lead_time_days' => 21,
        'status' => 'PENDING'
    ]
]);
assertTest("WF7: Supplier formal offer sent", ($msg2['id'] ?? 0) > 0, "Supplier offer failed");

// Buyer accepts offer -> Deal Agreed
\App\Models\ChatConversation::updateStatus($convId, 'DEAL_AGREED', 'ACCEPTED');
$agreedConv = \App\Models\ChatConversation::findById($convId);
assertTest("WF7: Chat conversation status updated to DEAL_AGREED", ($agreedConv['status'] ?? '') === 'DEAL_AGREED', "Status not DEAL_AGREED");


// =========================================================================
// WORKFLOW 8: Supplier & Product rejection with mandatory revision reason
// =========================================================================
echo "\n--- WORKFLOW 8: Supplier & Product Rejection with Mandatory Reason ---\n";
// Create product for rejection
$rejectProdData = [
    'supplier_id' => $suppId,
    'category_id' => $catId,
    'subcategory_id' => $subId,
    'title' => "Reject Me Product $uniq1",
    'slug' => "reject-me-{$uniq1}",
    'description' => "Description for rejection testing",
    'price_min' => 500.00,
    'price_max' => 1000.00,
    'price_unit_id' => 1,
    'moq' => 10,
    'moq_unit_id' => 1,
    'country_id' => 1,
    'status' => 'DRAFT',
    'approval_status' => 'PENDING',
    'is_featured' => 0
];
$rejectProdId = \App\Models\Product::create($rejectProdData);

$productRejectReason = "Images blurry; please upload high-resolution product photography on pure white background.";
\App\Models\Product::rejectProduct($rejectProdId, $adminId, $productRejectReason);
$rejectedProd = \App\Models\Product::findById($rejectProdId);

assertTest("WF8: Product rejected with mandatory revision reason recorded",
    ($rejectedProd['approval_status'] ?? '') === 'REJECTED' &&
    ($rejectedProd['rejection_reason'] ?? '') === $productRejectReason &&
    in_array($rejectedProd['status'] ?? '', ['REJECTED', 'INACTIVE', 'DRAFT'], true),
    "Product rejection reason not stored or status incorrect"
);

// Supplier rejection check
$suppRejectEmail = "supp_reject_{$uniq1}@example.com";
$pdo->prepare("INSERT INTO users (role, email, password, name, phone, status, created_at) VALUES ('SUPPLIER', :email, :pass, 'Bad Supplier', '9999999999', 'PENDING_VERIFICATION', NOW())")
    ->execute([':email' => $suppRejectEmail, ':pass' => $suppHashed]);
$badSuppUid = (int)$pdo->lastInsertId();

$badSuppId = \App\Models\Supplier::create([
    'user_id' => $badSuppUid,
    'business_type_id' => 1,
    'country_id' => 1,
    'company_name' => "Unverified Sports Ltd $uniq1",
    'contact_person' => "Bad Contact $uniq1",
    'email' => $suppRejectEmail,
    'phone' => '9999999999',
    'status' => 'INACTIVE',
    'approval_status' => 'PENDING_APPROVAL'
]);

$suppRejectReason = "Invalid GSTIN certificate provided; mismatch with business entity registration.";
\App\Models\Supplier::updateApprovalStatus($badSuppId, 'REJECTED', $suppRejectReason, $adminId);
$rejectedSupplier = \App\Models\Supplier::findById($badSuppId);
$badUser = $pdo->query("SELECT status FROM users WHERE id = {$badSuppUid}")->fetch(PDO::FETCH_ASSOC);

assertTest("WF8: Supplier rejected with verification reason recorded and user marked INACTIVE",
    ($rejectedSupplier['approval_status'] ?? '') === 'REJECTED' &&
    ($rejectedSupplier['rejection_reason'] ?? '') === $suppRejectReason &&
    ($rejectedSupplier['status'] ?? '') === 'INACTIVE' &&
    ($badUser['status'] ?? '') === 'INACTIVE',
    "Supplier rejection notes not stored or supplier/user still active"
);


// =========================================================================
// WORKFLOW 9: Dynamic logo & CMS content updates across public and admin headers
// =========================================================================
echo "\n--- WORKFLOW 9: Dynamic Logo & CMS Updates ---\n";
$originalLogo = \App\Models\SiteSetting::get('site_logo', '');
$testLogoPath = 'uploads/settings/logo_v_' . $uniq1 . '.png';

\App\Models\SiteSetting::set('site_logo', $testLogoPath);
$updatedUrl = \App\Models\SiteSetting::getLogoUrl();

assertTest("WF9: Dynamic site logo setting updated and reflected in getLogoUrl()",
    str_contains($updatedUrl, $testLogoPath),
    "Updated logo URL missing dynamic path: $updatedUrl"
);

// Revert back cleanly
\App\Models\SiteSetting::set('site_logo', $originalLogo);
assertTest("WF9: Reverted logo cleanly", !str_contains(\App\Models\SiteSetting::getLogoUrl(), $testLogoPath), "Failed to revert logo");

echo "\n=======================================================\n";
echo "WORKFLOW TEST SUITE SUMMARY: $passedTests PASSED, $failedTests FAILED\n";
echo "=======================================================\n";

if ($failedTests > 0) {
    exit(1);
}
exit(0);
