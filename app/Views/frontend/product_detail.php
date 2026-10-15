<?php
use App\Core\Response;
$baseUrl = Response::baseUrl();

$primaryImg = \App\Helpers\Uploader::imageUrl($product['primary_image'] ?? null);

$galleryImages = $product['images'] ?? [];
if (empty($galleryImages) && !empty($primaryImg)) {
    $galleryImages = [['image_path' => $primaryImg]];
}

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
?>

<!-- Schema.org JSON-LD Structured Data for Technical SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": <?= json_encode($product['name'] ?? $product['title']) ?>,
  "image": <?= json_encode($primaryImg) ?>,
  "description": <?= json_encode($product['short_description'] ?? $product['description'] ?? '') ?>,
  "sku": <?= json_encode($product['sku'] ?? 'SJ-' . $product['id']) ?>,
  "brand": {
    "@type": "Brand",
    "name": <?= json_encode($product['brand_name'] ?? 'Sports Junction') ?>
  },
  "offers": {
    "@type": "AggregateOffer",
    "url": <?= json_encode($currentUrl) ?>,
    "priceCurrency": "INR",
    "lowPrice": <?= (float)$product['min_price'] ?>,
    "highPrice": <?= (float)$product['max_price'] ?>,
    "offerCount": "1",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition"
  }
}
</script>

