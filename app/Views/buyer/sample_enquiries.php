<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="inline-flex items-center gap-1 text-xs font-bold text-teal-800 hover:underline mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Back to Buyer Dashboard</span>
            </a>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 border border-teal-200 text-teal-800 uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[14px] text-teal-600">science</span>
                    <span>5-7 Days Prototype Delivery</span>
                </span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">My Prototype Sample Orders</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Track physical sample production, evaluation specifications, and door-to-door courier dispatch status.</p>
        </div>

        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-bold text-xs shadow-md transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
            <span>Request More Samples</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Sample ID</th>
                        <th class="py-3.5 px-4">Product Prototype</th>
                        <th class="py-3.5 px-4">Verified Factory</th>
                        <th class="py-3.5 px-4">Sample Qty</th>
                        <th class="py-3.5 px-4">Courier Destination</th>
                        <th class="py-3.5 px-4">Order Date</th>
                        <th class="py-3.5 px-4">Dispatch Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($sampleEnquiries)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-[36px] text-slate-300 block mb-2">science</span>
                                You have not placed any prototype sample orders yet. Browse our verified products and click "Order Prototype Sample" on any equipment page.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sampleEnquiries as $inq): ?>
                            <?php 
                                $st = strtoupper($inq['status'] ?? 'PENDING');
                                $badgeClass = match($st) {
                                    'IN_PRODUCTION'   => 'bg-blue-50 text-blue-800 border-blue-200',
                                    'SAMPLE_SHIPPED'  => 'bg-teal-50 text-teal-800 border-teal-200',
                                    'DELIVERED'       => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'REJECTED'        => 'bg-rose-50 text-rose-800 border-rose-200',
                                    default           => 'bg-amber-50 text-amber-800 border-amber-200'
                                };
                                $statusLabel = match($st) {
                                    'IN_PRODUCTION'   => 'In Production',
                                    'SAMPLE_SHIPPED'  => 'Sample Shipped',
                                    'DELIVERED'       => 'Delivered',
                                    'REJECTED'        => 'Cannot Fulfill',
                                    default           => 'Pending Review'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-4 font-bold text-slate-900 font-mono">
                                    #SMP-<?= str_pad((string)(int)$inq['id'], 5, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td class="py-4 px-4 font-bold text-slate-900 flex items-center gap-3">
                                    <?php if (!empty($inq['product_id'])): ?>
                                        <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($inq['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shrink-0">
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($inq['product_name'] ?: ($inq['subject'] ?: 'Product Prototype')) ?></div>
                                        <?php if (!empty($inq['product_slug'])): ?>
                                            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($inq['product_slug']) ?>" target="_blank" class="text-[11px] text-teal-700 hover:underline">View Catalog Specs &rarr;</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-700 font-semibold">
                                    <?= htmlspecialchars($inq['supplier_name'] ?: 'Verified Indian Hub') ?>
                                </td>
                                <td class="py-4 px-4 text-slate-900 font-bold">
                                    <?= (int)$inq['target_quantity'] ?> pcs
                                    <?php if (!empty($inq['target_price']) || !empty($inq['sample_price'])): ?>
                                        <div class="text-[10px] text-slate-400 font-normal">$<?= number_format((float)($inq['target_price'] ?? $inq['sample_price']), 2) ?>/pc</div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-slate-600 max-w-xs">
                                    <div class="truncate" title="<?= htmlspecialchars($inq['delivery_location'] ?? '') ?>">
                                        <span class="material-symbols-outlined text-[14px] text-teal-700 align-middle">local_shipping</span>
                                        <?= htmlspecialchars($inq['delivery_location'] ?: 'Standard address') ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap text-[11px]">
                                    <?= date('d M Y, h:i A', strtotime($inq['created_at'])) ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $badgeClass ?>">
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right whitespace-nowrap">
                                    <button type="button" onclick="showBuyerSampleDetail(<?= htmlspecialchars(json_encode([
                                        'id' => (int)$inq['id'],
                                        'product' => $inq['product_name'] ?: ($inq['subject'] ?: 'Prototype'),
                                        'supplier' => $inq['supplier_name'] ?: 'Verified Indian Hub',
                                        'qty' => (int)$inq['target_quantity'],
                                        'price' => (float)($inq['target_price'] ?? $inq['sample_price'] ?? 0),
                                        'address' => $inq['delivery_location'] ?? '',
                                        'specs' => $inq['specifications'] ?? '',
                                        'status' => $statusLabel,
                                        'status_badge' => $badgeClass,
                                        'date' => date('d M Y, h:i A', strtotime($inq['created_at'])),
                                        'attachment' => $inq['attachment_path'] ? "{$baseUrl}/admin/enquiries/attachment/{$inq['id']}" : null
                                    ])) ?>)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        <span>View Details</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Buyer Sample Detail Modal -->
