<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Models\Subcategory;

class ShopController extends Controller
{
    /**
     * All Products listing or filtered listing
     */
    public function products(): void
    {
        $categories = Category::allActive();
        $filters = $this->request->all();

        $result = Product::getFilteredProducts($filters);

        $selectedCategory = null;
        if (!empty($filters['category_slug'])) {
            $selectedCategory = Category::findBySlug((string) $filters['category_slug']);
        }

        $selectedSubcategory = null;
        if (!empty($filters['subcategory_slug'])) {
            $selectedSubcategory = Subcategory::findBySlug((string) $filters['subcategory_slug']);
        }

        $pageTitle = 'Wholesale Sports & Fitness Equipment Products | Sports Junction B2B';
        if ($selectedCategory) {
            $pageTitle = htmlspecialchars($selectedCategory['name']) . ' Manufacturers & Wholesale Suppliers | Sports Junction';
        }

        $this->render('frontend/products', [
            'pageTitle'           => $pageTitle,
            'metaDescription'     => 'Discover verified wholesale manufacturers and OEM suppliers for commercial fitness equipment, cricket gear, inflatables, and athletic accessories with zero commission.',
            'categories'          => $categories,
            'products'            => $result['products'],
            'total'               => $result['total'],
            'currentPage'         => $result['current_page'],
            'lastPage'            => $result['last_page'],
            'from'                => $result['from'],
            'to'                  => $result['to'],
            'filters'             => $filters,
            'selectedCategory'    => $selectedCategory,
            'selectedSubcategory' => $selectedSubcategory,
        ], 'frontend');
    }

    /**
     * Category Products listing (/category/{catSlug})
     */
    public function categoryProducts(array $params): void
    {
        $catSlug = $params['catSlug'] ?? '';
        $category = Category::findBySlug($catSlug);

        if (!$category) {
            $this->response->setStatusCode(404)->send("Category not found: {$catSlug}");
            return;
        }

        $filters = $this->request->all();
        $filters['category_slug'] = $catSlug;

        $categories = Category::allActive();
        $result = Product::getFilteredProducts($filters);

        $pageTitle = htmlspecialchars($category['name']) . ' — Verified Indian Manufacturers & Wholesale Suppliers';
        $metaDescription = $category['description'] ?? "Source {$category['name']} directly from verified factory manufacturing plants in India at wholesale ex-factory pricing.";

        $this->render('frontend/products', [
            'pageTitle'           => $pageTitle,
            'metaDescription'     => $metaDescription,
            'categories'          => $categories,
            'products'            => $result['products'],
            'total'               => $result['total'],
            'currentPage'         => $result['current_page'],
            'lastPage'            => $result['last_page'],
            'from'                => $result['from'],
            'to'                  => $result['to'],
            'filters'             => $filters,
            'selectedCategory'    => $category,
            'selectedSubcategory' => null,
        ], 'frontend');
    }

    /**
     * Subcategory Products listing (/category/{catSlug}/{subSlug})
     */
    public function subcategoryProducts(array $params): void
    {
        $catSlug = $params['catSlug'] ?? '';
        $subSlug = $params['subSlug'] ?? '';

        $category = Category::findBySlug($catSlug);
        $subcategory = Subcategory::findBySlug($subSlug);

        if (!$category || !$subcategory) {
            $this->response->setStatusCode(404)->send("Category or subcategory not found");
            return;
        }

        $filters = $this->request->all();
        $filters['category_slug'] = $catSlug;
        $filters['subcategory_slug'] = $subSlug;

        $categories = Category::allActive();
        $result = Product::getFilteredProducts($filters);

        $pageTitle = htmlspecialchars($subcategory['name']) . ' Wholesale Manufacturers — ' . htmlspecialchars($category['name']);
        $metaDescription = $subcategory['description'] ?? "Factory direct sourcing for {$subcategory['name']} with custom logo OEM printing and low MOQ.";

        $this->render('frontend/products', [
            'pageTitle'           => $pageTitle,
            'metaDescription'     => $metaDescription,
            'categories'          => $categories,
            'products'            => $result['products'],
            'total'               => $result['total'],
            'currentPage'         => $result['current_page'],
            'lastPage'            => $result['last_page'],
            'from'                => $result['from'],
            'to'                  => $result['to'],
            'filters'             => $filters,
            'selectedCategory'    => $category,
            'selectedSubcategory' => $subcategory,
        ], 'frontend');
    }

