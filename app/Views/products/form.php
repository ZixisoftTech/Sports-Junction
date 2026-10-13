<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Uploader;

$baseUrl = Response::baseUrl();
$isEdit = $isEdit ?? false;
$product = $product ?? null;
$subcategories = $subcategories ?? [];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($baseUrl) ?>/admin/products" class="text-decoration-none">Product Inventory</a></li>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($baseUrl) ?>/admin/product-approvals" class="text-decoration-none">Product Approvals</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi <?= $isEdit ? 'bi-pencil-square text-primary' : 'bi-plus-circle-fill text-success' ?> me-2"></i>
            <?= $isEdit ? 'Edit Product: ' . htmlspecialchars($product['name'] ?? $product['title'] ?? '') : 'Add Wholesale Product' ?>
        </h4>
        <p class="text-muted small mb-0">Complete or modify any section below to manage sports equipment specifications before or after approval.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/product-approvals" class="btn btn-outline-warning btn-sm shadow-sm px-3 text-dark">
            <i class="bi bi-shield-check me-1.5"></i> Approvals Queue
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/products" class="btn btn-outline-secondary btn-sm shadow-sm px-3">
            <i class="bi bi-arrow-left me-1.5"></i> Back to Products
        </a>
        <?php if ($isEdit && !empty($product['slug'])): ?>
            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($product['slug']) ?>" target="_blank" class="btn btn-outline-primary btn-sm shadow-sm px-3">
                <i class="bi bi-box-arrow-up-right me-1.5"></i> View Live Page
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isEdit && !empty($product)): ?>
    <?php
        $appSt = $product['approval_status'] ?? 'APPROVED';
        $hasPend = !empty($product['has_pending_changes']);
    ?>
    <div class="alert <?= ($appSt === 'APPROVED' && !$hasPend) ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center justify-content-between py-2.5 px-3 mb-4 shadow-xs">
        <div class="small">
            <i class="bi <?= ($appSt === 'APPROVED' && !$hasPend) ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <strong>Current Approval Status: <?= htmlspecialchars($appSt) ?></strong>
            <?php if ($hasPend): ?>
                — This product has a pending supplier edit revision loaded into the fields below. Saving will apply your edited values directly.
            <?php elseif ($appSt === 'PENDING_APPROVAL'): ?>
                — You are editing this product <strong>before approval</strong>. You can keep it as Pending Approval or set Approval Status to Approved in Step 4.
            <?php else: ?>
                — You are editing this product <strong>after approval</strong>. Changes saved here by Super Admin take effect immediately.
            <?php endif; ?>
        </div>
        <span class="badge <?= $appSt === 'APPROVED' ? 'bg-success' : ($appSt === 'REJECTED' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= htmlspecialchars($appSt) ?></span>
    </div>
<?php endif; ?>

