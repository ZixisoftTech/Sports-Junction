<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

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

use App\Models\Product;
use App\Models\ProductCertificate;
use App\Models\Category;

echo "=== SPORTS JUNCTION PRODUCT TAG / CERTIFICATE BADGE VERIFICATION ===\n\n";

$slug = 'adidas-fifa-world-cup-26-league-football';
$product = Product::findBySlug($slug);

if (!$product) {
    echo "FAIL: Product not found with slug: {$slug}\n";
    exit(1);
}

$productId = (int)$product['id'];
echo "Found product ID: {$productId} ({$product['name']})\n";

// Helper to render view
function renderProductDetailHtml(array $prod): string {
    $product = $prod;
    $primaryImg = \App\Helpers\Uploader::imageUrl($product['primary_image'] ?? null);
    $galleryImages = $product['images'] ?? [];
    $productCertificates = !empty($product['certificates']) ? $product['certificates'] : ProductCertificate::getForProduct((int)$product['id']);
    $supplierCertificationTags = [];
    $baseUrl = \App\Core\Response::baseUrl();
    $relatedProducts = [];
    $isFavourite = false;

    ob_start();
    include dirname(__DIR__) . '/app/Views/frontend/product_detail.php';
    return ob_get_clean();
}

// 1. Initial State Check (Assigned Certificate 8: Top Rated)
echo "\n[TEST 1] Verifying product 32 has Top Rated certificate attached...\n";
ProductCertificate::syncForProduct($productId, [8]);
$product = Product::findBySlug($slug);
$certs = $product['certificates'] ?? [];

if (empty($certs)) {
    echo "FAIL: Certificates array is empty on product!\n";
    exit(1);
}

$topRated = null;
foreach ($certs as $c) {
    if ((int)$c['id'] === 8 || ($c['name'] ?? '') === 'Top Rated') {
        $topRated = $c;
        break;
    }
}

if (!$topRated) {
    echo "FAIL: Top Rated certificate not found in product certificates!\n";
    exit(1);
}
echo "  PASS: Top Rated certificate attached. Icon: {$topRated['icon']}\n";

// 2. Render Check: Top Rated icon & badge rendered, Audited Factory Plant absent
echo "\n[TEST 2] Verifying Product Detail HTML rendering...\n";
$html = renderProductDetailHtml($product);

if (strpos($html, 'Audited Factory Plant') !== false) {
    echo "FAIL: Hardcoded 'Audited Factory Plant' badge is still present in rendered HTML!\n";
    exit(1);
}
echo "  PASS: 'Audited Factory Plant' is completely removed!\n";

if (strpos($html, 'Top Rated') === false) {
    echo "FAIL: 'Top Rated' badge text missing in rendered HTML!\n";
    exit(1);
}
echo "  PASS: 'Top Rated' badge text rendered successfully!\n";

if (strpos($html, '9bf8e2dded27eec4bc47_1792214725.png') === false) {
    echo "FAIL: Uploaded image icon path missing in rendered HTML!\n";
    exit(1);
}
echo "  PASS: Image icon for 'Top Rated' tag rendered successfully in HTML!\n";

// 3. Multi-tag Selection Check: Assign both ID 7 (Best Seller) and ID 8 (Top Rated)
echo "\n[TEST 3] Verifying multi-tag support (IDs 7 and 8)...\n";
ProductCertificate::syncForProduct($productId, [7, 8]);
$product = Product::findBySlug($slug);
$multiHtml = renderProductDetailHtml($product);

if (strpos($multiHtml, 'Top Rated') === false || strpos($multiHtml, 'Best Seller') === false) {
    echo "FAIL: Both 'Top Rated' and 'Best Seller' should render when multiple tags are assigned!\n";
    exit(1);
}
echo "  PASS: Both tags rendered simultaneously in hero badge container!\n";

// 4. Empty Tags Check: When no tags are assigned, no fake badge should display
echo "\n[TEST 4] Verifying 0 tags behavior (clean hero image)...\n";
ProductCertificate::syncForProduct($productId, []);
$product = Product::findBySlug($slug);
$emptyHtml = renderProductDetailHtml($product);

if (strpos($emptyHtml, 'Top Rated') !== false || strpos($emptyHtml, 'Best Seller') !== false || strpos($emptyHtml, 'Audited Factory Plant') !== false) {
    echo "FAIL: Badges found when none should be rendered!\n";
    exit(1);
}
echo "  PASS: No badges rendered when none selected!\n";

// Restore initial assignment (ID 8)
ProductCertificate::syncForProduct($productId, [8]);
echo "\n[RESTORE] Successfully restored Product 32 tags to [8: Top Rated].\n";

echo "\n======================================================\n";
echo "ALL PRODUCT TAG & CERTIFICATE TESTS PASSED 100%!\n";
echo "======================================================\n";
