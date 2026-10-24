<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Mailer;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Models\BulkProductEnquiry;
use App\Models\BuyerFavourite;
use App\Models\BuyerRecentlyViewed;
use App\Models\Country;
use App\Models\Otp;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Models\User;

class BuyerPortalController extends Controller
{
    private function getBuyer(): array
    {
        $user = $this->authUser();
        if (!$user || !in_array($user['role'] ?? '', ['BUYER', 'SUPPLIER', 'ADMIN', 'SUPER_ADMIN'])) {
            $this->redirect('/login');
            exit;
        }
        $fullUser = User::findById((int)$user['id']);
        return $fullUser ?: $user;
    }

    public function dashboard(): void
    {
        $buyer = $this->getBuyer();
        $enquiries = ProductInquiry::getBuyerEnquiries((int)$buyer['id'], $buyer['email']);
        $sampleEnquiries = ProductInquiry::getBuyerSampleEnquiries((int)$buyer['id'], $buyer['email']);
        $sortList = BuyerFavourite::getBuyerSortList((int)$buyer['id']);
        $recentViews = BuyerRecentlyViewed::getRecent((int)$buyer['id'], 6);
        $bulkEnquiriesData = BulkProductEnquiry::getByBuyer((int)$buyer['id'], 5, 0);

        $counts = [
            'enquiries'      => count($enquiries),
            'samples'        => count($sampleEnquiries),
            'sortList'       => count($sortList),
            'favourites'     => count($sortList), // backward compat
            'bulkEnquiries'  => $bulkEnquiriesData['total'] ?? 0,
            'recent'         => count($recentViews),
        ];

        $this->render('buyer/dashboard', [
            'pageTitle'      => 'Buyer Dashboard — Sports Junction',
            'buyer'          => $buyer,
            'counts'         => $counts,
            'enquiries'      => array_slice($enquiries, 0, 5),
            'sortList'       => array_slice($sortList, 0, 4),
            'favourites'     => array_slice($sortList, 0, 4), // backward compat
            'bulkEnquiries'  => $bulkEnquiriesData['records'] ?? [],
            'recentViews'    => $recentViews,
        ], 'frontend');
    }

    public function enquiries(): void
    {
        $buyer = $this->getBuyer();
        $enquiries = ProductInquiry::getBuyerEnquiries((int)$buyer['id'], $buyer['email']);

        $this->render('buyer/enquiries', [
            'pageTitle' => 'My Product Inquiries — Sports Junction',
            'buyer'     => $buyer,
            'enquiries' => $enquiries,
        ], 'frontend');
    }

    public function sampleEnquiries(): void
    {
        $buyer = $this->getBuyer();
        $sampleEnquiries = ProductInquiry::getBuyerSampleEnquiries((int)$buyer['id'], $buyer['email']);

        $this->render('buyer/sample_enquiries', [
            'pageTitle'       => 'My Prototype Sample Orders — Sports Junction',
            'buyer'           => $buyer,
            'sampleEnquiries' => $sampleEnquiries,
        ], 'frontend');
    }

    public function enquiryDetail(array $params): void
    {
        $buyer = $this->getBuyer();
        $id = (int)($params['id'] ?? 0);
        $inquiry = ProductInquiry::findById($id);

        if (!$inquiry) {
            Session::flash('error', 'Inquiry not found.');
            $this->redirect('/buyer/enquiries');
            return;
        }

        // Ownership check: Contact Us messages only match if submitted while logged in (buyer_user_id matches)
        $isContactUs = (($inquiry['enquiry_source'] ?? '') === 'CONTACT_US');
        $matchesUser = ((int)($inquiry['buyer_user_id'] ?? 0) === (int)$buyer['id']);
        $matchesEmail = !$isContactUs && (strtolower(trim($inquiry['buyer_email'] ?? '')) === strtolower(trim($buyer['email'] ?? '')));

        if (!$matchesUser && !$matchesEmail) {
            Session::flash('error', 'Unauthorized access to this enquiry.');
            $this->redirect('/buyer/enquiries');
            return;
        }

        $replies = ProductInquiry::getReplies($id);

        $this->render('buyer/enquiry_detail', [
            'pageTitle' => "Inquiry #{$id} Details — Sports Junction",
            'buyer'     => $buyer,
            'inquiry'   => $inquiry,
            'replies'   => $replies,
        ], 'frontend');
    }