<!-- Main Wizard Card -->
<div class="sj-card mb-5 shadow-sm border-0">
    <!-- Step Wizard Nav Header -->
    <div class="bg-white border-bottom p-0 rounded-top">
        <ul class="nav nav-pills nav-justified step-wizard-nav p-2 border-0" id="wizardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active step-tab-btn" id="step1-tab" data-step="1" type="button" role="tab">
                    <span class="step-num">1</span>
                    <span class="step-title">
                        <i class="bi bi-info-circle me-1"></i> Basic Info
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link step-tab-btn" id="step2-tab" data-step="2" type="button" role="tab">
                    <span class="step-num">2</span>
                    <span class="step-title">
                        <i class="bi bi-tag me-1"></i> Pricing &amp; MOQ
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link step-tab-btn" id="step3-tab" data-step="3" type="button" role="tab">
                    <span class="step-num">3</span>
                    <span class="step-title">
                        <i class="bi bi-sliders me-1"></i> Specs &amp; Customization
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link step-tab-btn" id="step4-tab" data-step="4" type="button" role="tab">
                    <span class="step-num">4</span>
                    <span class="step-title">
                        <i class="bi bi-truck me-1"></i> Shipping &amp; Status
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link step-tab-btn" id="step5-tab" data-step="5" type="button" role="tab">
                    <span class="step-num">5</span>
                    <span class="step-title">
                        <i class="bi bi-images me-1"></i> Images &amp; Media
                    </span>
                </button>
            </li>
        </ul>
        
        <!-- Step Progress Bar -->
        <div class="progress" style="height: 4px; border-radius: 0;">
            <div class="progress-bar bg-primary" id="wizardProgressBar" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <!-- Wizard Form Body -->
    <form id="productPageForm" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id" id="productId" value="<?= htmlspecialchars((string)($product['id'] ?? '')) ?>">
        <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">
        <input type="hidden" name="deleted_image_ids" id="deletedImageIds" value="">

        <div class="card-body p-4 p-md-5">
            <!-- Alert Container for Form-Level Notifications -->
            <div id="wizardAlertBox" class="alert alert-danger d-none mb-4 shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                    <span id="wizardAlertMsg">Please complete all required fields on this step before proceeding.</span>
                </div>
            </div>

            <!-- STEP 1: BASIC INFO -->
            <div class="wizard-step-pane active" id="wizardStep1" data-step="1">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark m-0">1. Basic Product Information</h5>
                        <small class="text-muted">Enter essential catalog details, manufacturer assignment, and category categorization.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border px-3 py-2">Step 1 of 5</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="prodTitle" class="form-label fw-semibold small">Product Name / Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="prodTitle" name="name" required placeholder="e.g. Professional PU Leather Football (Match Size 5)" maxlength="200" value="<?= htmlspecialchars($product['name'] ?? $product['title'] ?? '') ?>">
                        <div class="invalid-feedback">Product name / title is required (up to 200 characters).</div>
                    </div>
                    <div class="col-md-4">
                        <label for="prodSku" class="form-label fw-semibold small">Product SKU / Code</label>
                        <input type="text" class="form-control" id="prodSku" name="sku" placeholder="e.g. SJ-FTB-M05" maxlength="100" value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                        <div class="text-muted small" style="font-size: 11px;">Optional factory identification or internal inventory SKU.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="prodSupplier" class="form-label fw-semibold small">Supplier / Manufacturer <span class="text-danger">*</span></label>
                        <select class="form-select" id="prodSupplier" name="supplier_id" required>
                            <option value="">Select Approved Manufacturer...</option>
                            <?php if (!empty($suppliers)): foreach ($suppliers as $sup): ?>
                                <option value="<?= $sup['id'] ?>" <?= ((int)($product['supplier_id'] ?? 0) === (int)$sup['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sup['company_name'] ?? $sup['contact_person'] ?? 'Supplier #' . $sup['id']) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <div class="invalid-feedback">Please select the approved supplier/factory producing this item.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="prodBrand" class="form-label fw-semibold small">Brand Name / Manufacturer Tag</label>
                        <input type="text" class="form-control" id="prodBrand" name="brand_name" placeholder="e.g. ApexSport Industries" maxlength="150" value="<?= htmlspecialchars($product['brand_name'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="prodCategory" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="prodCategory" name="category_id" required>
                            <option value="">Select Master Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a parent category.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="prodSubcategory" class="form-label fw-semibold small">Subcategory <span class="text-danger">*</span></label>
                        <select class="form-select" id="prodSubcategory" name="subcategory_id" required>
                            <option value="">Select Subcategory...</option>
                            <?php if (!empty($subcategories)): foreach ($subcategories as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= ((int)($product['subcategory_id'] ?? 0) === (int)$sub['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['name']) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <div class="invalid-feedback">Please select a subcategory.</div>
                    </div>

                    <div class="col-md-12">
                        <label for="prodSlug" class="form-label fw-semibold small">URL Slug <span class="text-muted">(Auto-generated if left blank)</span></label>
                        <input type="text" class="form-control font-monospace" id="prodSlug" name="slug" placeholder="e.g. professional-pu-leather-football" maxlength="260" value="<?= htmlspecialchars($product['slug'] ?? '') ?>">
                        <div class="text-muted small" style="font-size: 11px;">Unique SEO web handle for buyer storefront discovery.</div>
                    </div>

                    <div class="col-12">
                        <label for="prodShortDesc" class="form-label fw-semibold small">Short Description / Key Highlights</label>
                        <input type="text" class="form-control" id="prodShortDesc" name="short_description" placeholder="One-sentence highlight summarizing construction, FIFA standard, or materials..." value="<?= htmlspecialchars($product['short_description'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="prodDescription" class="form-label fw-semibold small">Detailed Technical & Commercial Description</label>
                        <textarea class="form-control" id="prodDescription" name="description" rows="5" placeholder="Comprehensive product specifications, factory manufacturing process, packaging options, and quality control guarantees..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 2: PRICING & MOQ -->
            <div class="wizard-step-pane d-none" id="wizardStep2" data-step="2">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark m-0">2. Wholesale Pricing Tiers &amp; MOQ</h5>
                        <small class="text-muted">Configure minimum and maximum bulk pricing matrices and order quantity thresholds.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border px-3 py-2">Step 2 of 5</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="prodMinPrice" class="form-label fw-semibold small">Min Wholesale Price (Tier 1) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-muted">&#8377;</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="prodMinPrice" name="min_price" required placeholder="0.00" value="<?= htmlspecialchars((string)($product['min_price'] ?? '')) ?>">
                            <div class="invalid-feedback">Valid min price (> 0) is required.</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="prodMaxPrice" class="form-label fw-semibold small">Max Wholesale Price (Tier 2) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-muted">&#8377;</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="prodMaxPrice" name="max_price" required placeholder="0.00" value="<?= htmlspecialchars((string)($product['max_price'] ?? '')) ?>">
                            <div class="invalid-feedback">Max price must be greater than or equal to min price.</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="prodPriceUnit" class="form-label fw-semibold small">Price Currency / Unit <span class="text-danger">*</span></label>
                        <select class="form-select" id="prodPriceUnit" name="price_unit_id" required>
                            <option value="">Select Price Unit...</option>
                            <?php foreach ($priceUnits as $pu): ?>
                                <option value="<?= $pu['id'] ?>" <?= ((int)($product['price_unit_id'] ?? 1) === (int)$pu['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pu['name']) ?> (<?= htmlspecialchars($pu['symbol'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a pricing unit.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="prodMoq" class="form-label fw-semibold small">Minimum Order Quantity (MOQ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="prodMoq" name="moq" required min="1" placeholder="e.g. 50" value="<?= htmlspecialchars((string)($product['moq_quantity'] ?? $product['moq'] ?? 10)) ?>">
                        <div class="invalid-feedback">MOQ must be at least 1 unit.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="prodMoqUnit" class="form-label fw-semibold small">MOQ Measurement Unit <span class="text-danger">*</span></label>
                        <select class="form-select" id="prodMoqUnit" name="moq_unit_id" required>
                            <option value="">Select Measurement Unit...</option>
                            <?php foreach ($moqUnits as $mu): ?>
                                <option value="<?= $mu['id'] ?>" <?= ((int)($product['moq_unit_id'] ?? 1) === (int)$mu['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mu['name']) ?> (<?= htmlspecialchars($mu['symbol'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select an MOQ unit.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="prodAvailQty" class="form-label fw-semibold small">Available Ready Stock</label>
                        <input type="number" class="form-control" id="prodAvailQty" name="available_quantity" min="0" placeholder="e.g. 1000" value="<?= htmlspecialchars((string)($product['available_quantity'] ?? 1000)) ?>">
                    </div>

                    <div class="col-md-6">
                        <div class="card p-3 bg-light border-0 rounded-3 mt-2">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="prodSampleAvail" name="sample_available" value="1" <?= (!empty($product['sample_available'])) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold small text-dark" for="prodSampleAvail">
                                    Paid Sample Available Before Bulk Order
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6" id="samplePriceWrapper">
                        <label for="prodSamplePrice" class="form-label fw-semibold small">Sample Price Per Unit</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-muted">&#8377;</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="prodSamplePrice" name="sample_price" placeholder="0.00" value="<?= htmlspecialchars((string)($product['sample_price'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: SPECIFICATIONS & CUSTOMIZATION -->
            <div class="wizard-step-pane d-none" id="wizardStep3" data-step="3">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark m-0">3. Specifications &amp; Factory Customization</h5>
                        <small class="text-muted">Define physical characteristics, materials, and OEM/ODM private label capabilities.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border px-3 py-2">Step 3 of 5</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="prodMaterial" class="form-label fw-semibold small">Material Specification</label>
                        <input type="text" class="form-control" id="prodMaterial" name="material" placeholder="e.g. Japanese PU Leather, Multi-Ply Poly-Cotton Lining" value="<?= htmlspecialchars($product['material'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="prodColors" class="form-label fw-semibold small">Available Colorways</label>
                        <input type="text" class="form-control" id="prodColors" name="colors" placeholder="e.g. White/Blue, Safety Orange, Neon Lime, Custom" value="<?= htmlspecialchars($product['colors'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="prodSize" class="form-label fw-semibold small">Dimensions / Size Matrix</label>
                        <input type="text" class="form-control" id="prodSize" name="size" placeholder="e.g. Standard Size 5 (Circumference 68-70cm)" value="<?= htmlspecialchars($product['size'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="prodWeight" class="form-label fw-semibold small">Unit Weight / Specification</label>
                        <input type="text" class="form-control" id="prodWeight" name="weight" placeholder="e.g. 410 - 450 grams" value="<?= htmlspecialchars($product['weight'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small d-block mb-2">OEM / ODM Customization Capabilities</label>
                        <div class="row g-2 p-3 bg-light rounded-3 border">
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkCustomLogo" name="custom_logo" value="1" <?= (!empty($product['custom_logo'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="checkCustomLogo"><i class="bi bi-tag-fill text-primary me-1"></i> Custom Logo Print</label>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkCustomColor" name="custom_color" value="1" <?= (!empty($product['custom_color'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="checkCustomColor"><i class="bi bi-palette-fill text-danger me-1"></i> Custom Pantone Color</label>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkCustomPkg" name="custom_packaging" value="1" <?= (!empty($product['custom_packaging'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="checkCustomPkg"><i class="bi bi-box-seam text-warning me-1"></i> Custom Packaging</label>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkOem" name="oem_available" value="1" <?= (!empty($product['oem_available'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="checkOem"><i class="bi bi-gear-fill text-info me-1"></i> OEM Service</label>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkOdm" name="odm_available" value="1" <?= (!empty($product['odm_available'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="checkOdm"><i class="bi bi-bezier2 text-success me-1"></i> ODM Design</label>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkPrivateLabel" name="private_label" value="1" <?= (!empty($product['private_label'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="checkPrivateLabel"><i class="bi bi-shield-check text-secondary me-1"></i> Private Label</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="prodCustomDetails" class="form-label fw-semibold small">Customization MOQ &amp; Production Details</label>
                        <textarea class="form-control" id="prodCustomDetails" name="customization_details" rows="3" placeholder="e.g. Silk-screen logo min order 100 pcs; custom debossed valve min order 500 pcs; branded presentation polybags included..."><?= htmlspecialchars($product['customization_details'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 4: SHIPPING & PUBLISHING -->
            <div class="wizard-step-pane d-none" id="wizardStep4" data-step="4">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark m-0">4. Shipping, Logistics &amp; Publishing Status</h5>
                        <small class="text-muted">Specify port dispatch details, estimated lead times, and marketplace publishing visibility.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border px-3 py-2">Step 4 of 5</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="prodCountry" class="form-label fw-semibold small">Dispatch Origin / Country</label>
                        <select class="form-select" id="prodCountry" name="country_id">
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ((int)($product['country_id'] ?? 1) === (int)$c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['iso_alpha2']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="prodLeadTime" class="form-label fw-semibold small">Estimated Production Lead Time</label>
                        <input type="text" class="form-control" id="prodLeadTime" name="lead_time" placeholder="e.g. 10 - 20 Business Days" value="<?= htmlspecialchars($product['lead_time'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="prodShippingInfo" class="form-label fw-semibold small">Shipping, Freight &amp; Port Dispatch Terms</label>
                        <textarea class="form-control" id="prodShippingInfo" name="shipping_info" rows="2" placeholder="e.g. FOB Nhava Sheva (Mumbai) / Mundra Port or Worldwide Express Air Freight"><?= htmlspecialchars($product['shipping_info'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-3">
                        <label for="prodApprovalStatus" class="form-label fw-semibold small">Approval Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="prodApprovalStatus" name="approval_status" required>
                            <option value="APPROVED" <?= (($product['approval_status'] ?? 'APPROVED') === 'APPROVED') ? 'selected' : '' ?>>Approved (Verified)</option>
                            <option value="PENDING_APPROVAL" <?= (($product['approval_status'] ?? '') === 'PENDING_APPROVAL') ? 'selected' : '' ?>>Pending Approval (Before Approve)</option>
                            <option value="REJECTED" <?= (($product['approval_status'] ?? '') === 'REJECTED') ? 'selected' : '' ?>>Rejected</option>
                            <option value="DRAFT" <?= (($product['approval_status'] ?? '') === 'DRAFT') ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="prodStatus" class="form-label fw-semibold small">Marketplace Publishing Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="prodStatus" name="status" required>
                            <option value="PUBLISHED" <?= (($product['status'] ?? 'PUBLISHED') === 'PUBLISHED') ? 'selected' : '' ?>>Published (Live in Catalog)</option>
                            <option value="DRAFT" <?= (($product['status'] ?? '') === 'DRAFT') ? 'selected' : '' ?>>Draft (Under Review)</option>
                            <option value="INACTIVE" <?= (($product['status'] ?? '') === 'INACTIVE') ? 'selected' : '' ?>>Inactive (Archived)</option>
                        </select>
                        <div class="invalid-feedback">Please select a publishing status.</div>
                    </div>

                    <div class="col-md-3">
                        <div class="card p-3 bg-light border-0 rounded-3 mt-4">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="prodFeatured" name="is_featured" value="1" <?= (!empty($product['is_featured'])) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold small text-dark" for="prodFeatured">
                                    <i class="bi bi-star-fill text-warning me-1"></i> Highlight as Featured Product
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="prodFeatOrder" class="form-label fw-semibold small">Featured Display Priority</label>
                        <input type="number" class="form-control" id="prodFeatOrder" name="featured_order" min="0" value="<?= htmlspecialchars((string)($product['featured_order'] ?? 0)) ?>">
                        <div class="text-muted small" style="font-size: 11px;">Higher priority numbers sort first on the homepage carousel.</div>
                    </div>
                </div>
            </div>

            <!-- STEP 5: IMAGES & MEDIA (LAST FORM) -->
            <div class="wizard-step-pane d-none" id="wizardStep5" data-step="5">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark m-0">5. Images, Gallery &amp; Video Media</h5>
                        <small class="text-muted">Upload high-resolution photography, multi-angle product showcase, and demonstration links.</small>
                    </div>
                    <span class="badge bg-success-subtle text-success border px-3 py-2">Final Step 5 of 5</span>
                </div>

                <div class="row g-4">
                    <!-- Primary Image Upload -->
                    <div class="col-12">
                        <label class="form-label fw-semibold small mb-2">Primary Product Image</label>
                        <div class="p-3 bg-light rounded-3 border d-flex flex-column flex-sm-row align-items-center gap-4">
                            <div class="position-relative border bg-white rounded-3 shadow-xs d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 120px; height: 120px;">
                                <img id="primaryImgPreview" src="<?= htmlspecialchars(!empty($product['primary_image']) ? Uploader::imageUrl($product['primary_image']) : $baseUrl . '/assets/images/logo.jpg') ?>" alt="Product Preview" class="w-100 h-100 object-fit-cover rounded">
                            </div>
                            <div class="flex-grow-1 w-100">
                                <label for="prodPrimaryImgFile" class="form-label fw-semibold small text-dark mb-1">
                                    <i class="bi bi-cloud-arrow-up text-primary me-1"></i> Upload High-Res Image File
                                </label>
                                <input type="file" class="form-control mb-2" id="prodPrimaryImgFile" name="primary_image_file" accept="image/jpeg,image/png,image/webp,image/gif">
                                <div class="text-muted small" style="font-size: 11px;">Square aspect ratio recommended (e.g. 800×800px). Max 8MB file size. Updates live preview.</div>
                                
                                <div class="mt-2 pt-2 border-top">
                                    <label for="prodPrimaryImgUrl" class="form-label text-muted mb-1" style="font-size: 11px;">Or Provide Stored Image Path / External URL:</label>
                                    <input type="text" class="form-control form-control-sm font-monospace" id="prodPrimaryImgUrl" name="primary_image" placeholder="/uploads/products/... or https://..." value="<?= htmlspecialchars($product['primary_image'] ?? '') ?>" style="font-size: 12px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Upload -->
                    <div class="col-12">
                        <label for="prodGalleryFiles" class="form-label fw-semibold small mb-1">Add Additional Gallery Angles (Multi-Select)</label>
                        <input type="file" class="form-control" id="prodGalleryFiles" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/webp">
                        <div class="text-muted small mt-1" style="font-size: 11px;">Select multiple files at once. These will be added to the product gallery showcase.</div>
                    </div>

                    <!-- Existing Gallery Images -->
                    <?php if ($isEdit && !empty($product['images'])): ?>
                        <div class="col-12">
                            <label class="form-label fw-semibold small mb-2">Existing Gallery Photos (Click trash to remove on save):</label>
                            <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border" id="existingGalleryContainer">
                                <?php foreach ($product['images'] as $img): ?>
                                    <div class="position-relative border rounded-3 overflow-hidden bg-white shadow-xs gallery-thumb-item" style="width: 100px; height: 100px;" data-image-id="<?= $img['id'] ?>">
                                        <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Gallery" class="w-100 h-100 object-fit-cover">
                                        <button type="button" class="btn btn-danger btn-sm p-0 position-absolute top-0 end-0 m-1 rounded-circle d-flex align-items-center justify-content-center btn-delete-gallery-img" style="width: 24px; height: 24px;" title="Delete image">
                                            <i class="bi bi-x fs-6"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Video URL -->
                    <div class="col-12">
                        <label for="prodVideoUrl" class="form-label fw-semibold small">Demonstration Video Link</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-danger"><i class="bi bi-youtube"></i></span>
                            <input type="url" class="form-control" id="prodVideoUrl" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($product['video_url'] ?? '') ?>">
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Embeddable YouTube, Vimeo, or factory demonstration video link.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Sticky Footer Navigation Bar -->
        <div class="card-footer bg-light border-top p-3 px-md-5 d-flex align-items-center justify-content-between">
            <div>
                <!-- Previous Button (Hidden on Step 1) -->
                <button type="button" class="btn btn-outline-secondary px-3 d-none" id="btnWizardPrev">
                    <i class="bi bi-arrow-left me-1"></i> Previous
                </button>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/products" class="btn btn-outline-secondary px-3" id="btnWizardCancel">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted me-2 d-none d-sm-inline" id="stepIndicatorLabel">Step 1 of 5</span>
                
                <!-- Next Button (Steps 1, 2, 3, 4) -->
                <button type="button" class="btn btn-sj-primary px-4 fw-semibold" id="btnWizardNext">
                    Next <i class="bi bi-arrow-right ms-1"></i>
                </button>

                <!-- Final Submit Button (Visible ONLY on Step 5) -->
                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm d-none" id="btnSaveProduct">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSpinner" role="status" aria-hidden="true"></span>
                    <i class="bi bi-check2-circle me-1" id="saveIcon"></i>
                    <span id="saveBtnText">Save Product</span>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.step-wizard-nav .nav-link {
    border-radius: 8px;
    padding: 10px 14px;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid transparent;
}
.step-wizard-nav .nav-link .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background-color: #e2e8f0;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
}
.step-wizard-nav .nav-link.active {
    background-color: #eff4ff;
    color: #00288e;
    border-color: #bfdbfe;
}
.step-wizard-nav .nav-link.active .step-num {
    background-color: #00288e;
    color: #ffffff;
}
.step-wizard-nav .nav-link.is-complete {
    color: #15803d;
}
.step-wizard-nav .nav-link.is-complete .step-num {
    background-color: #dcfce7;
    color: #15803d;
}
.step-wizard-nav .nav-link.is-locked {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction.baseUrl || '<?= htmlspecialchars($baseUrl) ?>';
    const form = document.getElementById('productPageForm');
    const isEdit = <?= json_encode($isEdit) ?>;
    const currentProductId = document.getElementById('productId').value;
    
    let currentStep = 1;
    const totalSteps = 5;
    const completedSteps = new Set(isEdit ? [1, 2, 3, 4, 5] : []);
    let deletedImageIds = [];

    const btnNext = document.getElementById('btnWizardNext');
    const btnPrev = document.getElementById('btnWizardPrev');
    const btnCancel = document.getElementById('btnWizardCancel');
    const btnSave = document.getElementById('btnSaveProduct');
    const progressBar = document.getElementById('wizardProgressBar');
    const alertBox = document.getElementById('wizardAlertBox');
    const alertMsg = document.getElementById('wizardAlertMsg');
    const stepLabel = document.getElementById('stepIndicatorLabel');

    // Show step by number
    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;

        // Hide all steps
        for (let i = 1; i <= totalSteps; i++) {
            const pane = document.getElementById(`wizardStep${i}`);
            const tabBtn = document.getElementById(`step${i}-tab`);
            if (pane) pane.classList.add('d-none');
            if (tabBtn) tabBtn.classList.remove('active');
        }

        // Show target step
        const currentPane = document.getElementById(`wizardStep${step}`);
        const currentTabBtn = document.getElementById(`step${step}-tab`);
        if (currentPane) currentPane.classList.remove('d-none');
        if (currentTabBtn) currentTabBtn.classList.add('active');

        currentStep = step;
        hideAlert();

        // Update progress bar
        const pct = Math.round((step / totalSteps) * 100);
        progressBar.style.width = `${pct}%`;
        progressBar.setAttribute('aria-valuenow', pct);
        if (stepLabel) stepLabel.textContent = `Step ${step} of ${totalSteps}`;

        // Update buttons:
        // On step 1: Hide Previous, show Cancel
        if (step === 1) {
            btnPrev.classList.add('d-none');
            btnCancel.classList.remove('d-none');
        } else {
            btnPrev.classList.remove('d-none');
            btnCancel.classList.add('d-none');
        }

        // On steps 1-4: Show "Next" button, hide "Save Product"
        // On step 5 (LAST FORM): Hide "Next", show "Save Product"
        if (step === totalSteps) {
            btnNext.classList.add('d-none');
            btnSave.classList.remove('d-none');
        } else {
            btnNext.classList.remove('d-none');
            btnSave.classList.add('d-none');
        }

        // Scroll smoothly to top of card
        window.scrollTo({ top: document.querySelector('.sj-card').offsetTop - 30, behavior: 'smooth' });
    }

    function showAlert(msg) {
        alertMsg.textContent = msg;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        alertBox.classList.add('d-none');
    }

    // Step Validation Functions
    function validateStep(step) {
        let isValid = true;
        let firstInvalid = null;

        if (step === 1) {
            const title = document.getElementById('prodTitle');
            const supplier = document.getElementById('prodSupplier');
            const category = document.getElementById('prodCategory');
            const subcategory = document.getElementById('prodSubcategory');

            [title, supplier, category, subcategory].forEach(el => {
                el.classList.remove('is-invalid');
                if (!el.value || el.value.trim() === '') {
                    el.classList.add('is-invalid');
                    isValid = false;
                    if (!firstInvalid) firstInvalid = el;
                }
            });

            if (!isValid) {
                showAlert('Please fill in all required fields (Product Name, Supplier, Category, and Subcategory) before proceeding.');
            }
        } else if (step === 2) {
            const minPrice = document.getElementById('prodMinPrice');
            const maxPrice = document.getElementById('prodMaxPrice');
            const priceUnit = document.getElementById('prodPriceUnit');
            const moq = document.getElementById('prodMoq');
            const moqUnit = document.getElementById('prodMoqUnit');

            [minPrice, maxPrice, priceUnit, moq, moqUnit].forEach(el => {
                el.classList.remove('is-invalid');
                if (!el.value || el.value.trim() === '') {
                    el.classList.add('is-invalid');
                    isValid = false;
                    if (!firstInvalid) firstInvalid = el;
                }
            });

            const minVal = parseFloat(minPrice.value || '0');
            const maxVal = parseFloat(maxPrice.value || '0');
            if (minVal < 0) {
                minPrice.classList.add('is-invalid');
                isValid = false;
                if (!firstInvalid) firstInvalid = minPrice;
            }
            if (maxVal < minVal) {
                maxPrice.classList.add('is-invalid');
                isValid = false;
                if (!firstInvalid) firstInvalid = maxPrice;
                showAlert('Max Wholesale Price must be greater than or equal to Min Wholesale Price.');
                return false;
            }

            if (!isValid) {
                showAlert('Please enter valid pricing and MOQ numbers before proceeding.');
            }
        } else if (step === 4) {
            const status = document.getElementById('prodStatus');
            status.classList.remove('is-invalid');
            if (!status.value) {
                status.classList.add('is-invalid');
                isValid = false;
                firstInvalid = status;
                showAlert('Please select a valid publishing status.');
            }
        }

        if (!isValid && firstInvalid) {
            firstInvalid.focus();
        }

        return isValid;
    }

    // Next Button Click
    btnNext.addEventListener('click', function () {
        if (!validateStep(currentStep)) {
            return;
        }

        // Mark current step as completed
        completedSteps.add(currentStep);
        const tabBtn = document.getElementById(`step${currentStep}-tab`);
        if (tabBtn) tabBtn.classList.add('is-complete');

        goToStep(currentStep + 1);
    });

    // Previous Button Click
    btnPrev.addEventListener('click', function () {
        goToStep(currentStep - 1);
    });

    // Tab Header Clicks: User is NOT able to jump to next step without completing earlier details
    document.querySelectorAll('.step-tab-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetStep = parseInt(this.getAttribute('data-step'));

            if (targetStep === currentStep) return;

            // If navigating backwards, allow freely
            if (targetStep < currentStep) {
                goToStep(targetStep);
                return;
            }

            // If navigating forward, must validate all steps in between
            for (let s = 1; s < targetStep; s++) {
                if (!validateStep(s)) {
                    showAlert(`Please complete Step ${s} before proceeding to Step ${targetStep}.`);
                    goToStep(s);
                    return;
                }
                completedSteps.add(s);
                const prevTabBtn = document.getElementById(`step${s}-tab`);
                if (prevTabBtn) prevTabBtn.classList.add('is-complete');
            }

            goToStep(targetStep);
        });
    });

    // Dynamic Subcategories Loader
    function loadSubcategories(categoryId, selectedSubId = null) {
        const subSelect = document.getElementById('prodSubcategory');
        subSelect.innerHTML = '<option value="">Loading subcategories...</option>';
        if (!categoryId) {
            subSelect.innerHTML = '<option value="">Select Subcategory...</option>';
            return;
        }

        fetch(`${baseUrl}/api/masters/subcategories-by-category/${categoryId}`)
            .then(res => res.json())
            .then(data => {
                subSelect.innerHTML = '<option value="">Select Subcategory...</option>';
                if (data.data && data.data.length > 0) {
                    data.data.forEach(sub => {
                        const opt = document.createElement('option');
                        opt.value = sub.id;
                        opt.textContent = sub.name;
                        if (selectedSubId && parseInt(selectedSubId) === parseInt(sub.id)) {
                            opt.selected = true;
                        }
                        subSelect.appendChild(opt);
                    });
                }
            })
            .catch(() => {
                subSelect.innerHTML = '<option value="">Failed to load subcategories</option>';
            });
    }

    document.getElementById('prodCategory').addEventListener('change', function () {
        loadSubcategories(this.value);
    });

    // Live Primary Image Preview
    const primaryImgInput = document.getElementById('prodPrimaryImgFile');
    const primaryUrlInput = document.getElementById('prodPrimaryImgUrl');
    const previewImg = document.getElementById('primaryImgPreview');
    const defaultLogo = window.SportsJunction.defaultLogo || `${baseUrl}/assets/images/logo.jpg`;

    if (primaryImgInput) {
        primaryImgInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    if (primaryUrlInput) {
        primaryUrlInput.addEventListener('input', function () {
            if (this.value.trim() && (!primaryImgInput.files || primaryImgInput.files.length === 0)) {
                previewImg.src = this.value.trim();
            }
        });
    }
    previewImg.onerror = function () {
        this.onerror = null;
        this.src = defaultLogo;
    };

    // Delete existing gallery thumbnails
    document.querySelectorAll('.btn-delete-gallery-img').forEach(btn => {
        btn.addEventListener('click', function () {
            const item = this.closest('.gallery-thumb-item');
            const imgId = item.getAttribute('data-image-id');
            if (imgId) {
                deletedImageIds.push(imgId);
                document.getElementById('deletedImageIds').value = deletedImageIds.join(',');
            }
            item.remove();
        });
    });

    // Auto slug from title
    const prodTitle = document.getElementById('prodTitle');
    const prodSlug = document.getElementById('prodSlug');
    if (prodTitle && prodSlug && !isEdit) {
        prodTitle.addEventListener('blur', function () {
            if (!prodSlug.value.trim()) {
                prodSlug.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    }

    // FINAL FORM SUBMISSION (Only on Step 5: "Save Product")
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Validate all 5 steps before saving
        for (let s = 1; s <= totalSteps; s++) {
            if (!validateStep(s)) {
                goToStep(s);
                return;
            }
        }

        // Show spinner on Save button
        const saveSpinner = document.getElementById('saveSpinner');
        const saveIcon = document.getElementById('saveIcon');
        const saveBtnText = document.getElementById('saveBtnText');
        
        btnSave.disabled = true;
        if (saveSpinner) saveSpinner.classList.remove('d-none');
        if (saveIcon) saveIcon.classList.add('d-none');
        if (saveBtnText) saveBtnText.textContent = isEdit ? 'Updating Product...' : 'Saving Product...';

        const formData = new FormData(form);
        const endpoint = isEdit ? `${baseUrl}/api/products/${currentProductId}` : `${baseUrl}/api/products`;

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Redirect back to products inventory with toast indicator
                sessionStorage.setItem('product_flash_message', data.message || (isEdit ? 'Product updated successfully.' : 'Product created successfully.'));
                window.location.href = `${baseUrl}/admin/products`;
            } else {
                btnSave.disabled = false;
                if (saveSpinner) saveSpinner.classList.add('d-none');
                if (saveIcon) saveIcon.classList.remove('d-none');
                if (saveBtnText) saveBtnText.textContent = 'Save Product';

                showAlert(data.message || 'Validation error while saving product. Please check your inputs.');
            }
        })
        .catch(err => {
            console.error('Save failed:', err);
            btnSave.disabled = false;
            if (saveSpinner) saveSpinner.classList.add('d-none');
            if (saveIcon) saveIcon.classList.remove('d-none');
            if (saveBtnText) saveBtnText.textContent = 'Save Product';

            showAlert('Network or server error while saving product. Please try again.');
        });
    });
});
</script>
