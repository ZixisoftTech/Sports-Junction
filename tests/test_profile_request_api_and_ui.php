<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/Request.php';
require_once dirname(__DIR__) . '/app/Core/Response.php';
require_once dirname(__DIR__) . '/app/Core/Controller.php';
require_once dirname(__DIR__) . '/app/Helpers/Session.php';
require_once dirname(__DIR__) . '/app/Helpers/AuditLogger.php';
require_once dirname(__DIR__) . '/app/Helpers/CSRF.php';
require_once dirname(__DIR__) . '/app/Models/ProfileChangeRequest.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminProfileChangeController.php';

use App\Models\ProfileChangeRequest;
use App\Controllers\AdminProfileChangeController;

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $message): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$message}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$message}\n";
    }
}

echo "====================================================\n";
echo "PROFILE REQUEST API & ACTION BUTTON VERIFICATION\n";
echo "====================================================\n\n";

// TEST 1: ProfileChangeRequest::findById()
echo "1. Testing ProfileChangeRequest::findById()...\n";
$req1 = ProfileChangeRequest::findById(1);
assertCondition(!empty($req1), "Found profile change request #1");
assertCondition(isset($req1['decoded_current']) && is_array($req1['decoded_current']), "decoded_current is parsed as array");
assertCondition(isset($req1['decoded_requested']) && is_array($req1['decoded_requested']), "decoded_requested is parsed as array");
assertCondition(!empty($req1['supplier_company']) || !empty($req1['user_name']), "supplier_company or user_name is present");

// TEST 2: Controller import in public/index.php
echo "\n2. Testing public/index.php imports...\n";
$indexContent = file_get_contents(dirname(__DIR__) . '/public/index.php');
assertCondition(strpos($indexContent, 'use App\Controllers\AdminProfileChangeController;') !== false, "AdminProfileChangeController is imported in public/index.php");

// TEST 3: Suppliers DataTable column layout
echo "\n3. Testing suppliers.php column layout...\n";
$supView = file_get_contents(dirname(__DIR__) . '/app/Views/admin/suppliers.php');

// company_name should NOT have revBadge inside it
$hasNoBadgeInCompany = (strpos($supView, "let revBadge = row.pending_profile_change_id") === false);
assertCondition($hasNoBadgeInCompany, "Review Changes button is removed from Company Name column");

// actions column should have the Review Changes button
$hasRevInActions = (strpos($supView, "if (row.pending_profile_change_id)") !== false && strpos($supView, "Review Changes") !== false);
assertCondition($hasRevInActions, "Review Changes button is located inside the Actions column");

// TEST 4: Buyers DataTable country badge
echo "\n4. Testing buyers.php country badge & CSS...\n";
$buyerView = file_get_contents(dirname(__DIR__) . '/app/Views/admin/buyers.php');
assertCondition(strpos($buyerView, 'badge-country') !== false, "buyers.php renders .badge-country for country_name");

$cssContent = file_get_contents(dirname(__DIR__) . '/public/assets/css/admin.css');
assertCondition(strpos($cssContent, '.badge-country') !== false, "admin.css contains .badge-country class");
assertCondition(strpos($cssContent, '.text-slate-700') !== false, "admin.css contains .text-slate-700 utility");
assertCondition(strpos($cssContent, '.text-slate-900') !== false, "admin.css contains .text-slate-900 utility");

// TEST 5: API Endpoint execution simulation
echo "\n5. Testing /api/admin/profile-requests/1 route execution...\n";
$cmd = "/Applications/XAMPP/xamppfiles/bin/php -r " . escapeshellarg('
require_once "app/Helpers/Env.php";
\App\Helpers\Env::load(".env");
require_once "app/Helpers/Session.php";
\App\Helpers\Session::start();
\App\Helpers\Session::set("auth_user", ["id" => 1, "role" => "SUPER_ADMIN", "status" => "ACTIVE"]);
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/api/admin/profile-requests/1";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["HTTP_X_REQUESTED_WITH"] = "XMLHttpRequest";
require "public/index.php";
');

$output = shell_exec($cmd . ' 2>&1');
$jsonStart = strpos($output, '{"success"');
$jsonStr = $jsonStart !== false ? substr($output, $jsonStart) : '';
$json = json_decode($jsonStr, true);
assertCondition(is_array($json), "Endpoint returned valid JSON");
assertCondition(($json['success'] ?? false) === true, "Endpoint returned success: true");
assertCondition(($json['data']['id'] ?? 0) === 1, "Endpoint returned request data for ID 1");
assertCondition(!empty($json['data']['supplier_company']), "Endpoint included supplier_company name");

echo "\n====================================================\n";
echo "SUMMARY: Passed: {$passed}, Failed: {$failed}\n";
echo "====================================================\n";

exit($failed > 0 ? 1 : 0);
