<?php
declare(strict_types=1);

use App\Core\Response;

$baseUrl = Response::baseUrl();
$enquiries = !empty($enquiries) ? $enquiries : [];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb & Header -->
    <div class="mb-6">
        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="inline-flex items-center gap-1 text-xs font-bold text-blue-900 hover:underline mb-2">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Buyer Dashboard</span>
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-900 text-3xl">receipt_long</span>
                    <span>My Bulk Enquiries</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Review and track all your multi-product sourcing quotations and factory requests submitted through the Sort List.
                </p>
            </div>
            <div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/sort-list" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-900 hover:bg-blue-950 text-white text-xs font-bold transition-all shadow-xs">
                    <span class="material-symbols-outlined text-[16px]">checklist</span>
                    <span>Open Sort List</span>
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($enquiries)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400 max-w-xl mx-auto my-6 shadow-xs">
            <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-900 flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-3xl">receipt_long</span>
            </div>
            <h3 class="font-headline font-bold text-lg text-slate-800 mb-1">No Bulk Enquiries Submitted Yet</h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mb-6 max-w-md mx-auto">
                Shortlist items into your <strong>Sort List</strong> and submit them together in one comprehensive sourcing inquiry.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/sort-list" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-blue-900 text-white font-bold text-xs shadow-xs hover:bg-blue-950 transition-all">
                    <span>Go to Sort List</span>
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-all">
                    <span>Explore Products</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Enquiries Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">Reference No</th>
                            <th class="py-3.5 px-4">Submission Date</th>
                            <th class="py-3.5 px-4 text-center">Products</th>
                            <th class="py-3.5 px-4">Summary of Equipment</th>
                            <th class="py-3.5 px-4">Destination</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($enquiries as $enq): ?>
                            <?php
                                $statusKey = strtoupper((string)($enq['status'] ?? 'SUBMITTED'));
                                $statusBadge = match($statusKey) {
                                    'SUBMITTED' => 'bg-blue-50 text-blue-800 border-blue-200',
                                    'REVIEWED'  => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'PROCESSED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'CLOSED'    => 'bg-slate-100 text-slate-700 border-slate-300',
                                    default     => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-blue-900 whitespace-nowrap">
                                    <?= htmlspecialchars($enq['reference_no']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y, h:i A', strtotime($enq['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-900 font-bold text-xs">
                                        <?= (int)$enq['product_count'] ?> Products
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="line-clamp-2 text-slate-800 font-medium">
                                        <?= htmlspecialchars($enq['products_summary'] ?? $enq['subject'] ?? 'Bulk Sourcing Request') ?>
                                    </div>
                                    <?php if (!empty($enq['suppliers_summary'])): ?>
                                        <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">
                                            Factories: <?= htmlspecialchars($enq['suppliers_summary']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600">
                                    <?= htmlspecialchars($enq['country_name'] ?? 'India') ?>
                                    <?php if (!empty($enq['delivery_location'])): ?>
                                        <span class="text-slate-400 text-[11px] block">(<?= htmlspecialchars($enq['delivery_location']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full border text-[11px] font-bold <?= $statusBadge ?>">
                                        <?= htmlspecialchars(\App\Models\BulkProductEnquiry::STATUSES[$statusKey] ?? $statusKey) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <button type="button" onclick="viewBuyerBulkEnquiry(<?= (int)$enq['id'] ?>)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-900 font-bold text-xs transition-colors">
                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                        <span>View Details</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>Showing Page <?= $page ?> of <?= $totalPages ?> (Total: <?= $total ?> enquiries)</div>
                    <div class="flex items-center gap-1.5">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 font-bold">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 font-bold">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- DETAIL MODAL FOR BUYER -->
<!-- ========================================================================= -->
<div id="buyerBulkDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div id="buyerBulkDetailModalCard" class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col relative transform scale-95 transition-transform duration-200 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Bulk Sourcing Submission</div>
                <h3 class="font-headline font-bold text-lg text-slate-900 flex items-center gap-2 mt-0.5">
                    <span id="detailRefNo">BENQ-XXXXX</span>
                    <span id="detailStatusBadge" class="px-2 py-0.5 rounded-full border text-[10px] font-bold"></span>
                </h3>
            </div>
            <button type="button" onclick="closeBuyerBulkDetailModal()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-slate-50 rounded-xl text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Submitted Date</span>
                    <span id="detailDate" class="font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Destination Country</span>
                    <span id="detailCountry" class="font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">City / Location</span>
                    <span id="detailLocation" class="font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Total Products</span>
                    <span id="detailCount" class="font-semibold text-blue-900"></span>
                </div>
            </div>

            <!-- Overall Notes -->
            <div id="detailOverallBox" class="hidden p-3.5 rounded-xl bg-blue-50/50 border border-blue-100 text-xs">
                <span class="font-bold text-blue-900 block mb-1">Overall Sourcing Notes:</span>
                <p id="detailOverallMessage" class="text-slate-700 whitespace-pre-line"></p>
            </div>

            <!-- Shared Attachment Link -->
            <div id="detailSharedAttachmentBox" class="hidden p-3 rounded-xl bg-slate-100 border border-slate-200 text-xs flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-500 text-[18px]">attachment</span>
                    <span class="font-bold text-slate-700" id="detailSharedAttachmentName">Shared Tech Pack</span>
                </div>
                <a id="detailSharedAttachmentLink" href="#" target="_blank" download class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-blue-900 font-bold hover:bg-blue-50 transition-colors">
                    Download
                </a>
            </div>

            <!-- Product Items List -->
            <div>
                <h4 class="font-headline font-bold text-xs uppercase tracking-wider text-slate-500 mb-3">
                    Requested Products (<span id="detailItemsHeaderCount">0</span>)
                </h4>
                <div id="detailItemsContainer" class="space-y-3">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end shrink-0">
            <button type="button" onclick="closeBuyerBulkDetailModal()" class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function viewBuyerBulkEnquiry(id) {
    const modal = document.getElementById('buyerBulkDetailModal');
    const card = document.getElementById('buyerBulkDetailModalCard');

    fetch(`<?= htmlspecialchars($baseUrl) ?>/buyer/bulk-enquiries/${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.enquiry) {
            alert(res.message || 'Error fetching bulk enquiry details.');
            return;
        }

        const enq = res.enquiry;
        document.getElementById('detailRefNo').innerText = enq.reference_no;
        document.getElementById('detailDate').innerText = enq.created_at;
        document.getElementById('detailCountry').innerText = enq.country_name || enq.resolved_country_name || 'India';
        document.getElementById('detailLocation').innerText = enq.delivery_location || 'Not Specified';
        document.getElementById('detailCount').innerText = `${enq.product_count} Products`;
        document.getElementById('detailItemsHeaderCount').innerText = enq.product_count;

        // Status Badge
        const badge = document.getElementById('detailStatusBadge');
        badge.innerText = enq.status;
        badge.className = 'px-2 py-0.5 rounded-full border text-[10px] font-bold ' + 
            (enq.status === 'SUBMITTED' ? 'bg-blue-50 text-blue-800 border-blue-200' : 
            (enq.status === 'PROCESSED' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-300'));

        // Overall Message
        const overallBox = document.getElementById('detailOverallBox');
        if (enq.overall_message && enq.overall_message.trim().length > 0) {
            document.getElementById('detailOverallMessage').innerText = enq.overall_message;
            overallBox.classList.remove('hidden');
        } else {
            overallBox.classList.add('hidden');
        }

        // Shared Attachment
        const sharedBox = document.getElementById('detailSharedAttachmentBox');
        if (enq.attachment_path) {
            document.getElementById('detailSharedAttachmentName').innerText = enq.attachment_name || 'Tech Pack Attachment';
            document.getElementById('detailSharedAttachmentLink').href = `<?= htmlspecialchars($baseUrl) ?>/${enq.attachment_path}`;
            sharedBox.classList.remove('hidden');
        } else {
            sharedBox.classList.add('hidden');
        }

        // Render Items
        const itemsContainer = document.getElementById('detailItemsContainer');
        itemsContainer.innerHTML = '';
        (enq.items || []).forEach(item => {
            const row = document.createElement('div');
            row.className = 'p-3.5 rounded-xl border border-slate-200 bg-white space-y-2';
            row.innerHTML = `
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <img src="${item.primary_image ? (item.primary_image.indexOf('/') !== -1 ? '<?= htmlspecialchars($baseUrl) ?>/' + item.primary_image.replace(/^\//, '') : '<?= htmlspecialchars($baseUrl) ?>/uploads/products/' + item.primary_image) : '<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg'}" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-10 h-10 rounded-lg object-cover border border-slate-100 shrink-0">
                        <div>
                            <div class="font-headline font-bold text-xs sm:text-sm text-slate-900">${item.product_name}</div>
                            <div class="text-[11px] text-slate-500">Manufacturer: <strong>${item.supplier_name || 'Verified Indian Factory'}</strong></div>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="font-bold text-blue-900 text-xs">Qty: ${item.target_quantity} ${item.moq_unit_name || 'pcs'}</div>
                        <div class="text-[10px] text-slate-400">MOQ: ${item.moq_quantity}</div>
                    </div>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-lg text-xs text-slate-700 border border-slate-100">
                    <span class="font-bold text-slate-800 text-[11px] block mb-0.5">Specifications:</span>
                    <p class="whitespace-pre-line text-[11px]">${item.specifications || 'Standard manufacturer specifications.'}</p>
                </div>
                ${item.attachment_path ? `
                    <div class="text-[11px] pt-1 flex items-center justify-between">
                        <span class="text-slate-500 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">attachment</span>
                            ${item.attachment_name || 'File Specs'}
                        </span>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/${item.attachment_path}" target="_blank" download class="font-bold text-blue-900 hover:underline">Download File</a>
                    </div>
                ` : ''}
            `;
            itemsContainer.appendChild(row);
        });

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    })
    .catch(() => alert('Network error loading enquiry details.'));
}

function closeBuyerBulkDetailModal() {
    const modal = document.getElementById('buyerBulkDetailModal');
    const card = document.getElementById('buyerBulkDetailModalCard');
    modal.classList.add('opacity-0');
    card.classList.add('scale-95');
    card.classList.remove('scale-100');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}
</script>
