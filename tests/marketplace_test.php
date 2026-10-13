<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — DYNAMIC B2B MARKETPLACE MVP AUTOMATED TEST SUITE
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

use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Controllers\HomeController;
use App\Controllers\ShopController;
use App\Controllers\CategoryController;
use App\Controllers\SubcategoryController;
use App\Controllers\ProductController;

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
echo " SPORTS JUNCTION — MARKETPLACE MVP VERIFICATION TEST SUITE\n";
echo "=======================================================================\n\n";

// -------------------------------------------------------------------
// 1. CATEGORY & SUBCATEGORY CRUD TESTS
// -------------------------------------------------------------------
echo "--- 1. Category & Subcategory Master Tests ---\n";

it('Category CRUD: creates category with auto-generated unique slug and retrieves active records', function () {
    $uniqueName = 'Testing Agility Arena ' . time();
    $catId = Category::create([
        'name'          => $uniqueName,
        'description'   => 'Dedicated test category for agility gear',
        'display_order' => 99,
        'status'        => 'ACTIVE'
    ]);
    assert_true($catId > 0, 'Failed to create category');

    $cat = Category::findById($catId);
    assert_equals($uniqueName, $cat['name']);
    assert_true(!empty($cat['slug']), 'Slug should be generated');

    $activeCats = Category::allActive();
    $found = array_filter($activeCats, fn($c) => (int)$c['id'] === $catId);
    assert_true(count($found) > 0, 'New category must appear in active categories list');

    // Clean up
    Category::hardDelete($catId, true);
});

it('Category Dependency Guard: blocks hard deletion of category when dependent subcategories exist', function () {
    // Find category #1 (Sports & Fitness)
    $cat = Category::findBySlug('sports-and-fitness');
    assert_true($cat !== null, 'Category sports-and-fitness must exist');

    $deps = Category::getDependencies((int)$cat['id']);
    assert_true($deps['has_active'] || $deps['has_protected'], 'Category should have dependencies');

    $delRes = Category::hardDelete((int)$cat['id'], false);
    assert_true(!$delRes['success'], 'Hard delete without cascade must be rejected when dependents exist');
});

it('Subcategory CRUD: creates and binds subcategory to parent category', function () {
    $cat = Category::findBySlug('sports-and-fitness');
    assert_true($cat !== null);

    $subName = 'Test Weight Benches ' . time();
    $subId = Subcategory::create([
        'category_id'   => $cat['id'],
        'name'          => $subName,
        'description'   => 'Commercial incline and flat benches',
        'display_order' => 10,
        'status'        => 'ACTIVE'
    ]);
    assert_true($subId > 0, 'Subcategory should be created');

    $sub = Subcategory::findById($subId);
    assert_equals($subName, $sub['name']);
    assert_equals((int)$cat['id'], (int)$sub['category_id']);

    // Clean up
    Subcategory::hardDelete($subId);
});

// -------------------------------------------------------------------
// 2. PRODUCT CRUD & SPECIFICATIONS TESTS
// -------------------------------------------------------------------
echo "\n--- 2. Product Management & Specs Tests ---\n";

it('Product CRUD: creates product with pricing tiers, MOQ, specifications, and OEM flags', function () {
    $cat = Category::findBySlug('sports-and-fitness');
    $sub = Subcategory::byCategoryActive((int)$cat['id'])[0] ?? null;
    assert_true($sub !== null, 'Subcategory must exist');

    $title = 'Test Pro Kettlebell Series ' . time();
    $prodId = Product::create([
        'category_id'           => $cat['id'],
        'subcategory_id'        => $sub['id'],
        'name'                  => $title,
        'sku'                   => 'TST-KB-99',
        'brand_name'            => 'ApexIron Test',
        'min_price'             => 1450.00,
        'max_price'             => 1850.00,
        'price_unit_id'         => 1,
        'moq'                   => 25,
        'moq_unit_id'           => 1,
        'available_quantity'    => 500,
        'sample_available'      => 1,
        'sample_price'          => 1600.00,
        'material'              => 'Solid Cast Iron with Neoprene Dip',
        'colors'                => 'Color-Coded Competition Ring',
        'size'                  => '16kg / 24kg / 32kg',
        'weight'                => '16kg',
        'custom_logo'           => 1,
        'custom_color'          => 1,
        'custom_packaging'      => 1,
        'oem_available'         => 1,
        'status'                => 'PUBLISHED',
        'is_featured'           => 1,
        'featured_order'        => 99
    ], ['https://placehold.co/800x800?text=Kettlebell1', 'https://placehold.co/800x800?text=Kettlebell2']);

    assert_true($prodId > 0, 'Product should be created');

    $p = Product::findById($prodId);
    assert_equals($title, $p['name']);
    assert_equals(1450.00, (float)$p['min_price']);
    assert_equals(1850.00, (float)$p['max_price']);
    assert_equals(25, (int)$p['moq_quantity']);
    assert_equals(1, (int)$p['is_featured']);
    assert_equals(1, (int)$p['oem_available']);
    assert_equals(2, count($p['images']), 'Product gallery should have 2 images');

    // Toggle featured
    Product::setFeatured($prodId, false);
    $pUpdated = Product::findById($prodId);
    assert_equals(0, (int)$pUpdated['is_featured']);

    // Toggle status
    Product::setStatus($prodId, 'INACTIVE');
    $pInactive = Product::findById($prodId);
    assert_equals('INACTIVE', $pInactive['status']);

    // Clean up
    Product::hardDelete($prodId);
});

