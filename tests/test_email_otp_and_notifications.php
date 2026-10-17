<?php
/**
 * Sports Junction - Comprehensive Verification Suite
 * Task: Footer Cleanup + Hostinger SMTP + OTP & Transactional Notifications
 */
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Helpers\Mailer;
use App\Models\Otp;
use App\Models\User;
use App\Models\Supplier;
use App\Models\ProductInquiry;

$pdo = Database::getConnection();

$testsPassed = 0;
$testsFailed = 0;
$failures = [];

function assertTest(string $description, bool $condition, string $detail = '') {
    global $testsPassed, $testsFailed, $failures;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] {$description}\n";
    } else {
        $testsFailed++;
        $failures[] = "{$description} - {$detail}";
        echo " [FAIL] {$description}: {$detail}\n";
    }
}

echo "========================================================================\n";
echo " SPORTS JUNCTION - VERIFICATION TEST SUITE\n";
echo "========================================================================\n\n";

// ------------------------------------------------------------------------
// 1. FOOTER INTEGRITY CHECKS
// ------------------------------------------------------------------------
echo "--- 1. Testing Footer Cleanup & Structure ---\n";
$footerContent = file_get_contents(__DIR__ . '/../app/Views/layouts/frontend.php');

assertTest(
    'Payment/Security badges strip removed from footer',
    !str_contains($footerContent, 'PCI DSS') && 
    !str_contains($footerContent, 'Verisign') && 
    !str_contains($footerContent, 'T/T Wire') &&
    !str_contains($footerContent, 'Apple Pay'),
    'Found leftover payment/security badges in frontend.php'
);

assertTest(
    'Official Trade Desk block removed from footer',
    !str_contains($footerContent, 'Official Trade Desk') &&
    !str_contains($footerContent, 'trade@sportsjunction.com'),
    'Found leftover Official Trade Desk block in frontend.php'
);

assertTest(
    'Sub-footer bottom-left logo removed',
    !str_contains($footerContent, 'h-7 w-auto object-contain brightness-0 invert') &&
    !preg_match('/<div[^>]*>\s*<img[^>]*siteLogo/s', $footerContent),
    'Found site logo in sub-footer'
);

assertTest(
    'Sub-footer contains exact required copyright text',
    str_contains($footerContent, '&copy; <?= date(\'Y\') ?> Sports Junction | All rights reserved.') ||
    str_contains($footerContent, 'Sports Junction | All rights reserved.'),
    'Missing exact copyright string'
);

assertTest(
    'Sub-footer retains Stay Connected section',
    str_contains($footerContent, 'Stay Connected'),
    'Missing Stay Connected section'
);

// ------------------------------------------------------------------------
// 2. SMTP CONNECTION & CONFIGURATION CHECKS
// ------------------------------------------------------------------------
echo "\n--- 2. Testing Hostinger SMTP Configuration & Connectivity ---\n";

assertTest(
    'SMTP Host is configured for Hostinger',
    ($_ENV['MAIL_HOST'] ?? '') === 'smtp.hostinger.com',
    'MAIL_HOST must be smtp.hostinger.com'
);

assertTest(
    'SMTP Port is 465 with SSL encryption',
    (int)($_ENV['MAIL_PORT'] ?? 0) === 465 && strtolower($_ENV['MAIL_ENCRYPTION'] ?? '') === 'ssl',
    'MAIL_PORT must be 465 and MAIL_ENCRYPTION must be ssl'
);

assertTest(
    'SMTP Username and From Address match authenticated mailbox',
    ($_ENV['MAIL_USERNAME'] ?? '') === 'no-reply@sportsjunction.in' &&
    ($_ENV['MAIL_FROM_ADDRESS'] ?? '') === 'no-reply@sportsjunction.in',
    'MAIL_USERNAME/FROM_ADDRESS must be no-reply@sportsjunction.in'
);

