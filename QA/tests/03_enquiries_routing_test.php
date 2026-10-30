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

echo "=== TEST SUITE 3: ALL 9 ENQUIRY TYPES & STRICT ROUTING ISOLATION ===\n";

$pdo = \App\Core\Database::getConnection();

// Sample product and supplier for relations
$sampleProd = $pdo->query("SELECT id, supplier_id, title FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$prodId = $sampleProd ? (int)$sampleProd['id'] : 1;
$suppId = $sampleProd ? (int)$sampleProd['supplier_id'] : 1;

// 1. Submit Product Page Enquiry
$inq1Id = \App\Models\ProductInquiry::create([
    'product_id' => $prodId,
    'supplier_id' => $suppId,
    'buyer_name' => 'QA Buyer Product Page',
    'buyer_email' => 'qa.buyer.product@example.com',
    'buyer_phone' => '+91 9876500001',
    'company_name' => 'Retail Sporting Goods Inc',
    'subject' => 'Send Direct Factory Inquiry: ' . ($sampleProd['title'] ?? 'Product'),
    'message' => 'Please quote for 500 units FOB Mumbai.',
    'enquiry_source' => 'PRODUCT_PAGE',
    'quantity' => 500,
    'country_name' => 'India'
]);
assertTest("Product Page Inquiry created", $inq1Id > 0);

// 2. Submit RFQ Enquiry
$inq2Id = \App\Models\ProductInquiry::create([
    'buyer_name' => 'QA Buyer RFQ',
    'buyer_email' => 'qa.buyer.rfq@example.com',
    'buyer_phone' => '+91 9876500002',
    'company_name' => 'Global Imports UK',
    'subject' => 'Request for Quotation: Inflatable Training Soccer Balls',
    'message' => 'Need 2000 balls with custom FIFA-standard stitching.',
    'enquiry_source' => 'RFQ',
    'quantity' => 2000,
    'country_name' => 'United Kingdom'
]);
assertTest("RFQ Inquiry created", $inq2Id > 0);

// 3. Submit Fast OEM Enquiry
$inq3Id = \App\Models\ProductInquiry::create([
    'product_id' => $prodId,
    'supplier_id' => $suppId,
    'buyer_name' => 'QA Buyer Fast OEM',
    'buyer_email' => 'qa.buyer.oem@example.com',
    'buyer_phone' => '+91 9876500003',
    'company_name' => 'Custom Gear Germany',
    'subject' => 'Fast OEM Customization: Private Label Gym Gloves',
    'message' => 'We require our logo embroidered on the wrist strap and custom retail packaging.',
    'enquiry_source' => 'FAST_OEM',
    'quantity' => 1000,
    'country_name' => 'Germany'
]);
assertTest("Fast OEM Inquiry created", $inq3Id > 0);

// 4. Submit Prototype Sample Enquiry
$inq4Id = \App\Models\ProductInquiry::create([
    'product_id' => $prodId,
    'supplier_id' => $suppId,
    'buyer_name' => 'QA Buyer Prototype',
    'buyer_email' => 'qa.buyer.sample@example.com',
    'buyer_phone' => '+91 9876500004',
    'company_name' => 'Aussie Sports Wholesale',
    'subject' => 'Order Prototype Sample: Match Bat',
    'message' => 'Please courier 2 evaluation samples to Melbourne address before production run.',
    'enquiry_source' => 'SAMPLE',
    'quantity' => 2,
    'country_name' => 'Australia'
]);
assertTest("Prototype Sample Inquiry created", $inq4Id > 0);

// 5. Submit General Enquiry
$inq5Id = \App\Models\ProductInquiry::create([
    'buyer_name' => 'QA Buyer General',
    'buyer_email' => 'qa.buyer.general@example.com',
    'buyer_phone' => '+91 9876500005',
    'company_name' => 'Sports League USA',
    'subject' => 'General Sourcing Inquiry: Stadium Padding',
    'message' => 'Looking for manufacturers capable of producing impact protection pads.',
    'enquiry_source' => 'GENERAL',
    'country_name' => 'United States'
]);
assertTest("General Inquiry created", $inq5Id > 0);

// 6. Submit Contact Us Enquiry
$inq6Id = \App\Models\ProductInquiry::create([
    'buyer_name' => 'QA Visitor Contact Us',
    'buyer_email' => 'qa.visitor.contact@example.com',
    'buyer_phone' => '+91 9876500006',
    'company_name' => 'Media & Press Agency',
    'subject' => 'Partnership Inquiry from Contact Us',
    'message' => 'We would like to feature Sports Junction manufacturers in our upcoming trade journal.',
    'enquiry_source' => 'CONTACT_US',
    'country_name' => 'India'
]);
assertTest("Contact Us Inquiry created", $inq6Id > 0);

// 7. Submit Support Enquiry
$inq7Id = \App\Models\ProductInquiry::create([
    'buyer_name' => 'QA User Support',
    'buyer_email' => 'qa.user.support@example.com',
    'buyer_phone' => '+91 9876500007',
    'company_name' => 'Retail Buyer',
    'subject' => 'Support Request: Need assistance with container tracking',
    'message' => 'My recent shipment container number is not showing active updates.',
    'enquiry_source' => 'SUPPORT',
    'country_name' => 'India'
]);
assertTest("Support Inquiry created", $inq7Id > 0);

// 8. Submit Bulk Product Enquiry
$bulkRes = \App\Models\BulkProductEnquiry::createBulkEnquiry([
    'buyer_user_id' => 1,
    'buyer_name' => 'QA Buyer Bulk Multi-Product',
    'buyer_email' => 'qa.bulk.buyer@example.com',
    'buyer_phone' => '+91 9876500008',
    'company_name' => 'National Sports Distributor',
    'delivery_location' => 'Delhi, India',
    'overall_message' => 'Bulk sourcing order for annual school games championship.',
    'expected_timeline' => '30 Days'
], [
    [
        'product_id' => $prodId,
        'supplier_id' => $suppId,
        'quantity' => 300,
        'specifications' => 'Item 1: Size 5 match balls'
    ]
]);
$bulkEnqId = (int)($bulkRes['id'] ?? $bulkRes['bulk_enquiry_id'] ?? 0);
assertTest("Bulk Multi-Product Enquiry created in bulk_product_enquiries", $bulkEnqId > 0);

// 9. Submit Suggestions & Feedback
$feedbackId = \App\Models\SiteFeedback::create([
    'name' => 'QA User Feedback',
    'company_name' => 'Feedback Org',
    'email' => 'qa.feedback.user@example.com',
    'country_id' => 1,
    'country_name' => 'India',
    'feedback_type' => 'Website Experience',
    'subject' => 'Product Filter Usability Suggestion',
    'message' => 'Adding quick MOQ pills would make sourcing faster.',
    'consent_contact' => 1
]);
assertTest("Suggestions & Feedback created in site_feedbacks", $feedbackId > 0);

echo "\n--- VERIFYING STRICT ROUTING & QUERY ISOLATION ---\n";

// Verify Product Page Enquiries query does NOT return RFQ, FAST_OEM, SAMPLE, CONTACT_US, SUPPORT
$prodEnqs = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'PRODUCT_PAGE', 'length' => 100]);
$prodEnqIds = array_column($prodEnqs['data'] ?? [], 'id');
assertTest("Product Page Enquiries includes inq1", in_array($inq1Id, $prodEnqIds), "inq1 missing from Product Page Enquiries");
assertTest("Product Page Enquiries isolates from RFQ (inq2)", !in_array($inq2Id, $prodEnqIds), "inq2 leaked into Product Page Enquiries");
assertTest("Product Page Enquiries isolates from Fast OEM (inq3)", !in_array($inq3Id, $prodEnqIds), "inq3 leaked into Product Page Enquiries");
assertTest("Product Page Enquiries isolates from Prototype Sample (inq4)", !in_array($inq4Id, $prodEnqIds), "inq4 leaked into Product Page Enquiries");
assertTest("Product Page Enquiries isolates from Contact Us (inq6)", !in_array($inq6Id, $prodEnqIds), "inq6 leaked into Product Page Enquiries");
assertTest("Product Page Enquiries isolates from Support (inq7)", !in_array($inq7Id, $prodEnqIds), "inq7 leaked into Product Page Enquiries");

