<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';

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

\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

use App\Core\Database;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Models\Supplier;
use App\Models\User;

$db = Database::getConnection();
$passed = 0;
$failed = 0;

function assertCheck(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] {$label}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$label}\n";
        $failed++;
    }
}

echo "=== SPORTS JUNCTION BUYER & CENTRALIZED ENQUIRY MANAGEMENT VERIFICATION ===\n\n";

// 1. Setup test buyer & suppliers
$uniqueEmail = 'testbuyer_' . time() . '@sportsjunction.test';
$buyerId = User::create([
    'name'              => 'Rohan Verma Procurement',
    'company_name'      => 'Apex Sports Academy Ltd',
    'email'             => $uniqueEmail,
    'password'          => 'TestPass@123',
    'role'              => 'BUYER',
    'status'            => 'ACTIVE',
    'phone'             => '9876543210',
    'country_code'      => '+91',
    'country_id'        => 1,
    'email_verified_at' => date('Y-m-d H:i:s'),
    'terms_accepted'    => true,
]);

assertCheck($buyerId > 0, "1. Created test buyer account (#{$buyerId})");

// Test Buyer Management DataTable search & details
$dtBuyers = User::getBuyersDataTable([
    'draw'   => 1,
    'start'  => 0,
    'length' => 10,
    'search' => ['value' => 'Apex Sports Academy'],
]);
assertCheck(($dtBuyers['recordsFiltered'] ?? 0) >= 1, "2. Super Admin Buyer DataTable searches across company_name");

$buyerDetails = User::getBuyerDetails($buyerId);
assertCheck($buyerDetails !== null && $buyerDetails['email'] === $uniqueEmail, "3. Super Admin can view complete buyer profile");

// Test Edit Buyer with email change resetting verification
$updatedEmail = 'rohan.updated_' . time() . '@sportsjunction.test';
$updateRes = User::updateBuyerByAdmin($buyerId, [
    'name'         => 'Rohan Verma Updated',
    'company_name' => 'Apex Global Sports',
    'email'        => $updatedEmail,
    'phone'        => '9811122233',
    'country_code' => '+91',
    'country_id'   => 1,
    'status'       => 'ACTIVE',
]);
$reloadedBuyer = User::getBuyerDetails($buyerId);
assertCheck(
    $updateRes['success'] === true
    && $reloadedBuyer['status'] === 'PENDING_VERIFICATION'
    && $reloadedBuyer['email_verified_at'] === null,
    "4. Editing buyer email resets email verification status to PENDING_VERIFICATION"
);

// Test Deactivating and Reactivating Buyer
User::setBuyerStatus($buyerId, 'INACTIVE');
$deactivatedBuyer = User::getBuyerDetails($buyerId);
assertCheck($deactivatedBuyer['status'] === 'INACTIVE', "5. Super Admin can deactivate buyer account without deleting history");

User::setBuyerStatus($buyerId, 'ACTIVE');
User::markEmailVerified($buyerId, 'BUYER');

