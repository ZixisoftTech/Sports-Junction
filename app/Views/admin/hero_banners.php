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
                    <li class="breadcrumb-item active" aria-current="page">Hero Banners</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold text-dark mb-0">Website Hero Banners</h2>
            <p class="text-muted small mb-0">Manage homepage hero carousel slides, headlines, promotional badges, and action buttons.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5" id="btnAddNewBanner">
                <i class="bi bi-plus-circle fs-6"></i>
                <span class="fw-semibold">Add New Banner</span>
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-sm-auto d-flex flex-wrap align-items-center gap-2">
                    <label for="bannerTypeFilter" class="small fw-semibold text-secondary mb-0">Type:</label>
                    <select id="bannerTypeFilter" class="form-select form-select-sm" style="width: 140px;">
                        <option value="">All Types</option>
                        <option value="WITH_TEXT">Text &amp; Buttons</option>
                        <option value="IMAGE_ONLY">Image Only</option>
                    </select>

                    <label for="bannerStatusFilter" class="small fw-semibold text-secondary mb-0 ms-sm-2">Status:</label>
                    <select id="bannerStatusFilter" class="form-select form-select-sm" style="width: 130px;">
                        <option value="">All Statuses</option>
                        <option value="ACTIVE" selected>Active Only</option>
                        <option value="INACTIVE">Inactive Only</option>
                    </select>
                </div>
                <div class="col-12 col-sm-auto text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Banners with the lowest <strong>Display Order</strong> appear first on the homepage carousel.
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="heroBannersTable" class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
                    <thead class="table-light text-secondary small text-uppercase fw-bold border-bottom">
                        <tr>
                            <th style="width: 50px;">#ID</th>
                            <th style="width: 90px;">Banner</th>
                            <th>Headline &amp; Mode</th>
                            <th>Primary Action / Link</th>
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

