<?php
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

echo "=== Comprehensive Test: Task 7 Country Selection in Enquiries & Contact ===" . PHP_EOL;

$db = \App\Core\Database::getConnection();

// 1. Test Countries
$countries = \App\Models\Country::allActive();
echo "Total active countries: " . count($countries) . PHP_EOL;
assert(count($countries) >= 190, "Should have 190+ active countries");
$india = null;
foreach ($countries as $c) {
    if ($c['name'] === 'India') {
        $india = $c;
        break;
    }
}
assert($india !== null, "India must be in active countries list");
echo "Found country India with ID: " . $india['id'] . PHP_EOL;

// 2. Direct Model creation with country_id
$testInqId = \App\Models\ProductInquiry::create([
    'enquiry_source'    => 'CONTACT_US',
    'buyer_name'        => 'Test Country Buyer',
    'buyer_email'       => 'test_country_verify@sportsjunction.test',
    'buyer_phone'       => '+91 9988776655',
    'country_id'        => (int)$india['id'],
    'country_name'      => $india['name'],
    'company_name'      => 'Test Cricket Academy',
    'subject'           => 'Custom Bat Manufacturing Inquiry',
    'target_quantity'   => 150,
    'delivery_location' => 'Jalandhar Sports Hub',
    'specifications'    => 'English willow grade 1 bats with custom laser engraving.',
    'status'            => 'SUBMITTED',
]);

echo "Created test inquiry ID: " . $testInqId . PHP_EOL;
assert($testInqId > 0, "Inquiry ID must be > 0");

// 3. Verify findById returns country
$fetched = \App\Models\ProductInquiry::findById($testInqId, true);
assert(!empty($fetched), "Must find enquiry by ID");
echo "Fetched country_id: " . ($fetched['country_id'] ?? 'null') . PHP_EOL;
echo "Fetched country_name: " . ($fetched['country_name'] ?? 'null') . PHP_EOL;
assert((int)$fetched['country_id'] === (int)$india['id'], "Persisted country_id must match");
assert($fetched['country_name'] === 'India', "Persisted country_name must be India");

// 4. Test Admin DataTable filters and finds by Country
$dtResult = \App\Models\ProductInquiry::getAdminDataTable([
    'search' => ['value' => 'India'],
    'length' => 10,
    'start'  => 0,
]);
assert($dtResult['recordsFiltered'] > 0, "DataTable search by India must find records");
$foundInTable = false;
foreach ($dtResult['data'] as $row) {
    if ((int)$row['id'] === $testInqId) {
        $foundInTable = true;
        assert($row['country_name'] === 'India', "Row country_name must be India");
        break;
    }
}
echo "Found in Admin DataTable with country: " . ($foundInTable ? "YES" : "NO") . PHP_EOL;
assert($foundInTable, "Created inquiry must appear in Admin DataTable");

// 5. Test Admin Export contains Country column
$exportData = \App\Models\ProductInquiry::getAdminExportData(['search' => ['value' => 'test_country_verify']]);
assert(count($exportData) === 1, "Export data must find the specific enquiry");
echo "Exported columns: " . implode(', ', array_keys($exportData[0])) . PHP_EOL;
assert(isset($exportData[0]['Country']), "Export row must contain Country column");
assert($exportData[0]['Country'] === 'India', "Exported country must be India");

// 6. Test HTTP /api/contact rejection when country is missing
$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/contact');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'buyer_name'     => 'No Country Tester',
        'buyer_email'    => 'nocountry@test.com',
        'specifications' => 'Valid message but without country parameter.',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP /api/contact without country returned HTTP: " . $httpCode . PHP_EOL;
echo "Response: " . $resp . PHP_EOL;
assert($httpCode === 422, "Missing country must return HTTP 422");
$decoded = json_decode($resp, true);
assert(isset($decoded['success']) && $decoded['success'] === false, "Must fail with success=false");
assert(str_contains(strtolower($decoded['message'] ?? ''), 'country'), "Message must mention country");

