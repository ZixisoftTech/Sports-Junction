<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-patch-check-fill text-primary me-2"></i>Product Certificates Management</h4>
        <p class="text-muted small mb-0">Manage authorized sports equipment laboratory compliance marks, safety standards, and factory certifications (e.g. CE, FIFA Pro, ISO, BCCI).</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <!-- Add New Button -->
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewCertificate">
            <i class="bi bi-plus-lg me-1"></i> Add Certificate
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-6">
                <h6 class="m-0 fw-bold text-dark">Authorized Product Certificates</h6>
            </div>
            <div class="col-sm-6 d-flex justify-content-sm-end align-items-center gap-2">
                <label for="certStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="certStatusFilter" class="form-select form-select-sm" style="max-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="certificatesTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 60px;">Icon</th>
                        <th>Certificate Name</th>
                        <th style="width: 120px;">Code / Tag</th>
                        <th>Issuing Authority</th>
                        <th style="width: 110px;">Assigned Prods</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 130px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Certificate Modal -->
<div class="modal fade" id="certificateModal" tabindex="-1" aria-labelledby="certificateModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="certificateModalLabel">Add New Product Certificate</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="certificateForm" novalidate>
                <?= CSRF::field() ?>
                <input type="hidden" name="id" id="certId" value="">

                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="certName" class="form-label small fw-semibold">Certificate Standard Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="certName" name="name" placeholder="e.g. CE Mark Compliance, FIFA Quality Pro" required maxlength="150">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="certCode" class="form-label small fw-semibold">Code / Standard Identifier</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" id="certCode" name="code" placeholder="e.g. CE, FIFA-PRO, ISO-9001" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label for="certIcon" class="form-label small fw-semibold">Icon Symbol</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white" id="certIconPreviewBox">
                                    <span class="material-symbols-outlined text-primary fs-5" id="certIconPreview">verified</span>
                                </span>
                                <input type="text" class="form-control" id="certIcon" name="icon" value="verified" placeholder="verified, sports_soccer...">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="certAuthority" class="form-label small fw-semibold">Issuing Authority / Testing Lab</label>
                        <input type="text" class="form-control form-control-sm" id="certAuthority" name="issuing_authority" placeholder="e.g. European Conformity Body, FIFA Lab" maxlength="150">
                    </div>

                    <div class="mb-3">
                        <label for="certDescription" class="form-label small fw-semibold">Description / Scope</label>
                        <textarea class="form-control form-control-sm" id="certDescription" name="description" rows="3" placeholder="Explain the compliance scope, test standards, safety guarantees..."></textarea>
                    </div>

                    <div class="mb-2">
                        <label for="certStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="certStatus" name="status" required>
                            <option value="ACTIVE">Active (Available for Product Assignment)</option>
                            <option value="INACTIVE">Inactive (Disabled)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sj-primary btn-sm px-3" id="btnSaveCertificate">
                        <i class="bi bi-check-lg me-1"></i> Save Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction.baseUrl;
    const certModalEl = document.getElementById('certificateModal');
    const certModal = new bootstrap.Modal(certModalEl);
    const form = document.getElementById('certificateForm');
    const modalTitle = document.getElementById('certificateModalLabel');
    const iconInput = document.getElementById('certIcon');
    const iconPreview = document.getElementById('certIconPreview');

    if (iconInput && iconPreview) {
        iconInput.addEventListener('input', function() {
            iconPreview.textContent = this.value.trim() || 'verified';
        });
    }

    const table = $('#certificatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: `${baseUrl}/api/masters/product-certificates/data`,
            type: 'GET',
            data: function (d) {
                d.status = $('#certStatusFilter').val();
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            { 
                data: 'icon', 
                width: '60px',
                orderable: false,
                render: function(data) {
                    const ic = data || 'verified';
                    if (ic.startsWith('/') || ic.startsWith('http')) {
                        return `<img src="${baseUrl}${ic}" style="width:24px;height:24px;object-fit:contain;">`;
                    }
                    return `<span class="material-symbols-outlined text-primary fs-5">${ic}</span>`;
                }
            },
            {
                data: 'name',
                render: function (data, type, row) {
                    return `<strong>${data}</strong>${row.description ? `<div class="text-muted small text-truncate" style="max-width: 280px;">${row.description}</div>` : ''}`;
                }
            },
            { 
                data: 'code', 
                width: '120px',
                render: function(data) {
                    return data ? `<span class="badge bg-light text-dark border font-monospace">${data}</span>` : '—';
                }
            },
            { data: 'issuing_authority', defaultContent: '—' },
            {
                data: 'assigned_products_count',
                width: '110px',
                render: function (data) {
                    return `<span class="badge bg-primary-subtle text-primary border">${data || 0} Products</span>`;
                }
            },
            {
                data: 'status',
                width: '100px',
                render: function (data, type, row) {
                    const isActive = data === 'ACTIVE';
                    return `<span class="badge ${isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border'} px-2 py-1 cursor-pointer btn-toggle-status" data-id="${row.id}" data-status="${data}" title="Click to toggle status">
                        <i class="bi ${isActive ? 'bi-check-circle-fill' : 'bi-dash-circle-fill'} me-1"></i>${data}
                    </span>`;
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit" data-id="${row.id}" title="Edit Certificate">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="${row.id}" data-name="${row.name}" title="Delete Certificate">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    $('#certStatusFilter').on('change', function () {
        table.ajax.reload();
    });

    document.getElementById('btnAddNewCertificate').addEventListener('click', function () {
        form.reset();
        document.getElementById('certId').value = '';
        modalTitle.textContent = 'Add New Product Certificate';
        if (iconPreview) iconPreview.textContent = 'verified';
        certModal.show();
    });

    // Edit
    $('#certificatesTable').on('click', '.btn-edit', function () {
        const id = $(this).data('id');
        fetch(`${baseUrl}/api/masters/product-certificates/${id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const d = res.data;
                    document.getElementById('certId').value = d.id;
                    document.getElementById('certName').value = d.name || '';
                    document.getElementById('certCode').value = d.code || '';
                    document.getElementById('certAuthority').value = d.issuing_authority || '';
                    document.getElementById('certDescription').value = d.description || '';
                    document.getElementById('certIcon').value = d.icon || 'verified';
                    document.getElementById('certStatus').value = d.status || 'ACTIVE';
                    if (iconPreview) iconPreview.textContent = d.icon || 'verified';

                    modalTitle.textContent = `Edit Certificate: ${d.name}`;
                    certModal.show();
                } else {
                    alert(res.message || 'Failed to load details.');
                }
            });
    });

    // Form Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('certId').value;
        const isEdit = !!id;
        const url = isEdit ? `${baseUrl}/api/masters/product-certificates/${id}` : `${baseUrl}/api/masters/product-certificates`;
        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                certModal.hide();
                table.ajax.reload(null, false);
            } else {
                alert(res.message || 'Validation error while saving.');
            }
        })
        .catch(err => {
            alert('Server error while saving.');
        });
    });

    // Toggle Status
    $('#certificatesTable').on('click', '.btn-toggle-status', function () {
        const id = $(this).data('id');
        const curr = $(this).data('status');
        const next = curr === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        if (!confirm(`Are you sure you want to change certificate status to ${next}? Inactive certificates will not be available for new assignments.`)) return;

        fetch(`${baseUrl}/api/masters/product-certificates/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= CSRF::getToken() ?>'
            },
            body: JSON.stringify({ status: next })
        })
        .then(r => r.json())
        .then(res => {
            table.ajax.reload(null, false);
        });
    });

    // Delete
    $('#certificatesTable').on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        if (!confirm(`Are you sure you want to permanently delete certificate "${name}"? This will safely remove it from all assigned products.`)) return;

        fetch(`${baseUrl}/api/masters/product-certificates/${id}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '<?= CSRF::getToken() ?>'
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                table.ajax.reload(null, false);
            } else {
                alert(res.message || 'Failed to delete certificate.');
            }
        });
    });
});
</script>