<div id="buyerSampleModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div id="buyerSampleModalCard" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col relative transform scale-95 transition-transform duration-200 overflow-hidden">
        <div class="p-5 pb-3 border-b border-slate-100 flex-shrink-0 relative">
            <button type="button" onclick="closeBuyerSampleModal()" class="absolute top-4 right-4 p-1.5 text-gray-400 hover:text-gray-700 rounded-lg bg-gray-100 transition-colors" title="Close">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
            <div class="flex items-center gap-2 mb-1 text-teal-700 font-semibold text-xs uppercase tracking-wider">
                <span class="material-symbols-outlined text-[18px]">science</span>
                <span>Prototype Sample Evaluation</span>
            </div>
            <h3 id="bModalTitle" class="font-headline font-bold text-lg text-slate-900">Sample Order Details</h3>
        </div>

        <div class="p-5 overflow-y-auto space-y-4 text-xs">
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold">Dispatch Status</span>
                    <span id="bModalStatus" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border mt-0.5"></span>
                </div>
                <div class="text-right">
                    <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold">Order Date</span>
                    <span id="bModalDate" class="font-bold text-slate-800"></span>
                </div>
            </div>

            <div>
                <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold mb-1">Product Prototype</span>
                <div id="bModalProduct" class="p-2.5 rounded-lg bg-slate-50 text-slate-800 font-bold"></div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold mb-1">Manufacturer</span>
                    <div id="bModalSupplier" class="p-2.5 rounded-lg bg-slate-50 text-slate-800 font-semibold"></div>
                </div>
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold mb-1">Quantity Ordered</span>
                    <div id="bModalQty" class="p-2.5 rounded-lg bg-slate-50 text-slate-800 font-semibold"></div>
                </div>
            </div>

            <div>
                <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold mb-1">Courier Shipping Destination</span>
                <div id="bModalAddress" class="p-2.5 rounded-lg bg-slate-50 text-slate-800 font-semibold"></div>
            </div>

            <div>
                <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold mb-1">Testing &amp; Evaluation Specifications</span>
                <div id="bModalSpecs" class="p-2.5 rounded-lg bg-slate-50 text-slate-800 font-mono whitespace-pre-wrap max-h-32 overflow-y-auto"></div>
            </div>

            <div id="bModalAttachmentRow" class="hidden">
                <span class="text-slate-500 uppercase tracking-wider block text-[10px] font-bold mb-1">Tech Pack Attachment</span>
                <a id="bModalAttachmentLink" href="#" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-50 text-teal-800 hover:bg-teal-100 font-bold transition-colors">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    <span>Download Attached File</span>
                </a>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end">
            <button type="button" onclick="closeBuyerSampleModal()" class="px-4 py-2 rounded-xl bg-teal-800 hover:bg-teal-900 text-white text-xs font-bold shadow-xs">Close</button>
        </div>
    </div>
</div>

<script>
function showBuyerSampleDetail(data) {
    document.getElementById('bModalTitle').textContent = `Sample Order #SMP-${String(data.id).padStart(5, '0')}`;
    const statusBadge = document.getElementById('bModalStatus');
    statusBadge.textContent = data.status;
    statusBadge.className = `inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border mt-0.5 ${data.status_badge}`;
    document.getElementById('bModalDate').textContent = data.date;
    document.getElementById('bModalProduct').textContent = data.product;
    document.getElementById('bModalSupplier').textContent = data.supplier;
    document.getElementById('bModalQty').textContent = `${data.qty} pcs` + (data.price > 0 ? ` ($${data.price.toFixed(2)}/pc)` : '');
    document.getElementById('bModalAddress').textContent = data.address || 'Address not specified';
    document.getElementById('bModalSpecs').textContent = data.specs || 'No specific notes provided.';

    const attachRow = document.getElementById('bModalAttachmentRow');
    const attachLink = document.getElementById('bModalAttachmentLink');
    if (data.attachment) {
        attachLink.href = data.attachment;
        attachRow.classList.remove('hidden');
    } else {
        attachRow.classList.add('hidden');
    }

    const modal = document.getElementById('buyerSampleModal');
    const card = document.getElementById('buyerSampleModalCard');
    document.body.classList.add('overflow-hidden');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
    }, 10);
}

function closeBuyerSampleModal() {
    const modal = document.getElementById('buyerSampleModal');
    const card = document.getElementById('buyerSampleModalCard');
    document.body.classList.remove('overflow-hidden');
    card.classList.add('scale-95');
    modal.classList.add('opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

document.getElementById('buyerSampleModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeBuyerSampleModal();
});
</script>
