<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<!-- Page Header & Action Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-2 fw-bold" style="font-size: 11px;">CATALOG MANAGEMENT</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Product Catalog</h3>
        <p class="text-slate-500 small mb-0">Control wholesale products, factory specifications, wholesale pricing tiers, and homepage featured highlights.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Export Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle shadow-sm px-3 py-2 rounded-3 fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1.5"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                <li><a class="dropdown-item py-2 btn-prod-export" href="#" data-format="csv"><i class="bi bi-filetype-csv text-primary me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2 btn-prod-export" href="#" data-format="excel"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Export as Excel</a></li>
                <li><a class="dropdown-item py-2 btn-prod-export" href="#" data-format="pdf"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Export as PDF</a></li>
            </ul>
        </div>

        <!-- Bulk Upload Button -->
        <button type="button" class="btn btn-outline-primary btn-sm shadow-sm px-3 py-2 rounded-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#bulkUploadProductModal" id="btnAdminBulkUpload">
            <i class="bi bi-file-earmark-arrow-up me-1.5"></i> Bulk Upload
        </button>

        <!-- Add New Button: Dedicated Full Page Route -->
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/products/create" class="btn btn-sj-primary btn-sm shadow-sm px-3 py-2 rounded-3 fw-semibold" id="btnAddNewProduct">
            <i class="bi bi-plus-lg me-1"></i> Add Product
        </a>
    </div>
</div>