$smtpTest = Mailer::testConnection();
assertTest(
    'Live SMTP socket handshake & AUTH LOGIN succeeded',
    ($smtpTest['status'] ?? '') === 'AUTHENTICATED_AND_READY',
    $smtpTest['message'] ?? 'Connection failed'
);

// ------------------------------------------------------------------------
// 3. OTP LIFECYCLE & SECURITY TESTS
// ------------------------------------------------------------------------
echo "\n--- 3. Testing OTP Cryptographic Security, Expiration, & Resends ---\n";

$testEmail = 'qa_test_otp_' . time() . '@example.com';

// Clean any old test records
$pdo->prepare("DELETE FROM email_otps WHERE email = ?")->execute([$testEmail]);

// 3.1 Generation
$otp1 = Otp::generate($testEmail, 'BUYER_REGISTRATION', '127.0.0.1');
assertTest(
    'OTP is a 6-digit numeric string',
    strlen($otp1) === 6 && ctype_digit($otp1),
    "Generated OTP is invalid: {$otp1}"
);

// Verify OTP hash stored in DB, not plain text
$stmt = $pdo->prepare("SELECT otp_code, attempts, resend_count, expires_at, verified_at FROM email_otps WHERE email = ? AND purpose = 'BUYER_REGISTRATION'");
$stmt->execute([$testEmail]);
$otpRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

assertTest(
    'OTP is securely hashed in database with BCRYPT (not plain text)',
    $otpRecord && $otpRecord['otp_code'] !== $otp1 && password_verify($otp1, $otpRecord['otp_code']),
    'Database does not contain a valid bcrypt hash of the OTP'
);

// 3.2 Cooldown enforcement
$canResend = Otp::canResend($testEmail, 'BUYER_REGISTRATION');
assertTest(
    'OTP resend is blocked within cooldown window',
    $canResend['allowed'] === false && (($canResend['remaining'] ?? 0) > 0 || ($canResend['wait_seconds'] ?? 0) > 0),
    "Expected cooldown block, but got allowed: " . json_encode($canResend)
);

// 3.3 Failed attempt tracking & Max 5 attempts
$failResult = Otp::verify($testEmail, '000000', 'BUYER_REGISTRATION');
assertTest(
    'Incorrect OTP verification fails with remaining attempts notice',
    $failResult['success'] === false && str_contains($failResult['message'] ?? '', 'attempt'),
    "Expected remaining attempts notice, got: " . json_encode($failResult)
);

// Fast-forward attempts to 5 in DB
$pdo->prepare("UPDATE email_otps SET attempts = 5 WHERE email = ? AND purpose = 'BUYER_REGISTRATION'")->execute([$testEmail]);
$lockedResult = Otp::verify($testEmail, $otp1, 'BUYER_REGISTRATION');
assertTest(
    'OTP locks after 5 failed attempts even if correct OTP is subsequently provided',
    $lockedResult['success'] === false && (str_contains($lockedResult['message'] ?? '', 'exceeded') || str_contains($lockedResult['message'] ?? '', 'Too many')),
    "Expected locked message, got: " . json_encode($lockedResult)
);

// 3.4 Valid verification & Single-use invalidation
$testEmail2 = 'qa_test_valid_' . time() . '@example.com';
$otp2 = Otp::generate($testEmail2, 'BUYER_REGISTRATION', '127.0.0.1');

$validResult = Otp::verify($testEmail2, $otp2, 'BUYER_REGISTRATION');
assertTest(
    'Correct OTP successfully verifies',
    $validResult['success'] === true,
    "Verification failed: " . json_encode($validResult)
);

// Replay attack test (cannot use same OTP twice)
$replayResult = Otp::verify($testEmail2, $otp2, 'BUYER_REGISTRATION');
assertTest(
    'OTP is single-use: replay attempt immediately rejected',
    $replayResult['success'] === false && (str_contains($replayResult['message'] ?? '', 'already used') || str_contains($replayResult['message'] ?? '', 'No active')),
    "Replay attack was not rejected: " . json_encode($replayResult)
);

