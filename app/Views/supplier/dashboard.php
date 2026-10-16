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
        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            <span>Add New Product</span>
        </a>
    </div>

    <!-- Metrics Cards (Database-backed counts) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Products</span>
                <span class="material-symbols-outlined text-[20px] text-blue-900">inventory_2</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-slate-900"><?= (int)$counts['total'] ?></div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-teal-700 uppercase tracking-wider">Published</span>
                <span class="material-symbols-outlined text-[20px] text-teal-600">storefront</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-teal-700"><?= (int)$counts['published'] ?></div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Pending Approval</span>
                <span class="material-symbols-outlined text-[20px] text-amber-600">hourglass_top</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-amber-600"><?= (int)$counts['pending'] ?></div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-red-700 uppercase tracking-wider">Rejected</span>
                <span class="material-symbols-outlined text-[20px] text-red-600">cancel</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-red-600"><?= (int)$counts['rejected'] ?></div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">Customer Enquiries</span>
                <span class="material-symbols-outlined text-[20px] text-blue-800">mark_email_unread</span>
            </div>
            <div class="font-headline font-extrabold text-2xl text-blue-800"><?= (int)$counts['enquiries'] ?></div>
        </div>
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