    public function replyEnquiry(array $params): void
    {
        $buyer = $this->getBuyer();
        $id = (int)($params['id'] ?? 0);
        $inquiry = ProductInquiry::findById($id);

        if (!$inquiry) {
            $this->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
            return;
        }

        $isContactUs = (($inquiry['enquiry_source'] ?? '') === 'CONTACT_US');
        $matchesUser = ((int)($inquiry['buyer_user_id'] ?? 0) === (int)$buyer['id']);
        $matchesEmail = !$isContactUs && (strtolower(trim($inquiry['buyer_email'] ?? '')) === strtolower(trim($buyer['email'] ?? '')));

        if (!$matchesUser && !$matchesEmail) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $message = trim((string)$this->request->input('message', ''));
        if (empty($message)) {
            $this->json(['success' => false, 'message' => 'Reply message cannot be empty.'], 422);
            return;
        }

        ProductInquiry::addReply($id, (int)$buyer['id'], 'BUYER', $message);

        $this->json(['success' => true, 'message' => 'Your reply has been dispatched to the supplier.']);
    }

    private function getOptionalBuyer(): ?array
    {
        $user = $this->authUser();
        if (!$user) {
            return null;
        }
        $fullUser = User::findById((int)$user['id']);
        return $fullUser ?: $user;
    }

    /**
     * GET /buyer/sort-list, GET /sort-list & GET /buyer/favourites
     * View all products in Sort List with selection and bulk enquiry capability
     * (Role: BUYER Mandatory)
     */
    public function sortList(): void
    {
        $buyer = $this->getBuyer();
        $sortProducts = BuyerFavourite::getBuyerSortList((int)$buyer['id']);
        $allCountries = Country::allActive();

        $this->render('buyer/sort_list', [
            'pageTitle'    => 'My Sort List — Sports Junction',
            'buyer'        => $buyer,
            'sortProducts' => $sortProducts,
            'favourites'   => $sortProducts, // backward compat
            'countries'    => $allCountries,
        ], 'frontend');
    }

    public function favourites(): void
    {
        $this->sortList();
    }

    /**
     * POST /api/buyer/sort-list/toggle, POST /api/sort-list/toggle & POST /api/buyer/favourites/toggle
     * Toggle item in Sort List (Buyer Login Mandatory)
     */
    public function toggleSortList(): void
    {
        $user = Session::get('auth_user');
        if (!$user || !in_array($user['role'] ?? '', ['BUYER', 'SUPER_ADMIN'])) {
            $this->json([
                'success'        => false,
                'login_required' => true,
                'message'        => 'Please sign in to your Buyer Account to shortlist products.'
            ], 401);
            return;
        }

        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $productId = (int)$this->request->input('product_id', 0);
        if ($productId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid product.'], 422);
            return;
        }

        $isInList = BuyerFavourite::isInSortList((int)$buyer['id'], $productId);
        if ($isInList) {
            BuyerFavourite::removeFromSortList((int)$buyer['id'], $productId);
            $count = BuyerFavourite::countForBuyer((int)$buyer['id']);
            $this->json([
                'success'      => true, 
                'in_sort_list' => false, 
                'favourited'   => false, 
                'count'        => $count, 
                'message'      => 'Removed from your Sort List.'
            ]);
        } else {
            $ok = BuyerFavourite::addToSortList((int)$buyer['id'], $productId);
            if ($ok) {
                $count = BuyerFavourite::countForBuyer((int)$buyer['id']);
                $this->json([
                    'success'      => true, 
                    'in_sort_list' => true, 
                    'favourited'   => true, 
                    'count'        => $count, 
                    'message'      => 'Added to your Sort List!'
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'This product is currently unavailable or unlisted.'], 400);
            }
        }
    }

    public function toggleFavourite(): void
    {
        $this->toggleSortList();
    }

