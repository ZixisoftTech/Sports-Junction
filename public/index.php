<?php
declare(strict_types=1);

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
if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

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
use App\Controllers\SupplierPortalController;
use App\Controllers\BuyerPortalController;
use App\Controllers\HomeController;
use App\Controllers\ShopController;
use App\Controllers\Api\MasterApiController;

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

// -------------------------------------------------------------
// PUBLIC MARKETPLACE ROUTES (Driven 100% by Database)
// -------------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/products', [ShopController::class, 'products']);
$router->get('/category/{catSlug}', [ShopController::class, 'categoryProducts']);
$router->get('/category/{catSlug}/{subSlug}', [ShopController::class, 'subcategoryProducts']);
$router->get('/product/{slug}', [ShopController::class, 'productDetail']);
$router->post('/api/inquiries', [ShopController::class, 'submitInquiry']);
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

// Stitch Screen 3: Email Verified Success
$router->get('/email-verified', [AuthController::class, 'showVerified']);

// OTP Resend Endpoint
$router->post('/api/otp/resend', [AuthController::class, 'resendOtp']);

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
$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->get('/admin/logout', [AuthController::class, 'logout']);

// -------------------------------------------------------------
// PROTECTED BUYER PORTAL ROUTES (Role: BUYER)
// -------------------------------------------------------------
$router->get('/buyer/dashboard', [BuyerPortalController::class, 'dashboard'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/enquiries', [BuyerPortalController::class, 'enquiries'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/enquiries/{id}', [BuyerPortalController::class, 'enquiryDetail'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/enquiries/{id}/reply', [BuyerPortalController::class, 'replyEnquiry'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/favourites', [BuyerPortalController::class, 'favourites'], [BuyerAuthMiddleware::class]);
$router->post('/api/buyer/favourites/toggle', [BuyerPortalController::class, 'toggleFavourite'], [BuyerAuthMiddleware::class]);
$router->delete('/buyer/favourites/{id}', [BuyerPortalController::class, 'removeFavourite'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/favourites/{id}/delete', [BuyerPortalController::class, 'removeFavourite'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/recently-viewed', [BuyerPortalController::class, 'recentlyViewed'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/recently-viewed/clear', [BuyerPortalController::class, 'clearRecentViews'], [BuyerAuthMiddleware::class]);
$router->get('/buyer/profile', [BuyerPortalController::class, 'profile'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/profile', [BuyerPortalController::class, 'updateProfile'], [BuyerAuthMiddleware::class]);
$router->post('/buyer/change-password', [BuyerPortalController::class, 'changePassword'], [BuyerAuthMiddleware::class]);

// -------------------------------------------------------------
// PROTECTED SUPPLIER PORTAL ROUTES (Role: SUPPLIER, Approved)
// -------------------------------------------------------------
$router->get('/supplier/dashboard', [SupplierPortalController::class, 'dashboard'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products', [SupplierPortalController::class, 'products'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products/add', [SupplierPortalController::class, 'addProduct'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/add', [SupplierPortalController::class, 'storeProduct'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/products/edit/{id}', [SupplierPortalController::class, 'editProduct'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/products/edit/{id}', [SupplierPortalController::class, 'updateProduct'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/enquiries', [SupplierPortalController::class, 'enquiries'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/enquiries/{id}', [SupplierPortalController::class, 'enquiryDetail'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/enquiries/{id}/reply', [SupplierPortalController::class, 'replyEnquiry'], [SupplierAuthMiddleware::class]);
$router->get('/supplier/profile', [SupplierPortalController::class, 'profile'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/profile', [SupplierPortalController::class, 'updateProfile'], [SupplierAuthMiddleware::class]);
$router->post('/supplier/change-password', [SupplierPortalController::class, 'changePassword'], [SupplierAuthMiddleware::class]);

// -------------------------------------------------------------
// PROTECTED SUPER ADMIN ROUTES (Role: SUPER_ADMIN)
// -------------------------------------------------------------
$router->get('/admin/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

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

// Super Admin Product Approvals
$router->get('/admin/product-approvals', [ProductApprovalController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/admin/product-approvals/data', [ProductApprovalController::class, 'data'], [AuthMiddleware::class]);
$router->get('/api/admin/product-approvals/{id}', [ProductApprovalController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/admin/product-approvals/{id}/approve', [ProductApprovalController::class, 'approve'], [AuthMiddleware::class]);
$router->post('/api/admin/product-approvals/{id}/reject', [ProductApprovalController::class, 'reject'], [AuthMiddleware::class]);

// Master Management Page Routes
$router->get('/admin/masters/countries', [CountryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/states', [StateController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/cities', [CityController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/business-types', [BusinessTypeController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/price-units', [PriceUnitController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/moq-units', [MoqUnitController::class, 'index'], [AuthMiddleware::class]);

// Categories & Subcategories Admin Routes
$router->get('/admin/categories', [CategoryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/categories', [CategoryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/subcategories', [SubcategoryController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/masters/subcategories', [SubcategoryController::class, 'index'], [AuthMiddleware::class]);

// Products Admin CRUD Routes
$router->get('/admin/products', [ProductController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/products/create', [ProductController::class, 'create'], [AuthMiddleware::class]);
$router->get('/admin/products/add', [ProductController::class, 'create'], [AuthMiddleware::class]);
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

// Dispatch incoming request
$router->dispatch();
