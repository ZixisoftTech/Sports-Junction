<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="inline-flex items-center gap-1 text-xs font-bold text-blue-900 hover:underline mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Back to Buyer Dashboard</span>
            </a>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Recently Viewed Products</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Your last 20 unique viewed sports equipment items, sorted by most recently viewed.</p>
        </div>

        <?php if (!empty($products)): ?>
            <button type="button" onclick="clearHistory()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition-colors self-start sm:self-auto">
                <span class="material-symbols-outlined text-[16px]">delete_sweep</span>
                <span>Clear History</span>
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($products)): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400">
            <span class="material-symbols-outlined text-[42px] text-slate-300 block mb-2">history</span>
            <p class="text-sm font-medium text-slate-600 mb-4">Your recently viewed product history is empty.</p>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-900 text-white font-bold text-xs">
                <span>Browse Sports Catalog</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($products as $prod): ?>
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col justify-between group hover:border-blue-700 transition-all">
                    <div>
                        <div class="relative rounded-xl overflow-hidden mb-3.5 aspect-square bg-slate-50 border border-slate-100">
                            <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($prod['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <div class="text-[10px] font-semibold text-slate-400 mb-1">Viewed <?= date('d M, h:i A', strtotime($prod['viewed_at'])) ?></div>
                        <h3 class="font-bold text-sm text-slate-900 line-clamp-2 mb-1"><?= htmlspecialchars($prod['name']) ?></h3>
                        <div class="text-xs text-slate-500 font-semibold mb-2"><?= htmlspecialchars($prod['supplier_name'] ?? 'Verified Factory') ?></div>
                        <div class="font-bold text-blue-900 text-base">
                            <?= htmlspecialchars($prod['price_unit_symbol'] ?? '₹') ?><?= number_format((float)$prod['min_price'], 2) ?>
                            <?php if ((float)$prod['max_price'] > (float)$prod['min_price']): ?>
                                - <?= htmlspecialchars($prod['price_unit_symbol'] ?? '₹') ?><?= number_format((float)$prod['max_price'], 2) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">MOQ: <strong><?= (int)$prod['moq_quantity'] ?> <?= htmlspecialchars($prod['moq_unit_name'] ?? 'units') ?></strong></div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($prod['slug']) ?>" class="block text-center py-2 px-3 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs transition-colors">
                            View Product
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function clearHistory() {
    if (!confirm('Are you sure you want to clear your entire recently viewed product history?')) return;
    fetch('<?= htmlspecialchars($baseUrl) ?>/buyer/recently-viewed/clear', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= htmlspecialchars(CSRF::token()) ?>'
        },
        body: JSON.stringify({ _csrf: '<?= htmlspecialchars(CSRF::token()) ?>' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Error clearing history');
        }
    });
}
</script>
