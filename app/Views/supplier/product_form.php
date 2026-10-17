<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Uploader;

$baseUrl = Response::baseUrl();
$isEdit = !empty($isEdit) && !empty($product);
$subcategories = $subcategories ?? [];
$actionUrl = $isEdit ? "{$baseUrl}/supplier/products/edit/{$product['id']}" : "{$baseUrl}/supplier/products/add";
$defaultLogo = "{$baseUrl}/assets/images/logo.jpg";
$currentPrimaryImg = !empty($product['primary_image']) ? Uploader::imageUrl($product['primary_image']) : $defaultLogo;
?>

<!-- Header & Breadcrumb -->
<div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                <li><a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="hover:text-blue-900 transition-colors">Dashboard</a></li>
                <li>/</li>
                <li><a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="hover:text-blue-900 transition-colors">My Products</a></li>
                <li>/</li>
                <li class="text-slate-900 font-bold"><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></li>
            </ol>
        </nav>
        <h1 class="font-headline font-extrabold text-xl sm:text-2xl text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-[24px] <?= $isEdit ? 'text-blue-800' : 'text-emerald-600' ?>">
                <?= $isEdit ? 'edit_square' : 'add_circle' ?>
            </span>
            <span><?= $isEdit ? 'Edit Product: ' . htmlspecialchars($product['name'] ?? $product['title'] ?? '') : 'Add Wholesale Product' ?></span>
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            <?= $isEdit 
                ? 'Complete all required sections below. Note: If this product is already live, updates will be reviewed by Super Admin before replacing the public version.' 
                : 'Complete all 5 steps below to submit sports equipment specifications for Super Admin catalog approval.' ?>
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-2xs transition-all">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Products</span>
        </a>
        <?php if ($isEdit && !empty($product['slug']) && ($product['approval_status'] ?? '') === 'APPROVED'): ?>
            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($product['slug']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-900 text-xs font-bold shadow-2xs transition-all">
                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                <span>View Live Page</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isEdit && !empty($product['pending_changes'])): ?>
    <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm">
        <div class="flex items-center gap-2 font-bold mb-1">
            <span class="material-symbols-outlined text-amber-600 text-[20px]">hourglass_top</span>
            <span>Product Revision Awaiting Super Admin Approval</span>
        </div>
        <p class="text-amber-700">You submitted modifications for this product. The proposed changes are currently queued for Super Admin review. Your active approved product remains live on the marketplace until the revision is approved.</p>
    </div>
<?php endif; ?>

<?php if ($isEdit && !empty($product['rejection_reason'])): ?>
    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm">
        <div class="flex items-center gap-2 font-bold mb-1">
            <span class="material-symbols-outlined text-rose-600 text-[20px]">cancel</span>
            <span><?= ($product['approval_status'] ?? '') === 'APPROVED' ? 'Previous Product Revision Was Rejected' : 'Product Submission Requires Attention' ?></span>
        </div>
        <p class="text-rose-700 mb-1"><strong>Feedback / Reason:</strong> <?= htmlspecialchars($product['rejection_reason']) ?></p>
        <small class="text-rose-500">Please review the feedback above, make the required corrections below, and re-submit for review.</small>
    </div>
<?php endif; ?>

