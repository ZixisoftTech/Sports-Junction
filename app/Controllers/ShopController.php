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
     * All Categories Directory (/categories)
     */
    public function allCategories(): void
    {
        $categories = Category::allActive();

        $this->render('frontend/categories', [
            'pageTitle'       => 'All Sports Categories & Industrial Sourcing Sectors | Sports Junction B2B',
            'metaDescription' => 'Explore all verified Indian sports manufacturing categories, subcategories, and athletic equipment sectors.',
            'categories'      => $categories,
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
            'isInSortList'    => $isFavourite,
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
        $reqSource = strtoupper(trim((string)($input['enquiry_source'] ?? '')));
        $incomingSubject = trim((string)($input['subject'] ?? ''));
        $isSampleRequest = ($reqSource === 'SAMPLE' || stripos($incomingSubject, 'Prototype Sample') !== false || stripos($incomingSubject, 'Sample Order') !== false);

        if ($isSampleRequest) {
            $input['enquiry_source'] = 'SAMPLE';
        }

        if (!empty($input['product_id'])) {
            if (!$isSampleRequest) {
                $input['enquiry_source'] = 'PRODUCT_PAGE';
            }
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
                    $prefix = ($input['enquiry_source'] === 'SAMPLE') ? 'Prototype Sample Order: ' : 'Product Inquiry: ';
                    $input['subject'] = $prefix . ($prod['title'] ?? $prod['name'] ?? '');
                }
                if ($input['enquiry_source'] === 'SAMPLE' && (!isset($input['target_price']) || $input['target_price'] === '') && !empty($prod['sample_price'])) {
                    $input['target_price'] = (float)$prod['sample_price'];
                }
            }
        } else {
            if ($isSampleRequest) {
                $input['enquiry_source'] = 'SAMPLE';
            } elseif (empty($input['enquiry_source']) || !in_array($input['enquiry_source'], ['GENERAL', 'CONTACT_US', 'RFQ', 'FAST_OEM', 'SAMPLE', 'SUPPORT'], true)) {
                $input['enquiry_source'] = 'GENERAL';
            }
            if (empty($input['subject'])) {
                $input['subject'] = match ($input['enquiry_source']) {
                    'RFQ' => 'Request for Quotation (RFQ)',
                    'FAST_OEM' => 'Fast OEM Customization Inquiry',
                    'SAMPLE' => 'Order Prototype Sample',
                    'SUPPORT' => 'Help & Support Request',
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
        $isAjax = $this->request->isJson() || $this->request->isAjax() || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

        $csrf = $input['csrf_token'] ?? $input['_csrf'] ?? $this->request->header('X-CSRF-Token');
        if (!CSRF::validate($csrf)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.'], 403);
                return;
            }
            \App\Helpers\Session::flash('error', 'Security token invalid or expired. Please refresh the page.');
            $this->redirect('/contact');
            return;
        }

        $user = $this->authUser();

        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $liveUser = \App\Models\User::findById((int)$user['id']);
            if ($liveUser && ($liveUser['status'] ?? '') === 'INACTIVE') {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'Your buyer account is currently deactivated.'], 403);
                    return;
                }
                \App\Helpers\Session::flash('error', 'Your buyer account is currently deactivated.');
                $this->redirect('/contact');
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
            $errMsg = 'Please provide your Full Name, a valid Email Address, and a detailed Message (at least 5 characters).';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $errMsg], 422);
                return;
            }
            \App\Helpers\Session::flash('error', $errMsg);
            $this->redirect('/contact');
            return;
        }

        if (empty($countryId) && empty($countryName)) {
            $errMsg = 'Please select your country.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $errMsg], 422);
                return;
            }
            \App\Helpers\Session::flash('error', $errMsg);
            $this->redirect('/contact');
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

            // Send notification email
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

            $successMsg = 'Thank you! Your message has been sent. Our team will get back to you shortly.';
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'id'      => $id,
                    'message' => $successMsg,
                ]);
                return;
            }
            \App\Helpers\Session::flash('success', $successMsg);
            $this->redirect('/contact');
        } catch (\Throwable $e) {
            $errMsg = 'Failed to submit Contact Us message: ' . $e->getMessage();
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $errMsg], 500);
                return;
            }
            \App\Helpers\Session::flash('error', $errMsg);
            $this->redirect('/contact');
        }
    }

    /**
     * Public Support & Help Centre Page (GET /support)
     */
    public function support(): void
    {
        $categories = Category::allActive();
        $user = $this->authUser();
        $buyerDetails = null;
        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $buyerDetails = \App\Models\User::findById((int)$user['id']);
        }

        $countries = \App\Models\Country::allActive();

        $this->render('frontend/support', [
            'pageTitle'       => 'Help Centre & Support Desk — Sports Junction',
            'metaDescription' => 'Get help with your B2B sports manufacturing enquiries, supplier orders, samples, or account assistance from our support team.',
            'categories'      => $categories,
            'buyerDetails'    => $buyerDetails,
            'countries'       => $countries,
        ], 'frontend');
    }

    /**
     * Submit Support Ticket / Enquiry (POST /api/support or POST /support)
     */
    public function submitSupport(): void
    {
        $input = $this->request->all();
        $isAjax = $this->request->isJson() || $this->request->isAjax() || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

        $csrf = $input['csrf_token'] ?? $input['_csrf'] ?? $this->request->header('X-CSRF-Token');
        if (!CSRF::validate($csrf)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.'], 403);
                return;
            }
            \App\Helpers\Session::flash('error', 'Security token invalid or expired. Please refresh the page.');
            $this->redirect('/support');
            return;
        }

        $user = $this->authUser();

        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $liveUser = \App\Models\User::findById((int)$user['id']);
            if ($liveUser && ($liveUser['status'] ?? '') === 'INACTIVE') {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'Your buyer account is currently deactivated.'], 403);
                    return;
                }
                \App\Helpers\Session::flash('error', 'Your buyer account is currently deactivated.');
                $this->redirect('/support');
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

        $name     = trim((string)($input['buyer_name'] ?? $input['name'] ?? ''));
        $email    = trim((string)($input['buyer_email'] ?? $input['email'] ?? ''));
        $phone    = trim((string)($input['buyer_phone'] ?? $input['phone'] ?? ''));
        $company  = trim((string)($input['company_name'] ?? ''));
        $category = trim((string)($input['support_category'] ?? 'General Support'));
        $subject  = trim((string)($input['subject'] ?? ''));
        $message  = trim((string)($input['specifications'] ?? $input['message'] ?? ''));

        if (!empty($category) && stripos($subject, $category) === false) {
            $fullSubject = "[{$category}] " . ($subject ?: 'Support Request');
        } else {
            $fullSubject = $subject ?: 'Support Request';
        }

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
            $errMsg = 'Please provide your Full Name, a valid Email Address, and an Issue Description (at least 5 characters).';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $errMsg], 422);
                return;
            }
            \App\Helpers\Session::flash('error', $errMsg);
            $this->redirect('/support');
            return;
        }

        if (empty($countryId) && empty($countryName)) {
            $errMsg = 'Please select your country.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $errMsg], 422);
                return;
            }
            \App\Helpers\Session::flash('error', $errMsg);
            $this->redirect('/support');
            return;
        }

        // Handle File Attachment Upload if present
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentSize = null;
        $attachmentMime = null;

        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                $errMsg = 'Attachment exceeds the 10MB file size limit.';
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => $errMsg], 422);
                    return;
                }
                \App\Helpers\Session::flash('error', $errMsg);
                $this->redirect('/support');
                return;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMimes, true)) {
                $errMsg = 'Invalid attachment format. Allowed formats: PDF, DOC, DOCX, JPG, PNG, WEBP.';
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => $errMsg], 422);
                    return;
                }
                \App\Helpers\Session::flash('error', $errMsg);
                $this->redirect('/support');
                return;
            }

            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/inquiries';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
            $savedFileName = 'support_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destination = $uploadDir . '/' . $savedFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $attachmentPath = 'storage/uploads/inquiries/' . $savedFileName;
                $attachmentName = $cleanOriginalName;
                $attachmentSize = (int) $file['size'];
                $attachmentMime = $mime;
            }
        }

        try {
            $inquiryData = [
                'enquiry_source'    => 'SUPPORT',
                'product_id'        => null,
                'supplier_id'       => null,
                'buyer_user_id'     => $input['buyer_user_id'] ?? null,
                'buyer_name'        => $name,
                'buyer_email'       => $email,
                'buyer_phone'       => $phone ?: null,
                'country_id'        => $countryId,
                'country_name'      => $countryName,
                'company_name'      => $company ?: null,
                'subject'           => $fullSubject,
                'target_quantity'   => 1,
                'delivery_location' => !empty($input['delivery_location']) ? trim((string)$input['delivery_location']) : null,
                'specifications'    => $message,
                'status'            => 'SUBMITTED',
                'attachment_path'   => $attachmentPath,
                'attachment_name'   => $attachmentName,
                'attachment_size'   => $attachmentSize,
                'attachment_mime'   => $attachmentMime,
            ];

            $id = ProductInquiry::create($inquiryData);
            $inquiryData['id'] = $id;

            $successMsg = 'Thank you! Your support ticket has been submitted (ID: SUP-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . '). Our support desk will reach out shortly.';
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'id'      => $id,
                    'message' => $successMsg,
                ]);
                return;
            }
            \App\Helpers\Session::flash('success', $successMsg);
            $this->redirect('/support');
        } catch (\Throwable $e) {
            $errMsg = 'Failed to submit support enquiry: ' . $e->getMessage();
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $errMsg], 500);
                return;
            }
            \App\Helpers\Session::flash('error', $errMsg);
            $this->redirect('/support');
        }
    }

    /**
     * Public Suggestions & Feedback Page (GET /feedback-suggestions)
     */
    public function feedback(): void
    {
        $categories = Category::allActive();
        $user = $this->authUser();
        $buyerDetails = null;
        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $buyerDetails = \App\Models\User::findById((int)$user['id']);
        }

        $countries = \App\Models\Country::allActive();

        $this->render('frontend/feedback_suggestions', [
            'pageTitle'       => 'Suggestions & Feedback — Sports Junction',
            'metaDescription' => 'We value your feedback. Help us improve Sports Junction by sharing your suggestions, experience, and ideas. Your feedback helps us build a better sports manufacturing and sourcing marketplace.',
            'categories'      => $categories,
            'buyerDetails'    => $buyerDetails,
            'countries'       => $countries,
        ], 'frontend');
    }

    /**
     * Submit Suggestions & Feedback Form (POST /api/feedback or POST /feedback-suggestions)
     */
    public function submitFeedback(): void
    {
        $input = $this->request->all();
        $isAjax = $this->request->isJson() || $this->request->isAjax() || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

        $csrf = $input['csrf_token'] ?? $input['_csrf'] ?? $this->request->header('X-CSRF-Token');
        if (!CSRF::validate($csrf)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.'], 403);
                return;
            }
            \App\Helpers\Session::flash('error', 'Security token invalid or expired. Please refresh the page.');
            $this->redirect('/feedback-suggestions');
            return;
        }

        $name = trim((string)($input['name'] ?? ''));
        $company = trim((string)($input['company_name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $phone = trim((string)($input['phone'] ?? ''));
        $countryId = !empty($input['country_id']) ? (int)$input['country_id'] : null;
        $countryName = trim((string)($input['country_name'] ?? ''));
        $feedbackType = trim((string)($input['feedback_type'] ?? 'General Feedback'));
        $subject = trim((string)($input['subject'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));
        $consent = !empty($input['consent_contact']);

        // Country resolution
        if ($countryId && empty($countryName)) {
            $c = \App\Models\Country::findById($countryId);
            if ($c) $countryName = $c['name'];
        }

        // Validation
        if (strlen($name) < 2) {
            $errMsg = 'Please provide your Full Name (at least 2 characters).';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errMsg = 'Please provide a valid Business Email Address.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
        }

        if (empty($countryId) && empty($countryName)) {
            $errMsg = 'Please select your Country.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
        }

        if (empty($subject) || strlen($subject) < 3) {
            $errMsg = 'Please provide a Subject for your suggestion or feedback.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
        }

        if (empty($message) || strlen($message) < 5) {
            $errMsg = 'Please provide detailed feedback (at least 5 characters).';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
        }

        if (!$consent) {
            $errMsg = 'Please confirm consent to be contacted regarding this feedback.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
        }

        // Optional file attachment handling
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentSize = null;
        $attachmentMime = null;

        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                $errMsg = 'Attachment exceeds the 10MB file size limit.';
                if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
                \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMimes, true)) {
                $errMsg = 'Invalid attachment format. Allowed formats: PDF, JPG, PNG, WEBP.';
                if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
                \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions'); return;
            }

            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/feedback';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
            $savedFileName = 'fb_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destination = $uploadDir . '/' . $savedFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $attachmentPath = 'storage/uploads/feedback/' . $savedFileName;
                $attachmentName = $cleanOriginalName;
                $attachmentSize = (int)$file['size'];
                $attachmentMime = $mime;
            }
        }

        try {
            $feedbackData = [
                'name'            => $name,
                'company_name'    => $company ?: null,
                'email'           => $email,
                'phone'           => $phone ?: null,
                'country_id'      => $countryId,
                'country_name'    => $countryName ?: 'India',
                'feedback_type'   => $feedbackType,
                'subject'         => $subject,
                'message'         => $message,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_size' => $attachmentSize,
                'attachment_mime' => $attachmentMime,
                'consent_contact' => 1,
            ];

            $feedbackId = \App\Models\SiteFeedback::create($feedbackData);
            if ($feedbackId <= 0) {
                throw new \Exception('Failed to insert feedback record.');
            }

            $savedRow = \App\Models\SiteFeedback::findById($feedbackId);
            $refId = $savedRow['reference_id'] ?? ('SFB-' . date('Ym') . '-' . str_pad((string)$feedbackId, 5, '0', STR_PAD_LEFT));

            // Notify Super Admin via Email
            try {
                $siteSettings = SiteSetting::getAllKeyed();
                $adminEmail = $siteSettings['contact_email'] ?? $siteSettings['admin_notification_email'] ?? Env::get('SUPER_ADMIN_EMAIL', 'support@sportsjunction.com');
                Mailer::sendFeedbackNotificationToAdmin($savedRow ?? $feedbackData, $adminEmail);
            } catch (\Throwable $mailErr) {
                @error_log("SiteFeedback email notification error: " . $mailErr->getMessage());
            }

            $successMsg = "Thank you! Your feedback has been received (Reference ID: {$refId}). Our product and operations team review all submissions.";
            if ($isAjax) {
                $this->json([
                    'success'      => true,
                    'id'           => $feedbackId,
                    'reference_id' => $refId,
                    'message'      => $successMsg,
                ]);
                return;
            }
            \App\Helpers\Session::flash('success', $successMsg);
            $this->redirect('/feedback-suggestions');
        } catch (\Throwable $e) {
            $errMsg = 'Failed to submit feedback: ' . $e->getMessage();
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 500); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/feedback-suggestions');
        }
    }

    /**
     * Public Careers Page (GET /careers)
     */
    public function careers(): void
    {
        $categories = Category::allActive();
        $user = $this->authUser();
        $buyerDetails = null;
        if ($user && ($user['role'] ?? '') === 'BUYER') {
            $buyerDetails = \App\Models\User::findById((int)$user['id']);
        }

        $countries = \App\Models\Country::allActive();
        $jobOpenings = \App\Models\JobOpening::allActive();

        $this->render('frontend/careers', [
            'pageTitle'       => 'Careers at Sports Junction — Join Our Mission',
            'metaDescription' => 'Build your career with Sports Junction. Join our team and contribute to transforming the sports manufacturing and sourcing industry.',
            'categories'      => $categories,
            'buyerDetails'    => $buyerDetails,
            'countries'       => $countries,
            'jobOpenings'     => $jobOpenings,
        ], 'frontend');
    }

    /**
     * Submit Candidate Job Application (POST /api/careers/apply or POST /careers/apply)
     */
    public function submitCareerApplication(): void
    {
        $input = $this->request->all();
        $isAjax = $this->request->isJson() || $this->request->isAjax() || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

        $csrf = $input['csrf_token'] ?? $input['_csrf'] ?? $this->request->header('X-CSRF-Token');
        if (!CSRF::validate($csrf)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.'], 403);
                return;
            }
            \App\Helpers\Session::flash('error', 'Security token invalid or expired. Please refresh the page.');
            $this->redirect('/careers');
            return;
        }

        $fullName       = trim((string)($input['full_name'] ?? ''));
        $email          = trim((string)($input['email'] ?? ''));
        $phone          = trim((string)($input['phone'] ?? ''));
        $city           = trim((string)($input['city'] ?? ''));
        $countryId      = !empty($input['country_id']) ? (int)$input['country_id'] : null;
        $countryName    = trim((string)($input['country_name'] ?? ''));
        $jobOpeningId   = !empty($input['job_opening_id']) ? (int)$input['job_opening_id'] : null;
        $position       = trim((string)($input['position_applied'] ?? 'General Application'));
        $experience     = trim((string)($input['total_experience'] ?? ''));
        $currentCompany = trim((string)($input['current_company'] ?? ''));
        $currentJobTitle= trim((string)($input['current_job_title'] ?? ''));
        $expectedSalary = trim((string)($input['expected_salary'] ?? ''));
        $noticePeriod   = trim((string)($input['notice_period'] ?? '30 Days'));
        $linkedinUrl    = trim((string)($input['linkedin_url'] ?? ''));
        $portfolioUrl   = trim((string)($input['portfolio_url'] ?? ''));
        $coverLetter    = trim((string)($input['cover_letter'] ?? ''));
        $consent        = !empty($input['consent_privacy']);

        // Country resolution
        if ($countryId && empty($countryName)) {
            $c = \App\Models\Country::findById($countryId);
            if ($c) $countryName = $c['name'];
        }

        // Required Validations
        if (strlen($fullName) < 2) {
            $errMsg = 'Please provide your Full Name.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errMsg = 'Please provide a valid Email Address.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (empty($phone) || strlen($phone) < 7) {
            $errMsg = 'Please provide a valid Mobile Phone Number.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (empty($city)) {
            $errMsg = 'Please provide your Current Location / City.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (empty($countryId) && empty($countryName)) {
            $errMsg = 'Please select your Country.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (empty($position)) {
            $errMsg = 'Please select the Position Applied For.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (empty($experience)) {
            $errMsg = 'Please select your Total Work Experience.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (empty($noticePeriod)) {
            $errMsg = 'Please specify your Notice Period / Availability.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        if (!$consent) {
            $errMsg = 'Please confirm consent to the Privacy Policy.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        // Resume Upload Validation (Mandatory)
        if (empty($_FILES['resume']['name']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            $errMsg = 'Please upload your Resume (PDF, DOC, or DOCX up to 10MB).';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        $resumeFile = $_FILES['resume'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($resumeFile['size'] > $maxSize) {
            $errMsg = 'Resume file exceeds the 10MB file size limit.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        $ext = strtolower(pathinfo($resumeFile['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $resumeFile['tmp_name']);
        finfo_close($finfo);

        $allowedExts = ['pdf', 'doc', 'docx'];
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMimes, true)) {
            $errMsg = 'Invalid resume format. Allowed formats: PDF, DOC, DOCX.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 422); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/resumes';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $resumeFile['name']);
        $savedFileName = 'resume_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destination = $uploadDir . '/' . $savedFileName;

        if (!@move_uploaded_file($resumeFile['tmp_name'], $destination) && !copy($resumeFile['tmp_name'], $destination)) {
            $errMsg = 'Failed to securely store your resume. Please try again.';
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 500); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers'); return;
        }

        try {
            $appData = [
                'job_opening_id'    => $jobOpeningId,
                'position_applied'  => $position,
                'full_name'         => $fullName,
                'email'             => $email,
                'phone'             => $phone,
                'city'              => $city,
                'country_id'        => $countryId,
                'country_name'      => $countryName ?: 'India',
                'total_experience'  => $experience,
                'current_company'   => $currentCompany ?: null,
                'current_job_title' => $currentJobTitle ?: null,
                'expected_salary'   => $expectedSalary ?: null,
                'notice_period'     => $noticePeriod,
                'linkedin_url'      => $linkedinUrl ?: null,
                'portfolio_url'     => $portfolioUrl ?: null,
                'resume_path'       => 'storage/uploads/resumes/' . $savedFileName,
                'resume_name'       => $cleanOriginalName,
                'resume_size'       => (int)$resumeFile['size'],
                'resume_mime'       => $mime,
                'cover_letter'      => $coverLetter ?: null,
                'consent_privacy'   => 1,
            ];

            $appId = \App\Models\JobApplication::create($appData);
            if ($appId <= 0) {
                throw new \Exception('Failed to record candidate application.');
            }

            $savedApp = \App\Models\JobApplication::findById($appId);
            $appNo = $savedApp['application_no'] ?? ('APP-' . date('Ym') . '-' . str_pad((string)$appId, 5, '0', STR_PAD_LEFT));

            // Notify Super Admin via Email
            try {
                $siteSettings = SiteSetting::getAllKeyed();
                $adminEmail = $siteSettings['contact_email'] ?? $siteSettings['admin_notification_email'] ?? Env::get('SUPER_ADMIN_EMAIL', 'support@sportsjunction.com');
                Mailer::sendCareerApplicationNotificationToAdmin($savedApp ?? $appData, $adminEmail);
                Mailer::sendCareerApplicationConfirmationToCandidate($savedApp ?? $appData);
            } catch (\Throwable $mailErr) {
                @error_log("JobApplication email notification error: " . $mailErr->getMessage());
            }

            $successMsg = "Thank you! Your application for {$position} has been received (Reference: {$appNo}). Our talent team will review your profile.";
            if ($isAjax) {
                $this->json([
                    'success'        => true,
                    'id'             => $appId,
                    'application_no' => $appNo,
                    'message'        => $successMsg,
                ]);
                return;
            }
            \App\Helpers\Session::flash('success', $successMsg);
            $this->redirect('/careers');
        } catch (\Throwable $e) {
            $errMsg = 'Failed to submit application: ' . $e->getMessage();
            if ($isAjax) { $this->json(['success' => false, 'message' => $errMsg], 500); return; }
            \App\Helpers\Session::flash('error', $errMsg); $this->redirect('/careers');
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

