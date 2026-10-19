<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Mailer;
use App\Helpers\Env;
use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\SiteSetting;

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
            'totalVerifiedCount'  => Product::getTotalVerifiedCount(),
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

        $pageTitle = $category['name'] . ' — Verified Indian Manufacturers & Wholesale Suppliers';
        $metaDescription = $category['description'] ?? "Source {$category['name']} directly from verified factory manufacturing plants in India at wholesale ex-factory pricing.";

        $this->render('frontend/products', [
            'pageTitle'           => $pageTitle,
            'metaDescription'     => $metaDescription,
            'categories'          => $categories,
            'products'            => $result['products'],
            'total'               => $result['total'],
            'totalVerifiedCount'  => Product::getTotalVerifiedCount(),
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

        $pageTitle = $subcategory['name'] . ' Wholesale Manufacturers — ' . $category['name'];
        $metaDescription = $subcategory['description'] ?? "Factory direct sourcing for {$subcategory['name']} with custom logo OEM printing and low MOQ.";

        $this->render('frontend/products', [
            'pageTitle'           => $pageTitle,
            'metaDescription'     => $metaDescription,
            'categories'          => $categories,
            'products'            => $result['products'],
            'total'               => $result['total'],
            'totalVerifiedCount'  => Product::getTotalVerifiedCount(),
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

        $currentSupplierId = \App\Models\Supplier::currentSupplierId();
        if ($currentSupplierId > 0) {
            if (!empty($input['product_id'])) {
                $prodCheck = Product::findById((int)$input['product_id']);
                if ($prodCheck && (int)($prodCheck['supplier_id'] ?? 0) === $currentSupplierId) {
                    $this->json(['success' => false, 'message' => 'You cannot submit inquiries on your own products.'], 403);
                    return;
                }
            }
            if (!empty($input['supplier_id']) && (int)$input['supplier_id'] === $currentSupplierId) {
                $this->json(['success' => false, 'message' => 'You cannot submit inquiries to your own supplier store.'], 403);
                return;
            }
        }

        $user = $this->authUser();
        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $liveUser = \App\Models\User::findById((int)$user['id']);
            if ($liveUser && ($liveUser['status'] ?? '') === 'INACTIVE') {
                $this->json(['success' => false, 'message' => 'Your buyer account is currently deactivated. Please contact support.'], 403);
                return;
            }
            $input['buyer_user_id'] = (int)$user['id'];
            if (empty($input['buyer_email'])) {
                $input['buyer_email'] = $liveUser['email'] ?? $user['email'];
            }
            if (empty($input['buyer_name'])) {
                $input['buyer_name'] = $liveUser['name'] ?? $user['name'];
            }
            if (empty($input['company_name']) && !empty($liveUser['company_name'])) {
                $input['company_name'] = $liveUser['company_name'];
            }
            if (empty($input['buyer_phone']) && !empty($liveUser['phone'])) {
                $input['buyer_phone'] = ($liveUser['country_code'] ?? '+91') . ' ' . $liveUser['phone'];
            }
            if (empty($input['country_id']) && !empty($liveUser['country_id'])) {
                $input['country_id'] = (int)$liveUser['country_id'];
            }
            if (empty($input['country_name']) && !empty($liveUser['country_name'])) {
                $input['country_name'] = $liveUser['country_name'];
            }
        }

        // Auto-resolve supplier_id and source from product if product_id is passed
        if (!empty($input['product_id'])) {
            $input['enquiry_source'] = 'PRODUCT_PAGE';
            $prod = Product::findById((int)$input['product_id']);
            if ($prod) {
                if (empty($input['supplier_id']) && !empty($prod['supplier_id'])) {
                    $input['supplier_id'] = (int)$prod['supplier_id'];
                }
                if ($currentSupplierId > 0 && (int)($prod['supplier_id'] ?? 0) === $currentSupplierId) {
                    $this->json(['success' => false, 'message' => 'You cannot submit inquiries on your own products.'], 403);
                    return;
                }
                if (empty($input['subject'])) {
                    $input['subject'] = 'Product Inquiry: ' . ($prod['title'] ?? $prod['name'] ?? '');
                }
            }
        } else {
            if (empty($input['enquiry_source']) || !in_array($input['enquiry_source'], ['GENERAL', 'CONTACT_US', 'RFQ', 'FAST_OEM'], true)) {
                $input['enquiry_source'] = 'GENERAL';
            }
            if (empty($input['subject'])) {
                $input['subject'] = match ($input['enquiry_source']) {
                    'RFQ' => 'Request for Quotation (RFQ)',
                    'FAST_OEM' => 'Fast OEM Customization Inquiry',
                    default => 'General Bulk Requirement / RFQ'
                };
            }
        }

        // Resolve and validate Country
        $countryId = !empty($input['country_id']) ? (int)$input['country_id'] : null;
        $countryName = trim((string)($input['country_name'] ?? ''));
        if ($countryId && empty($countryName)) {
            $c = \App\Models\Country::findById($countryId);
            if ($c) $countryName = $c['name'];
        } elseif (!$countryId && !empty($countryName)) {
            $c = \App\Models\Country::fetch("SELECT id, name FROM countries WHERE LOWER(name) = LOWER(:name) LIMIT 1", [':name' => $countryName]);
            if ($c) {
                $countryId = (int)$c['id'];
                $countryName = $c['name'];
            }
        }

        if (empty($countryId) && empty($countryName)) {
            $this->json(['success' => false, 'message' => 'Please select your country.'], 422);
            return;
        }

        $input['country_id'] = $countryId;
        $input['country_name'] = $countryName;

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

        // Handle File Attachment Upload (Task 13)
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                $this->json(['success' => false, 'message' => 'Attachment exceeds the 10MB file size limit.'], 422);
                return;
            }

            $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMimes, true)) {
                $this->json(['success' => false, 'message' => 'Invalid attachment format. Allowed formats: PDF, DOC, DOCX, JPG, PNG, WEBP.'], 422);
                return;
            }

            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/inquiries';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
            $savedFileName = 'inq_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destination = $uploadDir . '/' . $savedFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $input['attachment_path'] = 'storage/uploads/inquiries/' . $savedFileName;
                $input['attachment_name'] = $cleanOriginalName;
                $input['attachment_size'] = (int) $file['size'];
                $input['attachment_mime'] = $mime;
            } else {
                $this->json(['success' => false, 'message' => 'Failed to store attachment. Please try again.'], 500);
                return;
            }
        } elseif (!empty($_FILES['attachment']['error']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $this->json(['success' => false, 'message' => 'File upload error occurred. Please try again.'], 422);
            return;
        }

        try {
            $id = ProductInquiry::create($input);
            $input['id'] = $id;

            // Task 6: Transactional Email Notifications
            try {
                $buyerSent = Mailer::sendEnquiryConfirmationToBuyer($input);
                $supplierSent = false;
                if (!empty($input['supplier_id'])) {
                    $supplier = Supplier::findById((int)$input['supplier_id']);
                    if ($supplier) {
                        $supplierSent = Mailer::sendEnquiryNotificationToSupplier($input, $supplier);
                    }
                }
                $siteSettings = SiteSetting::getAllKeyed();
                $adminEmail = $siteSettings['contact_email'] ?? Env::get('SUPER_ADMIN_EMAIL', 'support@sportsjunction.com');
                $adminSent = Mailer::sendEnquiryNotificationToAdmin($input, $adminEmail);

                $notifyStatus = ($buyerSent || $supplierSent || $adminSent) ? 'SENT' : 'FAILED';
                ProductInquiry::execute("
                    UPDATE product_inquiries 
                    SET email_notified_at = NOW(), email_notification_status = :status 
                    WHERE id = :id
                ", [':status' => $notifyStatus, ':id' => $id]);
            } catch (\Throwable $mailErr) {
                ProductInquiry::execute("
                    UPDATE product_inquiries 
                    SET email_notified_at = NOW(), email_notification_status = 'FAILED', email_notification_error = :err 
                    WHERE id = :id
                ", [':err' => $mailErr->getMessage(), ':id' => $id]);
            }

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
     * Public Contact Us Page (GET /contact)
     */
    public function contact(): void
    {
        $categories = Category::allActive();
        $user = $this->authUser();
        $buyerDetails = null;
        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $buyerDetails = \App\Models\User::findById((int)$user['id']);
        }

        $countries = \App\Models\Country::allActive();

        $this->render('frontend/contact', [
            'pageTitle'       => 'Contact Us — Sports Junction B2B Manufacturing Marketplace',
            'metaDescription' => 'Contact Sports Junction for bulk sports manufacturing enquiries, supplier partnerships, or B2B procurement assistance.',
            'categories'      => $categories,
            'buyerDetails'    => $buyerDetails,
            'countries'       => $countries,
        ], 'frontend');
    }

    /**
     * Submit Contact Us Form (POST /api/contact)
     */
    public function submitContact(): void
    {
        $input = $this->request->all();
        $user = $this->authUser();

        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $liveUser = \App\Models\User::findById((int)$user['id']);
            if ($liveUser && ($liveUser['status'] ?? '') === 'INACTIVE') {
                $this->json(['success' => false, 'message' => 'Your buyer account is currently deactivated.'], 403);
                return;
            }
            $input['buyer_user_id'] = (int)$user['id'];
            if (empty($input['buyer_name'])) {
                $input['buyer_name'] = $liveUser['name'] ?? $user['name'];
            }
            if (empty($input['buyer_email'])) {
                $input['buyer_email'] = $liveUser['email'] ?? $user['email'];
            }
            if (empty($input['company_name']) && !empty($liveUser['company_name'])) {
                $input['company_name'] = $liveUser['company_name'];
            }
            if (empty($input['country_id']) && !empty($liveUser['country_id'])) {
                $input['country_id'] = (int)$liveUser['country_id'];
            }
            if (empty($input['country_name']) && !empty($liveUser['country_name'])) {
                $input['country_name'] = $liveUser['country_name'];
            }
        }

        $name        = trim((string)($input['buyer_name'] ?? $input['name'] ?? ''));
        $email       = trim((string)($input['buyer_email'] ?? $input['email'] ?? ''));
        $phone       = trim((string)($input['buyer_phone'] ?? $input['phone'] ?? ''));
        $company     = trim((string)($input['company_name'] ?? ''));
        $subject     = trim((string)($input['subject'] ?? 'General Contact Us Message'));
        $message     = trim((string)($input['specifications'] ?? $input['message'] ?? ''));

        // Resolve Country
        $countryId   = !empty($input['country_id']) ? (int)$input['country_id'] : null;
        $countryName = trim((string)($input['country_name'] ?? ''));
        if ($countryId && empty($countryName)) {
            $c = \App\Models\Country::findById($countryId);
            if ($c) $countryName = $c['name'];
        } elseif (!$countryId && !empty($countryName)) {
            $c = \App\Models\Country::fetch("SELECT id, name FROM countries WHERE LOWER(name) = LOWER(:name) LIMIT 1", [':name' => $countryName]);
            if ($c) {
                $countryId = (int)$c['id'];
                $countryName = $c['name'];
            }
        }

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($message) < 5) {
            $this->json(['success' => false, 'message' => 'Please provide your Full Name, a valid Email Address, and a detailed Message (at least 5 characters).'], 422);
            return;
        }

        if (empty($countryId) && empty($countryName)) {
            $this->json(['success' => false, 'message' => 'Please select your country.'], 422);
            return;
        }

        try {
            $inquiryData = [
                'enquiry_source'    => 'CONTACT_US',
                'product_id'        => null,
                'supplier_id'       => null,
                'buyer_user_id'     => $input['buyer_user_id'] ?? null,
                'buyer_name'        => $name,
                'buyer_email'       => $email,
                'buyer_phone'       => $phone ?: null,
                'country_id'        => $countryId,
                'country_name'      => $countryName,
                'company_name'      => $company ?: null,
                'subject'           => $subject,
                'target_quantity'   => max(1, (int)($input['target_quantity'] ?? 1)),
                'delivery_location' => !empty($input['delivery_location']) ? trim((string)$input['delivery_location']) : null,
                'specifications'    => $message,
                'status'            => 'SUBMITTED',
            ];

            $id = ProductInquiry::create($inquiryData);
            $inquiryData['id'] = $id;

            // Task 7: Send Contact Us Emails
            try {
                $userSent = Mailer::sendContactUsConfirmationToUser($inquiryData);

                $siteSettings = SiteSetting::getAllKeyed();
                $adminEmail = $siteSettings['contact_email'] ?? Env::get('SUPER_ADMIN_EMAIL', 'support@sportsjunction.com');
                $adminSent = Mailer::sendContactUsNotificationToAdmin($inquiryData, $adminEmail);

                $notifyStatus = ($userSent || $adminSent) ? 'SENT' : 'FAILED';
                ProductInquiry::execute("
                    UPDATE product_inquiries 
                    SET email_notified_at = NOW(), email_notification_status = :status 
                    WHERE id = :id
                ", [':status' => $notifyStatus, ':id' => $id]);
            } catch (\Throwable $mailErr) {
                ProductInquiry::execute("
                    UPDATE product_inquiries 
                    SET email_notified_at = NOW(), email_notification_status = 'FAILED', email_notification_error = :err 
                    WHERE id = :id
                ", [':err' => $mailErr->getMessage(), ':id' => $id]);
            }

            $this->json([
                'success' => true,
                'id'      => $id,
                'message' => 'Thank you for contacting Sports Junction! Your message (#ENQ-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . ') has been received by our Super Admin desk.',
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to submit Contact Us message: ' . $e->getMessage()], 500);
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

    /**
     * Public Dedicated Supplier Storefront Page
     * Shows supplier verification, credentials, factory details, and all uploaded products
     */
    public function supplierProfile(array $params = []): void
    {
        $id = (int) ($params['id'] ?? $this->request->get('id', 0));
        if ($id <= 0) {
            $this->response->redirect('/products');
            return;
        }

        $supplier = \App\Models\Supplier::findById($id);
        if (!$supplier || ($supplier['status'] ?? '') !== 'ACTIVE' || ($supplier['approval_status'] ?? '') !== 'APPROVED') {
            $this->response->setStatusCode(404);
            $this->render('errors/404', ['message' => 'Supplier storefront not found or currently inactive.'], 'frontend');
            return;
        }

        $products = \App\Models\Product::getSupplierPublishedProducts($id);

        $this->render('frontend/supplier_profile', [
            'pageTitle' => ($supplier['company_name'] ?? 'Supplier') . ' — Factory Storefront & Catalog | Sports Junction',
            'supplier'  => $supplier,
            'products'  => $products,
        ], 'frontend');
    }
}

