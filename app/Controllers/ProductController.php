<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Exporter;
use App\Helpers\Uploader;
use App\Helpers\Validator;
use App\Models\Category;
use App\Models\Country;
use App\Models\MoqUnit;
use App\Models\PriceUnit;
use App\Models\Product;
use App\Models\Subcategory;

class ProductController extends Controller
{
    public function index(): void
    {
        $categories = Category::allActive();
        $priceUnits = PriceUnit::allActive();
        $moqUnits = MoqUnit::allActive();
        $countries = Country::allActive();
        $suppliers = \App\Models\Supplier::allApproved();
        $metrics = Product::getApprovalMetrics();

        $this->render('products/index', [
            'pageTitle'       => 'Product Management',
            'activeMenu'      => 'products',
            'categories'      => $categories,
            'priceUnits'      => $priceUnits,
            'moqUnits'        => $moqUnits,
            'countries'       => $countries,
            'suppliers'       => $suppliers,
            'metrics'         => $metrics,
            'metaDescription' => 'Enterprise management of sports equipment catalog, factory specifications, wholesale pricing tiers and homepage featured products.',
        ]);
    }

    public function create(): void
    {
        $categories = Category::allActive();
        $priceUnits = PriceUnit::allActive();
        $moqUnits = MoqUnit::allActive();
        $countries = Country::allActive();
        $certificates = \App\Models\ProductCertificate::allActive();
        $suppliers = \App\Models\Supplier::allApproved();
        if (empty($suppliers)) {
            $suppliers = \App\Models\Supplier::allActive();
        }

        $this->render('products/form', [
            'pageTitle'              => 'Add New Product',
            'activeMenu'             => 'products',
            'categories'             => $categories,
            'priceUnits'             => $priceUnits,
            'moqUnits'               => $moqUnits,
            'countries'              => $countries,
            'suppliers'              => $suppliers,
            'certificates'           => $certificates,
            'assignedCertificateIds' => [],
            'product'                => null,
            'isEdit'                 => false,
            'subcategories'          => [],
            'metaDescription'        => 'Add new sports equipment catalog item.',
        ]);
    }

    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $product = Product::findById($id);
        if (!$product) {
            $this->redirect('/admin/products');
            return;
        }

        // If product has pending_changes from a supplier edit, merge them so Super Admin can review and edit them directly
        if (!empty($product['pending_changes'])) {
            $pending = json_decode((string)$product['pending_changes'], true);
            if (is_array($pending)) {
                $product = array_merge($product, $pending);
                if (isset($pending['name'])) {
                    $product['title'] = $pending['name'];
                }
                if (isset($pending['moq'])) {
                    $product['moq_quantity'] = $pending['moq'];
                }
                $product['has_pending_changes'] = true;
            }
        }

        $categories = Category::allActive();
        $priceUnits = PriceUnit::allActive();
        $moqUnits = MoqUnit::allActive();
        $countries = Country::allActive();
        $suppliers = \App\Models\Supplier::allApproved();

        // Ensure product's own supplier is present in dropdown even if unapproved/suspended
        $hasSupplierInList = false;
        foreach ($suppliers as $sup) {
            if ((int)$sup['id'] === (int)($product['supplier_id'] ?? 0)) {
                $hasSupplierInList = true;
                break;
            }
        }
        if (!$hasSupplierInList && !empty($product['supplier_id'])) {
            $prodSup = \App\Models\Supplier::findById((int)$product['supplier_id']);
            if ($prodSup) {
                $suppliers[] = $prodSup;
            }
        }

        $subcategories = [];
        if (!empty($product['category_id'])) {
            $subcategories = Subcategory::byCategory((int)$product['category_id']);
        }

        $certificates = \App\Models\ProductCertificate::allActive();
        $assignedCertificateIds = \App\Models\ProductCertificate::getAssignedIds($id);

