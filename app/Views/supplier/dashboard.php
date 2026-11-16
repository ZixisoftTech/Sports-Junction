<?php
declare(strict_types=1);

use App\Core\Response;
$baseUrl = Response::baseUrl();
?>

<div>
    <!-- Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Manufacturer Operations Desk</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage wholesale sports catalog, view audit approvals, and submit equipment specs.</p>
        </div>
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <button type="button" id="btnOpenBulkModal" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-blue-900/20 text-blue-900 bg-blue-50 hover:bg-blue-100 font-bold text-xs shadow-xs transition-all cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">upload_file</span>
                <span>Bulk Upload Products</span>
            </button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Add New Product</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards (Database-backed counts & Clickable Navigation) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-blue-900 hover:shadow-md transition-all group block cursor-pointer" title="View all products">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider group-hover:text-blue-900 transition-colors">Total Products</span>
                <span class="material-symbols-outlined text-[20px] text-blue-900 group-hover:scale-110 transition-transform">inventory_2</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-slate-900"><?= (int)$counts['total'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1 font-semibold group-hover:text-blue-900 transition-colors">Manage catalog &rarr;</div>
        </a>

        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products?status=APPROVED" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-teal-700 hover:shadow-md transition-all group block cursor-pointer" title="View published products">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-teal-700 uppercase tracking-wider group-hover:text-teal-800 transition-colors">Published</span>
                <span class="material-symbols-outlined text-[20px] text-teal-600 group-hover:scale-110 transition-transform">storefront</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-teal-700"><?= (int)$counts['published'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1 font-semibold group-hover:text-teal-700 transition-colors">Live in store &rarr;</div>
        </a>

        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products?status=PENDING_APPROVAL" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-amber-600 hover:shadow-md transition-all group block cursor-pointer" title="View pending review products">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-amber-700 uppercase tracking-wider group-hover:text-amber-800 transition-colors">Pending Approval</span>
                <span class="material-symbols-outlined text-[20px] text-amber-600 group-hover:scale-110 transition-transform">hourglass_top</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-amber-600"><?= (int)$counts['pending'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1 font-semibold group-hover:text-amber-700 transition-colors">Under review &rarr;</div>
        </a>

        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products?status=REJECTED" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-red-600 hover:shadow-md transition-all group block cursor-pointer" title="View rejected products">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-red-700 uppercase tracking-wider group-hover:text-red-800 transition-colors">Rejected</span>
                <span class="material-symbols-outlined text-[20px] text-red-600 group-hover:scale-110 transition-transform">cancel</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-red-600"><?= (int)$counts['rejected'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1 font-semibold group-hover:text-red-700 transition-colors">Needs correction &rarr;</div>
        </a>

        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/enquiries" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-blue-800 hover:shadow-md transition-all group block cursor-pointer" title="View customer enquiries">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider group-hover:text-blue-900 transition-colors">Customer Enquiries</span>
                <span class="material-symbols-outlined text-[20px] text-blue-800 group-hover:scale-110 transition-transform">mark_email_unread</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-blue-800"><?= (int)$counts['enquiries'] ?></div>
            <div class="text-[11px] text-slate-400 mt-1 font-semibold group-hover:text-blue-800 transition-colors">View inquiries &rarr;</div>
        </a>
    </div>

    <!-- Recent Products Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-headline font-bold text-base text-slate-900">Recent Equipment Listings</h2>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="text-xs font-bold text-blue-900 hover:underline">View All &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Product Name</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Price Tier</th>
                        <th class="py-3 px-4">MOQ</th>
                        <th class="py-3 px-4">Approval Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($recent)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">No products added yet. Click "+ Add New Product" to submit equipment for approval.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent as $p): ?>
                            <?php 
                                $status = $p['approval_status'] ?? 'DRAFT'; 
                                $badgeClass = match($status) {
                                    'APPROVED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'PENDING_APPROVAL' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'REJECTED' => 'bg-red-50 text-red-800 border-red-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900 flex items-center gap-3">
                                    <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($p['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-10 h-10 rounded-lg object-cover border border-slate-200">
                                    <div>
                                        <div><?= htmlspecialchars($p['title']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-normal">SKU: <?= htmlspecialchars($p['sku'] ?? 'N/A') ?></div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                                <td class="py-3 px-4 text-blue-900 font-bold">
                                    $<?= number_format((float)$p['min_price'], 2) ?>
                                </td>
                                <td class="py-3 px-4 text-slate-700"><?= (int)$p['moq_quantity'] ?> <?= htmlspecialchars($p['moq_unit_name'] ?? 'units') ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badgeClass ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/edit/<?= (int)$p['id'] ?>" class="text-blue-900 hover:underline font-bold">Edit / Resubmit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Product Upload Modal -->
<?php require __DIR__ . '/bulk_upload_modal.php'; ?>