// Verify RFQ Enquiries query
$rfqEnqs = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'RFQ', 'length' => 100]);
$rfqEnqIds = array_column($rfqEnqs['data'] ?? [], 'id');
assertTest("RFQ Enquiries includes inq2", in_array($inq2Id, $rfqEnqIds), "inq2 missing from RFQ Enquiries");
assertTest("RFQ Enquiries isolates from Product Page (inq1)", !in_array($inq1Id, $rfqEnqIds), "inq1 leaked into RFQ Enquiries");

// Verify Fast OEM Enquiries query
$oemEnqs = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'FAST_OEM', 'length' => 100]);
$oemEnqIds = array_column($oemEnqs['data'] ?? [], 'id');
assertTest("Fast OEM Enquiries includes inq3", in_array($inq3Id, $oemEnqIds), "inq3 missing from Fast OEM Enquiries");

// Verify Prototype Sample Enquiries query
$sampleEnqs = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'SAMPLE', 'length' => 100]);
$sampleEnqIds = array_column($sampleEnqs['data'] ?? [], 'id');
assertTest("Prototype Sample Enquiries includes inq4", in_array($inq4Id, $sampleEnqIds), "inq4 missing from Sample Enquiries");

// Verify Contact Us Enquiries query
$contactEnqs = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'CONTACT_US', 'length' => 100]);
$contactEnqIds = array_column($contactEnqs['data'] ?? [], 'id');
assertTest("Contact Us Enquiries includes inq6", in_array($inq6Id, $contactEnqIds), "inq6 missing from Contact Us Enquiries");

