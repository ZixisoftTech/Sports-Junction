<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Mailer;
use App\Helpers\Session;
use App\Helpers\Uploader;
use App\Helpers\Validator;
use App\Models\Category;
use App\Models\Country;
use App\Models\MoqUnit;
use App\Models\PriceUnit;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Supplier;

class SupplierPortalController extends Controller
{
    private function getSupplier(): array
    {
        $user = $this->authUser();
        $supplier = Supplier::findByUserId((int)($user['id'] ?? 0));
        if (!$supplier) {
            $this->redirect('/login');
            exit;
        }
        return $supplier;
    }

    public function dashboard(): void
    {
        $supplier = $this->getSupplier();
        $products = Product::getSupplierProducts((int)$supplier['id']);

        $enquiries = \App\Models\ProductInquiry::getSupplierEnquiries((int)$supplier['id']);

        $counts = [
            'total'       => count($products),
            'published'   => 0,
            'approved'    => 0,
            'pending'     => 0,
            'rejected'    => 0,
            'draft'       => 0,
            'enquiries'   => count($enquiries),
        ];

        foreach ($products as $p) {
            $st = $p['approval_status'] ?? 'DRAFT';
            $pub = $p['status'] ?? 'DRAFT';
            if ($pub === 'PUBLISHED' || $pub === 'ACTIVE') $counts['published']++;
            if ($st === 'APPROVED') $counts['approved']++;
            elseif ($st === 'PENDING_APPROVAL') $counts['pending']++;
            elseif ($st === 'REJECTED') $counts['rejected']++;
            else $counts['draft']++;
        }

        $this->render('supplier/dashboard', [
            'pageTitle' => 'Supplier Portal Dashboard — Sports Junction',
            'supplier'  => $supplier,
            'counts'    => $counts,
            'recent'    => array_slice($products, 0, 5),
            'recentEnquiries' => array_slice($enquiries, 0, 5),
        ], 'supplier');
    }

    public function products(): void
    {
        $supplier = $this->getSupplier();
        $products = Product::getSupplierProducts((int)$supplier['id']);

        $this->render('supplier/products', [
            'pageTitle' => 'My Sports Equipment Catalog — Sports Junction',
            'supplier'  => $supplier,
            'products'  => $products,
        ], 'supplier');
    }

    public function addProduct(): void
    {
        $supplier = $this->getSupplier();
        $categories = Category::allActive();
        $priceUnits = PriceUnit::allActive();
        $moqUnits = MoqUnit::allActive();
        $countries = Country::allActive();

        $this->render('supplier/product_form', [
            'pageTitle'   => 'Submit Product for Approval — Sports Junction',
            'supplier'    => $supplier,
            'categories'  => $categories,
            'priceUnits'  => $priceUnits,
            'moqUnits'    => $moqUnits,
            'countries'   => $countries,
            'product'     => null,
            'isEdit'      => false,
        ], 'supplier');
    }

    public function editProduct(array $params): void
    {
        $supplier = $this->getSupplier();
        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product || (int)$product['supplier_id'] !== (int)$supplier['id']) {
            Session::flash('error', 'Product not found or access denied.');
            $this->redirect('/supplier/products');
            return;
        }

        $categories = Category::allActive();
        $subcategories = !empty($product['category_id']) ? Subcategory::byCategory((int)$product['category_id']) : [];
        $priceUnits = PriceUnit::allActive();
        $moqUnits = MoqUnit::allActive();
        $countries = Country::allActive();

