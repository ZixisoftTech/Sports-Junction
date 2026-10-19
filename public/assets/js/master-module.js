/**
 * Sports Junction — Universal Master CRUD JavaScript Module
 * Powers Countries, States, Cities, Business Types, Price Units, and MOQ Units
 */

const MasterModule = (function () {
    let dtTable = null;
    let currentConfig = null;
    let pendingStatusChange = null;
    let pendingDelete = null;

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const id = 'toast_' + Date.now();
        const bgClass = type === 'success' ? 'text-bg-success' : (type === 'danger' ? 'text-bg-danger' : 'text-bg-warning');
        const icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'danger' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill');

        const html = `
            <div id="${id}" class="toast align-items-center ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center py-2 px-3">
                        <i class="bi ${icon} me-2 fs-6"></i>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(id);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function init(config) {
        currentConfig = config;
        const baseUrl = window.SportsJunction?.baseUrl || window.baseUrl || '';
        const entity = config.entity;

        // 1. Initialize DataTable
        dtTable = $(config.tableSelector).DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: `${baseUrl}/api/masters/${entity}/data`,
                type: 'GET',
                data: function (d) {
                    if (config.getExtraFilters) {
                        Object.assign(d, config.getExtraFilters());
                    }
                },
                error: function (xhr, error, thrown) {
                    showToast('Failed to load table data. Server returned error.', 'danger');
                    const proc = document.querySelector(config.tableSelector + '_processing');
                    if (proc) proc.style.display = 'none';
                }
            },
            columns: config.columns,
            order: config.order || [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total records)",
                emptyTable: "No master records found",
                zeroRecords: "No matching records found",
                processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading data...'
            },
            drawCallback: function () {
                bindTableEvents();
            }
        });

        // 2. Global Status Filter change
        if (config.statusFilterSelector) {
            $(config.statusFilterSelector).on('change', function () {
                dtTable.ajax.reload();
            });
        }

        // 3. Form Submission (Add / Edit)
        const form = document.querySelector(config.formSelector);
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitForm();
            });
        }

        // 4. Add New Button Click (Reset form)
        if (config.addNewBtnSelector) {
            $(config.addNewBtnSelector).on('click', function () {
                resetForm();
                const modal = new bootstrap.Modal(document.querySelector(config.modalSelector));
                modal.show();
            });
        }

        // 5. Setup Status Confirmation Listeners
        $('#confirmStatusChangeBtn').off('click').on('click', function () {
            executeStatusChange({ force: true });
        });

        $('#btnForceStatusBtn').off('click').on('click', function () {
            executeStatusChange({ force: true });
        });

        $('#btnCascadeStatusBtn').off('click').on('click', function () {
            executeStatusChange({ cascade: true });
        });

        // 6. Setup Delete Cascade Confirmation Listener
        $('#cascadeConfirmInput').off('input').on('input', function () {
            const val = $(this).val().trim();
            $('#executePermanentDeleteBtn').prop('disabled', val !== 'DELETE');
        });

        $('#executePermanentDeleteBtn').off('click').on('click', function () {
            executePermanentDelete();
        });

        // 7. Setup Export Triggers
        setupExportHandlers();
    }

    function bindTableEvents() {
        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;

        // Edit button
        $('.btn-edit-master').off('click').on('click', function () {
            const id = $(this).data('id');
            editRecord(id);
        });

        // Status Toggle button
        $('.btn-toggle-status').off('click').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name') || 'Record';
            const currentStatus = $(this).data('status');
            openStatusModal(id, name, currentStatus);
        });

        // Delete button
        $('.btn-delete-master').off('click').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name') || 'Record';
            openDeleteModal(id, name);
        });
    }

    function resetForm() {
        const form = document.querySelector(currentConfig.formSelector);
        if (!form) return;
        form.reset();
        form.classList.remove('was-validated');
        $(form).find('.is-invalid').removeClass('is-invalid');
        $(form).find('.invalid-feedback').remove();

        // Clear hidden ID field
        const idField = form.querySelector('[name="id"]');
        if (idField) idField.value = '';

        // Reset title
        const titleEl = document.querySelector(`${currentConfig.modalSelector} .modal-title`);
        if (titleEl) titleEl.innerText = `Add New ${currentConfig.entityTitle}`;

        if (currentConfig.onFormReset) {
            currentConfig.onFormReset();
        }
    }

    function editRecord(id) {
        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;

        fetch(`${baseUrl}/api/masters/${entity}/${id}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    showToast(res.message || 'Unable to fetch record', 'danger');
                    return;
                }

                resetForm();
                const form = document.querySelector(currentConfig.formSelector);
                const data = res.data;

                // Populate fields
                for (const key in data) {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = (data[key] == 1 || data[key] === true || data[key] === '1');
                        } else if (input.type !== 'file') {
                            input.value = data[key] !== null ? data[key] : '';
                        }
                    }
                }

                // Title
                const titleEl = document.querySelector(`${currentConfig.modalSelector} .modal-title`);
                if (titleEl) titleEl.innerText = `Edit ${currentConfig.entityTitle}: ${data.name || ''}`;

                if (currentConfig.onEditPopulate) {
                    currentConfig.onEditPopulate(data);
                }

                const modal = new bootstrap.Modal(document.querySelector(currentConfig.modalSelector));
                modal.show();
            })
            .catch(() => showToast('Error connecting to server', 'danger'));
    }

    function submitForm() {
        const form = document.querySelector(currentConfig.formSelector);
        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;
        const submitBtn = form.querySelector('button[type="submit"]');

        const formData = new FormData(form);
        // Explicitly set all checkboxes so unchecked checkboxes submit '0' and checked submit '1'
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (cb.name) {
                formData.set(cb.name, cb.checked ? '1' : '0');
            }
        });

        const payload = {};
        formData.forEach((val, key) => payload[key] = val);

        const id = payload.id;
        const isUpdate = !!id;
        const url = isUpdate ? `${baseUrl}/api/masters/${entity}/${id}` : `${baseUrl}/api/masters/${entity}`;
        const method = isUpdate ? 'PUT' : 'POST';

        // Clear previous validation errors
        $(form).find('.is-invalid').removeClass('is-invalid');
        $(form).find('.invalid-feedback').remove();

        submitBtn.disabled = true;
        const origBtnHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        const hasFiles = form.querySelector('input[type="file"]') && Array.from(form.querySelectorAll('input[type="file"]')).some(inp => inp.files && inp.files.length > 0);
        const useFormData = hasFiles || currentConfig.useFormData;

        let requestBody;
        let requestHeaders = { 'X-Requested-With': 'XMLHttpRequest' };

        const csrfToken = form.querySelector('input[name="_csrf"]')?.value || document.querySelector('input[name="_csrf"]')?.value;
        if (csrfToken) {
            requestHeaders['X-CSRF-Token'] = csrfToken;
        }

        if (useFormData) {
            requestBody = formData;
        } else {
            requestBody = JSON.stringify(payload);
            requestHeaders['Content-Type'] = 'application/json';
        }

        fetch(url, {
            method: 'POST',
            headers: requestHeaders,
            body: requestBody
        })
            .then(async res => {
                let data;
                try {
                    data = await res.json();
                } catch (e) {
                    const text = await res.text();
                    data = { message: text || `HTTP ${res.status}: Server request failed` };
                }
                return { status: res.status, data };
            })
            .then(({ status, data }) => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnHtml;

                if (status === 200 || status === 201) {
                    showToast(data.message || 'Saved successfully', 'success');
                    const modalEl = document.querySelector(currentConfig.modalSelector);
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    dtTable.ajax.reload(null, false);
                } else if (status === 422) {
                    if (data.errors) {
                        for (const field in data.errors) {
                            const input = form.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback';
                                errorDiv.innerText = data.errors[field][0];
                                input.parentNode.appendChild(errorDiv);
                            }
                        }
                    }
                    showToast(data.message || 'Validation failed. Please correct errors.', 'warning');
                } else {
                    showToast(data.message || 'An error occurred while saving.', 'danger');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnHtml;
                showToast(err && err.message ? err.message : 'Failed to contact server', 'danger');
            });
    }

    function openStatusModal(id, name, currentStatus) {
        pendingStatusChange = { id, name, currentStatus };
        const newStatus = currentStatus === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;

        $('#statusTargetName').text(`Update Status: ${name}`);
        $('#statusConfirmText').text(`Are you sure you want to mark this record as ${newStatus}?`);
        
        // Reset modal buttons and warning
        $('#statusDependencyWarning').addClass('d-none').empty();
        $('#btnCascadeStatusBtn').addClass('d-none');
        $('#btnForceStatusBtn').addClass('d-none');
        $('#confirmStatusChangeBtn').removeClass('d-none').text(`Yes, Mark as ${newStatus}`);

        // If deactivating, check whether active dependents exist to show convenient choices
        if (newStatus === 'INACTIVE') {
            fetch(`${baseUrl}/api/masters/${entity}/${id}/dependencies`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.dependencies && res.dependencies.has_active) {
                        const deps = res.dependencies;
                        const parts = [];
                        if (deps.states > 0) parts.push(`<strong>${deps.states} State(s)</strong>`);
                        if (deps.cities > 0) parts.push(`<strong>${deps.cities} City(ies)</strong>`);
                        if (deps.suppliers > 0) parts.push(`<strong>${deps.suppliers} Supplier(s)</strong>`);
                        if (deps.products > 0) parts.push(`<strong>${deps.products} Product(s)</strong>`);

                        const warningHtml = `
                            <div class="mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <strong>Active Dependents Detected:</strong></div>
                            <p class="mb-2">This ${currentConfig.entityTitle} is currently associated with ${parts.join(' and ')}.</p>
                            <div class="small text-muted">You can deactivate this record alone, or cascade deactivation to keep catalog filters consistent.</div>
                        `;

                        $('#statusDependencyWarning').removeClass('d-none').html(warningHtml);
                        $('#confirmStatusChangeBtn').addClass('d-none');
                        $('#btnForceStatusBtn').removeClass('d-none');
                        if (deps.states > 0 || deps.cities > 0) {
                            $('#btnCascadeStatusBtn').removeClass('d-none');
                        }
                    }
                })
                .catch(() => {});
        }

        const modal = new bootstrap.Modal(document.getElementById('statusConfirmModal'));
        modal.show();
    }

    function executeStatusChange(options = {}) {
        if (!pendingStatusChange) return;

        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;
        const { id } = pendingStatusChange;

        // Disable all buttons in modal footer while processing
        const modalFooter = document.querySelector('#statusConfirmModal .modal-footer');
        const buttons = modalFooter.querySelectorAll('button');
        buttons.forEach(b => b.disabled = true);

        fetch(`${baseUrl}/api/masters/${entity}/${id}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                force: options.force || false,
                cascade: options.cascade || false
            })
        })
            .then(res => res.json())
            .then(res => {
                buttons.forEach(b => b.disabled = false);

                if (res.success) {
                    showToast(res.message, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('statusConfirmModal'));
                    if (modal) modal.hide();
                    dtTable.ajax.reload(null, false);
                } else if (res.has_dependencies) {
                    // Show options if not already visible
                    $('#statusDependencyWarning').removeClass('d-none').html(`
                        <div class="mb-1"><strong><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Active Dependents Found:</strong></div>
                        <p class="mb-2">${res.message}</p>
                    `);
                    $('#confirmStatusChangeBtn').addClass('d-none');
                    $('#btnForceStatusBtn').removeClass('d-none');
                    $('#btnCascadeStatusBtn').removeClass('d-none');
                    showToast(res.message, 'warning');
                } else {
                    showToast(res.message || 'Status change failed', 'danger');
                }
            })
            .catch(() => {
                buttons.forEach(b => b.disabled = false);
                showToast('Network error while updating status', 'danger');
            });
    }

    function openDeleteModal(id, name) {
        pendingDelete = { id, name, cascade: false };
        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;

        $('#deleteModalLoading').removeClass('d-none');
        $('#deleteModalContent').addClass('d-none');
        $('#deleteProtectedAlert').addClass('d-none');
        $('#deleteCascadeSection').addClass('d-none');
        $('#deleteCleanAlert').addClass('d-none');
        $('#cascadeConfirmInput').val('');
        $('#executePermanentDeleteBtn').prop('disabled', true);

        $('#deleteTargetTitle').text(`Permanent Delete: ${name}`);

        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();

        // Check foreign-key and business dependencies
        fetch(`${baseUrl}/api/masters/${entity}/${id}/dependencies`)
            .then(res => res.json())
            .then(res => {
                $('#deleteModalLoading').addClass('d-none');
                $('#deleteModalContent').removeClass('d-none');

                if (!res.success) {
                    showToast('Unable to inspect dependencies', 'danger');
                    return;
                }

                const deps = res.dependencies;

                // Case 1: Protected business entities exist (Suppliers, Products) -> BLOCK!
                if (deps.has_protected) {
                    $('#deleteProtectedAlert').removeClass('d-none');
                    let msg = `Cannot delete '${name}' because it is directly referenced by: `;
                    const parts = [];
                    if (deps.suppliers > 0) parts.push(`${deps.suppliers} Supplier/Vendor Account(s)`);
                    if (deps.products > 0) parts.push(`${deps.products} Marketplace Product(s)`);
                    msg += parts.join(' and ') + '. Deletion is rejected to protect business data.';
                    $('#deleteProtectedMsg').text(msg);
                    $('#executePermanentDeleteBtn').prop('disabled', true);
                    pendingDelete.canDelete = false;
                }
                // Case 2: Sub-masters exist (e.g. States / Cities) -> Require typing "DELETE"
                else if (deps.can_cascade) {
                    $('#deleteCascadeSection').removeClass('d-none');
                    const list = $('#deleteDependencyList').empty();
                    if (deps.states > 0) list.append(`<li>${deps.states} State record(s)</li>`);
                    if (deps.cities > 0) list.append(`<li>${deps.cities} City record(s)</li>`);
                    pendingDelete.cascade = true;
                    pendingDelete.canDelete = true;
                    // Button remains disabled until user types DELETE
                }
                // Case 3: Clean delete (Zero dependencies)
                else {
                    $('#deleteCleanAlert').removeClass('d-none');
                    $('#executePermanentDeleteBtn').prop('disabled', false);
                    pendingDelete.cascade = false;
                    pendingDelete.canDelete = true;
                }
            })
            .catch(() => {
                $('#deleteModalLoading').addClass('d-none');
                showToast('Failed to check dependencies', 'danger');
            });
    }

    function executePermanentDelete() {
        if (!pendingDelete || !pendingDelete.canDelete) return;

        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;
        const { id, cascade } = pendingDelete;
        const confirmText = $('#cascadeConfirmInput').val().trim();
        const btn = document.getElementById('executePermanentDeleteBtn');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...';

        fetch(`${baseUrl}/api/masters/${entity}/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                cascade: cascade,
                confirm_text: confirmText
            })
        })
            .then(res => res.json())
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Permanently Delete';

                if (res.success) {
                    showToast(res.message, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                    if (modal) modal.hide();
                    dtTable.ajax.reload(null, false);
                } else {
                    showToast(res.message || 'Permanent deletion failed', 'danger');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Permanently Delete';
                showToast('Network error during deletion', 'danger');
            });
    }

    function setupExportHandlers() {
        const baseUrl = window.SportsJunction.baseUrl;
        const entity = currentConfig.entity;

        $('.btn-export-action').off('click').on('click', function (e) {
            e.preventDefault();
            const format = $(this).data('format'); // csv, excel, pdf
            const scope = $(this).data('scope');   // current, all

            let exportUrl = `${baseUrl}/api/masters/${entity}/export?format=${format}&scope=${scope}`;

            if (scope === 'current' && dtTable) {
                const searchVal = dtTable.search();
                if (searchVal) exportUrl += `&search=${encodeURIComponent(searchVal)}`;

                if (currentConfig.statusFilterSelector) {
                    const stVal = $(currentConfig.statusFilterSelector).val();
                    if (stVal) exportUrl += `&status=${encodeURIComponent(stVal)}`;
                }
            }

            if (format === 'pdf') {
                window.open(exportUrl, '_blank');
            } else {
                window.location.href = exportUrl;
            }
        });
    }

    return {
        init: init,
        reload: function () { if (dtTable) dtTable.ajax.reload(null, false); },
        showToast: showToast
    };
})();