// Verify Support Enquiries query
$supportEnqs = \App\Models\ProductInquiry::getAdminDataTable(['source' => 'SUPPORT', 'length' => 100]);
$supportEnqIds = array_column($supportEnqs['data'] ?? [], 'id');
assertTest("Support Enquiries includes inq7", in_array($inq7Id, $supportEnqIds), "inq7 missing from Support Enquiries");

// Verify Bulk Product Enquiry exists in its own table
$bulkCheck = \App\Models\BulkProductEnquiry::findById($bulkEnqId);
assertTest("Bulk Enquiry retrieved with distinct items", $bulkCheck !== null && !empty($bulkCheck['items']), "Bulk enquiry missing or no items");

// Verify Site Feedback exists with reference ID
$fbCheck = \App\Models\SiteFeedback::findById($feedbackId);
assertTest("Site Feedback has reference ID starting with SFB-", str_starts_with($fbCheck['reference_id'] ?? '', 'SFB-'), "Feedback reference ID format wrong");

// Cleanup test inquiries
$pdo->query("DELETE FROM product_inquiries WHERE id IN ($inq1Id, $inq2Id, $inq3Id, $inq4Id, $inq5Id, $inq6Id, $inq7Id)");
$pdo->query("DELETE FROM bulk_product_enquiry_items WHERE bulk_enquiry_id = $bulkEnqId");
$pdo->query("DELETE FROM bulk_product_enquiries WHERE id = $bulkEnqId");
$pdo->query("DELETE FROM site_feedbacks WHERE id = $feedbackId");

echo "\nSuite 3 Results: $passedTests PASSED, $failedTests FAILED.\n";
if (!empty($bugs)) {
    echo "Bugs logged:\n";
    print_r($bugs);
}
