<?php
declare(strict_types=1);

/**
 * Sports Junction — Web Endpoints & Controller Integration Test
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

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Session;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\BusinessType;
use App\Models\PriceUnit;
use App\Models\MoqUnit;

echo "--- Web & Controller Integration Test ---\n";

// 1. Test Login View Rendering
ob_start();
$request = new Request();
$response = new Response();
$authCtrl = new \App\Controllers\AuthController($request, $response);
$authCtrl->showLogin();
$loginHtml = ob_get_clean();

assert(str_contains($loginHtml, 'B2B Portal') || str_contains($loginHtml, 'Welcome Back'), 'Login view must contain portal heading');
assert(str_contains($loginHtml, 'name="email"'), 'Login view must contain email field');
assert(str_contains($loginHtml, 'name="password"'), 'Login view must contain password field');
assert(str_contains($loginHtml, 'name="remember"'), 'Login view must contain remember checkbox');
assert(str_contains($loginHtml, 'Sports Junction'), 'Login view must render brand title');
echo "  [PASS] Login Page renders correctly with Stitch B2B Portal design and required fields\n";

// 2. Test Dashboard View Rendering with Super Admin Session
Session::set('auth_user', [
    'id'    => 1,
    'name'  => 'Super Administrator',
    'email' => 'sportjunctions@gmail.com',
    'role'  => 'SUPER_ADMIN'
]);

ob_start();
$dashCtrl = new \App\Controllers\DashboardController($request, $response);
$dashCtrl->index();
$dashHtml = ob_get_clean();

assert(str_contains($dashHtml, 'Master Management'), 'Dashboard must contain Master Management section');
assert(str_contains($dashHtml, 'Countries'), 'Dashboard must list Countries metric');
assert(str_contains($dashHtml, 'States'), 'Dashboard must list States metric');
assert(str_contains($dashHtml, 'Cities'), 'Dashboard must list Cities metric');
assert(str_contains($dashHtml, 'Business Types'), 'Dashboard must list Business Types metric');
assert(str_contains($dashHtml, 'Price Units'), 'Dashboard must list Price Units metric');
assert(str_contains($dashHtml, 'MOQ Units'), 'Dashboard must list MOQ Units metric');
assert(str_contains($dashHtml, 'Audit Trail'), 'Dashboard must render Audit Trail table');
echo "  [PASS] Admin Dashboard renders metrics, protected entity overview, and audit trail\n";

// 3. Test Master Views Rendering
$masterControllers = [
    'countries'      => [\App\Controllers\CountryController::class, 'Country Management'],
    'states'         => [\App\Controllers\StateController::class, 'State Management'],
    'cities'         => [\App\Controllers\CityController::class, 'City Management'],
    'business_types' => [\App\Controllers\BusinessTypeController::class, 'Business Type Management'],
    'price_units'    => [\App\Controllers\PriceUnitController::class, 'Price Unit Management'],
    'moq_units'      => [\App\Controllers\MoqUnitController::class, 'MOQ Unit Management'],
];

foreach ($masterControllers as $key => [$ctrlClass, $expectedTitle]) {
    ob_start();
    $c = new $ctrlClass($request, $response);
    $c->index();
    $html = ob_get_clean();
    assert(str_contains($html, $expectedTitle), "{$key} view must contain title {$expectedTitle}");
    assert(str_contains($html, 'MasterModule.init'), "{$key} view must initialize MasterModule");
    assert(str_contains($html, 'Export'), "{$key} view must provide Export button");
    echo "  [PASS] {$expectedTitle} view renders successfully with DataTables and Export controls\n";
}

echo "\n[✓] All web views and controllers validated successfully!\n";