// Fetch two suppliers and one product for testing enquiries
$suppliers = Supplier::allActive();
$supplier1Id = (int) ($suppliers[0]['id'] ?? 1);
$supplier2Id = (int) ($suppliers[1]['id'] ?? ($supplier1Id + 1));
$sampleProd = $db->query("SELECT id, supplier_id, title FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$productId = (int) ($sampleProd['id'] ?? 1);
$prodSupplierId = (int) ($sampleProd['supplier_id'] ?? $supplier1Id);

// Create 3 Enquiries from all 3 sources
$enqProductPageId = ProductInquiry::create([
    'enquiry_source'    => 'PRODUCT_PAGE',
    'product_id'        => $productId,
    'supplier_id'       => $prodSupplierId,
    'buyer_user_id'     => $buyerId,
    'buyer_name'        => 'Rohan Verma Updated',
    'buyer_email'       => $updatedEmail,
    'buyer_phone'       => '+91 9811122233',
    'company_name'      => 'Apex Global Sports',
    'subject'           => 'English Willow Bat Bulk Order',
    'target_quantity'   => 250,
    'target_price'      => 4500.00,
    'delivery_location' => 'Mumbai Port, Maharashtra',
    'specifications'    => 'Need custom laser engraving on handle and padded covers.',
]);

$enqGeneralId = ProductInquiry::create([
    'enquiry_source'    => 'GENERAL',
    'product_id'        => null,
    'supplier_id'       => null,
    'buyer_user_id'     => $buyerId,
    'buyer_name'        => 'Rohan Verma Updated',
    'buyer_email'       => $updatedEmail,
    'buyer_phone'       => '+91 9811122233',
    'company_name'      => 'Apex Global Sports',
    'subject'           => 'Complete Synthetic Turf & Agility Kit RFQ',
    'target_quantity'   => 500,
    'delivery_location' => 'Bengaluru, Karnataka',
    'specifications'    => 'Looking for OEM supplier for agility cones, hurdles, and speed parachutes.',
]);

$enqContactUsGuestId = ProductInquiry::create([
    'enquiry_source'    => 'CONTACT_US',
    'product_id'        => null,
    'supplier_id'       => null,
    'buyer_user_id'     => null, // Submitted publicly without login, even with same email
    'buyer_name'        => 'Public Visitor',
    'buyer_email'       => $updatedEmail,
    'buyer_phone'       => '+91 9999988888',
    'company_name'      => 'Visitor Club',
    'subject'           => 'Partnership Question via Contact Us',
    'target_quantity'   => 1,
    'delivery_location' => 'Hyderabad',
    'specifications'    => 'How do we onboard as an institutional distributor?',
]);

assertCheck(
    $enqProductPageId > 0 && $enqGeneralId > 0 && $enqContactUsGuestId > 0,
    "6. All 3 enquiry sources (PRODUCT_PAGE, GENERAL, CONTACT_US) saved in unified product_inquiries table"
);

// Verify Super Admin DataTable shows all 3 sources and filters by supplier and global search
$dtSourceContact = ProductInquiry::getAdminDataTable([
    'enquiry_source' => 'CONTACT_US',
    'search'         => ['value' => 'Partnership Question via Contact Us'],
]);
assertCheck(($dtSourceContact['recordsFiltered'] ?? 0) >= 1, "7. Super Admin can filter by CONTACT_US source and search globally");

$dtSupplierFilter = ProductInquiry::getAdminDataTable([
    'supplier_id' => $prodSupplierId,
]);
$foundProdEnq = false;
foreach ($dtSupplierFilter['data'] as $row) {
    if ((int)$row['id'] === $enqProductPageId) {
        $foundProdEnq = true;
        break;
    }
}
assertCheck($foundProdEnq, "8. Super Admin supplier filter shows enquiries related to that supplier");

// Verify Super Admin can assign supplier to General Enquiry and add private Admin Notes
ProductInquiry::assignSupplier($enqGeneralId, $supplier1Id);
ProductInquiry::updateAdminNotes($enqGeneralId, 'CONFIDENTIAL_SUPER_ADMIN_NOTE_XYZ');
ProductInquiry::updateStatus($enqGeneralId, 'IN_DISCUSSION');

$adminViewEnq = ProductInquiry::findById($enqGeneralId, true);
$publicViewEnq = ProductInquiry::findById($enqGeneralId, false);
$exportRows = ProductInquiry::getAdminExportData(['search_query' => 'Complete Synthetic Turf']);

assertCheck(
    ($adminViewEnq['admin_notes'] ?? '') === 'CONFIDENTIAL_SUPER_ADMIN_NOTE_XYZ'
    && !array_key_exists('admin_notes', $publicViewEnq)
    && !isset($exportRows[0]['admin_notes']),
    "9. Internal Super Admin notes are saved for Super Admin and strictly hidden from Buyer/Supplier views and Exports"
);

// Verify Buyer My Enquiries: sees own Product & General enquiries, does NOT see guest Contact Us message
$buyerEnquiries = ProductInquiry::getBuyerEnquiries($buyerId, $updatedEmail);
$buyerEnqIds = array_map(fn($r) => (int)$r['id'], $buyerEnquiries);
assertCheck(
    in_array($enqProductPageId, $buyerEnqIds, true)
    && in_array($enqGeneralId, $buyerEnqIds, true)
    && !in_array($enqContactUsGuestId, $buyerEnqIds, true),
    "10. Buyer sees their own Product & General enquiries, while unauthenticated Contact Us messages remain private"
);

// Verify Supplier Customer Enquiries: Supplier 1 sees assigned enquiry, does NOT see unassigned Contact Us enquiry
$sup1Enquiries = ProductInquiry::getSupplierEnquiries($supplier1Id);
$sup1Ids = array_map(fn($r) => (int)$r['id'], $sup1Enquiries);
assertCheck(
    in_array($enqGeneralId, $sup1Ids, true) && !in_array($enqContactUsGuestId, $sup1Ids, true),
    "11. Supplier sees enquiries assigned to them or their products, and never sees unassigned/other suppliers' enquiries"
);

// Cleanup test records
$db->prepare("DELETE FROM product_inquiries WHERE id IN (?, ?, ?)")->execute([$enqProductPageId, $enqGeneralId, $enqContactUsGuestId]);
$db->prepare("DELETE FROM users WHERE id = ?")->execute([$buyerId]);

echo "\n=================================================================\n";
echo "Total Passed: {$passed} | Total Failed: {$failed}\n";
if ($failed > 0) {
    exit(1);
}
exit(0);
