<?php
use App\Core\Response;
$baseUrl = Response::baseUrl();
?>
<!-- 1. HERO / SEARCH SECTION -->
<section class="relative bg-surface py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Dynamic Hero Banners Carousel -->
        <?php 
        $banners = !empty($heroBanners) ? $heroBanners : [
            [
                'badge_text'           => 'Factory Direct Sourcing Hub',
                'title'                => 'Direct From Verified Indian Sports & Fitness Manufacturers',
                'subtitle'             => 'Factory-Direct Wholesale Pricing • Guaranteed MOQs • ISO 9001 & BSCI Audited Production Facilities in Meerut & Jalandhar with Fast Sample Turnaround.',
                'image'                => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=1600&q=80',
                'cta_text'             => 'Source Catalog',
                'cta_url'              => '/products',
                'secondary_cta_text'   => 'Request Quotation',
                'secondary_cta_url'    => 'rfq_modal',
                'display_order'        => 1,
            ]
        ];
        $totalBanners = count($banners);
        ?>
        <div id="heroBannerCarousel" class="w-full relative rounded-2xl overflow-hidden bg-slate-900 min-h-[380px] sm:min-h-[420px] lg:min-h-[450px] flex items-center shadow-lg group">
            <?php foreach ($banners as $idx => $b): 
                $imgSrc = Response::formatImageUrl($b['image']);
                $bannerType = $b['banner_type'] ?? 'WITH_TEXT';
                $isImageOnly = ($bannerType === 'IMAGE_ONLY');
                $rawCta = trim((string)($b['cta_url'] ?? ''));
                $ctaUrl = !empty($rawCta) ? (str_starts_with($rawCta, 'http') ? $rawCta : $baseUrl . '/' . ltrim($rawCta, '/')) : '';
                $secUrl = $b['secondary_cta_url'] ?? 'rfq_modal';
                $isActive = ($idx === 0);
            ?>
            <div class="hero-slide absolute inset-0 w-full h-full flex items-center transition-opacity duration-700 ease-in-out <?= $isActive ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none' ?>" data-slide-index="<?= $idx ?>">
                <?php if ($isImageOnly): ?>
                    <?php if (!empty($ctaUrl)): ?>
                    <a href="<?= htmlspecialchars($ctaUrl) ?>" class="absolute inset-0 w-full h-full block z-10" aria-label="<?= htmlspecialchars($b['title'] ?: 'Hero Banner') ?>">
                        <img alt="<?= htmlspecialchars($b['title'] ?: 'Hero Banner') ?>" class="w-full h-full object-cover" src="<?= htmlspecialchars($imgSrc) ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=1600&q=80';">
                    </a>
                    <?php else: ?>
                    <img alt="<?= htmlspecialchars($b['title'] ?: 'Hero Banner') ?>" class="absolute inset-0 w-full h-full object-cover" src="<?= htmlspecialchars($imgSrc) ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=1600&q=80';">
                    <?php endif; ?>

                    <!-- Bottom Indicators for Image Only Banner -->
                    <div class="absolute bottom-4 left-6 sm:left-10 z-20 pointer-events-auto">
                        <div class="inline-flex items-center gap-2 bg-black/50 backdrop-blur-md px-3 py-1.5 rounded-full text-xs text-white border border-white/10 shadow-sm">
                            <?php if ($totalBanners > 1): ?>
                                <?php for ($d = 0; $d < $totalBanners; $d++): ?>
                                    <button type="button" class="hero-dot transition-all <?= ($d === $idx) ? 'w-6 h-1.5 rounded-full bg-blue-500' : 'w-2.5 h-1.5 rounded-full bg-white/40 hover:bg-white/70' ?>" data-dot-index="<?= $d ?>" aria-label="Slide <?= $d + 1 ?>"></button>
                                <?php endfor; ?>
                                <span class="ml-1.5 font-mono font-semibold"><?= sprintf('%02d / %02d', $idx + 1, $totalBanners) ?></span>
                            <?php else: ?>
                                <span class="w-6 h-1.5 rounded-full bg-blue-500"></span>
                                <span class="ml-1.5 font-mono font-semibold">01 / 01</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <img alt="<?= htmlspecialchars($b['title']) ?>" class="absolute inset-0 w-full h-full object-cover opacity-35" src="<?= htmlspecialchars($imgSrc) ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=1600&q=80';">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-950/95 via-blue-950/70 to-transparent"></div>

                    <div class="relative z-10 max-w-2xl p-6 sm:p-10 lg:p-12 text-white flex flex-col justify-between h-full w-full">
                        <div class="space-y-4">
                            <?php if (!empty($b['badge_text'])): ?>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 backdrop-blur-md text-teal-300 text-xs font-bold uppercase tracking-wider border border-teal-500/30">
                                <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                                <span><?= htmlspecialchars($b['badge_text']) ?></span>
                            </div>
                            <?php endif; ?>

                            <h1 class="font-headline text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight tracking-tight text-white">
                                <?= htmlspecialchars($b['title']) ?>
                            </h1>

                            <?php if (!empty($b['subtitle'])): ?>
                            <p class="text-sm sm:text-base text-gray-200 leading-relaxed max-w-xl">
                                <?= htmlspecialchars($b['subtitle']) ?>
                            </p>
                            <?php endif; ?>

                            <div class="flex flex-wrap items-center gap-3 pt-3">
                                <?php if (!empty($b['cta_text'])): ?>
                                <a href="<?= htmlspecialchars(!empty($ctaUrl) ? $ctaUrl : $baseUrl . '/products') ?>" class="px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-headline font-bold text-sm shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
                                    <span><?= htmlspecialchars($b['cta_text']) ?></span>
                                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                </a>
                                <?php endif; ?>

                                <?php if (!empty($b['secondary_cta_text'])): ?>
                                    <?php if ($secUrl === 'rfq_modal' || empty($secUrl)): ?>
                                    <button type="button" onclick="openRfqModal()" class="px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 backdrop-blur-md font-headline font-bold text-sm transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-[18px]">description</span>
                                        <span><?= htmlspecialchars($b['secondary_cta_text']) ?></span>
                                    </button>
                                    <?php else: ?>
                                    <a href="<?= htmlspecialchars(str_starts_with($secUrl, 'http') ? $secUrl : $baseUrl . '/' . ltrim($secUrl, '/')) ?>" class="px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 backdrop-blur-md font-headline font-bold text-sm transition-all flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">description</span>
                                        <span><?= htmlspecialchars($b['secondary_cta_text']) ?></span>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Bottom Indicators -->
                        <div class="mt-8 pt-4 border-t border-white/10 flex items-center justify-start text-xs text-gray-300">
                            <div class="flex items-center gap-2">
                                <?php if ($totalBanners > 1): ?>
                                    <?php for ($d = 0; $d < $totalBanners; $d++): ?>
                                        <button type="button" class="hero-dot transition-all <?= ($d === $idx) ? 'w-8 h-1.5 rounded-full bg-blue-500' : 'w-3 h-1.5 rounded-full bg-white/30 hover:bg-white/50' ?>" data-dot-index="<?= $d ?>" aria-label="Slide <?= $d + 1 ?>"></button>
                                    <?php endfor; ?>
                                    <span class="ml-2 font-mono font-semibold text-white"><?= sprintf('%02d / %02d', $idx + 1, $totalBanners) ?></span>
                                <?php else: ?>
                                    <span class="w-8 h-1.5 rounded-full bg-blue-500"></span>
                                    <span class="ml-2 font-mono font-semibold text-white">01 / 01</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php if ($totalBanners > 1): ?>
            <!-- Prev / Next Controls on hover -->
            <button type="button" id="heroPrevBtn" class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/30 hover:bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm cursor-pointer border border-white/10" aria-label="Previous Slide">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>
            <button type="button" id="heroNextBtn" class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/30 hover:bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm cursor-pointer border border-white/10" aria-label="Next Slide">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>
            <?php endif; ?>
        </div>

        <?php if ($totalBanners > 1): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const carousel = document.getElementById('heroBannerCarousel');
            if (!carousel) return;
            const slides = carousel.querySelectorAll('.hero-slide');
            const total = slides.length;
            if (total <= 1) return;

            let current = 0;
            let autoTimer = null;

            function goToSlide(index) {
                slides[current].classList.remove('opacity-100', 'z-10');
                slides[current].classList.add('opacity-0', 'z-0', 'pointer-events-none');

                current = (index + total) % total;

                slides[current].classList.remove('opacity-0', 'z-0', 'pointer-events-none');
                slides[current].classList.add('opacity-100', 'z-10');
            }

            function nextSlide() { goToSlide(current + 1); }
            function prevSlide() { goToSlide(current - 1); }

            function startTimer() {
                stopTimer();
                autoTimer = setInterval(nextSlide, 6000);
            }
            function stopTimer() {
                if (autoTimer) clearInterval(autoTimer);
            }

            carousel.addEventListener('mouseenter', stopTimer);
            carousel.addEventListener('mouseleave', startTimer);

            const prevBtn = document.getElementById('heroPrevBtn');
            const nextBtn = document.getElementById('heroNextBtn');
            if (prevBtn) prevBtn.addEventListener('click', function(e) { e.preventDefault(); prevSlide(); });
            if (nextBtn) nextBtn.addEventListener('click', function(e) { e.preventDefault(); nextSlide(); });

            carousel.querySelectorAll('.hero-dot').forEach(dot => {
                dot.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetIdx = parseInt(this.getAttribute('data-dot-index'));
                    if (!isNaN(targetIdx)) goToSlide(targetIdx);
                });
            });

            startTimer();
        });
        </script>
        <?php endif; ?>
    </div>
