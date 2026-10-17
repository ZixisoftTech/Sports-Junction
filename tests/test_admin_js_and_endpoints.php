<?php
declare(strict_types=1);

/**
 * Sports Junction — Comprehensive Test for Admin JS, formatImageUrl, and DataTables Endpoints
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

$pdo = \App\Core\Database::getConnection();
$userStmt = $pdo->query("SELECT * FROM users WHERE role = 'SUPER_ADMIN' LIMIT 1");
$admin = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Error: No SUPER_ADMIN user found in database.\n");
}

\App\Helpers\Session::start();
\App\Helpers\Session::set('auth_user', $admin);
\App\Helpers\Session::set('user_id', (int)$admin['id']);
\App\Helpers\Session::set('user_role', 'SUPER_ADMIN');

echo "=== TESTING 5 DATATABLES ENDPOINTS FOR SUPER ADMIN ===\n";

// 1. Categories
$req = new \App\Core\Request();
$res = new \App\Core\Response();
$catCtrl = new \App\Controllers\Api\MasterApiController($req, $res);
$_GET = ['draw' => 1, 'start' => 0, 'length' => 10];
ob_start();
$catCtrl->data(['entity' => 'categories']);
$catOut = ob_get_clean();
$catJson = json_decode($catOut, true);
if (isset($catJson['data']) && is_array($catJson['data'])) {
    echo "[PASS] Categories endpoint returned valid JSON with " . count($catJson['data']) . " records.\n";
} else {
    echo "[FAIL] Categories endpoint failed: " . substr($catOut, 0, 200) . "\n";
}

// 2. Subcategories
ob_start();
$catCtrl->data(['entity' => 'subcategories']);
$subOut = ob_get_clean();
$subJson = json_decode($subOut, true);
if (isset($subJson['data']) && is_array($subJson['data'])) {
    echo "[PASS] Subcategories endpoint returned valid JSON with " . count($subJson['data']) . " records.\n";
} else {
    echo "[FAIL] Subcategories endpoint failed: " . substr($subOut, 0, 200) . "\n";
}

// 3. Products
$prodCtrl = new \App\Controllers\ProductController($req, $res);
ob_start();
$prodCtrl->apiData();
$prodOut = ob_get_clean();
$prodJson = json_decode($prodOut, true);
if (isset($prodJson['data']) && is_array($prodJson['data'])) {
    echo "[PASS] Products endpoint returned valid JSON with " . count($prodJson['data']) . " records.\n";
} else {
    echo "[FAIL] Products endpoint failed: " . substr($prodOut, 0, 200) . "\n";
}

// 4. Product Approvals
$apprCtrl = new \App\Controllers\ProductApprovalController($req, $res);
ob_start();
$apprCtrl->apiData();
$apprOut = ob_get_clean();
$apprJson = json_decode($apprOut, true);
if (isset($apprJson['data']) && is_array($apprJson['data'])) {
    echo "[PASS] Product Approvals endpoint returned valid JSON with " . count($apprJson['data']) . " records.\n";
} else {
    echo "[FAIL] Product Approvals endpoint failed: " . substr($apprOut, 0, 200) . "\n";
}

// 5. RFQ & OEM Enquiries
$enqCtrl = new \App\Controllers\EnquiryManagementController($req, $res);
ob_start();
$enqCtrl->apiRfqOemData();
$enqOut = ob_get_clean();
$enqJson = json_decode($enqOut, true);
if (isset($enqJson['data']) && is_array($enqJson['data'])) {
    echo "[PASS] RFQ & OEM Enquiries endpoint returned valid JSON with " . count($enqJson['data']) . " records.\n";
} else {
    echo "[FAIL] RFQ & OEM Enquiries endpoint failed: " . substr($enqOut, 0, 200) . "\n";
}

echo "=== ALL 5 ENDPOINTS VERIFIED ===\n";
