<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-tags-fill text-primary me-2"></i>Supplier Certification Tags</h4>
        <p class="text-muted small mb-0">Manage authorized manufacturer credibility badges, plant audits, and export credentials (e.g. ISO 9001:2015, SEDEX SMETA, BSCI, Star Export House).</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewTag">
            <i class="bi bi-plus-lg me-1"></i> Add Certification Tag
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-6">
                <h6 class="m-0 fw-bold text-dark">Authorized Supplier Credibility Tags</h6>
            </div>
            <div class="col-sm-6 d-flex justify-content-sm-end align-items-center gap-2">
                <label for="tagStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="tagStatusFilter" class="form-select form-select-sm" style="max-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="tagsTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 60px;">Icon</th>
                        <th>Tag Name &amp; Description</th>
                        <th style="width: 120px;">Code / Tag</th>
                        <th style="width: 110px;">Badge Style</th>
                        <th style="width: 110px;">Assigned Factories</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 130px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Tag Modal -->
<div class="modal fade" id="tagModal" tabindex="-1" aria-labelledby="tagModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="tagModalLabel">Add New Supplier Certification Tag</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="tagForm" novalidate>
                <?= CSRF::field() ?>
                <input type="hidden" name="id" id="tagId" value="">

                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="tagName" class="form-label small fw-semibold">Tag Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="tagName" name="name" placeholder="e.g. ISO 9001:2015 Certified Factory" required maxlength="150">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="tagCode" class="form-label small fw-semibold">Short Identifier Code</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" id="tagCode" name="code" placeholder="e.g. ISO-9001, BSCI, SEDEX" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label for="tagBadgeColor" class="form-label small fw-semibold">Theme Color</label>
                            <select class="form-select form-select-sm" id="tagBadgeColor" name="badge_color">
                                <option value="teal">Teal (Verified Quality)</option>
                                <option value="blue">Blue (International Audit)</option>
                                <option value="indigo">Indigo (Social Compliance)</option>
                                <option value="emerald">Emerald (Export Certified)</option>
                                <option value="amber">Amber (Top Rated / Gold)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tagIconFile" class="form-label small fw-semibold">Certification Tag Icon / Badge Image</label>
                        <input type="hidden" name="icon" id="tagIcon" value="">
                        <div class="d-flex align-items-center gap-2 p-2 border rounded bg-light">
                            <div class="border rounded bg-white d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 48px; height: 48px;" id="tagIconPreviewBox">
                                <img id="tagIconImgPreview" src="" class="d-none object-fit-contain" style="max-width: 100%; max-height: 100%;" alt="Tag Icon Preview">
                                <span class="material-symbols-outlined text-teal-700 fs-4" id="tagIconPreview">verified</span>
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" class="form-control form-control-sm" id="tagIconFile" name="icon_file" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                                <div class="form-text text-muted" style="font-size: 11px;">
                                    PNG, SVG, JPG, WEBP &bull; 1:1 Aspect Ratio (64x64px, Max 2MB)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tagDescription" class="form-label small fw-semibold">Description / Verification Standard</label>
                        <textarea class="form-control form-control-sm" id="tagDescription" name="description" rows="3" placeholder="Summary of audit requirements, worker rights, inspection protocols..."></textarea>
                    </div>

                    <div class="mb-2">
                        <label for="tagStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="tagStatus" name="status" required>
                            <option value="ACTIVE">Active (Available for Supplier Assignment)</option>
                            <option value="INACTIVE">Inactive (Disabled)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sj-primary btn-sm px-3" id="btnSaveTag">
                        <i class="bi bi-check-lg me-1"></i> Save Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction.baseUrl;
    const tagModalEl = document.getElementById('tagModal');
    const tagModal = new bootstrap.Modal(tagModalEl);
    const form = document.getElementById('tagForm');
    const modalTitle = document.getElementById('tagModalLabel');
    const iconFileInput = document.getElementById('tagIconFile');
    const iconImgPreview = document.getElementById('tagIconImgPreview');
    const iconPreview = document.getElementById('tagIconPreview');
    const iconHiddenInput = document.getElementById('tagIcon');

    if (iconFileInput) {
        iconFileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (iconImgPreview) {
                        iconImgPreview.src = e.target.result;
                        iconImgPreview.classList.remove('d-none');
                    }
                    if (iconPreview) iconPreview.classList.add('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    const table = $('#tagsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: `${baseUrl}/api/masters/certification-tags/data`,
            type: 'GET',
            data: function (d) {
                d.status = $('#tagStatusFilter').val();
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            { 
                data: 'icon', 
                width: '60px',
                orderable: false,
                render: function(data) {
                    const ic = data || '';
                    if (ic.startsWith('/') || ic.startsWith('http') || ic.includes('.') || ic.includes('uploads')) {
                        const imgUrl = window.SportsJunction ? window.SportsJunction.formatImageUrl(ic) : (baseUrl + ic);
                        return `<img src="${imgUrl}" style="width:28px;height:28px;object-fit:contain;" class="rounded border p-0.5 bg-white shadow-2xs" alt="Tag Icon">`;
                    }
                    return `<span class="material-symbols-outlined text-teal-700 fs-5">${ic || 'verified'}</span>`;
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
            {
                data: 'badge_color',
                width: '110px',
                render: function(data) {
                    const color = data || 'teal';
                    return `<span class="badge bg-${color === 'teal' ? 'info' : (color === 'emerald' ? 'success' : (color === 'amber' ? 'warning text-dark' : 'primary'))}-subtle text-dark border text-capitalize">${color}</span>`;
                }
            },
            {
                data: 'assigned_suppliers_count',
                width: '110px',
                render: function (data) {
                    return `<span class="badge bg-teal-subtle text-teal border">${data || 0} Factories</span>`;
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
                            <button type="button" class="btn btn-outline-primary btn-edit" data-id="${row.id}" title="Edit Tag">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="${row.id}" data-name="${row.name}" title="Delete Tag">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    $('#tagStatusFilter').on('change', function () {
        table.ajax.reload();
    });

    document.getElementById('btnAddNewTag').addEventListener('click', function () {
        form.reset();
        document.getElementById('tagId').value = '';
        if (iconHiddenInput) iconHiddenInput.value = '';
        if (iconFileInput) iconFileInput.value = '';
        if (iconImgPreview) {
            iconImgPreview.src = '';
            iconImgPreview.classList.add('d-none');
        }
        if (iconPreview) {
            iconPreview.classList.remove('d-none');
            iconPreview.textContent = 'verified';
        }
        modalTitle.textContent = 'Add New Certification Tag';
        tagModal.show();
    });

    // Edit
    $('#tagsTable').on('click', '.btn-edit', function () {
        const id = $(this).data('id');
        fetch(`${baseUrl}/api/masters/certification-tags/${id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const d = res.data;
                    document.getElementById('tagId').value = d.id;
                    document.getElementById('tagName').value = d.name || '';
                    document.getElementById('tagCode').value = d.code || '';
                    document.getElementById('tagBadgeColor').value = d.badge_color || 'teal';
                    document.getElementById('tagDescription').value = d.description || '';
                    document.getElementById('tagStatus').value = d.status || 'ACTIVE';

                    const iconVal = d.icon || '';
                    if (iconHiddenInput) iconHiddenInput.value = iconVal;
                    if (iconFileInput) iconFileInput.value = '';

                    const isImg = iconVal.startsWith('/') || iconVal.startsWith('http') || iconVal.includes('.') || iconVal.includes('uploads');
                    if (isImg && iconImgPreview) {
                        const imgUrl = window.SportsJunction ? window.SportsJunction.formatImageUrl(iconVal) : (baseUrl + iconVal);
                        iconImgPreview.src = imgUrl;
                        iconImgPreview.classList.remove('d-none');
                        if (iconPreview) iconPreview.classList.add('d-none');
                    } else {
                        if (iconImgPreview) {
                            iconImgPreview.src = '';
                            iconImgPreview.classList.add('d-none');
                        }
                        if (iconPreview) {
                            iconPreview.classList.remove('d-none');
                            iconPreview.textContent = iconVal || 'verified';
                        }
                    }

                    modalTitle.textContent = `Edit Tag: ${d.name}`;
                    tagModal.show();
                } else {
                    alert(res.message || 'Failed to load details.');
                }
            });
    });

    // Form Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('tagId').value;
        const isEdit = !!id;
        const url = isEdit ? `${baseUrl}/api/masters/certification-tags/${id}` : `${baseUrl}/api/masters/certification-tags`;
        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                tagModal.hide();
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
    $('#tagsTable').on('click', '.btn-toggle-status', function () {
        const id = $(this).data('id');
        const curr = $(this).data('status');
        const next = curr === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        if (!confirm(`Are you sure you want to change tag status to ${next}? Inactive tags will not be available for new factory assignments.`)) return;

        fetch(`${baseUrl}/api/masters/certification-tags/${id}/status`, {
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
    $('#tagsTable').on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        if (!confirm(`Are you sure you want to permanently delete certification tag "${name}"? This will safely remove it from all assigned factories.`)) return;

        fetch(`${baseUrl}/api/masters/certification-tags/${id}/delete`, {
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
                alert(res.message || 'Failed to delete tag.');
            }
        });
    });
});
</script>
