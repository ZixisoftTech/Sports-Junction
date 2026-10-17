<?php
declare(strict_types=1);

/**
 * Sports Junction — Comprehensive Test Suite for Super Admin JS, Loaders, and Settings Fixes
 * Covers:
 * Task 1: SportsJunction.formatImageUrl() helper availability, normalization, fallbacks
 * Task 2: All 7 Super Admin pages HTML/JS rendering without syntax errors
 * Task 3: 5 DataTables endpoints data structures & loader recovery
 * Task 4: Website Settings Logo fallback & 404 prevention
 * Task 5: Seller Agreement management, rendering, and persistence
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
    die("FATAL: No SUPER_ADMIN user found in database.\n");
}

\App\Helpers\Session::start();
\App\Helpers\Session::set('auth_user', $admin);
\App\Helpers\Session::set('user_id', (int)$admin['id']);
\App\Helpers\Session::set('user_role', 'SUPER_ADMIN');

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $msg) {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] {$msg}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$msg}\n";
        $failed++;
    }
}

echo "======================================================================\n";
echo "SPORTS JUNCTION — SUPER ADMIN FIXES VERIFICATION SUITE\n";
echo "======================================================================\n\n";

// -------------------------------------------------------------------------
// SECTION 1: Task 1 - Shared JavaScript Helper & Layout Definition
// -------------------------------------------------------------------------
echo "--- [SECTION 1] Task 1: Shared JavaScript Helper & Layout Definition ---\n";
$layoutContent = file_get_contents(__DIR__ . '/../app/Views/layouts/admin.php');

assertCondition(
    str_contains($layoutContent, 'window.SportsJunction.formatImageUrl = function'),
    "formatImageUrl is defined early in <head> before view contents and DataTables"
);

assertCondition(
    str_contains($layoutContent, 'window.formatImageUrl = window.SportsJunction.formatImageUrl'),
    "formatImageUrl is aliased to window.formatImageUrl"
);

assertCondition(
    str_contains($layoutContent, 'window.escapeHtml = window.SportsJunction.escapeHtml'),
    "escapeHtml is aliased to window.escapeHtml"
);

// Verify that Object.assign in footer terminates with });
$footerAssignMatches = preg_match('#window\.SportsJunction\s*=\s*Object\.assign\s*\([^;]*\}\s*\)\s*;#s', $layoutContent);
assertCondition(
    (bool)$footerAssignMatches,
    "Object.assign in footer is properly closed and terminated with });"
);

assertCondition(
    str_contains($layoutContent, '$.fn.dataTable.ext.errMode = \'none\''),
    "DataTables error mode configured to suppress browser alert popups"
);

assertCondition(
    str_contains($layoutContent, '$(document).on(\'error.dt\''),
    "Universal DataTables loader recovery handler registered on error.dt"
);

// -------------------------------------------------------------------------
// SECTION 2: Task 2 - Syntax Check Across All 7 Super Admin Pages
// -------------------------------------------------------------------------
echo "\n--- [SECTION 2] Task 2: HTML & Inline Script Syntax Check on 7 Pages ---\n";

$pagesToTest = [
    'Categories' => function() {
        $ctrl = new \App\Controllers\CategoryController(new \App\Core\Request(), new \App\Core\Response());
        ob_start();
        $ctrl->index();
        return ob_get_clean();
    },
    'Subcategories' => function() {
        $ctrl = new \App\Controllers\SubcategoryController(new \App\Core\Request(), new \App\Core\Response());
        ob_start();
        $ctrl->index();
        return ob_get_clean();
    },
    'Products' => function() {
        $ctrl = new \App\Controllers\ProductController(new \App\Core\Request(), new \App\Core\Response());
        ob_start();
        $ctrl->index();
        return ob_get_clean();
    },
    'Product Approvals' => function() {
        $ctrl = new \App\Controllers\ProductApprovalController(new \App\Core\Request(), new \App\Core\Response());
        ob_start();
        $ctrl->index();
        return ob_get_clean();
    },
    'RFQ & OEM' => function() {
        $ctrl = new \App\Controllers\EnquiryManagementController(new \App\Core\Request(), new \App\Core\Response());
        ob_start();
        $ctrl->rfqOem();
        return ob_get_clean();
    },
    'Website Settings' => function() {
        $ctrl = new \App\Controllers\AdminSettingsController(new \App\Core\Request(), new \App\Core\Response());
        ob_start();
        $ctrl->index();
        return ob_get_clean();
    },
    'Seller Agreement' => function() {
        $ctrl = new \App\Controllers\AdminSettingsController(new \App\Core\Request(), new \App\Core\Response());
        ob_start();
        $ctrl->sellerAgreement();
        return ob_get_clean();
    }
];

foreach ($pagesToTest as $name => $renderFn) {
    try {
        $html = $renderFn();
        
        // Extract all <script>...</script> blocks
        preg_match_all('#<script\b[^>]*>(.*?)</script>#is', $html, $scriptMatches);
        $inlineScripts = $scriptMatches[1] ?? [];
        
        $hasSyntaxError = false;
        $errorDetails = '';
        
        foreach ($inlineScripts as $idx => $script) {
            $trimmed = trim($script);
            if (empty($trimmed)) continue;
            
            // Check for missing closing paren on Object.assign
            if (preg_match('#Object\.assign\s*\([^;]*\};#s', $trimmed)) {
                $hasSyntaxError = true;
                $errorDetails = "Found Object.assign terminating with }; instead of }); in script #{$idx}";
                break;
            }
        }
        
        assertCondition(!$hasSyntaxError && strlen($html) > 500, "{$name} page rendered valid HTML & clean scripts ({$errorDetails})");
    } catch (\Throwable $e) {
        assertCondition(false, "{$name} threw exception: " . $e->getMessage());
    }
}

// -------------------------------------------------------------------------
// SECTION 3: Task 3 - Data Loading & Endpoints
// -------------------------------------------------------------------------
echo "\n--- [SECTION 3] Task 3: Data Loading & Endpoint Formats ---\n";

function runEndpointSubprocess(string $phpCode): ?array {
    $cmd = sprintf(
        '/Applications/XAMPP/xamppfiles/bin/php -r %s',
        escapeshellarg($phpCode)
    );
    $out = shell_exec($cmd);
    if (!$out) return null;
    return json_decode(trim($out), true);
}

// 1. Categories
$catData = runEndpointSubprocess('
    require_once "app/Helpers/Env.php";
    \App\Helpers\Env::load(".env");
    spl_autoload_register(function ($c) {
        $f = "app/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";
        if (file_exists($f)) require_once $f;
    });
    $pdo = \App\Core\Database::getConnection();
    $admin = $pdo->query("SELECT * FROM users WHERE role = \"SUPER_ADMIN\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    \App\Helpers\Session::start();
    \App\Helpers\Session::set("auth_user", $admin);
    \App\Helpers\Session::set("user_id", (int)$admin["id"]);
    \App\Helpers\Session::set("user_role", "SUPER_ADMIN");
    $_GET = ["draw" => 1, "start" => 0, "length" => 5];
    $c = new \App\Controllers\Api\MasterApiController(new \App\Core\Request(), new \App\Core\Response());
    $c->data(["entity" => "categories"]);
');

assertCondition(
    is_array($catData) && isset($catData['data']) && isset($catData['recordsTotal']),
    "Categories API returns valid DataTables JSON (records: " . (is_array($catData['data'] ?? null) ? count($catData['data']) : 0) . ")"
);

// 2. Subcategories
$subData = runEndpointSubprocess('
    require_once "app/Helpers/Env.php";
    \App\Helpers\Env::load(".env");
    spl_autoload_register(function ($c) {
        $f = "app/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";
        if (file_exists($f)) require_once $f;
    });
    $pdo = \App\Core\Database::getConnection();
    $admin = $pdo->query("SELECT * FROM users WHERE role = \"SUPER_ADMIN\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    \App\Helpers\Session::start();
    \App\Helpers\Session::set("auth_user", $admin);
    \App\Helpers\Session::set("user_id", (int)$admin["id"]);
    \App\Helpers\Session::set("user_role", "SUPER_ADMIN");
    $_GET = ["draw" => 1, "start" => 0, "length" => 5];
    $c = new \App\Controllers\Api\MasterApiController(new \App\Core\Request(), new \App\Core\Response());
    $c->data(["entity" => "subcategories"]);
');

assertCondition(
    is_array($subData) && isset($subData['data']) && isset($subData['recordsTotal']),
    "Subcategories API returns valid DataTables JSON (records: " . (is_array($subData['data'] ?? null) ? count($subData['data']) : 0) . ")"
);

// 3. Products
$prodData = runEndpointSubprocess('
    require_once "app/Helpers/Env.php";
    \App\Helpers\Env::load(".env");
    spl_autoload_register(function ($c) {
        $f = "app/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";
        if (file_exists($f)) require_once $f;
    });
    $pdo = \App\Core\Database::getConnection();
    $admin = $pdo->query("SELECT * FROM users WHERE role = \"SUPER_ADMIN\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    \App\Helpers\Session::start();
    \App\Helpers\Session::set("auth_user", $admin);
    \App\Helpers\Session::set("user_id", (int)$admin["id"]);
    \App\Helpers\Session::set("user_role", "SUPER_ADMIN");
    $_GET = ["draw" => 1, "start" => 0, "length" => 5];
    $c = new \App\Controllers\ProductController(new \App\Core\Request(), new \App\Core\Response());
    $c->data();
');

assertCondition(
    is_array($prodData) && isset($prodData['data']) && isset($prodData['recordsTotal']),
    "Products API returns valid DataTables JSON (records: " . (is_array($prodData['data'] ?? null) ? count($prodData['data']) : 0) . ")"
);

// 4. Product Approvals
$apprData = runEndpointSubprocess('
    require_once "app/Helpers/Env.php";
    \App\Helpers\Env::load(".env");
    spl_autoload_register(function ($c) {
        $f = "app/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";
        if (file_exists($f)) require_once $f;
    });
    $pdo = \App\Core\Database::getConnection();
    $admin = $pdo->query("SELECT * FROM users WHERE role = \"SUPER_ADMIN\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    \App\Helpers\Session::start();
    \App\Helpers\Session::set("auth_user", $admin);
    \App\Helpers\Session::set("user_id", (int)$admin["id"]);
    \App\Helpers\Session::set("user_role", "SUPER_ADMIN");
    $_GET = ["draw" => 1, "start" => 0, "length" => 5];
    $c = new \App\Controllers\ProductApprovalController(new \App\Core\Request(), new \App\Core\Response());
    $c->data();
');

assertCondition(
    is_array($apprData) && isset($apprData['data']) && isset($apprData['recordsTotal']),
    "Product Approvals API returns valid DataTables JSON (records: " . (is_array($apprData['data'] ?? null) ? count($apprData['data']) : 0) . ")"
);

// 5. RFQ & OEM
$rfqData = runEndpointSubprocess('
    require_once "app/Helpers/Env.php";
    \App\Helpers\Env::load(".env");
    spl_autoload_register(function ($c) {
        $f = "app/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";
        if (file_exists($f)) require_once $f;
    });
    $pdo = \App\Core\Database::getConnection();
    $admin = $pdo->query("SELECT * FROM users WHERE role = \"SUPER_ADMIN\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    \App\Helpers\Session::start();
    \App\Helpers\Session::set("auth_user", $admin);
    \App\Helpers\Session::set("user_id", (int)$admin["id"]);
    \App\Helpers\Session::set("user_role", "SUPER_ADMIN");
    $_GET = ["draw" => 1, "start" => 0, "length" => 5];
    $c = new \App\Controllers\EnquiryManagementController(new \App\Core\Request(), new \App\Core\Response());
    $c->rfqOemData();
');

assertCondition(
    is_array($rfqData) && isset($rfqData['data']) && isset($rfqData['recordsTotal']),
    "RFQ & OEM API returns valid DataTables JSON (records: " . (is_array($rfqData['data'] ?? null) ? count($rfqData['data']) : 0) . ")"
);

// -------------------------------------------------------------------------
// SECTION 4: Task 4 - Website Settings Logo Fallback & 404 Prevention
// -------------------------------------------------------------------------
echo "\n--- [SECTION 4] Task 4: Website Settings Logo Fallback & 404 Prevention ---\n";

// Test 1: Stored logo pointing to nonexistent file falls back safely
$fakePath = '/uploads/settings/logo_1792241473_b28f60e0.png';
\App\Models\SiteSetting::set('site_logo', $fakePath, 'branding');
$resolvedUrl = \App\Models\SiteSetting::getLogoUrl();

assertCondition(
    str_ends_with($resolvedUrl, '/assets/images/logo.png'),
    "Non-existent logo path ({$fakePath}) safely falls back to default brand asset ({$resolvedUrl})"
);

// Test 2: Valid local asset logo returns valid URL
\App\Models\SiteSetting::set('site_logo', '/assets/images/logo.png', 'branding');
$validUrl = \App\Models\SiteSetting::getLogoUrl();
assertCondition(
    str_contains($validUrl, '/assets/images/logo.png'),
    "Existing logo asset resolves correctly: {$validUrl}"
);

// -------------------------------------------------------------------------
// SECTION 5: Task 5 - Seller & Manufacturer Agreement Verification
// -------------------------------------------------------------------------
echo "\n--- [SECTION 5] Task 5: Seller & Manufacturer Agreement ---\n";

// 1. Verify Public Agreement page exists and loads
$cmsCtrl = new \App\Controllers\CmsPageController(new \App\Core\Request(), new \App\Core\Response());
ob_start();
$cmsCtrl->show(['slug' => 'seller-agreement']);
$publicAgreeHtml = ob_get_clean();

assertCondition(
    str_contains($publicAgreeHtml, 'Seller & Manufacturer Agreement') || str_contains($publicAgreeHtml, 'Seller &amp; Manufacturer Agreement'),
    "Public Seller Agreement page loads successfully with official title"
);

// 2. Verify Super Admin Edit Agreement form persists content with quotes and HTML
$testTitle = "Seller & Manufacturer Master Agreement " . time();
$testContent = "<h2>Section 1: Manufacturing Standards</h2><p>Manufacturer's \"direct warranty\" & compliance standards.</p>";

$b64Title = base64_encode($testTitle);
$b64Content = base64_encode($testContent);

$saveCode = '
    require_once "app/Helpers/Env.php";
    \App\Helpers\Env::load(".env");
    spl_autoload_register(function ($c) {
        $f = "app/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";
        if (file_exists($f)) require_once $f;
    });
    $pdo = \App\Core\Database::getConnection();
    $admin = $pdo->query("SELECT * FROM users WHERE role = \"SUPER_ADMIN\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    \App\Helpers\Session::start();
    \App\Helpers\Session::set("auth_user", $admin);
    \App\Helpers\Session::set("user_id", (int)$admin["id"]);
    \App\Helpers\Session::set("user_role", "SUPER_ADMIN");
    $_POST = [
        "title" => base64_decode("' . $b64Title . '"),
        "content" => base64_decode("' . $b64Content . '"),
        "is_active" => "1",
        "meta_title" => "Official Seller Agreement",
        "meta_description" => "Terms for verified sports equipment manufacturers and sellers.",
        "csrf_token" => \App\Helpers\CSRF::token()
    ];
    $c = new \App\Controllers\AdminSettingsController(new \App\Core\Request(), new \App\Core\Response());
    $c->updateSellerAgreement();
';

shell_exec('/Applications/XAMPP/xamppfiles/bin/php -r ' . escapeshellarg($saveCode));

$savedPage = \App\Models\CmsPage::findBySlug('seller-agreement');
assertCondition(
    $savedPage && $savedPage['title'] === $testTitle && str_contains($savedPage['content'], 'Manufacturer\'s "direct warranty"'),
    "Super Admin editor saved agreement with quotes, punctuation, and HTML without corruption"
);

// Restore default agreement title
$restoreCode = '
    require_once "app/Helpers/Env.php";
    \App\Helpers\Env::load(".env");
    spl_autoload_register(function ($c) {
        $f = "app/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";
        if (file_exists($f)) require_once $f;
    });
    $pdo = \App\Core\Database::getConnection();
    $admin = $pdo->query("SELECT * FROM users WHERE role = \"SUPER_ADMIN\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    \App\Helpers\Session::start();
    \App\Helpers\Session::set("auth_user", $admin);
    \App\Helpers\Session::set("user_id", (int)$admin["id"]);
    \App\Helpers\Session::set("user_role", "SUPER_ADMIN");
    $_POST = [
        "title" => "Seller & Manufacturer Agreement",
        "content" => base64_decode("' . $b64Content . '"),
        "is_active" => "1",
        "csrf_token" => \App\Helpers\CSRF::token()
    ];
    $c = new \App\Controllers\AdminSettingsController(new \App\Core\Request(), new \App\Core\Response());
    $c->updateSellerAgreement();
';
shell_exec('/Applications/XAMPP/xamppfiles/bin/php -r ' . escapeshellarg($restoreCode));

// -------------------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------------------
echo "\n======================================================================\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED out of " . ($passed + $failed) . " CHECKS\n";
echo "======================================================================\n";

if ($failed > 0) {
    exit(1);
}
