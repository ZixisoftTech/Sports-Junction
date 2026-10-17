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
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <button type="button" id="btnOpenBulkModal" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-blue-900/20 text-blue-900 bg-blue-50 hover:bg-blue-100 font-bold text-xs shadow-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">upload_file</span>
                <span>Bulk Upload Products</span>
            </button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Add New Product</span>
            </a>
        </div>
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
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($p['all_category_names'] ?? $p['category_name'] ?? '—') ?></div>
                                    <?php if (!empty($p['all_subcategory_names']) || !empty($p['subcategory_name'])): ?>
                                        <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($p['all_subcategory_names'] ?? $p['subcategory_name'] ?? '') ?></div>
                                    <?php endif; ?>
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

<!-- Bulk Product Upload Modal -->
<div id="supplierBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity duration-200">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-900 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">upload_file</span>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Bulk Product Upload</h3>
                    <p class="text-[11px] text-slate-500">Import your factory catalog items in bulk using an Excel spreadsheet</p>
                </div>
            </div>
            <button type="button" id="btnCloseBulkModal" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto space-y-5 flex-1 text-xs">
            <!-- Template Download Banner -->
            <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h4 class="font-bold text-blue-950 text-sm flex items-center gap-1.5 mb-1">
                        <span class="material-symbols-outlined text-[18px] text-blue-800">download</span>
                        <span>Step 1: Download Catalog Template</span>
                    </h4>
                    <p class="text-slate-600 text-[11px] leading-relaxed">
                        Fill in product titles, category names, MOQ, USD price brackets, and specs. Multiple categories can be separated with commas.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/bulk-template?format=xlsx" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors">
                        <span class="material-symbols-outlined text-[16px]">table_chart</span>
                        <span>Template (.xlsx)</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/bulk-template?format=csv" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors">
                        <span>CSV</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/import-reference?format=xlsx" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-blue-800 hover:bg-blue-900 text-white font-bold text-xs shadow-xs transition-colors" title="Download Reference IDs for Categories, Subcategories, Units, and Countries">
                        <span class="material-symbols-outlined text-[16px]">database</span>
                        <span>Reference Data (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- Drag & Drop Zone -->
            <div>
                <label class="block font-bold text-slate-700 mb-2">Step 2: Upload Completed Spreadsheet</label>
                <div id="supplierDropzone" class="border-2 border-dashed border-slate-300 hover:border-blue-600 rounded-2xl p-6 text-center cursor-pointer bg-slate-50/60 hover:bg-blue-50/20 transition-all">
                    <input type="file" id="supplierBulkFileInput" class="hidden" accept=".xlsx,.csv,.txt">
                    <span class="material-symbols-outlined text-[44px] text-blue-700 block mb-2">cloud_upload</span>
                    <p class="font-bold text-slate-800 text-sm">Click to select file or drag and drop here</p>
                    <p class="text-[11px] text-slate-400 mt-1">Supports Excel (.xlsx) and CSV files up to 15MB</p>
                    <div id="supplierSelectedFileInfo" class="mt-3 hidden">
                        <span id="supplierFileName" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-900 text-white font-bold text-xs"></span>
                    </div>
                </div>
            </div>

            <!-- Preview & Validation Container -->
            <div id="supplierPreviewSection" class="hidden space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[16px] text-emerald-600">verified</span>
                        <span>Spreadsheet Validation Summary</span>
                    </h4>
                    <div id="supplierStatsBadges" class="flex items-center gap-2"></div>
                </div>

                <!-- Error Messages Box -->
                <div id="supplierErrorsContainer" class="hidden p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800">
                    <div class="font-bold flex items-center gap-1.5 mb-1 text-xs">
                        <span class="material-symbols-outlined text-[16px] text-red-600">error</span>
                        <span>Validation Issues Found in Rows:</span>
                    </div>
                    <ul id="supplierErrorsList" class="list-disc list-inside text-[11px] space-y-1 max-h-36 overflow-y-auto"></ul>
                </div>

                <!-- Preview Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-60 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 text-slate-600 font-bold sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3">#</th>
                                <th class="py-2.5 px-3">Product Name</th>
                                <th class="py-2.5 px-3">Categories</th>
                                <th class="py-2.5 px-3">Min ($)</th>
                                <th class="py-2.5 px-3">Max ($)</th>
                                <th class="py-2.5 px-3">MOQ</th>
                                <th class="py-2.5 px-3">SKU</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody id="supplierPreviewTableBody" class="divide-y divide-slate-100 font-medium"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-3.5 border-t border-slate-200 bg-slate-50">
            <button type="button" id="btnCancelBulkModal" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-100 transition-colors">
                Cancel
            </button>
            <div class="flex items-center gap-2">
                <button type="button" id="btnSupplierBulkValidate" disabled class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-900 hover:bg-blue-950 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-xs shadow-xs transition-colors">
                    <span class="material-symbols-outlined text-[16px]">fact_check</span>
                    <span>Validate Spreadsheet</span>
                </button>
                <button type="button" id="btnSupplierBulkCommit" class="hidden inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                    <span>Submit Products for Approval</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';
    const csrfToken = '<?= \App\Helpers\CSRF::getToken() ?>';

    const modal = document.getElementById('supplierBulkModal');
    const btnOpen = document.getElementById('btnOpenBulkModal');
    const btnClose = document.getElementById('btnCloseBulkModal');
    const btnCancel = document.getElementById('btnCancelBulkModal');

    const dropzone = document.getElementById('supplierDropzone');
    const fileInput = document.getElementById('supplierBulkFileInput');
    const fileInfo = document.getElementById('supplierSelectedFileInfo');
    const fileNameBadge = document.getElementById('supplierFileName');
    const btnValidate = document.getElementById('btnSupplierBulkValidate');
    const btnCommit = document.getElementById('btnSupplierBulkCommit');
    const previewSection = document.getElementById('supplierPreviewSection');
    const statsContainer = document.getElementById('supplierStatsBadges');
    const errorsContainer = document.getElementById('supplierErrorsContainer');
    const errorsList = document.getElementById('supplierErrorsList');
    const previewTableBody = document.getElementById('supplierPreviewTableBody');

    let currentFile = null;
    let currentFileToken = null;

    function openModal() {
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('border-blue-700', 'bg-blue-50/40');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('border-blue-700', 'bg-blue-50/40');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-blue-700', 'bg-blue-50/40');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                handleFileSelect(this.files[0]);
            }
        });
    }

    function handleFileSelect(file) {
        currentFile = file;
        fileNameBadge.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        fileInfo.classList.remove('hidden');
        btnValidate.disabled = false;
        btnCommit.classList.add('hidden');
        previewSection.classList.add('hidden');
    }

    if (btnValidate) {
        btnValidate.addEventListener('click', async function () {
            if (!currentFile) return;

            btnValidate.disabled = true;
            btnValidate.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span><span>Validating...</span>';

            const formData = new FormData();
            formData.append('file', currentFile);
            formData.append('_csrf', csrfToken);

            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
            }

            try {
                const res = await fetch(`${baseUrl}/supplier/products/bulk-preview`, {
                    method: 'POST',
                    headers: headers,
                    body: formData
                });
                
                let data;
                try {
                    data = await res.json();
                } catch (jsonErr) {
                    throw new Error('Server returned an invalid response (Status ' + res.status + '). Please try again.');
                }

                if (!data.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: data.message || 'Unable to parse spreadsheet.',
                        confirmButtonColor: '#00288e'
                    });
                    btnValidate.disabled = false;
                    btnValidate.innerHTML = '<span class="material-symbols-outlined text-[16px]">fact_check</span><span>Validate Spreadsheet</span>';
                    return;
                }

                currentFileToken = data.file_token;
                previewSection.classList.remove('hidden');

                statsContainer.innerHTML = `
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">Total: ${data.total_rows}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Valid: ${data.valid_rows_count}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-800 border border-red-200">Issues: ${data.error_rows_count}</span>
                `;

                if (data.errors && data.errors.length > 0) {
                    errorsContainer.classList.remove('hidden');
                    errorsList.innerHTML = data.errors.map(err => `
                        <li><strong>Row ${err.row} [${err.product}]:</strong> ${err.errors.join('; ')}</li>
                    `).join('');
                } else {
                    errorsContainer.classList.add('hidden');
                    errorsList.innerHTML = '';
                }

                if (data.preview_rows && data.preview_rows.length > 0) {
                    previewTableBody.innerHTML = data.preview_rows.map(row => `
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 text-slate-400">Row ${row.row_num}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">${row.name}</td>
                            <td class="py-2.5 px-3 text-slate-600">${row.category_names ? row.category_names.join(', ') : '—'}</td>
                            <td class="py-2.5 px-3 font-bold text-blue-900">$${parseFloat(row.min_price).toFixed(2)}</td>
                            <td class="py-2.5 px-3 font-bold text-blue-900">$${parseFloat(row.max_price).toFixed(2)}</td>
                            <td class="py-2.5 px-3 text-slate-600">${row.moq}</td>
                            <td class="py-2.5 px-3 text-slate-500 font-mono text-[11px]">${row.sku || '—'}</td>
                            <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Ready</span></td>
                        </tr>
                    `).join('');
                } else {
                    previewTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-slate-400">No valid rows available to import.</td></tr>';
                }

                if (data.valid_rows_count > 0) {
                    btnCommit.classList.remove('hidden');
                    btnCommit.innerHTML = `<span class="material-symbols-outlined text-[16px]">check_circle</span><span>Submit (${data.valid_rows_count}) Products for Approval</span>`;
                } else {
                    btnCommit.classList.add('hidden');
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: err.message || 'Failed to communicate with validation server.',
                    confirmButtonColor: '#00288e'
                });
            } finally {
                btnValidate.disabled = false;
                btnValidate.innerHTML = '<span class="material-symbols-outlined text-[16px]">refresh</span><span>Re-Validate</span>';
            }
        });
    }

    if (btnCommit) {
        btnCommit.addEventListener('click', async function () {
            if (!currentFileToken) return;

            btnCommit.disabled = true;
            btnCommit.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span><span>Importing...</span>';

            try {
                const res = await fetch(`${baseUrl}/supplier/products/bulk-commit`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: new URLSearchParams({
                        file_token: currentFileToken,
                        _csrf: csrfToken
                    })
                });
                
                let data;
                try {
                    data = await res.json();
                } catch (jsonErr) {
                    throw new Error('Server returned an invalid response (Status ' + res.status + '). Please try again.');
                }

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Successful!',
                        text: data.message,
                        confirmButtonColor: '#00288e'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed',
                        text: data.message || 'Could not import products.',
                        confirmButtonColor: '#00288e'
                    });
                    btnCommit.disabled = false;
                    btnCommit.innerHTML = '<span class="material-symbols-outlined text-[16px]">check_circle</span><span>Try Again</span>';
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: err.message || 'Error occurred during product submission.',
                    confirmButtonColor: '#00288e'
                });
                btnCommit.disabled = false;
                btnCommit.innerHTML = '<span class="material-symbols-outlined text-[16px]">check_circle</span><span>Try Again</span>';
            }
        });
    }
});
</script>
