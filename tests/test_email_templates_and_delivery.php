<?php
declare(strict_types=1);

/**
 * Sports Junction — Comprehensive Transactional Email & SMTP Integration Test
 * Verifies all 27 templates (A through AA), live Google SMTP delivery, and Dynamic OTP verification.
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Helpers\Mailer;
use App\Models\Otp;
use App\Models\SiteSetting;

$tracker = [
    'TEST_1_SMTP_CONNECTION'    => 'NOT_STARTED',
    'TEST_2_DYNAMIC_OTP_ACTIVE' => 'NOT_STARTED',
    'TEST_3_INVENTORY_COUNT'    => 'NOT_STARTED',
    'TEST_4_ALL_27_RENDERS'     => 'NOT_STARTED',
    'TEST_5_LIVE_SMTP_DELIVERY' => 'NOT_STARTED',
    'TEST_6_DYNAMIC_OTP_FLOW'   => 'NOT_STARTED',
    'TEST_7_TRIGGER_INTEGRATION'=> 'NOT_STARTED',
];

echo "========================================================================\n";
echo "SPORTS JUNCTION — TRANSACTIONAL EMAIL & SMTP DELIVERY TEST SUITE\n";
echo "========================================================================\n\n";

// -----------------------------------------------------------------------------
// TEST 1: SMTP Connection & Authentication
// -----------------------------------------------------------------------------
echo "--> Test 1: Testing SMTP Connection & Authentication Credentials...\n";
$conn = Mailer::testConnection();
echo "    Status:  " . $conn['status'] . "\n";
echo "    Host:    " . ($conn['host'] ?? 'N/A') . ":" . ($conn['port'] ?? 'N/A') . "\n";
echo "    User:    " . ($conn['user'] ?? 'N/A') . "\n";
echo "    Message: " . ($conn['message'] ?? 'N/A') . "\n";

if ($conn['status'] === 'AUTHENTICATED_AND_READY') {
    $tracker['TEST_1_SMTP_CONNECTION'] = 'PASSED';
    echo "    [PASSED] Google SMTP connection authenticated successfully.\n\n";
} else {
    $tracker['TEST_1_SMTP_CONNECTION'] = 'FAILED';
    echo "    [FAILED] SMTP connection failed: " . ($conn['message'] ?? 'Unknown error') . "\n\n";
}

// -----------------------------------------------------------------------------
// TEST 2: Dynamic Cryptographic OTP Mode Check
// -----------------------------------------------------------------------------
echo "--> Test 2: Checking Dynamic OTP Mode (USE_STATIC_OTP=false)...\n";
$isStatic = Otp::isStaticOtpEnabled();
echo "    Otp::isStaticOtpEnabled(): " . ($isStatic ? 'true (STATIC)' : 'false (DYNAMIC)') . "\n";

if (!$isStatic) {
    $tracker['TEST_2_DYNAMIC_OTP_ACTIVE'] = 'PASSED';
    echo "    [PASSED] Dynamic OTP mode is active and static mode is disabled.\n\n";
} else {
    $tracker['TEST_2_DYNAMIC_OTP_ACTIVE'] = 'FAILED';
    echo "    [FAILED] Expected dynamic OTP mode, but static OTP is still active.\n\n";
}

// -----------------------------------------------------------------------------
// TEST 3: Template Inventory Registry (27 Templates: A to AA)
// -----------------------------------------------------------------------------
echo "--> Test 3: Verifying Template Inventory Registry...\n";
$inventory = Mailer::getTemplateInventory();
$count = count($inventory);
echo "    Total Registered Templates: {$count}\n";

$expectedCodes = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA'];
$foundCodes = array_map(fn($t) => $t['code'], $inventory);
$missingCodes = array_diff($expectedCodes, $foundCodes);

if ($count === 27 && empty($missingCodes)) {
    $tracker['TEST_3_INVENTORY_COUNT'] = 'PASSED';
    echo "    [PASSED] Exactly 27 unified templates (A through AA) are registered.\n\n";
} else {
    $tracker['TEST_3_INVENTORY_COUNT'] = 'FAILED';
    echo "    [FAILED] Missing codes: " . implode(', ', $missingCodes) . "\n\n";
}

// -----------------------------------------------------------------------------
// TEST 4: Render All 27 Templates (HTML + Plain-Text Integrity)
// -----------------------------------------------------------------------------
echo "--> Test 4: Rendering All 27 Templates with Sample Datasets...\n";
putenv('MAIL_DRIVER=log');
$_ENV['MAIL_DRIVER'] = 'log';

$renderFailures = [];
foreach ($inventory as $key => $item) {
    try {
        $result = ($item['send_callable'])('test@sportsjunction.in', $item['sample_data']);
        if (!$result) {
            $renderFailures[] = "Template {$item['code']} ({$item['name']}): Dispatch returned false";
        } else {
            echo "    [OK] Template {$item['code']}: {$item['name']}\n";
        }
    } catch (\Throwable $e) {
        $renderFailures[] = "Template {$item['code']} threw: " . $e->getMessage();
    }
}

if (empty($renderFailures)) {
    $tracker['TEST_4_ALL_27_RENDERS'] = 'PASSED';
    echo "    [PASSED] All 27 templates rendered cleanly without errors.\n\n";
} else {
    $tracker['TEST_4_ALL_27_RENDERS'] = 'FAILED';
    echo "    [FAILED] Failures: " . implode('; ', $renderFailures) . "\n\n";
}

// -----------------------------------------------------------------------------
// TEST 5: Live SMTP Delivery via Google SMTP
// -----------------------------------------------------------------------------
echo "--> Test 5: Testing Live SMTP Delivery via Google SMTP...\n";
putenv('MAIL_DRIVER=smtp');
$_ENV['MAIL_DRIVER'] = 'smtp';

$testRecipient = 'sportjunctions@gmail.com';
echo "    Sending Live Test Email (Template A) to {$testRecipient}...\n";

$liveTestResult = Mailer::sendBuyerSignupOtp($testRecipient, 'Super Admin Test', '582914');
if ($liveTestResult) {
    $tracker['TEST_5_LIVE_SMTP_DELIVERY'] = 'PASSED';
    echo "    [PASSED] Live email successfully transmitted to {$testRecipient} via Google SMTP!\n\n";
} else {
    $tracker['TEST_5_LIVE_SMTP_DELIVERY'] = 'FAILED';
    echo "    [FAILED] Live email delivery failed. Check storage/logs/mail.log.\n\n";
}

// -----------------------------------------------------------------------------
// TEST 6: Dynamic OTP End-to-End Lifecycle
// -----------------------------------------------------------------------------
echo "--> Test 6: Testing Dynamic OTP Generation, Verification & Resend...\n";
$testEmail = 'otp_live_test_' . time() . '@sportsjunction.in';
$purpose = 'BUYER_REGISTRATION';

// 1. Generate dynamic OTP
$otp1 = Otp::generate($testEmail, $purpose, '127.0.0.1');
echo "    Generated Dynamic OTP: {$otp1}\n";

if (strlen($otp1) !== 6 || !is_numeric($otp1)) {
    echo "    [ERROR] OTP is not a 6-digit numeric string.\n";
}

// 2. Reject incorrect passcode
$badVerify = Otp::verify($testEmail, '000000', $purpose);
if ($badVerify['success']) {
    echo "    [ERROR] Incorrect passcode '000000' was erroneously accepted!\n";
} else {
    echo "    [OK] Incorrect passcode correctly rejected: " . $badVerify['message'] . "\n";
}

// 3. Reject static code 123456 when dynamic mode is active
$staticVerify = Otp::verify($testEmail, '123456', $purpose);
if ($otp1 !== '123456' && $staticVerify['success']) {
    echo "    [ERROR] Static passcode '123456' was accepted in dynamic mode!\n";
} else {
    echo "    [OK] Static passcode '123456' rejected in dynamic mode.\n";
}

// 4. Verify legitimate dynamic code
$goodVerify = Otp::verify($testEmail, $otp1, $purpose);
if ($goodVerify['success']) {
    echo "    [OK] Real dynamic passcode {$otp1} verified successfully!\n";
} else {
    echo "    [ERROR] Real dynamic passcode failed to verify: " . $goodVerify['message'] . "\n";
}

// 5. Code cannot be reused (consumed)
$reuseVerify = Otp::verify($testEmail, $otp1, $purpose);
if ($reuseVerify['success']) {
    echo "    [ERROR] Consumed passcode was reused!\n";
} else {
    echo "    [OK] Consumed passcode cannot be reused.\n";
}

if ($goodVerify['success'] && !$badVerify['success'] && !$reuseVerify['success']) {
    $tracker['TEST_6_DYNAMIC_OTP_FLOW'] = 'PASSED';
    echo "    [PASSED] Dynamic OTP generation and verification flow works perfectly.\n\n";
} else {
    $tracker['TEST_6_DYNAMIC_OTP_FLOW'] = 'FAILED';
    echo "    [FAILED] Dynamic OTP flow test failed.\n\n";
}

// -----------------------------------------------------------------------------
// TEST 7: Event Trigger & Controller Hooking Check
// -----------------------------------------------------------------------------
echo "--> Test 7: Checking Event Trigger Wiring Across Controllers...\n";
$triggers = [
    'AuthController (Resend OTP Template C)'              => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/AuthController.php'), 'Mailer::sendOtpResend'),
    'AuthController (Forgot Password Template J/K)'       => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/AuthController.php'), 'Mailer::sendSupplierPasswordResetOtp'),
    'AuthController (Password Reset Updated Template L/M)'=> str_contains(file_get_contents(__DIR__ . '/../app/Controllers/AuthController.php'), 'Mailer::sendSupplierPasswordUpdated'),
    'BuyerPortalController (Password Updated Template L)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/BuyerPortalController.php'), 'Mailer::sendBuyerPasswordUpdated'),
    'SupplierPortalController (Password Updated Template M)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/SupplierPortalController.php'), 'Mailer::sendSupplierPasswordUpdated'),
    'SupplierPortalController (Profile Under Review Template G)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/SupplierPortalController.php'), 'Mailer::sendSupplierProfileUpdateUnderReview'),
    'SupplierPortalController (Product Update Template Y)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/SupplierPortalController.php'), 'Mailer::sendProductUpdateUnderReview'),
    'AdminProfileChangeController (Profile Update Approved Template H)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/AdminProfileChangeController.php'), 'Mailer::sendSupplierProfileUpdateApproved'),
    'AdminProfileChangeController (Profile Update Rejected Template I)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/AdminProfileChangeController.php'), 'Mailer::sendSupplierProfileUpdateRejected'),
    'ProductApprovalController (Product Creation Approved Template W)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/ProductApprovalController.php'), 'Mailer::sendProductCreationApproved'),
    'ProductApprovalController (Product Update Approved Template Z)' => str_contains(file_get_contents(__DIR__ . '/../app/Controllers/ProductApprovalController.php'), 'Mailer::sendProductUpdateApproved'),
    'ChatMessage (1st Buyer Message Template V)' => str_contains(file_get_contents(__DIR__ . '/../app/Models/ChatMessage.php'), 'Mailer::sendBuyerChatNotificationToSupplier'),
];

$allTriggersFound = true;
foreach ($triggers as $name => $found) {
    if ($found) {
        echo "    [OK] {$name}\n";
    } else {
        echo "    [FAIL] {$name} is missing!\n";
        $allTriggersFound = false;
    }
}

if ($allTriggersFound) {
    $tracker['TEST_7_TRIGGER_INTEGRATION'] = 'PASSED';
    echo "    [PASSED] All event triggers are properly hooked across controllers.\n\n";
} else {
    $tracker['TEST_7_TRIGGER_INTEGRATION'] = 'FAILED';
    echo "    [FAILED] One or more event triggers are missing.\n\n";
}

// -----------------------------------------------------------------------------
// SUMMARY REPORT
// -----------------------------------------------------------------------------
echo "========================================================================\n";
echo "TEST RESULTS TRACKER\n";
echo "========================================================================\n";
$allPassed = true;
foreach ($tracker as $testName => $status) {
    $badge = ($status === 'PASSED') ? '[PASSED]' : '[FAILED]';
    printf("  %-30s : %s\n", $testName, $badge);
    if ($status !== 'PASSED') {
        $allPassed = false;
    }
}
echo "========================================================================\n";

if ($allPassed) {
    echo "OVERALL VERDICT: ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "OVERALL VERDICT: ONE OR MORE TESTS FAILED!\n";
    exit(1);
}
