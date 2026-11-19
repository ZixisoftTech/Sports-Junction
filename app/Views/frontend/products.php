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
<div id="productsBreadcrumbWrapper" class="bg-surface-container-low/70 border-b border-surface-container py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav id="productsBreadcrumb" class="flex items-center gap-2 text-xs text-outline font-medium">
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="hover:text-primary transition-colors">Home</a>
            <span>/</span>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="breadcrumb-link <?= empty($selectedCategory) ? 'text-primary font-bold' : 'hover:text-primary' ?>">Products</a>
            <?php if (!empty($selectedCategory)): ?>
                <span>/</span>
                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($selectedCategory['slug']) ?>" class="breadcrumb-link <?= empty($selectedSubcategory) ? 'text-primary font-bold' : 'hover:text-primary' ?>">
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
                <!-- Keep search and category state if present -->
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                <?php endif; ?>
                <?php if (!empty($selectedCatSlug)): ?>
                    <input type="hidden" name="category_slug" id="hiddenCatSlug" value="<?= htmlspecialchars($selectedCatSlug) ?>">
                <?php else: ?>
                    <input type="hidden" name="category_slug" id="hiddenCatSlug" value="">
                <?php endif; ?>
                <?php if (!empty($selectedSubSlug)): ?>
                    <input type="hidden" name="subcategory_slug" id="hiddenSubSlug" value="<?= htmlspecialchars($selectedSubSlug) ?>">
                <?php else: ?>
                    <input type="hidden" name="subcategory_slug" id="hiddenSubSlug" value="">
                <?php endif; ?>

                <!-- Clear Filters & Title -->
                <div class="flex items-center justify-between pb-3 border-b border-surface-container">
                    <h3 class="font-headline font-bold text-sm text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">tune</span>
                        <span>Filter Results</span>
                    </h3>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="filter-reset-link text-xs text-blue-700 hover:underline font-semibold cursor-pointer">
                        Reset All
                    </a>
                </div>

                <!-- Category Accordion / Hierarchy with Subcategory Arrows -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Category</h4>
                    <div id="categoryAccordion" class="space-y-1 max-h-80 overflow-y-auto pr-1">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="category-filter-link flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-semibold <?= empty($selectedCatSlug) ? 'bg-primary text-white font-bold' : 'text-on-surface hover:bg-surface-container-low' ?>" data-slug="" data-name="All Categories">
                            <span>All Categories</span>
                            <span class="text-[10px] <?= empty($selectedCatSlug) ? 'text-white/80' : 'text-outline' ?>"><?= (int)($totalVerifiedCount ?? $total) ?></span>
                        </a>

                        <?php foreach ($categories as $cat): ?>
                            <?php 
                            $isCatActive = ($selectedCatSlug === $cat['slug']); 
                            $hasSubs = !empty($cat['subcategories']);
                            $catIcon = trim($cat['icon'] ?? 'directions_run');
                            $isImg = (str_starts_with($catIcon, '/') || str_starts_with($catIcon, 'http') || str_contains($catIcon, '.'));
                            ?>
                            <div class="category-sidebar-group mb-0.5" data-cat-slug="<?= htmlspecialchars($cat['slug']) ?>">
                                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg text-xs font-semibold transition-colors <?= $isCatActive && empty($selectedSubSlug) ? 'bg-blue-100 text-primary font-bold' : 'text-on-surface hover:bg-surface-container-low' ?>">
                                    <!-- Category Link (Clicking loads all products for this category) -->
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>" 
                                       class="category-filter-link flex items-center gap-1.5 truncate flex-1 min-w-0 pr-1 text-inherit cursor-pointer"
                                       title="View all <?= htmlspecialchars($cat['name']) ?> products"
                                       data-slug="<?= htmlspecialchars($cat['slug']) ?>"
                                       data-name="<?= htmlspecialchars($cat['name']) ?>">
                                        <?php if ($isImg): ?>
                                            <img src="<?= htmlspecialchars(\App\Core\Response::formatImageUrl($catIcon)) ?>" class="w-3.5 h-3.5 object-contain rounded shrink-0" alt="" onerror="this.onerror=null;this.replaceWith((function(){var s=document.createElement('span');s.className='material-symbols-outlined text-[16px] text-primary shrink-0';s.textContent='directions_run';return s;})())">
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-[16px] text-primary shrink-0"><?= htmlspecialchars($catIcon) ?></span>
                                        <?php endif; ?>
                                        <span class="truncate"><?= htmlspecialchars($cat['name']) ?></span>
                                    </a>

                                    <!-- Right side: Count & Subcategory Arrow Toggle -->
                                    <div class="flex items-center gap-1 shrink-0">
                                        <span class="text-[10px] text-outline px-1"><?= (int)($cat['product_count'] ?? 0) ?></span>
                                        <?php if ($hasSubs): ?>
                                            <button type="button" 
                                                    class="subcat-toggle-btn p-0.5 rounded hover:bg-blue-200/60 text-slate-500 hover:text-primary transition-transform duration-200 flex items-center justify-center cursor-pointer <?= $isCatActive ? 'expanded rotate-180 text-primary' : '' ?>"
                                                    title="Toggle <?= htmlspecialchars($cat['name']) ?> subcategories"
                                                    aria-label="Toggle subcategories"
                                                    onclick="toggleSubcategories(this, event)">
                                                <span class="material-symbols-outlined text-[16px]">expand_more</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Subcategories List (Rendered for ALL categories with subs; visible when active or toggled via arrow) -->
                                <?php if ($hasSubs): ?>
                                    <div class="subcategories-drawer pl-4 mt-1 space-y-0.5 border-l-2 border-blue-200 ml-3 <?= $isCatActive ? '' : 'hidden' ?>" data-parent-slug="<?= htmlspecialchars($cat['slug']) ?>">
                                        <?php foreach ($cat['subcategories'] as $sub): ?>
                                            <?php $isSubActive = ($selectedCatSlug === $cat['slug'] && $selectedSubSlug === $sub['slug']); ?>
                                            <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>/<?= htmlspecialchars($sub['slug']) ?>" 
                                               class="subcat-filter-link flex items-center justify-between px-2 py-1 rounded text-xs transition-colors cursor-pointer <?= $isSubActive ? 'bg-blue-600 text-white font-bold' : 'text-outline hover:text-primary hover:bg-blue-50/60' ?>"
                                               data-cat-slug="<?= htmlspecialchars($cat['slug']) ?>"
                                               data-sub-slug="<?= htmlspecialchars($sub['slug']) ?>"
                                               title="View <?= htmlspecialchars($sub['name']) ?>">
                                                <span class="truncate"><?= htmlspecialchars($sub['name']) ?></span>
                                                <span class="text-[9px] <?= $isSubActive ? 'text-white/80' : 'text-outline' ?>"><?= (int)($sub['product_count'] ?? 0) ?></span>
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
                        <input type="number" id="filterMinPrice" name="min_price" value="<?= htmlspecialchars((string)$minPrice) ?>" placeholder="Min" class="filter-input w-full px-2.5 py-1.5 text-xs bg-white border border-outline-variant rounded-lg outline-none focus:border-primary">
                        <input type="number" id="filterMaxPrice" name="max_price" value="<?= htmlspecialchars((string)$maxPrice) ?>" placeholder="Max" class="filter-input w-full px-2.5 py-1.5 text-xs bg-white border border-outline-variant rounded-lg outline-none focus:border-primary">
                    </div>
                </div>

                <!-- Maximum MOQ Filter -->
                <div class="pt-4 border-t border-surface-container">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Maximum MOQ</h4>
                    <input type="number" id="filterMaxMoq" name="max_moq" value="<?= htmlspecialchars((string)$maxMoq) ?>" placeholder="e.g. 100" class="filter-input w-full px-2.5 py-1.5 text-xs bg-white border border-outline-variant rounded-lg outline-none focus:border-primary">
                </div>

                <!-- Verified / Sourcing Badges -->
                <div class="pt-4 border-t border-surface-container space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-2">Sourcing Filters</h4>
                    
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="in_stock" value="1" <?= !empty($filters['in_stock']) ? 'checked' : '' ?> class="filter-checkbox rounded text-primary focus:ring-0">
                        <span>Ready Stock Only</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="verified_only" value="1" <?= !empty($filters['verified_only']) ? 'checked' : '' ?> class="filter-checkbox rounded text-primary focus:ring-0">
                        <span>Verified Manufacturers</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="trade_assurance" value="1" <?= !empty($filters['trade_assurance']) ? 'checked' : '' ?> class="filter-checkbox rounded text-primary focus:ring-0">
                        <span>Sports Junction Assured</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="custom_logo" value="1" <?= !empty($filters['custom_logo']) ? 'checked' : '' ?> class="filter-checkbox rounded text-primary focus:ring-0">
                        <span>Custom Logo Print</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="sample_available" value="1" <?= !empty($filters['sample_available']) ? 'checked' : '' ?> class="filter-checkbox rounded text-primary focus:ring-0">
                        <span>Paid Sample Available</span>
                    </label>
                </div>

                <!-- Submit Filter Button -->
                <button type="submit" id="applyFilterBtn" class="w-full py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-headline font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                    <span>Apply Filters</span>
                </button>
            </form>
        </aside>

        <!-- MAIN PRODUCTS CONTENT (Replaced Dynamically via AJAX) -->
        <main class="flex-1 relative min-w-0" id="productsMainContent">
            <!-- Loading Indicator Overlay -->
            <div id="productsLoadingOverlay" class="absolute inset-0 bg-white/75 backdrop-blur-xs flex items-center justify-center z-30 rounded-2xl hidden opacity-0 transition-opacity duration-200">
                <div class="flex flex-col items-center gap-2 p-5 bg-white rounded-2xl shadow-xl border border-slate-200 text-center">
                    <div class="w-9 h-9 border-3 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-bold text-slate-800">Updating product catalog...</span>
                </div>
            </div>

            <!-- Top Controls Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 mb-6 border-b border-surface-container">
                <div>
                    <h1 class="font-headline font-bold text-xl sm:text-2xl text-on-surface" id="productsCatalogTitle">
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
                    <p class="text-xs text-outline mt-0.5" id="productsCatalogCount">
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
                    <select id="sortSelect" onchange="applySorting(this.value)" class="text-xs bg-white border border-outline-variant rounded-lg pl-3 pr-8 py-2 font-medium text-on-surface outline-none focus:border-primary min-w-[200px] sm:min-w-[220px] cursor-pointer shadow-xs">
                        <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured Highlights</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="moq_low" <?= $sort === 'moq_low' ? 'selected' : '' ?>>MOQ: Low to High</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid Container -->
            <div id="productsGridContainer">
                <?php if (!empty($products)): ?>
                    <?php
                        $currentUser = \App\Helpers\Session::get('auth_user');
                        $buyerSortIds = [];
                        if ($currentUser && ($currentUser['role'] ?? '') === 'BUYER') {
                            try {
                                $buyerSortIds = \App\Models\BuyerFavourite::getBuyerSortProductIds((int)$currentUser['id']);
                            } catch (\Throwable $e) {
                                $buyerSortIds = [];
                            }
                        } else {
                            $buyerSortIds = [];
                        }
                    ?>
                    <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
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

                                    <div class="absolute top-3 right-3 flex items-center gap-1.5 z-10">
                                        <span class="px-2 py-0.5 rounded-md bg-blue-700 text-white font-mono text-[10px] font-bold shadow-sm">
                                            OEM / ODM
                                        </span>
                                        <?php 
                                            $inSort = in_array((int)$prod['id'], $buyerSortIds, true);
                                        ?>
                                        <button type="button" 
                                            data-product-sort-id="<?= (int)$prod['id'] ?>"
                                            onclick="toggleSortProduct(<?= (int)$prod['id'] ?>, this)" 
                                            class="sort-btn w-7 h-7 rounded-full bg-white/95 backdrop-blur-md shadow-xs border <?= $inSort ? 'text-blue-900 border-blue-500 bg-blue-50' : 'text-slate-600 border-slate-200 hover:text-blue-900 hover:border-blue-400' ?> flex items-center justify-center transition-all cursor-pointer" 
                                            title="<?= $inSort ? 'In Sort List' : 'Add to Sort' ?>">
                                            <span class="material-symbols-outlined text-[16px] <?= $inSort ? 'fill-1' : '' ?>">checklist</span>
                                        </button>
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

                                    <!-- Card Footer CTAs (50% Shortlist / 50% Chat Now) -->
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
                                            <!-- Button 1: Shortlist -->
                                            <button type="button" 
                                                data-product-sort-id="<?= (int)$prod['id'] ?>"
                                                onclick="toggleSortProduct(<?= (int)$prod['id'] ?>, this)" 
                                                class="sort-footer-btn w-full py-2 text-center rounded-xl <?= $inSort ? 'bg-blue-50 text-blue-900 border border-blue-400' : 'bg-surface-container-low hover:bg-surface-container-high text-blue-900 border border-slate-200/80' ?> text-xs font-bold transition-colors inline-flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs" 
                                                title="<?= $inSort ? 'In Sort List' : 'Add to Sort' ?>">
                                                <span class="material-symbols-outlined text-[15px] <?= $inSort ? 'text-blue-900 font-bold' : 'text-blue-900' ?>"><?= $inSort ? 'check' : 'playlist_add' ?></span>
                                                <span class="sort-btn-text"><?= $inSort ? 'In Sort List' : 'Shortlist' ?></span>
                                            </button>

                                            <!-- Button 2: Chat Now -->
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
                                                <span>Chat Now</span>
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
                        $cleanFilters = array_filter($filters, fn($v) => $v !== '' && $v !== null && $v !== '0');
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                        ?>
                        <div id="productsPagination" class="mt-12 flex flex-wrap items-center justify-center gap-2 text-xs font-semibold">
                            <?php if ($currentPage > 1): ?>
                                <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $currentPage - 1])) ?>" class="pagination-link px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface cursor-pointer">
                                    &larr; Previous
                                </a>
                            <?php endif; ?>

                            <?php if ($startPage > 1): ?>
                                <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => 1])) ?>" class="pagination-link px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface cursor-pointer">
                                    1
                                </a>
                                <?php if ($startPage > 2): ?>
                                    <span class="px-2 py-2 text-outline">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $p])) ?>" class="pagination-link px-3.5 py-2 rounded-xl cursor-pointer <?= $p === $currentPage ? 'bg-primary text-white font-bold' : 'border border-surface-container bg-white hover:bg-surface-container-low text-on-surface' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($endPage < $lastPage): ?>
                                <?php if ($endPage < $lastPage - 1): ?>
                                    <span class="px-2 py-2 text-outline">...</span>
                                <?php endif; ?>
                                <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $lastPage])) ?>" class="pagination-link px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface cursor-pointer">
                                    <?= $lastPage ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($currentPage < $lastPage): ?>
                                <a href="?<?= http_build_query(array_merge($cleanFilters, ['page' => $currentPage + 1])) ?>" class="pagination-link px-3.5 py-2 rounded-xl border border-surface-container bg-white hover:bg-surface-container-low text-on-surface cursor-pointer">
                                    Next &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Results Empty State -->
                    <div id="noProductsFound" class="text-center py-20 bg-white rounded-2xl border border-surface-container p-8">
                        <span class="material-symbols-outlined text-[64px] text-gray-300 mb-4">search_off</span>
                        <h3 class="font-headline font-bold text-lg text-on-surface mb-2">No matching products found</h3>
                        <p class="text-xs text-outline max-w-md mx-auto mb-6">
                            We couldn't find any products matching your specific filter criteria. Try adjusting your price range, MOQ, or search keywords.
                        </p>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="filter-reset-link inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold shadow-sm cursor-pointer transition-colors">
                            <span>Clear All Filters</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
