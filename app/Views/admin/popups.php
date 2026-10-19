<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>

<div class="container-fluid py-3 px-2 px-md-4">
    <!-- Header Row -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="text-decoration-none">Admin</a></li>
                    <li class="breadcrumb-item">Website &amp; CMS</li>
                    <li class="breadcrumb-item active" aria-current="page">Autoload Popups</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold text-dark mb-0">Website Autoload Popups</h2>
            <p class="text-muted small mb-0">Manage promotional image popups automatically displayed to visitors upon landing on the site.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5" id="btnAddNewPopup">
                <i class="bi bi-plus-circle fs-6"></i>
                <span class="fw-semibold">Add New Popup</span>
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-sm-auto d-flex align-items-center gap-2">
                    <label for="popupStatusFilter" class="small fw-semibold text-secondary mb-0">Status:</label>
                    <select id="popupStatusFilter" class="form-select form-select-sm" style="width: 140px;">
                        <option value="">All Statuses</option>
                        <option value="ACTIVE" selected>Active Only</option>
                        <option value="INACTIVE">Inactive Only</option>
                    </select>
                </div>
                <div class="col-12 col-sm-auto text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    The active popup with the lowest <strong>Display Order</strong> is shown once per visitor session.
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="sitePopupsTable" class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
                    <thead class="table-light text-secondary small text-uppercase fw-bold border-bottom">
                        <tr>
                            <th style="width: 50px;">#ID</th>
                            <th style="width: 80px;">Image</th>
                            <th>Popup Title</th>
                            <th>Destination URL</th>
                            <th style="width: 90px;" class="text-center">Delay</th>
                            <th style="width: 110px;" class="text-center">Display Order</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 100px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Popup Modal -->
