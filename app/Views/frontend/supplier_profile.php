<?php
declare(strict_types=1);

use App\Core\Response;

$baseUrl = Response::baseUrl();
$supplierName = htmlspecialchars($supplier['company_name'] ?? 'Verified Manufacturer');
$cityName = htmlspecialchars($supplier['city_name'] ?? '');
$stateName = htmlspecialchars($supplier['state_name'] ?? '');
$countryName = htmlspecialchars($supplier['country_name'] ?? 'India');
$businessType = htmlspecialchars($supplier['business_type_name'] ?? 'Sports Equipment Manufacturer');
$address = htmlspecialchars($supplier['address'] ?? '');
$contactPerson = htmlspecialchars($supplier['contact_person'] ?? 'Factory Representative');
$supplierId = (int) ($supplier['id'] ?? 0);
$productCount = count($products);
$supplierTags = !empty($supplierId) ? \App\Models\SupplierCertificationTag::getForSupplier($supplierId) : [];
?>

<div class="bg-surface-container-lowest min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-outline font-medium">
            <a href="<?= $baseUrl ?>/" class="hover:text-primary transition-colors">Home</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <a href="<?= $baseUrl ?>/products" class="hover:text-primary transition-colors">Manufacturers</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="text-on-surface font-semibold truncate max-w-xs"><?= $supplierName ?></span>
        </nav>

        <!-- FACTORY HERO HEADER CARD -->
        <div class="bg-white rounded-3xl border border-surface-container shadow-sm overflow-hidden">
            <!-- Top Banner Accent -->
            <div class="h-28 sm:h-36 bg-gradient-to-r from-blue-900 via-teal-900 to-slate-900 relative">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.1),transparent)]"></div>
                <div class="absolute top-4 right-4 flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 backdrop-blur-md text-emerald-300 border border-emerald-400/30 text-xs font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Direct Manufacturer
                    </span>
                </div>
            </div>

            <!-- Profile Info Body -->
            <div class="px-6 pb-6 pt-0 relative">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-12 sm:-mt-14 mb-4">
                    <div class="flex items-end gap-4">
                        <!-- Factory Avatar / Logo Badge -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white p-1.5 shadow-xl border border-surface-container flex-shrink-0 flex items-center justify-center overflow-hidden">
                            <?php if (!empty($supplier['logo'])): ?>
                                <img src="<?= htmlspecialchars(str_starts_with($supplier['logo'], '/') ? ($baseUrl . $supplier['logo']) : $supplier['logo']) ?>" alt="<?= $supplierName ?>" class="w-full h-full object-contain rounded-xl">
                            <?php else: ?>
                                <div class="w-full h-full rounded-xl bg-gradient-to-br from-blue-900 to-teal-800 text-white flex items-center justify-center font-headline font-extrabold text-2xl sm:text-3xl shadow-inner">
                                    <?= strtoupper(substr($supplier['company_name'] ?? 'M', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="font-headline font-extrabold text-xl sm:text-2xl text-on-surface tracking-tight">
                                    <?= $supplierName ?>
                                </h1>
                                <span class="material-symbols-outlined text-teal-600 text-[22px]" title="Government & Platform Verified Indian Manufacturer">verified</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-outline mt-1 flex-wrap">
                                <span class="font-semibold text-teal-800"><?= $businessType ?></span>
                                <span>•</span>
                                <span class="flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[15px] text-blue-700">location_on</span>
                                    <?= $cityName ?><?= ($cityName && $stateName) ? ', ' : '' ?><?= $stateName ?><?= ($stateName && $countryName) ? ', ' : '' ?><?= $countryName ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Chat / RFQ Action Buttons -->
                    <div class="flex items-center gap-2.5">
                        <button type="button" onclick="openProductChat({supplier_id: <?= $supplierId ?>, supplier_name: '<?= htmlspecialchars(addslashes($supplier['company_name'] ?? 'Supplier')) ?>'})" class="px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-xs shadow-md transition-all flex items-center gap-2 transform hover:scale-[1.02]">
                            <span class="material-symbols-outlined text-[18px]">chat</span>
                            <span>Chat with Factory</span>
                        </button>
                        <a href="#factoryProducts" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors shadow-2xs flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                            <span>View Products (<?= $productCount ?>)</span>
                        </a>
                    </div>
                </div>

                <!-- Credibility & Trust Bar -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-surface-container text-xs">
                    <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Response SLA</span>
                        <div class="font-bold text-slate-900 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-teal-600">bolt</span>
                            <span>&lt; 2.4 Hours Response</span>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Pricing Model</span>
                        <div class="font-bold text-slate-900 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-blue-700">payments</span>
                            <span>Direct Factory (0% Markup)</span>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Verification Status</span>
                        <div class="font-bold text-teal-800 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-teal-600">verified_user</span>
                            <span>Govt & GST Audited</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Supplier Certification Tags -->
                <?php if (!empty($supplierTags)): ?>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2 flex-wrap">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Certifications & Audits:</span>
                        <?php foreach ($supplierTags as $tag): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 text-teal-800 border border-teal-200 text-xs font-semibold" title="<?= htmlspecialchars($tag['description'] ?? '') ?>">
                                <span class="material-symbols-outlined text-[15px] text-teal-600"><?= htmlspecialchars($tag['icon'] ?: 'verified') ?></span>
                                <span><?= htmlspecialchars($tag['name']) ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Description / Address if available -->
                <?php if (!empty($supplier['description']) || $address): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                        <?php if (!empty($supplier['description'])): ?>
                            <p class="leading-relaxed"><?= nl2br(htmlspecialchars($supplier['description'])) ?></p>
                        <?php endif; ?>
                        <?php if ($address): ?>
                            <div class="flex items-center gap-1.5 text-slate-500 pt-1">
                                <span class="material-symbols-outlined text-[15px]">apartment</span>
                                <span>Facility: <?= $address ?>, <?= $cityName ?>, <?= $stateName ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FACTORY PRODUCTS CATALOG SECTION -->
        <div id="factoryProducts" class="space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-surface-container">
                <div>
                    <h2 class="font-headline font-extrabold text-lg text-on-surface">Factory Product Catalog</h2>
                    <p class="text-xs text-outline">All products certified, quality-tested, and manufactured by <?= $supplierName ?>.</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-900 font-bold text-xs">
                    <?= $productCount ?> Published <?= $productCount === 1 ? 'Product' : 'Products' ?>
                </span>
            </div>

            <?php if (empty($products)): ?>
                <div class="bg-white rounded-2xl border border-surface-container p-12 text-center text-slate-400 space-y-3 shadow-xs">
                    <span class="material-symbols-outlined text-[48px] text-slate-300">inventory_2</span>
                    <h4 class="font-headline font-bold text-base text-slate-700">No Published Products Currently Listed</h4>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">This manufacturer is currently updating their production catalog or setting up private OEM supply contracts.</p>
                    <div class="pt-2">
                        <button type="button" onclick="openProductChat({supplier_id: <?= $supplierId ?>, supplier_name: '<?= htmlspecialchars(addslashes($supplier['company_name'] ?? 'Supplier')) ?>'})" class="px-4 py-2 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-xs transition-colors">
                            <span class="material-symbols-outlined text-[16px]">chat</span>
                            <span>Send Custom RFQ to Factory</span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($products as $p): ?>
                        <?php 
                            $imgSrc = !empty($p['primary_image']) ? ($baseUrl . $p['primary_image']) : ($baseUrl . '/assets/images/placeholder-product.jpg');
                            $priceStr = '$' . number_format((float)($p['min_price'] ?? $p['price'] ?? 0), 2);
                            $moqStr = (int)($p['moq_quantity'] ?? 1) . ' ' . htmlspecialchars($p['moq_unit_symbol'] ?? $p['moq_unit_name'] ?? 'Units');
                        ?>
                        <div class="bg-white rounded-2xl border border-surface-container overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col group">
                            <!-- Product Image -->
                            <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="block relative aspect-square bg-slate-50 overflow-hidden">
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['name'] ?? $p['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <?php if (!empty($p['category_name'])): ?>
                                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md bg-white/90 backdrop-blur-xs text-[10px] font-bold text-slate-700 border border-slate-200/80 shadow-2xs">
                                        <?= htmlspecialchars($p['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </a>

                            <!-- Product Info -->
                            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                                <div>
                                    <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="block">
                                        <h3 class="font-headline font-bold text-xs text-on-surface hover:text-blue-900 line-clamp-2 transition-colors">
                                            <?= htmlspecialchars($p['name'] ?? $p['title']) ?>
                                        </h3>
                                    </a>
                                    
                                    <div class="mt-2 flex items-baseline justify-between gap-1">
                                        <span class="font-headline font-extrabold text-sm text-blue-900"><?= $priceStr ?></span>
                                        <span class="text-[10px] text-slate-500 font-medium">MOQ: <?= $moqStr ?></span>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                                    <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="flex-1 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-center text-xs font-bold transition-colors">
                                        View Specs
                                    </a>
                                    <button type="button" onclick="openProductChat({id: <?= (int)$p['id'] ?>, product_id: <?= (int)$p['id'] ?>, supplier_id: <?= $supplierId ?>, title: '<?= htmlspecialchars(addslashes($p['name'] ?? $p['title'])) ?>', price: '<?= (float)($p['min_price'] ?? $p['price'] ?? 0) ?>', moq: <?= (int)($p['moq_quantity'] ?? 1) ?>, image: '<?= htmlspecialchars(addslashes($imgSrc)) ?>'})" class="p-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 transition-colors" title="Negotiate & Chat for this product">
                                        <span class="material-symbols-outlined text-[18px]">chat</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
