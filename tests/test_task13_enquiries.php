<?php
declare(strict_types=1);

/**
 * Test Suite for Task 13 (All Enquiries File Attachment & Admin Records)
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Models/ProductInquiry.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

use App\Models\ProductInquiry;
use App\Models\User;

$errors = [];
$passed = 0;

echo "=== STARTING VERIFICATION FOR TASK 13: ENQUIRY FILE ATTACHMENTS ===\n\n";

$cookieFile = tempnam(sys_get_temp_dir(), 'sj_admin_');

// Helper to login as Super Admin
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

$adminLoggedIn = loginAdmin($cookieFile);
if (!$adminLoggedIn) {
    echo "FAILED: Could not login as Super Admin\n";
    exit(1);
}

// TEST 1: Submit enquiry without attachment (optional attachment test)
echo "TEST 1: Submit enquiry without attachment... ";
$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/inquiries');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'buyer_name' => 'Test Buyer No File',
    'buyer_email' => 'testbuyer.nofile@example.com',
    'target_quantity' => '250',
    'specifications' => 'Looking for standard English willow bats without customization.',
    'enquiry_source' => 'RFQ'
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json1 = json_decode((string)$response, true);
if ($httpCode === 200 && !empty($json1['success']) && !empty($json1['id'])) {
    $enqId1 = (int)$json1['id'];
    $row1 = ProductInquiry::findById($enqId1, true);
    if ($row1 && empty($row1['attachment_path'])) {
        echo "OK (ID: #$enqId1, saved without attachment)\n";
        $passed++;
    } else {
        echo "FAILED (Attachment path not empty)\n";
        $errors[] = "Test 1 attachment path should be null";
    }
} else {
    echo "FAILED (HTTP $httpCode: $response)\n";
    $errors[] = "Test 1 submission failed";
}

// TEST 2: Submit enquiry with valid PDF attachment
echo "TEST 2: Submit enquiry with valid PDF attachment... ";
$testPdfPath = tempnam(sys_get_temp_dir(), 'test_spec_') . '.pdf';
file_put_contents($testPdfPath, "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\ntrailer\n<<\n>>\n%%EOF");

$cfile = new CURLFile($testPdfPath, 'application/pdf', 'Sports_Junction_Spec_Sheet.pdf');

$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/inquiries');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'buyer_name' => 'John Specifications Officer',
    'buyer_email' => 'techpack@apexsports.com',
    'company_name' => 'Apex Sports Global',
    'target_quantity' => '500',
    'specifications' => 'Please find attached our international grade tech pack specifications and branding layout.',
    'enquiry_source' => 'FAST_OEM',
    'attachment' => $cfile
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json2 = json_decode((string)$response, true);
$enqId2 = 0;
if ($httpCode === 200 && !empty($json2['success']) && !empty($json2['id'])) {
    $enqId2 = (int)$json2['id'];
    $row2 = ProductInquiry::findById($enqId2, true);
    $fullStoragePath = dirname(__DIR__) . '/' . ltrim($row2['attachment_path'] ?? '', '/');

    if ($row2 && !empty($row2['attachment_path']) && 
        $row2['attachment_name'] === 'Sports_Junction_Spec_Sheet.pdf' &&
        file_exists($fullStoragePath)) {
        echo "OK (ID: #$enqId2, File saved: {$row2['attachment_path']})\n";
        $passed++;
    } else {
        echo "FAILED (Attachment metadata or file missing on disk)\n";
        $errors[] = "Test 2 attachment verification failed";
    }
} else {
    echo "FAILED (HTTP $httpCode: $response)\n";
    $errors[] = "Test 2 submission failed";
}

// TEST 3: Super Admin enquiry view shows attachment card
echo "TEST 3: Super Admin view shows attachment details & download button... ";
$ch = curl_init("http://localhost/Antigravity/Sports-Junction/admin/enquiries/{$enqId2}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$detailHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && 
    str_contains($detailHtml, 'Sports_Junction_Spec_Sheet.pdf') &&
    str_contains($detailHtml, "/admin/enquiries/attachment/{$enqId2}")) {
    echo "OK (Admin detail view renders attachment card and download link)\n";
    $passed++;
} else {
    echo "FAILED (HTTP $httpCode: Detail HTML does not contain attachment link)\n";
    $errors[] = "Test 3 admin view missing attachment card";
}

// TEST 4: Super Admin downloads attachment with authorized access
echo "TEST 4: Super Admin downloads attachment securely... ";
$ch = curl_init("http://localhost/Antigravity/Sports-Junction/admin/enquiries/attachment/{$enqId2}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HEADER, true);
$downloadResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && 
    str_contains($downloadResponse, 'Content-Disposition: attachment; filename="Sports_Junction_Spec_Sheet.pdf"') &&
    str_contains($downloadResponse, '%PDF-1.4')) {
    echo "OK (File downloaded with 200 OK and matching PDF content)\n";
    $passed++;
} else {
    echo "FAILED (HTTP $httpCode)\n";
    $errors[] = "Test 4 attachment download failed";
}

// TEST 5: Unauthorized access (Guest) receives 401
echo "TEST 5: Unauthorized guest download attempt... ";
$ch = curl_init("http://localhost/Antigravity/Sports-Junction/admin/enquiries/attachment/{$enqId2}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$guestResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401) {
    echo "OK (401 Unauthorized correctly enforced)\n";
    $passed++;
} else {
    echo "FAILED (Expected 401, got $httpCode)\n";
    $errors[] = "Test 5 guest unauthorized check failed";
}

// TEST 6: Invalid file type rejection
echo "TEST 6: Rejection of invalid executable file format (.php/.exe)... ";
$testPhpScript = tempnam(sys_get_temp_dir(), 'exploit_') . '.php';
file_put_contents($testPhpScript, "<?php echo 'malicious';");
$badFile = new CURLFile($testPhpScript, 'application/x-php', 'backdoor.php');

$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/inquiries');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'buyer_name' => 'Hacker Test',
    'buyer_email' => 'badactor@example.com',
    'specifications' => 'Attempting to upload executable php file',
    'attachment' => $badFile
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 422 && str_contains((string)$response, 'Invalid attachment format')) {
    echo "OK (422 Unprocessable Entity - Invalid format rejected)\n";
    $passed++;
} else {
    echo "FAILED (HTTP $httpCode: $response)\n";
    $errors[] = "Test 6 invalid file type was not rejected with 422";
}

// TEST 7: Oversized file rejection (> 10MB)
echo "TEST 7: Rejection of oversized file (>10MB limit)... ";
$oversizedFile = tempnam(sys_get_temp_dir(), 'oversized_') . '.pdf';
$fp = fopen($oversizedFile, 'w');
fseek($fp, (11 * 1024 * 1024) - 1, SEEK_SET);
fwrite($fp, "\0");
fclose($fp);

$bigCfile = new CURLFile($oversizedFile, 'application/pdf', 'huge_catalog.pdf');

$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/inquiries');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'buyer_name' => 'Big File Buyer',
    'buyer_email' => 'bigfile@example.com',
    'specifications' => 'Sending 11MB file to test size threshold',
    'attachment' => $bigCfile
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 422 && str_contains((string)$response, 'exceeds the 10MB')) {
    echo "OK (422 Unprocessable Entity - Oversized file rejected)\n";
    $passed++;
} else {
    echo "FAILED (HTTP $httpCode: $response)\n";
    $errors[] = "Test 7 oversized file was not rejected";
}

// Clean up temporary local test files
@unlink($testPdfPath);
@unlink($testPhpScript);
@unlink($oversizedFile);
@unlink($cookieFile);

echo "\n=== TASK 13 SUMMARY ===\n";
echo "Tests Passed: $passed / 7\n";
if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $e) {
        echo " - $e\n";
    }
    exit(1);
} else {
    echo "TASK 13 ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
}
