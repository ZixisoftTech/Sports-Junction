<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="inline-flex items-center gap-1 text-xs font-bold text-blue-900 hover:underline mb-2">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Buyer Dashboard</span>
        </a>
        <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Saved Favourite Products</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Shortlisted equipment and verified manufacturers for your upcoming sourcing orders.</p>
    </div>

    <?php if (empty($favourites)): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400">
            <span class="material-symbols-outlined text-[42px] text-rose-300 block mb-2">favorite</span>
            <p class="text-sm font-medium text-slate-600 mb-4">You haven't saved any favourite products yet.</p>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-900 text-white font-bold text-xs">
                <span>Browse Sports Catalog</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($favourites as $fav): ?>
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col justify-between group hover:border-blue-700 transition-all">
                    <div>
                        <div class="relative rounded-xl overflow-hidden mb-3.5 aspect-square bg-slate-50 border border-slate-100">
                            <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($fav['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <button type="button" onclick="removeFavouriteItem(<?= (int)$fav['id'] ?>)" class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-white/90 text-rose-600 flex items-center justify-center shadow-sm hover:bg-rose-600 hover:text-white transition-colors" title="Remove from Favourites">
                                <span class="material-symbols-outlined text-[18px] fill-1">favorite</span>
                            </button>
                        </div>
                        <div class="text-[11px] font-bold text-blue-900 uppercase tracking-wider mb-1"><?= htmlspecialchars($fav['category_name'] ?? 'Sports Gear') ?></div>
                        <h3 class="font-bold text-sm text-slate-900 line-clamp-2 mb-1"><?= htmlspecialchars($fav['name']) ?></h3>
                        <div class="text-xs text-slate-500 font-semibold mb-2"><?= htmlspecialchars($fav['supplier_name'] ?? 'Verified Factory') ?></div>
                        <div class="font-bold text-blue-900 text-base">
                            $<?= number_format((float)$fav['min_price'], 2) ?>
                            <?php if ((float)$fav['max_price'] > (float)$fav['min_price']): ?>
                                - $<?= number_format((float)$fav['max_price'], 2) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">MOQ: <strong><?= (int)$fav['moq_quantity'] ?> <?= htmlspecialchars($fav['moq_unit_name'] ?? 'units') ?></strong></div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($fav['slug']) ?>" class="flex-1 text-center py-2 px-3 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs transition-colors">
                            View Product
                        </a>
                        <button type="button" onclick="removeFavouriteItem(<?= (int)$fav['id'] ?>)" class="p-2 rounded-xl border border-slate-200 hover:bg-rose-50 hover:border-rose-200 text-slate-500 hover:text-rose-600 transition-colors" title="Remove">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFavouriteItem(id) {
    if (!confirm('Remove this product from your saved favourites?')) return;
    fetch('<?= htmlspecialchars($baseUrl) ?>/buyer/favourites/' + id, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= htmlspecialchars(CSRF::token()) ?>'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Error removing favourite');
        }
    });
}
</script>