<!-- Main 5-Step Wizard Card -->
<div class="sj-card bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden mb-10">
    <!-- Step Wizard Nav Header -->
    <div class="bg-white border-b border-slate-200">
        <ul class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 p-2.5 step-wizard-nav" id="wizardTabs" role="tablist">
            <li role="presentation">
                <button class="w-full step-tab-btn active" id="step1-tab" data-step="1" type="button" role="tab">
                    <span class="step-num">1</span>
                    <span class="step-title">Basic Info</span>
                </button>
            </li>
            <li role="presentation">
                <button class="w-full step-tab-btn" id="step2-tab" data-step="2" type="button" role="tab">
                    <span class="step-num">2</span>
                    <span class="step-title">Pricing &amp; MOQ</span>
                </button>
            </li>
            <li role="presentation">
                <button class="w-full step-tab-btn" id="step3-tab" data-step="3" type="button" role="tab">
                    <span class="step-num">3</span>
                    <span class="step-title">Specs &amp; Customization</span>
                </button>
            </li>
            <li role="presentation">
                <button class="w-full step-tab-btn" id="step4-tab" data-step="4" type="button" role="tab">
                    <span class="step-num">4</span>
                    <span class="step-title">Shipping &amp; Status</span>
                </button>
            </li>
            <li role="presentation" class="col-span-2 sm:col-span-1">
                <button class="w-full step-tab-btn" id="step5-tab" data-step="5" type="button" role="tab">
                    <span class="step-num">5</span>
                    <span class="step-title">Images &amp; Media</span>
                </button>
            </li>
        </ul>

        <!-- Step Progress Bar -->
        <div class="w-full bg-slate-100 h-1 overflow-hidden">
            <div class="bg-blue-900 h-1 transition-all duration-300" id="wizardProgressBar" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <!-- Wizard Form Body -->
    <form id="productPageForm" action="<?= htmlspecialchars($actionUrl) ?>" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id" id="productId" value="<?= htmlspecialchars((string)($product['id'] ?? '')) ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(CSRF::getToken()) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">
        <input type="hidden" name="supplier_id" id="prodSupplier" value="<?= (int)($supplier['id'] ?? 0) ?>">
        <input type="hidden" name="deleted_image_ids" id="deletedImageIds" value="">

        <div class="p-6 sm:p-8">
            <!-- Alert Container for Form-Level Notifications -->
            <div id="wizardAlertBox" class="hidden mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm items-center gap-2.5 shadow-2xs" role="alert">
                <span class="material-symbols-outlined text-red-600 text-[20px]">error</span>
                <span id="wizardAlertMsg" class="font-semibold">Please complete all required fields on this step before proceeding.</span>
            </div>

            <!-- ========================================================= -->
            <!-- STEP 1: BASIC INFO                                        -->
            <!-- ========================================================= -->
            <div class="wizard-step-pane" id="wizardStep1" data-step="1">
                <div class="flex items-center justify-between pb-3 mb-5 border-b border-slate-100">
                    <div>
                        <h2 class="font-headline font-bold text-base text-slate-900">1. Basic Product Information</h2>
                        <p class="text-xs text-slate-500">Enter essential catalog details, manufacturer assignment, and category categorization.</p>
                    </div>
                    <span class="text-xs font-bold bg-blue-50 text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg">Step 1 of 5</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-8">
                        <label for="prodTitle" class="block text-xs font-bold text-slate-700 mb-1.5">Product Name / Title <span class="text-red-500">*</span></label>
                        <input type="text" class="sj-input w-full" id="prodTitle" name="name" required placeholder="e.g. Professional PU Leather Football (Match Size 5)" maxlength="200" value="<?= htmlspecialchars($product['name'] ?? $product['title'] ?? '') ?>">
                        <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1">Product name / title is required (up to 200 characters).</div>
                    </div>

                    <div class="md:col-span-4">
                        <label for="prodSku" class="block text-xs font-bold text-slate-700 mb-1.5">Product SKU / Code</label>
                        <input type="text" class="sj-input w-full" id="prodSku" name="sku" placeholder="e.g. SJ-FTB-M05" maxlength="100" value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                        <div class="text-[11px] text-slate-400 mt-1">Optional factory identification or internal inventory SKU.</div>
                    </div>

                    <div class="md:col-span-6">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Supplier / Manufacturer <span class="text-red-500">*</span></label>
                        <input type="text" class="sj-input w-full bg-slate-100 text-slate-600 cursor-not-allowed" readonly value="<?= htmlspecialchars($supplier['company_name'] ?? $supplier['contact_person'] ?? 'Verified Supplier') ?>">
                        <div class="text-[11px] text-slate-400 mt-1">Automatically linked to your verified manufacturer account.</div>
                    </div>

                    <div class="md:col-span-6">
                        <label for="prodBrand" class="block text-xs font-bold text-slate-700 mb-1.5">Brand Name / Manufacturer Tag</label>
                        <input type="text" class="sj-input w-full" id="prodBrand" name="brand_name" placeholder="e.g. ApexSport Industries" maxlength="150" value="<?= htmlspecialchars($product['brand_name'] ?? $supplier['company_name'] ?? '') ?>">
                    </div>

                    <div class="md:col-span-12">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Product Categories <span class="text-red-500">*</span> <span class="text-slate-400 font-normal">(Select one or more)</span></label>
                        <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80" id="supplierCategoryContainer">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                <?php 
                                $assignedCatIds = !empty($product['category_ids']) ? array_map('intval', $product['category_ids']) : (!empty($product['category_id']) ? [(int)$product['category_id']] : []);
                                foreach ($categories as $cat): 
                                    $isCatChecked = in_array((int)$cat['id'], $assignedCatIds, true);
                                ?>
                                    <label class="flex items-center gap-2.5 p-2.5 bg-white rounded-xl border border-slate-200/80 hover:border-blue-400 cursor-pointer transition-colors shadow-2xs">
                                        <input type="checkbox" name="category_ids[]" value="<?= (int)$cat['id'] ?>" class="category-checkbox rounded text-blue-900 focus:ring-blue-800" <?= $isCatChecked ? 'checked' : '' ?>>
                                        <span class="text-xs font-semibold text-slate-800 truncate" title="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="category_id" id="prodPrimaryCategory" value="<?= (int)($product['category_id'] ?? 0) ?>">
                            <div class="text-[11px] text-slate-400 mt-2.5">Products can belong to multiple categories. The first selected category serves as primary.</div>
                            <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1" id="catInvalidFeedback">Please select at least one product category.</div>
                        </div>
                    </div>

                    <div class="md:col-span-12">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Assigned Subcategories <span class="text-slate-400 font-normal">(Optional, select relevant subcategories)</span></label>
                        <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5" id="supplierSubcategoryContainer">
                                <div class="col-span-full text-xs text-slate-400 py-2">Select one or more categories above to view available subcategories.</div>
                            </div>
                            <input type="hidden" name="subcategory_id" id="prodPrimarySubcategory" value="<?= (int)($product['subcategory_id'] ?? 0) ?>">
                            <div class="text-[11px] text-slate-400 mt-2.5">Subcategories are dynamically loaded according to selected categories.</div>
                        </div>
                    </div>

                    <div class="md:col-span-12">
                        <label for="prodSlug" class="block text-xs font-bold text-slate-700 mb-1.5">URL Slug <span class="text-slate-400 font-normal">(Auto-generated if left blank)</span></label>
                        <input type="text" class="sj-input w-full font-mono" id="prodSlug" name="slug" placeholder="e.g. professional-pu-leather-football" maxlength="260" value="<?= htmlspecialchars($product['slug'] ?? '') ?>">
                        <div class="text-[11px] text-slate-400 mt-1">Unique SEO web handle for buyer storefront discovery.</div>
                    </div>

                    <div class="md:col-span-12">
                        <label for="prodShortDesc" class="block text-xs font-bold text-slate-700 mb-1.5">Short Description / Key Highlights</label>
                        <input type="text" class="sj-input w-full" id="prodShortDesc" name="short_description" placeholder="One-sentence highlight summarizing construction, FIFA standard, or materials..." value="<?= htmlspecialchars($product['short_description'] ?? '') ?>">
                    </div>

                    <div class="md:col-span-12">
                        <label for="prodDescription" class="block text-xs font-bold text-slate-700 mb-1.5">Detailed Technical &amp; Commercial Description</label>
                        <textarea class="sj-input w-full" id="prodDescription" name="description" rows="5" placeholder="Comprehensive product specifications, factory manufacturing process, packaging options, and quality control guarantees..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- STEP 2: PRICING & MOQ                                     -->
            <!-- ========================================================= -->
            <div class="wizard-step-pane hidden" id="wizardStep2" data-step="2">
                <div class="flex items-center justify-between pb-3 mb-5 border-b border-slate-100">
                    <div>
                        <h2 class="font-headline font-bold text-base text-slate-900">2. Wholesale Pricing Tiers &amp; MOQ</h2>
                        <p class="text-xs text-slate-500">Configure minimum and maximum bulk pricing matrices and order quantity thresholds.</p>
                    </div>
                    <span class="text-xs font-bold bg-blue-50 text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg">Step 2 of 5</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-4">
                        <label for="prodMinPrice" class="block text-xs font-bold text-slate-700 mb-1.5">Min Wholesale Price (Tier 1) <span class="text-red-500">*</span></label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-slate-200 bg-slate-100 text-slate-600 font-bold text-sm">$</span>
                            <input type="number" step="0.01" min="0" class="sj-input w-full rounded-l-none" id="prodMinPrice" name="min_price" required placeholder="0.00" value="<?= htmlspecialchars((string)($product['min_price'] ?? '')) ?>">
                        </div>
                        <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1">Valid min price (&gt; 0) is required.</div>
                    </div>

                    <div class="md:col-span-4">
                        <label for="prodMaxPrice" class="block text-xs font-bold text-slate-700 mb-1.5">Max Wholesale Price (Tier 2) <span class="text-red-500">*</span></label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-slate-200 bg-slate-100 text-slate-600 font-bold text-sm">$</span>
                            <input type="number" step="0.01" min="0" class="sj-input w-full rounded-l-none" id="prodMaxPrice" name="max_price" required placeholder="0.00" value="<?= htmlspecialchars((string)($product['max_price'] ?? '')) ?>">
                        </div>
                        <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1">Max price must be greater than or equal to min price.</div>
                    </div>

                    <div class="md:col-span-4">
                        <label for="prodPriceUnit" class="block text-xs font-bold text-slate-700 mb-1.5">Unit <span class="text-red-500">*</span></label>
                        <select class="sj-input w-full" id="prodPriceUnit" name="price_unit_id" required>
                            <option value="">Select Unit...</option>
                            <?php foreach ($priceUnits as $pu): ?>
                                <option value="<?= (int)$pu['id'] ?>" <?= ((int)($product['price_unit_id'] ?? 1) === (int)$pu['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pu['name']) ?> (<?= htmlspecialchars($pu['symbol'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1">Please select a pricing unit.</div>
                    </div>

                    <div class="md:col-span-4">
                        <label for="prodMoq" class="block text-xs font-bold text-slate-700 mb-1.5">Minimum Order Quantity (MOQ) <span class="text-red-500">*</span></label>
                        <input type="number" class="sj-input w-full" id="prodMoq" name="moq" required min="1" placeholder="e.g. 50" value="<?= htmlspecialchars((string)($product['moq_quantity'] ?? $product['moq'] ?? 10)) ?>">
                        <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1">MOQ must be at least 1 unit.</div>
                    </div>

                    <div class="md:col-span-4">
                        <label for="prodMoqUnit" class="block text-xs font-bold text-slate-700 mb-1.5">MOQ Measurement Unit <span class="text-red-500">*</span></label>
                        <select class="sj-input w-full" id="prodMoqUnit" name="moq_unit_id" required>
                            <option value="">Select Measurement Unit...</option>
                            <?php foreach ($moqUnits as $mu): ?>
                                <option value="<?= (int)$mu['id'] ?>" <?= ((int)($product['moq_unit_id'] ?? 1) === (int)$mu['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mu['name']) ?> (<?= htmlspecialchars($mu['symbol'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1">Please select an MOQ unit.</div>
                    </div>

                    <div class="md:col-span-4">
                        <label for="prodAvailQty" class="block text-xs font-bold text-slate-700 mb-1.5">Available Ready Stock</label>
                        <input type="number" class="sj-input w-full" id="prodAvailQty" name="available_quantity" min="0" placeholder="e.g. 1000" value="<?= htmlspecialchars((string)($product['available_quantity'] ?? 1000)) ?>">
                    </div>

                    <div class="md:col-span-6">
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl mt-2 flex items-center gap-3">
                            <input class="w-4 h-4 accent-blue-900 rounded cursor-pointer" type="checkbox" id="prodSampleAvail" name="sample_available" value="1" <?= (!empty($product['sample_available'])) ? 'checked' : '' ?>>
                            <label class="text-xs font-bold text-slate-800 cursor-pointer" for="prodSampleAvail">
                                Paid Sample Available Before Bulk Order
                            </label>
                        </div>
                    </div>

                    <div class="md:col-span-6" id="samplePriceWrapper">
                        <label for="prodSamplePrice" class="block text-xs font-bold text-slate-700 mb-1.5">Sample Price Per Unit</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-slate-200 bg-slate-100 text-slate-600 font-bold text-sm">$</span>
                            <input type="number" step="0.01" min="0" class="sj-input w-full rounded-l-none" id="prodSamplePrice" name="sample_price" placeholder="0.00" value="<?= htmlspecialchars((string)($product['sample_price'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- STEP 3: SPECIFICATIONS & CUSTOMIZATION                    -->
            <!-- ========================================================= -->
            <div class="wizard-step-pane hidden" id="wizardStep3" data-step="3">
                <div class="flex items-center justify-between pb-3 mb-5 border-b border-slate-100">
                    <div>
                        <h2 class="font-headline font-bold text-base text-slate-900">3. Specifications &amp; Factory Customization</h2>
                        <p class="text-xs text-slate-500">Define physical characteristics, materials, and OEM/ODM private label capabilities.</p>
                    </div>
                    <span class="text-xs font-bold bg-blue-50 text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg">Step 3 of 5</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-6">
                        <label for="prodMaterial" class="block text-xs font-bold text-slate-700 mb-1.5">Material Specification</label>
                        <input type="text" class="sj-input w-full" id="prodMaterial" name="material" placeholder="e.g. Japanese PU Leather, Multi-Ply Poly-Cotton Lining" value="<?= htmlspecialchars($product['material'] ?? '') ?>">
                    </div>

                    <div class="md:col-span-6">
                        <label for="prodColors" class="block text-xs font-bold text-slate-700 mb-1.5">Available Colorways</label>
                        <input type="text" class="sj-input w-full" id="prodColors" name="colors" placeholder="e.g. White/Blue, Safety Orange, Neon Lime, Custom" value="<?= htmlspecialchars($product['colors'] ?? '') ?>">
                    </div>

                    <div class="md:col-span-6">
                        <label for="prodSize" class="block text-xs font-bold text-slate-700 mb-1.5">Dimensions / Size Matrix</label>
                        <input type="text" class="sj-input w-full" id="prodSize" name="size" placeholder="e.g. Standard Size 5 (Circumference 68-70cm)" value="<?= htmlspecialchars($product['size'] ?? '') ?>">
                    </div>

                    <div class="md:col-span-6">
                        <label for="prodWeight" class="block text-xs font-bold text-slate-700 mb-1.5">Unit Weight / Specification</label>
                        <input type="text" class="sj-input w-full" id="prodWeight" name="weight" placeholder="e.g. 410 - 450 grams" value="<?= htmlspecialchars($product['weight'] ?? '') ?>">
                    </div>

                    <div class="md:col-span-12">
                        <label class="block text-xs font-bold text-slate-700 mb-2">OEM / ODM Customization Capabilities</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input class="w-4 h-4 accent-blue-900 rounded" type="checkbox" id="checkCustomLogo" name="custom_logo" value="1" <?= (!empty($product['custom_logo'])) ? 'checked' : '' ?>>
                                <span class="material-symbols-outlined text-blue-700 text-[18px]">sell</span>
                                <span>Custom Logo Print</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input class="w-4 h-4 accent-blue-900 rounded" type="checkbox" id="checkCustomColor" name="custom_color" value="1" <?= (!empty($product['custom_color'])) ? 'checked' : '' ?>>
                                <span class="material-symbols-outlined text-rose-600 text-[18px]">palette</span>
                                <span>Custom Pantone Color</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input class="w-4 h-4 accent-blue-900 rounded" type="checkbox" id="checkCustomPkg" name="custom_packaging" value="1" <?= (!empty($product['custom_packaging'])) ? 'checked' : '' ?>>
                                <span class="material-symbols-outlined text-amber-600 text-[18px]">inventory_2</span>
                                <span>Custom Packaging</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input class="w-4 h-4 accent-blue-900 rounded" type="checkbox" id="checkOem" name="oem_available" value="1" <?= (!empty($product['oem_available'])) ? 'checked' : '' ?>>
                                <span class="material-symbols-outlined text-sky-600 text-[18px]">settings</span>
                                <span>OEM Service</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input class="w-4 h-4 accent-blue-900 rounded" type="checkbox" id="checkOdm" name="odm_available" value="1" <?= (!empty($product['odm_available'])) ? 'checked' : '' ?>>
                                <span class="material-symbols-outlined text-emerald-600 text-[18px]">design_services</span>
                                <span>ODM Design</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input class="w-4 h-4 accent-blue-900 rounded" type="checkbox" id="checkPrivateLabel" name="private_label" value="1" <?= (!empty($product['private_label'])) ? 'checked' : '' ?>>
                                <span class="material-symbols-outlined text-indigo-600 text-[18px]">verified_user</span>
                                <span>Private Label</span>
                            </label>
                        </div>
                    </div>

                    <div class="md:col-span-12">
                        <label for="prodCustomDetails" class="block text-xs font-bold text-slate-700 mb-1.5">Customization MOQ &amp; Production Details</label>
                        <textarea class="sj-input w-full" id="prodCustomDetails" name="customization_details" rows="3" placeholder="e.g. Silk-screen logo min order 100 pcs; custom debossed valve min order 500 pcs; branded presentation polybags included..."><?= htmlspecialchars($product['customization_details'] ?? '') ?></textarea>
                    </div>

                    <!-- Product Certificates Assignment -->
                    <div class="md:col-span-12">
                        <label class="block text-xs font-bold text-slate-800 mb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-emerald-600 text-[18px]">verified</span>
                            <span>Product Certificates &amp; Compliance Standards</span>
                        </label>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <?php if (!empty($certificates)): ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                    <?php foreach ($certificates as $cert): ?>
                                        <?php $isAssigned = in_array((int)$cert['id'], array_map('intval', $assignedCertificateIds ?? [])); ?>
                                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-blue-400 transition-colors">
                                            <input type="checkbox" name="certificate_ids[]" value="<?= (int)$cert['id'] ?>" class="w-4 h-4 accent-blue-900 rounded" <?= $isAssigned ? 'checked' : '' ?>>
                                            <div class="truncate">
                                                <?php if (!empty($cert['code'])): ?>
                                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200 uppercase"><?= htmlspecialchars($cert['code']) ?></span>
                                                <?php endif; ?>
                                                <span class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($cert['name']) ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-2">Assigned certificates display under "Factory Audit &amp; Quality Control" on your live product page.</div>
                            <?php else: ?>
                                <div class="text-xs text-slate-400">No product certificates available.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- STEP 4: SHIPPING & PUBLISHING                             -->
            <!-- ========================================================= -->
            <div class="wizard-step-pane hidden" id="wizardStep4" data-step="4">
                <div class="flex items-center justify-between pb-3 mb-5 border-b border-slate-100">
                    <div>
                        <h2 class="font-headline font-bold text-base text-slate-900">4. Shipping, Logistics &amp; Publishing Status</h2>
                        <p class="text-xs text-slate-500">Specify port dispatch details, estimated lead times, and marketplace publishing visibility.</p>
                    </div>
                    <span class="text-xs font-bold bg-blue-50 text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg">Step 4 of 5</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-6">
                        <label for="prodCountry" class="block text-xs font-bold text-slate-700 mb-1.5">Dispatch Origin / Country</label>
                        <select class="sj-input w-full" id="prodCountry" name="country_id">
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= ((int)($product['country_id'] ?? $supplier['country_id'] ?? 1) === (int)$c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['iso_alpha2']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-6">
                        <label for="prodLeadTime" class="block text-xs font-bold text-slate-700 mb-1.5">Estimated Production Lead Time</label>
                        <input type="text" class="sj-input w-full" id="prodLeadTime" name="lead_time" placeholder="e.g. 10 - 20 Business Days" value="<?= htmlspecialchars($product['lead_time'] ?? '10 - 20 Business Days') ?>">
                    </div>

                    <div class="md:col-span-12">
                        <label for="prodShippingInfo" class="block text-xs font-bold text-slate-700 mb-1.5">Shipping, Freight &amp; Port Dispatch Terms</label>
                        <textarea class="sj-input w-full" id="prodShippingInfo" name="shipping_info" rows="2" placeholder="e.g. FOB Nhava Sheva (Mumbai) / Mundra Port or Worldwide Express Air Freight"><?= htmlspecialchars($product['shipping_info'] ?? '') ?></textarea>
                    </div>

                    <div class="md:col-span-12">
                        <label for="prodExportPackaging" class="block text-xs font-bold text-slate-700 mb-1.5">Export Packaging Specifications</label>
                        <textarea class="sj-input w-full" id="prodExportPackaging" name="export_packaging" rows="2" placeholder="e.g. Heavy-duty 5-ply export master cartons, individual moisture-proof polybags, and corner protectors"><?= htmlspecialchars($product['export_packaging'] ?? '') ?></textarea>
                    </div>

                    <div class="md:col-span-6">
                        <label for="prodStatus" class="block text-xs font-bold text-slate-700 mb-1.5">Marketplace Publishing Status <span class="text-red-500">*</span></label>
                        <select class="sj-input w-full" id="prodStatus" name="status" required>
                            <option value="PUBLISHED" <?= (($product['status'] ?? 'PUBLISHED') === 'PUBLISHED') ? 'selected' : '' ?>>Published (Live After Admin Approval)</option>
                            <option value="DRAFT" <?= (($product['status'] ?? '') === 'DRAFT') ? 'selected' : '' ?>>Draft (Under Review)</option>
                            <option value="INACTIVE" <?= (($product['status'] ?? '') === 'INACTIVE') ? 'selected' : '' ?>>Inactive (Archived)</option>
                        </select>
                        <div class="invalid-msg hidden text-[11px] text-red-600 font-semibold mt-1">Please select a publishing status.</div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- STEP 5: IMAGES & MEDIA (FINAL STEP)                       -->
            <!-- ========================================================= -->
            <div class="wizard-step-pane hidden" id="wizardStep5" data-step="5">
                <div class="flex items-center justify-between pb-3 mb-5 border-b border-slate-100">
                    <div>
                        <h2 class="font-headline font-bold text-base text-slate-900">5. Images, Gallery &amp; Video Media</h2>
                        <p class="text-xs text-slate-500">Upload high-resolution photography, multi-angle product showcase, and demonstration links.</p>
                    </div>
                    <span class="text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg">Final Step 5 of 5</span>
                </div>

                <div class="space-y-6">
                    <!-- Primary Image Upload -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Primary Product Image</label>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-center gap-5">
                            <div class="w-28 h-28 rounded-xl border border-slate-200 bg-white shadow-2xs flex items-center justify-center overflow-hidden shrink-0">
                                <img id="primaryImgPreview" src="<?= htmlspecialchars($currentPrimaryImg) ?>" alt="Product Preview" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 w-full">
                                <label for="prodPrimaryImgFile" class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-blue-800 text-[18px]">cloud_upload</span>
                                    <span>Upload High-Res Image File</span>
                                </label>
                                <input type="file" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-900 file:text-white hover:file:bg-blue-950" id="prodPrimaryImgFile" name="primary_image_file" accept="image/jpeg,image/png,image/webp,image/gif">
                                <div class="text-[11px] text-slate-400 mt-1">Square aspect ratio recommended (e.g. 800×800px). Max 8MB file size. Updates live preview.</div>

                                <div class="mt-3 pt-3 border-t border-slate-200/80">
                                    <label for="prodPrimaryImgUrl" class="block text-[11px] font-semibold text-slate-500 mb-1">Or Provide Stored Image Path / External URL:</label>
                                    <input type="text" class="sj-input w-full font-mono text-xs py-1.5" id="prodPrimaryImgUrl" name="primary_image" placeholder="/uploads/products/... or https://..." value="<?= htmlspecialchars($product['primary_image'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Upload -->
                    <div>
                        <label for="prodGalleryFiles" class="block text-xs font-bold text-slate-700 mb-1.5">Add Additional Gallery Angles (Multi-Select)</label>
                        <input type="file" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-700 file:text-white hover:file:bg-slate-800" id="prodGalleryFiles" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/webp">
                        <div class="text-[11px] text-slate-400 mt-1">Select multiple files at once (JPG, PNG, WEBP, max 8MB each). Live thumbnails appear below.</div>
                        
                        <!-- New Gallery Images Live Preview Container -->
                        <div id="newGalleryPreviewContainer" class="hidden mt-3 p-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                            <div class="text-[11px] font-bold text-slate-700 mb-2 flex items-center justify-between">
                                <span>Newly Selected Photos (<span id="newGalleryCount">0</span>):</span>
                                <span class="text-slate-400 font-normal">Click &times; to remove before saving</span>
                            </div>
                            <div class="flex flex-wrap gap-3" id="newGalleryThumbnails"></div>
                        </div>
                    </div>

                    <!-- Existing Gallery Images -->
                    <?php if ($isEdit && !empty($product['images'])): ?>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Existing Gallery Photos (Click X to remove on save):</label>
                            <div class="flex flex-wrap gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200" id="existingGalleryContainer">
                                <?php foreach ($product['images'] as $img): ?>
                                    <div class="relative w-24 h-24 border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs gallery-thumb-item" data-image-id="<?= (int)$img['id'] ?>">
                                        <img src="<?= htmlspecialchars($img['image_url'] ?? Uploader::imageUrl($img['image_path'] ?? '')) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultLogo) ?>';" alt="Gallery" class="w-full h-full object-cover">
                                        <button type="button" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center shadow-xs btn-delete-gallery-img" title="Delete image">
                                            <span class="material-symbols-outlined text-[14px]">close</span>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Video URL -->
                    <div>
                        <label for="prodVideoUrl" class="block text-xs font-bold text-slate-700 mb-1.5">Demonstration Video Link</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-slate-200 bg-slate-100 text-red-600">
                                <span class="material-symbols-outlined text-[18px]">smart_display</span>
                            </span>
                            <input type="url" class="sj-input w-full rounded-l-none" id="prodVideoUrl" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($product['video_url'] ?? '') ?>">
                        </div>
                        <div class="text-[11px] text-slate-400 mt-1">Embeddable YouTube, Vimeo, or factory demonstration video link.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Sticky Footer Navigation Bar -->
        <div class="bg-slate-50 border-t border-slate-200 px-6 sm:px-8 py-4 flex items-center justify-between">
            <div>
                <!-- Previous Button (Hidden on Step 1) -->
                <button type="button" class="hidden inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-all" id="btnWizardPrev">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Previous</span>
                </button>
                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-all" id="btnWizardCancel">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                    <span>Cancel</span>
                </a>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-slate-500 hidden sm:inline" id="stepIndicatorLabel">Step 1 of 5</span>

                <!-- Next Button (Steps 1, 2, 3, 4) -->
                <button type="button" class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white text-xs font-bold shadow-sm transition-all" id="btnWizardNext">
                    <span>Next</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </button>

                <!-- Final Submit Button (Visible ONLY on Step 5) -->
                <button type="submit" class="hidden inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-all" id="btnSaveProduct">
                    <span class="material-symbols-outlined text-[18px] animate-spin hidden" id="saveSpinner">progress_activity</span>
                    <span class="material-symbols-outlined text-[18px]" id="saveIcon">check_circle</span>
                    <span id="saveBtnText">Save Product</span>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.sj-input {
    padding: 0.625rem 0.875rem;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    color: #0f172a;
    outline: none;
    transition: all 0.15s ease;
}
.sj-input:focus {
    background-color: #ffffff;
    border-color: #1e40af;
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.12);
}
.sj-input.is-invalid {
    border-color: #dc2626 !important;
    background-color: #fef2f2 !important;
}
.step-wizard-nav .step-tab-btn {
    border-radius: 0.625rem;
    padding: 0.625rem 0.75rem;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: 1px solid transparent;
}
.step-wizard-nav .step-tab-btn .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 9999px;
    background-color: #e2e8f0;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
}
.step-wizard-nav .step-tab-btn.active {
    background-color: #eff6ff;
    color: #00288e;
    border-color: #bfdbfe;
}
.step-wizard-nav .step-tab-btn.active .step-num {
    background-color: #00288e;
    color: #ffffff;
}
.step-wizard-nav .step-tab-btn.is-complete {
    color: #15803d;
}
.step-wizard-nav .step-tab-btn.is-complete .step-num {
    background-color: #dcfce7;
    color: #15803d;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';
    const defaultLogo = '<?= htmlspecialchars($defaultLogo) ?>';
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

    function showAlert(msg) {
        alertMsg.textContent = msg;
        alertBox.classList.remove('hidden');
        alertBox.classList.add('flex');
    }

    function hideAlert() {
        alertBox.classList.add('hidden');
        alertBox.classList.remove('flex');
    }

    function setInvalid(el, invalid) {
        if (!el) return;
        const container = el.closest('div')?.parentElement || el.parentElement;
        const feedback = container ? container.querySelector('.invalid-msg') : null;
        if (invalid) {
            el.classList.add('is-invalid');
            if (feedback) feedback.classList.remove('hidden');
        } else {
            el.classList.remove('is-invalid');
            if (feedback) feedback.classList.add('hidden');
        }
    }

    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;

        for (let i = 1; i <= totalSteps; i++) {
            const pane = document.getElementById(`wizardStep${i}`);
            const tabBtn = document.getElementById(`step${i}-tab`);
            if (pane) pane.classList.add('hidden');
            if (tabBtn) tabBtn.classList.remove('active');
        }

        const currentPane = document.getElementById(`wizardStep${step}`);
        const currentTabBtn = document.getElementById(`step${step}-tab`);
        if (currentPane) currentPane.classList.remove('hidden');
        if (currentTabBtn) currentTabBtn.classList.add('active');

        currentStep = step;
        hideAlert();

        const pct = Math.round((step / totalSteps) * 100);
        progressBar.style.width = `${pct}%`;
        progressBar.setAttribute('aria-valuenow', pct);
        if (stepLabel) stepLabel.textContent = `Step ${step} of ${totalSteps}`;

        if (step === 1) {
            btnPrev.classList.add('hidden');
            btnCancel.classList.remove('hidden');
        } else {
            btnPrev.classList.remove('hidden');
            btnCancel.classList.add('hidden');
        }

        // Steps 1-4 show "Next", Step 5 shows "Save Product"
        if (step === totalSteps) {
            btnNext.classList.add('hidden');
            btnSave.classList.remove('hidden');
        } else {
            btnNext.classList.remove('hidden');
            btnSave.classList.add('hidden');
        }

        const cardTop = document.querySelector('.sj-card');
        if (cardTop) {
            window.scrollTo({ top: cardTop.offsetTop - 24, behavior: 'smooth' });
        }
    }

    function validateStep(step) {
        let isValid = true;
        let firstInvalid = null;

        if (step === 1) {
            const title = document.getElementById('prodTitle');
            const checkedCats = document.querySelectorAll('input[name="category_ids[]"]:checked');
            const catFeedback = document.getElementById('catInvalidFeedback');

            let step1Valid = true;
            if (title) {
                setInvalid(title, false);
                if (!title.value || title.value.trim() === '') {
                    setInvalid(title, true);
                    step1Valid = false;
                    if (!firstInvalid) firstInvalid = title;
                }
            }

            if (checkedCats.length === 0) {
                if (catFeedback) catFeedback.classList.remove('hidden');
                step1Valid = false;
                if (!firstInvalid) firstInvalid = document.getElementById('supplierCategoryContainer');
            } else {
                if (catFeedback) catFeedback.classList.add('hidden');
            }

            if (!step1Valid) {
                showAlert('Please fill in all required fields (Product Name and at least one Category) before proceeding.');
                return false;
            }
        } else if (step === 2) {
            const minPrice = document.getElementById('prodMinPrice');
            const maxPrice = document.getElementById('prodMaxPrice');
            const priceUnit = document.getElementById('prodPriceUnit');
            const moq = document.getElementById('prodMoq');
            const moqUnit = document.getElementById('prodMoqUnit');

            [minPrice, maxPrice, priceUnit, moq, moqUnit].forEach(el => {
                setInvalid(el, false);
                if (!el.value || el.value.trim() === '') {
                    setInvalid(el, true);
                    isValid = false;
                    if (!firstInvalid) firstInvalid = el;
                }
            });

            const minVal = parseFloat(minPrice.value || '0');
            const maxVal = parseFloat(maxPrice.value || '0');
            if (minVal < 0) {
                setInvalid(minPrice, true);
                isValid = false;
                if (!firstInvalid) firstInvalid = minPrice;
            }
            if (maxPrice.value !== '' && maxVal < minVal) {
                setInvalid(maxPrice, true);
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
            setInvalid(status, false);
            if (!status.value) {
                setInvalid(status, true);
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
        completedSteps.add(currentStep);
        const tabBtn = document.getElementById(`step${currentStep}-tab`);
        if (tabBtn) tabBtn.classList.add('is-complete');
        goToStep(currentStep + 1);
    });

    // Previous Button Click
    btnPrev.addEventListener('click', function () {
        goToStep(currentStep - 1);
    });

    // Tab Header Clicks: Prevent jumping ahead without completing earlier steps
    document.querySelectorAll('.step-tab-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetStep = parseInt(this.getAttribute('data-step'), 10);
            if (targetStep === currentStep) return;

            if (targetStep < currentStep) {
                goToStep(targetStep);
                return;
            }

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

    // Dynamic Subcategories Loader for Multiple Selected Categories
    const initialAssignedSubIds = <?= json_encode(!empty($product['subcategory_ids']) ? array_map('intval', $product['subcategory_ids']) : (!empty($product['subcategory_id']) ? [(int)$product['subcategory_id']] : [])) ?>;
    let selectedSubcategoryIds = new Set(initialAssignedSubIds);

    function getSelectedCategoryIds() {
        const checked = document.querySelectorAll('.category-checkbox:checked');
        return Array.from(checked).map(cb => parseInt(cb.value)).filter(id => !isNaN(id) && id > 0);
    }

    function updateSubcategoriesList() {
        const catIds = getSelectedCategoryIds();
        const container = document.getElementById('supplierSubcategoryContainer');
        const hiddenPrimaryCat = document.getElementById('prodPrimaryCategory');
        const hiddenPrimarySub = document.getElementById('prodPrimarySubcategory');

        if (hiddenPrimaryCat) {
            hiddenPrimaryCat.value = catIds.length > 0 ? catIds[0] : '';
        }

        if (catIds.length === 0) {
            container.innerHTML = '<div class="col-span-full text-xs text-slate-400 py-2">Select one or more categories above to view available subcategories.</div>';
            if (hiddenPrimarySub) hiddenPrimarySub.value = '';
            return;
        }

        container.innerHTML = '<div class="col-span-full text-xs text-slate-400 py-2"><span class="animate-spin inline-block w-3 h-3 border-2 border-current border-t-transparent rounded-full me-2"></span>Loading subcategories for selected categories...</div>';

        fetch(`${baseUrl}/api/masters/subcategories-by-categories?category_ids=${catIds.join(',')}`)
            .then(res => res.json())
            .then(data => {
                const subs = data.data || [];
                if (subs.length === 0) {
                    container.innerHTML = '<div class="col-span-full text-xs text-slate-400 py-2">No active subcategories found for the selected categories.</div>';
                    if (hiddenPrimarySub) hiddenPrimarySub.value = '';
                    return;
                }

                let html = '';
                subs.forEach(sub => {
                    const isChecked = selectedSubcategoryIds.has(parseInt(sub.id));
                    html += `
                        <label class="flex items-center gap-2.5 p-2.5 bg-white rounded-xl border border-slate-200/80 hover:border-blue-400 cursor-pointer transition-colors shadow-2xs">
                            <input type="checkbox" name="subcategory_ids[]" value="${sub.id}" class="subcategory-checkbox rounded text-blue-900 focus:ring-blue-800" ${isChecked ? 'checked' : ''}>
                            <span class="text-xs font-semibold text-slate-800 truncate" title="${sub.name}">
                                <span class="text-[10px] text-slate-400 font-normal mr-1">[${sub.category_name || 'Category'}]</span>
                                ${sub.name}
                            </span>
                        </label>
                    `;
                });
                container.innerHTML = html;

                container.querySelectorAll('.subcategory-checkbox').forEach(sc => {
                    sc.addEventListener('change', function() {
                        const sid = parseInt(this.value);
                        if (this.checked) {
                            selectedSubcategoryIds.add(sid);
                        } else {
                            selectedSubcategoryIds.delete(sid);
                        }
                        const firstChecked = container.querySelector('.subcategory-checkbox:checked');
                        if (hiddenPrimarySub) {
                            hiddenPrimarySub.value = firstChecked ? firstChecked.value : '';
                        }
                    });
                });

                const firstChecked = container.querySelector('.subcategory-checkbox:checked');
                if (hiddenPrimarySub) {
                    hiddenPrimarySub.value = firstChecked ? firstChecked.value : '';
                }
            })
            .catch(() => {
                container.innerHTML = '<div class="col-span-full text-xs text-rose-500 py-2">Failed to load subcategories.</div>';
            });
    }

    document.querySelectorAll('.category-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const catFeedback = document.getElementById('catInvalidFeedback');
            if (catFeedback) catFeedback.classList.add('hidden');
            updateSubcategoriesList();
        });
    });

    updateSubcategoriesList();

    // Live Primary Image Preview
    const primaryImgInput = document.getElementById('prodPrimaryImgFile');
    const primaryUrlInput = document.getElementById('prodPrimaryImgUrl');
    const previewImg = document.getElementById('primaryImgPreview');

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
    if (previewImg) {
        previewImg.onerror = function () {
            this.onerror = null;
            this.src = defaultLogo;
        };
    }

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

    // Live Gallery Images Preview with DataTransfer
    const galleryInput = document.getElementById('prodGalleryFiles');
    const newGalleryContainer = document.getElementById('newGalleryPreviewContainer');
    const newGalleryThumbnails = document.getElementById('newGalleryThumbnails');
    const newGalleryCount = document.getElementById('newGalleryCount');
    let galleryDataTransfer = new DataTransfer();

    if (galleryInput && newGalleryThumbnails) {
        galleryInput.addEventListener('change', function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 8 * 1024 * 1024; // 8MB

            Array.from(this.files).forEach(file => {
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Format',
                        text: `"${file.name}" is not a supported JPG, PNG, or WEBP image.`
                    });
                    return;
                }
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: `"${file.name}" exceeds the 8MB limit.`
                    });
                    return;
                }
                galleryDataTransfer.items.add(file);
            });

            galleryInput.files = galleryDataTransfer.files;
            renderNewGalleryPreviews();
        });

        function renderNewGalleryPreviews() {
            newGalleryThumbnails.innerHTML = '';
            const files = Array.from(galleryDataTransfer.files);
            if (files.length === 0) {
                newGalleryContainer.classList.add('hidden');
                return;
            }

            newGalleryContainer.classList.remove('hidden');
            if (newGalleryCount) newGalleryCount.textContent = files.length;

            files.forEach((file, index) => {
                const thumbWrapper = document.createElement('div');
                thumbWrapper.className = 'relative w-24 h-24 border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs group';

                const img = document.createElement('img');
                img.className = 'w-full h-full object-cover';
                img.alt = file.name;

                const reader = new FileReader();
                reader.onload = e => {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute top-1 right-1 w-6 h-6 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center shadow-xs transition-transform hover:scale-110';
                removeBtn.title = 'Remove ' + file.name;
                removeBtn.innerHTML = '<span class="material-symbols-outlined text-[14px]">close</span>';
                removeBtn.addEventListener('click', function () {
                    const dt = new DataTransfer();
                    Array.from(galleryDataTransfer.files).forEach((f, i) => {
                        if (i !== index) dt.items.add(f);
                    });
                    galleryDataTransfer = dt;
                    galleryInput.files = galleryDataTransfer.files;
                    renderNewGalleryPreviews();
                });

                thumbWrapper.appendChild(img);
                thumbWrapper.appendChild(removeBtn);
                newGalleryThumbnails.appendChild(thumbWrapper);
            });
        }
    }

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

    // Final Form Submission (Step 5: "Save Product")
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        for (let s = 1; s <= totalSteps; s++) {
            if (!validateStep(s)) {
                goToStep(s);
                return;
            }
        }

        const saveSpinner = document.getElementById('saveSpinner');
        const saveIcon = document.getElementById('saveIcon');
        const saveBtnText = document.getElementById('saveBtnText');

        btnSave.disabled = true;
        if (saveSpinner) saveSpinner.classList.remove('hidden');
        if (saveIcon) saveIcon.classList.add('hidden');
        if (saveBtnText) saveBtnText.textContent = isEdit ? 'Updating Product...' : 'Saving Product...';

        const formData = new FormData(form);
        const endpoint = isEdit ? `${baseUrl}/supplier/products/edit/${currentProductId}` : `${baseUrl}/supplier/products/add`;

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
                window.location.href = `${baseUrl}/supplier/products`;
            } else {
                btnSave.disabled = false;
                if (saveSpinner) saveSpinner.classList.add('hidden');
                if (saveIcon) saveIcon.classList.remove('hidden');
                if (saveBtnText) saveBtnText.textContent = 'Save Product';

                showAlert(data.message || 'Validation error while saving product. Please check your inputs.');
            }
        })
        .catch(err => {
            console.error('Save failed:', err);
            btnSave.disabled = false;
            if (saveSpinner) saveSpinner.classList.add('hidden');
            if (saveIcon) saveIcon.classList.remove('hidden');
            if (saveBtnText) saveBtnText.textContent = 'Save Product';

            showAlert('Network or server error while saving product. Please try again.');
        });
    });
});
</script>
