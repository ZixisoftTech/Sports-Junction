<?php
declare(strict_types=1);
use App\Core\Response;
$baseUrl = $baseUrl ?? Response::baseUrl();
?>
<!-- Bulk Upload Modal Component -->
<div id="supplierBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity duration-200">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-900 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined notranslate text-[20px]" translate="no">upload_file</span>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Bulk Product Upload</h3>
                    <p class="text-[11px] text-slate-500">Import your factory catalog items in bulk using an Excel spreadsheet</p>
                </div>
            </div>
            <button type="button" id="btnCloseBulkModal" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                <span class="material-symbols-outlined notranslate text-[22px]" translate="no">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto space-y-5 flex-1 text-xs">
            <!-- Template Download Banner -->
            <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h4 class="font-bold text-blue-950 text-sm flex items-center gap-1.5 mb-1">
                        <span class="material-symbols-outlined notranslate text-[18px] text-blue-800" translate="no">download</span>
                        <span>Step 1: Download Catalog Template</span>
                    </h4>
                    <p class="text-slate-600 text-[11px] leading-relaxed">
                        Fill in product titles, category names, MOQ, USD price brackets, and specs. Multiple categories can be separated with commas.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/bulk-template?format=xlsx" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors">
                        <span class="material-symbols-outlined notranslate text-[16px]" translate="no">table_chart</span>
                        <span>Template (.xlsx)</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/bulk-template?format=csv" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors">
                        <span>CSV</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/import-reference?format=xlsx" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-blue-800 hover:bg-blue-900 text-white font-bold text-xs shadow-xs transition-colors" title="Download Reference IDs for Categories, Subcategories, Units, and Countries">
                        <span class="material-symbols-outlined notranslate text-[16px]" translate="no">database</span>
                        <span>Reference Data (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- Drag & Drop Zone -->
            <div>
                <label class="block font-bold text-slate-700 mb-2">Step 2: Upload Completed Spreadsheet</label>
                <div id="supplierDropzone" class="border-2 border-dashed border-slate-300 hover:border-blue-600 rounded-2xl p-6 text-center cursor-pointer bg-slate-50/60 hover:bg-blue-50/20 transition-all">
                    <input type="file" id="supplierBulkFileInput" class="hidden" accept=".xlsx,.csv,.txt">
                    <span class="material-symbols-outlined notranslate text-[44px] text-blue-700 block mb-2" translate="no">cloud_upload</span>
                    <p class="font-bold text-slate-800 text-sm">Click to select file or drag and drop here</p>
                    <p class="text-[11px] text-slate-400 mt-1">Supports Excel (.xlsx) and CSV files up to 15MB</p>
                    <div id="supplierSelectedFileInfo" class="mt-3 hidden">
                        <span id="supplierFileName" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-900 text-white font-bold text-xs"></span>
                    </div>
                </div>
            </div>

            <!-- Preview & Validation Container -->
            <div id="supplierPreviewSection" class="hidden space-y-4">
                <!-- Validation Summary Header -->
                <div class="flex flex-wrap items-center justify-between border-b border-slate-200 pb-3 gap-2">
                    <div>
                        <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                            <span class="material-symbols-outlined notranslate text-[16px] text-emerald-600" translate="no">verified</span>
                            <span>Spreadsheet Import Validation Report</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Review row-by-row validation results before submitting products to Super Admin</p>
                    </div>
                    <div id="supplierStatsBadges" class="flex flex-wrap items-center gap-2"></div>
                </div>

                <!-- Navigation Tabs (Errors / Valid / Skipped) -->
                <div class="flex border-b border-slate-200 gap-1" id="supplierReportTabs">
                    <button type="button" id="tabBtnErrors" class="px-4 py-2 font-bold text-xs border-b-2 border-red-600 text-red-700 flex items-center gap-1.5 transition-colors">
                        <span class="material-symbols-outlined notranslate text-[15px]" translate="no">error</span>
                        <span>Failed Records</span>
                        <span id="badgeCountErrors" class="px-1.5 py-0.2 rounded-full bg-red-100 text-red-800 text-[10px] font-extrabold">0</span>
                    </button>
                    <button type="button" id="tabBtnValid" class="px-4 py-2 font-semibold text-xs border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 transition-colors">
                        <span class="material-symbols-outlined notranslate text-[15px]" translate="no">check_circle</span>
                        <span>Successful Records</span>
                        <span id="badgeCountValid" class="px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold">0</span>
                    </button>
                    <button type="button" id="tabBtnSkipped" class="px-4 py-2 font-semibold text-xs border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 transition-colors hidden">
                        <span class="material-symbols-outlined notranslate text-[15px]" translate="no">playlist_remove</span>
                        <span>Skipped / Duplicates</span>
                        <span id="badgeCountSkipped" class="px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 text-[10px] font-extrabold">0</span>
                    </button>
                </div>

                <!-- Tab Pane 1: Failed Records & Detailed Error Table -->
                <div id="paneErrors" class="space-y-3">
                    <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-900 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined notranslate text-[20px] text-red-600 shrink-0" translate="no">report_problem</span>
                            <div>
                                <span class="font-bold text-xs text-red-950 block">Row-Level Issues Identified in Spreadsheet</span>
                                <span class="text-[11px] text-red-700" id="errorsBannerSubtext">Every invalid field, failure reason, and suggested correction is detailed below.</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnDownloadErrorsXlsx" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold text-xs shadow-2xs transition-colors cursor-pointer">
                                <span class="material-symbols-outlined notranslate text-[15px]" translate="no">table_chart</span>
                                <span>Download Report (.xlsx)</span>
                            </button>
                            <button type="button" id="btnDownloadErrorsCsv" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-red-300 hover:bg-red-50 text-red-700 font-bold text-xs shadow-2xs transition-colors cursor-pointer">
                                <span class="material-symbols-outlined notranslate text-[15px]" translate="no">description</span>
                                <span>CSV</span>
                            </button>
                        </div>
                    </div>

                    <!-- Detailed Error Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden max-h-72 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0 border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3 w-20">Excel Row</th>
                                    <th class="py-2.5 px-3 w-48">Product Name / SKU</th>
                                    <th class="py-2.5 px-3 w-36">Field</th>
                                    <th class="py-2.5 px-3">Error Description</th>
                                    <th class="py-2.5 px-3">Suggested Fix</th>
                                </tr>
                            </thead>
                            <tbody id="supplierErrorTableBody" class="divide-y divide-slate-100 font-medium"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Pane 2: Successful Records -->
                <div id="paneValid" class="hidden space-y-3">
                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined notranslate text-[18px] text-emerald-600" translate="no">check_circle</span>
                            <span class="font-bold text-xs text-emerald-950">Valid catalog items ready for import and Super Admin approval.</span>
                        </div>
                        <span class="text-[11px] text-emerald-700 font-bold" id="validSummaryCount">0 Products</span>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden max-h-72 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0 border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">#</th>
                                    <th class="py-2.5 px-3">Row</th>
                                    <th class="py-2.5 px-3">Product Name</th>
                                    <th class="py-2.5 px-3">Categories</th>
                                    <th class="py-2.5 px-3">Min ($)</th>
                                    <th class="py-2.5 px-3">Max ($)</th>
                                    <th class="py-2.5 px-3">MOQ</th>
                                    <th class="py-2.5 px-3">SKU</th>
                                    <th class="py-2.5 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody id="supplierValidTableBody" class="divide-y divide-slate-100 font-medium"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Pane 3: Skipped / Duplicate Records -->
                <div id="paneSkipped" class="hidden space-y-3">
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-2">
                        <span class="material-symbols-outlined notranslate text-[18px] text-amber-600" translate="no">warning</span>
                        <span class="font-bold text-xs text-amber-950">These rows were skipped because they duplicate existing marketplace records or lack core information.</span>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden max-h-72 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0 border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3 w-20">Excel Row</th>
                                    <th class="py-2.5 px-3 w-48">Product Name</th>
                                    <th class="py-2.5 px-3 w-36">SKU</th>
                                    <th class="py-2.5 px-3">Reason Skipped</th>
                                    <th class="py-2.5 px-3">Suggested Action</th>
                                </tr>
                            </thead>
                            <tbody id="supplierSkippedTableBody" class="divide-y divide-slate-100 font-medium"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-3.5 border-t border-slate-200 bg-slate-50">
            <button type="button" id="btnCancelBulkModal" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-100 transition-colors">
                Close
            </button>
            <div class="flex items-center gap-2">
                <button type="button" id="btnSupplierBulkValidate" disabled class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-900 hover:bg-blue-950 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-xs shadow-xs transition-colors">
                    <span class="material-symbols-outlined notranslate text-[16px]" translate="no">fact_check</span>
                    <span>Validate Spreadsheet</span>
                </button>
                <button type="button" id="btnSupplierBulkCommit" class="hidden inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors">
                    <span class="material-symbols-outlined notranslate text-[16px]" translate="no">check_circle</span>
                    <span id="btnCommitText">Submit Products for Approval</span>
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
    const btnCommitText = document.getElementById('btnCommitText');
    const previewSection = document.getElementById('supplierPreviewSection');
    const statsContainer = document.getElementById('supplierStatsBadges');

    // Tabs
    const tabBtnErrors = document.getElementById('tabBtnErrors');
    const tabBtnValid = document.getElementById('tabBtnValid');
    const tabBtnSkipped = document.getElementById('tabBtnSkipped');
    const paneErrors = document.getElementById('paneErrors');
    const paneValid = document.getElementById('paneValid');
    const paneSkipped = document.getElementById('paneSkipped');
    const badgeCountErrors = document.getElementById('badgeCountErrors');
    const badgeCountValid = document.getElementById('badgeCountValid');
    const badgeCountSkipped = document.getElementById('badgeCountSkipped');

    // Tables
    const errorTableBody = document.getElementById('supplierErrorTableBody');
    const validTableBody = document.getElementById('supplierValidTableBody');
    const skippedTableBody = document.getElementById('supplierSkippedTableBody');
    const validSummaryCount = document.getElementById('validSummaryCount');

    let currentFile = null;
    let currentFileToken = null;
    let lastDetailedErrors = [];

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

    // Tab switching logic
    function switchTab(activeTab) {
        const tabs = [
            { btn: tabBtnErrors, pane: paneErrors, activeClass: 'border-red-600 text-red-700' },
            { btn: tabBtnValid, pane: paneValid, activeClass: 'border-emerald-600 text-emerald-700' },
            { btn: tabBtnSkipped, pane: paneSkipped, activeClass: 'border-amber-600 text-amber-700' }
        ];

        tabs.forEach(t => {
            if (!t.btn || !t.pane) return;
            if (t.btn === activeTab) {
                t.btn.classList.add(...t.activeClass.split(' '));
                t.btn.classList.remove('border-transparent', 'text-slate-500');
                t.pane.classList.remove('hidden');
            } else {
                t.btn.classList.remove(...t.activeClass.split(' '));
                t.btn.classList.add('border-transparent', 'text-slate-500');
                t.pane.classList.add('hidden');
            }
        });
    }

    if (tabBtnErrors) tabBtnErrors.addEventListener('click', () => switchTab(tabBtnErrors));
    if (tabBtnValid) tabBtnValid.addEventListener('click', () => switchTab(tabBtnValid));
    if (tabBtnSkipped) tabBtnSkipped.addEventListener('click', () => switchTab(tabBtnSkipped));

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
            btnValidate.innerHTML = '<span class="material-symbols-outlined notranslate text-[16px] animate-spin" translate="no">refresh</span><span>Validating...</span>';

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
                btnValidate.innerHTML = '<span class="material-symbols-outlined notranslate text-[16px]" translate="no">fact_check</span><span>Validate Spreadsheet</span>';

                if (!data.success && (!data.detailed_errors || data.detailed_errors.length === 0)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        text: data.message || 'File validation failed.',
                        confirmButtonColor: '#00288e'
                    });
                    return;
                }

                currentFileToken = data.file_token;
                lastDetailedErrors = data.detailed_errors || [];

                previewSection.classList.remove('hidden');

                const stats = data.stats || {};
                const totalRows = stats.total || data.total_rows || 0;
                const validRows = stats.valid || data.valid_rows_count || 0;
                const failedRows = stats.failed || data.error_rows_count || 0;
                const skippedRows = stats.skipped || 0;
                const totalIssues = stats.issues || lastDetailedErrors.length || 0;

                // Update Badges
                badgeCountErrors.textContent = totalIssues;
                badgeCountValid.textContent = validRows;
                badgeCountSkipped.textContent = skippedRows;
                validSummaryCount.textContent = `${validRows} Products Ready`;

                statsContainer.innerHTML = `
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200">
                        Total Rows: ${totalRows}
                    </span>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-xs">
                        Valid: ${validRows}
                    </span>
                    ${failedRows > 0 ? `
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-50 text-red-800 border border-red-200 font-bold text-xs">
                            Failed: ${failedRows} (${totalIssues} issues)
                        </span>
                    ` : ''}
                    ${skippedRows > 0 ? `
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-bold text-xs">
                            Skipped: ${skippedRows}
                        </span>
                    ` : ''}
                `;

                // Render Detailed Error Table
                if (lastDetailedErrors.length > 0) {
                    errorTableBody.innerHTML = lastDetailedErrors.map(err => {
                        const rowNum = err.row || '—';
                        const prodTitle = err.product ? escapeHtml(err.product) : '—';
                        const sku = err.sku ? escapeHtml(err.sku) : '—';
                        const field = err.field ? escapeHtml(err.field) : 'General';
                        const desc = err.error ? escapeHtml(err.error) : 'Validation failed';
                        const fix = err.suggested_fix ? escapeHtml(err.suggested_fix) : 'Review row specifications and correct value.';

                        return `
                            <tr class="hover:bg-red-50/40 transition-colors">
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-[11px] font-bold bg-red-100 text-red-800 border border-red-200">
                                        Row ${rowNum}
                                    </span>
                                </td>
                                <td class="py-2 px-3">
                                    <div class="font-bold text-slate-900">${prodTitle}</div>
                                    <div class="font-mono text-[10px] text-slate-500">SKU: ${sku}</div>
                                </td>
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        ${field}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-red-900 font-semibold leading-relaxed">
                                    ${desc}
                                </td>
                                <td class="py-2 px-3">
                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-900 border border-emerald-200 text-[11px] font-medium leading-relaxed">
                                        <span class="material-symbols-outlined notranslate text-[14px] text-emerald-600 shrink-0" translate="no">lightbulb</span>
                                        <span>${fix}</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');

                    // Default to error tab if issues exist
                    switchTab(tabBtnErrors);
                } else {
                    errorTableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="py-8 text-center text-emerald-700 font-bold">
                                <span class="material-symbols-outlined notranslate text-[32px] text-emerald-600 block mb-1" translate="no">task_alt</span>
                                All rows passed validation with zero errors!
                            </td>
                        </tr>
                    `;
                    switchTab(tabBtnValid);
                }

                // Render Valid Records Table
                const validRecords = data.preview_rows || data.valid_records || [];
                if (validRecords.length > 0) {
                    validTableBody.innerHTML = validRecords.map((r, idx) => {
                        const catNames = Array.isArray(r.category_names) ? r.category_names.join(', ') : (r.category_names || r.categories || '—');
                        return `
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2 px-3 text-slate-400 font-mono">${idx + 1}</td>
                                <td class="py-2 px-3"><span class="badge bg-slate-100 text-slate-700 font-mono font-bold px-2 py-0.5 rounded">Row ${r.row_num || idx + 2}</span></td>
                                <td class="py-2 px-3 font-bold text-slate-900">${escapeHtml(r.name || r.title || '—')}</td>
                                <td class="py-2 px-3 text-slate-600"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-blue-50 text-blue-800 border border-blue-200">${escapeHtml(catNames)}</span></td>
                                <td class="py-2 px-3 text-emerald-800 font-bold">$${parseFloat(r.min_price || 0).toFixed(2)}</td>
                                <td class="py-2 px-3 text-emerald-800 font-bold">$${parseFloat(r.max_price || 0).toFixed(2)}</td>
                                <td class="py-2 px-3 text-slate-700">${r.moq || r.moq_quantity || 1}</td>
                                <td class="py-2 px-3 font-mono text-slate-500">${escapeHtml(r.sku || '—')}</td>
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Ready</span>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    validTableBody.innerHTML = '<tr><td colspan="9" class="text-center py-6 text-slate-400">No valid products ready for import. Please fix row errors.</td></tr>';
                }

                // Render Skipped / Duplicate Records Table
                const skippedRecords = data.skipped_records || [];
                if (skippedRecords.length > 0) {
                    tabBtnSkipped.classList.remove('hidden');
                    skippedTableBody.innerHTML = skippedRecords.map(s => {
                        return `
                            <tr class="hover:bg-amber-50/30">
                                <td class="py-2 px-3"><span class="badge bg-amber-100 text-amber-800 font-mono font-bold px-2 py-0.5 rounded">Row ${s.row}</span></td>
                                <td class="py-2 px-3 font-bold text-slate-900">${escapeHtml(s.product || '—')}</td>
                                <td class="py-2 px-3 font-mono text-slate-600">${escapeHtml(s.sku || '—')}</td>
                                <td class="py-2 px-3 text-amber-900 font-semibold">${escapeHtml(s.message || 'Duplicate SKU or duplicate record')}</td>
                                <td class="py-2 px-3 text-slate-600">Provide a distinct SKU or leave blank to auto-generate.</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tabBtnSkipped.classList.add('hidden');
                }

                // Setup Commit Button
                if (validRows > 0) {
                    btnCommit.classList.remove('hidden');
                    btnCommit.disabled = false;
                    btnCommitText.textContent = `Confirm & Import (${validRows}) Valid Products`;
                } else {
                    btnCommit.classList.add('hidden');
                }

            } catch (err) {
                btnValidate.disabled = false;
                btnValidate.innerHTML = '<span class="material-symbols-outlined notranslate text-[16px]" translate="no">fact_check</span><span>Validate Spreadsheet</span>';
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Error',
                    text: err.message || 'An error occurred during file upload and validation.',
                    confirmButtonColor: '#00288e'
                });
            }
        });
    }

    // Client-side & Server-side Error Report Download Handlers
    function triggerDownload(content, fileName, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function generateCsvErrorReport(errors) {
        let csv = '\uFEFF'; // UTF-8 BOM
        csv += '"Excel Row","Product Name","SKU","Field with Error","Error Description","Suggested Fix"\n';
        errors.forEach(err => {
            const row = `"${(err.row || '').toString().replace(/"/g, '""')}"`;
            const prod = `"${(err.product || '').toString().replace(/"/g, '""')}"`;
            const sku = `"${(err.sku || '').toString().replace(/"/g, '""')}"`;
            const field = `"${(err.field || '').toString().replace(/"/g, '""')}"`;
            const desc = `"${(err.error || '').toString().replace(/"/g, '""')}"`;
            const fix = `"${(err.suggested_fix || '').toString().replace(/"/g, '""')}"`;
            csv += `${row},${prod},${sku},${field},${desc},${fix}\n`;
        });
        return csv;
    }

    const btnDownloadXlsx = document.getElementById('btnDownloadErrorsXlsx');
    if (btnDownloadXlsx) {
        btnDownloadXlsx.addEventListener('click', function () {
            if (currentFileToken) {
                window.location.href = `${baseUrl}/supplier/products/bulk-error-report?token=${encodeURIComponent(currentFileToken)}&format=xlsx`;
            } else if (lastDetailedErrors.length > 0) {
                const csvData = generateCsvErrorReport(lastDetailedErrors);
                triggerDownload(csvData, `bulk_upload_error_report_${new Date().toISOString().slice(0,10)}.csv`, 'text/csv;charset=utf-8;');
            }
        });
    }

    const btnDownloadCsv = document.getElementById('btnDownloadErrorsCsv');
    if (btnDownloadCsv) {
        btnDownloadCsv.addEventListener('click', function () {
            if (lastDetailedErrors.length > 0) {
                const csvData = generateCsvErrorReport(lastDetailedErrors);
                triggerDownload(csvData, `bulk_upload_error_report_${new Date().toISOString().slice(0,10)}.csv`, 'text/csv;charset=utf-8;');
            } else if (currentFileToken) {
                window.location.href = `${baseUrl}/supplier/products/bulk-error-report?token=${encodeURIComponent(currentFileToken)}&format=csv`;
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
                title: 'Confirm Catalog Import?',
                text: 'Valid products will be created and submitted to Super Admin for catalog approval. Any invalid rows will be excluded.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00288e',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Submit Products'
            });

            if (!confirm.isConfirmed) return;

            btnCommit.disabled = true;
            btnCommit.innerHTML = '<span class="material-symbols-outlined notranslate text-[16px] animate-spin" translate="no">refresh</span><span>Submitting to Admin...</span>';

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
                        text: data.message || 'Your products were submitted successfully.',
                        confirmButtonColor: '#00288e'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed',
                        text: data.message || 'Could not import products into the catalog.',
                        confirmButtonColor: '#00288e'
                    });
                    btnCommit.disabled = false;
                    btnCommit.innerHTML = '<span class="material-symbols-outlined notranslate text-[16px]" translate="no">check_circle</span><span>Try Again</span>';
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: err.message || 'Error occurred during product submission.',
                    confirmButtonColor: '#00288e'
                });
                btnCommit.disabled = false;
                btnCommit.innerHTML = '<span class="material-symbols-outlined notranslate text-[16px]" translate="no">check_circle</span><span>Try Again</span>';
            }
        });
    }
});
</script>
