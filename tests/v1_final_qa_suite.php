<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — V1 FINAL HANDOVER MASTER QA TEST SUITE
 * Executes all 6 domain verification suites + V1 Final Handover Checks
 * =======================================================================
 */

$suites = [
    '1. Master CRUD, Security & Formula-Safe Export Suite' => __DIR__ . '/run_tests.php',
    '2. Marketplace Catalog, SEO & Dynamic Storefront Suite' => __DIR__ . '/marketplace_test.php',
    '3. Validation, Image Uploader & 422 Error Suite' => __DIR__ . '/validation_and_upload_test.php',
    '4. Authentication, Static OTP (123456) & Approval Suite' => __DIR__ . '/auth_and_approval_workflow_test.php',
    '5. Buyer Portal, Seller Portal & Product Revision Suite' => __DIR__ . '/buyer_seller_admin_test.php',
    '6. Super Admin Buyer Management & Centralized Enquiry Suite' => __DIR__ . '/buyer_enquiry_management_test.php',
    '7. Real-Time Chat, Negotiation & Deal Management Suite' => __DIR__ . '/chat_negotiation_module_test.php',
];

echo "=======================================================================\n";
echo " SPORTS JUNCTION B2B MARKETPLACE — V1 FINAL QA VERIFICATION RUNNER\n";
echo " Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "=======================================================================\n\n";

$suitePassed = 0;
$suiteFailed = 0;

foreach ($suites as $title => $file) {
    echo ">>> RUNNING SUITE: {$title}\n";
    $cmd = 'php ' . escapeshellarg($file) . ' 2>&1';
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    foreach ($output as $line) {
        if (str_contains($line, 'Module "intl" is already loaded')) {
            continue;
        }
        echo "    " . $line . "\n";
    }

    if ($exitCode === 0) {
        $suitePassed++;
        echo "<<< RESULT: PASSED\n\n";
    } else {
        $suiteFailed++;
        echo "<<< RESULT: FAILED (Exit Code {$exitCode})\n\n";
    }
}

// Additional V1 Handover Structural & Route Checks
echo ">>> RUNNING SUITE: 7. V1 Final Handover Structural & Route Integrity Checks\n";
$rootDir = dirname(__DIR__);
$requiredFiles = [
    'public/index.php',
    'public/assets/images/logo.jpg',
    'app/Controllers/AuthController.php',
    'app/Controllers/BuyerPortalController.php',
    'app/Controllers/SupplierPortalController.php',
    'app/Controllers/SupplierApprovalController.php',
    'app/Controllers/ProductApprovalController.php',
    'app/Controllers/BuyerManagementController.php',
    'app/Controllers/EnquiryManagementController.php',
    'app/Controllers/ProductController.php',
    'app/Controllers/ShopController.php',
    'app/Views/admin/buyers.php',
    'app/Views/admin/enquiries.php',
    'app/Views/admin/enquiry_detail.php',
    'app/Views/frontend/contact.php',
    'app/Views/supplier/product_form.php',
    'app/Views/products/form.php',
    'app/Controllers/ChatApiController.php',
    'app/Controllers/BuyerMessagesController.php',
    'app/Controllers/SupplierMessagesController.php',
    'app/Controllers/AdminCommunicationController.php',
    'app/Views/buyer/messages.php',
    'app/Views/supplier/messages.php',
    'app/Views/admin/communication.php',
    'app/Views/admin/communication_detail.php',
    'app/Views/components/chat_popup.php',
];

$structuralPass = true;
foreach ($requiredFiles as $relPath) {
    $exists = file_exists($rootDir . '/' . $relPath);
    if ($exists) {
        echo "    [PASS] File verified: {$relPath}\n";
    } else {
        echo "    [FAIL] Missing required file: {$relPath}\n";
        $structuralPass = false;
    }
}

// Verify Supplier Product Form does NOT contain Featured toggle while Admin Product Form DOES
$supplierFormContent = (string) file_get_contents($rootDir . '/app/Views/supplier/product_form.php');
$adminFormContent    = (string) file_get_contents($rootDir . '/app/Views/products/form.php');

$featuredRestrictedOk = !str_contains($supplierFormContent, 'name="is_featured"') && str_contains($adminFormContent, 'name="is_featured"');
if ($featuredRestrictedOk) {
    echo "    [PASS] 'Highlight as Featured Product' restricted strictly to Super Admin (hidden from Supplier)\n";
} else {
    echo "    [FAIL] Featured product visibility check failed\n";
    $structuralPass = false;
}

if ($structuralPass) {
    $suitePassed++;
    echo "<<< RESULT: PASSED\n\n";
} else {
    $suiteFailed++;
    echo "<<< RESULT: FAILED\n\n";
}

echo "=======================================================================\n";
echo " V1 FINAL QA SUMMARY: {$suitePassed} / " . ($suitePassed + $suiteFailed) . " TEST SUITES PASSED\n";
echo "=======================================================================\n";

exit($suiteFailed === 0 ? 0 : 1);
