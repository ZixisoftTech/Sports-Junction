<?php
declare(strict_types=1);

// Start root output buffering immediately to prevent any PHP notices/warnings from corrupting JSON/HTTP responses
ob_start();

/**
 * =======================================================================
 * SPORTS JUNCTION — INDIA'S SPORTS MANUFACTURING MARKETPLACE
 * Super Admin & Dynamic B2B Marketplace MVP
 * Front Controller & Application Entry Point
 * =======================================================================
 */

date_default_timezone_set('Asia/Kolkata');

// Load Environment Configuration
require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

// Error Handling
$isDebug = (bool) \App\Helpers\Env::get('APP_DEBUG', false);
if ($isDebug || !empty($_GET['debug']) || !empty($_GET['show_error'])) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

// Global Exception & Error Catching
set_exception_handler(function (\Throwable $e) use ($isDebug): void {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $logMsg = sprintf(
        "[%s] UNCAUGHT EXCEPTION: %s: %s in %s:%d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @error_log($logMsg);
    @file_put_contents(dirname(__DIR__) . '/storage/logs/error.log', $logMsg, FILE_APPEND);

    http_response_code(500);
    $showDebug = $isDebug || !empty($_GET['debug']) || !empty($_GET['show_error']);
    header('Content-Type: text/html; charset=UTF-8');
    if ($showDebug) {
        echo '<!DOCTYPE html><html><head><title>Application Exception</title><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<style>body{font-family:monospace;background:#0f172a;color:#f8fafc;padding:24px}pre{background:#020617;padding:16px;border-radius:8px;overflow-x:auto;color:#cbd5e1}</style></head><body>';
        echo '<h2 style="color:#f43f5e">500 Application Exception</h2>';
        echo '<p><strong>' . htmlspecialchars(get_class($e)) . ':</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<h3>Stack Trace</h3><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    } else {
        echo '<!DOCTYPE html><html><head><title>500 Internal Server Error</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#1e293b}.box{text-align:center;padding:40px;background:#fff;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.1);max-width:480px}h1{font-size:48px;margin:0 0 10px;color:#00288e}</style></head><body><div class="box"><h1>500</h1><h2>System Temporarily Unavailable</h2><p>The system encountered a technical issue while processing this request. Please refresh in a few moments.</p><a href="javascript:location.reload()">Refresh Page</a></div></body></html>';
    }
    exit;
});

// Register PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize Session
\App\Helpers\Session::start();

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\BuyerAuthMiddleware;
use App\Middleware\SupplierAuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CountryController;
use App\Controllers\StateController;
use App\Controllers\CityController;
use App\Controllers\BusinessTypeController;
use App\Controllers\PriceUnitController;
use App\Controllers\MoqUnitController;
use App\Controllers\CategoryController;
use App\Controllers\SubcategoryController;
use App\Controllers\ProductController;
use App\Controllers\SupplierApprovalController;
use App\Controllers\ProductApprovalController;
use App\Controllers\BuyerManagementController;
use App\Controllers\EnquiryManagementController;
use App\Controllers\SupplierPortalController;
use App\Controllers\BuyerPortalController;
use App\Controllers\HomeController;
use App\Controllers\ShopController;
use App\Controllers\Api\MasterApiController;
use App\Controllers\BuyerMessagesController;
use App\Controllers\SupplierMessagesController;
use App\Controllers\AdminCommunicationController;
use App\Controllers\ChatApiController;
use App\Controllers\AdminSettingsController;
use App\Controllers\AdminHeroBannerController;
use App\Controllers\AdminPopupController;
use App\Controllers\AdminProfileChangeController;
use App\Controllers\CmsPageController;
use App\Controllers\ProductCertificateController;
use App\Controllers\CertificationTagController;
use App\Controllers\AdminImpersonationController;
use App\Controllers\AdminAuditTrailController;
use App\Controllers\AdminNotificationController;
use App\Controllers\AdminBulkEnquiryController;
use App\Controllers\AdminFeedbackController;
use App\Controllers\AdminCareerController;

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

// -------------------------------------------------------------
// PUBLIC MARKETPLACE ROUTES (Driven 100% by Database)
// -------------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/categories', [ShopController::class, 'allCategories']);
$router->get('/products', [ShopController::class, 'products']);
$router->get('/category/{catSlug}', [ShopController::class, 'categoryProducts']);
$router->get('/category/{catSlug}/{subSlug}', [ShopController::class, 'subcategoryProducts']);
$router->get('/product/{slug}', [ShopController::class, 'productDetail']);
$router->get('/supplier-store/{id}', [ShopController::class, 'supplierProfile']);
$router->get('/suppliers/{id}', [ShopController::class, 'supplierProfile']);
$router->post('/api/inquiries', [ShopController::class, 'submitInquiry']);
$router->get('/contact', [ShopController::class, 'contact']);
$router->post('/contact', [ShopController::class, 'submitContact']);
$router->post('/api/contact', [ShopController::class, 'submitContact']);
$router->get('/support', [ShopController::class, 'support']);
$router->get('/help', [ShopController::class, 'support']);
$router->post('/support', [ShopController::class, 'submitSupport']);
$router->post('/api/support', [ShopController::class, 'submitSupport']);
// -------------------------------------------------------------
// PUBLIC CMS & DYNAMIC FOOTER PAGES (21 Dedicated Pages)
// -------------------------------------------------------------
$dedicatedCmsPages = [
    // Column 1: About Sports Junction
    '/about-sports-junction'              => 'about-sports-junction',
    '/why-choose-sports-junction'         => 'why-choose-sports-junction',
    '/corporate-responsibility'           => 'corporate-responsibility',

    // Column 2: Buyer & Seller Protection
    '/secure-payments'                    => 'secure-payments',
    '/order-protection-policies'          => 'order-protection-policies',
    '/guaranteed-delivery'                => 'guaranteed-delivery',
    '/production-monitoring-inspection'   => 'production-monitoring-inspection',

    // Column 3: Help & Support
    '/buyer-help-centre'                  => 'buyer-help-centre',
    '/seller-help-centre'                 => 'seller-help-centre',
    '/live-chat'                          => 'live-chat',
    '/become-verified-supplier'           => 'become-verified-supplier',
    '/contact-us'                         => 'contact-us',

    // Column 4: Sourcing & Logistics
    '/sourcing-solutions'                 => 'sourcing-solutions',
    '/container-freight-logistics'        => 'container-freight-logistics',
    '/sitemap'                            => 'sitemap',
    '/xml-sitemap'                        => 'sitemap',

    // Column 5: Legal & Platform Policies
    '/terms-and-conditions'               => 'terms-and-conditions',
    '/privacy-policy'                     => 'privacy-policy',
    '/manufacturing-sla'                  => 'manufacturing-sla',
    '/terms-manufacturing-sla'            => 'terms-manufacturing-sla',
    '/seller-manufacturer-agreement'      => 'seller-manufacturer-agreement',
    '/seller-agreement'                   => 'seller-agreement',
];

foreach ($dedicatedCmsPages as $routePath => $cmsSlug) {
    $router->get($routePath, function (Request $req, Response $res) use ($cmsSlug) {
        (new CmsPageController($req, $res))->show(['slug' => $cmsSlug]);
    });
}

// Dedicated Suggestions & Feedback Form Routes
$router->get('/feedback-suggestions', [ShopController::class, 'feedback']);
$router->get('/suggestions-feedback', [ShopController::class, 'feedback']);
$router->post('/api/feedback', [ShopController::class, 'submitFeedback']);
$router->post('/feedback-suggestions', [ShopController::class, 'submitFeedback']);

// Dedicated Careers & Job Application Routes
$router->get('/careers', [ShopController::class, 'careers']);
$router->get('/careers-with-us', [ShopController::class, 'careers']);
$router->post('/careers/apply', [ShopController::class, 'submitCareerApplication']);
$router->post('/api/careers/apply', [ShopController::class, 'submitCareerApplication']);

$router->post('/contact-us', [ShopController::class, 'submitContact']);

$router->get('/page/{slug}', [CmsPageController::class, 'show']);
$router->get('/sitemap.xml', [ShopController::class, 'sitemap']);

// -------------------------------------------------------------
// PUBLIC AUTHENTICATION & ONBOARDING ROUTES
// -------------------------------------------------------------
// Stitch Screen 1: Register Buyer
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

// Supplier / Seller Registration & Dedicated Login
$router->get('/supplier/register', [AuthController::class, 'showSupplierRegister']);
$router->post('/supplier/register', [AuthController::class, 'supplierRegister']);
$router->get('/seller/register', [AuthController::class, 'showSupplierRegister']);
$router->post('/seller/register', [AuthController::class, 'supplierRegister']);
$router->get('/seller/login', [AuthController::class, 'showSellerLogin']);
$router->post('/seller/login', [AuthController::class, 'sellerLogin']);
$router->get('/supplier/login', [AuthController::class, 'showSellerLogin']);
$router->post('/supplier/login', [AuthController::class, 'sellerLogin']);

// Stitch Screen 2: OTP Email Verification
$router->get('/verify-email', [AuthController::class, 'showVerifyOtp']);
$router->post('/verify-email', [AuthController::class, 'verifyOtp']);
$router->get('/change-email', [AuthController::class, 'changeRegistrationEmail']);

// Stitch Screen 3: Email Verified Success
$router->get('/email-verified', [AuthController::class, 'showVerified']);

// OTP Resend Endpoint
$router->post('/api/otp/resend', [AuthController::class, 'resendOtp']);

// Profile Email Change Endpoints (Secure Authenticated OTP Verification)
$router->post('/api/profile/change-email', [AuthController::class, 'requestEmailChange']);
$router->post('/api/profile/verify-change-email', [AuthController::class, 'verifyEmailChange']);

// Stitch Screen 4: Unified Login (Buyer / Supplier / Admin)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

// Logout
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

// Password Recovery Flow
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// Supplier Pending Approval Notice Screen
$router->get('/supplier/pending-approval', [AuthController::class, 'supplierPendingApproval']);

// Public Dependent Location Dropdowns (Registration)
$router->get('/api/public/states-by-country/{countryId}', [AuthController::class, 'publicStatesByCountry']);
$router->get('/api/public/cities-by-state/{stateId}', [AuthController::class, 'publicCitiesByState']);

// Super Admin Login Alias
$router->get('/admin', function (Request $req, Response $res) {
    $user = \App\Helpers\Session::get('auth_user');
    if ($user && ($user['role'] ?? '') === 'SUPER_ADMIN') {
        $res->redirect('/admin/dashboard');
    } else {
        $res->redirect('/admin/login');
    }
});
$router->get('/admin/login', [AuthController::class, 'showAdminLogin']);
$router->post('/admin/login', [AuthController::class, 'adminLogin']);
$router->get('/admin/logout', [AuthController::class, 'logout']);

// -------------------------------------------------------------
// PROTECTED BUYER PORTAL ROUTES (Role: BUYER)
// -------------------------------------------------------------
$router->get('/buyer/dashboard', [BuyerPortalController::class, 'dashboard'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/enquiries', [BuyerPortalController::class, 'enquiries'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/sample-enquiries', [BuyerPortalController::class, 'sampleEnquiries'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/enquiries/{id}', [BuyerPortalController::class, 'enquiryDetail'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/enquiries/{id}/reply', [BuyerPortalController::class, 'replyEnquiry'], [BuyerAuthMiddleware::class]);
// Protected Buyer Sort List (Role: BUYER Mandatory)
$router->get('/buyer/favourites', [BuyerPortalController::class, 'favourites'], [BuyerAuthMiddleware::class]);
$router->post('/api/buyer/favourites/toggle', [BuyerPortalController::class, 'toggleFavourite'], [BuyerAuthMiddleware::class]);
$router->delete('/buyer/favourites/{id}', [BuyerPortalController::class, 'removeFavourite'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/favourites/{id}/delete', [BuyerPortalController::class, 'removeFavourite'], [BuyerAuthMiddleware::class]);

$router->get('/buyer/sort-list', [BuyerPortalController::class, 'sortList'], [BuyerAuthMiddleware::class]);
$router->get('/sort-list', [BuyerPortalController::class, 'sortList'], [BuyerAuthMiddleware::class]);
$router->post('/api/buyer/sort-list/toggle', [BuyerPortalController::class, 'toggleSortList'], [BuyerAuthMiddleware::class]);
$router->post('/api/sort-list/toggle', [BuyerPortalController::class, 'toggleSortList'], [BuyerAuthMiddleware::class]);
$router->delete('/buyer/sort-list/{id}', [BuyerPortalController::class, 'removeSortItem'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/sort-list/{id}/delete', [BuyerPortalController::class, 'removeSortItem'], [BuyerAuthMiddleware::class]);
$router->post('/api/buyer/sort-list/remove-selected', [BuyerPortalController::class, 'removeSelectedSortItems'], [BuyerAuthMiddleware::class]);
$router->post('/api/buyer/sort-list/clear', [BuyerPortalController::class, 'clearSortList'], [BuyerAuthMiddleware::class]);
$router->post('/api/buyer/bulk-enquiry/submit', [BuyerPortalController::class, 'submitBulkEnquiry'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/bulk-enquiries', [BuyerPortalController::class, 'bulkEnquiries'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/bulk-enquiries/{id}', [BuyerPortalController::class, 'bulkEnquiryDetail'], [BuyerAuthMiddleware::class]);
$router->get('/api/buyer/bulk-enquiries/{id}', [BuyerPortalController::class, 'bulkEnquiryDetail'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/recently-viewed', [BuyerPortalController::class, 'recentlyViewed'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/recently-viewed/clear', [BuyerPortalController::class, 'clearRecentViews'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/profile', [BuyerPortalController::class, 'profile'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/profile', [BuyerPortalController::class, 'updateProfile'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/change-password', [BuyerPortalController::class, 'changePassword'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/messages', [BuyerMessagesController::class, 'index'], [BuyerAuthMiddleware::class]);

// -------------------------------------------------------------
// PROTECTED SUPPLIER PORTAL ROUTES (Role: SUPPLIER, Approved)
// -------------------------------------------------------------
$router->get('/supplier/dashboard', [SupplierPortalController::class, 'dashboard'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products', [SupplierPortalController::class, 'products'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products/add', [SupplierPortalController::class, 'addProduct'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/add', [SupplierPortalController::class, 'storeProduct'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/store', [SupplierPortalController::class, 'storeProduct'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products/bulk-template', [SupplierPortalController::class, 'downloadBulkTemplate'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products/import-reference', [SupplierPortalController::class, 'downloadImportReference'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/bulk-preview', [SupplierPortalController::class, 'bulkPreview'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/bulk-commit', [SupplierPortalController::class, 'bulkCommit'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products/bulk-error-report', [SupplierPortalController::class, 'downloadErrorReport'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products/edit/{id}', [SupplierPortalController::class, 'editProduct'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/edit/{id}', [SupplierPortalController::class, 'updateProduct'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/update/{id}', [SupplierPortalController::class, 'updateProduct'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/enquiries', [SupplierPortalController::class, 'enquiries'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/sample-enquiries', [SupplierPortalController::class, 'sampleEnquiries'], [SupplierAuthMiddleware::class]);
$router->post('/api/supplier/sample-enquiries/{id}/status', [SupplierPortalController::class, 'updateSampleStatus'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/enquiries/{id}', [SupplierPortalController::class, 'enquiryDetail'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/enquiries/{id}/reply', [SupplierPortalController::class, 'replyEnquiry'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/messages', [SupplierMessagesController::class, 'index'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/profile', [SupplierPortalController::class, 'profile'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/profile', [SupplierPortalController::class, 'updateProfile'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/change-password', [SupplierPortalController::class, 'changePassword'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/{id}', [ShopController::class, 'supplierProfile']);

// -------------------------------------------------------------
// PROTECTED SUPER ADMIN ROUTES (Role: SUPER_ADMIN)
// -------------------------------------------------------------
$router->get('/admin/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Super Admin Buyer Management
$router->get('/admin/buyers', [BuyerManagementController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/buyers/data', [BuyerManagementController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/buyers/export', [BuyerManagementController::class, 'export'], [AuthMiddleware::class]);
$router->get('/api/admin/buyers/{id}', [BuyerManagementController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/buyers/{id}/update', [BuyerManagementController::class, 'update'], [AuthMiddleware::class]);
$router->post('/api/admin/buyers/{id}/toggle-status', [BuyerManagementController::class, 'toggleStatus'], [AuthMiddleware::class]);

// Super Admin One-Click Login as Buyer or Seller (Impersonation)
$router->get('/admin/impersonate/user/{id}', [AdminImpersonationController::class, 'impersonateUser'], [AuthMiddleware::class]);
$router->get('/admin/impersonate/supplier/{id}', [AdminImpersonationController::class, 'impersonateSupplier'], [AuthMiddleware::class]);
$router->get('/admin/exit-impersonation', [AdminImpersonationController::class, 'exitImpersonation']);
$router->get('/api/admin/users/{id}/credentials', [AdminImpersonationController::class, 'getCredentials'], [AuthMiddleware::class]);
$router->get('/api/admin/suppliers/{id}/credentials', [AdminImpersonationController::class, 'getSupplierCredentials'], [AuthMiddleware::class]);
$router->post('/api/admin/users/{id}/set-password', [AdminImpersonationController::class, 'setPassword'], [AuthMiddleware::class]);

// Super Admin Centralized Enquiry Management
$router->get('/admin/enquiries', [EnquiryManagementController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/rfq-oem', [EnquiryManagementController::class, 'rfqOem'], [AuthMiddleware::class]);
$router->get('/admin/sample-enquiries', [EnquiryManagementController::class, 'sampleEnquiries'], [AuthMiddleware::class]);
$router->get('/admin/prototype-sample-enquiries', [EnquiryManagementController::class, 'sampleEnquiries'], [AuthMiddleware::class]);
$router->get('/admin/product-enquiries', [EnquiryManagementController::class, 'productEnquiries'], [AuthMiddleware::class]);
$router->get('/admin/rfq-enquiries', [EnquiryManagementController::class, 'rfqEnquiries'], [AuthMiddleware::class]);
$router->get('/admin/fast-oem-enquiries', [EnquiryManagementController::class, 'fastOemEnquiries'], [AuthMiddleware::class]);
$router->get('/admin/general-enquiries', [EnquiryManagementController::class, 'generalEnquiries'], [AuthMiddleware::class]);
$router->get('/admin/contact-us-enquiries', [EnquiryManagementController::class, 'contactEnquiries'], [AuthMiddleware::class]);
$router->get('/admin/support-enquiries', [EnquiryManagementController::class, 'supportEnquiries'], [AuthMiddleware::class]);

$router->get('/admin/enquiries/attachment/{id}', [EnquiryManagementController::class, 'downloadAttachment']);
$router->get('/admin/rfq-oem/attachment/{id}', [EnquiryManagementController::class, 'downloadAttachment']);
$router->get('/admin/sample-enquiries/attachment/{id}', [EnquiryManagementController::class, 'downloadAttachment']);
$router->get('/admin/enquiries/{id}', [EnquiryManagementController::class, 'show'], [AuthMiddleware::class]);
$router->post('/admin/enquiries/{id}/reply', [EnquiryManagementController::class, 'reply'], [AuthMiddleware::class]);

$router->get('/api/admin/enquiries/data', [EnquiryManagementController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/enquiries/export', [EnquiryManagementController::class, 'export'], [AuthMiddleware::class]);
$router->get('/api/admin/rfq-oem/data', [EnquiryManagementController::class, 'rfqOemData'], [AuthMiddleware::class]);
$router->get('/api/admin/rfq-oem/export', [EnquiryManagementController::class, 'rfqOemExport'], [AuthMiddleware::class]);
$router->get('/api/admin/sample-enquiries/data', [EnquiryManagementController::class, 'sampleEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/sample-enquiries/export', [EnquiryManagementController::class, 'sampleEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/prototype-sample-enquiries/data', [EnquiryManagementController::class, 'sampleEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/prototype-sample-enquiries/export', [EnquiryManagementController::class, 'sampleEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/product-enquiries/data', [EnquiryManagementController::class, 'productEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/product-enquiries/export', [EnquiryManagementController::class, 'productEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/rfq-enquiries/data', [EnquiryManagementController::class, 'rfqEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/rfq-enquiries/export', [EnquiryManagementController::class, 'rfqEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/fast-oem-enquiries/data', [EnquiryManagementController::class, 'fastOemEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/fast-oem-enquiries/export', [EnquiryManagementController::class, 'fastOemEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/general-enquiries/data', [EnquiryManagementController::class, 'generalEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/general-enquiries/export', [EnquiryManagementController::class, 'generalEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/contact-us-enquiries/data', [EnquiryManagementController::class, 'contactEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/contact-us-enquiries/export', [EnquiryManagementController::class, 'contactEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/support-enquiries/data', [EnquiryManagementController::class, 'supportEnquiriesData'], [AuthMiddleware::class]);
$router->get('/api/admin/support-enquiries/export', [EnquiryManagementController::class, 'supportEnquiriesExport'], [AuthMiddleware::class]);
$router->get('/api/admin/enquiries/{id}', [EnquiryManagementController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/enquiries/{id}/assign-supplier', [EnquiryManagementController::class, 'assignSupplier'], [AuthMiddleware::class]);
$router->post('/api/admin/enquiries/{id}/status', [EnquiryManagementController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->post('/api/admin/enquiries/{id}/notes', [EnquiryManagementController::class, 'updateNotes'], [AuthMiddleware::class]);
$router->post('/api/admin/enquiries/{id}/reply', [EnquiryManagementController::class, 'reply'], [AuthMiddleware::class]);

// Super Admin Centralized Bulk Product Enquiries
$router->get('/admin/bulk-enquiries', [AdminBulkEnquiryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/bulk-enquiries/{id}', [AdminBulkEnquiryController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/bulk-enquiries/{id}/status', [AdminBulkEnquiryController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->get('/admin/bulk-enquiries/attachment/{id}', [AdminBulkEnquiryController::class, 'downloadAttachment']);
$router->get('/admin/bulk-enquiries/item-attachment/{id}', [AdminBulkEnquiryController::class, 'downloadItemAttachment']);

// Super Admin Communication & Negotiation Monitoring Center
$router->get('/admin/communication', [AdminCommunicationController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/communication/{id}', [AdminCommunicationController::class, 'show'], [AuthMiddleware::class]);
$router->post('/admin/communication/reports/{id}/resolve', [AdminCommunicationController::class, 'resolveReport'], [AuthMiddleware::class]);

// Super Admin Notification Center
$router->get('/admin/notifications', [AdminNotificationController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/notifications/{id}/go', [AdminNotificationController::class, 'go'], [AuthMiddleware::class]);
$router->get('/api/admin/notifications/poll', [AdminNotificationController::class, 'poll'], [AuthMiddleware::class]);
$router->post('/api/admin/notifications/poll', [AdminNotificationController::class, 'poll'], [AuthMiddleware::class]);
$router->post('/api/admin/notifications/{id}/read', [AdminNotificationController::class, 'markRead'], [AuthMiddleware::class]);
$router->post('/api/admin/notifications/{id}/unread', [AdminNotificationController::class, 'markUnread'], [AuthMiddleware::class]);
$router->post('/api/admin/notifications/mark-all-read', [AdminNotificationController::class, 'markAllRead'], [AuthMiddleware::class]);

// Super Admin Site Settings & Footer CMS Management
$router->get('/admin/settings', [AdminSettingsController::class, 'index'], [AuthMiddleware::class]);
$router->post('/admin/settings', [AdminSettingsController::class, 'updateGeneral'], [AuthMiddleware::class]);
$router->post('/admin/settings/general', [AdminSettingsController::class, 'updateGeneral'], [AuthMiddleware::class]);
$router->post('/admin/settings/homepage-categories', [AdminSettingsController::class, 'updateHomepageCategories'], [AuthMiddleware::class]);
$router->get('/admin/pages', [AdminSettingsController::class, 'pages'], [AuthMiddleware::class]);
$router->post('/admin/pages', [AdminSettingsController::class, 'storePage'], [AuthMiddleware::class]);
$router->post('/admin/pages/create', [AdminSettingsController::class, 'storePage'], [AuthMiddleware::class]);
$router->post('/admin/pages/edit/{id}', [AdminSettingsController::class, 'updatePage'], [AuthMiddleware::class]);
$router->post('/admin/pages/{id}', [AdminSettingsController::class, 'updatePage'], [AuthMiddleware::class]);
$router->put('/admin/pages/{id}', [AdminSettingsController::class, 'updatePage'], [AuthMiddleware::class]);
$router->post('/admin/pages/delete/{id}', [AdminSettingsController::class, 'deletePage'], [AuthMiddleware::class]);
$router->post('/admin/pages/{id}/delete', [AdminSettingsController::class, 'deletePage'], [AuthMiddleware::class]);
$router->delete('/admin/pages/{id}', [AdminSettingsController::class, 'deletePage'], [AuthMiddleware::class]);
$router->post('/api/admin/pages/{id}/toggle-status', [AdminSettingsController::class, 'togglePageStatus'], [AuthMiddleware::class]);
$router->post('/api/admin/pages/{id}/toggle-footer', [AdminSettingsController::class, 'togglePageFooter'], [AuthMiddleware::class]);

// Super Admin Hero Banner Management
$router->get('/admin/banners', [AdminHeroBannerController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/banners/data', [AdminHeroBannerController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/banners/{id}', [AdminHeroBannerController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/banners', [AdminHeroBannerController::class, 'store'], [AuthMiddleware::class]);
$router->post('/api/admin/banners/{id}', [AdminHeroBannerController::class, 'update'], [AuthMiddleware::class]);
$router->put('/api/admin/banners/{id}', [AdminHeroBannerController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/admin/banners/{id}', [AdminHeroBannerController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/api/admin/banners/{id}/delete', [AdminHeroBannerController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/api/admin/banners/{id}/toggle-status', [AdminHeroBannerController::class, 'toggleStatus'], [AuthMiddleware::class]);

// Super Admin Autoload Popup Management
$router->get('/admin/popups', [AdminPopupController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/popups/data', [AdminPopupController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/popups/{id}', [AdminPopupController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/popups', [AdminPopupController::class, 'store'], [AuthMiddleware::class]);
$router->post('/api/admin/popups/{id}', [AdminPopupController::class, 'update'], [AuthMiddleware::class]);
$router->put('/api/admin/popups/{id}', [AdminPopupController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/admin/popups/{id}', [AdminPopupController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/api/admin/popups/{id}/delete', [AdminPopupController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/api/admin/popups/{id}/toggle-status', [AdminPopupController::class, 'toggleStatus'], [AuthMiddleware::class]);

// Super Admin Dedicated Audit Trail
$router->get('/admin/audit-trail', [AdminAuditTrailController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/audit-trail/data', [AdminAuditTrailController::class, 'data'], [AuthMiddleware::class]);
$router->post('/api/admin/audit-trail/data', [AdminAuditTrailController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/audit-trail/export', [AdminAuditTrailController::class, 'export'], [AuthMiddleware::class]);

// Super Admin Dedicated Seller Agreement Management
$router->get('/admin/seller-agreement', [AdminSettingsController::class, 'sellerAgreement'], [AuthMiddleware::class]);
$router->post('/admin/seller-agreement', [AdminSettingsController::class, 'updateSellerAgreement'], [AuthMiddleware::class]);

// Super Admin Transactional Email Templates & SMTP Delivery Tool
$router->get('/admin/email-templates', [AdminSettingsController::class, 'emailTemplates'], [AuthMiddleware::class]);
$router->post('/admin/email-templates/test-send', [AdminSettingsController::class, 'testSendEmail'], [AuthMiddleware::class]);

// Super Admin Suggestions & Feedback Management
$router->get('/admin/feedbacks', [AdminFeedbackController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/feedbacks/data', [AdminFeedbackController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/feedbacks/export', [AdminFeedbackController::class, 'export'], [AuthMiddleware::class]);
$router->get('/api/admin/feedbacks/{id}', [AdminFeedbackController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/feedbacks/{id}/status', [AdminFeedbackController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->post('/api/admin/feedbacks/{id}/notes', [AdminFeedbackController::class, 'updateNotes'], [AuthMiddleware::class]);
$router->get('/admin/feedbacks/attachment/{id}', [AdminFeedbackController::class, 'downloadAttachment']);

// Super Admin Careers & Candidate Applications Management
$router->get('/admin/careers', [AdminCareerController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/careers/applications/data', [AdminCareerController::class, 'applicationsData'], [AuthMiddleware::class]);
$router->get('/api/admin/careers/applications/export', [AdminCareerController::class, 'exportApplications'], [AuthMiddleware::class]);
$router->get('/api/admin/careers/applications/{id}', [AdminCareerController::class, 'showApplication'], [AuthMiddleware::class]);
$router->post('/api/admin/careers/applications/{id}/status', [AdminCareerController::class, 'updateApplicationStatus'], [AuthMiddleware::class]);
$router->post('/api/admin/careers/applications/{id}/notes', [AdminCareerController::class, 'updateApplicationNotes'], [AuthMiddleware::class]);
$router->get('/admin/careers/resume/{id}', [AdminCareerController::class, 'downloadResume']);

// Super Admin Job Openings Management
$router->get('/api/admin/careers/jobs', [AdminCareerController::class, 'jobsData'], [AuthMiddleware::class]);
$router->post('/api/admin/careers/jobs', [AdminCareerController::class, 'saveJob'], [AuthMiddleware::class]);
$router->post('/api/admin/careers/jobs/{id}/delete', [AdminCareerController::class, 'deleteJob'], [AuthMiddleware::class]);
$router->post('/api/admin/careers/jobs/{id}/toggle-status', [AdminCareerController::class, 'toggleJobStatus'], [AuthMiddleware::class]);

// Super Admin Supplier Management & Approvals
$router->get('/admin/suppliers', [SupplierApprovalController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/suppliers/data', [SupplierApprovalController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/suppliers/{id}', [SupplierApprovalController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers', [SupplierApprovalController::class, 'store'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers/{id}', [SupplierApprovalController::class, 'update'], [AuthMiddleware::class]);
$router->put('/api/admin/suppliers/{id}', [SupplierApprovalController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/admin/suppliers/{id}', [SupplierApprovalController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers/{id}/delete', [SupplierApprovalController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers/{id}/approve', [SupplierApprovalController::class, 'approve'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers/{id}/reject', [SupplierApprovalController::class, 'reject'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers/{id}/suspend', [SupplierApprovalController::class, 'suspend'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers/{id}/reactivate', [SupplierApprovalController::class, 'reactivate'], [AuthMiddleware::class]);
$router->post('/api/admin/suppliers/{id}/toggle-status', [SupplierApprovalController::class, 'toggleStatus'], [AuthMiddleware::class]);

// Super Admin Product Approvals
$router->get('/admin/product-approvals', [ProductApprovalController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/product-approvals/data', [ProductApprovalController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/product-approvals/{id}', [ProductApprovalController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/product-approvals/{id}/approve', [ProductApprovalController::class, 'approve'], [AuthMiddleware::class]);
$router->post('/api/admin/product-approvals/{id}/reject', [ProductApprovalController::class, 'reject'], [AuthMiddleware::class]);

// Profile Change Requests (Buyer & Supplier Controlled Approvals)
$router->get('/api/admin/profile-requests', [AdminProfileChangeController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/profile-requests/{id}', [AdminProfileChangeController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/profile-requests/{id}/approve', [AdminProfileChangeController::class, 'approve'], [AuthMiddleware::class]);
$router->post('/api/admin/profile-requests/{id}/reject', [AdminProfileChangeController::class, 'reject'], [AuthMiddleware::class]);

// Master Management Page Routes
$router->get('/admin/masters/countries', [CountryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/states', [StateController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/cities', [CityController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/business-types', [BusinessTypeController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/price-units', [PriceUnitController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/moq-units', [MoqUnitController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/product-certificates', [ProductCertificateController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/certification-tags', [CertificationTagController::class, 'index'], [AuthMiddleware::class]);

// Categories & Subcategories Admin Routes
$router->get('/admin/categories', [CategoryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/categories', [CategoryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/subcategories', [SubcategoryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/subcategories', [SubcategoryController::class, 'index'], [AuthMiddleware::class]);

// Products Admin CRUD Routes
$router->get('/admin/products', [ProductController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/products/create', [ProductController::class, 'create'], [AuthMiddleware::class]);
$router->get('/admin/products/add', [ProductController::class, 'create'], [AuthMiddleware::class]);
$router->get('/admin/products/bulk-template', [ProductController::class, 'downloadBulkTemplate'], [AuthMiddleware::class]);
$router->get('/api/products/bulk-template', [ProductController::class, 'downloadBulkTemplate'], [AuthMiddleware::class]);
$router->get('/admin/products/import-reference', [ProductController::class, 'downloadImportReference'], [AuthMiddleware::class]);
$router->get('/api/products/import-reference', [ProductController::class, 'downloadImportReference'], [AuthMiddleware::class]);
$router->post('/api/products/bulk-preview', [ProductController::class, 'bulkPreview'], [AuthMiddleware::class]);
$router->post('/api/products/bulk-commit', [ProductController::class, 'bulkCommit'], [AuthMiddleware::class]);
$router->get('/api/products/bulk-error-report', [ProductController::class, 'downloadErrorReport'], [AuthMiddleware::class]);
$router->get('/admin/products/edit/{id}', [ProductController::class, 'edit'], [AuthMiddleware::class]);
$router->get('/api/products/data', [ProductController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/products/export', [ProductController::class, 'export'], [AuthMiddleware::class]);
$router->get('/api/products/{id}', [ProductController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/products', [ProductController::class, 'store'], [AuthMiddleware::class]);
$router->post('/api/products/{id}', [ProductController::class, 'update'], [AuthMiddleware::class]);
$router->put('/api/products/{id}', [ProductController::class, 'update'], [AuthMiddleware::class]);
$router->post('/api/products/{id}/status', [ProductController::class, 'toggleStatus'], [AuthMiddleware::class]);
$router->patch('/api/products/{id}/status', [ProductController::class, 'toggleStatus'], [AuthMiddleware::class]);
$router->post('/api/products/{id}/featured', [ProductController::class, 'toggleFeatured'], [AuthMiddleware::class]);
$router->patch('/api/products/{id}/featured', [ProductController::class, 'toggleFeatured'], [AuthMiddleware::class]);
$router->post('/api/products/{id}/delete', [ProductController::class, 'delete'], [AuthMiddleware::class]);
$router->delete('/api/products/{id}', [ProductController::class, 'delete'], [AuthMiddleware::class]);

// -------------------------------------------------------------
// PROTECTED MASTER REST API ENDPOINTS
// -------------------------------------------------------------
// Dynamic dependent states and subcategories loaders
$router->get('/api/masters/states-by-country/{countryId}', [MasterApiController::class, 'statesByCountry'], [AuthMiddleware::class]);
$router->get('/api/masters/subcategories-by-category/{categoryId}', [MasterApiController::class, 'subcategoriesByCategory']);
$router->get('/api/masters/subcategories-by-categories', [MasterApiController::class, 'subcategoriesByCategories']);
$router->post('/api/masters/subcategories-by-categories', [MasterApiController::class, 'subcategoriesByCategories']);

// DataTables AJAX data endpoint
$router->get('/api/masters/{entity}/data', [MasterApiController::class, 'data'], [AuthMiddleware::class]);

// Dependencies check endpoint
$router->get('/api/masters/{entity}/{id}/dependencies', [MasterApiController::class, 'dependencies'], [AuthMiddleware::class]);

// Export endpoint
$router->get('/api/masters/{entity}/export', [MasterApiController::class, 'export'], [AuthMiddleware::class]);

// Single record details
$router->get('/api/masters/{entity}/{id}', [MasterApiController::class, 'show'], [AuthMiddleware::class]);

// Create master record
$router->post('/api/masters/{entity}', [MasterApiController::class, 'store'], [AuthMiddleware::class]);

// Update master record
$router->put('/api/masters/{entity}/{id}', [MasterApiController::class, 'update'], [AuthMiddleware::class]);
$router->post('/api/masters/{entity}/{id}', [MasterApiController::class, 'update'], [AuthMiddleware::class]);

// Status toggle endpoint
$router->patch('/api/masters/{entity}/{id}/status', [MasterApiController::class, 'toggleStatus'], [AuthMiddleware::class]);
$router->post('/api/masters/{entity}/{id}/status', [MasterApiController::class, 'toggleStatus'], [AuthMiddleware::class]);

// Hard delete endpoint
$router->delete('/api/masters/{entity}/{id}', [MasterApiController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/api/masters/{entity}/{id}/delete', [MasterApiController::class, 'delete'], [AuthMiddleware::class]);

// -------------------------------------------------------------
// REAL-TIME CHAT, NEGOTIATION & DEAL MANAGEMENT API ENDPOINTS
// -------------------------------------------------------------
$router->get('/api/chat/conversations', [ChatApiController::class, 'conversations']);
$router->post('/api/chat/conversations/start', [ChatApiController::class, 'startConversation']);
$router->get('/api/chat/conversations/{id}', [ChatApiController::class, 'getConversation']);
$router->post('/api/chat/messages', [ChatApiController::class, 'sendMessage']);
$router->get('/api/chat/stream', [ChatApiController::class, 'stream']);
$router->get('/api/chat/poll', [ChatApiController::class, 'poll']);
$router->post('/api/chat/attachments/upload', [ChatApiController::class, 'uploadAttachment']);
$router->get('/api/chat/attachments/{id}/download', [ChatApiController::class, 'downloadAttachment']);
$router->post('/api/chat/offers', [ChatApiController::class, 'createOffer']);
$router->post('/api/chat/offers/{id}/accept', [ChatApiController::class, 'acceptOffer']);
$router->post('/api/chat/offers/{id}/reject', [ChatApiController::class, 'rejectOffer']);
$router->post('/api/chat/offers/{id}/withdraw', [ChatApiController::class, 'withdrawOffer']);
$router->post('/api/chat/block', [ChatApiController::class, 'toggleBlock']);
$router->post('/api/chat/report', [ChatApiController::class, 'submitReport']);
$router->post('/api/chat/admin/reply', [ChatApiController::class, 'adminReply']);

// -------------------------------------------------------------
// SYSTEM HEALTH & ENVIRONMENT DIAGNOSTIC ENDPOINT
// -------------------------------------------------------------
$router->get('/health', function(Request $req, Response $res) {
    $dbConnected = false;
    $dbError = null;
    $dbConfig = [
        'host' => \App\Helpers\Env::get('DB_HOST', '127.0.0.1'),
        'database' => \App\Helpers\Env::get('DB_DATABASE', 'sports_junction'),
        'username' => \App\Helpers\Env::get('DB_USERNAME', 'root'),
        'socket' => \App\Helpers\Env::get('DB_SOCKET', ''),
        'env_file_exists' => file_exists(dirname(__DIR__) . '/.env')
    ];
    try {
        \App\Core\Database::getConnection();
        $dbConnected = true;
    } catch (\Throwable $e) {
        $dbError = $e->getMessage();
    }

    $res->json([
        'status' => $dbConnected ? 'healthy' : 'database_unreachable',
        'app_name' => \App\Helpers\Env::get('APP_NAME', 'Sports Junction'),
        'php_version' => PHP_VERSION,
        'server_time' => date('Y-m-d H:i:s T'),
        'database' => [
            'connected' => $dbConnected,
            'error' => $dbError,
            'config' => $dbConfig
        ]
    ], $dbConnected ? 200 : 503);
});

// Dispatch incoming request
try {
    $router->dispatch();
} catch (\Throwable $e) {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $logMsg = sprintf(
        "[%s] ROUTER DISPATCH EXCEPTION: %s: %s in %s:%d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @error_log($logMsg);
    @file_put_contents(dirname(__DIR__) . '/storage/logs/error.log', $logMsg, FILE_APPEND);

    http_response_code(500);
    $showDebug = $isDebug || !empty($_GET['debug']) || !empty($_GET['show_error']);
    header('Content-Type: text/html; charset=UTF-8');
    if ($showDebug) {
        echo '<!DOCTYPE html><html><head><title>500 Internal Server Error</title><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<style>body{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;background:#0f172a;color:#f8fafc;padding:24px;line-height:1.5}.card{max-width:960px;margin:20px auto;background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.5)}h1{color:#f43f5e;font-size:22px;margin-top:0}.meta{background:#090d16;padding:12px 16px;border-radius:8px;border-left:4px solid #f43f5e;margin-bottom:16px}pre{background:#020617;padding:16px;border-radius:8px;overflow-x:auto;color:#cbd5e1;font-size:13px}.hint{background:#1e3a5f;border-left:4px solid #38bdf8;padding:12px 16px;border-radius:8px;margin-top:16px;color:#e0f2fe}</style></head><body><div class="card">';
        echo '<h1>500 Internal Application Error</h1>';
        echo '<div class="meta">';
        echo '<div><strong>Exception:</strong> ' . htmlspecialchars(get_class($e)) . '</div>';
        echo '<div><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<div><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</div>';
        echo '</div>';
        echo '<h3>Stack Trace</h3><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div></body></html>';
    } else {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Temporarily Unavailable — Sports Junction</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}.box{background:#fff;border-radius:16px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.08);padding:40px;max-width:500px;text-align:center;border:1px solid #e2e8f0}h1{font-size:32px;margin:0 0 12px;color:#00288e;font-weight:800}p{color:#64748b;font-size:15px;line-height:1.6;margin-bottom:24px}.btn{display:inline-block;background:#00288e;color:#fff;padding:10px 24px;border-radius:10px;text-decoration:none;font-weight:600}</style></head><body><div class="box"><h1>500</h1><h2>System Temporarily Unavailable</h2><p>Our marketplace is currently completing a scheduled update or maintenance. Please refresh the page in a few moments.</p><a href="javascript:location.reload()" class="btn">Reload Page</a></div></body></html>';
    }
    exit;
}