// 7. Test HTTP /api/contact success with country
$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/contact');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'buyer_name'        => 'HTTP Country Tester',
        'buyer_email'       => 'http_country@test.com',
        'country_id'        => (int)$india['id'],
        'subject'           => 'HTTP Contact Submission',
        'delivery_location' => 'New Delhi',
        'specifications'    => 'Valid message with country ID provided.',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP /api/contact with country returned HTTP: " . $httpCode . PHP_EOL;
echo "Response: " . $resp . PHP_EOL;
assert($httpCode === 200, "Valid contact submission must return HTTP 200");
$decoded = json_decode($resp, true);
assert(isset($decoded['success']) && $decoded['success'] === true, "Must succeed with success=true");
$httpInqId = (int)$decoded['id'];
assert($httpInqId > 0, "Returned inquiry id must be > 0");

$httpInq = \App\Models\ProductInquiry::findById($httpInqId, true);
assert((int)$httpInq['country_id'] === (int)$india['id'], "HTTP inquiry country_id must be persisted");
assert($httpInq['country_name'] === 'India', "HTTP inquiry country_name must be persisted");

// 8. Test HTTP /api/inquiries rejection when country is missing
$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/inquiries');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'buyer_name'     => 'No Country RFQ Tester',
        'buyer_email'    => 'nocountry_rfq@test.com',
        'specifications' => 'Valid RFQ specifications without country.',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP /api/inquiries without country returned HTTP: " . $httpCode . PHP_EOL;
echo "Response: " . $resp . PHP_EOL;
assert($httpCode === 422, "Missing country in RFQ must return HTTP 422");

// 9. Test HTTP /api/inquiries success with country
$ch = curl_init('http://localhost/Antigravity/Sports-Junction/api/inquiries');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'buyer_name'        => 'HTTP Country RFQ Tester',
        'buyer_email'       => 'http_rfq@test.com',
        'country_id'        => (int)$india['id'],
        'target_quantity'   => 500,
        'subject'           => 'Custom Wholesale RFP',
        'delivery_location' => 'Mumbai Seaport',
        'specifications'    => 'Full container load of match footballs FIFA Quality Pro.',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP /api/inquiries with country returned HTTP: " . $httpCode . PHP_EOL;
echo "Response: " . $resp . PHP_EOL;
assert($httpCode === 200, "Valid RFQ submission must return HTTP 200");
$decoded = json_decode($resp, true);
assert(isset($decoded['success']) && $decoded['success'] === true, "RFQ must succeed");
$rfqInqId = (int)$decoded['id'];

$rfqInq = \App\Models\ProductInquiry::findById($rfqInqId, true);
assert((int)$rfqInq['country_id'] === (int)$india['id'], "RFQ country_id must be persisted");
assert($rfqInq['country_name'] === 'India', "RFQ country_name must be persisted");

// 10. Check /contact page HTML contains SweetAlert2 and Country select
$ch = curl_init('http://localhost/Antigravity/Sports-Junction/contact');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
]);
$contactHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "GET /contact returned HTTP: " . $httpCode . PHP_EOL;
assert($httpCode === 200, "Contact page must return HTTP 200");
assert(str_contains($contactHtml, 'sweetalert2'), "Contact page must include sweetalert2");
assert(str_contains($contactHtml, 'contactCountrySelect'), "Contact page must have contactCountrySelect");
assert(str_contains($contactHtml, 'India'), "Contact page country dropdown must contain India");
echo "Verified: /contact contains SweetAlert2 and Country dropdown with India." . PHP_EOL;

// Clean up test enquiries
$db->exec("DELETE FROM product_inquiries WHERE id IN ({$testInqId}, {$httpInqId}, {$rfqInqId})");

echo "ALL TESTS PASSED: Task 7 is 100% verified and functional!" . PHP_EOL;
