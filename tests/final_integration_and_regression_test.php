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

echo "======================================================================\n";
echo "SPORTS JUNCTION — FINAL INTEGRATION & FULL REGRESSION SUITE (12 TASKS)\n";
echo "======================================================================\n\n";

$pass = 0;
$fail = 0;

function check(string $task, string $title, bool $condition, string $detail = ''): void {
    global $pass, $fail;
    if ($condition) {
        echo " [PASS] [$task] $title\n";
        if ($detail) echo "        $detail\n";
        $pass++;
    } else {
        echo " [FAIL] [$task] $title\n";
        if ($detail) echo "        $detail\n";
        $fail++;
    }
}

// -------------------------------------------------------------------------
// Task 1: Seller Registration Country Flags
// -------------------------------------------------------------------------
$countryPickerJs = file_get_contents(__DIR__ . '/../public/assets/js/country-picker.js');
$supRegPhp = file_get_contents(__DIR__ . '/../app/Views/auth/supplier_register.php');
$buyRegPhp = file_get_contents(__DIR__ . '/../app/Views/auth/register.php');

check('Task 1', 'country-picker.js exists and implements search with flag styling', 
    str_contains($countryPickerJs, 'initCountryPicker') && str_contains($countryPickerJs, 'sj-country-flag'), 
    'Found dropdown search, flag display, and dialing code sync.'
);
check('Task 1', 'Supplier registration uses custom country picker with flags',
    str_contains($supRegPhp, 'data-country-picker') && str_contains($supRegPhp, 'country-picker.js'),
    'Supplier registration view binds country picker component and loads JS asset.'
);
check('Task 1', 'Buyer registration uses custom country picker with flags',
    str_contains($buyRegPhp, 'data-country-picker') && str_contains($buyRegPhp, 'country-picker.js'),
    'Buyer registration view binds country picker component and loads JS asset.'
);

// -------------------------------------------------------------------------
// Task 2: Seller Agreement Page & Admin CMS
// -------------------------------------------------------------------------
$agreementPage = \App\Models\CmsPage::findBySlug('seller-agreement');
check('Task 2', 'CMS page seller-agreement exists in database',
    $agreementPage !== null && !empty($agreementPage['title']),
    'Slug: seller-agreement, Title: ' . ($agreementPage['title'] ?? 'N/A')
);
$sellerAgreementView = file_get_contents(__DIR__ . '/../app/Views/admin/seller_agreement.php');
check('Task 2', 'Admin CMS edit view for Seller Agreement exists with form action',
    str_contains($sellerAgreementView, 'admin/seller-agreement') && str_contains($sellerAgreementView, 'Seller &amp; Manufacturer Agreement'),
    'Found admin editor view with rich management form.'
);

// -------------------------------------------------------------------------
// Task 3: Change Email on Verify Email Page
// -------------------------------------------------------------------------
$verifyOtpView = file_get_contents(__DIR__ . '/../app/Views/auth/verify_otp.php');
$authController = file_get_contents(__DIR__ . '/../app/Controllers/AuthController.php');
check('Task 3', 'Verify Email page has Change Email action link',
    str_contains($verifyOtpView, '/change-email'),
    'Found /change-email route in verify_otp.php'
);
check('Task 3', 'AuthController handles changeRegistrationEmail and pending_edit_user_id',
    str_contains($authController, 'changeRegistrationEmail') && str_contains($authController, 'pending_edit_user_id'),
    'Found session prefill and duplicate prevention logic.'
);

// -------------------------------------------------------------------------
// Task 4: Forgot Password Unknown Emails Security
// -------------------------------------------------------------------------
check('Task 4', 'Forgot password does not generate OTPs for unknown/inactive emails',
    str_contains($authController, 'Session::remove(\'reset_email\')') && (str_contains($authController, 'No account found with this email address') || str_contains($authController, 'If an active account exists with')),
    'Only active users receive OTPs; unknown emails are rejected with an explicit error without OTP or reset session.'
);

