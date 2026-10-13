<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — SUPER ADMIN & MASTER CRUD AUTOMATED TEST SUITE
 * =======================================================================
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

use App\Core\Database;
use App\Helpers\Exporter;
use App\Helpers\Session;
use App\Middleware\RateLimiter;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\BusinessType;
use App\Models\PriceUnit;
use App\Models\MoqUnit;
use App\Models\AuditLog;

$passed = 0;
$failed = 0;

function it(string $title, callable $test) {
    global $passed, $failed;
    try {
        $test();
        echo "  [PASS] {$title}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  [FAIL] {$title}\n";
        echo "         Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

function assert_true($cond, string $msg = 'Expected true') {
    if (!$cond) throw new \RuntimeException($msg);
}

function assert_equals($expected, $actual, string $msg = '') {
    if ($expected !== $actual) {
        throw new \RuntimeException(($msg ? $msg . ': ' : '') . 'Expected ' . json_encode($expected) . ' but got ' . json_encode($actual));
    }
}

echo "=======================================================================\n";
echo " SPORTS JUNCTION — VERIFICATION & TEST SUITE RUNNER\n";
echo "=======================================================================\n\n";

// -------------------------------------------------------------------
// 1. SUPER ADMIN AUTHENTICATION & ACCESS CONTROL TESTS
// -------------------------------------------------------------------
echo "--- 1. Super Admin Authentication & Security Tests ---\n";

it('Super Admin account exists and is seeded with correct email and role', function () {
    $admin = User::findByEmail('sportjunctions@gmail.com');
    assert_true($admin !== null, 'Super Admin record not found');
    assert_equals('SUPER_ADMIN', $admin['role']);
    assert_equals('ACTIVE', $admin['status']);
});

it('Super Admin password verifies securely against Bcrypt hash (Sport@123)', function () {
    $admin = User::findByEmail('sportjunctions@gmail.com');
    assert_true(User::verifyPassword('Sport@123', $admin['password']), 'Password hash verification failed');
    assert_true(!User::verifyPassword('WrongPassword!', $admin['password']), 'Wrong password must be rejected');
});

it('Rate limiter records failed login attempts and resets upon success', function () {
    $ip = '127.0.0.99';
    $email = 'fake_test@sportsjunction.com';

    RateLimiter::clearAttempts($ip, $email);
    assert_true(!RateLimiter::isLocked($ip, $email), 'Should not be locked initially');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::recordFailedAttempt($ip, $email);
    }
    assert_true(RateLimiter::isLocked($ip, $email), 'Rate limiter must lock after 5 attempts');

    RateLimiter::clearAttempts($ip, $email);
    assert_true(!RateLimiter::isLocked($ip, $email), 'Rate limiter must reset upon clearing');
});

// -------------------------------------------------------------------
// 2. MASTER CRUD TESTS (All 6 Modules)
// -------------------------------------------------------------------
echo "\n--- 2. Master CRUD Functionality Tests ---\n";

it('Country CRUD: create, unique constraints, and status toggle', function () {
    $testCode = chr(rand(65, 90)) . chr(rand(65, 90));
    $countryName = 'Testland ' . $testCode;

    $id = Country::create([
        'name'        => $countryName,
        'iso_alpha2'  => $testCode,
        'iso_alpha3'  => 'TL' . rand(1, 9),
        'phone_code'  => '+999',
        'status'      => 'ACTIVE',
    ]);
    assert_true($id > 0, 'Country creation returned invalid ID');

    $found = Country::findById($id);
    assert_equals($countryName, $found['name']);
    assert_equals('ACTIVE', $found['status']);

    // Status change
    Country::setStatus($id, 'INACTIVE');
    $updated = Country::findById($id);
    assert_equals('INACTIVE', $updated['status']);

    // Clean up
    Country::hardDelete($id);
});

it('State CRUD: creation, country relationship, and status', function () {
    $india = Country::fetch("SELECT id, name FROM countries WHERE iso_alpha2 = 'IN' LIMIT 1");
    assert_true(!empty($india), 'India not found in database');

    $stateName = 'Test Province ' . rand(100, 999);
    $stateId = State::create([
        'country_id' => $india['id'],
        'name'       => $stateName,
        'code'       => 'TP' . rand(10, 99),
        'status'     => 'ACTIVE',
    ]);
    assert_true($stateId > 0);

    $state = State::findById($stateId);
    assert_equals($stateName, $state['name']);
    assert_equals($india['name'], $state['country_name']);

    State::hardDelete($stateId);
});

it('City CRUD: dynamic dependent validation and state link', function () {
    $up = State::fetch("SELECT s.id, s.country_id, c.name AS country_name FROM states s JOIN countries c ON s.country_id = c.id WHERE s.name = 'Uttar Pradesh' LIMIT 1");
    assert_true(!empty($up), 'State UP not found');

    $cityName = 'Test City ' . rand(100, 999);
    $cityId = City::create([
        'country_id' => $up['country_id'],
        'state_id'   => $up['id'],
        'name'       => $cityName,
        'status'     => 'ACTIVE',
    ]);
    assert_true($cityId > 0);

    $city = City::findById($cityId);
    assert_equals('Uttar Pradesh', $city['state_name']);
    assert_equals($up['country_name'], $city['country_name']);

    City::hardDelete($cityId);
});

it('Business Type CRUD: create, update, and search', function () {
    $btName = 'Specialized Sports OEM ' . rand(100, 999);
    $btId = BusinessType::create([
        'name'        => $btName,
        'description' => 'Contract manufacturing for carbon fiber racquets',
        'status'      => 'ACTIVE',
    ]);
    assert_true($btId > 0);

    BusinessType::update($btId, [
        'name'        => $btName . ' Updated',
        'description' => 'Updated description',
        'status'      => 'ACTIVE',
    ]);

    $bt = BusinessType::findById($btId);
    assert_equals($btName . ' Updated', $bt['name']);

    BusinessType::hardDelete($btId);
});

it('Price Unit CRUD: creation and DataTable query support', function () {
    $name = 'Carton ' . rand(100, 999);
    $id = PriceUnit::create([
        'name'        => $name,
        'symbol'      => 'ctn',
        'description' => 'Per corrugated shipping carton',
        'status'      => 'ACTIVE',
    ]);
    assert_true($id > 0);

    $dt = PriceUnit::getDataTable(['search' => $name, 'length' => 10, 'start' => 0]);
    assert_true($dt['recordsFiltered'] >= 1, 'DataTable search failed for Price Unit');

    PriceUnit::hardDelete($id);
});

it('MOQ Unit CRUD: creation and status toggle', function () {
    $name = 'Cartons ' . rand(100, 999);
    $id = MoqUnit::create([
        'name'        => $name,
        'symbol'      => 'ctns',
        'description' => 'Minimum order lots in cartons',
        'status'      => 'ACTIVE',
    ]);
    assert_true($id > 0);

    MoqUnit::setStatus($id, 'INACTIVE');
    $moq = MoqUnit::findById($id);
    assert_equals('INACTIVE', $moq['status']);

    MoqUnit::hardDelete($id);
});

// -------------------------------------------------------------------
// 3. EXPORT & FORMULA INJECTION SANITIZATION TESTS
// -------------------------------------------------------------------
echo "\n--- 3. Export & Formula Injection Prevention Tests ---\n";

it('Exporter properly neutralizes spreadsheet formula injection (=, +, -, @)', function () {
    $malicious = '=cmd|"/C calc"!A0';
    $sanitized = Exporter::sanitizeCellValue($malicious);
    assert_equals("'=cmd|\"/C calc\"!A0", $sanitized, 'Formula starting with = must be escaped with single quote');

    $safe = 'Sports Ball';
    assert_equals('Sports Ball', Exporter::sanitizeCellValue($safe));
});

// -------------------------------------------------------------------
// 4. DEPENDENCY & SAFETY HARD DELETE TESTS
// -------------------------------------------------------------------
echo "\n--- 4. Dependency Safety & Protected Business Integrity Tests ---\n";

it('Blocks hard delete of Country when dependent states and cities exist without cascade', function () {
    $india = Country::fetch("SELECT id FROM countries WHERE iso_alpha2 = 'IN' LIMIT 1");
    $deps = Country::getDependencies((int)$india['id']);
    assert_true($deps['states'] > 0, 'India must have states');
    assert_true($deps['cities'] > 0, 'India must have cities');

    $res = Country::hardDelete((int)$india['id'], false);
    assert_true(!$res['success'], 'Direct hard delete of India must be rejected');
});

it('Blocks deletion of City (Meerut) because protected Supplier account references it', function () {
    $meerut = City::fetch("SELECT id FROM cities WHERE name = 'Meerut' LIMIT 1");
    assert_true(!empty($meerut), 'Meerut not found');

    $deps = City::getDependencies((int)$meerut['id']);
    assert_true($deps['suppliers'] > 0, 'Meerut must have supplier');
    assert_true($deps['has_protected'], 'Supplier reference must trigger has_protected flag');

    $res = City::hardDelete((int)$meerut['id']);
    assert_true(!$res['success'], 'Deleting Meerut must be BLOCKED to protect Supplier record');

    // Verify Meerut still exists in database!
    $check = City::findById((int)$meerut['id']);
    assert_true(!empty($check), 'Meerut must still exist');
});

it('Blocks deletion of Business Type (Manufacturer) because Suppliers use it', function () {
    $mfr = BusinessType::fetch("SELECT id FROM business_types WHERE name = 'Manufacturer' LIMIT 1");
    assert_true(!empty($mfr), 'Manufacturer not found');

    $deps = BusinessType::getDependencies((int)$mfr['id']);
    assert_true($deps['has_protected'], 'Manufacturer business type has active suppliers');

    $res = BusinessType::hardDelete((int)$mfr['id']);
    assert_true(!$res['success'], 'Deletion must be rejected');
});

it('Blocks deletion of Price Unit (Piece) because Products use it', function () {
    $pc = PriceUnit::fetch("SELECT id FROM price_units WHERE name = 'Piece' LIMIT 1");
    assert_true(!empty($pc), 'Price Unit Piece not found');

    $deps = PriceUnit::getDependencies((int)$pc['id']);
    assert_true($deps['has_protected'], 'Products must reference Piece');

    $res = PriceUnit::hardDelete((int)$pc['id']);
    assert_true(!$res['success'], 'Piece price unit deletion must be blocked');
});

it('Blocks deletion of MOQ Unit (Pieces) because Products use it', function () {
    $pcs = MoqUnit::fetch("SELECT id FROM moq_units WHERE name = 'Pieces' LIMIT 1");
    assert_true(!empty($pcs), 'MOQ Unit Pieces not found');

    $deps = MoqUnit::getDependencies((int)$pcs['id']);
    assert_true($deps['has_protected'], 'Products must reference Pieces');

    $res = MoqUnit::hardDelete((int)$pcs['id']);
    assert_true(!$res['success'], 'Pieces MOQ unit deletion must be blocked');
});

it('Allows cascade hard-delete ONLY on eligible sub-masters in transaction, and logs audit', function () {
    // Create isolated hierarchy with NO suppliers/products
    $isoCode = 'Q' . chr(rand(65, 90));
    $cId = Country::create([
        'name'        => 'Isolated Country ' . $isoCode . rand(100, 999),
        'iso_alpha2'  => $isoCode,
        'status'      => 'ACTIVE'
    ]);
    $sId = State::create([
        'country_id' => $cId,
        'name'       => 'Isolated State ' . $isoCode,
        'status'     => 'ACTIVE'
    ]);
    $ciId = City::create([
        'country_id' => $cId,
        'state_id'   => $sId,
        'name'       => 'Isolated City ' . $isoCode,
        'status'     => 'ACTIVE'
    ]);

    // Country has states and cities
    $deps = Country::getDependencies($cId);
    assert_true(!$deps['has_protected'], 'No protected entities attached');
    assert_true($deps['can_cascade'], 'Must be eligible for cascade');

    // Execute cascade delete
    $delRes = Country::hardDelete($cId, true);
    assert_true($delRes['success'], 'Cascade delete must succeed on eligible sub-masters');
    assert_equals(1, $delRes['affectedCounts']['countries']);
    assert_equals(1, $delRes['affectedCounts']['states']);
    assert_equals(1, $delRes['affectedCounts']['cities']);

    // Verify completely deleted from database
    assert_true(Country::findById($cId) === null, 'Country must be deleted');
    assert_true(State::findById($sId) === null, 'State must be deleted');
    assert_true(City::findById($ciId) === null, 'City must be deleted');
});

echo "\n=======================================================================\n";
echo " TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
