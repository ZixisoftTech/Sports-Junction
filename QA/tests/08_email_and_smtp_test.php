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

echo "=== TEST SUITE 8: EMAIL & SMTP TRANSACTIONAL AUDIT ===\n";

// 1. SMTP Connection Status
$conn = \App\Helpers\Mailer::testConnection();
assertTest("SMTP Authentication & Connection", ($conn['status'] ?? '') === 'AUTHENTICATED_AND_READY', "SMTP is not authenticated: " . ($conn['message'] ?? ''));

// 2. Buyer Registration OTP Email
$otp = '789123';
$buyerHtml = \App\Helpers\Mailer::renderTemplate(
    'Verify Your Buyer Email Address',
    'EMAIL VERIFICATION',
    "<p>Hello Aarav Patel,</p><p>Please use the verification code below to activate your Sports Junction buyer account:</p>" . \App\Helpers\Mailer::renderOtpBox($otp, '10')
);
assertTest("Buyer Registration OTP template renders", !empty($buyerHtml) && str_contains($buyerHtml, $otp));
assertTest("No unparsed template variables in Buyer OTP", !preg_match('/\{\{[a-zA-Z0-9_\s]+\}\}/', $buyerHtml));

// 3. Supplier Registration OTP Email
$supOtp = '456789';
$supHtml = \App\Helpers\Mailer::renderTemplate(
    'Verify Your Manufacturing Facility Account',
    'SUPPLIER VERIFICATION',
    "<p>Hello Apex Sporting Goods,</p><p>Your account verification code is below:</p>" . \App\Helpers\Mailer::renderOtpBox($supOtp, '10')
);
assertTest("Supplier Registration OTP template renders", !empty($supHtml) && str_contains($supHtml, $supOtp));
assertTest("No unparsed template variables in Supplier OTP", !preg_match('/\{\{[a-zA-Z0-9_\s]+\}\}/', $supHtml));

// 4. Password Reset OTP Email
$resetOtp = '112233';
$resetHtml = \App\Helpers\Mailer::renderTemplate(
    'Reset Your Sports Junction Password',
    'SECURITY NOTICE',
    "<p>Hello Aarav Patel,</p><p>Use this OTP code to reset your password:</p>" . \App\Helpers\Mailer::renderOtpBox($resetOtp, '10')
);
assertTest("Password Reset OTP template renders", !empty($resetHtml) && str_contains($resetHtml, $resetOtp));

// 5. Supplier Approval & Rejection Emails
$supApprovedHtml = \App\Helpers\Mailer::renderTemplate(
    'Supplier Account Approved',
    'ACCOUNT ACTIVATED',
    "<p>Congratulations Apex Sporting Goods! Your manufacturing facility profile has been approved and published.</p>",
    'https://sportsjunction.in/seller/login',
    'Sign In to Seller Portal'
);
assertTest("Supplier Approved template renders with login URL", !empty($supApprovedHtml) && str_contains($supApprovedHtml, 'seller/login'));

$rejectionReason = "Factory GST certificate copy was unreadable.";
$supRejectedHtml = \App\Helpers\Mailer::renderTemplate(
    'Supplier Application Status Update',
    'STATUS UPDATE',
    "<p>Dear Apex Sporting Goods,</p>" . \App\Helpers\Mailer::renderCalloutBox('Rejection Reason', $rejectionReason, 'danger')
);
assertTest("Supplier Rejected template renders with rejection reason", !empty($supRejectedHtml) && str_contains($supRejectedHtml, $rejectionReason));

// 6. Product Approval & Rejection Emails
$prodApprovedHtml = \App\Helpers\Mailer::renderTemplate(
    'Product Approved & Published',
    'CATALOG UPDATE',
    "<p>Your product <strong>Match Cricket Bat</strong> has passed review and is now live on the marketplace.</p>"
);
assertTest("Product Approved template renders", !empty($prodApprovedHtml) && str_contains($prodApprovedHtml, 'Match Cricket Bat'));

