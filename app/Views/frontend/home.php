<?php
use App\Core\Response;
$baseUrl = Response::baseUrl();
?>
<!-- 1. HERO / SEARCH SECTION -->
<section class="relative bg-surface py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- High-Conversion B2B Hero Banner (Full Width) -->
        <div class="w-full relative rounded-2xl overflow-hidden bg-slate-900 min-h-[380px] sm:min-h-[420px] lg:min-h-[450px] flex items-center shadow-lg">
            <img alt="Sports manufacturing warehouse" class="absolute inset-0 w-full h-full object-cover opacity-35" src="https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=1600&q=80">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-950/95 via-blue-950/70 to-transparent"></div>

            <div class="relative z-10 max-w-2xl p-6 sm:p-10 lg:p-12 text-white flex flex-col justify-between h-full">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 backdrop-blur-md text-teal-300 text-xs font-bold uppercase tracking-wider border border-teal-500/30">
                        <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                        <span>Factory Direct Sourcing Hub</span>
                    </div>

                    <h1 class="font-headline text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight tracking-tight text-white">
                        Direct From Verified Indian Sports & Fitness Manufacturers
                    </h1>

                    <p class="text-sm sm:text-base text-gray-200 leading-relaxed max-w-xl">
                        Factory-Direct Wholesale Pricing • Guaranteed MOQs • ISO 9001 & BSCI Audited Production Facilities in Meerut & Jalandhar with Fast Sample Turnaround.
                    </p>

                    <div class="flex flex-wrap items-center gap-3 pt-3">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-headline font-bold text-sm shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
                            <span>Source Catalog</span>
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </a>
                        <button type="button" onclick="openRfqModal()" class="px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 backdrop-blur-md font-headline font-bold text-sm transition-all flex items-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">description</span>
                            <span>Request Quotation</span>
                        </button>
                    </div>
                </div>

                <!-- Bottom Trust Indicators -->
                <div class="mt-8 pt-4 border-t border-white/10 flex items-center justify-between text-xs text-gray-300">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-1.5 rounded-full bg-blue-500"></span>
                        <span class="w-3 h-1.5 rounded-full bg-white/30"></span>
                        <span class="w-3 h-1.5 rounded-full bg-white/30"></span>
                        <span class="ml-2 font-mono font-semibold text-white">01 / 03</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-teal-400">local_shipping</span>
                        <span>Global Port Dispatch: Nhava Sheva & Mundra</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sourcing Highlights Strip -->
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="flex items-center gap-3 p-3.5 rounded-xl bg-white border border-surface-container shadow-sm">
                <span class="material-symbols-outlined text-[24px] text-blue-700">verified</span>
                <div>
                    <div class="text-xs font-bold text-on-surface">15,000+ Verified Plants</div>
                    <div class="text-[11px] text-outline">Direct factory agreements</div>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3.5 rounded-xl bg-white border border-surface-container shadow-sm">
                <span class="material-symbols-outlined text-[24px] text-teal-600">bolt</span>
                <div>
                    <div class="text-xs font-bold text-on-surface">&lt; 2.4hr Inquiry SLA</div>
                    <div class="text-[11px] text-outline">Dedicated lead engineers</div>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3.5 rounded-xl bg-white border border-surface-container shadow-sm">
                <span class="material-symbols-outlined text-[24px] text-blue-700">fact_check</span>
                <div>
                    <div class="text-xs font-bold text-on-surface">ISO &amp; BSCI Audited</div>
                    <div class="text-[11px] text-outline">On-site inspection reports</div>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3.5 rounded-xl bg-white border border-surface-container shadow-sm">
                <span class="material-symbols-outlined text-[24px] text-teal-600">payments</span>
                <div>
                    <div class="text-xs font-bold text-on-surface">Zero Commission</div>
                    <div class="text-[11px] text-outline">Authentic ex-factory pricing</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. POPULAR B2B SOURCING CATEGORIES -->
<?php if (!empty($homepageCategories)): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 w-full">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-blue-800 mb-1">Global Industrial Sectors</div>
            <h2 class="font-headline text-2xl sm:text-3xl text-on-surface font-extrabold">Popular Sports Sourcing Categories</h2>
        </div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 hover:text-blue-900 transition-colors">
            <span>View All Categories</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </div>

    <!-- Category Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($homepageCategories as $cat): ?>
            <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>" class="group p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-blue-300 transition-all flex items-center gap-3.5 relative overflow-hidden">
                <div class="w-12 h-12 rounded-xl bg-blue-50/80 group-hover:bg-blue-600 flex items-center justify-center text-blue-900 group-hover:text-white transition-colors shrink-0 p-2.5">
                    <?php 
                    $catIcon = trim($cat['icon'] ?? 'sports_tennis');
                    $isImg = (str_starts_with($catIcon, '/') || str_starts_with($catIcon, 'http') || str_contains($catIcon, '.'));
                    if ($isImg): ?>
                        <img src="<?= htmlspecialchars(\App\Core\Response::formatImageUrl($catIcon)) ?>" class="w-full h-full object-contain" alt="<?= htmlspecialchars($cat['name']) ?>" onerror="this.onerror=null;this.parentElement.innerHTML='<span class=\'material-symbols-outlined text-[24px]\'>sports_tennis</span>';">
                    <?php else: ?>
                        <span class="material-symbols-outlined text-[24px]"><?= htmlspecialchars($catIcon) ?></span>
                    <?php endif; ?>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-headline font-bold text-sm text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                        <?= htmlspecialchars($cat['name']) ?>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5 font-normal">
                        <?= (int) ($cat['product_count'] ?? 0) ?> active products
                    </p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- 3. FEATURED PRODUCTS HIGHLIGHTS -->