<!-- Add / Edit Banner Modal -->
<div class="modal fade" id="heroBannerModal" tabindex="-1" aria-labelledby="heroBannerModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="heroBannerForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="id" id="bannerId">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

                <div class="modal-header border-bottom bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="heroBannerModalLabel">Add New Hero Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Banner Mode Selector -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-2">Banner Layout Mode <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-check border rounded-3 p-3 h-100 bg-white shadow-xs banner-mode-card" id="cardModeWithText" style="cursor: pointer;">
                                        <input class="form-check-input" type="radio" name="banner_type" id="typeWithText" value="WITH_TEXT" checked>
                                        <label class="form-check-label w-100 cursor-pointer" for="typeWithText">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <i class="bi bi-card-heading text-primary fs-5"></i>
                                                <span class="fw-bold text-dark small">Text &amp; Buttons (Full Banner)</span>
                                            </div>
                                            <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                                                Overlay headline title, promotional badge, descriptive text, and CTA buttons on top of the image.
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-check border rounded-3 p-3 h-100 bg-white shadow-xs banner-mode-card" id="cardModeImageOnly" style="cursor: pointer;">
                                        <input class="form-check-input" type="radio" name="banner_type" id="typeImageOnly" value="IMAGE_ONLY">
                                        <label class="form-check-label w-100 cursor-pointer" for="typeImageOnly">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <i class="bi bi-image text-success fs-5"></i>
                                                <span class="fw-bold text-dark small">Image Only (Clickable Graphic)</span>
                                            </div>
                                            <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                                                Upload only an image graphic banner without headline or buttons. Optionally attach a redirect link.
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Banner Image Upload & Live Preview Card (Always visible) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Banner Graphic Image <span class="text-danger">*</span></label>
                            <div class="card border rounded-3 p-3 bg-light">
                                <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                                    <div class="flex-shrink-0 bg-dark rounded overflow-hidden shadow-xs border" style="width: 150px; height: 90px; position: relative;">
                                        <img id="bannerImagePreview" src="" alt="Preview" class="w-100 h-100 object-fit-cover d-none">
                                        <div id="bannerImagePlaceholder" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image fs-4"></i>
                                            <span style="font-size: 10px;">Preview</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 w-100">
                                        <div class="mb-2">
                                            <label for="bannerImageFile" class="form-label small fw-semibold mb-1">Upload Image File (JPG, PNG, WEBP):</label>
                                            <input type="file" class="form-control form-control-sm" id="bannerImageFile" name="image_file" accept="image/jpeg,image/png,image/webp,image/jpg">
                                            <div class="text-muted small" style="font-size: 11px;">Recommended resolution: 1600×500px or higher. Max 8MB.</div>
                                        </div>
                                        <div>
                                            <label for="bannerImageUrl" class="form-label small text-muted mb-1" style="font-size: 11px;">Or Direct Image URL / Stored Path:</label>
                                            <input type="text" class="form-control form-control-sm font-monospace" id="bannerImageUrl" name="image" placeholder="https://... or /uploads/banners/..." style="font-size: 12px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Display Order & Status (Always visible) -->
                        <div class="col-12 col-md-6">
                            <label for="bannerOrder" class="form-label small fw-semibold">Display Order <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="bannerOrder" name="display_order" value="1" min="0" required>
                            <div class="text-muted small" style="font-size: 11px;">Lowest number (e.g. 1) displays first in the carousel.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="bannerStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="bannerStatus" name="status" required>
                                <option value="ACTIVE" selected>ACTIVE (Visible on Website)</option>
                                <option value="INACTIVE">INACTIVE (Hidden)</option>
                            </select>
                        </div>

                        <!-- SECTION 1: Image Only Banner Settings -->
                        <div id="sectionImageOnly" class="col-12 d-none">
                            <div class="border rounded-3 p-3 bg-white">
                                <h6 class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5 border-bottom pb-2">
                                    <i class="bi bi-link-45deg text-success fs-5"></i>
                                    Image Banner Action &amp; Target Link
                                </h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="bannerClickUrl" class="form-label small fw-semibold">Destination / Redirect Link URL (Optional)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light"><i class="bi bi-globe"></i></span>
                                            <input type="text" class="form-control font-monospace" id="bannerClickUrl" placeholder="e.g. /products, /categories/cricket, or https://...">
                                        </div>
                                        <div class="text-muted small mt-1" style="font-size: 11px;">
                                            When visitors click anywhere on this banner image, they will be redirected to this link. Leave empty if the image should not be clickable.
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="bannerImageOnlyTitle" class="form-label small fw-semibold">Banner Name / Alt Text (Optional)</label>
                                        <input type="text" class="form-control form-control-sm" id="bannerImageOnlyTitle" placeholder="e.g. Diwali Mega Wholesale Sale Graphic">
                                        <div class="text-muted small mt-1" style="font-size: 11px;">
                                            For your reference in the admin list and image alt accessibility. If left empty, it will default to "Image Banner".
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: Text & Buttons Banner Settings -->
                        <div id="sectionWithText" class="col-12">
                            <div class="border rounded-3 p-3 bg-white">
                                <h6 class="fw-bold text-dark small mb-3 d-flex align-items-center gap-1.5 border-bottom pb-2">
                                    <i class="bi bi-card-text text-primary fs-5"></i>
                                    Headline &amp; Button Overlay
                                </h6>
                                <div class="row g-3">
                                    <!-- Main Headline Title -->
                                    <div class="col-12">
                                        <label for="bannerTitle" class="form-label small fw-semibold">Headline Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="bannerTitle" name="title" required placeholder="e.g. Direct From Verified Indian Sports & Fitness Manufacturers">
                                        <div class="invalid-feedback">Please enter a banner headline.</div>
                                    </div>

                                    <!-- Badge / Tag -->
                                    <div class="col-12 col-md-6">
                                        <label for="bannerBadge" class="form-label small fw-semibold">Pill Badge Text</label>
                                        <input type="text" class="form-control form-control-sm" id="bannerBadge" name="badge_text" placeholder="e.g. Factory Direct Sourcing Hub">
                                        <div class="text-muted small" style="font-size: 11px;">Small highlighted tag shown above the main headline.</div>
                                    </div>

                                    <!-- Subtitle / Descriptive Text -->
                                    <div class="col-12 col-md-6">
                                        <label for="bannerSubtitle" class="form-label small fw-semibold">Subtitle / Descriptive Text</label>
                                        <textarea class="form-control form-control-sm" id="bannerSubtitle" name="subtitle" rows="2" placeholder="e.g. Factory-Direct Wholesale Pricing • Guaranteed MOQs..."></textarea>
                                    </div>

                                    <!-- Primary CTA -->
                                    <div class="col-12 col-md-6">
                                        <label for="bannerCtaText" class="form-label small fw-semibold">Primary CTA Button Label</label>
                                        <input type="text" class="form-control form-control-sm" id="bannerCtaText" name="cta_text" value="Source Catalog" placeholder="e.g. Source Catalog">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="bannerCtaUrl" class="form-label small fw-semibold">Primary CTA Link URL</label>
                                        <input type="text" class="form-control form-control-sm font-monospace" id="bannerCtaUrl" name="cta_url" value="/products" placeholder="e.g. /products">
                                    </div>

                                    <!-- Secondary CTA -->
                                    <div class="col-12 col-md-6">
                                        <label for="bannerSecCtaText" class="form-label small fw-semibold">Secondary CTA Button Label</label>
                                        <input type="text" class="form-control form-control-sm" id="bannerSecCtaText" name="secondary_cta_text" value="Request Quotation" placeholder="e.g. Request Quotation">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="bannerSecCtaUrl" class="form-label small fw-semibold">Secondary Action / Link URL</label>
                                        <input type="text" class="form-control form-control-sm font-monospace" id="bannerSecCtaUrl" name="secondary_cta_url" value="rfq_modal" placeholder="rfq_modal or https://...">
                                        <div class="text-muted small" style="font-size: 11px;">Use <code>rfq_modal</code> to open the Request for Quotation popup.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light px-4 py-2.5">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSaveBanner">
                        <i class="bi bi-check-lg me-1"></i> Save Hero Banner
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

    // Image preview handler
    const fileInput = document.getElementById('bannerImageFile');
    const urlInput = document.getElementById('bannerImageUrl');
    const previewImg = document.getElementById('bannerImagePreview');
    const placeholder = document.getElementById('bannerImagePlaceholder');

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

    // Banner mode toggling (WITH_TEXT vs IMAGE_ONLY)
    function setBannerMode(mode) {
        const isImage = (mode === 'IMAGE_ONLY');
        document.getElementById('typeImageOnly').checked = isImage;
        document.getElementById('typeWithText').checked = !isImage;

        const cardText = document.getElementById('cardModeWithText');
        const cardImage = document.getElementById('cardModeImageOnly');
        const sectionText = document.getElementById('sectionWithText');
        const sectionImage = document.getElementById('sectionImageOnly');
        const titleInput = document.getElementById('bannerTitle');

        if (isImage) {
            cardImage.classList.add('border-success', 'bg-success-subtle');
            cardText.classList.remove('border-primary', 'bg-primary-subtle');
            sectionImage.classList.remove('d-none');
            sectionText.classList.add('d-none');
            titleInput.removeAttribute('required');
        } else {
            cardText.classList.add('border-primary', 'bg-primary-subtle');
            cardImage.classList.remove('border-success', 'bg-success-subtle');
            sectionText.classList.remove('d-none');
            sectionImage.classList.add('d-none');
            titleInput.setAttribute('required', 'required');
        }
    }

    document.querySelectorAll('input[name="banner_type"]').forEach(radio => {
        radio.addEventListener('change', function () {
            setBannerMode(this.value);
        });
    });

    document.getElementById('cardModeWithText').addEventListener('click', function (e) {
        if (e.target.tagName !== 'INPUT') {
            setBannerMode('WITH_TEXT');
        }
    });

    document.getElementById('cardModeImageOnly').addEventListener('click', function (e) {
        if (e.target.tagName !== 'INPUT') {
            setBannerMode('IMAGE_ONLY');
        }
    });

    // Initialize DataTable with stateSave and display_order default
    const bannersTable = $('#heroBannersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        stateSave: true,
        ajax: {
            url: `${baseUrl}/api/admin/banners/data`,
            type: 'GET',
            data: function (d) {
                d.status = $('#bannerStatusFilter').val();
                d.banner_type = $('#bannerTypeFilter').val();
            },
            error: function (xhr) {
                console.error('Error fetching hero banners:', xhr);
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
                    return `<img src="${src}" class="rounded object-fit-cover shadow-xs border" style="width: 76px; height: 48px;" alt="Banner" onerror="this.src='https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=300&q=80';">`;
                }
            },
            {
                data: 'title',
                render: function (data, type, row) {
                    const isImageOnly = (row.banner_type === 'IMAGE_ONLY');
                    const modeBadge = isImageOnly 
                        ? `<span class="badge bg-info-subtle text-info border mb-1" style="font-size: 10px;"><i class="bi bi-image me-1"></i>Image Only</span>`
                        : `<span class="badge bg-primary-subtle text-primary border mb-1" style="font-size: 10px;"><i class="bi bi-card-heading me-1"></i>Text &amp; Buttons</span>`;

                    if (isImageOnly) {
                        const titleText = data && data !== 'Image Banner' ? escapeHtml(data) : 'Full-Bleed Graphic Slide';
                        return `
                            <div>
                                ${modeBadge}
                                <div class="fw-semibold text-dark">${titleText}</div>
                                <div class="text-muted" style="font-size: 11px;"><i class="bi bi-check2-circle text-success me-1"></i>Pure image (no text overlay)</div>
                            </div>
                        `;
                    }

                    const badge = row.badge_text ? `<span class="badge bg-light text-secondary border mb-1 ms-1" style="font-size: 10px;">${escapeHtml(row.badge_text)}</span>` : '';
                    const subtitle = row.subtitle ? `<div class="text-muted text-truncate" style="max-width: 320px; font-size: 11px;">${escapeHtml(row.subtitle)}</div>` : '';
                    return `
                        <div>
                            <div>${modeBadge}${badge}</div>
                            <span class="fw-bold text-dark">${escapeHtml(data)}</span>
                            ${subtitle}
                        </div>
                    `;
                }
            },
            {
                data: 'cta_text',
                render: function (data, type, row) {
                    const isImageOnly = (row.banner_type === 'IMAGE_ONLY');
                    if (isImageOnly) {
                        if (row.cta_url && row.cta_url.trim() !== '') {
                            return `
                                <div class="small">
                                    <span class="badge bg-success-subtle text-success border"><i class="bi bi-link-45deg me-1"></i>Click URL</span>
                                    <div class="text-dark font-monospace text-truncate mt-1" style="max-width: 220px; font-size: 11px;" title="${escapeHtml(row.cta_url)}">${escapeHtml(row.cta_url)}</div>
                                </div>
                            `;
                        }
                        return `<span class="text-muted small"><i class="bi bi-dash me-1"></i>No click URL</span>`;
                    }

                    if (!data) return '<span class="text-muted">—</span>';
                    return `
                        <div class="small">
                            <span class="badge bg-primary-subtle text-primary border">${escapeHtml(data)}</span>
                            <div class="text-muted font-monospace" style="font-size: 10px;">${escapeHtml(row.cta_url || '')}</div>
                        </div>
                    `;
                }
            },
            {
                data: 'display_order',
                className: 'text-center',
                render: function (data) {
                    return `<span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1.5">${data}</span>`;
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
                            <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-toggle-banner-status" data-id="${row.id}" data-status="${data}" title="Toggle status">
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
                            <button type="button" class="btn btn-outline-primary btn-edit-banner" data-id="${row.id}" title="Edit Banner">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-banner" data-id="${row.id}" data-title="${escapeHtml(row.title)}" title="Delete Banner">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[4, 'asc']] // Default to display_order ascending
    });

    $('#bannerStatusFilter, #bannerTypeFilter').on('change', function () {
        bannersTable.ajax.reload();
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

    // Add New Banner
    const bannerModalEl = document.getElementById('heroBannerModal');
    const bannerModal = new bootstrap.Modal(bannerModalEl);
    const form = document.getElementById('heroBannerForm');

    document.getElementById('btnAddNewBanner').addEventListener('click', function () {
        form.reset();
        document.getElementById('bannerId').value = '';
        document.getElementById('bannerOrder').value = '1';
        document.getElementById('bannerStatus').value = 'ACTIVE';
        document.getElementById('bannerCtaText').value = 'Source Catalog';
        document.getElementById('bannerCtaUrl').value = '/products';
        document.getElementById('bannerSecCtaText').value = 'Request Quotation';
        document.getElementById('bannerSecCtaUrl').value = 'rfq_modal';
        document.getElementById('bannerClickUrl').value = '';
        document.getElementById('bannerImageOnlyTitle').value = '';
        document.getElementById('heroBannerModalLabel').innerText = 'Add New Hero Banner';
        setBannerMode('WITH_TEXT');
        updatePreview('');
        bannerModal.show();
    });

    // Form Submit (Add / Edit)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const isImageOnly = document.getElementById('typeImageOnly').checked;

        if (isImageOnly) {
            const clickUrl = document.getElementById('bannerClickUrl').value.trim();
            const altTitle = document.getElementById('bannerImageOnlyTitle').value.trim();
            document.getElementById('bannerCtaUrl').value = clickUrl;
            document.getElementById('bannerTitle').value = altTitle || 'Image Banner';
            document.getElementById('bannerCtaText').value = '';
            document.getElementById('bannerSecCtaText').value = '';
            document.getElementById('bannerSecCtaUrl').value = '';
            document.getElementById('bannerBadge').value = '';
            document.getElementById('bannerSubtitle').value = '';
        }

        const id = document.getElementById('bannerId').value;
        const isUpdate = !!id;
        const url = isUpdate ? `${baseUrl}/api/admin/banners/${id}` : `${baseUrl}/api/admin/banners`;

        const submitBtn = document.getElementById('btnSaveBanner');
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
                bannerModal.hide();
                bannersTable.ajax.reload(null, false);
                alert(data.message || 'Saved successfully');
            } else {
                alert(data.message || 'Validation error occurred.');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnText;
            alert('Server communication error.');
        });
    });

    // Edit Banner Button
    $('#heroBannersTable').on('click', '.btn-edit-banner', function () {
        const id = $(this).data('id');
        fetch(`${baseUrl}/api/admin/banners/${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const b = res.data;
                    document.getElementById('bannerId').value = b.id;
                    document.getElementById('bannerBadge').value = b.badge_text || '';
                    document.getElementById('bannerTitle').value = b.title || '';
                    document.getElementById('bannerSubtitle').value = b.subtitle || '';
                    document.getElementById('bannerImageUrl').value = b.image || '';
                    document.getElementById('bannerCtaText').value = b.cta_text || '';
                    document.getElementById('bannerCtaUrl').value = b.cta_url || '';
                    document.getElementById('bannerSecCtaText').value = b.secondary_cta_text || '';
                    document.getElementById('bannerSecCtaUrl').value = b.secondary_cta_url || '';
                    document.getElementById('bannerOrder').value = b.display_order ?? 0;
                    document.getElementById('bannerStatus').value = b.status || 'ACTIVE';

                    document.getElementById('bannerClickUrl').value = b.cta_url || '';
                    document.getElementById('bannerImageOnlyTitle').value = b.title || '';

                    const mode = (b.banner_type === 'IMAGE_ONLY') ? 'IMAGE_ONLY' : 'WITH_TEXT';
                    setBannerMode(mode);

                    fileInput.value = '';
                    updatePreview(b.image);

                    document.getElementById('heroBannerModalLabel').innerText = `Edit Hero Banner: #${b.id}`;
                    bannerModal.show();
                } else {
                    alert(res.message || 'Failed to fetch banner details.');
                }
            })
            .catch(() => alert('Network error while loading banner.'));
    });

    // Toggle Status Button
    $('#heroBannersTable').on('click', '.btn-toggle-banner-status', function () {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        const nextStatus = currentStatus === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        if (!confirm(`Mark this banner as ${nextStatus}?`)) return;

        fetch(`${baseUrl}/api/admin/banners/${id}/toggle-status`, {
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
                bannersTable.ajax.reload(null, false);
            } else {
                alert(res.message || 'Status update failed.');
            }
        })
        .catch(() => alert('Network error while toggling status.'));
    });

    // Delete Banner Button with pagination boundary protection
    $('#heroBannersTable').on('click', '.btn-delete-banner', function () {
        const id = $(this).data('id');
        const title = $(this).data('title') || 'this banner';

        if (!confirm(`Are you sure you want to permanently delete "${title}"?`)) return;

        fetch(`${baseUrl}/api/admin/banners/${id}`, {
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
                bannersTable.ajax.reload(function () {
                    const info = bannersTable.page.info();
                    if (info && info.pages > 0 && info.page >= info.pages) {
                        bannersTable.page(Math.max(0, info.pages - 1)).draw('page');
                    }
                }, false);
                alert(res.message || 'Banner deleted successfully.');
            } else {
                alert(res.message || 'Deletion failed.');
            }
        })
        .catch(() => alert('Network error during deletion.'));
    });
});
</script>