$prodRejectionReason = "Product images must have clean white background.";
$prodRejectedHtml = \App\Helpers\Mailer::renderTemplate(
    'Product Approval Status Update',
    'ACTION REQUIRED',
    "<p>Product: <strong>Match Cricket Bat</strong></p>" . \App\Helpers\Mailer::renderCalloutBox('Revision Required', $prodRejectionReason, 'warning')
);
assertTest("Product Rejected template renders with reason", !empty($prodRejectedHtml) && str_contains($prodRejectedHtml, $prodRejectionReason));

// 7. Enquiries Notifications (Product, RFQ, Fast OEM, Prototype Sample)
$sampleInquiry = [
    'id' => 8881,
    'buyer_name' => 'John Doe',
    'buyer_email' => 'john.doe@example.com',
    'buyer_phone' => '+44 7700 900077',
    'company_name' => 'UK Sports Retail Ltd',
    'country_name' => 'United Kingdom',
    'subject' => 'Send Direct Factory Inquiry: Tennis Rackets',
    'quantity' => 500,
    'message' => 'Please provide quotation for 500 units.',
    'created_at' => date('Y-m-d H:i:s')
];

$rfqHtml = \App\Helpers\Mailer::renderTemplate(
    'New Sourcing RFQ Received',
    'RFQ INQUIRY',
    "<p>A new Request for Quotation was submitted by <strong>UK Sports Retail Ltd</strong> for 500 units.</p>"
);
assertTest("RFQ Notification template renders with buyer specs", !empty($rfqHtml) && str_contains($rfqHtml, 'UK Sports Retail Ltd'));

// 8. Suggestions & Feedback Notification
$fbRefId = 'SFB-202610-99999';
$fbHtml = \App\Helpers\Mailer::renderTemplate(
    "New Feedback Received: {$fbRefId}",
    'MARKETPLACE VOICE',
    "<p>Reference ID: <strong>{$fbRefId}</strong></p><p>Sender: Rohan Sharma (Apex Sports)</p>"
);
assertTest("Feedback Notification template renders with Reference ID", !empty($fbHtml) && str_contains($fbHtml, $fbRefId));

// 9. Career Application Notification & Candidate Confirmation
$appNo = 'APP-202610-99999';
$careerAdminHtml = \App\Helpers\Mailer::renderTemplate(
    "New Candidate Application: {$appNo}",
    'CAREERS',
    "<p>Application No: <strong>{$appNo}</strong></p><p>Candidate: Vikram Malhotra</p>"
);
assertTest("Career Application Admin Alert renders with Application No", !empty($careerAdminHtml) && str_contains($careerAdminHtml, $appNo));

$candidateConfHtml = \App\Helpers\Mailer::renderTemplate(
    'Your Application to Sports Junction',
    'APPLICATION RECEIVED',
    "<p>Dear Vikram Malhotra,</p><p>Your application (Ref: {$appNo}) has been received.</p>"
);
assertTest("Career Application Candidate Confirmation renders", !empty($candidateConfHtml) && str_contains($candidateConfHtml, 'Vikram Malhotra'));

// 10. Buyer Chat Notification to Supplier
$chatAlertHtml = \App\Helpers\Mailer::renderTemplate(
    'New Message from Buyer',
    'INQUIRY CHAT',
    "<p>John Doe sent a message: <em>Are you able to deliver by end of November?</em></p>"
);
assertTest("Chat Alert template renders with message snippet", !empty($chatAlertHtml) && str_contains($chatAlertHtml, 'Are you able to deliver'));

// 11. Security & Password Update Notifications
$pwUpdateHtml = \App\Helpers\Mailer::renderTemplate(
    'Password Updated Successfully',
    'SECURITY ALERT',
    "<p>Dear Aarav Patel, your Sports Junction password was changed.</p>"
);
assertTest("Password Updated Security Notice renders", !empty($pwUpdateHtml) && str_contains($pwUpdateHtml, 'Aarav Patel'));

echo "\nSuite 8 Results: $passedTests PASSED, $failedTests FAILED.\n";
if (!empty($bugs)) {
    echo "Bugs logged:\n";
    print_r($bugs);
}
