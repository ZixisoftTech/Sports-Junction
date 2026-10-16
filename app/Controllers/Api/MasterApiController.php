<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Exporter;
use App\Helpers\Validator;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\BusinessType;
use App\Models\PriceUnit;
use App\Models\MoqUnit;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ProductCertificate;
use App\Models\SupplierCertificationTag;
use App\Helpers\Uploader;

class MasterApiController extends Controller
{
    private function getModel(string $entity): ?string
    {
        return match (strtolower($entity)) {
            'countries'                                  => Country::class,
            'states'                                     => State::class,
            'cities'                                     => City::class,
            'business-types', 'business_types'           => BusinessType::class,
            'price-units', 'price_units'                 => PriceUnit::class,
            'moq-units', 'moq_units'                     => MoqUnit::class,
            'categories'                                 => Category::class,
            'subcategories'                              => Subcategory::class,
            'product-certificates', 'product_certificates' => ProductCertificate::class,
            'certification-tags', 'certification_tags'   => SupplierCertificationTag::class,
            default                                      => null,
        };
    }

    private function getEntityName(string $entity): string
    {
        return match (strtolower($entity)) {
            'countries'                                  => 'Country',
            'states'                                     => 'State',
            'cities'                                     => 'City',
            'business-types', 'business_types'           => 'Business Type',
            'price-units', 'price_units'                 => 'Price Unit',
            'moq-units', 'moq_units'                     => 'MOQ Unit',
            'categories'                                 => 'Category',
            'subcategories'                              => 'Subcategory',
            'product-certificates', 'product_certificates' => 'Product Certificate',
            'certification-tags', 'certification_tags'   => 'Certification Tag',
            default                                      => 'Record',
        };
    }

    public function data(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $model = $this->getModel($entity);
        if (!$model) {
            $this->json(['error' => 'Invalid master entity'], 400);
            return;
        }

        $result = $model::getDataTable($this->request->all());
        $this->json($result);
    }

    public function show(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $id = (int)($params['id'] ?? 0);
        $model = $this->getModel($entity);

        if (!$model || $id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid master record request'], 400);
            return;
        }

        $record = $model::findById($id);
        if (!$record) {
            $this->json(['success' => false, 'message' => 'Record not found'], 404);
            return;
        }

        $this->json(['success' => true, 'data' => $record]);
    }

