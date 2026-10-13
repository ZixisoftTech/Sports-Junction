<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-900 text-xs font-bold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <span>Verified Buyer Portal</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight">Welcome, <?= htmlspecialchars($buyer['name']) ?></h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1"><?= htmlspecialchars($buyer['company_name'] ?? 'Direct Sourcing Account') ?> • <?= htmlspecialchars($buyer['email']) ?></p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all">
                <span class="material-symbols-outlined text-[16px]">explore</span>
                <span>Explore Catalog</span>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/profile" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors shadow-xs">
                <span class="material-symbols-outlined text-[16px]">account_circle</span>
                <span>My Profile</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/enquiries" class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-blue-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Enquiries</span>
                <span class="p-2.5 rounded-xl bg-blue-50 text-blue-900 group-hover:bg-blue-900 group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[20px]">request_quote</span>
                </span>
            </div>
            <div class="font-headline font-extrabold text-3xl text-slate-900"><?= (int)$counts['enquiries'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Quotations &amp; RFQs with suppliers &rarr;</div>
        </a>

        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/favourites" class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-blue-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Saved Favourites</span>
                <span class="p-2.5 rounded-xl bg-rose-50 text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[20px] fill-1">favorite</span>
                </span>
            </div>
            <div class="font-headline font-extrabold text-3xl text-slate-900"><?= (int)$counts['favourites'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Shortlisted factory equipment &rarr;</div>
        </a>

        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/recently-viewed" class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-blue-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Browsing History</span>
                <span class="p-2.5 rounded-xl bg-teal-50 text-teal-700 group-hover:bg-teal-700 group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[20px]">history</span>
                </span>
            </div>
            <div class="font-headline font-extrabold text-3xl text-slate-900"><?= (int)$counts['recent'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Recently viewed equipment &rarr;</div>
        </a>
    </div>

    <!-- Active Inquiries Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-900 text-[20px]">chat</span>
                <h2 class="font-headline font-bold text-base text-slate-900">Recent Enquiries &amp; Quotations</h2>
            </div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/enquiries" class="text-xs font-bold text-blue-900 hover:underline">View All (<?= (int)$counts['enquiries'] ?>) &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Enquiry ID</th>
                        <th class="py-3 px-4">Product</th>
                        <th class="py-3 px-4">Manufacturer</th>
                        <th class="py-3 px-4">Requested Qty</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($enquiries)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                You have not submitted any product inquiries yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enquiries as $inq): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">#ENQ-<?= (int)$inq['id'] ?></td>
                                <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                    <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($inq['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-9 h-9 rounded-lg object-cover border border-slate-200 shrink-0">
                                    <span><?= htmlspecialchars($inq['product_name'] ?? 'Product Inquiry') ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-semibold"><?= htmlspecialchars($inq['supplier_name'] ?? 'Sports Junction Hub') ?></td>
                                <td class="py-3.5 px-4 text-slate-900 font-bold"><?= (int)$inq['target_quantity'] ?> units</td>
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]"><?= date('d M Y', strtotime($inq['created_at'])) ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= ($inq['status'] === 'RESPONDED') ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' ?>">
                                        <?= htmlspecialchars($inq['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/enquiries/<?= (int)$inq['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs transition-colors">
                                        <span>View Details</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Saved Favourites Grid -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-600 text-[20px] fill-1">favorite</span>
                <h2 class="font-headline font-bold text-base text-slate-900">Saved Favourite Products</h2>
            </div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/favourites" class="text-xs font-bold text-blue-900 hover:underline">View All (<?= (int)$counts['favourites'] ?>) &rarr;</a>
        </div>

        <?php if (empty($favourites)): ?>
            <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center text-slate-400 text-xs">
                No favourite products saved yet. Browse our verified sports catalog and click the heart icon on any product to save it here.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($favourites as $fav): ?>
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-3.5 flex flex-col justify-between group hover:border-blue-700 transition-all">
                        <div>
                            <div class="relative rounded-xl overflow-hidden mb-3 aspect-square bg-slate-50 border border-slate-100">
                                <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($fav['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            <h3 class="font-bold text-xs text-slate-900 line-clamp-2 mb-1"><?= htmlspecialchars($fav['name']) ?></h3>
                            <div class="text-[11px] text-slate-500 font-semibold mb-2"><?= htmlspecialchars($fav['supplier_name'] ?? 'Verified Factory') ?></div>
                            <div class="font-bold text-blue-900 text-sm">
                                <?= htmlspecialchars($fav['price_unit_symbol'] ?? '₹') ?><?= number_format((float)$fav['min_price'], 2) ?>
                            </div>
                            <div class="text-[10px] text-slate-400">MOQ: <?= (int)$fav['moq_quantity'] ?> <?= htmlspecialchars($fav['moq_unit_name'] ?? 'units') ?></div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($fav['slug']) ?>" class="text-xs font-bold text-blue-900 hover:underline">View Product &rarr;</a>
                            <button type="button" onclick="removeFav(<?= (int)$fav['id'] ?>, this)" class="text-slate-400 hover:text-rose-600 transition-colors" title="Remove Favourite">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function removeFav(id, btn) {
    if (!confirm('Remove this product from your favourites?')) return;
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
            alert(res.message || 'Error removing item');
        }
    });
}
</script>