    /**
     * Product Detail Page (/product/{slug})
     */
    public function productDetail(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $product = Product::findBySlug($slug);

        if (!$product) {
            $this->response->setStatusCode(404)->send("Product not found or currently unavailable.");
            return;
        }

        // Increment view count
        Product::incrementViewCount((int) $product['id']);

        // Track recently viewed and favourite state for logged-in buyer
        $user = $this->authUser();
        $isFavourite = false;
        if ($user && ($user['role'] ?? '') === 'BUYER') {
            \App\Models\BuyerRecentlyViewed::recordView((int)$user['id'], (int)$product['id']);
            $isFavourite = \App\Models\BuyerFavourite::isFavourite((int)$user['id'], (int)$product['id']);
        }

        $categories = Category::allActive();
        $relatedProducts = Product::getRelatedProducts((int) $product['id'], (int) $product['category_id'], 4);

        $pageTitle = htmlspecialchars($product['name']) . ' | Factory Direct Wholesale | Sports Junction';
        $metaDescription = $product['short_description'] ?? "Wholesale inquiry for {$product['name']}. Direct factory price {$product['min_price']} - {$product['max_price']}, MOQ {$product['moq_quantity']}. OEM & custom logo available.";

        $this->render('frontend/product_detail', [
            'pageTitle'       => $pageTitle,
            'metaDescription' => $metaDescription,
            'product'         => $product,
            'categories'      => $categories,
            'relatedProducts' => $relatedProducts,
            'isFavourite'     => $isFavourite,
        ], 'frontend');
    }

    /**
     * Submit Product or General Factory Inquiry (/api/inquiries)
     */
    public function submitInquiry(): void
    {
        $input = $this->request->all();

        $user = $this->authUser();
        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $input['buyer_user_id'] = (int)$user['id'];
            if (empty($input['buyer_email'])) {
                $input['buyer_email'] = $user['email'];
            }
            if (empty($input['buyer_name'])) {
                $input['buyer_name'] = $user['name'];
            }
        }

        // Auto-resolve supplier_id from product if product_id is passed
        if (!empty($input['product_id']) && empty($input['supplier_id'])) {
            $prod = Product::findById((int)$input['product_id']);
            if ($prod && !empty($prod['supplier_id'])) {
                $input['supplier_id'] = (int)$prod['supplier_id'];
            }
        }

        $validator = Validator::make($input);
        $rules = [
            'buyer_email'     => 'required|email',
            'specifications'  => 'required|min:5',
            'target_quantity' => 'numeric',
        ];

        if (!$validator->validate($rules)) {
            $this->json(['success' => false, 'message' => $validator->firstError()], 422);
            return;
        }

        try {
            $id = ProductInquiry::create($input);
            $this->json([
                'success' => true,
                'id'      => $id,
                'message' => 'Direct factory RFQ successfully transmitted! The manufacturing engineering team will respond within 2.4 hours.'
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to record inquiry: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Dynamic XML Sitemap (/sitemap.xml)
     */
    public function sitemap(): void
    {
        $baseUrl = \App\Core\Response::baseUrl();
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $fullBase = rtrim($host . $baseUrl, '/');

        $categories = Category::allActive();
        $products = \App\Core\Database::getConnection()->query("
            SELECT slug, updated_at FROM products WHERE status IN ('PUBLISHED', 'ACTIVE') ORDER BY id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        echo "  <url>\n    <loc>{$fullBase}/</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
        // Products catalog
        echo "  <url>\n    <loc>{$fullBase}/products</loc>\n    <changefreq>daily</changefreq>\n    <priority>0.9</priority>\n  </url>\n";

        // Categories & Subcategories
        foreach ($categories as $cat) {
            $loc = "{$fullBase}/category/{$cat['slug']}";
            echo "  <url>\n    <loc>{$loc}</loc>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";

            if (!empty($cat['subcategories'])) {
                foreach ($cat['subcategories'] as $sub) {
                    $subLoc = "{$fullBase}/category/{$cat['slug']}/{$sub['slug']}";
                    echo "  <url>\n    <loc>{$subLoc}</loc>\n    <changefreq>weekly</changefreq>\n    <priority>0.7</priority>\n  </url>\n";
                }
            }
        }

        // Products
        foreach ($products as $p) {
            $pLoc = "{$fullBase}/product/{$p['slug']}";
            $lastMod = date('Y-m-d', strtotime($p['updated_at'] ?? 'now'));
            echo "  <url>\n    <loc>{$pLoc}</loc>\n    <lastmod>{$lastMod}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
        }

        echo '</urlset>';
    }
}