    public function store(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $model = $this->getModel($entity);
        if (!$model) {
            $this->json(['success' => false, 'message' => 'Invalid master entity'], 400);
            return;
        }

        $input = $this->request->all();
        $validator = Validator::make($input);
        $rules = [];

        switch (strtolower($entity)) {
            case 'countries':
                $rules = [
                    'name'        => 'required|max:100|unique:countries,name',
                    'iso_alpha2'  => 'required|min:2|max:2|alpha_dash|unique:countries,iso_alpha2',
                    'iso_alpha3'  => 'max:3|unique:countries,iso_alpha3',
                    'status'      => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'states':
                $rules = [
                    'country_id' => 'required|exists:countries,id',
                    'name'       => 'required|max:100',
                    'code'       => 'max:20',
                    'status'     => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'cities':
                $rules = [
                    'country_id' => 'required|exists:countries,id',
                    'state_id'   => 'required|exists:states,id',
                    'name'       => 'required|max:100',
                    'status'     => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'business-types':
            case 'business_types':
                $rules = [
                    'name'   => 'required|max:100|unique:business_types,name',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'price-units':
            case 'price_units':
                $rules = [
                    'name'   => 'required|max:50|unique:price_units,name',
                    'symbol' => 'max:20',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'moq-units':
            case 'moq_units':
                $rules = [
                    'name'   => 'required|max:50|unique:moq_units,name',
                    'symbol' => 'max:20',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'categories':
                $rules = [
                    'name'   => 'required|max:150|unique:categories,name',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'subcategories':
                $rules = [
                    'category_id' => 'required|integer',
                    'name'        => 'required|max:150',
                    'status'      => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'product-certificates':
            case 'product_certificates':
                $rules = [
                    'name'   => 'required|max:150|unique:product_certificates,name',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'certification-tags':
            case 'certification_tags':
                $rules = [
                    'name'   => 'required|max:150|unique:supplier_certification_tags,name',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;
        }

        // Handle image file upload if provided
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $subfolder = strtolower($entity) === 'subcategories' ? 'subcategories' : 'categories';
                $input['image'] = Uploader::uploadImage($_FILES['image_file'], $subfolder);
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => 'Image upload failed: ' . $e->getMessage()], 422);
                return;
            }
        }

        // Handle icon file upload if provided (for categories, certificates, tags, subcategories)
        if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $iconFolder = match (strtolower($entity)) {
                    'product-certificates', 'product_certificates' => 'certificates/icons',
                    'certification-tags', 'certification_tags'     => 'tags/icons',
                    'subcategories'                                => 'subcategories/icons',
                    default                                        => 'categories/icons',
                };
                $input['icon'] = Uploader::uploadImage($_FILES['icon_file'], $iconFolder);
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => 'Icon upload failed: ' . $e->getMessage()], 422);
                return;
            }
        }

        if (!$validator->validate($rules)) {
            $this->json(['success' => false, 'errors' => $validator->errors(), 'message' => $validator->firstError()], 422);
            return;
        }

        // Composite uniqueness checks & State-Country relationship verification
        if (strtolower($entity) === 'states') {
            $existingState = State::fetch(
                "SELECT COUNT(*) FROM states WHERE country_id = :cid AND name = :name",
                [':cid' => (int)$input['country_id'], ':name' => trim((string)$input['name'])]
            );
            if ((int)$existingState['COUNT(*)'] > 0) {
                $this->json(['success' => false, 'message' => "State '{$input['name']}' already exists for this country."], 422);
                return;
            }
        }

        if (strtolower($entity) === 'cities') {
            $state = State::findById((int)$input['state_id']);
            if (!$state || (int)$state['country_id'] !== (int)$input['country_id']) {
                $this->json(['success' => false, 'message' => 'The selected state does not belong to the selected country.'], 422);
                return;
            }

            $existingCity = City::fetch(
                "SELECT COUNT(*) FROM cities WHERE state_id = :sid AND name = :name",
                [':sid' => (int)$input['state_id'], ':name' => trim((string)$input['name'])]
            );
            if ((int)$existingCity['COUNT(*)'] > 0) {
                $this->json(['success' => false, 'message' => "City '{$input['name']}' already exists in this state."], 422);
                return;
            }
        }

        try {
            $id = $model::create($input);
            $entityName = $this->getEntityName($entity);
            $this->json([
                'success' => true,
                'id'      => $id,
                'message' => "{$entityName} created successfully."
            ], 201);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to save record: ' . $e->getMessage()], 500);
        }
    }

    public function update(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $id = (int)($params['id'] ?? 0);
        $model = $this->getModel($entity);

        if (!$model || $id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid master entity or ID'], 400);
            return;
        }

        $existing = $model::findById($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Record not found'], 404);
            return;
        }

        $input = $this->request->all();
        $validator = Validator::make($input);
        $rules = [];

        switch (strtolower($entity)) {
            case 'countries':
                $rules = [
                    'name'        => "required|max:100|unique:countries,name,{$id}",
                    'iso_alpha2'  => "required|min:2|max:2|alpha_dash|unique:countries,iso_alpha2,{$id}",
                    'iso_alpha3'  => "max:3|unique:countries,iso_alpha3,{$id}",
                    'status'      => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'states':
                $rules = [
                    'country_id' => 'required|exists:countries,id',
                    'name'       => 'required|max:100',
                    'code'       => 'max:20',
                    'status'     => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'cities':
                $rules = [
                    'country_id' => 'required|exists:countries,id',
                    'state_id'   => 'required|exists:states,id',
                    'name'       => 'required|max:100',
                    'status'     => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'business-types':
            case 'business_types':
                $rules = [
                    'name'   => "required|max:100|unique:business_types,name,{$id}",
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'price-units':
            case 'price_units':
                $rules = [
                    'name'   => "required|max:50|unique:price_units,name,{$id}",
                    'symbol' => 'max:20',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'moq-units':
            case 'moq_units':
                $rules = [
                    'name'   => "required|max:50|unique:moq_units,name,{$id}",
                    'symbol' => 'max:20',
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'categories':
                $rules = [
                    'name'   => "required|max:150|unique:categories,name,{$id}",
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'subcategories':
                $rules = [
                    'category_id' => 'required|integer',
                    'name'        => 'required|max:150',
                    'status'      => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'product-certificates':
            case 'product_certificates':
                $rules = [
                    'name'   => "required|max:150|unique:product_certificates,name,{$id}",
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;

            case 'certification-tags':
            case 'certification_tags':
                $rules = [
                    'name'   => "required|max:150|unique:supplier_certification_tags,name,{$id}",
                    'status' => 'required|in:ACTIVE,INACTIVE',
                ];
                break;
        }

        // Handle image file upload if provided
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $subfolder = strtolower($entity) === 'subcategories' ? 'subcategories' : 'categories';
                $input['image'] = Uploader::uploadImage($_FILES['image_file'], $subfolder);
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => 'Image upload failed: ' . $e->getMessage()], 422);
                return;
            }
        }

        // Handle icon file upload if provided (for categories, certificates, tags, subcategories)
        if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $iconFolder = match (strtolower($entity)) {
                    'product-certificates', 'product_certificates' => 'certificates/icons',
                    'certification-tags', 'certification_tags'     => 'tags/icons',
                    'subcategories'                                => 'subcategories/icons',
                    default                                        => 'categories/icons',
                };
                $input['icon'] = Uploader::uploadImage($_FILES['icon_file'], $iconFolder);
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => 'Icon upload failed: ' . $e->getMessage()], 422);
                return;
            }
        }

        if (!$validator->validate($rules)) {
            $this->json(['success' => false, 'errors' => $validator->errors(), 'message' => $validator->firstError()], 422);
            return;
        }

        // Composite uniqueness checks & relationship verification
        if (strtolower($entity) === 'states') {
            $existingState = State::fetch(
                "SELECT COUNT(*) FROM states WHERE country_id = :cid AND name = :name AND id != :id",
                [':cid' => (int)$input['country_id'], ':name' => trim((string)$input['name']), ':id' => $id]
            );
            if ((int)$existingState['COUNT(*)'] > 0) {
                $this->json(['success' => false, 'message' => "State '{$input['name']}' already exists for this country."], 422);
                return;
            }
        }

        if (strtolower($entity) === 'cities') {
            $state = State::findById((int)$input['state_id']);
            if (!$state || (int)$state['country_id'] !== (int)$input['country_id']) {
                $this->json(['success' => false, 'message' => 'The selected state does not belong to the selected country.'], 422);
                return;
            }

            $existingCity = City::fetch(
                "SELECT COUNT(*) FROM cities WHERE state_id = :sid AND name = :name AND id != :id",
                [':sid' => (int)$input['state_id'], ':name' => trim((string)$input['name']), ':id' => $id]
            );
            if ((int)$existingCity['COUNT(*)'] > 0) {
                $this->json(['success' => false, 'message' => "City '{$input['name']}' already exists in this state."], 422);
                return;
            }
        }

        try {
            $model::update($id, $input);
            $entityName = $this->getEntityName($entity);
            $this->json([
                'success' => true,
                'message' => "{$entityName} updated successfully."
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to update record: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $id = (int)($params['id'] ?? 0);
        $model = $this->getModel($entity);

        if (!$model || $id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid master entity or ID'], 400);
            return;
        }

        $record = $model::findById($id);
        if (!$record) {
            $this->json(['success' => false, 'message' => 'Record not found'], 404);
            return;
        }

        $currentStatus = $record['status'] ?? 'ACTIVE';
        $newStatus = ($currentStatus === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        $force = (bool)$this->request->post('force', false);
        $cascade = (bool)$this->request->post('cascade', false);

        // If activating, activate immediately
        if ($newStatus === 'ACTIVE') {
            $success = $model::setStatus($id, 'ACTIVE');
            $this->json([
                'success'   => true,
                'newStatus' => 'ACTIVE',
                'message'   => "{$this->getEntityName($entity)} '{$record['name']}' activated successfully."
            ]);
            return;
        }

        // Deactivating: Case 1 - Cascade requested
        if ($cascade && method_exists($model, 'setStatusWithCascade')) {
            $res = $model::setStatusWithCascade($id, 'INACTIVE');
            $this->json($res);
            return;
        }

        // Deactivating: Case 2 - Force requested (deactivate parent only)
        if ($force) {
            $success = $model::setStatus($id, 'INACTIVE');
            $this->json([
                'success'   => true,
                'newStatus' => 'INACTIVE',
                'message'   => "{$this->getEntityName($entity)} '{$record['name']}' status changed to INACTIVE."
            ]);
            return;
        }

        // Deactivating: Case 3 - Neither force nor cascade, check dependencies
        if (method_exists($model, 'getDependencies')) {
            $deps = $model::getDependencies($id);
            if (!empty($deps['has_active'])) {
                $entityName = $this->getEntityName($entity);
                $this->json([
                    'success'          => false,
                    'has_dependencies' => true,
                    'dependencies'     => $deps,
                    'message'          => "Active dependent records exist for {$entityName} '{$record['name']}'."
                ], 200);
                return;
            }
        }

        // Deactivating: Case 4 - No dependencies, normal deactivation
        $success = $model::setStatus($id, 'INACTIVE');
        if ($success) {
            $this->json([
                'success'   => true,
                'newStatus' => 'INACTIVE',
                'message'   => "{$this->getEntityName($entity)} '{$record['name']}' status changed to INACTIVE."
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to change status.'], 500);
        }
    }

    public function dependencies(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $id = (int)($params['id'] ?? 0);
        $model = $this->getModel($entity);

        if (!$model || $id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $record = $model::findById($id);
        if (!$record) {
            $this->json(['success' => false, 'message' => 'Record not found'], 404);
            return;
        }

        $deps = method_exists($model, 'getDependencies') ? $model::getDependencies($id) : ['total' => 0];

        $this->json([
            'success'      => true,
            'record'       => [
                'id'   => $record['id'],
                'name' => $record['name'] ?? ''
            ],
            'dependencies' => $deps
        ]);
    }

    public function delete(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $id = (int)($params['id'] ?? 0);
        $model = $this->getModel($entity);

        if (!$model || $id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid master entity or ID'], 400);
            return;
        }

        $cascade = (bool)$this->request->post('cascade', false);
        $confirmText = trim((string)$this->request->post('confirm_text', ''));

        // If cascading deletion requested, require explicit typing of DELETE as per Section 13 Step 3
        if ($cascade && $confirmText !== 'DELETE') {
            $this->json([
                'success' => false,
                'message' => "Type 'DELETE' in all capital letters to confirm cascading permanent deletion."
            ], 422);
            return;
        }

        $result = $model::hardDelete($id, $cascade);
        $statusCode = ($result['success'] ?? false) ? 200 : 422;
        $this->json($result, $statusCode);
    }

    public function statesByCountry(array $params): void
    {
        $countryId = (int)($params['countryId'] ?? 0);
        if ($countryId <= 0) {
            $this->json(['success' => true, 'data' => []]);
            return;
        }

        $states = State::getByCountry($countryId, true);
        $this->json(['success' => true, 'data' => $states]);
    }

    public function subcategoriesByCategory(array $params): void
    {
        $catId = (int)($params['categoryId'] ?? 0);
        if ($catId <= 0) {
            $this->json(['success' => true, 'data' => []]);
            return;
        }

        $subs = Subcategory::byCategoryActive($catId);
        $this->json(['success' => true, 'data' => $subs]);
    }

    public function subcategoriesByCategories(): void
    {
        $raw = $this->request->get('category_ids') ?? $this->request->input('category_ids') ?? '';
        if (is_array($raw)) {
            $catIds = $raw;
        } else {
            $catIds = array_filter(array_map('trim', explode(',', (string)$raw)));
        }

        $subs = Subcategory::byCategoriesActive($catIds);
        $this->json(['success' => true, 'data' => $subs]);
    }

    public function export(array $params): void
    {
        $entity = $params['entity'] ?? '';
        $format = strtolower((string)$this->request->get('format', 'csv'));
        $scope = strtolower((string)$this->request->get('scope', 'all'));
        $search = trim((string)$this->request->get('search', ''));
        $statusFilter = trim((string)$this->request->get('status', ''));

        $model = $this->getModel($entity);
        if (!$model) {
            $this->response->setStatusCode(400)->send('Invalid master entity for export');
            return;
        }

        $queryParams = [
            'draw'   => 1,
            'start'  => 0,
            'length' => ($scope === 'current') ? (int)$this->request->get('length', 25) : 0, // 0 = all
            'search' => ($scope === 'current') ? $search : '',
            'status' => ($scope === 'current') ? $statusFilter : '',
            'order'  => [['column' => 0, 'dir' => 'asc']],
        ];

        $dtResult = $model::getDataTable($queryParams);
        $records = $dtResult['data'] ?? [];

        $headers = [];
        $rows = [];
        $title = $this->getEntityName($entity) . ' Records Export';
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd_His');

        switch (strtolower($entity)) {
            case 'countries':
                $headers = ['ID', 'Country Name', 'ISO Alpha-2', 'ISO Alpha-3', 'Phone Calling Code', 'Status', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['iso_alpha2'], $r['iso_alpha3'] ?? '-', $r['phone_code'] ?? '-', $r['status'], $r['created_at']];
                }
                break;

            case 'states':
                $headers = ['ID', 'State Name', 'State Code', 'Country Name', 'Status', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['code'] ?? '-', $r['country_name'] ?? '-', $r['status'], $r['created_at']];
                }
                break;

            case 'cities':
                $headers = ['ID', 'City Name', 'State Name', 'Country Name', 'Status', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['state_name'] ?? '-', $r['country_name'] ?? '-', $r['status'], $r['created_at']];
                }
                break;

            case 'business-types':
            case 'business_types':
                $headers = ['ID', 'Business Type Name', 'Description', 'Status', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['description'] ?? '-', $r['status'], $r['created_at']];
                }
                break;

            case 'price-units':
            case 'price_units':
                $headers = ['ID', 'Unit Name', 'Symbol', 'Description', 'Status', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['symbol'] ?? '-', $r['description'] ?? '-', $r['status'], $r['created_at']];
                }
                break;

            case 'moq-units':
            case 'moq_units':
                $headers = ['ID', 'Unit Name', 'Symbol', 'Description', 'Status', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['symbol'] ?? '-', $r['description'] ?? '-', $r['status'], $r['created_at']];
                }
                break;

            case 'categories':
                $headers = ['ID', 'Category Name', 'Slug', 'Display Order', 'Status', 'Subcategories Count', 'Products Count', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['slug'], $r['display_order'], $r['status'], $r['subcategories_count'] ?? 0, $r['products_count'] ?? 0, $r['created_at']];
                }
                break;

            case 'subcategories':
                $headers = ['ID', 'Subcategory Name', 'Parent Category', 'Slug', 'Display Order', 'Status', 'Products Count', 'Created Date'];
                foreach ($records as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['category_name'] ?? '-', $r['slug'], $r['display_order'], $r['status'], $r['products_count'] ?? 0, $r['created_at']];
                }
                break;
        }

        if ($format === 'excel') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } elseif ($format === 'pdf') {
            Exporter::exportPdfView($title, $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }
}
