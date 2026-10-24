<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
$sortProducts = !empty($sortProducts) ? $sortProducts : (!empty($favourites) ? $favourites : []);
$totalItems = count($sortProducts);
$availableItemsCount = 0;
foreach ($sortProducts as $p) {
    if (!empty($p['is_available'])) {
        $availableItemsCount++;
    }
}
$allCountries = !empty($countries) ? $countries : \App\Models\Country::allActive();
$buyerCountryId = (int)($buyer['country_id'] ?? 0);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb & Page Title -->
    <div class="mb-6">
        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="inline-flex items-center gap-1 text-xs font-bold text-blue-900 hover:underline mb-2">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Buyer Dashboard</span>
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-900 text-3xl">checklist</span>
                    <span>My Sort List</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Review shortlisted products, select items, and submit one centralized bulk enquiry directly to verified manufacturers.
                </p>
            </div>
            <div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/bulk-enquiries" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    <span class="material-symbols-outlined text-[16px] text-blue-900">receipt_long</span>
                    <span>View Submitted Bulk Enquiries</span>
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($sortProducts)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400 max-w-2xl mx-auto my-6 shadow-xs">
            <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-900 flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-3xl">format_list_bulleted</span>
            </div>
            <h3 class="font-headline font-bold text-lg text-slate-800 mb-1">Your Sort List is Empty</h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mb-6 max-w-md mx-auto">
                Browse our verified sports equipment catalog and click <strong>"Add to Sort"</strong> on any product to assemble multiple items for one bulk enquiry.
            </p>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">search</span>
                <span>Explore Sports Catalog</span>
            </a>
        </div>
    <?php else: ?>
        <!-- Sort List Controls Toolbar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 mb-6 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Left: Selection controls & counter -->
                <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800 select-none">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded text-blue-900 border-slate-300 focus:ring-blue-700 focus:ring-offset-0">
                        <span>Select All (<span id="availableCounter"><?= $availableItemsCount ?></span> eligible)</span>
                    </label>
                    <span class="text-slate-300 hidden sm:inline">|</span>
                    <div class="text-xs font-semibold text-slate-600">
                        <span id="selectedCountText" class="font-bold text-blue-900">0</span> of <?= $totalItems ?> products selected
                    </div>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btnRemoveSelected" onclick="removeSelectedProducts()" class="hidden px-3.5 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-all items-center gap-1.5" title="Remove selected products from Sort List">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                        <span>Remove Selected (<span id="removeSelectedCount">0</span>)</span>
                    </button>
                    <button type="button" onclick="clearSortList()" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold transition-colors inline-flex items-center gap-1.5" title="Remove all items from Sort List">
                        <span class="material-symbols-outlined text-[16px]">clear_all</span>
                        <span>Clear List</span>
                    </button>
                    <button type="button" id="btnOpenBulkModal" onclick="openBulkEnquiryModal()" disabled class="px-5 py-2.5 rounded-xl bg-blue-900 text-white text-xs font-bold transition-all inline-flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-950 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        <span>Send Bulk Enquiry (<span id="bulkBtnCount">0</span>)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($sortProducts as $fav): ?>
                <?php
                    $isAvailable = !empty($fav['is_available']);
                    $productImg = \App\Helpers\Uploader::imageUrl($fav['primary_image'] ?? null);
                    $productUrl = htmlspecialchars($baseUrl) . '/product/' . htmlspecialchars($fav['slug'] ?? (string)$fav['id']);
                ?>
                <div class="bg-white rounded-2xl border <?= $isAvailable ? 'border-slate-200/80 hover:border-blue-700 shadow-xs' : 'border-amber-200 bg-amber-50/20' ?> p-4 flex flex-col justify-between group transition-all relative product-sort-card" data-id="<?= (int)$fav['id'] ?>">
                    <div>
                        <!-- Card Top: Checkbox & Remove button -->
                        <div class="flex items-center justify-between mb-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" 
                                       class="product-select-checkbox w-4 h-4 rounded text-blue-900 border-slate-300 focus:ring-blue-700 focus:ring-offset-0 <?= !$isAvailable ? 'opacity-40 cursor-not-allowed' : '' ?>"
                                       value="<?= (int)$fav['id'] ?>"
                                       data-id="<?= (int)$fav['id'] ?>"
                                       data-name="<?= htmlspecialchars($fav['name']) ?>"
                                       data-supplier-id="<?= (int)($fav['supplier_id'] ?? 1) ?>"
                                       data-supplier="<?= htmlspecialchars($fav['supplier_name'] ?? 'Verified Factory') ?>"
                                       data-moq="<?= (int)($fav['moq_quantity'] ?? 1) ?>"
                                       data-moq-unit="<?= htmlspecialchars($fav['moq_unit_name'] ?? 'units') ?>"
                                       data-image="<?= htmlspecialchars($productImg) ?>"
                                       data-price="<?= number_format((float)$fav['min_price'], 2) ?>"
                                       data-available="<?= $isAvailable ? '1' : '0' ?>"
                                       <?= !$isAvailable ? 'disabled' : '' ?>
                                       onchange="onProductSelectChange()">
                                <span class="text-[11px] font-bold <?= $isAvailable ? 'text-slate-700' : 'text-slate-400' ?>">
                                    <?= $isAvailable ? 'Select for Enquiry' : 'Unavailable' ?>
                                </span>
                            </label>

                            <button type="button" onclick="removeSingleSortItem(<?= (int)$fav['id'] ?>, '<?= htmlspecialchars(addslashes($fav['name'])) ?>')" class="w-7 h-7 rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors" title="Remove from Sort List">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>

                        <!-- Product Thumbnail -->
                        <div class="relative rounded-xl overflow-hidden mb-3 aspect-video sm:aspect-square bg-slate-50 border border-slate-100">
                            <a href="<?= $productUrl ?>">
                                <img src="<?= htmlspecialchars($productImg) ?>" alt="<?= htmlspecialchars($fav['name']) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </a>

                            <?php if (!$isAvailable): ?>
                                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[1px] flex items-center justify-center p-3 text-center">
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-500 text-white font-bold text-[10px] uppercase tracking-wider">
                                        <?= htmlspecialchars($fav['unavailable_reason']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Details -->
                        <div class="text-[11px] font-bold text-blue-900 uppercase tracking-wider mb-1">
                            <?= htmlspecialchars($fav['category_name'] ?? 'Sports Equipment') ?>
                        </div>
                        <h3 class="font-bold text-sm text-slate-900 line-clamp-2 mb-1.5 hover:text-blue-900">
                            <a href="<?= $productUrl ?>"><?= htmlspecialchars($fav['name']) ?></a>
                        </h3>
                        
                        <?php if (!empty($fav['sku'])): ?>
                            <div class="text-[10px] font-mono text-slate-400 mb-1">SKU: <?= htmlspecialchars($fav['sku']) ?></div>
                        <?php endif; ?>

                        <div class="text-xs text-slate-500 font-medium mb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[15px] text-teal-600">location_on</span>
                            <span class="text-[11px]"><?= htmlspecialchars($fav['country_name'] ?? 'India') ?></span>
                        </div>

                        <div class="flex items-baseline justify-between pt-2 border-t border-slate-100">
                            <div class="font-headline font-extrabold text-blue-900 text-sm sm:text-base">
                                $<?= number_format((float)$fav['min_price'], 2) ?>
                                <?php if ((float)$fav['max_price'] > (float)$fav['min_price']): ?>
                                    - $<?= number_format((float)$fav['max_price'], 2) ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-500">
                                MOQ: <strong class="text-slate-800"><?= (int)$fav['moq_quantity'] ?> <?= htmlspecialchars($fav['moq_unit_name'] ?? 'units') ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                        <a href="<?= $productUrl ?>" class="flex-1 text-center py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition-colors">
                            View Details
                        </a>
                        <button type="button" onclick="removeSingleSortItem(<?= (int)$fav['id'] ?>, '<?= htmlspecialchars(addslashes($fav['name'])) ?>')" class="py-2 px-3 rounded-xl border border-slate-200 hover:bg-rose-50 hover:border-rose-200 text-slate-500 hover:text-rose-600 text-xs font-bold transition-colors inline-flex items-center gap-1" title="Remove from Sort List">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                            <span>Remove</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- BULK ENQUIRY MODAL (Matching existing product enquiry form fields) -->
<!-- ========================================================================= -->
<div id="bulkEnquiryModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div id="bulkEnquiryModalCard" class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[92vh] flex flex-col relative transform scale-95 transition-transform duration-200 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="p-5 pb-4 border-b border-slate-100 flex-shrink-0 relative bg-gradient-to-r from-blue-900 to-indigo-900 text-white">
            <button type="button" onclick="closeBulkEnquiryModal()" class="absolute top-4 right-4 p-1.5 text-white/70 hover:text-white rounded-lg bg-white/10 hover:bg-white/20 transition-colors" title="Close">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
            <div class="flex items-center gap-2 mb-1 text-blue-200 font-bold text-xs uppercase tracking-wider">
                <span class="material-symbols-outlined text-[18px]">checklist</span>
                <span>Centralized Sourcing Workflow</span>
            </div>
            <h3 class="font-headline font-bold text-xl sm:text-2xl text-white">Send Bulk Product Enquiry</h3>
            <p class="text-xs text-blue-200 mt-0.5">Submit one comprehensive quotation request covering all your selected equipment directly to verified factories.</p>
        </div>

        <!-- Selected Products Summary Strip -->
        <div class="bg-slate-50 px-5 py-3 border-b border-slate-200/80 flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-2 overflow-x-auto py-1" id="selectedProductsChipsContainer">
                <!-- Dynamically populated with selected products thumbnails -->
            </div>
            <div class="text-xs font-bold text-blue-900 shrink-0">
                <span id="modalSelectedProductsCount">0</span> Products Included
            </div>
        </div>

        <!-- Scrollable Form Body -->
        <form id="bulkEnquiryForm" onsubmit="submitBulkEnquiryForm(event)" class="flex flex-col flex-1 overflow-hidden" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(CSRF::token()) ?>">

            <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-6">
                
                <!-- Section 1: Shared Buyer Information -->
                <div>
                    <h4 class="font-headline font-bold text-sm text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-1.5 pb-2 border-b border-slate-100">
                        <span class="material-symbols-outlined text-blue-900 text-[18px]">person</span>
                        <span>1. Buyer &amp; Contact Details</span>
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Your Full Name *</label>
                            <input type="text" name="buyer_name" id="bulkBuyerName" required value="<?= htmlspecialchars($buyer['name'] ?? '') ?>" placeholder="John Doe" class="w-full bg-slate-50 border border-slate-200 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Business Email *</label>
                            <input type="email" name="buyer_email" id="bulkBuyerEmail" required value="<?= htmlspecialchars($buyer['email'] ?? '') ?>" placeholder="procurement@company.com" class="w-full bg-slate-50 border border-slate-200 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mt-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Phone / WhatsApp</label>
                            <input type="text" name="buyer_phone" id="bulkBuyerPhone" value="<?= htmlspecialchars($buyer['phone'] ?? '') ?>" placeholder="+1 234 567 8900" class="w-full bg-slate-50 border border-slate-200 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Company / Organization</label>
                            <input type="text" name="company_name" id="bulkCompanyName" value="<?= htmlspecialchars($buyer['company_name'] ?? '') ?>" placeholder="Apex Global Sports LLC" class="w-full bg-slate-50 border border-slate-200 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mt-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Destination Country *</label>
                            <select name="country_id" id="bulkCountryId" required class="w-full bg-slate-50 border border-slate-200 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                                <option value="">Select Destination Country *</option>
                                <?php foreach ($allCountries as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= ($buyerCountryId === (int)$c['id'] || (empty($buyerCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                        <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">City / Delivery Location</label>
                            <input type="text" name="delivery_location" id="bulkDeliveryLocation" placeholder="e.g. New York, USA or CIF Mumbai" class="w-full bg-slate-50 border border-slate-200 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Product-Specific Requirements -->
                <div>
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-3">
                        <h4 class="font-headline font-bold text-sm text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-blue-900 text-[18px]">inventory_2</span>
                            <span>2. Product Quantities &amp; Custom Specifications</span>
                        </h4>
                        <span class="text-[11px] text-slate-500">Configure specifications for each selected product</span>
                    </div>

                    <div id="bulkProductsContainer" class="space-y-4">
                        <!-- Dynamically populated per selected product item -->
                    </div>
                </div>

                <!-- Section 3: Shared Tech Pack / Notes (Optional) -->
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80">
                    <h4 class="font-headline font-bold text-xs sm:text-sm text-slate-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-blue-900 text-[18px]">attachment</span>
                        <span>3. Overall Requirements &amp; Master Tech Pack (Optional)</span>
                    </h4>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Overall Message / Timeline Requirements</label>
                            <textarea name="overall_message" id="bulkOverallMessage" rows="2" placeholder="Provide any overall procurement instructions, CIF/FOB delivery terms, or preferred completion timelines..." class="w-full bg-white border border-slate-200 px-3 py-2 text-slate-900 rounded-xl text-xs focus:ring-2 focus:ring-blue-700 outline-none transition-all"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Master Tech Pack / RFQ Specification Document</label>
                            <input type="file" name="shared_attachment" id="bulkSharedAttachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" class="w-full bg-white border border-slate-200 px-3 py-1.5 text-slate-700 rounded-xl text-xs outline-none focus:ring-2 focus:ring-blue-700 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 transition-all">
                            <div class="text-[11px] text-slate-500 mt-1">Accepted: PDF, Word (.doc, .docx), Images (.jpg, .png, .webp) &bull; Max 10MB</div>
                        </div>
                    </div>
                </div>

                <!-- Inline Alert Area -->
                <div id="bulkEnquiryAlert" class="hidden p-3.5 rounded-xl text-xs font-bold"></div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3 shrink-0">
                <button type="button" onclick="closeBulkEnquiryModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitBulkEnquiry" class="px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white text-xs font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    <span>Submit Bulk Enquiry (<span id="submitBtnCount">0</span> Items)</span>
                </button>
            </div>
        </form>

        <!-- Success Confirmation View -->
        <div id="bulkSuccessContainer" class="hidden flex flex-col items-center justify-center p-8 sm:p-12 text-center my-auto">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-4xl">check_circle</span>
            </div>
            <h3 class="font-headline font-extrabold text-2xl text-slate-900 mb-1">Bulk Enquiry Transmitted Successfully!</h3>
            <p class="text-xs sm:text-sm text-slate-600 max-w-md mx-auto mb-4">
                Your centralized sourcing enquiry has been received and registered under Reference:
            </p>
            <div class="inline-block px-4 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 font-mono font-bold text-lg mb-6 tracking-wider" id="successRefNumber">
                BENQ-XXXXX
            </div>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                All selected products have been associated with their respective manufacturers. Our Super Admin team has been notified.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/bulk-enquiries" class="px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all">
                    View My Bulk Enquiries
                </a>
                <button type="button" onclick="window.location.reload()" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors">
                    Back to Sort List
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT CONTROLLERS -->
<!-- ========================================================================= -->
<script>
const csrfToken = '<?= htmlspecialchars(CSRF::token()) ?>';
const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';

// Track selected products in memory
let selectedProductsMap = new Map();

function getSelectedCheckboxes() {
    return Array.from(document.querySelectorAll('.product-select-checkbox:checked'));
}

function onProductSelectChange() {
    selectedProductsMap.clear();
    const checkedBoxes = getSelectedCheckboxes();
    checkedBoxes.forEach(cb => {
        selectedProductsMap.set(parseInt(cb.value), {
            id: parseInt(cb.getAttribute('data-id')),
            name: cb.getAttribute('data-name'),
            supplierId: parseInt(cb.getAttribute('data-supplier-id')),
            supplier: cb.getAttribute('data-supplier'),
            moq: parseInt(cb.getAttribute('data-moq')),
            moqUnit: cb.getAttribute('data-moq-unit'),
            image: cb.getAttribute('data-image'),
            price: cb.getAttribute('data-price')
        });
    });

    updateCounters();
}

function updateCounters() {
    const count = selectedProductsMap.size;
    const totalAvail = parseInt(document.getElementById('availableCounter')?.innerText || '0');

    document.getElementById('selectedCountText').innerText = count;
    document.getElementById('bulkBtnCount').innerText = count;
    document.getElementById('submitBtnCount').innerText = count;
    document.getElementById('removeSelectedCount').innerText = count;

    // Enable / disable bulk button
    const bulkBtn = document.getElementById('btnOpenBulkModal');
    if (bulkBtn) {
        bulkBtn.disabled = (count === 0);
    }

    // Show / hide remove selected button
    const removeBtn = document.getElementById('btnRemoveSelected');
    if (removeBtn) {
        if (count > 0) {
            removeBtn.classList.remove('hidden');
            removeBtn.classList.add('inline-flex');
        } else {
            removeBtn.classList.add('hidden');
            removeBtn.classList.remove('inline-flex');
        }
    }

    // Sync select all checkbox state
    const selectAllCb = document.getElementById('selectAllCheckbox');
    if (selectAllCb && totalAvail > 0) {
        selectAllCb.checked = (count === totalAvail);
        selectAllCb.indeterminate = (count > 0 && count < totalAvail);
    }
}

function toggleSelectAll(masterCb) {
    const isChecked = masterCb.checked;
    const checkboxes = document.querySelectorAll('.product-select-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = isChecked;
    });
    onProductSelectChange();
}

// Remove single product
function removeSingleSortItem(id, name) {
    if (!confirm(`Remove "${name}" from your Sort List?`)) return;
    fetch(`${baseUrl}/buyer/sort-list/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Error removing product.');
        }
    })
    .catch(() => alert('Network error occurred.'));
}

// Remove selected products
function removeSelectedProducts() {
    const ids = Array.from(selectedProductsMap.keys());
    if (ids.length === 0) return;

    if (!confirm(`Remove ${ids.length} selected product(s) from your Sort List?`)) return;

    fetch(`${baseUrl}/api/buyer/sort-list/remove-selected`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ product_ids: ids })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Error removing selected products.');
        }
    })
    .catch(() => alert('Network error occurred.'));
}

// Clear Sort List
function clearSortList() {
    if (!confirm('Are you sure you want to clear your entire Sort List?')) return;

    fetch(`${baseUrl}/api/buyer/sort-list/clear`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Error clearing Sort List.');
        }
    })
    .catch(() => alert('Network error occurred.'));
}

// =========================================================================
// BULK ENQUIRY MODAL LOGIC
// =========================================================================
function openBulkEnquiryModal() {
    if (selectedProductsMap.size === 0) {
        alert('Please select at least one product to submit a bulk enquiry.');
        return;
    }

    const modal = document.getElementById('bulkEnquiryModal');
    const card = document.getElementById('bulkEnquiryModalCard');
    const chipsContainer = document.getElementById('selectedProductsChipsContainer');
    const productsContainer = document.getElementById('bulkProductsContainer');
    const modalCount = document.getElementById('modalSelectedProductsCount');

    modalCount.innerText = selectedProductsMap.size;
    chipsContainer.innerHTML = '';
    productsContainer.innerHTML = '';

    // Render chips and product items
    let idx = 0;
    selectedProductsMap.forEach(prod => {
        idx++;
        // Chip
        const chip = document.createElement('div');
        chip.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-slate-200 text-xs font-bold text-slate-800 shadow-xs shrink-0';
        chip.innerHTML = `
            <img src="${prod.image}" class="w-4 h-4 rounded-full object-cover">
            <span class="truncate max-w-[140px]">${escapeHtml(prod.name)}</span>
        `;
        chipsContainer.appendChild(chip);

        // Product Form Row Card
        const itemCard = document.createElement('div');
        itemCard.className = 'p-4 rounded-xl bg-slate-50 border border-slate-200/90 space-y-3';
        itemCard.innerHTML = `
            <div class="flex items-center justify-between gap-3 pb-2.5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <img src="${prod.image}" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                    <div>
                        <h5 class="font-headline font-bold text-xs sm:text-sm text-slate-900 line-clamp-1">${escapeHtml(prod.name)}</h5>
                        <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                            <span class="material-symbols-outlined text-[13px] text-teal-600">payments</span>
                            <span>Est. Price: $${prod.price}</span>
                        </div>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-900 font-bold text-[10px]">
                        MOQ: ${prod.moq} ${escapeHtml(prod.moqUnit)}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">
                        Target Order Qty * <span class="text-slate-400 font-normal">(Min: ${prod.moq} ${escapeHtml(prod.moqUnit)})</span>
                    </label>
                    <input type="number" 
                           name="items[${prod.id}][target_quantity]" 
                           required 
                           min="${prod.moq}" 
                           value="${prod.moq}" 
                           class="w-full bg-white border border-slate-300 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                    <input type="hidden" name="items[${prod.id}][product_id]" value="${prod.id}">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">
                        Target Unit Price (Optional, USD)
                    </label>
                    <input type="number" 
                           step="0.01" 
                           name="items[${prod.id}][target_price]" 
                           placeholder="e.g. ${prod.price}" 
                           class="w-full bg-white border border-slate-300 px-3 py-2 text-slate-900 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-blue-700 outline-none transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">
                    Custom Specifications &amp; Packaging Requirements *
                </label>
                <textarea name="items[${prod.id}][specifications]" 
                          required 
                          rows="2" 
                          placeholder="Specify custom logo embossing, colors, materials, packaging boxes, or testing standards..." 
                          class="w-full bg-white border border-slate-300 px-3 py-2 text-slate-900 rounded-xl text-xs focus:ring-2 focus:ring-blue-700 outline-none transition-all"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">
                    Tech Pack / Specs File for this Product (Optional)
                </label>
                <input type="file" 
                       name="attachment_product_${prod.id}" 
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" 
                       class="w-full bg-white border border-slate-300 px-3 py-1.5 text-slate-700 rounded-xl text-xs outline-none focus:ring-2 focus:ring-blue-700 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 transition-all">
            </div>
        `;
        productsContainer.appendChild(itemCard);
    });

    // Reset views
    document.getElementById('bulkEnquiryForm').classList.remove('hidden');
    document.getElementById('bulkSuccessContainer').classList.add('hidden');
    document.getElementById('bulkEnquiryAlert').classList.add('hidden');

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }, 10);
}

function closeBulkEnquiryModal() {
    const modal = document.getElementById('bulkEnquiryModal');
    const card = document.getElementById('bulkEnquiryModalCard');
    modal.classList.add('opacity-0');
    card.classList.add('scale-95');
    card.classList.remove('scale-100');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function submitBulkEnquiryForm(e) {
    e.preventDefault();
    const form = document.getElementById('bulkEnquiryForm');
    const btn = document.getElementById('btnSubmitBulkEnquiry');
    const alertBox = document.getElementById('bulkEnquiryAlert');

    alertBox.classList.add('hidden');
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = `<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span><span>Submitting Enquiry...</span>`;

    const formData = new FormData(form);

    fetch(`${baseUrl}/api/buyer/bulk-enquiry/submit`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('bulkEnquiryForm').classList.add('hidden');
            document.getElementById('successRefNumber').innerText = res.reference_no;
            document.getElementById('bulkSuccessContainer').classList.remove('hidden');
        } else {
            alertBox.className = 'p-3.5 rounded-xl text-xs font-bold bg-rose-50 border border-rose-200 text-rose-800';
            alertBox.innerText = res.message || 'Failed to submit bulk enquiry. Please review required fields.';
            alertBox.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    })
    .catch(() => {
        alertBox.className = 'p-3.5 rounded-xl text-xs font-bold bg-rose-50 border border-rose-200 text-rose-800';
        alertBox.innerText = 'Network error during submission. Please try again.';
        alertBox.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = origHtml;
    });
}

function escapeHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.innerText = str;
    return d.innerHTML;
}
</script>