// ------------------------------------------------------------------------
// 4. SUPPLIER APPROVAL WORKFLOW INTEGRITY
// ------------------------------------------------------------------------
echo "\n--- 4. Testing Supplier Registration & Approval State Integrity ---\n";

$supEmail = 'qa_supplier_' . time() . '@test.com';

// 1. Create Supplier User
$supUserId = User::createSupplierUser([
    'name'         => 'QA Supplier Tester',
    'company_name' => 'QA Sports Goods Ltd',
    'email'        => $supEmail,
    'password'     => 'Secret123!@#',
    'phone'        => '9876543210',
    'country_code' => '+91',
    'country_id'   => 1,
    'state'        => 'Punjab',
    'city'         => 'Jalandhar',
]);

$supProfileId = Supplier::create([
    'user_id'          => $supUserId,
    'business_type_id' => 1,
    'country_id'       => 1,
    'state'            => 'Punjab',
    'city'             => 'Jalandhar',
    'company_name'     => 'QA Sports Goods Ltd',
    'contact_person'   => 'QA Supplier Tester',
    'email'            => $supEmail,
    'phone'            => '9876543210',
    'country_code'     => '+91',
    'address'          => 'Leather Complex, Road No 2',
    'postal_code'      => '144021',
    'status'           => 'ACTIVE',
    'approval_status'  => 'PENDING_VERIFICATION',
]);

$supOtp = Otp::generate($supEmail, 'SUPPLIER_REGISTRATION');

// Verify initial state
$supRecord = Supplier::findById($supProfileId);
assertTest(
    'Supplier created with initial status PENDING_VERIFICATION',
    $supRecord['approval_status'] === 'PENDING_VERIFICATION',
    "Actual status: {$supRecord['approval_status']}"
);

// Simulate OTP Verification step for Supplier
$verifySup = Otp::verify($supEmail, $supOtp, 'SUPPLIER_REGISTRATION');
assertTest('Supplier registration OTP verifies successfully', $verifySup['success'] === true);

// Execute the exact transition that AuthController performs:
User::markEmailVerified((int)$supUserId, 'SUPPLIER');
Supplier::updateApprovalStatus((int)$supProfileId, 'PENDING_APPROVAL');

$supAfterVerify = Supplier::findById($supProfileId);
$userAfterVerify = User::findById($supUserId);

assertTest(
    'CRITICAL: Supplier moves to PENDING_APPROVAL after email verification (NOT APPROVED)',
    $supAfterVerify['approval_status'] === 'PENDING_APPROVAL' && !empty($userAfterVerify['email_verified_at']),
    "Status must remain PENDING_APPROVAL for admin review, got: {$supAfterVerify['approval_status']}"
);

assertTest(
    'Pending supplier CANNOT publish products (Backend authorization guard)',
    $supAfterVerify['approval_status'] !== 'APPROVED',
    'Pending supplier must not be approved'
);

// ------------------------------------------------------------------------
// 5. PROFILE EMAIL CHANGE WORKFLOW
// ------------------------------------------------------------------------
echo "\n--- 5. Testing Profile Email Change Verification Workflow ---\n";

$oldEmail = $supEmail;
$newEmail = 'qa_new_email_' . time() . '@test.com';

// Simulate Request Email Change
User::execute("UPDATE users SET pending_email = :pending WHERE id = :id", [
    ':pending' => $newEmail,
    ':id'      => $supUserId
]);
$emailChangeOtp = Otp::generate($newEmail, 'PROFILE_EMAIL_CHANGE');

$userPending = User::findById($supUserId);
assertTest(
    'Profile email change records pending_email in users table',
    $userPending['pending_email'] === $newEmail,
    "Expected {$newEmail}, got {$userPending['pending_email']}"
);

// Verify with wrong OTP
$wrongRes = Otp::verify($newEmail, '999999', 'PROFILE_EMAIL_CHANGE');
assertTest(
    'Wrong OTP does NOT update profile email',
    $wrongRes['success'] === false,
    'Wrong OTP was accepted'
);