        $this->render('supplier/product_form', [
            'pageTitle'     => 'Edit Product — ' . htmlspecialchars($product['title']),
            'supplier'      => $supplier,
            'categories'    => $categories,
            'subcategories' => $subcategories,
            'priceUnits'    => $priceUnits,
            'moqUnits'      => $moqUnits,
            'countries'     => $countries,
            'product'       => $product,
            'isEdit'        => true,
        ], 'supplier');
    }

    public function storeProduct(): void
    {
        $supplier = $this->getSupplier();
        $csrfToken = (string)$this->request->post('csrf_token', '');
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired. Please try again.');
            $this->redirect('/supplier/products/add');
            return;
        }

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'name'           => 'required|max:200',
            'category_id'    => 'required|integer',
            'subcategory_id' => 'required|integer',
            'min_price'      => 'required|numeric',
            'price_unit_id'  => 'required|integer',
            'moq'            => 'required|integer',
            'moq_unit_id'    => 'required|integer',
        ];

        if (!$validator->validate($rules)) {
            Session::flash('error', $validator->firstError() ?? 'Validation failed.');
            $this->redirect('/supplier/products/add');
            return;
        }

        // Handle primary image
        $images = [];
        if (isset($_FILES['primary_image_file']) && $_FILES['primary_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $input['primary_image'] = Uploader::uploadImage($_FILES['primary_image_file'], 'products');
                $images[] = $input['primary_image'];
            } catch (\Throwable $e) {
                Session::flash('error', 'Image upload error: ' . $e->getMessage());
                $this->redirect('/supplier/products/add');
                return;
            }
        }

        // Handle gallery images
        if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
            $filesCount = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $filesCount; $i++) {
                if (!empty($_FILES['gallery_images']['tmp_name'][$i]) && is_uploaded_file($_FILES['gallery_images']['tmp_name'][$i])) {
                    $singleFile = [
                        'name'     => $_FILES['gallery_images']['name'][$i],
                        'type'     => $_FILES['gallery_images']['type'][$i],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                        'error'    => $_FILES['gallery_images']['error'][$i],
                        'size'     => $_FILES['gallery_images']['size'][$i],
                    ];
                    try {
                        $images[] = Uploader::uploadImage($singleFile, 'products');
                    } catch (\Throwable) {
                        // Skip failed individual gallery image
                    }
                }
            }
        }

        // Automatically bind supplier's own facility location
        $input['country_id'] = $supplier['country_id'];
        $input['state_id'] = $supplier['state_id'];
        $input['city_id'] = $supplier['city_id'];

        try {
            $id = Product::submitSupplierProduct($input, (int)$supplier['id'], $images);
            Mailer::sendProductSubmitted($supplier['email'], $supplier['company_name'], (string)$input['name']);

            Session::flash('success', "Product submitted successfully! It is queued for Super Admin review before publishing.");
            $this->redirect('/supplier/products');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to submit product: ' . $e->getMessage());
            $this->redirect('/supplier/products/add');
        }
    }

    public function updateProduct(array $params): void
    {
        $supplier = $this->getSupplier();
        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product || (int)$product['supplier_id'] !== (int)$supplier['id']) {
            Session::flash('error', 'Product not found or access denied.');
            $this->redirect('/supplier/products');
            return;
        }

        $csrfToken = (string)$this->request->post('csrf_token', '');
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired. Please try again.');
            $this->redirect("/supplier/products/edit/{$id}");
            return;
        }

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'name'           => 'required|max:200',
            'category_id'    => 'required|integer',
            'subcategory_id' => 'required|integer',
            'min_price'      => 'required|numeric',
            'price_unit_id'  => 'required|integer',
            'moq'            => 'required|integer',
            'moq_unit_id'    => 'required|integer',
        ];

        if (!$validator->validate($rules)) {
            Session::flash('error', $validator->firstError() ?? 'Validation failed.');
            $this->redirect("/supplier/products/edit/{$id}");
            return;
        }

        $primaryImage = null;
        if (isset($_FILES['primary_image_file']) && $_FILES['primary_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $primaryImage = Uploader::uploadImage($_FILES['primary_image_file'], 'products');
            } catch (\Throwable $e) {
                Session::flash('error', 'Image upload error: ' . $e->getMessage());
                $this->redirect("/supplier/products/edit/{$id}");
                return;
            }
        }

        $galleryImages = [];
        if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
            $filesCount = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $filesCount; $i++) {
                if (!empty($_FILES['gallery_images']['tmp_name'][$i]) && is_uploaded_file($_FILES['gallery_images']['tmp_name'][$i])) {
                    $singleFile = [
                        'name'     => $_FILES['gallery_images']['name'][$i],
                        'type'     => $_FILES['gallery_images']['type'][$i],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                        'error'    => $_FILES['gallery_images']['error'][$i],
                        'size'     => $_FILES['gallery_images']['size'][$i],
                    ];
                    try {
                        $galleryImages[] = Uploader::uploadImage($singleFile, 'products');
                    } catch (\Throwable) {
                        // Skip failed individual gallery image
                    }
                }
            }
        }

        try {
            Product::saveSupplierEdit($id, (int)$supplier['id'], $input, $primaryImage, $galleryImages);
            Mailer::sendProductSubmitted($supplier['email'], $supplier['company_name'], (string)$input['name']);

            Session::flash('success', "Product modifications saved and queued for Super Admin review.");
            $this->redirect('/supplier/products');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to update product: ' . $e->getMessage());
            $this->redirect("/supplier/products/edit/{$id}");
        }
    }

    public function enquiries(): void
    {
        $supplier = $this->getSupplier();
        $enquiries = \App\Models\ProductInquiry::getSupplierEnquiries((int)$supplier['id']);

        $this->render('supplier/enquiries', [
            'pageTitle' => 'Customer Inquiries & RFQs — Sports Junction',
            'supplier'  => $supplier,
            'enquiries' => $enquiries,
        ], 'supplier');
    }

    public function enquiryDetail(array $params): void
    {
        $supplier = $this->getSupplier();
        $id = (int)($params['id'] ?? 0);
        $inquiry = \App\Models\ProductInquiry::findById($id);

        if (!$inquiry || (int)$inquiry['supplier_id'] !== (int)$supplier['id']) {
            Session::flash('error', 'Inquiry not found or access denied.');
            $this->redirect('/supplier/enquiries');
            return;
        }

        $replies = \App\Models\ProductInquiry::getReplies($id);

        $this->render('supplier/enquiry_detail', [
            'pageTitle' => "Customer Inquiry #{$id} — Sports Junction",
            'supplier'  => $supplier,
            'inquiry'   => $inquiry,
            'replies'   => $replies,
        ], 'supplier');
    }

    public function replyEnquiry(array $params): void
    {
        $supplier = $this->getSupplier();
        $id = (int)($params['id'] ?? 0);
        $inquiry = \App\Models\ProductInquiry::findById($id);

        if (!$inquiry || (int)$inquiry['supplier_id'] !== (int)$supplier['id']) {
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

        $user = $this->authUser();
        \App\Models\ProductInquiry::addReply($id, (int)$user['id'], 'SUPPLIER', $message);

        $this->json(['success' => true, 'message' => 'Your reply has been dispatched to the buyer.']);
    }

    public function profile(): void
    {
        $supplier = $this->getSupplier();
        $countries = Country::allActive();
        $businessTypes = \App\Models\BusinessType::allActive();

        $this->render('supplier/profile', [
            'pageTitle'     => 'Factory Profile & Facility Settings — Sports Junction',
            'supplier'      => $supplier,
            'countries'     => $countries,
            'businessTypes' => $businessTypes,
        ], 'supplier');
    }

    public function updateProfile(): void
    {
        $supplier = $this->getSupplier();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'company_name'     => 'required|max:150',
            'contact_person'   => 'required|max:100',
            'email'            => 'required|email|max:191',
            'phone'            => 'required|max:30',
            'business_type_id' => 'required|integer',
            'country_id'       => 'required|integer',
            'state_id'         => 'required|integer',
            'city_id'          => 'required|integer',
        ];

        if (!$validator->validate($rules)) {
            Session::flash('error', $validator->firstError() ?? 'Validation failed.');
            $this->redirect('/supplier/profile');
            return;
        }

        $newEmail = strtolower(trim((string)$input['email']));
        if (\App\Models\User::emailExists($newEmail, (int)($supplier['user_id'] ?? 0))) {
            Session::flash('error', 'This email address is already in use by another account.');
            $this->redirect('/supplier/profile');
            return;
        }

        $emailChanged = strtolower(trim($supplier['email'])) !== $newEmail;

        Supplier::updateProfile((int)$supplier['id'], $input);

        if ($emailChanged) {
            $otp = \App\Models\Otp::generate($newEmail, 'SUPPLIER_REGISTRATION', $this->request->getIp());
            Mailer::sendSupplierOtp($newEmail, (string)$input['company_name'], $otp);
            Session::set('otp_email', $newEmail);
            Session::set('otp_purpose', 'SUPPLIER_REGISTRATION');
            Session::remove('auth_user');

            Session::flash('info', 'Your business email was changed. Please verify your new address with the passcode sent.');
            $this->redirect('/verify-email');
            return;
        }

        Session::flash('success', 'Factory profile updated successfully.');
        $this->redirect('/supplier/profile');
    }

    public function changePassword(): void
    {
        $supplier = $this->getSupplier();
        $user = $this->authUser();
        $fullUser = \App\Models\User::findById((int)$user['id']);

        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $currentPass = (string)$this->request->post('current_password', '');
        $newPass = (string)$this->request->post('new_password', '');
        $confPass = (string)$this->request->post('password_confirmation', '');

        if (!\App\Models\User::verifyPassword($currentPass, $fullUser['password'])) {
            Session::flash('error', 'Current password is incorrect.');
            $this->redirect('/supplier/profile#security');
            return;
        }

        if (strlen($newPass) < 8) {
            Session::flash('error', 'New password must be at least 8 characters in length.');
            $this->redirect('/supplier/profile#security');
            return;
        }

        if ($newPass !== $confPass) {
            Session::flash('error', 'New password and confirmation do not match.');
            $this->redirect('/supplier/profile#security');
            return;
        }

        \App\Models\User::updatePassword((int)$fullUser['id'], $newPass);
        Session::flash('success', 'Password updated successfully.');
        $this->redirect('/supplier/profile');
    }
}

