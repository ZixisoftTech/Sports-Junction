<?php
declare(strict_types=1);

/**
 * Test Suite for Task 12 (Complete Website Settings Page & Persistence)
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Models/SiteSetting.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

use App\Models\SiteSetting;
use App\Models\User;

$errors = [];
$passed = 0;

echo "=== STARTING VERIFICATION FOR TASK 12: WEBSITE SETTINGS ===\n\n";

$cookieFile = tempnam(sys_get_temp_dir(), 'sj_cookie_');

// Helper: login as admin using curl with cookiejar and csrf token
function loginAdmin(string $cookieFile): bool {
    $ch = curl_init('http://localhost/Antigravity/Sports-Junction/admin/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return false;

    preg_match('/name="csrf_token"\s+value="([^"]+)"/i', $html, $matches);
    $csrf = $matches[1] ?? '';

    $ch = curl_init('http://localhost/Antigravity/Sports-Junction/admin/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'email' => 'sportjunctions@gmail.com',
        'password' => 'Sport@123',
        'csrf_token' => $csrf
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200 && str_contains($res, 'Dashboard'));
}

$loggedIn = loginAdmin($cookieFile);
if (!$loggedIn) {
    echo "FAILED: Could not login as Super Admin\n";
    exit(1);
}

// TEST 1: GET /admin/settings renders HTTP 200 with no fatal errors
echo "TEST 1: GET /admin/settings as Super Admin... ";
$ch = curl_init('http://localhost/Antigravity/Sports-Junction/admin/settings');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && str_contains($html, 'Website Branding, Contact & Social Settings')) {
    echo "OK (HTTP 200, Content Loaded)\n";
    $passed++;
} else {
    echo "FAILED (HTTP $httpCode)\n";
    $errors[] = "Failed to load /admin/settings cleanly";
}

// TEST 2: POST /admin/settings/general updates database settings
echo "TEST 2: POST /admin/settings/general persistence test... ";
$postData = [
    'site_name' => 'Sports Junction Verified Global',
    'site_tagline' => 'India\'s Direct Sports Goods Manufacturing Hub',
    'contact_phone' => '+91 98888 77777',
    'contact_email' => 'tradedesk.test@sportsjunction.in',
    'contact_address' => 'Phase 2 Industrial Area, Meerut, UP, India',
    'working_hours' => 'Mon - Sat: 9:00 AM - 7:00 PM IST',
    'social_facebook' => 'https://facebook.com/sportsjunctiontest12',
    'social_twitter' => 'https://x.com/sportsjunctiontest12',
    'social_instagram' => 'https://instagram.com/sportsjunctiontest12'
];

$ch = curl_init('http://localhost/Antigravity/Sports-Junction/admin/settings/general');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$resultHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verify in DB directly
$saved = SiteSetting::getAllKeyed();
if ($saved['site_name'] === 'Sports Junction Verified Global' &&
    $saved['contact_email'] === 'tradedesk.test@sportsjunction.in' &&
    $saved['social_twitter'] === 'https://x.com/sportsjunctiontest12') {
    echo "OK (Settings persisted in DB)\n";
    $passed++;
} else {
    echo "FAILED (DB values mismatch)\n";
    $errors[] = "DB settings persistence mismatch";
}

// TEST 3: Public homepage & contact page reflect updated settings
echo "TEST 3: Verify public frontend reflects updated settings... ";
$homeHtml = file_get_contents('http://localhost/Antigravity/Sports-Junction/');
$contactHtml = file_get_contents('http://localhost/Antigravity/Sports-Junction/contact');

$hasName = str_contains($homeHtml, 'Sports Junction Verified Global');
$hasEmail = str_contains($contactHtml, 'tradedesk.test@sportsjunction.in');
$hasSocialX = str_contains($contactHtml, 'https://x.com/sportsjunctiontest12');

if ($hasName && $hasEmail && $hasSocialX) {
    echo "OK (Public site reflects updated name, email, and social link)\n";
    $passed++;
} else {
    echo "FAILED (Frontend did not reflect updated settings)\n";
    $errors[] = "Frontend display mismatch";
}

// TEST 4: Revert to standard official settings
echo "TEST 4: Restoring standard official settings... ";
SiteSetting::set('site_name', 'Sports Junction', 'branding');
SiteSetting::set('site_tagline', 'India\'s Sports Manufacturing Marketplace', 'branding');
SiteSetting::set('contact_phone', '+91 98765 43210', 'contact');
SiteSetting::set('contact_email', 'sportjunctions@gmail.com', 'contact');
SiteSetting::set('contact_address', 'Sports Complex Industrial Area, Jalandhar & Meerut Clusters, India', 'contact');
SiteSetting::set('working_hours', 'Mon - Sat: 9:00 AM - 8:00 PM IST', 'contact');
SiteSetting::set('social_facebook', 'https://facebook.com/sportsjunctionindia', 'social');
SiteSetting::set('social_twitter', 'https://x.com/sportsjunctionin', 'social');
SiteSetting::set('social_instagram', 'https://instagram.com/sportsjunctionin', 'social');
SiteSetting::set('social_linkedin', '', 'social');
SiteSetting::set('social_youtube', '', 'social');
SiteSetting::set('social_tiktok', '', 'social');

$restored = SiteSetting::getAllKeyed();
if ($restored['site_name'] === 'Sports Junction' && $restored['contact_email'] === 'sportjunctions@gmail.com') {
    echo "OK (Restored defaults)\n";
    $passed++;
} else {
    echo "FAILED (Failed to restore defaults)\n";
    $errors[] = "Failed to restore defaults";
}

echo "\n=== TASK 12 SUMMARY ===\n";
echo "Tests Passed: $passed / 4\n";
if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $e) {
        echo " - $e\n";
    }
    exit(1);
} else {
    echo "TASK 12 ALL TESTS PASSED!\n";
    exit(0);
}