    /**
     * DELETE/POST /buyer/sort-list/{id}
     */
    public function removeSortItem(array $params): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $productId = (int)($params['id'] ?? 0);
        BuyerFavourite::removeFromSortList((int)$buyer['id'], $productId);
        $count = BuyerFavourite::countForBuyer((int)$buyer['id']);

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'count' => $count, 'message' => 'Removed from your Sort List.']);
        } else {
            Session::flash('success', 'Product removed from your Sort List.');
            $this->redirect('/buyer/sort-list');
        }
    }

    public function removeFavourite(array $params): void
    {
        $this->removeSortItem($params);
    }

    /**
     * POST /api/buyer/sort-list/remove-selected
     */
    public function removeSelectedSortItems(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $rawIds = $this->request->input('product_ids', []);
        if (is_string($rawIds)) {
            $rawIds = json_decode($rawIds, true) ?: explode(',', $rawIds);
        }
        $productIds = array_filter(array_map('intval', (array)$rawIds), fn($id) => $id > 0);

        if (empty($productIds)) {
            $this->json(['success' => false, 'message' => 'No products selected for removal.'], 422);
            return;
        }

        BuyerFavourite::removeMultiple((int)$buyer['id'], $productIds);
        $count = BuyerFavourite::countForBuyer((int)$buyer['id']);

        $this->json([
            'success' => true,
            'count'   => $count,
            'message' => count($productIds) . ' product(s) removed from your Sort List.'
        ]);
    }

    /**
     * POST /api/buyer/sort-list/clear
     */
    public function clearSortList(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        BuyerFavourite::clearSortList((int)$buyer['id']);

        $this->json([
            'success' => true,
            'count'   => 0,
            'message' => 'Your Sort List has been cleared.'
        ]);
    }

    /**
     * POST /api/buyer/bulk-enquiry/submit
     * Submit One Bulk Enquiry covering multiple selected products
     */
    public function submitBulkEnquiry(): void
    {
        $buyer = $this->getBuyer();
        $buyerUserId = (int)$buyer['id'];
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        // Buyer Shared Information
        $buyerName = trim((string)$this->request->input('buyer_name', $buyer['name'] ?? ''));
        $buyerEmail = trim((string)$this->request->input('buyer_email', $buyer['email'] ?? ''));
        $buyerPhone = trim((string)$this->request->input('buyer_phone', $buyer['phone'] ?? ''));
        $companyName = trim((string)$this->request->input('company_name', $buyer['company_name'] ?? ''));
        $countryId = (int)$this->request->input('country_id', 0);
        $deliveryLocation = trim((string)$this->request->input('delivery_location', ''));
        $overallMessage = trim((string)$this->request->input('overall_message', ''));
        $subject = trim((string)$this->request->input('subject', ''));

        if (empty($buyerName)) {
            $this->json(['success' => false, 'message' => 'Your Full Name is required.'], 422);
            return;
        }

        if (empty($buyerEmail) || !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'A valid Business Email is required.'], 422);
            return;
        }

        if ($countryId <= 0) {
            $this->json(['success' => false, 'message' => 'Please select your destination Country.'], 422);
            return;
        }

        $country = Country::findById($countryId);
        $countryName = $country['name'] ?? null;

        // Parse Products / Items Data
        $itemsInput = $this->request->input('items', []);
        if (empty($itemsInput) || !is_array($itemsInput)) {
            $rawProducts = $this->request->input('products');
            if ($rawProducts) {
                $itemsInput = is_string($rawProducts) ? json_decode($rawProducts, true) : $rawProducts;
            }
        }

        if (empty($itemsInput) || !is_array($itemsInput)) {
            $this->json(['success' => false, 'message' => 'No products selected for bulk enquiry.'], 422);
            return;
        }

        // Extract product IDs
        $requestedProductIds = [];
        foreach ($itemsInput as $key => $item) {
            $pId = (int)($item['product_id'] ?? $key);
            if ($pId > 0) {
                $requestedProductIds[] = $pId;
            }
        }
        $requestedProductIds = array_unique($requestedProductIds);

        if (empty($requestedProductIds)) {
            $this->json(['success' => false, 'message' => 'No valid products selected.'], 422);
            return;
        }

        // Validate products in DB: Must be in buyer's sort list AND currently eligible
        $eligibleProducts = BuyerFavourite::getEligibleProductsForBulk((int)$buyer['id'], $requestedProductIds);
        if (count($eligibleProducts) !== count($requestedProductIds)) {
            $this->json([
                'success' => false,
                'message' => 'One or more selected products are no longer available or have been unlisted by their manufacturer. Please deselect them and try again.'
            ], 422);
            return;
        }

        // Map eligible products by ID
        $productMap = [];
        foreach ($eligibleProducts as $ep) {
            $productMap[(int)$ep['id']] = $ep;
        }

        // Validate individual product requirements (quantities, MOQ, specifications)
        $validatedItems = [];
        foreach ($itemsInput as $key => $item) {
            $pId = (int)($item['product_id'] ?? $key);
            if (!isset($productMap[$pId])) {
                continue;
            }
            $pData = $productMap[$pId];
            $moq = (int)($pData['moq_quantity'] ?? 1);
            $moqUnit = $pData['moq_unit_name'] ?? 'units';

            $targetQty = (int)($item['target_quantity'] ?? $item['quantity'] ?? 0);
            if ($targetQty < $moq) {
                $this->json([
                    'success' => false,
                    'message' => "Order quantity for '{$pData['name']}' must be at least the Minimum Order Quantity ({$moq} {$moqUnit})."
                ], 422);
                return;
            }

            $specs = trim((string)($item['specifications'] ?? $item['custom_specifications'] ?? ''));
            if (empty($specs)) {
                $this->json([
                    'success' => false,
                    'message' => "Please provide custom specifications & packaging requirements for '{$pData['name']}'."
                ], 422);
                return;
            }

            $targetPrice = !empty($item['target_price']) ? (float)$item['target_price'] : null;

            // Check if product-specific file attachment was uploaded
            $itemAttachment = null;
            $fileKey = "attachment_product_{$pId}";
            if (!empty($_FILES[$fileKey]['name']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $itemAttachment = $this->handleFileUpload($_FILES[$fileKey]);
                if (isset($itemAttachment['error'])) {
                    $this->json(['success' => false, 'message' => "File error for '{$pData['name']}': " . $itemAttachment['error']], 422);
                    return;
                }
            }

            $validatedItems[] = [
                'product_id'      => $pId,
                'supplier_id'     => (int)$pData['supplier_id'],
                'product_name'    => $pData['name'],
                'supplier_name'   => $pData['supplier_name'] ?? 'Verified Factory',
                'target_quantity' => $targetQty,
                'moq_quantity'    => $moq,
                'moq_unit_name'   => $moqUnit,
                'target_price'    => $targetPrice,
                'currency'        => 'USD',
                'specifications'  => $specs,
                'attachment_path' => $itemAttachment['path'] ?? null,
                'attachment_name' => $itemAttachment['name'] ?? null,
                'attachment_size' => $itemAttachment['size'] ?? null,
                'attachment_mime' => $itemAttachment['mime'] ?? null,
            ];
        }

        // Handle shared attachment if uploaded
        $sharedAttachment = null;
        if (!empty($_FILES['shared_attachment']['name']) && $_FILES['shared_attachment']['error'] === UPLOAD_ERR_OK) {
            $sharedAttachment = $this->handleFileUpload($_FILES['shared_attachment']);
            if (isset($sharedAttachment['error'])) {
                $this->json(['success' => false, 'message' => $sharedAttachment['error']], 422);
                return;
            }
        } elseif (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $sharedAttachment = $this->handleFileUpload($_FILES['attachment']);
            if (isset($sharedAttachment['error'])) {
                $this->json(['success' => false, 'message' => $sharedAttachment['error']], 422);
                return;
            }
        }

        try {
            $buyerPayload = [
                'buyer_user_id'     => $buyerUserId,
                'buyer_name'        => $buyerName,
                'buyer_email'       => $buyerEmail,
                'buyer_phone'       => $buyerPhone,
                'company_name'      => $companyName,
                'country_id'        => $countryId,
                'country_name'      => $countryName,
                'delivery_location' => $deliveryLocation,
                'subject'           => !empty($subject) ? $subject : "Bulk Sourcing Enquiry (" . count($validatedItems) . " Products)",
                'overall_message'   => $overallMessage,
            ];

            $result = BulkProductEnquiry::createBulkEnquiry($buyerPayload, $validatedItems, $sharedAttachment);

            // Remove submitted products from Sort List
            BuyerFavourite::removeMultiple((int)$buyer['id'], $requestedProductIds);
            $newSortCount = BuyerFavourite::countForBuyer((int)$buyer['id']);

            $this->json([
                'success'      => true,
                'reference_no' => $result['reference_no'],
                'item_count'   => $result['item_count'],
                'sort_count'   => $newSortCount,
                'message'      => "Your bulk product enquiry has been transmitted successfully! Reference: {$result['reference_no']}"
            ]);
        } catch (\Throwable $e) {
            error_log("submitBulkEnquiry error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Failed to process bulk enquiry submission. Please try again.'], 500);
        }
    }

    /**
     * Helper to handle file upload safely
     */
    private function handleFileUpload(array $file): array
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return ['error' => 'Attachment exceeds the 10MB file size limit.'];
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
            return ['error' => 'Invalid attachment format. Allowed formats: PDF, DOC, DOCX, JPG, PNG, WEBP.'];
        }

        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/inquiries';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
        $savedFileName = 'bulk_inq_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destination = $uploadDir . '/' . $savedFileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'path' => 'storage/uploads/inquiries/' . $savedFileName,
                'name' => $cleanOriginalName,
                'size' => (int) $file['size'],
                'mime' => $mime,
            ];
        }

        return ['error' => 'Failed to store uploaded attachment.'];
    }

    /**
     * GET /buyer/bulk-enquiries
     * View buyer's submitted bulk enquiries (strictly owner access, no chat/reply)
     */
    public function bulkEnquiries(): void
    {
        $buyer = $this->getBuyer();
        $page = max(1, (int)$this->request->get('page', 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $data = BulkProductEnquiry::getByBuyer((int)$buyer['id'], $limit, $offset);

        $this->render('buyer/bulk_enquiries', [
            'pageTitle'     => 'My Bulk Enquiries — Sports Junction',
            'buyer'         => $buyer,
            'enquiries'     => $data['records'],
            'total'         => $data['total'],
            'page'          => $page,
            'perPage'       => $limit,
            'totalPages'    => (int)ceil($data['total'] / max(1, $limit)),
        ], 'frontend');
    }

    /**
     * GET /buyer/bulk-enquiries/{id}
     * View single bulk enquiry details (strictly for the owner)
     */
    public function bulkEnquiryDetail(array $params): void
    {
        $buyer = $this->getBuyer();
        $id = (int)($params['id'] ?? 0);

        $enquiry = BulkProductEnquiry::getDetailForBuyer($id, (int)$buyer['id']);
        if (!$enquiry) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'Bulk enquiry not found or access denied.'], 404);
                return;
            }
            Session::flash('error', 'Bulk enquiry not found.');
            $this->redirect('/buyer/bulk-enquiries');
            return;
        }

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'enquiry' => $enquiry]);
            return;
        }

        $this->render('buyer/bulk_enquiries', [
            'pageTitle'     => 'Bulk Enquiry Details (' . ($enquiry['reference_no'] ?? '') . ') — Sports Junction',
            'buyer'         => $buyer,
            'activeEnquiry' => $enquiry,
        ], 'frontend');
    }

    public function recentlyViewed(): void
    {
        $buyer = $this->getBuyer();
        $recent = BuyerRecentlyViewed::getRecent((int)$buyer['id'], 20);

        $this->render('buyer/recently_viewed', [
            'pageTitle' => 'Recently Viewed Equipment — Sports Junction',
            'buyer'     => $buyer,
            'products'  => $recent,
        ], 'frontend');
    }

    public function clearRecentViews(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        BuyerRecentlyViewed::clearHistory((int)$buyer['id']);

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'message' => 'Browsing history cleared.']);
        } else {
            Session::flash('success', 'Browsing history cleared successfully.');
            $this->redirect('/buyer/recently-viewed');
        }
    }

    public function profile(): void
    {
        $buyer = $this->getBuyer();
        $countries = Country::allActive();
        $latestChange = \App\Models\ProfileChangeRequest::getLatestForUser((int)$buyer['id']);

        $this->render('buyer/profile', [
            'pageTitle'    => 'My Account & Profile — Sports Junction',
            'buyer'        => $buyer,
            'countries'    => $countries,
            'latestChange' => $latestChange,
        ], 'frontend');
    }

    public function updateProfile(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'name'         => 'required|max:150',
            'company_name' => 'required|max:150',
            'phone'        => 'required|max:30',
            'country_id'   => 'required|integer',
        ];

        if (!$validator->validate($rules)) {
            Session::flash('error', $validator->firstError() ?? 'Validation failed.');
            $this->redirect('/buyer/profile');
            return;
        }

        $currentValues = [
            'name'         => $buyer['name'] ?? '',
            'company_name' => $buyer['company_name'] ?? '',
            'phone'        => $buyer['phone'] ?? '',
            'country_code' => $buyer['country_code'] ?? '+91',
            'country_id'   => (int)($buyer['country_id'] ?? 1),
            'state'        => $buyer['state'] ?? '',
            'city'         => $buyer['city'] ?? '',
        ];

        $requestedChanges = [
            'name'         => trim((string)$input['name']),
            'company_name' => trim((string)$input['company_name']),
            'phone'        => trim((string)$input['phone']),
            'country_code' => trim((string)($input['country_code'] ?? '+91')),
            'country_id'   => (int)$input['country_id'],
            'state'        => trim((string)($input['state'] ?? '')),
            'city'         => trim((string)($input['city'] ?? '')),
        ];

        // Directly update buyer profile in users table
        User::execute("
            UPDATE users SET
                name         = :name,
                company_name = :company_name,
                phone        = :phone,
                country_code = :country_code,
                country_id   = :country_id,
                state        = :state,
                city         = :city,
                updated_at   = NOW()
            WHERE id = :id AND role = 'BUYER'
        ", [
            ':name'         => $requestedChanges['name'],
            ':company_name' => $requestedChanges['company_name'],
            ':phone'        => $requestedChanges['phone'],
            ':country_code' => $requestedChanges['country_code'],
            ':country_id'   => $requestedChanges['country_id'],
            ':state'        => $requestedChanges['state'],
            ':city'         => $requestedChanges['city'],
            ':id'           => (int)$buyer['id'],
        ]);

        // Keep auth_user session synchronized
        $authUser = Session::get('auth_user');
        if ($authUser && (int)$authUser['id'] === (int)$buyer['id']) {
            $authUser['name'] = $requestedChanges['name'];
            $authUser['company_name'] = $requestedChanges['company_name'];
            $authUser['phone'] = $requestedChanges['phone'];
            $authUser['country_code'] = $requestedChanges['country_code'];
            $authUser['country_id'] = $requestedChanges['country_id'];
            $authUser['state'] = $requestedChanges['state'];
            $authUser['city'] = $requestedChanges['city'];
            Session::set('auth_user', $authUser);
        }

        // Record approved entry for historical tracking
        \App\Models\ProfileChangeRequest::createApproved(
            (int)$buyer['id'],
            null,
            'BUYER',
            $currentValues,
            $requestedChanges
        );

        Session::flash('success', 'Your buyer profile has been updated successfully!');
        $this->redirect('/buyer/profile');
    }

    public function changePassword(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $currentPass = (string)$this->request->post('current_password', '');
        $newPass = (string)$this->request->post('new_password', '');
        $confPass = (string)$this->request->post('password_confirmation', '');

        if (!User::verifyPassword($currentPass, $buyer['password'])) {
            Session::flash('error', 'Current password is incorrect.');
            $this->redirect('/buyer/profile#security');
            return;
        }

        if (strlen($newPass) < 8) {
            Session::flash('error', 'New password must be at least 8 characters in length.');
            $this->redirect('/buyer/profile#security');
            return;
        }

        if ($newPass !== $confPass) {
            Session::flash('error', 'New password and confirmation do not match.');
            $this->redirect('/buyer/profile#security');
            return;
        }

        User::updatePassword((int)$buyer['id'], $newPass);
        Mailer::sendBuyerPasswordUpdated($buyer['email'], $buyer['name'] ?? 'Valued Buyer');
        Session::flash('success', 'Your password has been changed securely.');
        $this->redirect('/buyer/profile');
    }
}