<!-- Stat Metrics Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100 cursor-pointer clickable-card" id="cardProdTotal" data-card="total" role="button" tabindex="0" title="Click to show all products">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Products</div>
                    <div class="stat-number text-slate-900"><?= (int)($metrics['total'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#eff6ff;color:#2563eb;">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100 cursor-pointer clickable-card" id="cardProdLive" data-card="live" role="button" tabindex="0" title="Click to filter published & live products">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Published &amp; Live</div>
                    <div class="stat-number text-success"><?= (int)($metrics['approved'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100 cursor-pointer clickable-card" id="cardProdPending" data-card="pending" role="button" tabindex="0" title="Click to filter audit pending products">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Audit Pending</div>
                    <div class="stat-number text-warning"><?= (int)($metrics['pending'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100 cursor-pointer clickable-card" id="cardProdFeatured" data-card="featured" role="button" tabindex="0" title="Click to filter featured highlights">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Featured Highlights</div>
                    <div class="stat-number text-primary"><?= (int)($metrics['featured'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;">
                    <i class="bi bi-star-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.clickable-card {
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent !important;
    position: relative;
    user-select: none;
}
.clickable-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 40, 142, 0.12) !important;
}
.clickable-card.active-card {
    border-color: #00288e !important;
    background-color: #f0f4ff !important;
    box-shadow: 0 0 0 3px rgba(0, 40, 142, 0.18), 0 6px 16px rgba(0, 40, 142, 0.12) !important;
    transform: translateY(-2px);
}
.clickable-card.active-card::after {
    content: "ACTIVE FILTER";
    position: absolute;
    top: 6px;
    right: 8px;
    font-size: 9px;
    font-weight: 700;
    color: #00288e;
    background: #dbeafe;
    padding: 1px 6px;
    border-radius: 999px;
    letter-spacing: 0.5px;
}
</style>

<!-- Dedicated Filter Toolbar Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-slate-700 mb-1">
                    <i class="bi bi-grid text-primary me-1"></i>Filter by Category
                </label>
                <select id="prodCategoryFilter" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-slate-700 mb-1">
                    <i class="bi bi-toggle-on text-primary me-1"></i>Publishing Status
                </label>
                <select id="prodStatusFilter" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="PUBLISHED">Published &amp; Live</option>
                    <option value="DRAFT">Draft</option>
                    <option value="INACTIVE">Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-slate-700 mb-1">
                    <i class="bi bi-star-fill text-warning me-1"></i>Featured Highlights
                </label>
                <select id="prodFeaturedFilter" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    <option value="1">Featured Only</option>
                    <option value="0">Standard Only</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100 fw-semibold" id="btnResetFilters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Products DataTable Card -->
<div class="card card-premium shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-box-seam-fill text-primary"></i>
            <span class="fw-bold small text-uppercase text-slate-800">Products Catalog Directory</span>
        </div>
        <div class="small text-muted">
            <i class="bi bi-shield-check text-success me-1"></i>Real-time Server-Side Directory
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="productsTable" class="table sj-table table-hover align-middle w-100 mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 55px;" class="text-center">ID</th>
                        <th style="width: 65px;" class="text-center">Photo</th>
                        <th style="min-width: 260px;">Product Details</th>
                        <th style="min-width: 170px;">Category</th>
                        <th style="min-width: 160px;">Manufacturer / Supplier</th>
                        <th style="min-width: 140px;">Wholesale Price</th>
                        <th style="min-width: 90px;">MOQ</th>
                        <th style="min-width: 100px;" class="text-center">Featured</th>
                        <th style="min-width: 110px;" class="text-center">Status</th>
                        <th style="min-width: 140px;" class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<input type="hidden" id="csrfTokenHolder" name="_csrf" value="<?= CSRF::getToken() ?>">

<script>
window.SJ_BASE_URL = '<?= htmlspecialchars($baseUrl) ?>';
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction?.baseUrl || window.SJ_BASE_URL;
    let productsDataTable = null;
    let activeProductCardFilter = null;

    // Helper: HTML Escaping
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Helper: Toast
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const id = 'toast_' + Date.now();
        const bgClass = type === 'success' ? 'text-bg-success' : (type === 'danger' ? 'text-bg-danger' : 'text-bg-warning');
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
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

    // Check for redirected flash message from full-page form
    const flashMsg = sessionStorage.getItem('product_flash_message');
    if (flashMsg) {
        showToast(flashMsg, 'success');
        sessionStorage.removeItem('product_flash_message');
    }

    // Initialize Products DataTable
    productsDataTable = $('#productsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        autoWidth: false,
        ajax: {
            url: `${baseUrl}/api/products/data`,
            type: 'GET',
            data: function (d) {
                d.category_id = $('#prodCategoryFilter').val();
                d.status = $('#prodStatusFilter').val();
                d.is_featured = $('#prodFeaturedFilter').val();
                if (activeProductCardFilter === 'pending') {
                    d.audit_pending = '1';
                }
            },
            error: function (xhr, error, thrown) {
                console.error("Products table AJAX error:", xhr.status, thrown);
                const proc = document.getElementById('productsTable_processing');
                if (proc) proc.style.display = 'none';
            }
        },
        columns: [
            {
                data: 'id',
                width: '55px',
                className: 'text-center align-middle',
                render: function (data) {
                    return `<span class="fw-bold font-monospace text-secondary" style="font-size: 12px;">#${data}</span>`;
                }
            },
            {
                data: 'primary_image',
                width: '65px',
                orderable: false,
                searchable: false,
                className: 'text-center align-middle',
                render: function (data, type, row) {
                    const defaultLogo = window.SportsJunction.defaultLogo;
                    const imgSrc = window.SportsJunction.formatImageUrl(data);
                    return `
                        <img src="${imgSrc}" alt="${row.title || 'Product'}" class="table-product-thumb" onerror="this.onerror=null;this.src='${defaultLogo}';">
                    `;
                }
            },
            {
                data: 'title',
                render: function (data, type, row) {
                    return `
                        <div style="max-width: 320px;">
                            <a href="${baseUrl}/admin/products/edit/${row.id}" class="text-decoration-none">
                                <div class="table-product-title" title="${escapeHtml(data)}">${escapeHtml(data)}</div>
                            </a>
                            <div class="d-flex align-items-center gap-2 mt-0.5">
                                <span class="table-product-sku">${row.sku ? 'SKU: ' + escapeHtml(row.sku) : 'SKU: N/A'}</span>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: 'category_name',
                render: function (data, type, row) {
                    const catString = row.all_category_names || data || 'Unassigned';
                    const cats = catString.split(',').map(s => s.trim()).filter(Boolean);
                    let catBadges = cats.map(c => `<span class="badge badge-soft-primary px-2 py-0.5 rounded-pill me-1 mb-1">${escapeHtml(c)}</span>`).join('');
                    if (!catBadges) {
                        catBadges = '<span class="badge badge-soft-secondary px-2 py-0.5 rounded-pill">Unassigned</span>';
                    }
                    
                    const subcatString = row.all_subcategory_names || row.subcategory_name || '';
                    const subcat = subcatString ? `<div class="small text-muted mt-1 d-flex align-items-center flex-wrap"><i class="bi bi-arrow-return-right text-primary me-1" style="font-size: 11px;"></i>${escapeHtml(subcatString)}</div>` : '';
                    return `
                        <div class="d-flex flex-column align-items-start">
                            <div class="d-flex flex-wrap gap-1">${catBadges}</div>
                            ${subcat}
                        </div>
                    `;
                }
            },
            {
                data: 'supplier_name',
                render: function (data) {
                    return `
                        <div>
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;">${escapeHtml(data || 'Sports Junction Direct')}</div>
                            <div class="small text-muted"><i class="bi bi-patch-check-fill text-primary me-1" style="font-size: 11px;"></i>Verified Supplier</div>
                        </div>
                    `;
                }
            },
            {
                data: 'min_price',
                render: function (data, type, row) {
                    const minNum = parseFloat(data || 0);
                    const minFormatted = minNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const maxNum = row.max_price ? parseFloat(row.max_price) : 0;
                    const maxFormatted = maxNum > minNum ? maxNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : null;
                    const priceStr = maxFormatted ? `$${minFormatted} – $${maxFormatted}` : `$${minFormatted}`;
                    return `<div class="fw-bold text-success">${priceStr}</div>`;
                }
            },
            {
                data: 'moq_quantity',
                render: function (data, type, row) {
                    const qty = parseInt(data || 0).toLocaleString('en-IN');
                    const unit = row.moq_unit_symbol || row.moq_unit_name || 'pcs';
                    return `
                        <div>
                            <span class="fw-bold text-dark">${qty}</span>
                            <span class="small text-muted d-block">${unit}</span>
                        </div>
                    `;
                }
            },
            {
                data: 'is_featured',
                className: 'text-center align-middle',
                render: function (data, type, row) {
                    const isFeat = parseInt(data) === 1;
                    return `
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none btn-toggle-featured" data-id="${row.id}" data-featured="${isFeat ? 1 : 0}">
                            ${isFeat ? '<span class="badge-pill-featured"><i class="bi bi-star-fill text-warning"></i>Featured</span>' : '<span class="badge badge-soft-secondary px-2.5 py-1 rounded-pill">Standard</span>'}
                        </button>
                    `;
                }
            },
            {
                data: 'status',
                className: 'text-center align-middle',
                render: function (data, type, row) {
                    let badge = '';
                    if (data === 'PUBLISHED' || data === 'ACTIVE') {
                        badge = '<span class="badge badge-soft-success px-2.5 py-1 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Published</span>';
                    } else if (data === 'DRAFT') {
                        badge = '<span class="badge badge-soft-warning px-2.5 py-1 rounded-pill"><i class="bi bi-file-earmark-text me-1"></i>Draft</span>';
                    } else {
                        badge = '<span class="badge badge-soft-danger px-2.5 py-1 rounded-pill"><i class="bi bi-dash-circle me-1"></i>Inactive</span>';
                    }

                    let appBadge = '';
                    if (row.approval_status === 'PENDING_APPROVAL' || row.pending_changes) {
                        appBadge = '<div class="mt-1"><span class="badge badge-soft-warning" style="font-size: 10px;"><i class="bi bi-hourglass-split me-1"></i>Audit Pending</span></div>';
                    } else if (row.approval_status === 'REJECTED') {
                        appBadge = '<div class="mt-1"><span class="badge badge-soft-danger" style="font-size: 10px;"><i class="bi bi-x-circle me-1"></i>Rejected</span></div>';
                    }

                    return `<div>${badge}${appBadge}</div>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end align-middle pe-3',
                render: function (data, type, row) {
                    const viewUrl = `${baseUrl}/product/${row.slug || row.id}`;
                    return `
                        <div class="d-flex align-items-center justify-content-end gap-1">
                            <button type="button" class="btn-action-icon btn-action-view btn-view-product" data-id="${row.id}" title="Quick View" data-bs-toggle="tooltip">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="${baseUrl}/admin/products/edit/${row.id}" class="btn-action-icon btn-action-edit" title="Edit Product" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="${viewUrl}" target="_blank" class="btn-action-icon btn-action-external" title="Storefront View" data-bs-toggle="tooltip">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <button type="button" class="btn-action-icon btn-action-delete btn-delete-product" data-id="${row.id}" data-name="${row.title || row.name}" title="Delete Product" data-bs-toggle="tooltip">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        stateSave: true,
        order: [[0, 'desc']],
        drawCallback: function () {
            bindProductActions();
        }
    });
    window.productsDataTable = productsDataTable;

    function setProductCardHighlight(cardKey) {
        $('.clickable-card').removeClass('active-card');
        if (cardKey) {
            $(`.clickable-card[data-card="${cardKey}"]`).addClass('active-card');
        }
    }

    $('.clickable-card').on('click', function () {
        const clickedCard = $(this).data('card');

        // Toggle off if clicking the currently active card
        if (activeProductCardFilter === clickedCard) {
            activeProductCardFilter = null;
            setProductCardHighlight(null);
            $('#prodStatusFilter').val('');
            $('#prodFeaturedFilter').val('');
            productsDataTable.ajax.reload();
            return;
        }

        activeProductCardFilter = clickedCard;
        setProductCardHighlight(clickedCard);

        if (clickedCard === 'total') {
            $('#prodStatusFilter').val('');
            $('#prodFeaturedFilter').val('');
        } else if (clickedCard === 'live') {
            $('#prodStatusFilter').val('PUBLISHED');
            $('#prodFeaturedFilter').val('');
        } else if (clickedCard === 'pending') {
            $('#prodStatusFilter').val('');
            $('#prodFeaturedFilter').val('');
        } else if (clickedCard === 'featured') {
            $('#prodStatusFilter').val('');
            $('#prodFeaturedFilter').val('1');
        }

        productsDataTable.ajax.reload();
    });

    $('#prodStatusFilter, #prodFeaturedFilter').on('change', function () {
        const status = $('#prodStatusFilter').val();
        const feat = $('#prodFeaturedFilter').val();
        if (status === 'PUBLISHED' && feat === '') {
            activeProductCardFilter = 'live';
            setProductCardHighlight('live');
        } else if (feat === '1' && status === '') {
            activeProductCardFilter = 'featured';
            setProductCardHighlight('featured');
        } else if (activeProductCardFilter !== 'pending') {
            activeProductCardFilter = null;
            setProductCardHighlight(null);
        }
        productsDataTable.ajax.reload();
    });

    $('#prodCategoryFilter').on('change', function () {
        productsDataTable.ajax.reload();
    });

    $('#btnResetFilters').on('click', function () {
        activeProductCardFilter = null;
        setProductCardHighlight(null);
        $('#prodCategoryFilter').val('');
        $('#prodStatusFilter').val('');
        $('#prodFeaturedFilter').val('');
        productsDataTable.search('').draw();
    });

    function ynBadge(val, label) {
        const active = parseInt(val || 0) === 1;
        return `<span class="badge ${active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border'} me-1 mb-1">
            <i class="bi ${active ? 'bi-check-circle-fill' : 'bi-dash-circle'} me-1"></i>${label}: ${active ? 'Yes' : 'No'}
        </span>`;
    }

    // Bind view, featured toggle & delete
    function bindProductActions() {
        // View Full Product Details Modal
        $('.btn-view-product').off('click').on('click', function () {
            const id = $(this).data('id');
            const modal = new bootstrap.Modal(document.getElementById('productQuickViewModal'));
            modal.show();
            $('#quickViewBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
            $('#quickViewEditBtn').attr('href', `${baseUrl}/admin/products/edit/${id}`);

            fetch(`${baseUrl}/api/products/${id}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        $('#quickViewBody').html(`<div class="alert alert-danger">${res.message || 'Failed to load details'}</div>`);
                        return;
                    }
                    const p = res.data;
                    const sym = '$';
                    const img = window.SportsJunction.formatImageUrl(p.primary_image);
                    $('#quickViewTitle').text(`Product Details: ${p.title}`);

                    let galleryHtml = '';
                    if (p.images && p.images.length > 0) {
                        galleryHtml = '<div class="d-flex flex-wrap gap-2 mt-2">';
                        p.images.forEach(g => {
                            const gUrl = window.SportsJunction.formatImageUrl(g.image_url || g.image_path);
                            galleryHtml += `<img src="${gUrl}" onerror="this.onerror=null;this.src='${window.SportsJunction.defaultLogo}';" class="rounded border" style="width: 60px; height: 60px; object-fit: cover;">`;
                        });
                        galleryHtml += '</div>';
                    } else {
                        galleryHtml = '<div class="text-muted small">No gallery photos.</div>';
                    }

                    const html = `
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded-3 border mb-3">
                                    <img src="${img}" onerror="this.onerror=null;this.src='${window.SportsJunction.defaultLogo}';" class="img-fluid rounded" style="max-height: 220px; object-fit: contain;">
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold small text-uppercase text-muted d-block mb-1">Gallery Photos</label>
                                    ${galleryHtml}
                                </div>
                                <div class="p-3 bg-light rounded-3 border small">
                                    <div class="mb-1"><strong>Approval Status:</strong> <span class="badge ${p.approval_status === 'APPROVED' ? 'bg-success' : 'bg-warning text-dark'}">${p.approval_status}</span></div>
                                    <div class="mb-1"><strong>Publishing Status:</strong> <span class="badge bg-secondary">${p.status}</span></div>
                                    <div class="mb-1"><strong>Featured:</strong> ${parseInt(p.is_featured || 0) === 1 ? `Yes (Priority: ${p.featured_order || 0})` : 'No'}</div>
                                    <div class="mb-1"><strong>Supplier:</strong> ${p.supplier_name || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="border rounded-3 p-3 mb-3 small">
                                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-2">1. Basic Product Information</h6>
                                    <div class="row g-2">
                                        <div class="col-md-8"><strong>Title:</strong> ${p.title || '—'}</div>
                                        <div class="col-md-4"><strong>SKU:</strong> ${p.sku || 'N/A'}</div>
                                        <div class="col-md-6"><strong>Category:</strong> ${p.all_category_names || p.category_name || '—'}</div>
                                        <div class="col-md-6"><strong>Subcategory:</strong> ${p.all_subcategory_names || p.subcategory_name || '—'}</div>
                                        <div class="col-md-6"><strong>Brand:</strong> ${p.brand_name || '—'}</div>
                                        <div class="col-md-6"><strong>Slug:</strong> <code>${p.slug || '—'}</code></div>
                                        <div class="col-12"><strong>Short Summary:</strong> ${p.short_description || '—'}</div>
                                        <div class="col-12"><strong>Description:</strong><div class="p-2 bg-light rounded border mt-1" style="white-space: pre-wrap;">${p.description || '—'}</div></div>
                                    </div>
                                </div>
                                <div class="border rounded-3 p-3 mb-3 small">
                                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-2">2. Pricing &amp; MOQ</h6>
                                    <div class="row g-2">
                                        <div class="col-md-4"><strong>Price Range:</strong> ${sym}${parseFloat(p.min_price || 0).toFixed(2)} - ${sym}${parseFloat(p.max_price || 0).toFixed(2)}</div>
                                        <div class="col-md-4"><strong>MOQ:</strong> ${p.moq_quantity} ${p.moq_unit_name || ''}</div>
                                        <div class="col-md-4"><strong>Ready Stock:</strong> ${p.available_quantity ?? 0}</div>
                                        <div class="col-md-6"><strong>Sample Available:</strong> ${parseInt(p.sample_available || 0) === 1 ? `Yes (${sym}${parseFloat(p.sample_price || 0).toFixed(2)})` : 'No'}</div>
                                    </div>
                                </div>
                                <div class="border rounded-3 p-3 mb-3 small">
                                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-2">3. Specifications &amp; Customization</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6"><strong>Material:</strong> ${p.material || '—'}</div>
                                        <div class="col-md-6"><strong>Colors:</strong> ${p.colors || '—'}</div>
                                        <div class="col-md-6"><strong>Size:</strong> ${p.size || '—'}</div>
                                        <div class="col-md-6"><strong>Weight:</strong> ${p.weight || '—'}</div>
                                        <div class="col-12 mt-1">
                                            ${ynBadge(p.custom_logo, 'Custom Logo')}
                                            ${ynBadge(p.custom_color, 'Custom Color')}
                                            ${ynBadge(p.custom_packaging, 'Custom Packaging')}
                                            ${ynBadge(p.oem_available, 'OEM')}
                                            ${ynBadge(p.odm_available, 'ODM')}
                                            ${ynBadge(p.private_label, 'Private Label')}
                                        </div>
                                        <div class="col-12"><strong>Customization Details:</strong> ${p.customization_details || '—'}</div>
                                    </div>
                                </div>
                                <div class="border rounded-3 p-3 small">
                                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-2">4. Shipping &amp; Origin</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6"><strong>Country:</strong> ${p.country_name || 'India'}</div>
                                        <div class="col-md-6"><strong>Lead Time:</strong> ${p.lead_time || '—'}</div>
                                        <div class="col-12"><strong>Shipping Info:</strong> ${p.shipping_info || '—'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#quickViewBody').html(html);
                });
        });

        // Toggle Featured
        $('.btn-toggle-featured').off('click').on('click', function () {
            const id = $(this).data('id');
            const current = parseInt($(this).data('featured')) === 1;
            const newFeat = current ? 0 : 1;
            const csrfToken = document.getElementById('csrfTokenHolder').value;

            fetch(`${baseUrl}/api/products/${id}/featured`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `is_featured=${newFeat}&_csrf=${csrfToken}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    productsDataTable.ajax.reload(null, false);
                } else {
                    showToast(data.message || 'Error updating featured flag', 'danger');
                }
            });
        });

        // Delete Product
        $('.btn-delete-product').off('click').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const csrfToken = document.getElementById('csrfTokenHolder').value;

            if (confirm(`Are you sure you want to permanently delete '${name}'? This cannot be undone.`)) {
                fetch(`${baseUrl}/api/products/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `_csrf=${csrfToken}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        productsDataTable.ajax.reload(function () {
                            const info = productsDataTable.page.info();
                            if (info && info.pages > 0 && info.page >= info.pages) {
                                productsDataTable.page(Math.max(0, info.pages - 1)).draw('page');
                            }
                        }, false);
                    } else {
                        showToast(data.message || 'Failed to delete product', 'danger');
                    }
                });
            }
        });
    }

    // Export handler
    $('.btn-prod-export').on('click', function (e) {
        e.preventDefault();
        const format = $(this).data('format');
        const search = productsDataTable.search();
        const status = $('#prodStatusFilter').val();
        window.location.href = `${baseUrl}/api/products/export?format=${format}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
    });
});
</script>

<!-- Product Quick View Modal -->
<div class="modal fade" id="productQuickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6" id="quickViewTitle">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="quickViewBody"></div>
            <div class="modal-footer bg-light border-top py-2.5 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <a href="#" id="quickViewEditBtn" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i>Edit All Product Details
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Product Upload Modal -->
<div class="modal fade" id="bulkUploadProductModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-arrow-up fs-5"></i>
                    <h5 class="modal-title fw-bold m-0 fs-6">Bulk Product Upload (Excel / CSV)</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Template Download Card -->
                <div class="alert alert-info border-info-subtle d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-download me-1.5 text-primary"></i>Official Product Upload Template</h6>
                        <p class="small text-muted mb-0">Use our formatted Excel template with pre-configured headers, multi-category support, and sample products.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 shrink-0">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/api/products/bulk-template?format=xlsx" class="btn btn-success btn-sm shadow-sm">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Template (.xlsx)
                        </a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/api/products/bulk-template?format=csv" class="btn btn-outline-secondary btn-sm shadow-sm bg-white">
                            <i class="bi bi-filetype-csv me-1"></i> CSV
                        </a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/api/products/import-reference?format=xlsx" class="btn btn-primary btn-sm shadow-sm">
                            <i class="bi bi-database-down me-1"></i> Reference Data (.xlsx)
                        </a>
                    </div>
                </div>

                <!-- Supplier Selection for Super Admin -->
                <div class="mb-4 bg-light p-3 rounded-3 border">
                    <label class="form-label fw-bold text-dark small mb-1" for="adminBulkSupplierSelect">
                        Target Supplier <span class="text-danger">*</span>
                    </label>
                    <select id="adminBulkSupplierSelect" class="form-select form-select-sm" style="max-width: 480px;">
                        <option value="">-- Choose Supplier (or specify in file row) --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int)$sup['id'] ?>"><?= htmlspecialchars($sup['company_name'] ?: ($sup['name'] ?? 'Supplier #' . $sup['id'])) ?> (ID: <?= (int)$sup['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted" style="font-size: 11px;">
                        Products will be assigned to this verified supplier unless an explicit supplier ID or company name is given in the spreadsheet.
                    </div>
                </div>

                <!-- Upload Drag-Drop Box -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small mb-2">Select Spreadsheet to Import (.xlsx or .csv)</label>
                    <div class="border-2 border-dashed rounded-3 p-4 text-center bg-light" id="adminBulkDropzone" style="border-color: #cbd5e1 !important; cursor: pointer;">
                        <input type="file" id="adminBulkFileInput" class="d-none" accept=".xlsx,.csv,.txt">
                        <i class="bi bi-cloud-arrow-up display-6 text-primary mb-2 d-block"></i>
                        <span class="fw-bold text-dark d-block">Click to browse file or drag &amp; drop here</span>
                        <span class="text-muted small d-block mt-1">Supported formats: Microsoft Excel (.xlsx) and Comma-Separated Values (.csv) up to 15MB</span>
                        <div id="adminBulkSelectedFileInfo" class="mt-3 d-none">
                            <span class="badge bg-primary px-3 py-2 fs-7" id="adminBulkFileName">filename.xlsx</span>
                        </div>
                    </div>
                </div>

                <!-- Preview / Validation Results Section -->
                <div id="adminBulkPreviewSection" class="d-none">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                            <i class="bi bi-clipboard-check text-success"></i> Upload Validation Summary
                        </h6>
                        <div class="d-flex align-items-center gap-2" id="adminBulkStatsBadges"></div>
                    </div>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-3" id="adminBulkTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold text-danger d-flex align-items-center gap-1.5" id="adminTabBtnErrors" type="button">
                                <i class="bi bi-exclamation-octagon-fill"></i> Failed Records <span class="badge bg-danger rounded-pill ms-1" id="adminBadgeCountErrors">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold text-success d-flex align-items-center gap-1.5" id="adminTabBtnValid" type="button">
                                <i class="bi bi-check-circle-fill"></i> Successful Records <span class="badge bg-success rounded-pill ms-1" id="adminBadgeCountValid">0</span>
                            </button>
                        </li>
                        <li class="nav-item d-none" id="adminTabLiSkipped">
                            <button class="nav-link fw-bold text-warning d-flex align-items-center gap-1.5" id="adminTabBtnSkipped" type="button">
                                <i class="bi bi-slash-circle-fill"></i> Skipped / Duplicates <span class="badge bg-warning text-dark rounded-pill ms-1" id="adminBadgeCountSkipped">0</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Pane 1: Failed Records & Error Table -->
                    <div id="adminPaneErrors" class="mb-3">
                        <div class="alert alert-danger p-3 mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="fw-bold text-danger mb-0 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Row-by-Row Validation Issues Found
                                </h6>
                                <span class="small text-danger-emphasis">Review invalid fields, exact error descriptions, and suggested corrections below.</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" id="btnAdminDownloadErrorsXlsx" class="btn btn-sm btn-danger fw-bold shadow-xs">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Download Report (.xlsx)
                                </button>
                                <button type="button" id="btnAdminDownloadErrorsCsv" class="btn btn-sm btn-outline-danger bg-white fw-bold shadow-xs">
                                    <i class="bi bi-filetype-csv me-1"></i> CSV
                                </button>
                            </div>
                        </div>

                        <!-- Detailed Error Table -->
                        <div class="table-responsive border rounded-3" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 text-nowrap small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 85px;">Excel Row</th>
                                        <th style="width: 220px;">Product Name / SKU</th>
                                        <th style="width: 140px;">Field</th>
                                        <th>Error Description</th>
                                        <th>Suggested Fix</th>
                                    </tr>
                                </thead>
                                <tbody id="adminBulkErrorTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Pane 2: Successful Records -->
                    <div id="adminPaneValid" class="d-none mb-3">
                        <div class="alert alert-success p-2.5 mb-2 d-flex align-items-center justify-content-between">
                            <span class="small fw-bold text-success-emphasis"><i class="bi bi-check-circle-fill me-1 text-success"></i> Products validated and ready for immediate catalog import.</span>
                            <span class="badge bg-success" id="adminValidSummaryCount">0 Ready</span>
                        </div>
                        <div class="table-responsive border rounded-3" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 text-nowrap small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>#</th>
                                        <th>Row</th>
                                        <th>Product Name</th>
                                        <th>Categories</th>
                                        <th>Min Price ($)</th>
                                        <th>Max Price ($)</th>
                                        <th>MOQ</th>
                                        <th>SKU</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="adminBulkPreviewTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Pane 3: Skipped Records -->
                    <div id="adminPaneSkipped" class="d-none mb-3">
                        <div class="alert alert-warning p-2.5 mb-2 small fw-bold text-warning-emphasis">
                            <i class="bi bi-slash-circle-fill me-1 text-warning"></i> These rows were skipped because they contain duplicate SKUs or invalid formats.
                        </div>
                        <div class="table-responsive border rounded-3" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 text-nowrap small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Excel Row</th>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Reason Skipped</th>
                                        <th>Suggested Fix</th>
                                    </tr>
                                </thead>
                                <tbody id="adminBulkSkippedTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2.5 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnAdminBulkValidate" disabled>
                        <i class="bi bi-shield-check me-1"></i> Validate &amp; Preview
                    </button>
                    <button type="button" class="btn btn-success btn-sm px-4 d-none" id="btnAdminBulkCommit">
                        <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Import Products
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction?.baseUrl || window.SJ_BASE_URL || '<?= htmlspecialchars($baseUrl) ?>';
    const dropzone = document.getElementById('adminBulkDropzone');
    const fileInput = document.getElementById('adminBulkFileInput');
    const fileInfo = document.getElementById('adminBulkSelectedFileInfo');
    const fileNameBadge = document.getElementById('adminBulkFileName');
    const btnValidate = document.getElementById('btnAdminBulkValidate');
    const btnCommit = document.getElementById('btnAdminBulkCommit');
    const previewSection = document.getElementById('adminBulkPreviewSection');
    const statsContainer = document.getElementById('adminBulkStatsBadges');
    const previewTableBody = document.getElementById('adminBulkPreviewTableBody');

    // Admin Tabs & Tables
    const tabBtnErrors = document.getElementById('adminTabBtnErrors');
    const tabBtnValid = document.getElementById('adminTabBtnValid');
    const tabBtnSkipped = document.getElementById('adminTabBtnSkipped');
    const tabLiSkipped = document.getElementById('adminTabLiSkipped');
    const paneErrors = document.getElementById('adminPaneErrors');
    const paneValid = document.getElementById('adminPaneValid');
    const paneSkipped = document.getElementById('adminPaneSkipped');
    const badgeCountErrors = document.getElementById('adminBadgeCountErrors');
    const badgeCountValid = document.getElementById('adminBadgeCountValid');
    const badgeCountSkipped = document.getElementById('adminBadgeCountSkipped');
    const errorTableBody = document.getElementById('adminBulkErrorTableBody');
    const skippedTableBody = document.getElementById('adminBulkSkippedTableBody');
    const validSummaryCount = document.getElementById('adminValidSummaryCount');

    let currentFile = null;
    let currentFileToken = null;
    let lastDetailedErrors = [];

    function switchAdminTab(activeTab) {
        const tabs = [
            { btn: tabBtnErrors, pane: paneErrors, activeClass: 'active text-danger border-danger' },
            { btn: tabBtnValid, pane: paneValid, activeClass: 'active text-success border-success' },
            { btn: tabBtnSkipped, pane: paneSkipped, activeClass: 'active text-warning border-warning' }
        ];

        tabs.forEach(t => {
            if (!t.btn || !t.pane) return;
            if (t.btn === activeTab) {
                t.btn.classList.add('active');
                t.pane.classList.remove('d-none');
            } else {
                t.btn.classList.remove('active');
                t.pane.classList.add('d-none');
            }
        });
    }

    if (tabBtnErrors) tabBtnErrors.addEventListener('click', () => switchAdminTab(tabBtnErrors));
    if (tabBtnValid) tabBtnValid.addEventListener('click', () => switchAdminTab(tabBtnValid));
    if (tabBtnSkipped) tabBtnSkipped.addEventListener('click', () => switchAdminTab(tabBtnSkipped));

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('bg-white', 'border-primary');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('bg-white', 'border-primary');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('bg-white', 'border-primary');
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
        fileInfo.classList.remove('d-none');
        btnValidate.disabled = false;
        btnCommit.classList.add('d-none');
        previewSection.classList.add('d-none');
    }

    if (btnValidate) {
        btnValidate.addEventListener('click', async function () {
            if (!currentFile) return;

            btnValidate.disabled = true;
            btnValidate.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validating...';

            const csrfToken = document.querySelector('input[name="_csrf"]')?.value || '<?= CSRF::getToken() ?>';
            const formData = new FormData();
            formData.append('file', currentFile);
            formData.append('_csrf', csrfToken);

            const supSelect = document.getElementById('adminBulkSupplierSelect');
            if (supSelect && supSelect.value) {
                formData.append('supplier_id', supSelect.value);
            }

            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
            }

            try {
                const res = await fetch(`${baseUrl}/api/products/bulk-preview`, {
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

                if (!data.success && (!data.detailed_errors || data.detailed_errors.length === 0)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        text: data.message || 'Unable to parse spreadsheet.',
                        confirmButtonColor: '#00288e'
                    });
                    btnValidate.disabled = false;
                    btnValidate.innerHTML = '<i class="bi bi-shield-check me-1"></i> Validate &amp; Preview';
                    return;
                }

                currentFileToken = data.file_token;
                lastDetailedErrors = data.detailed_errors || [];
                previewSection.classList.remove('d-none');

                const stats = data.stats || {};
                const totalRows = stats.total || data.total_rows || 0;
                const validRows = stats.valid || data.valid_rows_count || 0;
                const failedRows = stats.failed || data.error_rows_count || 0;
                const skippedRows = stats.skipped || 0;
                const totalIssues = stats.issues || lastDetailedErrors.length || 0;

                badgeCountErrors.textContent = totalIssues;
                badgeCountValid.textContent = validRows;
                badgeCountSkipped.textContent = skippedRows;
                if (validSummaryCount) validSummaryCount.textContent = `${validRows} Ready to Import`;

                statsContainer.innerHTML = `
                    <span class="badge bg-secondary">Total Rows: ${totalRows}</span>
                    <span class="badge bg-success">Valid: ${validRows}</span>
                    ${failedRows > 0 ? `<span class="badge bg-danger">Failed: ${failedRows} (${totalIssues} issues)</span>` : ''}
                    ${skippedRows > 0 ? `<span class="badge bg-warning text-dark">Skipped: ${skippedRows}</span>` : ''}
                `;

                // Render Error Table
                if (lastDetailedErrors.length > 0) {
                    errorTableBody.innerHTML = lastDetailedErrors.map(err => {
                        return `
                            <tr>
                                <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle font-mono fw-bold">Row ${err.row || '—'}</span></td>
                                <td>
                                    <div class="fw-bold text-dark">${escapeHtml(err.product || '—')}</div>
                                    <div class="small font-mono text-muted">SKU: ${escapeHtml(err.sku || '—')}</div>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary border">${escapeHtml(err.field || 'General')}</span></td>
                                <td class="text-danger fw-semibold">${escapeHtml(err.error || 'Validation error')}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success-emphasis border text-wrap text-start">
                                        <i class="bi bi-lightbulb me-1"></i>${escapeHtml(err.suggested_fix || 'Check spreadsheet value')}
                                    </span>
                                </td>
                            </tr>
                        `;
                    }).join('');
                    switchAdminTab(tabBtnErrors);
                } else {
                    errorTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>All rows passed validation!</td></tr>';
                    switchAdminTab(tabBtnValid);
                }

                // Render Valid Records Table
                const validRecords = data.preview_rows || data.valid_records || [];
                if (validRecords.length > 0) {
                    previewTableBody.innerHTML = validRecords.map((row, idx) => `
                        <tr>
                            <td><span class="badge bg-light text-muted border">${idx + 1}</span></td>
                            <td><span class="badge bg-light text-dark border">Row ${row.row_num || idx + 2}</span></td>
                            <td class="fw-bold text-dark">${escapeHtml(row.name || row.title || '—')}</td>
                            <td><span class="badge bg-primary-subtle text-primary border">${row.category_names ? escapeHtml(row.category_names.join(', ')) : 'Category'}</span></td>
                            <td class="text-success fw-bold">$${parseFloat(row.min_price || 0).toFixed(2)}</td>
                            <td>$${parseFloat(row.max_price || 0).toFixed(2)}</td>
                            <td>${row.moq || 1}</td>
                            <td><code class="text-muted small">${escapeHtml(row.sku || '-')}</code></td>
                            <td><span class="badge bg-success-subtle text-success border">Ready</span></td>
                        </tr>
                    `).join('');
                } else {
                    previewTableBody.innerHTML = '<tr><td colspan="9" class="text-center py-3 text-muted">No valid rows available to import. Please resolve the issues above.</td></tr>';
                }

                // Render Skipped Table
                const skippedRecords = data.skipped_records || [];
                if (skippedRecords.length > 0) {
                    tabLiSkipped.classList.remove('d-none');
                    skippedTableBody.innerHTML = skippedRecords.map(s => `
                        <tr>
                            <td><span class="badge bg-warning-subtle text-warning-emphasis border font-mono fw-bold">Row ${s.row}</span></td>
                            <td class="fw-bold text-dark">${escapeHtml(s.product || '—')}</td>
                            <td class="font-mono text-muted">${escapeHtml(s.sku || '—')}</td>
                            <td class="text-warning-emphasis fw-semibold">${escapeHtml(s.message || 'Duplicate SKU or duplicate entry')}</td>
                            <td class="text-muted">Provide a unique SKU or leave blank to auto-generate.</td>
                        </tr>
                    `).join('');
                } else {
                    tabLiSkipped.classList.add('d-none');
                }

                if (validRows > 0) {
                    btnCommit.classList.remove('d-none');
                    btnCommit.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Confirm &amp; Import (${validRows}) Products`;
                } else {
                    btnCommit.classList.add('d-none');
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: err.message || 'Failed to contact validation server.',
                    confirmButtonColor: '#00288e'
                });
            } finally {
                btnValidate.disabled = false;
                btnValidate.innerHTML = '<i class="bi bi-shield-check me-1"></i> Re-Validate';
            }
        });
    }

    // Admin Error Report Download Handlers
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const btnAdminDownloadXlsx = document.getElementById('btnAdminDownloadErrorsXlsx');
    if (btnAdminDownloadXlsx) {
        btnAdminDownloadXlsx.addEventListener('click', function () {
            if (currentFileToken) {
                window.location.href = `${baseUrl}/api/products/bulk-error-report?token=${encodeURIComponent(currentFileToken)}&format=xlsx`;
            }
        });
    }

    const btnAdminDownloadCsv = document.getElementById('btnAdminDownloadErrorsCsv');
    if (btnAdminDownloadCsv) {
        btnAdminDownloadCsv.addEventListener('click', function () {
            if (currentFileToken) {
                window.location.href = `${baseUrl}/api/products/bulk-error-report?token=${encodeURIComponent(currentFileToken)}&format=csv`;
            }
        });
    }

    if (btnCommit) {
        btnCommit.addEventListener('click', async function () {
            if (!currentFileToken) return;

            btnCommit.disabled = true;
            btnCommit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing...';

            const csrfToken = document.querySelector('input[name="_csrf"]')?.value || '<?= CSRF::getToken() ?>';

            try {
                const res = await fetch(`${baseUrl}/api/products/bulk-commit`, {
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
                        title: 'Import Complete!',
                        text: data.message,
                        confirmButtonColor: '#00288e'
                    }).then(() => {
                        const modalEl = document.getElementById('bulkUploadProductModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (window.productsDataTable) {
                            window.productsDataTable.ajax.reload(null, false);
                        } else {
                            setTimeout(() => location.reload(), 500);
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed',
                        text: data.message || 'Could not complete import.',
                        confirmButtonColor: '#00288e'
                    });
                    btnCommit.disabled = false;
                    btnCommit.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Try Again';
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Import Error',
                    text: 'Network communication failed during import transaction.',
                    confirmButtonColor: '#00288e'
                });
                btnCommit.disabled = false;
            }
        });
    }
});
</script>