</section>

<!-- 2. POPULAR B2B SOURCING CATEGORIES -->
<?php if (!empty($homepageCategories)): ?>
<?php
    $displayedIds = array_map(fn($c) => (int)$c['id'], $homepageCategories);
    $remainingCategories = !empty($categories) ? array_values(array_filter($categories, fn($c) => !in_array((int)$c['id'], $displayedIds, true))) : [];
    $totalCategoriesCount = count($homepageCategories) + count($remainingCategories);
?>
<section id="popularCategoriesSection" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 w-full">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-blue-800 mb-1">Global Industrial Sectors</div>
            <h2 class="font-headline text-2xl sm:text-3xl text-on-surface font-extrabold">Popular Sports Sourcing Categories</h2>
        </div>
        <?php if (!empty($remainingCategories)): ?>
        <button type="button" id="toggleAllCategoriesBtn" class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100/80 px-4 py-2 rounded-xl transition-all cursor-pointer border border-blue-200/80 shadow-2xs">
            <span id="toggleCategoriesLabel">View All Categories (<?= $totalCategoriesCount ?>)</span>
            <span id="toggleCategoriesIcon" class="material-symbols-outlined text-[18px] transition-transform duration-300">expand_more</span>
        </button>
        <?php endif; ?>
    </div>

    <!-- Category Cards Grid -->
    <div id="categoryCardsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Initially visible top categories -->
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

        <!-- Extra Categories revealed on click -->
        <?php foreach ($remainingCategories as $cat): ?>
            <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>" class="extra-category-card hidden group p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-blue-300 transition-all flex items-center gap-3.5 relative overflow-hidden">
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