<section class="bg-surface-container-low/60 py-14 border-y border-surface-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-teal-700 mb-1">Direct Factory Line</div>
                <h2 class="font-headline text-2xl sm:text-3xl text-on-surface font-extrabold">Verified Manufacturer Highlights</h2>
            </div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 hover:text-blue-900 transition-colors">
                <span>Explore Full Catalog (<?= count($featuredProducts) ?>+ items)</span>
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>

        <!-- Featured Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $prod): ?>
                    <?php 
                        $prodUrl = htmlspecialchars($baseUrl) . '/product/' . htmlspecialchars($prod['slug'] ?? (string)$prod['id']);
                        $img = \App\Helpers\Uploader::imageUrl($prod['primary_image'] ?? null);
                    ?>
                    <div class="bg-white rounded-2xl border border-surface-container shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                        <!-- Image Container -->
                        <div class="relative w-full h-52 bg-gray-100 overflow-hidden">
                            <a href="<?= $prodUrl ?>">
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </a>
                            
                            <?php if (!empty($prod['certificates'])): ?>
                                <div class="absolute top-3 left-3 flex flex-col gap-1 items-start max-w-[80%] pointer-events-none">
                                    <?php foreach ($prod['certificates'] as $c): ?>
                                        <?php 
                                            $cIcon = trim($c['icon'] ?? 'verified');
                                            $isImg = (str_starts_with($cIcon, '/') || str_starts_with($cIcon, 'http') || str_contains($cIcon, '.'));
                                            $iconUrl = $isImg ? \App\Core\Response::formatImageUrl($cIcon) : '';
                                        ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white/95 backdrop-blur-md text-[10px] font-bold text-slate-800 shadow-sm border border-slate-200/80 max-w-full">
                                            <?php if ($isImg): ?>
                                                <img src="<?= htmlspecialchars($iconUrl) ?>" alt="" class="w-3.5 h-3.5 object-contain rounded-xs shrink-0" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-[13px] text-amber-500 shrink-0"><?= htmlspecialchars($cIcon ?: 'verified') ?></span>
                                            <?php endif; ?>
                                            <span class="truncate"><?= htmlspecialchars($c['name']) ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="absolute top-3 right-3">
                                <span class="px-2 py-0.5 rounded-md bg-blue-700 text-white font-mono text-[10px] font-bold shadow-sm">
                                    OEM / ODM
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 flex flex-col flex-1 justify-between">
                            <div>
                                <div class="text-[11px] text-outline font-semibold uppercase tracking-wider mb-1">
                                    <?= htmlspecialchars($prod['all_category_names'] ?? $prod['category_name'] ?? 'Sports Gear') ?>
                                </div>
                                <h3 class="font-headline font-bold text-sm text-on-surface line-clamp-2 mb-2 group-hover:text-blue-700 transition-colors">
                                    <a href="<?= $prodUrl ?>"><?= htmlspecialchars($prod['name']) ?></a>
                                </h3>

                                <!-- Price Matrix -->
                                <div class="mt-2 mb-3">
                                    <div class="font-headline font-extrabold text-base text-blue-900">
                                        $<?= number_format((float)$prod['min_price'], 2) ?>
                                        <?php if ((float)$prod['max_price'] > (float)$prod['min_price']): ?>
                                            - $<?= number_format((float)$prod['max_price'], 2) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-outline mt-1 font-medium">
                                        <span>MOQ: <strong class="text-on-surface"><?= (int)$prod['moq_quantity'] ?> <?= htmlspecialchars($prod['moq_unit_name'] ?? 'units') ?></strong></span>
                                        <span>•</span>
                                        <span><?= htmlspecialchars($prod['country_name'] ?? 'India') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer CTAs -->
                            <div class="pt-3 border-t border-surface-container flex items-center gap-2">
                                <?php 
                                $isOwnProd = \App\Models\Supplier::isOwnedByCurrentSupplier($prod);
                                ?>
                                <?php if ($isOwnProd): ?>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/edit/<?= (int)$prod['id'] ?>" class="flex-1 py-2 text-center rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-900 text-xs font-bold transition-colors inline-flex items-center justify-center gap-1.5" title="Manage / Edit Product in Supplier Portal">
                                        <span class="material-symbols-outlined text-[15px]">edit</span>
                                        <span>Manage / Edit</span>
                                    </a>
                                    <a href="<?= $prodUrl ?>" class="p-2 rounded-xl border border-surface-container hover:bg-slate-50 text-slate-600 transition-colors shrink-0 flex items-center justify-center" title="View Public Page" aria-label="View Public Page">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= $prodUrl ?>" class="flex-1 py-2 text-center rounded-xl bg-surface-container-low hover:bg-surface-container-high text-blue-900 text-xs font-bold transition-colors">
                                        Specs & Samples
                                    </a>
                                    <button type="button" onclick="toggleFavouriteProduct(<?= (int)$prod['id'] ?>, this)" class="p-2 rounded-xl border border-surface-container hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition-colors shrink-0" title="Save to Favourites" aria-label="Save to Favourites">
                                        <span class="material-symbols-outlined text-[18px]">favorite</span>
                                    </button>
                                    <button type="button" onclick="openProductChat(<?= htmlspecialchars(json_encode([
                                        'id' => (int)$prod['id'],
                                        'product_id' => (int)$prod['id'],
                                        'supplier_id' => (int)($prod['supplier_id'] ?? 1),
                                        'title' => $prod['name'] ?? $prod['title'] ?? '',
                                        'name' => $prod['name'] ?? $prod['title'] ?? '',
                                        'primary_image' => $prod['primary_image'] ?? '',
                                        'min_price' => (float)($prod['min_price'] ?? 0),
                                        'max_price' => (float)($prod['max_price'] ?? 0),
                                        'moq_quantity' => (int)($prod['moq_quantity'] ?? 1),
                                        'supplier_name' => $prod['supplier_name'] ?? 'Verified Factory'
                                    ])) ?>)" class="p-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white transition-colors shrink-0 flex items-center justify-center cursor-pointer" title="Chat with Factory about this Product" aria-label="Chat with Factory">
                                        <span class="material-symbols-outlined text-[18px]">send</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 4. THREE-STEP B2B SOURCING WORKFLOW -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-blue-800">End-to-End Compliance</span>
        <h2 class="font-headline text-2xl sm:text-3xl font-extrabold text-on-surface mt-1">Direct Factory Sourcing Made Seamless</h2>
        <p class="text-sm text-outline mt-2">Zero brokers, zero markups. Direct contract manufacturing with verified sports facilities in India.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="p-6 rounded-2xl bg-white border border-surface-container shadow-sm relative">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-800 font-headline font-bold text-lg flex items-center justify-center mb-4">
                01
            </div>
            <h3 class="font-headline font-bold text-base text-on-surface mb-2">Submit Specs or Pick Catalog</h3>
            <p class="text-xs text-outline leading-relaxed">
                Provide custom branding logos, Pantone shades, materials (e.g. Japanese PU, English Willow), or choose ready certified models.
            </p>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-surface-container shadow-sm relative">
            <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-800 font-headline font-bold text-lg flex items-center justify-center mb-4">
                02
            </div>
            <h3 class="font-headline font-bold text-base text-on-surface mb-2">Receive Direct Factory Quotes</h3>
            <p class="text-xs text-outline leading-relaxed">
                Audited Indian manufacturing clusters quote FOB/CIF with verified turnaround times, sample dispatch, and volume discount matrices.
            </p>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-surface-container shadow-sm relative">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-800 font-headline font-bold text-lg flex items-center justify-center mb-4">
                03
            </div>
            <h3 class="font-headline font-bold text-base text-on-surface mb-2">Quality Inspection & Port Freight</h3>
            <p class="text-xs text-outline leading-relaxed">
                100% pre-shipment quality audit and express dispatch from Indian seaports (Nhava Sheva, Mundra) to your distribution warehouses worldwide.
            </p>
        </div>
    </div>