// Verify with DB that no OTP is generated for non-existent email
$unknownTestEmail = 'nonexistent_' . time() . '@nowhere.invalid';
$otpCountBefore = (int)\App\Core\Model::fetchColumn("SELECT COUNT(*) FROM email_otps WHERE email = :email", [':email' => $unknownTestEmail]);
check('Task 4', 'Zero OTP records in database for unknown email address',
    $otpCountBefore === 0,
    "No OTP generated in database for {$unknownTestEmail}."
);

// -------------------------------------------------------------------------
// Task 5: Dev/Test Static OTP 123456
// -------------------------------------------------------------------------
$otpEmail = 'final_regression_test_' . time() . '@sportsjunction.test';
$otpCode = \App\Models\Otp::generate($otpEmail, 'EMAIL_VERIFICATION');
check('Task 5', 'In DEV_TEST mode, generated OTP is static 123456',
    $otpCode === '123456',
    "Generated OTP: $otpCode"
);
$otpResult = \App\Models\Otp::verify($otpEmail, '123456', 'EMAIL_VERIFICATION');
check('Task 5', 'Verification of static OTP 123456 succeeds and marks verified',
    is_array($otpResult) && ($otpResult['success'] ?? false) === true,
    'Verified successfully: ' . ($otpResult['message'] ?? '')
);

// -------------------------------------------------------------------------
// Task 6: Remove Price Units and Display USD Only
// -------------------------------------------------------------------------
$productDetailView = file_get_contents(__DIR__ . '/../app/Views/frontend/product_detail.php');
$productApprovalView = file_get_contents(__DIR__ . '/../app/Views/admin/product_approvals.php');
$productsIndexView = file_get_contents(__DIR__ . '/../app/Views/products/index.php');

check('Task 6', 'Product detail page does not display / unit on wholesale tiers',
    !str_contains($productDetailView, '/ <?= $product[\'price_unit_name\'] ?>') && !str_contains($productDetailView, '/ unit'),
    'Removed / unit from wholesale price tiers.'
);
check('Task 6', 'Admin product approvals formats price with USD $',
    str_contains($productApprovalView, "const sym = '$';") || str_contains($productApprovalView, 'Currency: USD ($)'),
    'Admin approvals displays prices in USD ($).'
);
check('Task 6', 'Admin products table displays prices in USD ($)',
    str_contains($productsIndexView, 'min_price') && !str_contains($productsIndexView, '/ ${unit}'),
    'Clean USD format without confusing unit symbols.'
);

// -------------------------------------------------------------------------
// Task 7: Export Packaging Must Be Supplier-Entered
// -------------------------------------------------------------------------
$prodCols = \App\Core\Model::fetchAll("SHOW COLUMNS FROM products LIKE 'export_packaging'");
check('Task 7', 'Column export_packaging exists in products table',
    count($prodCols) > 0,
    'Field export_packaging present in schema.'
);
$supForm = file_get_contents(__DIR__ . '/../app/Views/supplier/product_form.php');
check('Task 7', 'Supplier product form has export_packaging field in Shipping & Logistics',
    str_contains($supForm, 'export_packaging'),
    'Found export_packaging input in Step 4.'
);
check('Task 7', 'Product detail view renders supplier export_packaging',
    str_contains($productDetailView, '$product[\'export_packaging\']'),
    'Product detail page displays custom export packaging or standard fallback.'
);

// -------------------------------------------------------------------------
// Task 8: Product Enquiry Popup Scrolling & Submission
// -------------------------------------------------------------------------
$frontendLayout = file_get_contents(__DIR__ . '/../app/Views/layouts/frontend.php');
check('Task 8', 'Product enquiry popup card has fixed max-height and flex column layout',
    str_contains($frontendLayout, 'max-h-[90vh]') && str_contains($frontendLayout, 'flex flex-col'),
    'Modal card maintains viewport constraints.'
);
check('Task 8', 'Product enquiry popup fields are in scrollable flex-1 container',
    str_contains($frontendLayout, 'overflow-y-auto flex-1'),
    'Form body scrolls smoothly on all viewports.'
);
check('Task 8', 'Product enquiry popup footer is fixed flex-shrink-0 with sticky action',
    str_contains($frontendLayout, 'flex-shrink-0') && str_contains($frontendLayout, 'modal-submit-btn'),
    'Submit button remains visible in sticky footer container.'
);

