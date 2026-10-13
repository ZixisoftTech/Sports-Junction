<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — EMAIL DELIVERY & SMTP DIAGNOSTIC TEST SUITE
 * =======================================================================
 * Tests all transactional email templates, SMTP socket connectivity,
 * fallback simulation logging, and OTP verification retrieval.
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

use App\Helpers\Env;
use App\Helpers\Mailer;

$passed = 0;
$failed = 0;

function it(string $title, callable $test) {
    global $passed, $failed;
    echo "\n  • Testing: {$title} ... ";
    try {
        $test();
        echo "\033[32mPASSED\033[0m\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "\033[31mFAILED\033[0m\n";
        echo "    \033[33mError: " . $e->getMessage() . "\033[0m\n";
        echo "    \033[33mAt: " . $e->getFile() . ":" . $e->getLine() . "\033[0m\n";
        $failed++;
    }
}

function expectTrue($val, string $msg = 'Expected value to be true') {
    if ($val !== true) {
        throw new \Exception($msg . ' (Got: ' . var_export($val, true) . ')');
    }
}

function expectContains(string $needle, string $haystack, string $msg = '') {
    if (!str_contains($haystack, $needle)) {
        throw new \Exception(($msg ?: "Expected string to contain '{$needle}'"));
    }
}

echo "\n\033[1;34m=======================================================================\033[0m\n";
echo "\033[1;34m   SPORTS JUNCTION — EMAIL SUBSYSTEM & DELIVERY TEST SUITE             \033[0m\n";
echo "\033[1;34m=======================================================================\033[0m\n";

// --- Group 1: Configuration & SMTP Diagnostics ---
echo "\n\033[1;36m[GROUP 1: Mail Configuration & SMTP Diagnostics]\033[0m\n";

it('Validates mail configuration keys in .env', function() {
    $host = Env::get('MAIL_HOST');
    $port = Env::get('MAIL_PORT');
    $user = Env::get('MAIL_USERNAME');
    $from = Env::get('MAIL_FROM_ADDRESS');

    expectTrue(!empty($host), 'MAIL_HOST must be defined');
    expectTrue(!empty($port), 'MAIL_PORT must be defined');
    expectTrue(!empty($user), 'MAIL_USERNAME must be defined');
    expectTrue(!empty($from), 'MAIL_FROM_ADDRESS must be defined');
});

it('Runs Mailer::testConnection() diagnostic report', function() {
    $diag = Mailer::testConnection();
    expectTrue(is_array($diag), 'Diagnostic should return an array');
    expectTrue(isset($diag['status']), 'Diagnostic must report a status');
    expectTrue(isset($diag['log_file']), 'Diagnostic must report a log file');

    echo "\n    \033[36m[Diagnostic Info]\033[0m";
    echo "\n    Status:     " . $diag['status'];
    echo "\n    Host:Port:  " . ($diag['host'] ?? 'N/A') . ':' . ($diag['port'] ?? 'N/A');
    echo "\n    User:       " . ($diag['user'] ?? 'N/A');
    echo "\n    Message:    " . ($diag['message'] ?? 'N/A');
    echo "\n    Log File:   " . ($diag['log_file'] ?? 'N/A') . "\n";
});

// --- Group 2: All 10 Transactional Email Methods ---
echo "\n\033[1;36m[GROUP 2: Transactional Email Generation & Dispatch]\033[0m\n";

$testEmail = 'sportsjunction_test_' . time() . '@example.com';
$buyerOtp = (string) random_int(100000, 999999);
$supplierOtp = (string) random_int(100000, 999999);
$resetOtp = (string) random_int(100000, 999999);

it('Dispatches Buyer Registration OTP (#1)', function() use ($testEmail, $buyerOtp) {
    $sent = Mailer::sendBuyerOtp($testEmail, 'Arjun Singh', $buyerOtp);
    expectTrue($sent, 'sendBuyerOtp should complete successfully');
});

it('Dispatches Supplier Facility Registration OTP (#2)', function() use ($testEmail, $supplierOtp) {
    $sent = Mailer::sendSupplierOtp($testEmail, 'Vortex Sports Industries Ltd', $supplierOtp);
    expectTrue($sent, 'sendSupplierOtp should complete successfully');
});

it('Dispatches Password Reset Recovery OTP (#3)', function() use ($testEmail, $resetOtp) {
    $sent = Mailer::sendPasswordResetOtp($testEmail, 'Arjun Singh', $resetOtp);
    expectTrue($sent, 'sendPasswordResetOtp should complete successfully');
});

it('Dispatches Buyer Verification Success Email (#4)', function() use ($testEmail) {
    $sent = Mailer::sendBuyerVerified($testEmail, 'Arjun Singh');
    expectTrue($sent, 'sendBuyerVerified should complete successfully');
});

it('Dispatches Supplier Application Received Email (#5)', function() use ($testEmail) {
    $sent = Mailer::sendSupplierApplicationReceived($testEmail, 'Vortex Sports Industries Ltd');
    expectTrue($sent, 'sendSupplierApplicationReceived should complete successfully');
});

it('Dispatches Supplier Approved Notification (#6)', function() use ($testEmail) {
    $sent = Mailer::sendSupplierApproved($testEmail, 'Vortex Sports Industries Ltd');
    expectTrue($sent, 'sendSupplierApproved should complete successfully');
});

it('Dispatches Supplier Rejected Notification with Reason (#7)', function() use ($testEmail) {
    $sent = Mailer::sendSupplierRejected(
        $testEmail,
        'Vortex Sports Industries Ltd',
        'Submitted GST registration certificate is illegible. Please re-upload clear PDF.'
    );
    expectTrue($sent, 'sendSupplierRejected should complete successfully');
});

it('Dispatches Product Submitted for Review Notification (#8)', function() use ($testEmail) {
    $sent = Mailer::sendProductSubmitted($testEmail, 'Vortex Sports Industries Ltd', 'Professional Hand-Stitched Match Football (Size 5)');
    expectTrue($sent, 'sendProductSubmitted should complete successfully');
});

it('Dispatches Product Approved Notification (#9)', function() use ($testEmail) {
    $sent = Mailer::sendProductApproved($testEmail, 'Vortex Sports Industries Ltd', 'Professional Hand-Stitched Match Football (Size 5)');
    expectTrue($sent, 'sendProductApproved should complete successfully');
});

it('Dispatches Product Rejected Notification with Specifications Feedback (#10)', function() use ($testEmail) {
    $sent = Mailer::sendProductRejected(
        $testEmail,
        'Vortex Sports Industries Ltd',
        'Professional Hand-Stitched Match Football (Size 5)',
        'Primary product gallery requires minimum 3 high-resolution images showing stitching detail.'
    );
    expectTrue($sent, 'sendProductRejected should complete successfully');
});

// --- Group 3: Log Verification & OTP Extraction ---
echo "\n\033[1;36m[GROUP 3: Log Audit & OTP Extraction Helpers]\033[0m\n";

it('Verifies storage/logs/mail.log exists and contains entries', function() use ($testEmail) {
    $logFile = dirname(__DIR__) . '/storage/logs/mail.log';
    expectTrue(file_exists($logFile), "Log file {$logFile} must exist");
    
    $contents = file_get_contents($logFile);
    expectContains($testEmail, $contents, 'Log file must contain test email address');
    expectContains('Sports Junction', $contents, 'Log file must contain Sports Junction branding');
});

it('Verifies Mailer::getLastSentOtp accurately retrieves passcodes', function() use ($testEmail, $resetOtp) {
    $retrieved = Mailer::getLastSentOtp($testEmail);
    expectTrue(!empty($retrieved), 'getLastSentOtp must retrieve an OTP passcode');
    // The last OTP sent to this email was resetOtp
    expectTrue($retrieved === $resetOtp, "Expected retrieved OTP '{$retrieved}' to match last dispatched OTP '{$resetOtp}'");
});

// Summary
echo "\n\033[1;34m=======================================================================\033[0m\n";
echo "  \033[1mTESTS COMPLETED:\033[0m Total: " . ($passed + $failed) . " | \033[32mPassed: {$passed}\033[0m | \033[31mFailed: {$failed}\033[0m\n";
echo "\033[1;34m=======================================================================\033[0m\n\n";

if ($failed > 0) {
    exit(1);
}
