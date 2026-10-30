<?php
declare(strict_types=1);
use App\Core\Response;
$baseUrl = $baseUrl ?? Response::baseUrl();
?>
<!-- Bulk Upload Modal Component -->
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
                    <div class="flex items-center justify-between mb-1.5 flex-wrap gap-2">
                        <div class="font-bold flex items-center gap-1.5 text-xs">
                            <span class="material-symbols-outlined text-[16px] text-red-600">error</span>
                            <span>Validation Issues Found in Rows:</span>
                        </div>
                        <button type="button" id="btnDownloadErrorReport" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold text-[11px] transition-colors shadow-2xs cursor-pointer">
                            <span class="material-symbols-outlined text-[14px]">download</span>
                            <span>Download Error Report (.txt)</span>
                        </button>
                    </div>
                    <ul id="supplierErrorsList" class="list-disc list-inside text-[11px] space-y-1.5 max-h-48 overflow-y-auto pr-1"></ul>
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
        if (!modal) return;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
    }

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

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

                btnValidate.disabled = false;
                btnValidate.innerHTML = '<span class="material-symbols-outlined text-[16px]">fact_check</span><span>Validate Spreadsheet</span>';

                if (!data.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        text: data.message || 'File validation failed.',
                        confirmButtonColor: '#00288e'
                    });
                    return;
                }

                currentFileToken = data.file_token;
                previewSection.classList.remove('hidden');

                // Render Stats Badges
                const stats = data.stats || {};
                statsContainer.innerHTML = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-[11px]">
                        Total: ${stats.total || 0}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-[11px]">
                        Valid: ${stats.valid || 0}
                    </span>
                    ${stats.errors > 0 ? `
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-red-800 border border-red-200 font-bold text-[11px]">
                            Issues: ${stats.errors}
                        </span>
                    ` : ''}
                `;

                // Render Errors if any
                const errors = data.errors || [];
                if (errors.length > 0) {
                    errorsContainer.classList.remove('hidden');
                    errorsList.innerHTML = errors.map(err => {
                        const errorDetails = Array.isArray(err.errors) 
                            ? err.errors.join('; ') 
                            : (err.message || 'Validation error');
                        const prodName = err.product ? ` [${escapeHtml(err.product)}]` : '';
                        return `<li><strong class="font-bold text-red-950">Row ${err.row}${prodName}:</strong> <span class="text-red-900">${escapeHtml(errorDetails)}</span></li>`;
                    }).join('');

                    // Enable download of error report
                    const btnDownload = document.getElementById('btnDownloadErrorReport');
                    if (btnDownload) {
                        btnDownload.onclick = function () {
                            let textContent = "SPORTS JUNCTION — BULK PRODUCT IMPORT ERROR REPORT\n";
                            textContent += "Generated At: " + new Date().toLocaleString() + "\n";
                            textContent += "Total Rows: " + (stats.total || 0) + " | Valid: " + (stats.valid || 0) + " | Issues: " + errors.length + "\n";
                            textContent += "--------------------------------------------------------\n\n";

                            errors.forEach(err => {
                                const details = Array.isArray(err.errors) ? err.errors.join('; ') : (err.message || 'Error');
                                textContent += `Row ${err.row}` + (err.product ? ` [${err.product}]` : '') + `:\n  - ${details}\n\n`;
                            });

                            const blob = new Blob([textContent], { type: 'text/plain;charset=utf-8' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `bulk_upload_errors_${new Date().toISOString().slice(0,10)}.txt`;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                        };
                    }
                } else {
                    errorsContainer.classList.add('hidden');
                    errorsList.innerHTML = '';
                }

                // Render Preview Table Rows
                const rows = data.preview_rows || [];
                previewTableBody.innerHTML = rows.map((r, idx) => {
                    const isValid = r.is_valid !== false;
                    const statusBadge = isValid
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Valid</span>'
                        : `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-800 border border-red-200" title="${escapeHtml(r.error || '')}">Error</span>`;

                    return `
                        <tr class="hover:bg-slate-50/50 ${!isValid ? 'bg-red-50/30' : ''}">
                            <td class="py-2 px-3 text-slate-400">${idx + 1}</td>
                            <td class="py-2 px-3 font-bold text-slate-900">${escapeHtml(r.title || '—')}</td>
                            <td class="py-2 px-3 text-slate-600">${escapeHtml(r.categories || '—')}</td>
                            <td class="py-2 px-3 text-blue-900 font-bold">$${parseFloat(r.min_price || 0).toFixed(2)}</td>
                            <td class="py-2 px-3 text-blue-900 font-bold">$${parseFloat(r.max_price || 0).toFixed(2)}</td>
                            <td class="py-2 px-3 text-slate-700">${r.moq_quantity || 1} ${escapeHtml(r.moq_unit || 'units')}</td>
                            <td class="py-2 px-3 font-mono text-slate-500">${escapeHtml(r.sku || 'AUTO')}</td>
                            <td class="py-2 px-3">${statusBadge}</td>
                        </tr>
                    `;
                }).join('');

                // Show commit button only if at least 1 valid row
                if ((stats.valid || 0) > 0) {
                    btnCommit.classList.remove('hidden');
                    btnCommit.disabled = false;
                    btnCommit.innerHTML = `<span class="material-symbols-outlined text-[16px]">check_circle</span><span>Submit ${stats.valid} Products for Approval</span>`;
                } else {
                    btnCommit.classList.add('hidden');
                }

            } catch (err) {
                btnValidate.disabled = false;
                btnValidate.innerHTML = '<span class="material-symbols-outlined text-[16px]">fact_check</span><span>Validate Spreadsheet</span>';
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Error',
                    text: err.message || 'An error occurred during file upload and validation.',
                    confirmButtonColor: '#00288e'
                });
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (btnCommit) {
        btnCommit.addEventListener('click', async function () {
            if (!currentFileToken) return;

            const confirm = await Swal.fire({
                title: 'Import Catalog Items?',
                text: 'All valid products will be created and submitted to Super Admin for approval.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00288e',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Submit Products'
            });

            if (!confirm.isConfirmed) return;

            btnCommit.disabled = true;
            btnCommit.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span><span>Submitting to Admin...</span>';

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
