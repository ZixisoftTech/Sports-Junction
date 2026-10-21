<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
$csrfToken = CSRF::generate();

$totalSamples = count($sampleEnquiries);
$pendingCount = 0;
$inProdCount = 0;
$shippedCount = 0;
$deliveredCount = 0;

foreach ($sampleEnquiries as $s) {
    $st = strtoupper($s['status'] ?? 'PENDING');
    if ($st === 'PENDING' || $st === 'SUBMITTED') $pendingCount++;
    elseif ($st === 'IN_PRODUCTION') $inProdCount++;
    elseif ($st === 'SAMPLE_SHIPPED') $shippedCount++;
    elseif ($st === 'DELIVERED') $deliveredCount++;
}
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-xs font-bold uppercase tracking-wider mb-2">
                <span class="material-symbols-outlined text-[16px] text-teal-600">science</span>
                <span>Fast-Track Prototype Evaluation (5-7 Days Dispatch)</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Prototype Sample Requests</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage prototype sample requests, coordinate rapid physical dispatch, and log door-to-door courier tracking.</p>
        </div>
    </div>

    <!-- Quick Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Requests</div>
            <div class="font-headline font-extrabold text-2xl text-slate-900"><?= $totalSamples ?></div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-1">Pending Review</div>
            <div class="font-headline font-extrabold text-2xl text-amber-700"><?= $pendingCount ?></div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">In Production</div>
            <div class="font-headline font-extrabold text-2xl text-blue-700"><?= $inProdCount ?></div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-teal-600 uppercase tracking-wider mb-1">Dispatched / Shipped</div>
            <div class="font-headline font-extrabold text-2xl text-teal-700"><?= $shippedCount ?></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Sample ID</th>
                        <th class="py-3.5 px-4">Product Name</th>
                        <th class="py-3.5 px-4">Buyer / Brand</th>
                        <th class="py-3.5 px-4">Sample Qty</th>
                        <th class="py-3.5 px-4">Courier Delivery Address</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($sampleEnquiries)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-[36px] text-slate-300 block mb-2">science</span>
                                No prototype sample requests received yet. When buyers request evaluation prototypes on your products, they will appear here.
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
                                    'REJECTED'        => 'Rejected',
                                    default           => 'Pending Review'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-4 font-bold text-slate-900 font-mono">
                                    #SMP-<?= str_pad((string)(int)$inq['id'], 5, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td class="py-4 px-4 font-bold text-slate-900 flex items-center gap-3">
                                    <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($inq['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shrink-0">
                                    <div>
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($inq['product_name'] ?: ($inq['subject'] ?: 'Prototype Sample')) ?></div>
                                        <?php if (!empty($inq['product_slug'])): ?>
                                            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($inq['product_slug']) ?>" target="_blank" class="text-[11px] text-teal-700 hover:underline">View Product &rarr;</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-700">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($inq['buyer_name']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($inq['company_name'] ?? 'Direct Buyer') ?></div>
                                    <?php if (!empty($inq['buyer_phone'])): ?>
                                        <div class="text-[11px] text-slate-500"><?= htmlspecialchars($inq['buyer_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-slate-900 font-bold">
                                    <?= (int)$inq['target_quantity'] ?> pcs
                                    <?php if (!empty($inq['sample_price']) || !empty($inq['target_price'])): ?>
                                        <div class="text-[10px] text-slate-400 font-normal">$<?= number_format((float)($inq['target_price'] ?? $inq['sample_price']), 2) ?>/pc</div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-slate-600 max-w-xs">
                                    <div class="truncate" title="<?= htmlspecialchars($inq['delivery_location'] ?? '') ?>">
                                        <span class="material-symbols-outlined text-[14px] text-teal-700 align-middle">local_shipping</span>
                                        <?= htmlspecialchars($inq['delivery_location'] ?: 'Not provided') ?>
                                    </div>
                                    <?php if (!empty($inq['country_name'])): ?>
                                        <div class="text-[10px] text-slate-400"><?= htmlspecialchars($inq['country_name']) ?></div>
                                    <?php endif; ?>
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
                                    <button type="button" onclick="openSupplierStatusModal(<?= (int)$inq['id'] ?>, '<?= htmlspecialchars(addslashes($st)) ?>', '<?= htmlspecialchars(addslashes($inq['specifications'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($inq['delivery_location'] ?? '')) ?>')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-bold transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">edit_note</span>
                                        <span>Update Dispatch</span>
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

<!-- Supplier Sample Status Update Modal -->
<div id="supplierSampleModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div id="supplierSampleModalCard" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col relative transform scale-95 transition-transform duration-200 overflow-hidden">
        <div class="p-5 pb-3 border-b border-slate-100 flex-shrink-0 relative">
            <button type="button" onclick="closeSupplierStatusModal()" class="absolute top-4 right-4 p-1.5 text-gray-400 hover:text-gray-700 rounded-lg bg-gray-100 transition-colors" title="Close">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
            <div class="flex items-center gap-2 mb-1.5 text-teal-700 font-semibold text-xs uppercase tracking-wider">
                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                <span>Prototype Dispatch Coordination</span>
            </div>
            <h3 id="modalSampleTitle" class="font-headline font-bold text-lg text-slate-900">Update Sample Order</h3>
        </div>

        <form id="supplierSampleStatusForm" class="p-5 space-y-4" onsubmit="submitSupplierSampleStatus(event)">
            <input type="hidden" id="modalSampleId" value="">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Buyer Courier Address</label>
                <div id="modalSampleAddress" class="p-2.5 bg-slate-50 rounded-lg text-xs text-slate-700 font-semibold"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Prototype Testing Specifications</label>
                <div id="modalSampleSpecs" class="p-2.5 bg-slate-50 rounded-lg text-xs text-slate-700 font-mono max-h-24 overflow-y-auto whitespace-pre-wrap"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Sample Order Status *</label>
                <select name="status" id="modalSampleStatus" required class="w-full bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none">
                    <option value="IN_PRODUCTION">In Production (Sample being manufactured)</option>
                    <option value="SAMPLE_SHIPPED">Sample Shipped (Dispatched with courier)</option>
                    <option value="DELIVERED">Delivered (Confirmed receipt)</option>
                    <option value="REJECTED">Cannot Fulfill (Cancel request)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Courier Tracking / Dispatch Notes</label>
                <textarea name="tracking_notes" id="modalTrackingNotes" rows="3" placeholder="e.g. Dispatched via BlueDart / DHL. Tracking AWB #987654321. Expected delivery in 3 days." class="w-full bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeSupplierStatusModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50">Cancel</button>
                <button type="submit" id="btnSaveSampleStatus" class="px-4 py-2 rounded-xl bg-teal-800 hover:bg-teal-900 text-white text-xs font-bold shadow-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Update Sample Order</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSupplierStatusModal(id, currentStatus, specs, address) {
    document.getElementById('modalSampleId').value = id;
    document.getElementById('modalSampleTitle').textContent = `Sample Order #SMP-${String(id).padStart(5, '0')}`;
    document.getElementById('modalSampleAddress').textContent = address || 'Address not specified';
    document.getElementById('modalSampleSpecs').textContent = specs || 'No specifications provided';
    document.getElementById('modalTrackingNotes').value = '';

    const sel = document.getElementById('modalSampleStatus');
    if (['IN_PRODUCTION', 'SAMPLE_SHIPPED', 'DELIVERED', 'REJECTED'].includes(currentStatus)) {
        sel.value = currentStatus;
    } else {
        sel.value = 'IN_PRODUCTION';
    }

    const modal = document.getElementById('supplierSampleModal');
    const card = document.getElementById('supplierSampleModalCard');
    document.body.classList.add('overflow-hidden');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
    }, 10);
}

function closeSupplierStatusModal() {
    const modal = document.getElementById('supplierSampleModal');
    const card = document.getElementById('supplierSampleModalCard');
    document.body.classList.remove('overflow-hidden');
    card.classList.add('scale-95');
    modal.classList.add('opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

document.getElementById('supplierSampleModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeSupplierStatusModal();
});

function submitSupplierSampleStatus(e) {
    e.preventDefault();
    const id = document.getElementById('modalSampleId').value;
    const form = e.target;
    const btn = document.getElementById('btnSaveSampleStatus');
    const origHtml = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span> Updating...';

    const formData = new FormData(form);

    fetch(`<?= $baseUrl ?>/api/supplier/sample-enquiries/${id}/status`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (data.success) {
            closeSupplierStatusModal();
            window.location.reload();
        } else {
            alert(data.message || 'Failed to update sample status.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        alert('Server or network connection error.');
    });
}
</script>
