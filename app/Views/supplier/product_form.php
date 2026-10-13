<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Uploader;

$baseUrl = Response::baseUrl();
$isEdit = !empty($isEdit) && !empty($product);
$actionUrl = $isEdit ? "{$baseUrl}/supplier/products/update/{$product['id']}" : "{$baseUrl}/supplier/products/store";
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-blue-900 transition-colors mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Back to Catalog</span>
            </a>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">
                <?= $isEdit ? 'Edit Equipment Specifications' : 'Submit New Equipment for Catalog Review' ?>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                <?= $isEdit 
                    ? 'Note: If this product is already active, edits will be reviewed by the Super Admin before appearing on the live marketplace.' 
                    : 'Submissions are verified by Super Admin compliance engineers before appearing publicly.' ?>
            </p>
        </div>
    </div>

    <form action="<?= htmlspecialchars($actionUrl) ?>" method="POST" enctype="multipart/form-data" class="space-y-6 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-10 shadow-xs">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">

        <!-- Section 1: Basic Info -->
        <div>
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-blue-900 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">sports_cricket</span>
                <span>1. Equipment Identity</span>
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Product Title / Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($product['title'] ?? '') ?>" placeholder="e.g. Grade-A English Willow Cricket Bat (Club Match Edition)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" id="prodCatSelect" required onchange="onCategoryChange(this.value)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subcategory <span class="text-red-500">*</span></label>
                        <select name="subcategory_id" id="prodSubcatSelect" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                            <option value="">Select Subcategory</option>
                            <?php if (!empty($subcategories)): ?>
                                <?php foreach ($subcategories as $sub): ?>
                                    <option value="<?= (int)$sub['id'] ?>" <?= ((int)($product['subcategory_id'] ?? 0) === (int)$sub['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">SKU / Model Number</label>
                        <input type="text" name="sku" value="<?= htmlspecialchars($product['sku'] ?? '') ?>" placeholder="e.g. SJ-BAT-001" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Brand / Plant Label</label>
                        <input type="text" name="brand_name" value="<?= htmlspecialchars($product['brand_name'] ?? $supplier['company_name'] ?? '') ?>" placeholder="e.g. ProMaster Sports" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Pricing & Wholesale Tiers -->
        <div>
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-blue-900 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                <span>2. Wholesale Pricing &amp; Minimum Orders</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Minimum Price <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="min_price" required value="<?= htmlspecialchars((string)($product['min_price'] ?? '')) ?>" placeholder="450.00" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Maximum Price</label>
                    <input type="number" step="0.01" name="max_price" value="<?= htmlspecialchars((string)($product['max_price'] ?? '')) ?>" placeholder="650.00" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Price Unit <span class="text-red-500">*</span></label>
                    <select name="price_unit_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                        <?php foreach ($priceUnits as $pu): ?>
                            <option value="<?= (int)$pu['id'] ?>" <?= ((int)($product['price_unit_id'] ?? 1) === (int)$pu['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pu['name']) ?> (<?= htmlspecialchars($pu['symbol']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Minimum Order Quantity (MOQ) <span class="text-red-500">*</span></label>
                    <input type="number" name="moq" required min="1" value="<?= htmlspecialchars((string)($product['moq_quantity'] ?? 50)) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">MOQ Unit <span class="text-red-500">*</span></label>
                    <select name="moq_unit_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                        <?php foreach ($moqUnits as $mu): ?>
                            <option value="<?= (int)$mu['id'] ?>" <?= ((int)($product['moq_unit_id'] ?? 1) === (int)$mu['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mu['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Estimated Lead Time</label>
                    <input type="text" name="lead_time" value="<?= htmlspecialchars($product['lead_time'] ?? '15 - 25 Business Days') ?>" placeholder="15 - 25 Days" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none transition-all">
                </div>
            </div>
        </div>

        <!-- Section 3: Visual Assets -->
        <div>
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-blue-900 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">photo_camera</span>
                <span>3. Equipment Media &amp; Product Images</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Primary Catalog Image</label>
                    <input type="file" name="primary_image_file" accept="image/*" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-900 file:text-white hover:file:bg-blue-950">
                    <?php if (!empty($product['primary_image'])): ?>
                        <div class="mt-2 flex items-center gap-2">
                            <img src="<?= htmlspecialchars(Uploader::imageUrl($product['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-12 h-12 rounded-lg object-cover border border-slate-200">
                            <span class="text-[11px] text-slate-500">Current Primary Image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Additional Angle Gallery Images</label>
                    <input type="file" name="gallery_images[]" multiple accept="image/*" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                </div>
            </div>
        </div>

        <!-- Section 4: Specifications & Details -->
        <div>
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-blue-900 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">description</span>
                <span>4. Detailed Technical Specifications</span>
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Brief Summary</label>
                    <textarea name="short_description" rows="2" placeholder="Brief wholesale highlight..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-blue-700 outline-none"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Technical Specifications &amp; Material Data</label>
                    <textarea name="description" rows="5" placeholder="Include materials used (e.g. Japanese PU, Grade-A English Willow), dimensions, testing certifications, export packaging..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:border-blue-700 outline-none"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="text-xs font-bold text-slate-600 hover:underline">Cancel</a>
            <button type="submit" class="py-3 px-8 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-xs shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">send</span>
                <span><?= $isEdit ? 'Submit Changes for Approval' : 'Submit Product for Approval' ?></span>
            </button>
        </div>
    </form>
</div>

<script>
const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';

function onCategoryChange(catId) {
    const subSelect = document.getElementById('prodSubcatSelect');
    subSelect.innerHTML = '<option value="">Loading...</option>';

    if (!catId) {
        subSelect.innerHTML = '<option value="">Select Subcategory</option>';
        return;
    }

    fetch(`${baseUrl}/api/masters/subcategories-by-category/${catId}`)
        .then(r => r.json())
        .then(res => {
            subSelect.innerHTML = '<option value="">Select Subcategory</option>';
            if (res.data) {
                res.data.forEach(s => {
                    subSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
                });
            }
        })
        .catch(() => {
            subSelect.innerHTML = '<option value="">Select Subcategory</option>';
        });
}
</script>
