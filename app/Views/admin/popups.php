<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>

<!-- Quill Rich-Text Stylesheet -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

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
            <p class="text-muted small mb-0">Create and manage marketing and informational popups displayed to visitors upon landing on the site (Image or Rich-Text modes).</p>
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
                <div class="col-12 col-sm-auto d-flex flex-wrap align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="popupStatusFilter" class="small fw-semibold text-secondary mb-0">Status:</label>
                        <select id="popupStatusFilter" class="form-select form-select-sm" style="width: 140px;">
                            <option value="">All Statuses</option>
                            <option value="ACTIVE" selected>Active Only</option>
                            <option value="INACTIVE">Inactive Only</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="popupTypeFilter" class="small fw-semibold text-secondary mb-0">Type:</label>
                        <select id="popupTypeFilter" class="form-select form-select-sm" style="width: 140px;">
                            <option value="">All Types</option>
                            <option value="IMAGE">Image Popup</option>
                            <option value="TEXT">Text Popup</option>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-sm-auto text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    The active popup with the lowest <strong>Display Order</strong> is displayed once per visitor session.
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="sitePopupsTable" class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
                    <thead class="table-light text-secondary small text-uppercase fw-bold border-bottom">
                        <tr>
                            <th style="width: 50px;">#ID</th>
                            <th style="width: 100px;">Mode</th>
                            <th style="width: 90px;">Creative</th>
                            <th>Popup Title / Heading</th>
                            <th>Destination URL / CTA</th>
                            <th style="width: 80px;" class="text-center">Delay</th>
                            <th style="width: 100px;" class="text-center">Display Order</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 120px;" class="text-end">Actions</th>
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
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="sitePopupForm" class="modal-content border-0 shadow-lg rounded-3" novalidate enctype="multipart/form-data">
            <input type="hidden" name="id" id="popupId">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

                <div class="modal-header border-bottom bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="sitePopupModalLabel">Add New Site Popup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- A. Content Type Switcher -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-secondary tracking-wider mb-2">
                            Select Popup Content Type <span class="text-danger">*</span>
                        </label>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="card border p-3 h-100 cursor-pointer content-type-card transition-all" id="cardTypeImage">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0 fs-5" type="radio" name="content_type" id="typeImage" value="IMAGE" checked>
                                        <div>
                                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                <i class="bi bi-image text-primary fs-5"></i> Image Popup
                                            </div>
                                            <div class="text-muted small">Display promotional banners, poster images, or graphical creative.</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="card border p-3 h-100 cursor-pointer content-type-card transition-all" id="cardTypeText">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0 fs-5" type="radio" name="content_type" id="typeText" value="TEXT">
                                        <div>
                                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                <i class="bi bi-fonts text-success fs-5"></i> Text Popup (Rich-Text Editor)
                                            </div>
                                            <div class="text-muted small">Display announcements, terms, structured updates with headings, lists &amp; links.</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Popup Heading / Title -->
                        <div class="col-12">
                            <label for="popupTitle" class="form-label small fw-semibold">
                                <span id="labelTitleText">Popup Title / Campaign Heading</span> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="popupTitle" name="title" required placeholder="e.g. Special Factory Sourcing Discount 2026">
                            <div class="invalid-feedback">Please enter a popup heading or title.</div>
                        </div>

                        <!-- B. Image Popup Fields (shown when IMAGE is selected) -->
                        <div id="sectionImagePopup" class="col-12">
                            <div class="card border rounded-3 p-3 bg-light">
                                <h6 class="fw-bold text-dark small mb-3"><i class="bi bi-file-earmark-image text-primary me-1"></i>Image Creative &amp; Accessibility</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-7">
                                        <label for="popupImageFile" class="form-label small fw-semibold mb-1">Upload Image from Computer (JPG, PNG, WEBP):</label>
                                        <input type="file" class="form-control form-control-sm mb-2" id="popupImageFile" name="image_file" accept="image/jpeg,image/png,image/webp,image/jpg">
                                        <div class="text-muted small mb-2" style="font-size: 11px;">Recommended size: ~960×600px or proportional aspect ratio. Max size: 10MB.</div>

                                        <label for="popupImageUrl" class="form-label small text-muted mb-1" style="font-size: 11px;">Or Direct Image URL / Existing Relative Path:</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <input type="text" class="form-control font-monospace" id="popupImageUrl" name="image" placeholder="storage/uploads/popups/... or https://..." style="font-size: 12px;">
                                            <button class="btn btn-outline-secondary" type="button" id="btnClearImage" title="Clear image input">
                                                <i class="bi bi-x-lg"></i> Clear
                                            </button>
                                        </div>

                                        <label for="popupAltText" class="form-label small fw-semibold mb-1">Alt Text (Accessibility &amp; SEO):</label>
                                        <input type="text" class="form-control form-control-sm" id="popupAltText" name="alt_text" placeholder="Short description of image for screen readers">
                                    </div>
                                    <div class="col-12 col-md-5 d-flex flex-column align-items-center justify-content-center">
                                        <div class="bg-white rounded-3 overflow-hidden shadow-xs border text-center p-2 w-100" style="min-height: 160px; max-height: 220px; display: flex; align-items: center; justify-content: center; position: relative;">
                                            <img id="popupImagePreview" src="" alt="Preview" class="w-100 h-100 object-fit-contain d-none">
                                            <div id="popupImagePlaceholder" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted py-4">
                                                <i class="bi bi-image fs-1 text-secondary"></i>
                                                <span class="small mt-1">Creative Preview</span>
                                            </div>
                                        </div>
                                        <!-- Replace / Remove Controls -->
                                        <div id="imageActionButtons" class="d-none w-100 mt-2 d-flex gap-2 justify-content-center">
                                            <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2.5 small fw-semibold" id="btnReplaceImage">
                                                <i class="bi bi-arrow-repeat me-1"></i>Replace Image
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2.5 small fw-semibold" id="btnRemoveImage">
                                                <i class="bi bi-trash3 me-1"></i>Remove Image
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- C. Text Popup Fields (shown when TEXT is selected) -->
                        <div id="sectionTextPopup" class="col-12 d-none">
                            <label class="form-label small fw-semibold mb-1">
                                Description &amp; Content (Rich-Text Editor) <span class="text-danger">*</span>
                            </label>
                            <div class="bg-white rounded-3 border">
                                <div id="popupQuillEditor" style="min-height: 200px; font-size: 14px;"></div>
                            </div>
                            <input type="hidden" name="content_html" id="popupContentHtml">
                            <div class="text-muted small mt-1" style="font-size: 11px;">
                                Use headings, bold, italic, bullet points, numbers, and links to structure your announcement.
                            </div>
                        </div>

                        <!-- Destination URL / CTA Link -->
                        <div class="col-12 col-md-6">
                            <label for="popupTargetUrl" class="form-label small fw-semibold">Destination URL / Action Link (Optional)</label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="popupTargetUrl" name="target_url" placeholder="/products or https://...">
                            <div class="text-muted small" style="font-size: 11px;">Where visitors are directed upon clicking (or CTA button in text mode).</div>
                        </div>

                        <!-- Autoload Delay -->
                        <div class="col-12 col-md-3">
                            <label for="popupDelay" class="form-label small fw-semibold">Delay (Seconds) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="popupDelay" name="delay_seconds" value="2" min="0" max="60" required>
                            <div class="text-muted small" style="font-size: 11px;">Seconds after page load.</div>
                        </div>

                        <!-- Display Order -->
                        <div class="col-12 col-md-3">
                            <label for="popupOrder" class="form-label small fw-semibold">Display Order <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="popupOrder" name="display_order" value="1" min="0" required>
                            <div class="text-muted small" style="font-size: 11px;">Lower order shown first.</div>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-md-6">
                            <label for="popupStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="popupStatus" name="status" required>
                                <option value="ACTIVE" selected>ACTIVE (Visible on frontend)</option>
                                <option value="INACTIVE">INACTIVE (Disabled)</option>
                            </select>
                        </div>

                        <!-- Schedule Dates -->
                        <div class="col-12 col-md-3">
                            <label for="popupStartDate" class="form-label small fw-semibold">Start Date (Optional)</label>
                            <input type="date" class="form-control form-control-sm" id="popupStartDate" name="start_date">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="popupEndDate" class="form-label small fw-semibold">End Date (Optional)</label>
                            <input type="date" class="form-control form-control-sm" id="popupEndDate" name="end_date">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light px-4 py-2.5 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-info btn-sm px-3 d-flex align-items-center gap-1.5" id="btnLivePreview">
                        <i class="bi bi-eye"></i>
                        <span>Preview Popup</span>
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSavePopup">
                            <i class="bi bi-check-lg me-1"></i> Save Popup
                        </button>
                    </div>
                </div>
            </form>
    </div>