<?php if (!empty($remainingCategories)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleAllCategoriesBtn');
    if (!toggleBtn) return;

    const extraCards = document.querySelectorAll('.extra-category-card');
    const label = document.getElementById('toggleCategoriesLabel');
    const icon = document.getElementById('toggleCategoriesIcon');
    const totalCount = <?= (int)$totalCategoriesCount ?>;
    let isExpanded = false;

    toggleBtn.addEventListener('click', function () {
        isExpanded = !isExpanded;
        extraCards.forEach(card => {
            if (isExpanded) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });

        if (isExpanded) {
            label.textContent = 'Show Less';
            icon.textContent = 'expand_less';
            toggleBtn.classList.add('bg-blue-100', 'border-blue-400');
        } else {
            label.textContent = `View All Categories (${totalCount})`;
            icon.textContent = 'expand_more';
            toggleBtn.classList.remove('bg-blue-100', 'border-blue-400');
            const section = document.getElementById('popularCategoriesSection');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });

    // If URL has #all-categories or ?all_categories=1, auto-expand
    if (window.location.hash === '#all-categories' || window.location.search.includes('all_categories=1')) {
        toggleBtn.click();
    }
});
</script>
<?php endif; ?>
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
            <?php endif; ?>
        </div>

        <!-- View All Products CTA Button at bottom of section -->
        <div class="mt-12 text-center">
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-headline font-bold text-sm shadow-md transition-all">
                <span>View All Products</span>
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

<!-- 4. THREE-STEP B2B SOURCING WORKFLOW -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h2 class="font-headline text-2xl sm:text-3xl font-extrabold text-on-surface">Direct Factory Sourcing Made Seamless</h2>
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