/**
 * SPORTS JUNCTION — DYNAMIC AJAX FILTERING & SUBCATEGORY ACCORDION ENGINE
 */
(function() {
    let isFetching = false;

    // Toggle Subcategories Dropdown inside Left Sidebar
    window.toggleSubcategories = function(btn, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const group = btn.closest('.category-sidebar-group');
        if (!group) return;
        const drawer = group.querySelector('.subcategories-drawer');
        if (!drawer) return;

        const isHidden = drawer.classList.contains('hidden');
        if (isHidden) {
            drawer.classList.remove('hidden');
            btn.classList.add('rotate-180', 'text-primary');
        } else {
            drawer.classList.add('hidden');
            btn.classList.remove('rotate-180', 'text-primary');
        }
    };

    // Sort Handler (AJAX)
    window.applySorting = function(sortVal) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortVal);
        url.searchParams.set('page', '1');
        fetchFilteredProducts(url.toString(), true, false);
    };

    // Main AJAX Fetch & DOM Replacement Method
    window.fetchFilteredProducts = async function(targetUrl, pushState = true, scrollToTop = false) {
        if (isFetching) return;
        isFetching = true;

        const mainContent = document.getElementById('productsMainContent');
        const overlay = document.getElementById('productsLoadingOverlay');

        // Show smooth overlay
        if (overlay) {
            overlay.classList.remove('hidden');
            setTimeout(() => { overlay.classList.remove('opacity-0'); }, 10);
        }

        try {
            const parsedUrl = new URL(targetUrl, window.location.origin);
            const response = await fetch(parsedUrl.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, */*'
                }
            });

            if (!response.ok) {
                // If fetch fails, fall back to normal browser navigation
                window.location.href = parsedUrl.toString();
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Update Main Products Content
            const newMain = doc.getElementById('productsMainContent');
            if (newMain && mainContent) {
                // Keep overlay
                mainContent.innerHTML = newMain.innerHTML;
            }

            // 2. Update Breadcrumbs
            const newBreadcrumb = doc.getElementById('productsBreadcrumb');
            const currentBreadcrumb = document.getElementById('productsBreadcrumb');
            if (newBreadcrumb && currentBreadcrumb) {
                currentBreadcrumb.innerHTML = newBreadcrumb.innerHTML;
            }

            // 3. Update Category Accordion
            const newAccordion = doc.getElementById('categoryAccordion');
            const currentAccordion = document.getElementById('categoryAccordion');
            if (newAccordion && currentAccordion) {
                currentAccordion.innerHTML = newAccordion.innerHTML;
            }

            // 4. Update Form Hidden Slug Inputs
            const newCatSlug = doc.getElementById('hiddenCatSlug');
            const currentCatSlug = document.getElementById('hiddenCatSlug');
            if (newCatSlug && currentCatSlug) {
                currentCatSlug.value = newCatSlug.value;
            }

            const newSubSlug = doc.getElementById('hiddenSubSlug');
            const currentSubSlug = document.getElementById('hiddenSubSlug');
            if (newSubSlug && currentSubSlug) {
                currentSubSlug.value = newSubSlug.value;
            }

            // 5. Update Browser URL and History
            if (pushState) {
                window.history.pushState({ url: parsedUrl.toString() }, '', parsedUrl.toString());
            }

            // 6. Update Document Title if available
            const newTitle = doc.querySelector('title');
            if (newTitle && newTitle.textContent) {
                document.title = newTitle.textContent;
            }

            // 7. Scroll smoothly to top of product grid if requested
            if (scrollToTop && mainContent) {
                const headerOffset = 100;
                const elementPosition = mainContent.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }

            // Re-bind dynamic interactions for newly loaded elements
            bindAjaxEvents();

        } catch (err) {
            console.error('[AJAX Filter] Request error:', err);
            window.location.href = targetUrl;
        } finally {
            isFetching = false;
            const newOverlay = document.getElementById('productsLoadingOverlay');
            if (newOverlay) {
                newOverlay.classList.add('opacity-0');
                setTimeout(() => { newOverlay.classList.add('hidden'); }, 200);
            }
        }
    };

    // Serialize Form inputs into query parameters
    function serializeFilterForm(form) {
        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            const strVal = String(value).trim();
            // Do not send empty or zero defaults for cleaner URLs
            if (strVal !== '' && strVal !== null) {
                params.append(key, strVal);
            }
        }

        // Always reset to page 1 on filter application
        params.delete('page');

        // Preserve sort if selected
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect && sortSelect.value && sortSelect.value !== 'featured') {
            params.set('sort', sortSelect.value);
        }

        return params;
    }

    // Attach event listeners to all interactive elements
    function bindAjaxEvents() {
        const filterForm = document.getElementById('filterForm');
        if (filterForm && !filterForm._hasAjaxSubmit) {
            filterForm._hasAjaxSubmit = true;
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const params = serializeFilterForm(filterForm);
                const baseUrl = window.location.pathname;
                const targetUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
                fetchFilteredProducts(targetUrl, true, true);
            });
        }

        // Auto-filter on checkbox click (instant dynamic feedback)
        document.querySelectorAll('.filter-checkbox').forEach(cb => {
            if (!cb._hasAjaxChange) {
                cb._hasAjaxChange = true;
                cb.addEventListener('change', function() {
                    if (filterForm) {
                        const params = serializeFilterForm(filterForm);
                        const baseUrl = window.location.pathname;
                        const targetUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
                        fetchFilteredProducts(targetUrl, true, false);
                    }
                });
            }
        });

        // Price and MOQ inputs: trigger filter on 'Enter' key
        document.querySelectorAll('.filter-input').forEach(input => {
            if (!input._hasAjaxEnter) {
                input._hasAjaxEnter = true;
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (filterForm) {
                            const params = serializeFilterForm(filterForm);
                            const baseUrl = window.location.pathname;
                            const targetUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
                            fetchFilteredProducts(targetUrl, true, true);
                        }
                    }
                });
            }
        });

        // Intercept Category Links
        document.querySelectorAll('.category-filter-link').forEach(link => {
            if (!link._hasAjaxClick) {
                link._hasAjaxClick = true;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Open subcategories drawer for this category if it exists
                    const group = this.closest('.category-sidebar-group');
                    if (group) {
                        const drawer = group.querySelector('.subcategories-drawer');
                        const toggleBtn = group.querySelector('.subcat-toggle-btn');
                        if (drawer) {
                            drawer.classList.remove('hidden');
                        }
                        if (toggleBtn) {
                            toggleBtn.classList.add('rotate-180', 'text-primary');
                        }
                    }
                    fetchFilteredProducts(this.href, true, true);
                });
            }
        });

        // Intercept Subcategory Links
        document.querySelectorAll('.subcat-filter-link').forEach(link => {
            if (!link._hasAjaxClick) {
                link._hasAjaxClick = true;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchFilteredProducts(this.href, true, true);
                });
            }
        });

        // Intercept Breadcrumb Category Links
        document.querySelectorAll('.breadcrumb-link').forEach(link => {
            if (!link._hasAjaxClick) {
                link._hasAjaxClick = true;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchFilteredProducts(this.href, true, true);
                });
            }
        });

        // Intercept Pagination Links
        document.querySelectorAll('.pagination-link').forEach(link => {
            if (!link._hasAjaxClick) {
                link._hasAjaxClick = true;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchFilteredProducts(this.href, true, true);
                });
            }
        });

        // Intercept Reset / Clear All Links
        document.querySelectorAll('.filter-reset-link').forEach(link => {
            if (!link._hasAjaxClick) {
                link._hasAjaxClick = true;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Clear inputs in form
                    if (filterForm) {
                        filterForm.reset();
                        const minP = document.getElementById('filterMinPrice');
                        const maxP = document.getElementById('filterMaxPrice');
                        const maxM = document.getElementById('filterMaxMoq');
                        const hiddenCat = document.getElementById('hiddenCatSlug');
                        const hiddenSub = document.getElementById('hiddenSubSlug');
                        if (minP) minP.value = '';
                        if (maxP) maxP.value = '';
                        if (maxM) maxM.value = '';
                        if (hiddenCat) hiddenCat.value = '';
                        if (hiddenSub) hiddenSub.value = '';
                    }
                    fetchFilteredProducts(this.href, true, true);
                });
            }
        });
    }

    // Handle Browser Back / Forward buttons without full page reloads
    window.addEventListener('popstate', function(e) {
        fetchFilteredProducts(window.location.href, false, false);
    });

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindAjaxEvents);
    } else {
        bindAjaxEvents();
    }
})();
</script>
