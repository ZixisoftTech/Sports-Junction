<?php
declare(strict_types=1);

/**
 * End-to-End Regression Test:
 * Hide Both Chat and Enquiry Buttons on Own Products for Logged-In Suppliers
 * & Enforce Backend API Lockdown for Chat and Inquiries
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Helpers/Session.php';
require_once __DIR__ . '/../app/Helpers/Validator.php';
require_once __DIR__ . '/../app/Helpers/Uploader.php';
require_once __DIR__ . '/../app/Helpers/CSRF.php';
require_once __DIR__ . '/../app/Helpers/ContactPolicy.php';
require_once __DIR__ . '/../app/Helpers/ChatNotifier.php';
require_once __DIR__ . '/../app/Helpers/AuditLogger.php';
require_once __DIR__ . '/../app/Helpers/Mailer.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Supplier.php';
require_once __DIR__ . '/../app/Models/Product.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Subcategory.php';
require_once __DIR__ . '/../app/Models/ProductCertificate.php';
require_once __DIR__ . '/../app/Models/Country.php';
require_once __DIR__ . '/../app/Models/ChatConversation.php';
require_once __DIR__ . '/../app/Models/ChatMessage.php';
require_once __DIR__ . '/../app/Models/ProductInquiry.php';
require_once __DIR__ . '/../app/Controllers/ShopController.php';
require_once __DIR__ . '/../app/Controllers/ChatApiController.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Model;
use App\Helpers\Session;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Controllers\ShopController;
use App\Controllers\ChatApiController;

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$message}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$message}\n";
        $failCount++;
    }
}

echo "========================================================================\n";
echo "TEST SUITE: OWN PRODUCT CHAT & ENQUIRY RESTRICTION (FRONTEND & API)\n";
echo "========================================================================\n\n";

// Setup Test Data:
// Find two distinct approved suppliers with at least one approved product each
$suppliers = Model::fetchAll("
    SELECT s.id, s.user_id, s.company_name, u.email 
    FROM suppliers s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.approval_status = 'APPROVED' AND s.status = 'ACTIVE'
    AND EXISTS (SELECT 1 FROM products p WHERE p.supplier_id = s.id AND p.status IN ('PUBLISHED', 'ACTIVE') AND p.approval_status = 'APPROVED')
    ORDER BY s.id ASC
    LIMIT 2
");

if (count($suppliers) < 2) {
    die("Error: Need at least 2 active approved suppliers with approved products to run tests.\n");
}

$supplierA = $suppliers[0];
$supplierB = $suppliers[1];

$prodA = Model::fetch("SELECT * FROM products WHERE supplier_id = :sid AND status IN ('PUBLISHED', 'ACTIVE') AND approval_status = 'APPROVED' ORDER BY id ASC LIMIT 1", [':sid' => $supplierA['id']]);
$prodB = Model::fetch("SELECT * FROM products WHERE supplier_id = :sid AND status IN ('PUBLISHED', 'ACTIVE') AND approval_status = 'APPROVED' ORDER BY id ASC LIMIT 1", [':sid' => $supplierB['id']]);

$buyer = Model::fetch("SELECT * FROM users WHERE role = 'BUYER' AND status = 'ACTIVE' LIMIT 1");
if (!$buyer) {
    die("Error: Need at least 1 active buyer.\n");
}

echo "Test Fixtures:\n";
echo "  Supplier A: #{$supplierA['id']} ({$supplierA['company_name']}) -> Owns Product #{$prodA['id']} ('{$prodA['title']}')\n";
echo "  Supplier B: #{$supplierB['id']} ({$supplierB['company_name']}) -> Owns Product #{$prodB['id']} ('{$prodB['title']}')\n";
echo "  Buyer: #{$buyer['id']} ({$buyer['email']})\n\n";

// -----------------------------------------------------------------------------
// STEP 1: Model Ownership Helpers Validation
// -----------------------------------------------------------------------------
echo "--- STEP 1: Model Ownership Helpers Validation ---\n";

// Logged out
Session::set('auth_user', null);
Session::set('auth_supplier', null);
assertTest(Supplier::currentSupplierId() === 0, "Guest has currentSupplierId = 0");
assertTest(Supplier::isOwnedByCurrentSupplier($prodA) === false, "Guest does not own Product A");
assertTest(Product::isOwnedByCurrentSupplier($prodA) === false, "Product::isOwnedByCurrentSupplier returns false for guest");

// Logged in as Buyer
Session::set('auth_user', ['id' => (int)$buyer['id'], 'role' => 'BUYER']);
Session::set('auth_supplier', null);
assertTest(Supplier::currentSupplierId() === 0, "Buyer has currentSupplierId = 0");
assertTest(Supplier::isOwnedByCurrentSupplier($prodA) === false, "Buyer does not own Product A");

// Logged in as Supplier A
Session::set('auth_user', ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER']);
Session::set('auth_supplier', $supplierA);
assertTest(Supplier::currentSupplierId() === (int)$supplierA['id'], "Supplier A session resolves currentSupplierId = {$supplierA['id']}");
assertTest(Supplier::isOwnedByCurrentSupplier($prodA) === true, "Supplier A owns Product A");
assertTest(Product::isOwnedByCurrentSupplier($prodA) === true, "Product::isOwnedByCurrentSupplier returns true for Product A");
assertTest(Supplier::isOwnedByCurrentSupplier($prodB) === false, "Supplier A does NOT own Product B");
assertTest(Product::isOwnedByCurrentSupplier($prodB) === false, "Product::isOwnedByCurrentSupplier returns false for Product B");
assertTest(Supplier::isOwnedByCurrentSupplier((int)$supplierA['id']) === true, "Supplier A owns Supplier A ID");
assertTest(Supplier::isOwnedByCurrentSupplier((int)$supplierB['id']) === false, "Supplier A does not own Supplier B ID");

// -----------------------------------------------------------------------------
// STEP 2: View Rendering Test: Product Listing (/products)
// -----------------------------------------------------------------------------
echo "\n--- STEP 2: Product Listing View (/products) Button Rendering ---\n";

function renderProductsView(array $products): string {
    ob_start();
    $categories = [];
    $total = count($products);
    $currentPage = 1;
    $lastPage = 1;
    $from = 1;
    $to = count($products);
    $filters = [];
    $selectedCategory = null;
    $selectedSubcategory = null;
    include __DIR__ . '/../app/Views/frontend/products.php';
    return ob_get_clean();
}

// Log in as Supplier A
Session::set('auth_user', ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER']);
Session::set('auth_supplier', $supplierA);

$productsList = [$prodA, $prodB];
$htmlSupplierA = renderProductsView($productsList);

// On Product A: Chat and Specs & Samples buttons should be HIDDEN, Manage / Edit shown
$hasChatA = str_contains($htmlSupplierA, "&quot;id&quot;:{$prodA['id']}") || str_contains($htmlSupplierA, "\"id\":{$prodA['id']}");
assertTest(!$hasChatA, "Supplier A on /products: Chat button for Product A is NOT rendered");
assertTest(str_contains($htmlSupplierA, "/supplier/products/edit/{$prodA['id']}"), "Supplier A on /products: Manage/Edit button for Product A IS rendered");

// On Product B: Chat button and Specs & Samples should be VISIBLE
$hasChatB = str_contains($htmlSupplierA, "&quot;id&quot;:{$prodB['id']}") || str_contains($htmlSupplierA, "\"id\":{$prodB['id']}");
assertTest($hasChatB, "Supplier A on /products: Chat button for Product B (other supplier) IS rendered");
assertTest(str_contains($htmlSupplierA, "Specs & Samples"), "Supplier A on /products: Specs & Samples for Product B IS rendered");

// Log in as Buyer
Session::set('auth_user', ['id' => (int)$buyer['id'], 'role' => 'BUYER']);
Session::set('auth_supplier', null);
$htmlBuyer = renderProductsView($productsList);

$buyerHasChatA = str_contains($htmlBuyer, "&quot;id&quot;:{$prodA['id']}") || str_contains($htmlBuyer, "\"id\":{$prodA['id']}");
$buyerHasChatB = str_contains($htmlBuyer, "&quot;id&quot;:{$prodB['id']}") || str_contains($htmlBuyer, "\"id\":{$prodB['id']}");
assertTest($buyerHasChatA, "Buyer on /products: Chat button for Product A IS rendered");
assertTest($buyerHasChatB, "Buyer on /products: Chat button for Product B IS rendered");
assertTest(!str_contains($htmlBuyer, "/supplier/products/edit/"), "Buyer on /products: Manage/Edit buttons are NOT rendered");

// -----------------------------------------------------------------------------
// STEP 3: View Rendering Test: Product Detail (/product/{slug})
// -----------------------------------------------------------------------------
echo "\n--- STEP 3: Product Detail View (/product/{slug}) Button Rendering ---\n";

function renderProductDetailView(array $product): string {
    ob_start();
    $relatedProducts = [];
    $isFavourite = false;
    include __DIR__ . '/../app/Views/frontend/product_detail.php';
    return ob_get_clean();
}

$prodDetailA = Product::findBySlug($prodA['slug']) ?? Product::findById((int)$prodA['id']);
$prodDetailB = Product::findBySlug($prodB['slug']) ?? Product::findById((int)$prodB['id']);

// Log in as Supplier A viewing own Product A
Session::set('auth_user', ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER']);
Session::set('auth_supplier', $supplierA);

$detailA_by_A = renderProductDetailView($prodDetailA);
$hasBannerA = str_contains($detailA_by_A, "This is your listed product") || str_contains($detailA_by_A, "registered manufacturer");
assertTest($hasBannerA, "Supplier A on own product detail: Informational banner displayed");
assertTest(!str_contains($detailA_by_A, "Send Direct Factory Inquiry"), "Supplier A on own product detail: 'Send Direct Factory Inquiry' button HIDDEN");
assertTest(!str_contains($detailA_by_A, "Chat & Negotiate with Factory"), "Supplier A on own product detail: 'Chat & Negotiate with Factory' button HIDDEN");
assertTest(!str_contains($detailA_by_A, "Contact Factory Rep"), "Supplier A on own product detail: 'Contact Factory Rep' button HIDDEN");
assertTest(!str_contains($detailA_by_A, "Order Prototype Sample"), "Supplier A on own product detail: Sample inquiry button HIDDEN");
assertTest(str_contains($detailA_by_A, "/supplier/products/edit/{$prodA['id']}"), "Supplier A on own product detail: Edit Product button displayed");

// Supplier A viewing other Supplier B's Product B
$detailB_by_A = renderProductDetailView($prodDetailB);
$hasBannerB = str_contains($detailB_by_A, "This is your listed product") || str_contains($detailB_by_A, "registered manufacturer");
assertTest(!$hasBannerB, "Supplier A on Product B detail: Owner banner NOT displayed");
assertTest(str_contains($detailB_by_A, "Send Direct Factory Inquiry"), "Supplier A on Product B detail: 'Send Direct Factory Inquiry' button VISIBLE");
assertTest(str_contains($detailB_by_A, "Chat & Negotiate with Factory"), "Supplier A on Product B detail: 'Chat & Negotiate with Factory' button VISIBLE");
assertTest(str_contains($detailB_by_A, "Contact Factory Rep"), "Supplier A on Product B detail: 'Contact Factory Rep' button VISIBLE");

// Buyer viewing Product A
Session::set('auth_user', ['id' => (int)$buyer['id'], 'role' => 'BUYER']);
Session::set('auth_supplier', null);
$detailA_by_Buyer = renderProductDetailView($prodDetailA);
assertTest(str_contains($detailA_by_Buyer, "Send Direct Factory Inquiry"), "Buyer on Product A detail: 'Send Direct Factory Inquiry' button VISIBLE");
assertTest(str_contains($detailA_by_Buyer, "Chat & Negotiate with Factory"), "Buyer on Product A detail: 'Chat & Negotiate with Factory' button VISIBLE");

// -----------------------------------------------------------------------------
// STEP 4: View Rendering Test: Supplier Profile (/supplier/{id})
// -----------------------------------------------------------------------------
echo "\n--- STEP 4: Supplier Profile View (/supplier/{id}) Button Rendering ---\n";

function renderSupplierProfileView(array $supplier, array $products): string {
    ob_start();
    include __DIR__ . '/../app/Views/frontend/supplier_profile.php';
    return ob_get_clean();
}

$fullSupplierA = Supplier::findById((int)$supplierA['id']);
$fullSupplierB = Supplier::findById((int)$supplierB['id']);

// Log in as Supplier A viewing own profile
Session::set('auth_user', ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER']);
Session::set('auth_supplier', $supplierA);

$profileA_by_A = renderSupplierProfileView($fullSupplierA, [$prodA]);
assertTest(!str_contains($profileA_by_A, "Chat with Factory"), "Supplier A on own profile: 'Chat with Factory' button HIDDEN");
assertTest(str_contains($profileA_by_A, "Edit My Factory Profile"), "Supplier A on own profile: 'Edit My Factory Profile' button VISIBLE");
assertTest(!str_contains($profileA_by_A, "openProductChat({id: {$prodA['id']}"), "Supplier A on own profile: Product chat button HIDDEN");

// Buyer viewing Supplier A's profile
Session::set('auth_user', ['id' => (int)$buyer['id'], 'role' => 'BUYER']);
Session::set('auth_supplier', null);

$profileA_by_Buyer = renderSupplierProfileView($fullSupplierA, [$prodA]);
assertTest(str_contains($profileA_by_Buyer, "Chat with Factory"), "Buyer on Supplier A profile: 'Chat with Factory' button VISIBLE");
assertTest(str_contains($profileA_by_Buyer, "openProductChat({id: {$prodA['id']}"), "Buyer on Supplier A profile: Product chat button VISIBLE");

// -----------------------------------------------------------------------------
// STEP 5: Backend Chat API Lockdown
// -----------------------------------------------------------------------------
echo "\n--- STEP 5: Backend Chat API Lockdown (POST /api/chat/conversations/start) ---\n";

function testChatStartApi(array $postData, array $authUser, ?array $authSupplier = null): array {
    Session::set('auth_user', $authUser);
    Session::set('auth_supplier', $authSupplier);
    
    $_POST = $postData;
    $_GET = [];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    $req = new \App\Core\Request();
    $res = new \App\Core\Response();
    
    $controller = new class($req, $res) extends ChatApiController {
        public $capturedJson = null;
        public $capturedStatus = 200;
        
        protected function json($data, int $statusCode = 200): void {
            $this->capturedJson = $data;
            $this->capturedStatus = $statusCode;
        }
    };
    
    $controller->startConversation();
    return ['status' => $controller->capturedStatus, 'json' => $controller->capturedJson];
}

// Case 5.1: Supplier A attempts to start chat for own product
$res = testChatStartApi([
    'product_id' => $prodA['id'],
    'supplier_id' => $supplierA['id'],
    'initial_message' => 'Hello self'
], ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER'], $supplierA);

assertTest($res['status'] === 403, "Supplier A starting chat on own product returns HTTP 403 (Got: {$res['status']})");
assertTest($res['json']['success'] === false, "Supplier A starting chat on own product has success = false");
assertTest(str_contains($res['json']['message'] ?? '', 'own product'), "Error message mentions own product restriction ('{$res['json']['message']}')");

// Case 5.2: Supplier A attempts to start chat with own supplier store directly
$res2 = testChatStartApi([
    'supplier_id' => $supplierA['id'],
    'initial_message' => 'Hello own store'
], ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER'], $supplierA);

assertTest($res2['status'] === 403, "Supplier A starting chat on own supplier store returns HTTP 403 (Got: {$res2['status']})");
assertTest(str_contains($res2['json']['message'] ?? '', 'own supplier store'), "Error message mentions own supplier store restriction ('{$res2['json']['message']}')");

// Case 5.3: Buyer starts chat for Supplier A's product -> MUST BE ALLOWED
$res3 = testChatStartApi([
    'product_id' => $prodA['id'],
    'supplier_id' => $supplierA['id'],
    'initial_message' => 'Wholesale inquiry for cricket gear'
], ['id' => (int)$buyer['id'], 'role' => 'BUYER'], null);

assertTest($res3['status'] === 200, "Buyer starting chat on Supplier A product returns HTTP 200 (Got: {$res3['status']})");
assertTest($res3['json']['success'] === true, "Buyer starting chat on Supplier A product succeeds");
assertTest(!empty($res3['json']['conversation']['id']), "Chat conversation was created/retrieved successfully");

// -----------------------------------------------------------------------------
// STEP 6: Backend Inquiry API Lockdown
// -----------------------------------------------------------------------------
echo "\n--- STEP 6: Backend Inquiry API Lockdown (POST /api/inquiries) ---\n";

function testInquirySubmitApi(array $postData, array $authUser, ?array $authSupplier = null): array {
    Session::set('auth_user', $authUser);
    Session::set('auth_supplier', $authSupplier);
    
    $_POST = $postData;
    $_GET = [];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    $req = new \App\Core\Request();
    $res = new \App\Core\Response();
    
    $controller = new class($req, $res) extends ShopController {
        public $capturedJson = null;
        public $capturedStatus = 200;
        
        protected function json($data, int $statusCode = 200): void {
            $this->capturedJson = $data;
            $this->capturedStatus = $statusCode;
        }
    };
    
    $controller->submitInquiry();
    return ['status' => $controller->capturedStatus, 'json' => $controller->capturedJson];
}

// Case 6.1: Supplier A attempts to submit inquiry on own product
$resInq1 = testInquirySubmitApi([
    'product_id' => $prodA['id'],
    'supplier_id' => $supplierA['id'],
    'buyer_email' => 'supplier_a@test.com',
    'buyer_name' => 'Supplier A',
    'specifications' => 'Self-inquiry test',
    'target_quantity' => 50
], ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER'], $supplierA);

assertTest($resInq1['status'] === 403, "Supplier A submitting inquiry on own product returns HTTP 403 (Got: {$resInq1['status']})");
assertTest($resInq1['json']['success'] === false, "Supplier A submitting inquiry on own product has success = false");
assertTest(str_contains($resInq1['json']['message'] ?? '', 'own products'), "Error message mentions 'own products' ('{$resInq1['json']['message']}')");

// Case 6.2: Supplier A attempts to submit inquiry to own supplier store without product_id
$resInq2 = testInquirySubmitApi([
    'supplier_id' => $supplierA['id'],
    'buyer_email' => 'supplier_a@test.com',
    'buyer_name' => 'Supplier A',
    'specifications' => 'Self-inquiry test direct to store',
    'target_quantity' => 100
], ['id' => (int)$supplierA['user_id'], 'role' => 'SUPPLIER'], $supplierA);

assertTest($resInq2['status'] === 403, "Supplier A submitting inquiry to own store returns HTTP 403 (Got: {$resInq2['status']})");
assertTest(str_contains($resInq2['json']['message'] ?? '', 'own supplier store'), "Error message mentions 'own supplier store' ('{$resInq2['json']['message']}')");

// Case 6.3: Buyer submits inquiry on Supplier A product -> MUST BE ALLOWED
$resInq3 = testInquirySubmitApi([
    'product_id' => $prodA['id'],
    'buyer_email' => 'legit_buyer@test.com',
    'buyer_name' => 'Legit Buyer',
    'specifications' => 'Legitimate export purchase requirement with custom branding',
    'target_quantity' => 250
], ['id' => (int)$buyer['id'], 'role' => 'BUYER'], null);

assertTest($resInq3['status'] === 200, "Buyer submitting inquiry on Supplier A product returns HTTP 200 (Got: {$resInq3['status']})");
assertTest($resInq3['json']['success'] === true, "Buyer inquiry submission succeeds");
assertTest(!empty($resInq3['json']['id'] ?? $resInq3['json']['inquiry_id']), "Inquiry ID created: #" . ($resInq3['json']['id'] ?? $resInq3['json']['inquiry_id'] ?? 'none'));

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n========================================================================\n";
echo "SUMMARY: Passed: {$passCount}, Failed: {$failCount}\n";
echo "========================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