<div class="modal fade" id="sitePopupModal" tabindex="-1" aria-labelledby="sitePopupModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="sitePopupForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="id" id="popupId">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

                <div class="modal-header border-bottom bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="sitePopupModalLabel">Add New Site Popup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Popup Title -->
                        <div class="col-12">
                            <label for="popupTitle" class="form-label small fw-semibold">Popup Title / Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="popupTitle" name="title" required placeholder="e.g. Sourcing Discount Campaign 2026">
                            <div class="invalid-feedback">Please enter a popup campaign title.</div>
                        </div>

                        <!-- Target Destination Link -->
                        <div class="col-12 col-md-6">
                            <label for="popupTargetUrl" class="form-label small fw-semibold">Destination URL (Optional)</label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="popupTargetUrl" name="target_url" placeholder="/products or https://...">
                            <div class="text-muted small" style="font-size: 11px;">Where visitors are redirected when clicking the popup image.</div>
                        </div>

                        <!-- Autoload Delay -->
                        <div class="col-12 col-md-3">
                            <label for="popupDelay" class="form-label small fw-semibold">Delay (Seconds) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="popupDelay" name="delay_seconds" value="2" min="0" max="60" required>
                            <div class="text-muted small" style="font-size: 11px;">Default: 2 seconds after page load.</div>
                        </div>

                        <!-- Display Order -->
                        <div class="col-12 col-md-3">
                            <label for="popupOrder" class="form-label small fw-semibold">Display Order <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="popupOrder" name="display_order" value="1" min="0" required>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-md-6">
                            <label for="popupStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="popupStatus" name="status" required>
                                <option value="ACTIVE" selected>ACTIVE (Visible to visitors)</option>
                                <option value="INACTIVE">INACTIVE (Disabled)</option>
                            </select>
                        </div>

                        <!-- Schedule (Optional) -->
                        <div class="col-12 col-md-3">
                            <label for="popupStartDate" class="form-label small fw-semibold">Start Date (Optional)</label>
                            <input type="date" class="form-control form-control-sm" id="popupStartDate" name="start_date">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="popupEndDate" class="form-label small fw-semibold">End Date (Optional)</label>
                            <input type="date" class="form-control form-control-sm" id="popupEndDate" name="end_date">
                        </div>

                        <!-- Image Upload & Preview Card -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Popup Banner Creative <span class="text-danger">*</span></label>
                            <div class="card border rounded-3 p-3 bg-light">
                                <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                                    <div class="flex-shrink-0 bg-white rounded overflow-hidden shadow-xs border text-center p-1" style="width: 120px; height: 120px; position: relative;">
                                        <img id="popupImagePreview" src="" alt="Preview" class="w-100 h-100 object-fit-contain d-none">
                                        <div id="popupImagePlaceholder" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image fs-3"></i>
                                            <span style="font-size: 10px;">Creative Preview</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 w-100">
                                        <div class="mb-2">
                                            <label for="popupImageFile" class="form-label small fw-semibold mb-1">Upload Creative File (JPG, PNG, WEBP):</label>
                                            <input type="file" class="form-control form-control-sm" id="popupImageFile" name="image_file" accept="image/jpeg,image/png,image/webp,image/jpg">
                                            <div class="text-muted small" style="font-size: 11px;">Recommended size: 600×600px or 800×600px. Max 8MB.</div>
                                        </div>
                                        <div>
                                            <label for="popupImageUrl" class="form-label small text-muted mb-1" style="font-size: 11px;">Or Direct Image URL / Stored Path:</label>
                                            <input type="text" class="form-control form-control-sm font-monospace" id="popupImageUrl" name="image" placeholder="https://... or /uploads/popups/..." style="font-size: 12px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light px-4 py-2.5">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSavePopup">
                        <i class="bi bi-check-lg me-1"></i> Save Popup
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?= $baseUrl ?>';
    const csrfToken = '<?= CSRF::getToken() ?>';

    // Image preview handling
    const fileInput = document.getElementById('popupImageFile');
    const urlInput = document.getElementById('popupImageUrl');
    const previewImg = document.getElementById('popupImagePreview');
    const placeholder = document.getElementById('popupImagePlaceholder');

    function updatePreview(src) {
        if (src && src.trim() !== '') {
            let finalSrc = src.trim();
            if (!finalSrc.startsWith('http') && !finalSrc.startsWith('/') && !finalSrc.startsWith('data:')) {
                finalSrc = `${baseUrl}/${finalSrc}`;
            }
            previewImg.src = finalSrc;
            previewImg.classList.remove('d-none');
            placeholder.classList.add('d-none');
        } else {
            previewImg.src = '';
            previewImg.classList.add('d-none');
            placeholder.classList.remove('d-none');
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    if (urlInput) {
        urlInput.addEventListener('input', function () {
            if (!fileInput.files || fileInput.files.length === 0) {
                updatePreview(this.value.trim());
            }
        });
    }

    // Initialize DataTable with stateSave and display_order ASC
    const popupsTable = $('#sitePopupsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        stateSave: true,
        ajax: {
            url: `${baseUrl}/api/admin/popups/data`,
            type: 'GET',
            data: function (d) {
                d.status = $('#popupStatusFilter').val();
            },
            error: function (xhr) {
                console.error('Error fetching site popups:', xhr);
            }
        },
        columns: [
            {
                data: 'id',
                className: 'text-center text-muted font-monospace',
                render: function (data) {
                    return `<strong>#${data}</strong>`;
                }
            },
            {
                data: 'image',
                orderable: false,
                render: function (data) {
                    const src = data.startsWith('http') ? data : `${baseUrl}/${data.replace(/^\//, '')}`;
                    return `<img src="${src}" class="rounded object-fit-contain shadow-xs border bg-white p-1" style="width: 60px; height: 60px;" alt="Popup" onerror="this.src='https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=200&q=80';">`;
                }
            },
            {
                data: 'title',
                render: function (data, type, row) {
                    let dates = '';
                    if (row.start_date || row.end_date) {
                        dates = `<div class="text-muted" style="font-size: 10px;"><i class="bi bi-calendar3 me-1"></i>${row.start_date || 'Any'} &rarr; ${row.end_date || 'Any'}</div>`;
                    }
                    return `
                        <div>
                            <span class="fw-bold text-dark">${escapeHtml(data)}</span>
                            ${dates}
                        </div>
                    `;
                }
            },
            {
                data: 'target_url',
                render: function (data) {
                    if (!data) return '<span class="text-muted small">No Link (Display Only)</span>';
                    return `<a href="${data}" target="_blank" class="text-decoration-none small text-truncate d-inline-block font-monospace" style="max-width: 220px;">${escapeHtml(data)}</a>`;
                }
            },
            {
                data: 'delay_seconds',
                className: 'text-center',
                render: function (data) {
                    return `<span class="badge bg-light text-dark border">${data}s</span>`;
                }
            },
            {
                data: 'display_order',
                className: 'text-center',
                render: function (data) {
                    return `<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">${data}</span>`;
                }
            },
            {
                data: 'status',
                className: 'text-center',
                render: function (data, type, row) {
                    const isActive = data === 'ACTIVE';
                    const badgeClass = isActive ? 'bg-success' : 'bg-danger';
                    return `
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <span class="badge ${badgeClass}">${data}</span>
                            <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-toggle-popup-status" data-id="${row.id}" data-status="${data}" title="Toggle status">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                    `;
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit-popup" data-id="${row.id}" title="Edit Popup">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-popup" data-id="${row.id}" data-title="${escapeHtml(row.title)}" title="Delete Popup">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[5, 'asc']] // Default by display_order ascending
    });

    $('#popupStatusFilter').on('change', function () {
        popupsTable.ajax.reload();
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Add New Popup
    const popupModalEl = document.getElementById('sitePopupModal');
    const popupModal = new bootstrap.Modal(popupModalEl);
    const form = document.getElementById('sitePopupForm');

    document.getElementById('btnAddNewPopup').addEventListener('click', function () {
        form.reset();
        document.getElementById('popupId').value = '';
        document.getElementById('popupOrder').value = '1';
        document.getElementById('popupDelay').value = '2';
        document.getElementById('popupStatus').value = 'ACTIVE';
        document.getElementById('sitePopupModalLabel').innerText = 'Add New Site Popup';
        updatePreview('');
        popupModal.show();
    });

    // Form Submit (Add / Edit)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('popupId').value;
        const isUpdate = !!id;
        const url = isUpdate ? `${baseUrl}/api/admin/popups/${id}` : `${baseUrl}/api/admin/popups`;

        const submitBtn = document.getElementById('btnSavePopup');
        submitBtn.disabled = true;
        const origBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnText;

            if (data.success) {
                popupModal.hide();
                popupsTable.ajax.reload(null, false);
                alert(data.message || 'Saved successfully');
            } else {
                alert(data.message || 'Validation error occurred.');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnText;
            alert('Server communication error.');
        });
    });

    // Edit Popup Button
    $('#sitePopupsTable').on('click', '.btn-edit-popup', function () {
        const id = $(this).data('id');
        fetch(`${baseUrl}/api/admin/popups/${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const p = res.data;
                    document.getElementById('popupId').value = p.id;
                    document.getElementById('popupTitle').value = p.title || '';
                    document.getElementById('popupImageUrl').value = p.image || '';
                    document.getElementById('popupTargetUrl').value = p.target_url || '';
                    document.getElementById('popupDelay').value = p.delay_seconds ?? 2;
                    document.getElementById('popupOrder').value = p.display_order ?? 0;
                    document.getElementById('popupStatus').value = p.status || 'ACTIVE';
                    document.getElementById('popupStartDate').value = p.start_date || '';
                    document.getElementById('popupEndDate').value = p.end_date || '';

                    fileInput.value = '';
                    updatePreview(p.image);

                    document.getElementById('sitePopupModalLabel').innerText = `Edit Site Popup: #${p.id}`;
                    popupModal.show();
                } else {
                    alert(res.message || 'Failed to fetch popup details.');
                }
            })
            .catch(() => alert('Network error while loading popup.'));
    });

    // Toggle Status Button
    $('#sitePopupsTable').on('click', '.btn-toggle-popup-status', function () {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        const nextStatus = currentStatus === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        if (!confirm(`Mark this popup as ${nextStatus}?`)) return;

        fetch(`${baseUrl}/api/admin/popups/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ status: nextStatus })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                popupsTable.ajax.reload(null, false);
            } else {
                alert(res.message || 'Status update failed.');
            }
        })
        .catch(() => alert('Network error while toggling status.'));
    });

    // Delete Popup Button with boundary protection
    $('#sitePopupsTable').on('click', '.btn-delete-popup', function () {
        const id = $(this).data('id');
        const title = $(this).data('title') || 'this popup';

        if (!confirm(`Are you sure you want to permanently delete "${title}"?`)) return;

        fetch(`${baseUrl}/api/admin/popups/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                popupsTable.ajax.reload(function () {
                    const info = popupsTable.page.info();
                    if (info && info.pages > 0 && info.page >= info.pages) {
                        popupsTable.page(Math.max(0, info.pages - 1)).draw('page');
                    }
                }, false);
                alert(res.message || 'Popup deleted successfully.');
            } else {
                alert(res.message || 'Deletion failed.');
            }
        })
        .catch(() => alert('Network error during deletion.'));
    });
});
</script>
