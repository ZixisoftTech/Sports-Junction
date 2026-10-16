<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-box-seam-fill text-primary me-2"></i>Product Catalog Management</h4>
        <p class="text-muted small mb-0">Control wholesale products, factory pricing tiers, customization specifications, and homepage featured highlights.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <!-- Export Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle shadow-sm px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1.5"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                <li><a class="dropdown-item btn-prod-export" href="#" data-format="csv"><i class="bi bi-file-earmark-text text-primary me-2"></i>Export CSV</a></li>
                <li><a class="dropdown-item btn-prod-export" href="#" data-format="excel"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Export Excel</a></li>
                <li><a class="dropdown-item btn-prod-export" href="#" data-format="pdf"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Export PDF</a></li>
            </ul>
        </div>

        <!-- Bulk Upload Button -->
        <button type="button" class="btn btn-outline-primary btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#bulkUploadProductModal" id="btnAdminBulkUpload">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Bulk Upload Products
        </button>

        <!-- Add New Button: Dedicated Full Page Route -->
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/products/create" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewProduct">
            <i class="bi bi-plus-lg me-1"></i> Add Product
        </a>
    </div>
</div>

<div class="sj-table-card mb-4">
    <div class="sj-table-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-box-seam-fill text-primary"></i> Enterprise Product Catalog
            </h6>
            <span class="text-muted small">Live factory equipment, supplier catalogs, wholesale pricing &amp; status</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <label for="prodCategoryFilter" class="small text-muted text-nowrap m-0 fw-semibold">Category:</label>
            <select id="prodCategoryFilter" class="form-select form-select-sm" style="min-width: 170px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="prodStatusFilter" class="small text-muted text-nowrap m-0 fw-semibold">Status:</label>
            <select id="prodStatusFilter" class="form-select form-select-sm" style="min-width: 130px;">
                <option value="">All Statuses</option>
                <option value="PUBLISHED">Published</option>
                <option value="DRAFT">Draft</option>
                <option value="INACTIVE">Inactive</option>
            </select>
        </div>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table id="productsTable" class="table sj-table table-hover align-middle w-100">
                <thead>
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
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction.baseUrl;
    let productsDataTable = null;

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
                                <div class="table-product-title" title="${data}">${data}</div>
                            </a>
                            <div class="d-flex align-items-center gap-2 mt-0.5">
                                <span class="table-product-sku">${row.sku ? 'SKU: ' + row.sku : 'SKU: N/A'}</span>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: 'category_name',
                render: function (data, type, row) {
                    const catName = data || 'Unassigned';
                    const subcat = row.subcategory_name ? `<div class="small text-muted mt-1 d-flex align-items-center"><i class="bi bi-arrow-return-right text-primary me-1" style="font-size: 11px;"></i>${row.subcategory_name}</div>` : '';
                    return `
                        <div>
                            <span class="badge badge-soft-primary px-2.5 py-1 rounded-pill">${catName}</span>
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
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;">${data || 'Sports Junction Direct'}</div>
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
                    const unit = row.price_unit_symbol || row.price_unit_name || 'Unit';
                    const priceStr = maxFormatted ? `$${minFormatted} – $${maxFormatted}` : `$${minFormatted}`;
                    return `
                        <div>
                            <div class="fw-bold text-success">${priceStr}</div>
                            <div class="small text-muted">/ ${unit}</div>
                        </div>
                    `;
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
        order: [[0, 'desc']],
        drawCallback: function () {
            bindProductActions();
        }
    });

    $('#prodCategoryFilter, #prodStatusFilter').on('change', function () {
        productsDataTable.ajax.reload();
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
                    const sym = p.price_unit_symbol || '$';
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
                                        <div class="col-md-6"><strong>Category:</strong> ${p.category_name || '—'}</div>
                                        <div class="col-md-6"><strong>Subcategory:</strong> ${p.subcategory_name || '—'}</div>
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
                        productsDataTable.ajax.reload();
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
                    <div class="d-flex align-items-center gap-2 shrink-0">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/api/products/bulk-template?format=xlsx" class="btn btn-success btn-sm shadow-sm">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Template (.xlsx)
                        </a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/api/products/bulk-template?format=csv" class="btn btn-outline-secondary btn-sm shadow-sm bg-white">
                            <i class="bi bi-filetype-csv me-1"></i> Download CSV
                        </a>
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

                    <!-- Error Messages Box -->
                    <div id="adminBulkErrorsContainer" class="d-none mb-3">
                        <div class="alert alert-danger p-3 mb-0">
                            <h6 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle-fill me-1.5"></i>Row-by-Row Validation Issues Found:</h6>
                            <ul class="mb-0 ps-3 small text-danger" id="adminBulkErrorsList" style="max-height: 160px; overflow-y: auto;"></ul>
                        </div>
                    </div>

                    <!-- Preview Table -->
                    <div class="table-responsive border rounded-3" style="max-height: 260px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0 text-nowrap small">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
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
    const dropzone = document.getElementById('adminBulkDropzone');
    const fileInput = document.getElementById('adminBulkFileInput');
    const fileInfo = document.getElementById('adminBulkSelectedFileInfo');
    const fileNameBadge = document.getElementById('adminBulkFileName');
    const btnValidate = document.getElementById('btnAdminBulkValidate');
    const btnCommit = document.getElementById('btnAdminBulkCommit');
    const previewSection = document.getElementById('adminBulkPreviewSection');
    const statsContainer = document.getElementById('adminBulkStatsBadges');
    const errorsContainer = document.getElementById('adminBulkErrorsContainer');
    const errorsList = document.getElementById('adminBulkErrorsList');
    const previewTableBody = document.getElementById('adminBulkPreviewTableBody');

    let currentFile = null;
    let currentFileToken = null;

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

            const formData = new FormData();
            formData.append('file', currentFile);

            try {
                const res = await fetch(`${baseUrl}/api/products/bulk-preview`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (!data.success) {
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
                previewSection.classList.remove('d-none');

                statsContainer.innerHTML = `
                    <span class="badge bg-secondary">Total: ${data.total_rows}</span>
                    <span class="badge bg-success">Valid: ${data.valid_rows_count}</span>
                    <span class="badge bg-danger">Issues: ${data.error_rows_count}</span>
                `;

                if (data.errors && data.errors.length > 0) {
                    errorsContainer.classList.remove('d-none');
                    errorsList.innerHTML = data.errors.map(err => `
                        <li><strong>Row ${err.row} [${err.product}]:</strong> ${err.errors.join('; ')}</li>
                    `).join('');
                } else {
                    errorsContainer.classList.add('d-none');
                    errorsList.innerHTML = '';
                }

                if (data.preview_rows && data.preview_rows.length > 0) {
                    previewTableBody.innerHTML = data.preview_rows.map(row => `
                        <tr>
                            <td><span class="badge bg-light text-muted border">Row ${row.row_num}</span></td>
                            <td class="fw-bold text-dark">${row.name}</td>
                            <td><span class="badge bg-primary-subtle text-primary border">${row.category_names ? row.category_names.join(', ') : 'Category'}</span></td>
                            <td class="text-success fw-bold">$${parseFloat(row.min_price).toFixed(2)}</td>
                            <td>$${parseFloat(row.max_price).toFixed(2)}</td>
                            <td>${row.moq}</td>
                            <td><code class="text-muted small">${row.sku || '-'}</code></td>
                            <td><span class="badge bg-success-subtle text-success border">Ready</span></td>
                        </tr>
                    `).join('');
                } else {
                    previewTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">No valid rows available to import.</td></tr>';
                }

                if (data.valid_rows_count > 0) {
                    btnCommit.classList.remove('d-none');
                    btnCommit.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Confirm &amp; Import (${data.valid_rows_count}) Products`;
                } else {
                    btnCommit.classList.add('d-none');
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to contact validation server.',
                    confirmButtonColor: '#00288e'
                });
            } finally {
                btnValidate.disabled = false;
                btnValidate.innerHTML = '<i class="bi bi-shield-check me-1"></i> Re-Validate';
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
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        file_token: currentFileToken,
                        _csrf: csrfToken
                    })
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Complete!',
                        text: data.message,
                        confirmButtonColor: '#00288e'
                    }).then(() => {
                        const modalEl = document.getElementById('bulkUploadProductModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                        if (typeof productsDataTable !== 'undefined') {
                            productsDataTable.ajax.reload();
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
