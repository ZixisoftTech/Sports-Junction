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
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">My Product Enquiries</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Track your Requests for Quotation (RFQs), view manufacturer responses, and message factories.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Enquiry ID</th>
                        <th class="py-3.5 px-4">Enquiry Source</th>
                        <th class="py-3.5 px-4">Product / Requirement</th>
                        <th class="py-3.5 px-4">Supplier Name</th>
                        <th class="py-3.5 px-4">Quantity</th>
                        <th class="py-3.5 px-4">Submission Date</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($enquiries)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-[36px] text-slate-300 block mb-2">request_quote</span>
                                No enquiries submitted yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enquiries as $inq): ?>
                            <?php 
                                $st = $inq['status'] ?? 'SUBMITTED';
                                $badgeClass = match($st) {
                                    'RESPONDED', 'IN_DISCUSSION', 'RESOLVED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'VIEWED'    => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                    'CLOSED'    => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default     => 'bg-amber-50 text-amber-800 border-amber-200'
                                };
                                $src = $inq['enquiry_source'] ?? 'PRODUCT_PAGE';
                                $srcLabel = match($src) {
                                    'CONTACT_US' => 'Contact Us',
                                    'GENERAL'    => 'General Requirement',
                                    default      => 'Product Page',
                                };
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-4 font-bold text-slate-900 font-mono">#ENQ-<?= str_pad((string)(int)$inq['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        <?= htmlspecialchars($srcLabel) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-bold text-slate-900 flex items-center gap-3">
                                    <?php if (!empty($inq['product_id'])): ?>
                                        <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($inq['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shrink-0">
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($inq['product_name'] ?: ($inq['subject'] ?: 'General Requirement')) ?></div>
                                        <?php if (!empty($inq['product_slug'])): ?>
                                            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($inq['product_slug']) ?>" target="_blank" class="text-[11px] text-blue-900 hover:underline">View Product &rarr;</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-600 font-semibold"><?= htmlspecialchars($inq['supplier_name'] ?: 'Unassigned') ?></td>
                                <td class="py-4 px-4 text-slate-900 font-bold"><?= $src === 'CONTACT_US' ? '-' : ((int)$inq['target_quantity'] . ' ' . htmlspecialchars($inq['moq_unit_name'] ?? 'units')) ?></td>
                                <td class="py-4 px-4 text-slate-500 text-[11px]"><?= date('d M Y, h:i A', strtotime($inq['created_at'])) ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $badgeClass ?>">
                                        <?= htmlspecialchars($st) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right whitespace-nowrap">
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/enquiries/<?= (int)$inq['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs transition-colors">
                                        <span class="material-symbols-outlined text-[14px]">chat</span>
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
</div>