</section>

<!-- 5. CALL TO ACTION BANNER -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 w-full">
    <div class="rounded-3xl bg-gradient-to-r from-blue-900 via-blue-800 to-teal-800 p-8 sm:p-12 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="space-y-3 max-w-xl text-center md:text-left">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                <span>Custom OEM / ODM Inquiries</span>
            </div>
            <h2 class="font-headline text-2xl sm:text-3xl font-extrabold text-white">Need Bespoke Sports Equipment for Your Brand?</h2>
            <p class="text-sm text-blue-100 leading-relaxed">
                Submit your target specifications. Our verified factories will generate customized production estimates and dispatch physical samples in 5-7 business days.
            </p>
        </div>
        <div class="shrink-0 flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <button type="button" onclick="openInquiryModal('Custom Wholesale RFP', 'Sports Junction Pool', 200)" class="px-8 py-3.5 rounded-xl bg-white text-blue-900 font-headline font-bold text-sm shadow-md hover:bg-gray-100 transition-all text-center">
                Submit Bulk RFQ
            </button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="px-8 py-3.5 rounded-xl bg-blue-700/60 hover:bg-blue-700 text-white font-headline font-bold text-sm border border-white/20 transition-all text-center">
                Browse Catalog
            </a>
        </div>
    </div>
</section>
