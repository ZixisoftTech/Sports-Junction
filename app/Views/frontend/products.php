<?php
use App\Core\Response;
$baseUrl = Response::baseUrl();

$selectedCatSlug = $selectedCategory['slug'] ?? ($filters['category_slug'] ?? '');
$selectedSubSlug = $selectedSubcategory['slug'] ?? ($filters['subcategory_slug'] ?? '');
$searchQuery = $filters['q'] ?? '';
$minPrice = $filters['min_price'] ?? '';
$maxPrice = $filters['max_price'] ?? '';
$maxMoq = $filters['max_moq'] ?? '';
$sort = $filters['sort'] ?? 'featured';
?>
<!-- Breadcrumb Header -->
<div class="bg-surface-container-low/70 border-b border-surface-container py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-xs text-outline font-medium">
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="hover:text-primary transition-colors">Home</a>
            <span>/</span>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="<?= empty($selectedCategory) ? 'text-primary font-bold' : 'hover:text-primary' ?>">Products</a>
            <?php if (!empty($selectedCategory)): ?>
                <span>/</span>
                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($selectedCategory['slug']) ?>" class="<?= empty($selectedSubcategory) ? 'text-primary font-bold' : 'hover:text-primary' ?>">
                    <?= htmlspecialchars($selectedCategory['name']) ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($selectedSubcategory)): ?>
                <span>/</span>
                <span class="text-primary font-bold"><?= htmlspecialchars($selectedSubcategory['name']) ?></span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- LEFT FILTER SIDEBAR -->
        <aside class="w-full lg:w-64 shrink-0 space-y-6">
            <form id="filterForm" action="<?= htmlspecialchars($baseUrl) ?>/products" method="GET" class="space-y-6">
                <!-- Keep search query if present -->
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                <?php endif; ?>

                <!-- Clear Filters & Title -->
                <div class="flex items-center justify-between pb-3 border-b border-surface-container">
                    <h3 class="font-headline font-bold text-sm text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">tune</span>
                        <span>Filter Results</span>
                    </h3>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="text-xs text-blue-700 hover:underline font-semibold">
                        Reset All
                    </a>
                </div>

                <!-- Category Accordion / Hierarchy -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Category</h4>
                    <div class="space-y-1 max-h-72 overflow-y-auto pr-1">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-semibold <?= empty($selectedCatSlug) ? 'bg-primary text-white' : 'text-on-surface hover:bg-surface-container-low' ?>">
                            <span>All Categories</span>
                            <span class="text-[10px]"><?= (int)($totalVerifiedCount ?? $total) ?></span>
                        </a>

                        <?php foreach ($categories as $cat): ?>
                            <?php $isCatActive = ($selectedCatSlug === $cat['slug']); ?>
                            <div>
                                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>" class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-semibold <?= $isCatActive && empty($selectedSubSlug) ? 'bg-blue-100 text-primary font-bold' : 'text-on-surface hover:bg-surface-container-low' ?>">
                                    <span class="flex items-center gap-1.5 truncate">
                                        <?php 
                                        $catIcon = trim($cat['icon'] ?? 'sports_tennis');
                                        $isImg = (str_starts_with($catIcon, '/') || str_starts_with($catIcon, 'http') || str_contains($catIcon, '.'));
                                        if ($isImg): ?>
                                            <img src="<?= htmlspecialchars(\App\Core\Response::formatImageUrl($catIcon)) ?>" class="w-3.5 h-3.5 object-contain rounded" alt="">
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-[16px] text-primary"><?= htmlspecialchars($catIcon) ?></span>
                                        <?php endif; ?>
                                        <span class="truncate"><?= htmlspecialchars($cat['name']) ?></span>
                                    </span>
                                    <span class="text-[10px] text-outline"><?= (int)($cat['product_count'] ?? 0) ?></span>
                                </a>

                                <!-- Subcategories -->
                                <?php if ($isCatActive && !empty($cat['subcategories'])): ?>
                                    <div class="pl-4 mt-1 space-y-0.5 border-l-2 border-blue-200 ml-3">
                                        <?php foreach ($cat['subcategories'] as $sub): ?>
                                            <?php $isSubActive = ($selectedSubSlug === $sub['slug']); ?>
                                            <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>/<?= htmlspecialchars($sub['slug']) ?>" class="flex items-center justify-between px-2 py-1 rounded text-xs <?= $isSubActive ? 'bg-blue-600 text-white font-bold' : 'text-outline hover:text-primary' ?>">
                                                <span class="truncate"><?= htmlspecialchars($sub['name']) ?></span>
                                                <span class="text-[9px]"><?= (int)($sub['product_count'] ?? 0) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Range Filter -->
                <div class="pt-4 border-t border-surface-container">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Price Range ($)</h4>
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <input type="number" name="min_price" value="<?= htmlspecialchars((string)$minPrice) ?>" placeholder="Min" class="w-full px-2.5 py-1.5 text-xs bg-white border border-outline-variant rounded-lg outline-none focus:border-primary">
                        <input type="number" name="max_price" value="<?= htmlspecialchars((string)$maxPrice) ?>" placeholder="Max" class="w-full px-2.5 py-1.5 text-xs bg-white border border-outline-variant rounded-lg outline-none focus:border-primary">
                    </div>
                </div>

                <!-- Maximum MOQ Filter -->
                <div class="pt-4 border-t border-surface-container">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Max Order MOQ</h4>
                    <input type="number" name="max_moq" value="<?= htmlspecialchars((string)$maxMoq) ?>" placeholder="e.g. 50" class="w-full px-2.5 py-1.5 text-xs bg-white border border-outline-variant rounded-lg outline-none focus:border-primary">
                </div>

                <!-- Customization Features -->
                <div class="pt-4 border-t border-surface-container space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Sourcing Features</h4>
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="oem" value="1" <?= !empty($filters['oem']) ? 'checked' : '' ?> class="rounded text-primary focus:ring-0">
                        <span>OEM / ODM Available</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="custom_logo" value="1" <?= !empty($filters['custom_logo']) ? 'checked' : '' ?> class="rounded text-primary focus:ring-0">
                        <span>Custom Logo Print</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="sample_available" value="1" <?= !empty($filters['sample_available']) ? 'checked' : '' ?> class="rounded text-primary focus:ring-0">
                        <span>Paid Sample Available</span>
                    </label>
                </div>

                <!-- Submit Filter Button -->
                <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-headline font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                    <span>Apply Filters</span>
                </button>
            </form>
        </aside>

        <!-- MAIN PRODUCTS CONTENT -->
        <main class="flex-1">
            <!-- Top Controls Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 mb-6 border-b border-surface-container">
                <div>
                    <h1 class="font-headline font-bold text-xl sm:text-2xl text-on-surface">
                        <?php if (!empty($selectedSubcategory)): ?>
                            <?= htmlspecialchars($selectedSubcategory['name']) ?>
                        <?php elseif (!empty($selectedCategory)): ?>
                            <?= htmlspecialchars($selectedCategory['name']) ?>
                        <?php elseif (!empty($searchQuery)): ?>
                            Search Results for "<?= htmlspecialchars($searchQuery) ?>"
                        <?php else: ?>
                            All Wholesale Products
                        <?php endif; ?>
                    </h1>
                    <p class="text-xs text-outline mt-0.5">
                        <?php if ($total > 0 && $from > 0): ?>
                            Showing <?= $from ?>–<?= $to ?> of <?= $total ?> products
                        <?php elseif ($total > 0): ?>
                            Showing 0 of <?= $total ?> products
                        <?php else: ?>
                            Showing 0 of 0 products
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Sort Control -->
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <label for="sortSelect" class="text-xs font-semibold text-outline text-nowrap">Sort By:</label>
                    <select id="sortSelect" onchange="applySorting(this.value)" class="text-xs bg-white border border-outline-variant rounded-lg px-3 py-1.5 font-medium text-on-surface outline-none focus:border-primary">
                        <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured Highlights</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="moq_low" <?= $sort === 'moq_low' ? 'selected' : '' ?>>MOQ: Low to High</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $prod): ?>
                        <?php 
                            $prodUrl = htmlspecialchars($baseUrl) . '/product/' . htmlspecialchars($prod['slug'] ?? (string)$prod['id']);
                            $img = \App\Helpers\Uploader::imageUrl($prod['primary_image'] ?? null);
                        ?>
                        <div class="bg-white rounded-2xl border border-surface-container shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                            <!-- Image Container -->
                            <div class="relative w-full h-56 bg-gray-100 overflow-hidden">
                                <a href="<?= $prodUrl ?>">
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prod['name'] ?? $prod['title'] ?? '') ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
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

                            <!-- Content Body -->
                            <div class="p-5 flex flex-col flex-1 justify-between">
                                <div>
                                    <div class="text-[11px] text-outline font-semibold uppercase tracking-wider mb-1">
                                        <?= htmlspecialchars(!empty($selectedCategory['name']) ? $selectedCategory['name'] : ($prod['all_category_names'] ?? $prod['category_name'] ?? 'Sports Gear')) ?>
                                    </div>
                                    <h3 class="font-headline font-bold text-sm text-on-surface line-clamp-2 mb-2 group-hover:text-blue-700 transition-colors">
                                        <a href="<?= $prodUrl ?>"><?= htmlspecialchars($prod['name'] ?? $prod['title'] ?? '') ?></a>
                                    </h3>

                                    <!-- Price Range -->
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

                                <!-- Card Footer CTAs (50% Specs & Samples / 50% Chat) -->
                                <div class="pt-3 border-t border-surface-container grid grid-cols-2 gap-2">
                                    <?php 
                                    $isOwnProd = \App\Models\Supplier::isOwnedByCurrentSupplier($prod);
                                    ?>
                                    <?php if ($isOwnProd): ?>
                                        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/edit/<?= (int)$prod['id'] ?>" class="w-full py-2 text-center rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-900 text-xs font-bold transition-colors inline-flex items-center justify-center gap-1.5" title="Manage / Edit Product in Supplier Portal">
                                            <span class="material-symbols-outlined text-[15px]">edit</span>
                                            <span>Manage / Edit</span>
                                        </a>
                                        <a href="<?= $prodUrl ?>" class="w-full py-2 text-center rounded-xl border border-surface-container hover:bg-slate-50 text-slate-700 text-xs font-bold transition-colors inline-flex items-center justify-center gap-1.5" title="View Public Page">
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>View Public</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $prodUrl ?>" class="w-full py-2 text-center rounded-xl bg-surface-container-low hover:bg-surface-container-high text-blue-900 text-xs font-bold transition-colors inline-flex items-center justify-center">
                                            Specs & Samples
                                        </a>
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
                                        ])) ?>)" class="w-full py-2 text-center rounded-xl bg-blue-900 hover:bg-blue-950 text-white text-xs font-bold transition-colors inline-flex items-center justify-center gap-1.5 cursor-pointer shadow-xs">
                                            <span class="material-symbols-outlined text-[16px]">chat</span>
                                            <span>Chat</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($lastPage > 1): ?>
                    <?php 
                    $cleanFilters = array_filter($filters, fn($v) => $v !== '' && $v !== null);
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($lastPage, $currentPage + 2);
                    ?>
                    <div class="mt-12 flex flex-wrap items-center justify-center gap-2 text-xs font-semibold">
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $currentPage - 1])) ?>" class="px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface">
                                &larr; Previous
                            </a>
                        <?php endif; ?>

                        <?php if ($startPage > 1): ?>
                            <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => 1])) ?>" class="px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface">
                                1
                            </a>
                            <?php if ($startPage > 2): ?>
                                <span class="px-2 py-2 text-outline">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                            <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $p])) ?>" class="px-3.5 py-2 rounded-xl <?= $p === $currentPage ? 'bg-primary text-white font-bold' : 'border border-surface-container bg-white hover:bg-surface-container-low text-on-surface' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($endPage < $lastPage): ?>
                            <?php if ($endPage < $lastPage - 1): ?>
                                <span class="px-2 py-2 text-outline">...</span>
                            <?php endif; ?>
                            <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $lastPage])) ?>" class="px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface">
                                <?= $lastPage ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($currentPage < $lastPage): ?>
                            <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $currentPage + 1])) ?>" class="px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface">
                                Next &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Results Empty State -->
                <div class="text-center py-20 bg-white rounded-2xl border border-surface-container p-8">
                    <span class="material-symbols-outlined text-[64px] text-gray-300 mb-4">search_off</span>
                    <h3 class="font-headline font-bold text-lg text-on-surface mb-2">No matching products found</h3>
                    <p class="text-xs text-outline max-w-md mx-auto mb-6">
                        We couldn't find any products matching your specific filter criteria. Try adjusting your price range, MOQ, or search keywords.
                    </p>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-700 text-white text-xs font-bold shadow-sm">
                        <span>Clear All Filters</span>
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    function applySorting(sortVal) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortVal);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }
</script>