<!-- Breadcrumb Navigation -->
<div class="bg-surface-container-low/70 border-b border-surface-container py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-xs text-outline font-medium flex-wrap">
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="hover:text-primary transition-colors">Home</a>
            <span>/</span>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="hover:text-primary transition-colors">Products</a>
            <?php if (!empty($product['category_slug'])): ?>
                <span>/</span>
                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($product['category_slug']) ?>" class="hover:text-primary transition-colors">
                    <?= htmlspecialchars($product['category_name']) ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($product['subcategory_slug'])): ?>
                <span>/</span>
                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($product['category_slug']) ?>/<?= htmlspecialchars($product['subcategory_slug']) ?>" class="hover:text-primary transition-colors">
                    <?= htmlspecialchars($product['subcategory_name']) ?>
                </a>
            <?php endif; ?>
            <span>/</span>
            <span class="text-primary font-bold truncate max-w-xs"><?= htmlspecialchars($product['name']) ?></span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <!-- Top Product Showcase Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">
        <!-- 1. LEFT GALLERY: 5 Cols -->
        <div class="lg:col-span-5 space-y-4">
            <!-- Main Hero Image Frame -->
            <div class="relative w-full aspect-square bg-white rounded-2xl border border-surface-container shadow-sm overflow-hidden flex items-center justify-center p-4">
                <img id="mainProductDisplay" src="<?= htmlspecialchars($primaryImg) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="max-h-full max-w-full object-contain transition-all duration-300">
                
                <div class="absolute top-4 left-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-600 text-white text-[11px] font-bold shadow-md">
                        <span class="material-symbols-outlined text-[14px]">verified</span>
                        <span>Audited Factory Plant</span>
                    </span>
                </div>
            </div>

            <!-- Thumbnail Carousel / Selector -->
            <?php if (!empty($galleryImages)): ?>
                <div class="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-none">
                    <?php foreach ($galleryImages as $idx => $gImg): ?>
                        <?php 
                            $thumbSrc = \App\Helpers\Uploader::imageUrl($gImg['image_path'] ?? null);
                        ?>
                        <button type="button" onclick="changeMainImage('<?= htmlspecialchars($thumbSrc) ?>', this)" class="w-16 h-16 rounded-xl border-2 <?= $idx === 0 ? 'border-primary' : 'border-surface-container' ?> bg-white p-1 overflow-hidden shrink-0 hover:border-primary transition-all focus:outline-none thumb-btn">
                            <img src="<?= htmlspecialchars($thumbSrc) ?>" alt="Thumb" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-full h-full object-cover rounded-lg">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Demonstration Video Callout if Available -->
            <?php if (!empty($product['video_url'])): ?>
                <a href="<?= htmlspecialchars($product['video_url']) ?>" target="_blank" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold border border-red-200 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">smart_display</span>
                    <span>Watch Factory Production Video</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- 2. CENTER SPECS & PRICING: 4 Cols -->
        <div class="lg:col-span-4 space-y-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-outline font-semibold mb-1">
                    <span>SKU: <strong class="text-on-surface"><?= htmlspecialchars($product['sku'] ?? 'SJ-PROD-' . $product['id']) ?></strong></span>
                    <span>•</span>
                    <span>Brand: <strong class="text-on-surface"><?= htmlspecialchars($product['brand_name'] ?? 'Sports Junction Partner') ?></strong></span>
                </div>
                <h1 class="font-headline font-extrabold text-xl sm:text-2xl text-on-surface leading-snug">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>
            </div>

            <!-- Wholesale Price Tier Card -->
            <div class="p-4 rounded-2xl bg-surface-container-low border border-surface-container space-y-2">
                <div class="text-xs font-semibold text-outline uppercase tracking-wider">Wholesale Ex-Factory Tier</div>
                <div class="font-headline font-extrabold text-2xl sm:text-3xl text-blue-900 flex items-baseline gap-1">
                    <?= htmlspecialchars($product['price_unit_symbol'] ?? '₹') ?><?= number_format((float)$product['min_price'], 2) ?>
                    <?php if ((float)$product['max_price'] > (float)$product['min_price']): ?>
                        - <?= htmlspecialchars($product['price_unit_symbol'] ?? '₹') ?><?= number_format((float)$product['max_price'], 2) ?>
                    <?php endif; ?>
                    <span class="text-xs font-normal text-outline">/ <?= htmlspecialchars($product['price_unit_name'] ?? 'Piece') ?></span>
                </div>
                <div class="flex items-center gap-4 text-xs font-medium text-on-surface pt-1">
                    <span>MOQ: <strong class="text-blue-900"><?= (int)$product['moq_quantity'] ?> <?= htmlspecialchars($product['moq_unit_name'] ?? 'pieces') ?></strong></span>
                    <span>•</span>
                    <span>Lead Time: <strong><?= htmlspecialchars($product['lead_time'] ?? '10 - 20 Days') ?></strong></span>
                </div>
            </div>

            <!-- Ready Stock & Sample Badge -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <?php if ((int)$product['sample_available'] === 1): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 font-bold border border-emerald-200">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        <span>Sample Available (<?= htmlspecialchars($product['price_unit_symbol'] ?? '₹') ?><?= number_format((float)($product['sample_price'] ?? $product['min_price']), 2) ?>)</span>
                    </span>
                <?php endif; ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-50 text-blue-800 font-bold border border-blue-200">
                    <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                    <span><?= number_format((int)$product['available_quantity']) ?> units available</span>
                </span>
            </div>

            <!-- Customization Capabilities Check -->
            <div class="space-y-2 pt-2 border-t border-surface-container">
                <span class="text-xs font-bold uppercase tracking-wider text-outline block">Customization Capabilities</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="flex items-center gap-1.5 <?= (int)$product['custom_logo'] ? 'text-teal-700 font-semibold' : 'text-gray-400' ?>">
                        <span class="material-symbols-outlined text-[16px]"><?= (int)$product['custom_logo'] ? 'check_circle' : 'cancel' ?></span>
                        <span>Custom Logo Print</span>
                    </div>
                    <div class="flex items-center gap-1.5 <?= (int)$product['custom_color'] ? 'text-teal-700 font-semibold' : 'text-gray-400' ?>">
                        <span class="material-symbols-outlined text-[16px]"><?= (int)$product['custom_color'] ? 'check_circle' : 'cancel' ?></span>
                        <span>Pantone Colors</span>
                    </div>
                    <div class="flex items-center gap-1.5 <?= (int)$product['custom_packaging'] ? 'text-teal-700 font-semibold' : 'text-gray-400' ?>">
                        <span class="material-symbols-outlined text-[16px]"><?= (int)$product['custom_packaging'] ? 'check_circle' : 'cancel' ?></span>
                        <span>Custom Packaging</span>
                    </div>
                    <div class="flex items-center gap-1.5 <?= (int)$product['oem_available'] ? 'text-teal-700 font-semibold' : 'text-gray-400' ?>">
                        <span class="material-symbols-outlined text-[16px]"><?= (int)$product['oem_available'] ? 'check_circle' : 'cancel' ?></span>
                        <span>OEM / ODM Service</span>
                    </div>
                </div>
            </div>

            <!-- Primary CTAs -->
            <div class="pt-4 space-y-2">
                <div class="flex gap-2">
                    <button type="button" onclick="openInquiryModal('<?= htmlspecialchars(addslashes($product['name'])) ?>', '<?= htmlspecialchars(addslashes($product['supplier_name'] ?? 'Verified Indian Factory')) ?>', <?= (int)$product['moq_quantity'] ?>, <?= (int)$product['id'] ?>, <?= (int)($product['supplier_id'] ?? 1) ?>)" class="flex-1 py-3.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        <span>Send Direct Factory Inquiry</span>
                    </button>
                    <button type="button" id="btnDetailFavourite" onclick="toggleFavouriteProduct(<?= (int)$product['id'] ?>, this)" class="px-4 py-3.5 rounded-xl border border-surface-container bg-white hover:bg-rose-50 transition-all flex items-center justify-center <?= !empty($isFavourite) ? 'text-rose-600 border-rose-200 bg-rose-50' : 'text-slate-600' ?>" title="Save to Favourites">
                        <span class="material-symbols-outlined text-[20px] <?= !empty($isFavourite) ? 'fill-1' : '' ?>">favorite</span>
                    </button>
                </div>
                <!-- Chat & Negotiate Button -->
                <button type="button" onclick="launchProductChat()" class="w-full py-3 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-headline font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">chat</span>
                    <span>Chat & Negotiate with Factory</span>
                </button>
                <?php if ((int)$product['sample_available'] === 1): ?>
                    <button type="button" onclick="openInquiryModal('Sample Request: <?= htmlspecialchars(addslashes($product['name'])) ?>', '<?= htmlspecialchars(addslashes($product['supplier_name'] ?? 'Verified Indian Factory')) ?>', 1, <?= (int)$product['id'] ?>, <?= (int)($product['supplier_id'] ?? 1) ?>)" class="w-full py-2.5 rounded-xl bg-white hover:bg-gray-50 text-blue-900 border border-surface-container font-headline font-bold text-xs transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-teal-600">science</span>
                        <span>Order Prototype Sample (5-7 Days Dispatch)</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. RIGHT VERIFIED SUPPLIER CARD: 3 Cols -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Supplier Card -->
            <div class="bg-white rounded-2xl border border-surface-container p-5 shadow-sm space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700 block">Verified Manufacturer</span>
                        <h4 class="font-headline font-bold text-sm text-on-surface mt-0.5">
                            <?php if (!empty($product['supplier_id'])): ?>
                                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/<?= (int)$product['supplier_id'] ?>" class="hover:text-teal-700 hover:underline transition-colors inline-flex items-center gap-1 group" title="View Factory Profile & Full Catalog">
                                    <span><?= htmlspecialchars($product['supplier_name'] ?? 'Verified Manufacturer') ?></span>
                                    <span class="material-symbols-outlined text-[14px] text-slate-400 group-hover:text-teal-700">open_in_new</span>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($product['supplier_name'] ?? 'Meerut Sports Gear Ltd') ?>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <span class="material-symbols-outlined text-[24px] text-teal-600">verified</span>
                </div>

                <div class="space-y-2 text-xs text-outline pt-2 border-t border-surface-container">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-blue-700">location_on</span>
                        <span><?= htmlspecialchars($product['city_name'] ?? 'Meerut') ?>, <?= htmlspecialchars($product['country_name'] ?? 'India') ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-teal-600">bolt</span>
                        <span>Response SLA: <strong>&lt; 2.4 Hours</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-blue-700">security</span>
                        <span>Direct Factory (Zero Commission)</span>
                    </div>
                </div>

                <div class="p-3 bg-surface-container-low rounded-xl text-xs space-y-1">
                    <div class="font-bold text-on-surface flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-teal-600">verified_user</span>
                        <span>Sourcing Protection</span>
                    </div>
                    <p class="text-[11px] text-outline leading-tight">
                        Payments held in secure escrow until physical goods pass pre-shipment quality inspection.
                    </p>
                </div>

                <button type="button" onclick="openInquiryModal('Contact Supplier: <?= htmlspecialchars(addslashes($product['name'])) ?>', '<?= htmlspecialchars(addslashes($product['supplier_name'] ?? 'Verified Factory')) ?>', <?= (int)$product['moq_quantity'] ?>, <?= (int)$product['id'] ?>, <?= (int)($product['supplier_id'] ?? 1) ?>)" class="w-full py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-blue-900 text-xs font-bold transition-colors">
                    Contact Factory Rep
                </button>
            </div>
        </div>
    </div>

    <!-- TABS SECTION: SPECIFICATIONS & LOGISTICS -->
    <div class="bg-white rounded-2xl border border-surface-container shadow-sm p-6 mb-12">
        <div class="border-b border-surface-container mb-6">
            <nav class="flex items-center gap-8 text-sm font-headline font-bold">
                <button type="button" onclick="switchTab('specs', this)" class="pb-3 border-b-2 border-primary text-primary tab-nav-btn focus:outline-none">
                    Technical Specifications
                </button>
                <button type="button" onclick="switchTab('logistics', this)" class="pb-3 border-b-2 border-transparent text-outline hover:text-on-surface tab-nav-btn focus:outline-none">
                    Packaging & Shipping
                </button>
                <button type="button" onclick="switchTab('factory', this)" class="pb-3 border-b-2 border-transparent text-outline hover:text-on-surface tab-nav-btn focus:outline-none">
                    Factory Audit & Quality Control
                </button>
            </nav>
        </div>

        <!-- TAB 1: TECHNICAL SPECS -->
        <div id="tab-content-specs" class="tab-pane-content space-y-4">
            <h3 class="font-headline font-bold text-base text-on-surface mb-3">Specification Matrix</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border border-surface-container rounded-xl overflow-hidden">
                    <tbody class="divide-y divide-surface-container">
                        <tr class="bg-surface-container-low/50">
                            <td class="py-3 px-4 font-semibold text-outline w-1/3">Material Composition</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= htmlspecialchars($product['material'] ?? 'Commercial Grade Athletic Composite') ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-outline">Available Colors</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= htmlspecialchars($product['colors'] ?? 'Standard Competition / Custom Pantone on order') ?></td>
                        </tr>
                        <tr class="bg-surface-container-low/50">
                            <td class="py-3 px-4 font-semibold text-outline">Dimensions / Size</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= htmlspecialchars($product['size'] ?? 'Standard Competition Model') ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-outline">Unit Weight</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= htmlspecialchars($product['weight'] ?? 'Standard Factory Specification') ?></td>
                        </tr>
                        <tr class="bg-surface-container-low/50">
                            <td class="py-3 px-4 font-semibold text-outline">Custom Branding Support</td>
                            <td class="py-3 px-4 text-on-surface font-medium">Silk screen logo, laser engraving, bespoke packaging boxes, barcode sticker integration</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-outline">Customization Guidance</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= nl2br(htmlspecialchars($product['customization_details'] ?? 'Custom branding MOQ starting at standard order minimum. Vector AI/PDF logo files required.')) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Full Description Prose -->
            <?php if (!empty($product['description'])): ?>
                <div class="pt-6">
                    <h4 class="font-headline font-bold text-sm text-on-surface mb-2">Manufacturing Overview</h4>
                    <div class="text-xs text-on-surface-variant leading-relaxed space-y-2">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: PACKAGING & LOGISTICS -->
        <div id="tab-content-logistics" class="tab-pane-content hidden space-y-4">
            <h3 class="font-headline font-bold text-base text-on-surface mb-3">Freight & Shipping Terms</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border border-surface-container rounded-xl overflow-hidden">
                    <tbody class="divide-y divide-surface-container">
                        <tr class="bg-surface-container-low/50">
                            <td class="py-3 px-4 font-semibold text-outline w-1/3">Ships From</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= htmlspecialchars($product['city_name'] ?? 'Meerut') ?>, <?= htmlspecialchars($product['country_name'] ?? 'India') ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-outline">Estimated Production SLA</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= htmlspecialchars($product['lead_time'] ?? '10 - 20 Business Days') ?></td>
                        </tr>
                        <tr class="bg-surface-container-low/50">
                            <td class="py-3 px-4 font-semibold text-outline">Logistics & Ports</td>
                            <td class="py-3 px-4 text-on-surface font-medium"><?= htmlspecialchars($product['shipping_info'] ?? 'FOB Nhava Sheva (Mumbai) / Mundra Port or Express Air Courier') ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-outline">Export Packaging</td>
                            <td class="py-3 px-4 text-on-surface font-medium">Heavy-duty export corrugated master cartons with plastic corner protectors and moisture desiccant bags</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: FACTORY AUDIT -->
        <div id="tab-content-factory" class="tab-pane-content hidden space-y-4">
            <h3 class="font-headline font-bold text-base text-on-surface mb-3">Audited Manufacturing Standards</h3>
            <p class="text-xs text-outline leading-relaxed">
                This factory line has been verified under the Sports Junction Tier-1 supplier certification program. All production lines are subjected to mandatory random batch testing before container loading.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div class="p-4 rounded-xl bg-surface-container-low border border-surface-container">
                    <span class="material-symbols-outlined text-[24px] text-teal-600 mb-2">verified</span>
                    <h4 class="font-bold text-xs text-on-surface">ISO 9001:2015 Certified</h4>
                    <p class="text-[11px] text-outline mt-1">Standardized quality management systems across production lines.</p>
                </div>
                <div class="p-4 rounded-xl bg-surface-container-low border border-surface-container">
                    <span class="material-symbols-outlined text-[24px] text-blue-700 mb-2">groups</span>
                    <h4 class="font-bold text-xs text-on-surface">BSCI Social Audit Compliant</h4>
                    <p class="text-[11px] text-outline mt-1">Fair labor standards and ethical manufacturing practices guaranteed.</p>
                </div>
                <div class="p-4 rounded-xl bg-surface-container-low border border-surface-container">
                    <span class="material-symbols-outlined text-[24px] text-teal-600 mb-2">fact_check</span>
                    <h4 class="font-bold text-xs text-on-surface">Pre-Shipment Inspection</h4>
                    <p class="text-[11px] text-outline mt-1">Full dimensional, packaging, and cosmetic quality check reports included.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- RELATED PRODUCTS SECTION -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-800">Related Catalog</span>
                    <h3 class="font-headline font-bold text-xl text-on-surface mt-0.5">Recommended Sourcing Alternates</h3>
                </div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($product['category_slug'] ?? '') ?>" class="text-xs font-bold text-blue-700 hover:underline">
                    View More in <?= htmlspecialchars($product['category_name'] ?? 'Category') ?> &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($relatedProducts as $rel): ?>
                    <?php 
                        $relUrl = htmlspecialchars($baseUrl) . '/product/' . htmlspecialchars($rel['slug'] ?? (string)$rel['id']);
                        $relImg = !empty($rel['primary_image']) ? $rel['primary_image'] : 'https://placehold.co/400x400?text=Sports+Junction';
                        if (!str_starts_with($relImg, 'http') && !str_starts_with($relImg, '/')) {
                            $relImg = $baseUrl . $relImg;
                        }
                    ?>
                    <div class="bg-white rounded-2xl border border-surface-container shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                        <div class="w-full h-44 bg-gray-100 overflow-hidden">
                            <a href="<?= $relUrl ?>">
                                <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </a>
                        </div>
                        <div class="p-4 flex flex-col flex-1 justify-between">
                            <div>
                                <h4 class="font-headline font-bold text-xs text-on-surface line-clamp-2 mb-1 group-hover:text-blue-700 transition-colors">
                                    <a href="<?= $relUrl ?>"><?= htmlspecialchars($rel['name']) ?></a>
                                </h4>
                                <div class="font-headline font-bold text-sm text-blue-900 mt-1">
                                    <?= htmlspecialchars($rel['price_unit_symbol'] ?? '₹') ?><?= number_format((float)$rel['min_price'], 2) ?>
                                    <span class="text-[10px] font-normal text-outline">/ unit</span>
                                </div>
                                <div class="text-[11px] text-outline mt-0.5">
                                    MOQ: <?= (int)$rel['moq_quantity'] ?> <?= htmlspecialchars($rel['moq_unit_name'] ?? 'units') ?>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-surface-container mt-3">
                                <a href="<?= $relUrl ?>" class="block w-full py-1.5 text-center rounded-lg bg-surface-container-low hover:bg-surface-container-high text-blue-900 text-xs font-bold transition-colors">
                                    View Specs
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function changeMainImage(src, btn) {
        document.getElementById('mainProductDisplay').src = src;
        document.querySelectorAll('.thumb-btn').forEach(b => {
            b.classList.remove('border-primary');
            b.classList.add('border-surface-container');
        });
        btn.classList.remove('border-surface-container');
        btn.classList.add('border-primary');
    }

    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-pane-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(`tab-content-${tabId}`)?.classList.remove('hidden');

        document.querySelectorAll('.tab-nav-btn').forEach(b => {
            b.classList.remove('border-primary', 'text-primary');
            b.classList.add('border-transparent', 'text-outline');
        });
        btn.classList.remove('border-transparent', 'text-outline');
        btn.classList.add('border-primary', 'text-primary');
    }

    function launchProductChat() {
        if (typeof openProductChat === 'function') {
            openProductChat({
                id: <?= (int)$product['id'] ?>,
                title: <?= json_encode($product['name'] ?? $product['title']) ?>,
                price: <?= (float)$product['min_price'] ?>,
                moq: <?= (int)$product['moq_quantity'] ?>,
                supplier_id: <?= (int)($product['supplier_id'] ?? 1) ?>,
                supplier_name: <?= json_encode($product['supplier_name'] ?? 'Verified Indian Factory') ?>,
                image: <?= json_encode($primaryImg) ?>
            });
        }
    }
</script>