</div>

<!-- Modal: Interactive Live Preview Modal (Simulating Frontend 960px Popup) -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 960px;">
        <div class="modal-content border-0 shadow-2xl rounded-3 overflow-hidden position-relative">
            <!-- Simulated Close Button -->
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3 bg-white p-2 shadow-sm rounded-circle" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0" id="previewModalContent">
                <!-- Injected via JavaScript -->
            </div>
        </div>
    </div>
</div>

<style>
.content-type-card:hover {
    border-color: #00288e !important;
    background-color: #f8fafc;
}
.content-type-card.active-type {
    border-color: #00288e !important;
    background-color: #f0f4ff !important;
    box-shadow: 0 0 0 1px #00288e;
}

/* Ensure modal dialog and body are properly bounded and scrollable */
#sitePopupModal .modal-dialog {
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
}
#sitePopupModal .modal-content {
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#sitePopupModal .modal-header,
#sitePopupModal .modal-footer {
    flex-shrink: 0;
}
#sitePopupModal .modal-body {
    flex: 1 1 auto;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
    max-height: calc(100vh - 170px);
}
</style>

<!-- Quill Rich-Text Library -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?= $baseUrl ?>';
    const csrfToken = '<?= CSRF::getToken() ?>';

    // 1. Initialize Quill Rich-Text Editor
    let popupQuill = null;
    const quillEl = document.getElementById('popupQuillEditor');
    if (quillEl) {
        popupQuill = new Quill('#popupQuillEditor', {
            theme: 'snow',
            placeholder: 'Compose rich announcement content, instructions, or promotional text...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'blockquote'],
                    ['clean']
                ]
            }
        });
    }

    // 2. Mode Switching Logic (Image vs Text)
    const typeImageRadio = document.getElementById('typeImage');
    const typeTextRadio = document.getElementById('typeText');
    const cardTypeImage = document.getElementById('cardTypeImage');
    const cardTypeText = document.getElementById('cardTypeText');
    const sectionImage = document.getElementById('sectionImagePopup');
    const sectionText = document.getElementById('sectionTextPopup');
    const labelTitleText = document.getElementById('labelTitleText');

    function syncContentTypeUI() {
        if (typeTextRadio.checked) {
            cardTypeText.classList.add('active-type');
            cardTypeImage.classList.remove('active-type');
            sectionText.classList.remove('d-none');
            sectionImage.classList.add('d-none');
            labelTitleText.innerText = 'Popup Heading / Announcement Title';
        } else {
            cardTypeImage.classList.add('active-type');
            cardTypeText.classList.remove('active-type');
            sectionImage.classList.remove('d-none');
            sectionText.classList.add('d-none');
            labelTitleText.innerText = 'Popup Title / Campaign Name';
        }
    }

    typeImageRadio.addEventListener('change', syncContentTypeUI);
    typeTextRadio.addEventListener('change', syncContentTypeUI);
    cardTypeImage.addEventListener('click', () => { typeImageRadio.checked = true; syncContentTypeUI(); });
    cardTypeText.addEventListener('click', () => { typeTextRadio.checked = true; syncContentTypeUI(); });

    // 3. Image preview handling
    const fileInput = document.getElementById('popupImageFile');
    const urlInput = document.getElementById('popupImageUrl');
    const previewImg = document.getElementById('popupImagePreview');
    const placeholder = document.getElementById('popupImagePlaceholder');
    const btnClearImage = document.getElementById('btnClearImage');

    const imageActionButtons = document.getElementById('imageActionButtons');
    const btnReplaceImage = document.getElementById('btnReplaceImage');
    const btnRemoveImage = document.getElementById('btnRemoveImage');

    function updatePreview(src) {
        if (src && src.trim() !== '') {
            let finalSrc = src.trim();
            if (!finalSrc.startsWith('http') && !finalSrc.startsWith('/') && !finalSrc.startsWith('data:')) {
                finalSrc = `${baseUrl}/${finalSrc}`;
            }
            previewImg.src = finalSrc;
            previewImg.classList.remove('d-none');
            placeholder.classList.add('d-none');
            if (imageActionButtons) imageActionButtons.classList.remove('d-none');
        } else {
            previewImg.src = '';
            previewImg.classList.add('d-none');
            placeholder.classList.remove('d-none');
            if (imageActionButtons) imageActionButtons.classList.add('d-none');
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    updatePreview(e.target.result);
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

    function removeCurrentCreative() {
        fileInput.value = '';
        urlInput.value = '';
        updatePreview('');
    }

    if (btnClearImage) {
        btnClearImage.addEventListener('click', removeCurrentCreative);
    }
    if (btnRemoveImage) {
        btnRemoveImage.addEventListener('click', removeCurrentCreative);
    }
    if (btnReplaceImage) {
        btnReplaceImage.addEventListener('click', function () {
            fileInput.click();
        });
    }

    // 4. Initialize DataTable
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
                d.content_type = $('#popupTypeFilter').val();
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
                data: 'content_type',
                className: 'text-center',
                render: function (data) {
                    if (data === 'TEXT') {
                        return `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-fonts me-1"></i>Text</span>`;
                    }
                    return `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-image me-1"></i>Image</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    if (row.content_type === 'TEXT') {
                        return `<div class="rounded bg-light text-secondary d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px;" title="Text Content"><i class="bi bi-card-text fs-4"></i></div>`;
                    }
                    if (row.image) {
                        const src = row.image.startsWith('http') ? row.image : `${baseUrl}/${row.image.replace(/^\//, '')}`;
                        return `<img src="${src}" class="rounded object-fit-contain shadow-xs border bg-white p-1" style="width: 50px; height: 50px;" alt="${escapeHtml(row.alt_text || row.title)}" onerror="this.src='https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=200&q=80';">`;
                    }
                    return `<div class="rounded bg-light text-muted d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px;"><i class="bi bi-image text-muted"></i></div>`;
                }
            },
            {
                data: 'title',
                render: function (data, type, row) {
                    let dates = '';
                    if (row.start_date || row.end_date) {
                        dates = `<div class="text-muted" style="font-size: 11px;"><i class="bi bi-calendar3 me-1"></i>${row.start_date || 'Any'} &rarr; ${row.end_date || 'Any'}</div>`;
                    }
                    const altBadge = (row.content_type === 'IMAGE' && row.alt_text) ? `<span class="badge bg-light text-secondary border ms-1" title="Alt: ${escapeHtml(row.alt_text)}"><i class="bi bi-universal-access"></i></span>` : '';
                    return `
                        <div>
                            <span class="fw-bold text-dark">${escapeHtml(data)}</span> ${altBadge}
                            ${dates}
                        </div>
                    `;
                }
            },
            {
                data: 'target_url',
                render: function (data) {
                    if (!data) return '<span class="text-muted small">No Link</span>';
                    return `<a href="${data}" target="_blank" class="text-decoration-none small text-truncate d-inline-block font-monospace" style="max-width: 200px;">${escapeHtml(data)}</a>`;
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
                            <button type="button" class="btn btn-outline-info btn-preview-row" data-id="${row.id}" title="Preview Popup">
                                <i class="bi bi-eye"></i>
                            </button>
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
        order: [[6, 'asc']] // Default by display_order ascending
    });

    $('#popupStatusFilter, #popupTypeFilter').on('change', function () {
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

    // 5. Add New Popup Modal
    const popupModalEl = document.getElementById('sitePopupModal');
    const popupModal = new bootstrap.Modal(popupModalEl);
    const form = document.getElementById('sitePopupForm');

    document.getElementById('btnAddNewPopup').addEventListener('click', function () {
        form.reset();
        document.getElementById('popupId').value = '';
        typeImageRadio.checked = true;
        syncContentTypeUI();
        document.getElementById('popupOrder').value = '1';
        document.getElementById('popupDelay').value = '2';
        document.getElementById('popupStatus').value = 'ACTIVE';
        document.getElementById('popupAltText').value = '';
        document.getElementById('popupImageUrl').value = '';
        if (popupQuill) popupQuill.root.innerHTML = '';
        document.getElementById('sitePopupModalLabel').innerText = 'Add New Site Popup';
        updatePreview('');
        popupModal.show();
    });

    // 6. Form Submission (Add / Edit)
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Sync Quill HTML content if in text mode
        if (typeTextRadio.checked && popupQuill) {
            document.getElementById('popupContentHtml').value = popupQuill.root.innerHTML;
        } else {
            document.getElementById('popupContentHtml').value = '';
        }

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

    // 7. Edit Popup Button
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
                    document.getElementById('popupAltText').value = p.alt_text || '';
                    document.getElementById('popupTargetUrl').value = p.target_url || '';
                    document.getElementById('popupDelay').value = p.delay_seconds ?? 2;
                    document.getElementById('popupOrder').value = p.display_order ?? 0;
                    document.getElementById('popupStatus').value = p.status || 'ACTIVE';
                    document.getElementById('popupStartDate').value = p.start_date || '';
                    document.getElementById('popupEndDate').value = p.end_date || '';

                    if (p.content_type === 'TEXT') {
                        typeTextRadio.checked = true;
                        if (popupQuill) popupQuill.root.innerHTML = p.content_html || '';
                    } else {
                        typeImageRadio.checked = true;
                    }
                    syncContentTypeUI();

                    fileInput.value = '';
                    updatePreview(p.image);

                    document.getElementById('sitePopupModalLabel').innerText = `Edit Site Popup: #${p.id} (${p.content_type})`;
                    popupModal.show();
                } else {
                    alert(res.message || 'Failed to fetch popup details.');
                }
            })
            .catch(() => alert('Network error while loading popup.'));
    });

    // 8. Live Preview Modal Logic
    const previewModalEl = document.getElementById('previewModal');
    const previewModal = new bootstrap.Modal(previewModalEl);
    const previewContainer = document.getElementById('previewModalContent');

    function renderPreviewHtml(type, title, htmlContent, imgSrc, targetUrl, altText) {
        let contentHtml = '';
        if (type === 'TEXT') {
            const ctaBtn = targetUrl ? `
                <div class="pt-4 mt-4 border-top d-flex justify-content-end">
                    <a href="${escapeHtml(targetUrl)}" target="_blank" class="btn btn-primary px-4 py-2 fw-bold rounded-3">
                        Learn More &amp; Explore <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            ` : '';

            contentHtml = `
                <div class="p-4 p-md-5 text-dark" style="max-height: 80vh; overflow-y: auto;">
                    <div class="border-bottom pb-3 mb-4 pe-5">
                        <span class="badge bg-success-subtle text-success border mb-2 px-2.5 py-1 fw-bold text-uppercase" style="font-size: 11px;">Announcements &amp; News</span>
                        <h3 class="fw-bold text-dark mb-0">${escapeHtml(title || 'Popup Heading Preview')}</h3>
                    </div>
                    <div class="popup-rich-content fs-6 text-secondary leading-relaxed">
                        ${htmlContent || '<p class="text-muted fst-italic">No description content entered yet.</p>'}
                    </div>
                    ${ctaBtn}
                </div>
            `;
        } else {
            const finalImg = imgSrc || 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=960&q=80';
            const imgEl = `<img src="${finalImg}" alt="${escapeHtml(altText || title)}" class="w-100 h-auto d-block mx-auto">`;
            const inner = targetUrl ? `<a href="${escapeHtml(targetUrl)}" target="_blank" class="d-block w-100">${imgEl}</a>` : imgEl;
            contentHtml = `<div class="w-100 bg-white" style="max-height: 80vh; overflow-y: auto;">${inner}</div>`;
        }
        previewContainer.innerHTML = contentHtml;
        previewModal.show();
    }

    // Preview from form button
    document.getElementById('btnLivePreview').addEventListener('click', function () {
        const type = typeTextRadio.checked ? 'TEXT' : 'IMAGE';
        const title = document.getElementById('popupTitle').value.trim();
        const html = popupQuill ? popupQuill.root.innerHTML : '';
        const imgSrc = previewImg.src;
        const targetUrl = document.getElementById('popupTargetUrl').value.trim();
        const altText = document.getElementById('popupAltText').value.trim();

        renderPreviewHtml(type, title, html, imgSrc, targetUrl, altText);
    });

    // Preview from table action icon
    $('#sitePopupsTable').on('click', '.btn-preview-row', function () {
        const id = $(this).data('id');
        fetch(`${baseUrl}/api/admin/popups/${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const p = res.data;
                    const finalImg = p.image ? (p.image.startsWith('http') ? p.image : `${baseUrl}/${p.image.replace(/^\//, '')}`) : '';
                    renderPreviewHtml(p.content_type || 'IMAGE', p.title, p.content_html, finalImg, p.target_url, p.alt_text);
                }
            });
    });

    // 9. Toggle Status Button
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

    // 10. Delete Popup Button
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
                popupsTable.ajax.reload(null, false);
                alert(res.message || 'Popup deleted successfully.');
            } else {
                alert(res.message || 'Deletion failed.');
            }
        })
        .catch(() => alert('Network error during deletion.'));
    });
});
</script>