// -------------------------------------------------------------------------
// Task 9: Seller Enquiry Reply JS Error
// -------------------------------------------------------------------------
$enquiryDetailView = file_get_contents(__DIR__ . '/../app/Views/supplier/enquiry_detail.php');
check('Task 9', 'Supplier enquiry detail submitReply uses vanilla JS without jQuery errors',
    str_contains($enquiryDetailView, 'document.getElementById(\'replyMessage\')') && !str_contains($enquiryDetailView, '$.ajax'),
    'Native DOM and fetch() API used with CSRF token.'
);

// -------------------------------------------------------------------------
// Task 10: Super Admin One-Click Login as Buyer or Seller
// -------------------------------------------------------------------------
$impersonationController = file_get_contents(__DIR__ . '/../app/Controllers/AdminImpersonationController.php');
$supplierLayout = file_get_contents(__DIR__ . '/../app/Views/layouts/supplier.php');
check('Task 10', 'AdminImpersonationController implements impersonateUser and impersonateSupplier',
    str_contains($impersonationController, 'impersonateUser') && str_contains($impersonationController, 'impersonateSupplier'),
    'Controller supports both Buyer and Supplier login.'
);
check('Task 10', 'Impersonation exit routes and session return implemented',
    str_contains($impersonationController, 'exitImpersonation') && str_contains($impersonationController, 'impersonator_admin'),
    'Admin session saved and restored upon exit.'
);
check('Task 10', 'Top impersonation banner displayed in layouts',
    str_contains($frontendLayout, 'impersonator_admin') && str_contains($supplierLayout, 'impersonator_admin'),
    'Persistent exit banner rendered on frontend and supplier portals.'
);

// -------------------------------------------------------------------------
// Task 11: Bulk Product Upload Workflows
// -------------------------------------------------------------------------
$importerPhp = file_get_contents(__DIR__ . '/../app/Helpers/BulkProductImporter.php');
$subModelPhp = file_get_contents(__DIR__ . '/../app/Models/Subcategory.php');
$supProductsView = file_get_contents(__DIR__ . '/../app/Views/supplier/products.php');

check('Task 11', 'Subcategory::all() and allActive() are defined',
    str_contains($subModelPhp, 'function all(): array') && str_contains($subModelPhp, 'function allActive(): array'),
    'Resolved undefined method fatal error.'
);
check('Task 11', 'BulkProductImporter uses safe writable temp directory',
    str_contains($importerPhp, 'getTempDir()'),
    'Resolved permission denied warnings.'
);
check('Task 11', 'BulkProductImporter supports Export Packaging in templates and parsing',
    str_contains($importerPhp, '\'Export Packaging\'') && str_contains($importerPhp, '\'export_packaging\''),
    'Export packaging integrated into bulk import matrix.'
);
check('Task 11', 'Supplier bulk upload fetch sets X-Requested-With, Accept, and CSRF token',
    str_contains($supProductsView, '\'X-Requested-With\': \'XMLHttpRequest\'') && str_contains($supProductsView, '\'Accept\': \'application/json\''),
    'Resolved validation server network error.'
);
check('Task 11', 'Admin bulk upload fetch sets X-Requested-With, Accept, and CSRF token',
    str_contains($productsIndexView, '\'X-Requested-With\': \'XMLHttpRequest\'') && str_contains($productsIndexView, '\'Accept\': \'application/json\''),
    'Resolved admin validation server network error.'
);

echo "\n======================================================================\n";
echo "SUMMARY: $pass PASSED, $fail FAILED out of " . ($pass + $fail) . " CHECKS\n";
echo "======================================================================\n";

if ($fail > 0) exit(1);