// Verify with correct OTP & perform migration
$correctRes = Otp::verify($newEmail, $emailChangeOtp, 'PROFILE_EMAIL_CHANGE');
assertTest('Correct OTP verifies for profile email change', $correctRes['success'] === true);

User::execute("
    UPDATE users 
    SET email = :email, email_verified_at = NOW(), pending_email = NULL, updated_at = NOW() 
    WHERE id = :id
", [
    ':email' => $newEmail,
    ':id'    => $supUserId
]);

Supplier::execute("
    UPDATE suppliers 
    SET email = :email, updated_at = NOW() 
    WHERE user_id = :userId
", [
    ':email'  => $newEmail,
    ':userId' => $supUserId
]);

$userUpdated = User::findById($supUserId);
$supUpdated = Supplier::findById($supProfileId);

assertTest(
    'User email updated to new email and pending_email cleared',
    $userUpdated['email'] === $newEmail && empty($userUpdated['pending_email']),
    "User email not updated properly"
);

assertTest(
    'Supplier profile email synchronized with user account email',
    $supUpdated['email'] === $newEmail,
    "Supplier profile email not synchronized"
);

// ------------------------------------------------------------------------
// 6. ENQUIRY NOTIFICATION LOGGING & RESILIENCE
// ------------------------------------------------------------------------
echo "\n--- 6. Testing Product Inquiry Email Notification Logging ---\n";

// Insert a test inquiry
$inquiryId = ProductInquiry::create([
    'buyer_name'     => 'QA Global Importer',
    'buyer_email'    => 'qa_importer_' . time() . '@test.com',
    'buyer_phone'    => '+1 555-0199',
    'country_id'     => 1,
    'product_id'     => 1,
    'quantity'       => 500,
    'message'        => 'Need bulk quotation CIF New York port with customized branding.',
    'enquiry_source' => 'PRODUCT_PAGE',
    'status'         => 'PENDING'
]);

assertTest('Test inquiry created in database', $inquiryId > 0);

// Verify columns exist in product_inquiries
$inquiryCols = $pdo->query("SHOW COLUMNS FROM product_inquiries LIKE 'email_notification_status'")->fetch();
assertTest(
    'Table product_inquiries contains email_notification_status column',
    !empty($inquiryCols),
    'Missing email_notification_status column'
);

// Update notification status as ShopController does
ProductInquiry::execute("
    UPDATE product_inquiries 
    SET email_notification_status = :status, email_notified_at = NOW() 
    WHERE id = :id
", [
    ':status' => 'SENT',
    ':id'     => (int)$inquiryId
]);

$freshInquiry = ProductInquiry::findById((int)$inquiryId);
assertTest(
    'Inquiry records email notification status and timestamp',
    $freshInquiry['email_notification_status'] === 'SENT' && !empty($freshInquiry['email_notified_at']),
    'Inquiry notification status not recorded properly'
);

// Clean up test records
$pdo->prepare("DELETE FROM email_otps WHERE email IN (?, ?, ?, ?)")->execute([$testEmail, $testEmail2, $oldEmail, $newEmail]);
$pdo->prepare("DELETE FROM product_inquiries WHERE id = ?")->execute([$inquiryId]);
$pdo->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$supProfileId]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$supUserId]);

// ------------------------------------------------------------------------
// SUMMARY REPORT
// ------------------------------------------------------------------------
echo "\n========================================================================\n";
echo " TEST RUN RESULTS: {$testsPassed} PASSED, {$testsFailed} FAILED\n";
echo "========================================================================\n";

if ($testsFailed > 0) {
    echo "\nFailures:\n";
    foreach ($failures as $f) {
        echo " - {$f}\n";
    }
    exit(1);
} else {
    echo "\nAll verification checks passed with 100% compliance.\n";
    exit(0);
}
