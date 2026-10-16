<?php
declare(strict_types=1);

/**
 * Cross-Feature Integration Test Suite for Sports Junction B2B Marketplace
 * Executes and verifies Workflows A through E
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
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\ProductInquiry;
use App\Models\ProductCertificate;
use App\Models\SupplierCertificationTag;
use App\Models\CmsPage;
use App\Models\SiteSetting;
use App\Middleware\BuyerAuthMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Session;

class MockResponse extends Response {
    public int $testStatus = 200;
    public ?string $testRedirect = null;
    public ?array $testJson = null;

    public function json(array $data, int $statusCode = 200): void {
        $this->testStatus = $statusCode;
        $this->testJson = $data;
    }

    public function redirect(string $path): void {
        $this->testStatus = 302;
        $this->testRedirect = $path;
    }
}

// Helper to login as Super Admin with cookie jar
function loginAdmin(string $cookieFile): bool {
    $ch = curl_init('http://localhost/Antigravity/Sports-Junction/admin/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return false;

    preg_match('/name="csrf_token"\s+value="([^"]+)"/i', (string)$html, $matches);
    $csrf = $matches[1] ?? '';

    $ch = curl_init('http://localhost/Antigravity/Sports-Junction/admin/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'email'      => 'sportjunctions@gmail.com',
        'password'   => 'Sport@123',
        'csrf_token' => $csrf
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200 && str_contains((string)$res, 'Dashboard'));
}

$pdo = Database::getConnection();

echo "======================================================================\n";
echo "   SPORTS JUNCTION — CROSS-FEATURE INTEGRATION TESTING (WORKFLOWS A-E) \n";
echo "======================================================================\n\n";

$workflowsPassed = 0;
$totalWorkflows = 5;

// ======================================================================
// WORKFLOW A: Categories & Products Full Lifecycle
// ======================================================================
echo ">>> EXECUTING WORKFLOW A: Categories & Products Lifecycle...\n";
try {
    $time = time();
    // 1. Create Category with image
    $catSlug = 'test-int-category-' . $time;
    $catId = Category::create([
        'name'          => 'Integration Test Equipment ' . $time,
        'slug'          => $catSlug,
        'description'   => 'Automated integration testing category',
        'icon'          => 'bi-trophy',
        'image'         => '/uploads/categories/test_int_cat.png',
        'status'        => 'ACTIVE',
        'display_order' => 99,
    ]);

    // 2. Create Subcategory with image
    $subcatSlug = 'test-int-subcat-' . $time;
    $subcatId = Subcategory::create([
        'category_id'   => $catId,
        'name'          => 'Integration Test Balls ' . $time,
        'slug'          => $subcatSlug,
        'description'   => 'Automated integration testing subcategory',
        'image'         => '/uploads/subcategories/test_int_subcat.png',
        'status'        => 'ACTIVE',
        'display_order' => 1,
    ]);

    // 3. Supplier adds product in PENDING_APPROVAL
    $supplier = Supplier::allApproved()[0] ?? null;
    if (!$supplier) {
        throw new RuntimeException("No approved supplier found for Workflow A.");
    }

    $prodSlug = 'int-product-ball-' . $time;
    $prodId = Product::create([
        'supplier_id'    => (int)$supplier['id'],
        'category_id'    => $catId,
        'subcategory_id' => $subcatId,
        'title'          => 'Pro Match Rugby Ball ' . $time,
        'slug'           => $prodSlug,
        'price'          => 45.00,
        'min_price'      => 40.00,
        'max_price'      => 50.00,
        'currency'       => 'USD',
        'price_unit_id'  => 1,
        'moq_quantity'   => 20,
        'moq_unit_id'    => 1,
        'status'         => 'ACTIVE',
        'approval_status'=> 'PENDING_APPROVAL',
    ]);

    $savedProd = Product::findById($prodId);
    if (($savedProd['approval_status'] ?? '') !== 'PENDING_APPROVAL') {
        throw new RuntimeException("Product not saved with PENDING_APPROVAL.");
    }

    // 4. Admin approves product
    Product::approveProduct($prodId, 1);
    $approvedProd = Product::findById($prodId);
    if (($approvedProd['approval_status'] ?? '') !== 'APPROVED') {
        throw new RuntimeException("Product approval failed.");
    }

    // 5. Verify query returns product in catalog
    $catalogResult = Product::getFilteredProducts(['category_id' => $catId]);
    $catalogProducts = $catalogResult['products'] ?? [];
    $found = false;
    foreach ($catalogProducts as $p) {
        if ((int)$p['id'] === $prodId) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        throw new RuntimeException("Approved product not found in active catalog listing for category #{$catId}.");
    }

    echo "  [WORKFLOW A PASSED]: Category #{$catId} & Subcategory #{$subcatId} created -> Product #{$prodId} submitted -> Admin approved -> Verified in live catalog.\n\n";
    $workflowsPassed++;
} catch (\Throwable $e) {
    echo "  [WORKFLOW A FAILED]: " . $e->getMessage() . "\n\n";
}

// ======================================================================
// WORKFLOW B: Buyer Registration, Country Master & Admin Management
// ======================================================================
echo ">>> EXECUTING WORKFLOW B: Buyer Registration, Country Master & Admin Management...\n";
try {
    $time = time();
    $buyerEmail = "int_buyer_{$time}@sportsjunction.test";
    $buyerPassword = password_hash('BuyerPass@123', PASSWORD_BCRYPT);

    // 1. Buyer registers with country_id = 1 (India)
    $buyerId = User::createBuyer([
        'name'         => 'Integration Buyer ' . $time,
        'email'        => $buyerEmail,
        'password'     => $buyerPassword,
        'country_id'   => 1,
        'company_name' => 'Initial Buyer Corp ' . $time,
        'phone'        => '+919876543299',
    ]);

    $createdBuyer = User::findById($buyerId);
    if (!$createdBuyer || (int)$createdBuyer['country_id'] !== 1) {
        throw new RuntimeException("Buyer created without proper country_id mapping.");
    }

    // 2. Admin views buyer detail
    $buyerDetail = User::getBuyerDetails($buyerId);
    if (!$buyerDetail || ($buyerDetail['country_name'] ?? '') === '') {
        throw new RuntimeException("Admin getBuyerDetails failed to resolve country name.");
    }

    // 3. Admin updates buyer profile
    $updateResult = User::updateBuyerByAdmin($buyerId, [
        'name'         => 'Updated Integration Buyer ' . $time,
        'company_name' => 'Apex Sporting Goods LLC',
        'email'        => $buyerEmail,
        'phone'        => '+919999988888',
        'country_id'   => 2, // Switch country to United States (id 2)
        'state_id'     => 1,
        'city_id'      => 1,
    ]);

    if (!($updateResult['success'] ?? false)) {
        throw new RuntimeException("User::updateBuyerByAdmin failed: " . ($updateResult['message'] ?? ''));
    }

    $updatedBuyer = User::findById($buyerId);
    if ((int)$updatedBuyer['country_id'] !== 2 || $updatedBuyer['company_name'] !== 'Apex Sporting Goods LLC') {
        throw new RuntimeException("Admin update buyer changes did not persist.");
    }

    // 4. Admin deactivates buyer
    User::setBuyerStatus($buyerId, 'INACTIVE');
    $deactivatedBuyer = User::findById($buyerId);
    if ($deactivatedBuyer['status'] !== 'INACTIVE') {
        throw new RuntimeException("Failed to set buyer status to INACTIVE.");
    }

    // 5. Verify BuyerAuthMiddleware rejects deactivated buyer
    Session::start();
    Session::set('auth_user', [
        'id'     => $buyerId,
        'name'   => $deactivatedBuyer['name'],
        'email'  => $deactivatedBuyer['email'],
        'role'   => 'BUYER',
        'status' => 'INACTIVE',
    ]);
    $req = new Request();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
    $res = new MockResponse();
    $mid = new BuyerAuthMiddleware();
    $allowedWhenInactive = $mid->handle($req, $res);

    if ($allowedWhenInactive !== false || $res->testStatus !== 403) {
        throw new RuntimeException("Deactivated buyer was not rejected with 403 Forbidden.");
    }

    // 6. Admin reactivates buyer
    User::setBuyerStatus($buyerId, 'ACTIVE');
    $resActive = new MockResponse();
    $allowedWhenActive = $mid->handle($req, $resActive);

    if ($allowedWhenActive !== true) {
        throw new RuntimeException("Reactivated buyer was not granted access.");
    }

    echo "  [WORKFLOW B PASSED]: Buyer #{$buyerId} registered with Country #1 -> Admin edited to Country #2 -> Deactivated & blocked with 403 -> Reactivated successfully.\n\n";
    $workflowsPassed++;
} catch (\Throwable $e) {
    echo "  [WORKFLOW B FAILED]: " . $e->getMessage() . "\n\n";
}

// ======================================================================
// WORKFLOW C: Website Settings, CMS & Socials Integration
// ======================================================================
echo ">>> EXECUTING WORKFLOW C: Website Settings, CMS & Socials Integration...\n";
try {
    $time = time();
    $origSettings = SiteSetting::getAllKeyed();

    // 1. Update settings: site name, email, phone, and 3 social links
    SiteSetting::set('site_name', 'Sports Junction Global ' . $time, 'branding');
    SiteSetting::set('contact_email', "support_{$time}@sportsjunction.test", 'contact');
    SiteSetting::set('contact_phone', '+1-800-555-' . substr((string)$time, -4), 'contact');
    SiteSetting::set('social_facebook', "https://facebook.com/sj_test_{$time}", 'social');
    SiteSetting::set('social_twitter', "https://x.com/sj_test_{$time}", 'social');
    SiteSetting::set('social_instagram', "https://instagram.com/sj_test_{$time}", 'social');

    // 2. Create dynamic CMS page in ORDER_PROTECTION column
    $cmsSlug = 'buyer-protection-guarantee-' . $time;
    $cmsId = CmsPage::create([
        'title'           => 'Buyer Protection Guarantee ' . $time,
        'slug'            => $cmsSlug,
        'content'         => '<p>Comprehensive escrow and factory inspection coverage.</p>',
        'meta_title'      => 'Buyer Protection Guarantee',
        'footer_column'   => 'ORDER_PROTECTION',
        'sort_order'      => 10,
        'is_active'       => 1,
    ]);

    // 3. Verify public footer includes dynamic CMS link and social channels
    $ch = curl_init('http://localhost/Antigravity/Sports-Junction/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $homeHtml = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$homeHtml) {
        throw new RuntimeException("Homepage failed to load with HTTP {$httpCode}");
    }

    if (!str_contains($homeHtml, $cmsSlug) ||
        !str_contains($homeHtml, "https://facebook.com/sj_test_{$time}") ||
        !str_contains($homeHtml, "https://x.com/sj_test_{$time}") ||
        !str_contains($homeHtml, "https://instagram.com/sj_test_{$time}")) {
        throw new RuntimeException("Homepage footer does not reflect updated CMS link or social channels.");
    }

    // 4. Restore settings
    foreach ($origSettings as $k => $v) {
        SiteSetting::set($k, (string)$v, 'general');
    }
    // Clean test CMS page
    CmsPage::delete($cmsId);

    echo "  [WORKFLOW C PASSED]: Settings & CMS Page #{$cmsId} created -> Public footer renders 3 social links and CMS link -> Cleanly restored.\n\n";
    $workflowsPassed++;
} catch (\Throwable $e) {
    echo "  [WORKFLOW C FAILED]: " . $e->getMessage() . "\n\n";
}

// ======================================================================
// WORKFLOW D: Enquiries & Attachments Full Lifecycle
// ======================================================================
echo ">>> EXECUTING WORKFLOW D: Enquiries & Attachments Full Lifecycle...\n";
try {
    $time = time();
    $supplier = Supplier::allApproved()[0] ?? null;
    if (!$supplier) {
        throw new RuntimeException("No approved supplier found for Workflow D.");
    }

    // 1. Submit RFQ Enquiry with attachment via public HTTP API
    $testPdfPath = tempnam(sys_get_temp_dir(), 'test_spec_') . '.pdf';
    file_put_contents($testPdfPath, "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\ntrailer\n<<\n>>\n%%EOF\nWorkflow D Tech Pack");
    $cfile = new CURLFile($testPdfPath, 'application/pdf', 'Workflow_D_TechPack.pdf');

    $ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/inquiries');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'buyer_name'      => 'Workflow D Officer',
        'buyer_email'     => "techpack_{$time}@apexsports.com",
        'company_name'    => 'Apex Sports Global',
        'target_quantity' => '1000',
        'specifications'  => 'International grade rugby balls according to attached spec sheet.',
        'enquiry_source'  => 'FAST_OEM',
        'attachment'      => $cfile
    ]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    @unlink($testPdfPath);

    $json = json_decode((string)$response, true);
    if ($httpCode !== 200 || empty($json['success']) || empty($json['id'])) {
        throw new RuntimeException("Failed to submit enquiry with attachment: HTTP {$httpCode} - {$response}");
    }
    $inqId = (int)$json['id'];

    $savedInq = ProductInquiry::findById($inqId, true);
    if (!$savedInq || $savedInq['attachment_name'] !== 'Workflow_D_TechPack.pdf') {
        throw new RuntimeException("Enquiry not saved with correct attachment metadata.");
    }

    // 2. Admin assigns supplier
    ProductInquiry::assignSupplier($inqId, (int)$supplier['id']);
    $assignedInq = ProductInquiry::findById($inqId);
    if ((int)$assignedInq['supplier_id'] !== (int)$supplier['id']) {
        throw new RuntimeException("Failed to assign supplier to enquiry.");
    }

    // 3. Verify assigned supplier can view enquiry
    $suppEnquiries = ProductInquiry::getSupplierEnquiries((int)$supplier['id']);
    $foundInq = false;
    foreach ($suppEnquiries as $eq) {
        if ((int)$eq['id'] === $inqId) {
            $foundInq = true;
            break;
        }
    }
    if (!$foundInq) {
        throw new RuntimeException("Assigned enquiry not found in Supplier's enquiry list.");
    }

    // 4. Super Admin downloads attachment
    $cookieFile = tempnam(sys_get_temp_dir(), 'admin_curl_');
    if (!loginAdmin($cookieFile)) {
        throw new RuntimeException("Could not authenticate as Super Admin for download test.");
    }

    $chDownload = curl_init("http://localhost/Antigravity/Sports-Junction/admin/enquiries/attachment/{$inqId}");
    curl_setopt($chDownload, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chDownload, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($chDownload, CURLOPT_COOKIEFILE, $cookieFile);
    $downloadContent = curl_exec($chDownload);
    $dlCode = curl_getinfo($chDownload, CURLINFO_HTTP_CODE);
    curl_close($chDownload);
    @unlink($cookieFile);

    if ($dlCode !== 200 || !str_contains((string)$downloadContent, 'Workflow D Tech Pack')) {
        throw new RuntimeException("Super Admin failed to download enquiry attachment (HTTP {$dlCode}).");
    }

    echo "  [WORKFLOW D PASSED]: Enquiry #{$inqId} created with PDF attachment -> Assigned to Supplier #{$supplier['id']} -> Verified in Supplier desk -> Super Admin downloaded attachment (200 OK).\n\n";
    $workflowsPassed++;
} catch (\Throwable $e) {
    echo "  [WORKFLOW D FAILED]: " . $e->getMessage() . "\n\n";
}

// ======================================================================
// WORKFLOW E: Supplier & Admin Product Editing & Certificates Integrity
// ======================================================================
echo ">>> EXECUTING WORKFLOW E: Product Editing & ProductCertificate Integrity...\n";
try {
    $time = time();
    $supplier = Supplier::allApproved()[0] ?? null;
    if (!$supplier) {
        throw new RuntimeException("No approved supplier found for Workflow E.");
    }

    // 1. Create product
    $editProdId = Product::create([
        'supplier_id'    => (int)$supplier['id'],
        'category_id'    => 1,
        'subcategory_id' => 1,
        'title'          => 'Multi-Certificate Training Equipment ' . $time,
        'slug'           => 'multi-cert-equipment-' . $time,
        'price'          => 120.00,
        'min_price'      => 100.00,
        'max_price'      => 140.00,
        'currency'       => 'USD',
        'price_unit_id'  => 1,
        'moq_quantity'   => 5,
        'moq_unit_id'    => 1,
        'status'         => 'ACTIVE',
        'approval_status'=> 'APPROVED',
    ]);

    // 2. Fetch available certificates & tags
    $certs = ProductCertificate::allActive();
    $tags = SupplierCertificationTag::allActive();
    $certIdsToSync = array_slice(array_column($certs, 'id'), 0, 2);
    $tagIdsToSync = array_slice(array_column($tags, 'id'), 0, 2);

    // 3. Edit product: update title and sync certificates + tags
    Product::beginTransaction();
    Product::update($editProdId, [
        'title'       => 'Multi-Certificate Training Equipment (Updated) ' . $time,
        'description' => 'Updated description with certified quality standards.',
        'min_price'   => 105.00,
    ]);

    // Test that ProductCertificate::syncForProduct executes cleanly without getConnection() error
    ProductCertificate::syncForProduct($editProdId, $certIdsToSync);
    SupplierCertificationTag::syncForSupplier((int)$supplier['id'], $tagIdsToSync);
    Product::commit();

    // 4. Verify DB persistence of synced certificates and tags
    $assignedCertIds = ProductCertificate::getAssignedIds($editProdId);
    $assignedTagIds = SupplierCertificationTag::getAssignedIds((int)$supplier['id']);

    if (count($assignedCertIds) !== count($certIdsToSync)) {
        throw new RuntimeException("Certificate count mismatch after syncForProduct.");
    }

    $updatedProduct = Product::findById($editProdId);
    if (!str_contains($updatedProduct['title'], '(Updated)')) {
        throw new RuntimeException("Product title update did not persist in database.");
    }

    echo "  [WORKFLOW E PASSED]: Product #{$editProdId} updated -> ProductCertificate & CertificationTag synced transactionally without getConnection() errors -> Verified in DB.\n\n";
    $workflowsPassed++;
} catch (\Throwable $e) {
    if (Product::getConnection()->inTransaction()) {
        Product::rollback();
    }
    echo "  [WORKFLOW E FAILED]: " . $e->getMessage() . "\n\n";
}

// ======================================================================
// SUMMARY
// ======================================================================
echo "======================================================================\n";
echo "CROSS-FEATURE INTEGRATION TESTING SUMMARY\n";
echo "Workflows Passed: {$workflowsPassed} / {$totalWorkflows}\n";
echo "======================================================================\n";

if ($workflowsPassed === $totalWorkflows) {
    echo "ALL 5 CROSS-FEATURE INTEGRATION WORKFLOWS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "INTEGRATION SUITE FAILED: " . ($totalWorkflows - $workflowsPassed) . " workflow(s) failed.\n";
    exit(1);
}
