<?php
declare(strict_types=1);

use App\Core\Response;
$baseUrl = Response::baseUrl();
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">My Equipment Catalog</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Review live products, view Super Admin approval states, and update specifications.</p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            <span>Add New Product</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Product Details</th>
                        <th class="py-3.5 px-4">Category / Subcategory</th>
                        <th class="py-3.5 px-4">Price Matrix</th>
                        <th class="py-3.5 px-4">MOQ</th>
                        <th class="py-3.5 px-4">Approval Status</th>
                        <th class="py-3.5 px-4">Publication Status</th>
                        <th class="py-3.5 px-4">Updated Date</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-[36px] text-slate-300 block mb-2">inventory_2</span>
                                No products in your catalog yet. Click "+ Add New Product" to submit sports goods for approval.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <?php 
                                $status = $p['approval_status'] ?? 'DRAFT'; 
                                $pubStatus = $p['status'] ?? 'DRAFT';
                                $badgeClass = match($status) {
                                    'APPROVED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'PENDING_APPROVAL' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'REJECTED' => 'bg-red-50 text-red-800 border-red-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-4 font-bold text-slate-900 flex items-center gap-3">
                                    <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($p['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shrink-0">
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($p['title']) ?></div>
                                        <div class="text-[11px] text-slate-400 font-normal">SKU: <?= htmlspecialchars($p['sku'] ?? 'SJ-PROD-' . $p['id']) ?></div>
                                        <?php if (!empty($p['pending_changes'])): ?>
                                            <span class="inline-flex items-center gap-1 mt-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                                <span class="material-symbols-outlined text-[12px]">pending_actions</span>
                                                <span>Modifications Pending Review</span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['rejection_reason'])): ?>
                                            <div class="text-[11px] text-red-700 bg-red-50 p-1.5 rounded border border-red-200 mt-1 max-w-xs font-normal">
                                                <strong>Feedback:</strong> <?= htmlspecialchars($p['rejection_reason']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-600">
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($p['category_name'] ?? '—') ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($p['subcategory_name'] ?? '—') ?></div>
                                </td>
                                <td class="py-4 px-4 text-blue-900 font-bold">
                                    $<?= number_format((float)$p['min_price'], 2) ?>
                                    <?php if ((float)$p['max_price'] > (float)$p['min_price']): ?>
                                        - $<?= number_format((float)$p['max_price'], 2) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-slate-700">
                                    <?= (int)$p['moq_quantity'] ?> <?= htmlspecialchars($p['moq_unit_name'] ?? 'units') ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold border <?= $badgeClass ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= ($pubStatus === 'PUBLISHED' || $pubStatus === 'ACTIVE') ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' ?>">
                                        <?= htmlspecialchars($pubStatus) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-500 text-[11px] whitespace-nowrap">
                                    <?= date('d M Y, h:i A', strtotime($p['updated_at'] ?? $p['created_at'])) ?>
                                </td>
                                <td class="py-4 px-4 text-right space-x-1 whitespace-nowrap">
                                    <?php if ($status === 'APPROVED' && ($pubStatus === 'PUBLISHED' || $pubStatus === 'ACTIVE')): ?>
                                        <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors" title="View Public Page">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            <span>View</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/edit/<?= (int)$p['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors" title="Edit Product">
                                        <span class="material-symbols-outlined text-[14px]">edit</span>
                                        <span>Edit</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