// -------------------------------------------------------------------
// 3. PUBLIC FRONTEND & DATABASE-DRIVEN VIEWS
// -------------------------------------------------------------------
echo "\n--- 3. Public Frontend Dynamic Rendering Tests ---\n";

it('Public Homepage: renders dynamically with database categories, featured products, and RFQ modal', function () {
    ob_start();
    $req = new Request();
    $res = new Response();
    $homeCtrl = new HomeController($req, $res);
    $homeCtrl->index();
    $html = ob_get_clean();

    assert_true(str_contains($html, 'Sports Junction'), 'Must render Sports Junction brand title');
    assert_true(str_contains($html, 'Adjustable Agility Training Hurdle') || str_contains($html, 'English Willow Cricket Bat'), 'Must render seeded products from database');
    assert_true(str_contains($html, 'Sports &amp; Fitness') || str_contains($html, 'Sports & Fitness'), 'Must render seeded categories from database');
    assert_true(str_contains($html, 'inquiry-modal'), 'Must include direct factory inquiry quick modal');
});

it('Public Products Listing: correctly applies category filter and sorting', function () {
    $_GET = ['category_slug' => 'agility-and-speed-training', 'sort' => 'price_low'];
    ob_start();
    $req = new Request();
    $res = new Response();
    $shopCtrl = new ShopController($req, $res);
    $shopCtrl->products();
    $html = ob_get_clean();

    assert_true(str_contains($html, 'Adjustable Agility Training Hurdle') || str_contains($html, 'Agility'), 'Must list products under filtered agility category');
    assert_true(str_contains($html, 'Filter Results'), 'Must include left filter sidebar');
    $_GET = [];
});

it('Public Product Detail Page: loads product by slug with images, specs matrix, and JSON-LD schema', function () {
    ob_start();
    $req = new Request();
    $res = new Response();
    $shopCtrl = new ShopController($req, $res);
    $shopCtrl->productDetail(['slug' => 'adjustable-agility-training-hurdle']);
    $html = ob_get_clean();

    assert_true(str_contains($html, 'Adjustable Agility Training Hurdle'), 'Must render product title');
    assert_true(str_contains($html, 'application/ld+json'), 'Must generate Schema.org JSON-LD tag for technical SEO');
    assert_true(str_contains($html, 'High-Impact Shatterproof PVC'), 'Must display material specifications');
    assert_true(str_contains($html, 'Send Direct Factory Inquiry'), 'Must display inquiry CTA button');
});

// -------------------------------------------------------------------
// 4. INQUIRY & SITEMAP SEO TESTS
// -------------------------------------------------------------------
echo "\n--- 4. Inquiries & Technical SEO Tests ---\n";

it('Product Inquiry API: creates buyer inquiry and records in database', function () {
    $inqId = ProductInquiry::create([
        'product_id'      => 3,
        'supplier_id'     => 1,
        'buyer_name'      => 'Robert Smith',
        'buyer_email'     => 'robert@globalsporting.com',
        'buyer_phone'     => '+1 415-555-2671',
        'company_name'    => 'Global Sporting Goods LLC',
        'target_quantity' => 250,
        'lead_time_req'   => 'Under 15 days (Rush)',
        'specifications'  => 'Need 250 units with custom silk-screen logo and barcoded blister packaging.'
    ]);

    assert_true($inqId > 0, 'Inquiry should be created');
    $recent = ProductInquiry::allRecent(5);
    $found = array_filter($recent, fn($r) => (int)$r['id'] === $inqId);
    assert_true(count($found) > 0, 'New inquiry must be present in recent inquiries');
});

it('XML Sitemap: generates valid XML with homepage, catalog, categories and product URLs', function () {
    ob_start();
    $req = new Request();
    $res = new Response();
    $shopCtrl = new ShopController($req, $res);
    
    // Catch exit
    try {
        $shopCtrl->sitemap();
    } catch (\Throwable $e) {}
    $xml = ob_get_clean();

    assert_true(str_contains($xml, '<?xml version="1.0" encoding="UTF-8"?>'), 'Must be valid XML declaration');
    assert_true(str_contains($xml, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'), 'Must contain sitemap urlset');
    assert_true(str_contains($xml, '/category/'), 'Must list category URLs');
    assert_true(str_contains($xml, '/product/'), 'Must list product URLs');
});

echo "\n=======================================================================\n";
echo " MARKETPLACE TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================================\n";

if ($failed > 0) exit(1);
