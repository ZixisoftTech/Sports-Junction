<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $rel = substr($class, strlen($prefix));
        $file = __DIR__ . '/../app/' . str_replace('\\', '/', $rel) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

\App\Helpers\Session::start();

echo "======================================================================\n";
echo "SPORTS JUNCTION — STATIC OTP (123456) EVERYWHERE VERIFICATION TEST\n";
echo "======================================================================\n";

$passed = 0;
$failed = 0;

function assertCheck(bool $condition, string $title, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$title}\n";
        if ($details) echo "         {$details}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$title}\n";
        if ($details) echo "         {$details}\n";
    }
}

// -------------------------------------------------------------------------
// TEST 1: Default Static OTP Enabled
// -------------------------------------------------------------------------
echo "\n--- [TEST 1] Static OTP Configuration Detection ---\n";

$isStatic = \App\Models\Otp::isStaticOtpEnabled();
assertCheck($isStatic === true, "Static OTP is enabled by default everywhere");

// -------------------------------------------------------------------------
// TEST 2: Registration OTP Generation & Verification
// -------------------------------------------------------------------------
echo "\n--- [TEST 2] Buyer & Supplier Registration OTP ---\n";

$testBuyerEmail = "static_buyer_" . time() . "@example.com";
$otpGenBuyer = \App\Models\Otp::generate($testBuyerEmail, 'REGISTRATION');
assertCheck($otpGenBuyer === '123456', "Buyer Registration OTP generates static code 123456", "Generated: {$otpGenBuyer}");

$verifyBuyerRes = \App\Models\Otp::verify($testBuyerEmail, '123456', 'REGISTRATION');
assertCheck($verifyBuyerRes['success'] === true, "Buyer Registration verification with 123456 succeeds", $verifyBuyerRes['message']);

$testSupEmail = "static_supplier_" . time() . "@example.com";
$otpGenSup = \App\Models\Otp::generate($testSupEmail, 'SUPPLIER_REGISTRATION');
assertCheck($otpGenSup === '123456', "Supplier Registration OTP generates static code 123456", "Generated: {$otpGenSup}");

$verifySupRes = \App\Models\Otp::verify($testSupEmail, '123456', 'SUPPLIER_REGISTRATION');
assertCheck($verifySupRes['success'] === true, "Supplier Registration verification with 123456 succeeds", $verifySupRes['message']);

// -------------------------------------------------------------------------
// TEST 3: Forgot Password Recovery OTP Generation & Verification
// -------------------------------------------------------------------------
echo "\n--- [TEST 3] Forgot Password Recovery OTP ---\n";

$testResetEmail = "static_reset_" . time() . "@example.com";
$otpGenReset = \App\Models\Otp::generate($testResetEmail, 'PASSWORD_RESET');
assertCheck($otpGenReset === '123456', "Password Reset OTP generates static code 123456", "Generated: {$otpGenReset}");

// Test wrong OTP is rejected
$wrongVerify = \App\Models\Otp::verify($testResetEmail, '999999', 'PASSWORD_RESET');
assertCheck($wrongVerify['success'] === false, "Incorrect passcode (999999) is rejected", $wrongVerify['message']);

// Test correct static OTP succeeds
$verifyResetRes = \App\Models\Otp::verify($testResetEmail, '123456', 'PASSWORD_RESET');
assertCheck($verifyResetRes['success'] === true, "Password Reset verification with 123456 succeeds", $verifyResetRes['message']);

// -------------------------------------------------------------------------
// TEST 4: View Banners Render Static Passcode 123456
// -------------------------------------------------------------------------
echo "\n--- [TEST 4] View Template Banners ---\n";

// 1. verify_otp.php
$verifyViewFile = __DIR__ . '/../app/Views/auth/verify_otp.php';
$verifyViewContent = file_get_contents($verifyViewFile);
assertCheck(
    str_contains($verifyViewContent, 'isStaticOtpEnabled') && str_contains($verifyViewContent, '123456'),
    "Verify Email view renders static passcode helper banner and 123456 auto-fill button"
);

// 2. reset_password.php
$resetViewFile = __DIR__ . '/../app/Views/auth/reset_password.php';
$resetViewContent = file_get_contents($resetViewFile);
assertCheck(
    str_contains($resetViewContent, 'isStaticOtpEnabled') && str_contains($resetViewContent, '123456'),
    "Reset Password view renders static passcode helper banner and 123456 auto-fill button"
);

// -------------------------------------------------------------------------
// TEST 5: Production Environment Persistence
// -------------------------------------------------------------------------
echo "\n--- [TEST 5] Static OTP in Production Mode Simulation ---\n";

// Temporarily set APP_ENV to production
$origEnv = $_ENV['APP_ENV'] ?? 'local';
$_ENV['APP_ENV'] = 'production';

$prodStatic = \App\Models\Otp::isStaticOtpEnabled();
assertCheck($prodStatic === true, "Static OTP remains active in production when USE_STATIC_OTP=true");

$prodOtp = \App\Models\Otp::generate("prod_test_" . time() . "@example.com", "REGISTRATION");
assertCheck($prodOtp === '123456', "Static OTP 123456 is generated in production mode", "Generated: {$prodOtp}");

// Restore APP_ENV
$_ENV['APP_ENV'] = $origEnv;

// -------------------------------------------------------------------------
// TEST 6: Admin Settings Toggle Simulation
// -------------------------------------------------------------------------
echo "\n--- [TEST 6] Admin Settings Toggle & Persistence ---\n";

\App\Models\SiteSetting::set('use_static_otp', '1', 'auth');
$savedDbSetting = \App\Models\SiteSetting::get('use_static_otp');
assertCheck($savedDbSetting === '1', "Admin SiteSetting 'use_static_otp' persists '1' in database");

echo "\n======================================================================\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED out of " . ($passed + $failed) . " CHECKS\n";
echo "======================================================================\n";

if ($failed > 0) {
    exit(1);
}