        $this->render('products/form', [
            'pageTitle'              => 'Edit Product: ' . ($product['name'] ?? 'Product #' . $id),
            'activeMenu'             => 'products',
            'categories'             => $categories,
            'priceUnits'             => $priceUnits,
            'moqUnits'               => $moqUnits,
            'countries'              => $countries,
            'suppliers'              => $suppliers,
            'certificates'           => $certificates,
            'assignedCertificateIds' => $assignedCertificateIds,
            'product'                => $product,
            'isEdit'                 => true,
            'subcategories'          => $subcategories,
            'metaDescription'        => 'Edit sports equipment catalog item.',
        ]);
    }

    public function data(): void
    {
        $result = Product::getDataTable($this->request->all());
        $this->json($result);
    }

    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $product = Product::findById($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found'], 404);
            return;
        }

        $this->json(['success' => true, 'data' => $product]);
    }

    public function store(): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $input = $this->request->all();

        // Normalize category_ids and subcategory_ids
        if (!empty($input['category_ids'])) {
            if (is_string($input['category_ids'])) {
                $input['category_ids'] = array_filter(explode(',', $input['category_ids']));
            }
            if (is_array($input['category_ids']) && count($input['category_ids']) > 0) {
                $input['category_id'] = (int)$input['category_ids'][0];
            }
        }
        if (!empty($input['subcategory_ids'])) {
            if (is_string($input['subcategory_ids'])) {
                $input['subcategory_ids'] = array_filter(explode(',', $input['subcategory_ids']));
            }
            if (is_array($input['subcategory_ids']) && count($input['subcategory_ids']) > 0) {
                $input['subcategory_id'] = (int)$input['subcategory_ids'][0];
            }
        }

        $validator = Validator::make($input);
        $rules = [
            'name'          => 'required|max:200',
            'supplier_id'   => 'required|integer',
            'category_id'   => 'required|integer',
            'min_price'     => 'required|numeric',
            'price_unit_id' => 'required|integer',
            'moq'           => 'required|integer',
            'moq_unit_id'   => 'required|integer',
            'status'        => 'required|in:PUBLISHED,DRAFT,INACTIVE',
        ];

        if (!$validator->validate($rules)) {
            $this->json(['success' => false, 'errors' => $validator->errors(), 'message' => $validator->firstError()], 422);
            return;
        }

        // Handle primary image upload
        $images = [];
        if (isset($_FILES['primary_image_file']) && $_FILES['primary_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $input['primary_image'] = Uploader::uploadImage($_FILES['primary_image_file'], 'products');
                $images[] = $input['primary_image'];
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => 'Primary image upload failed: ' . $e->getMessage()], 422);
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

        try {
            $id = Product::create($input, $images);

            // Sync product certificates if provided
            if (isset($input['certificate_ids'])) {
                $certIds = is_array($input['certificate_ids']) ? $input['certificate_ids'] : array_filter(explode(',', (string)$input['certificate_ids']));
                \App\Models\ProductCertificate::syncForProduct($id, $certIds);
            }

            $this->json([
                'success' => true,
                'id'      => $id,
                'message' => 'Product published successfully.'
            ], 201);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to save product: ' . $e->getMessage()], 500);
        }
    }

    public function update(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int) ($params['id'] ?? 0);
        $product = Product::findById($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found'], 404);
            return;
        }

        $input = $this->request->all();

        // Normalize category_ids and subcategory_ids
        if (!empty($input['category_ids'])) {
            if (is_string($input['category_ids'])) {
                $input['category_ids'] = array_filter(explode(',', $input['category_ids']));
            }
            if (is_array($input['category_ids']) && count($input['category_ids']) > 0) {
                $input['category_id'] = (int)$input['category_ids'][0];
            }
        }
        if (!empty($input['subcategory_ids'])) {
            if (is_string($input['subcategory_ids'])) {
                $input['subcategory_ids'] = array_filter(explode(',', $input['subcategory_ids']));
            }
            if (is_array($input['subcategory_ids']) && count($input['subcategory_ids']) > 0) {
                $input['subcategory_id'] = (int)$input['subcategory_ids'][0];
            }
        }

        $validator = Validator::make($input);
        $rules = [
            'name'          => 'required|max:200',
            'supplier_id'   => 'required|integer',
            'category_id'   => 'required|integer',
            'min_price'     => 'required|numeric',
            'price_unit_id' => 'required|integer',
            'moq'           => 'required|integer',
            'moq_unit_id'   => 'required|integer',
            'status'        => 'required|in:PUBLISHED,DRAFT,INACTIVE',
        ];

        if (!$validator->validate($rules)) {
            $this->json(['success' => false, 'errors' => $validator->errors(), 'message' => $validator->firstError()], 422);
            return;
        }

        // Normalize checkbox flags when unchecked in HTML form submission
        foreach (['sample_available', 'custom_logo', 'custom_color', 'custom_packaging', 'oem_available', 'odm_available', 'private_label', 'is_featured'] as $flag) {
            $input[$flag] = !empty($input[$flag]) ? 1 : 0;
        }
        $input['clear_pending_changes'] = 1;

        // Supplier reassignment check
        $oldSupplierId = (int)($product['supplier_id'] ?? 0);
        $newSupplierId = (int)($input['supplier_id'] ?? 0);
        $supplierChanged = ($oldSupplierId !== $newSupplierId && $oldSupplierId > 0 && $newSupplierId > 0);

        if ($supplierChanged) {
            $newSupplier = \App\Models\Supplier::findById($newSupplierId);
            if ($newSupplier && !empty($newSupplier['user_id'])) {
                $input['submitted_by'] = (int)$newSupplier['user_id'];
            }
        }

        // Primary image upload
        if (isset($_FILES['primary_image_file']) && $_FILES['primary_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $input['primary_image'] = Uploader::uploadImage($_FILES['primary_image_file'], 'products');
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => 'Primary image upload failed: ' . $e->getMessage()], 422);
                return;
            }
        }

        // New gallery images (include any pending supplier gallery images if present)
        $newImages = [];
        if (!empty($product['pending_changes'])) {
            $pending = json_decode((string)$product['pending_changes'], true);
            if (!empty($pending['gallery_images']) && is_array($pending['gallery_images'])) {
                $newImages = $pending['gallery_images'];
            }
        }
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
                        $newImages[] = Uploader::uploadImage($singleFile, 'products');
                    } catch (\Throwable) {
                        // Skip failed
                    }
                }
            }
        }

        $deletedImageIds = [];
        if (!empty($input['deleted_image_ids'])) {
            $deletedImageIds = array_map('intval', explode(',', (string) $input['deleted_image_ids']));
        }

        try {
            Product::beginTransaction();
            Product::update($id, $input, $newImages, $deletedImageIds);

            // Sync product certificates
            $certIds = !empty($input['certificate_ids']) 
                ? (is_array($input['certificate_ids']) ? $input['certificate_ids'] : array_filter(explode(',', (string)$input['certificate_ids'])))
                : [];
            \App\Models\ProductCertificate::syncForProduct($id, $certIds);

            if ($supplierChanged) {
                \App\Helpers\AuditLogger::log('UPDATE', 'Product', $id, $product['title'] ?? ('Product #' . $id), null, "Reassigned product supplier from #{$oldSupplierId} to #{$newSupplierId}");
            }

            Product::commit();

            $this->json([
                'success' => true,
                'id'      => $id,
                'message' => 'Product updated successfully.'
            ]);
        } catch (\Throwable $e) {
            Product::rollBack();
            $this->json(['success' => false, 'message' => 'Failed to update product: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int) ($params['id'] ?? 0);
        $newStatus = trim((string) ($this->request->input('status') ?? $this->request->get('status', '')));

        if (!in_array($newStatus, ['PUBLISHED', 'DRAFT', 'INACTIVE', 'ACTIVE'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid product status.'], 400);
            return;
        }

        $success = Product::setStatus($id, $newStatus);
        if ($success) {
            $this->json(['success' => true, 'message' => "Product status updated to {$newStatus}."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update product status.'], 404);
        }
    }

    public function toggleFeatured(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int) ($params['id'] ?? 0);
        $isFeatured = (bool) ($this->request->input('is_featured') ?? $this->request->get('is_featured', false));
        $order = (int) ($this->request->input('featured_order') ?? $this->request->get('featured_order', 0));

        $success = Product::setFeatured($id, $isFeatured, $order);
        if ($success) {
            $this->json(['success' => true, 'message' => 'Homepage featured status updated.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update featured flag.'], 404);
        }
    }

    public function delete(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int) ($params['id'] ?? 0);
        $result = Product::hardDelete($id);
        $statusCode = ($result['success'] ?? false) ? 200 : 422;
        $this->json($result, $statusCode);
    }

    public function export(): void
    {
        $format = strtolower((string)$this->request->get('format', 'csv'));
        $search = trim((string)$this->request->get('search', ''));
        $statusFilter = trim((string)$this->request->get('status', ''));

        $dtResult = Product::getDataTable([
            'draw'   => 1,
            'start'  => 0,
            'length' => 0, // all
            'search' => $search,
            'status' => $statusFilter,
            'order'  => [['column' => 0, 'dir' => 'desc']],
        ]);

        $records = $dtResult['data'] ?? [];
        $headers = ['ID', 'Product Title', 'Category', 'Subcategory', 'Min Price', 'Max Price', 'MOQ', 'Status', 'Featured', 'Created Date'];
        $rows = [];

        foreach ($records as $r) {
            $rows[] = [
                $r['id'],
                $r['title'],
                $r['category_name'] ?? '-',
                $r['subcategory_name'] ?? '-',
                $r['min_price'],
                $r['max_price'],
                $r['moq_quantity'] . ' ' . ($r['moq_unit_symbol'] ?? ''),
                $r['status'],
                $r['is_featured'] ? 'YES' : 'NO',
                $r['created_at'],
            ];
        }

        $title = 'Products Catalog Export';
        $filename = 'sports_junction_products_' . date('Ymd_His');

        if ($format === 'excel') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } elseif ($format === 'pdf') {
            Exporter::exportPdfView($title, $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }

    public function downloadBulkTemplate(): void
    {
        $format = (string)$this->request->get('format', 'xlsx');
        \App\Helpers\BulkProductImporter::downloadTemplate($format, true);
    }

    public function downloadImportReference(): void
    {
        $format = (string)$this->request->get('format', 'xlsx');
        \App\Helpers\BulkProductImporter::downloadReferenceData($format, true);
    }

    public function bulkPreview(): void
    {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Please upload a valid Excel (.xlsx) or CSV file.'], 422);
            return;
        }

        $tmpFile = $_FILES['file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid file format. Only .xlsx and .csv files are supported.'], 422);
            return;
        }

        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

        $storedTmp = \App\Helpers\BulkProductImporter::getSafeTempFile('sj_bulk_', $ext);
        if (!move_uploaded_file($tmpFile, $storedTmp) && !copy($tmpFile, $storedTmp)) {
            $this->json(['success' => false, 'message' => 'Failed to process uploaded file.'], 500);
            return;
        }

        try {
            $result = \App\Helpers\BulkProductImporter::parseAndValidate($storedTmp, null, true, $supplierId);
            @unlink($storedTmp);
            $this->json($result);
        } catch (\Throwable $e) {
            @unlink($storedTmp);
            $this->json(['success' => false, 'message' => 'Validation error: ' . $e->getMessage()], 500);
        }
    }

    public function bulkCommit(): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $fileToken = trim((string)$this->request->input('file_token', ''));
        if (empty($fileToken)) {
            $this->json(['success' => false, 'message' => 'Missing upload token. Please upload the file again.'], 422);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $result = \App\Helpers\BulkProductImporter::commit($fileToken, null, true, $adminId);
        $status = ($result['success'] ?? false) ? 200 : 422;
        $this->json($result, $status);
    }

    public function downloadErrorReport(): void
    {
        $fileToken = trim((string)$this->request->get('token', ''));
        $format = strtolower((string)$this->request->get('format', 'xlsx'));
        \App\Helpers\BulkProductImporter::downloadErrorReport($fileToken, $format);
    }
}
