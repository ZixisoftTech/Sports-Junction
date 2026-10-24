<?php
use App\Core\Response;
$baseUrl = Response::baseUrl();
?>
<section class="bg-surface-container-lowest/80 border-b border-surface-container py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-4" aria-label="Breadcrumb">
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="hover:text-blue-900 transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">home</span>
                <span>Home</span>
            </a>
            <span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span>
            <span class="text-slate-800 font-semibold">Categories</span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-800 text-xs font-bold uppercase tracking-wider mb-2 border border-blue-200/60">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span>Global Industrial Directory</span>
                </div>
                <h1 class="font-headline text-3xl sm:text-4xl text-slate-900 font-extrabold tracking-tight">
                    All Sports Sourcing Categories
                </h1>
                <p class="text-sm sm:text-base text-slate-600 mt-2 max-w-2xl leading-relaxed">
                    Explore India's verified sports manufacturing clusters in Meerut and Jalandhar across <?= count($categories ?? []) ?> industrial sectors with direct factory pricing and guaranteed MOQs.
                </p>
            </div>

            <!-- Instant Search Input -->
            <div class="w-full md:w-80 shrink-0">
                <div class="relative flex items-center bg-white rounded-2xl border border-slate-300 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-100 transition-all shadow-xs">
                    <span class="material-symbols-outlined text-slate-400 pl-3.5 text-[20px]">search</span>
                    <input type="text" id="categorySearchInput" placeholder="Filter categories or sports..." class="w-full bg-transparent pl-2.5 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Directory Grid -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div id="noCategoriesFound" class="hidden text-center py-16 bg-white rounded-2xl border border-slate-200">
        <span class="material-symbols-outlined text-4xl text-slate-400 mb-2">category</span>
        <h3 class="text-base font-bold text-slate-800">No categories found</h3>
        <p class="text-xs text-slate-500 mt-1">Try searching with a different keyword like "cricket", "fitness", or "balls".</p>
    </div>

    <div id="allCategoriesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($categories as $cat): ?>
            <?php 
                $subcats = $cat['subcategories'] ?? []; 
                $catIcon = trim($cat['icon'] ?? 'sports_tennis');
                $isImg = (str_starts_with($catIcon, '/') || str_starts_with($catIcon, 'http') || str_contains($catIcon, '.'));
                $searchKeywords = strtolower($cat['name'] . ' ' . implode(' ', array_column($subcats, 'name')));
            ?>
            <div class="category-directory-card group bg-white rounded-2xl border border-slate-200 hover:border-blue-400 hover:shadow-lg transition-all flex flex-col justify-between p-5 relative overflow-hidden" data-keywords="<?= htmlspecialchars($searchKeywords) ?>">
                <div>
                    <!-- Header with Icon & Product Count -->
                    <div class="flex items-start gap-4 mb-3">
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 group-hover:bg-blue-600 flex items-center justify-center text-blue-900 group-hover:text-white transition-colors shrink-0 p-3 shadow-2xs">
                            <?php if ($isImg): ?>
                                <img src="<?= htmlspecialchars(Response::formatImageUrl($catIcon)) ?>" class="w-full h-full object-contain" alt="<?= htmlspecialchars($cat['name']) ?>" onerror="this.onerror=null;this.parentElement.innerHTML='<span class=\'material-symbols-outlined text-[28px]\'>sports_tennis</span>';">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-[28px]"><?= htmlspecialchars($catIcon) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-headline font-bold text-base text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-tight">
                                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            </h2>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full border border-teal-200/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                    <span><?= (int) ($cat['product_count'] ?? 0) ?> active items</span>
                                </span>
                                <?php if (!empty($subcats)): ?>
                                    <span class="text-xs text-slate-400 font-medium">&bull; <?= count($subcats) ?> subcategories</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Subcategories tags list -->
                    <?php if (!empty($subcats)): ?>
                        <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                            <?php 
                            $topSubs = array_slice($subcats, 0, 5);
                            $moreCount = count($subcats) - count($topSubs);
                            ?>
                            <?php foreach ($topSubs as $sub): ?>
                                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>/<?= htmlspecialchars($sub['slug']) ?>" class="inline-flex items-center text-[11px] font-medium text-slate-600 bg-slate-50 hover:bg-blue-50 hover:text-blue-800 px-2.5 py-1 rounded-lg border border-slate-200/70 transition-colors">
                                    <span><?= htmlspecialchars($sub['name']) ?></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if ($moreCount > 0): ?>
                                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>" class="inline-flex items-center text-[11px] font-bold text-blue-700 hover:text-blue-900 px-2 py-1 transition-colors">
                                    +<?= $moreCount ?> more
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer Action -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($cat['slug']) ?>" class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:text-blue-900 transition-colors">
                        <span>Browse Verified Factory Catalog</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('categorySearchInput');
    const cards = document.querySelectorAll('.category-directory-card');
    const noResults = document.getElementById('noCategoriesFound');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            cards.forEach(card => {
                const keywords = card.getAttribute('data-keywords') || '';
                if (query === '' || keywords.includes(query)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        });
    }
});
</script>
