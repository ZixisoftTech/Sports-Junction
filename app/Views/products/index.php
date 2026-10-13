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

        <!-- Add New Button: Dedicated Full Page Route -->
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/products/create" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewProduct">
            <i class="bi bi-plus-lg me-1"></i> Add Product
        </a>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-4">
                <h6 class="m-0 fw-bold text-dark">Enterprise Product Inventory</h6>
            </div>
            <div class="col-sm-8 d-flex justify-content-sm-end align-items-center gap-2 flex-wrap">
                <label for="prodCategoryFilter" class="small text-muted text-nowrap m-0">Category:</label>
                <select id="prodCategoryFilter" class="form-select form-select-sm" style="max-width: 170px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="prodStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="prodStatusFilter" class="form-select form-select-sm" style="max-width: 130px;">
                    <option value="">All Statuses</option>
                    <option value="PUBLISHED">Published</option>
                    <option value="DRAFT">Draft</option>
                    <option value="INACTIVE">Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="productsTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 70px;">Photo</th>
                        <th>Product Title</th>
                        <th>Category / Subcategory</th>
                        <th style="width: 140px;">Supplier / Factory</th>
                        <th style="width: 120px;">Price Range</th>
                        <th style="width: 90px;">MOQ</th>
                        <th style="width: 80px;" class="text-center">Featured</th>
                        <th style="width: 90px;" class="text-center">Status</th>
                        <th style="width: 120px;" class="text-end">Actions</th>
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
        responsive: true,
        ajax: {
            url: `${baseUrl}/api/products/data`,
            type: 'GET',
            data: function (d) {
                d.category_id = $('#prodCategoryFilter').val();
                d.status = $('#prodStatusFilter').val();
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            {
                data: 'primary_image',
                width: '70px',
                orderable: false,
                searchable: false,
                render: function (data) {
                    const defaultLogo = window.SportsJunction.defaultLogo;
                    const imgSrc = window.SportsJunction.formatImageUrl(data);
                    return `
                        <div class="sj-table-thumb">
                            <img src="${imgSrc}" alt="Thumbnail" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;" onerror="this.onerror=null;this.src='${defaultLogo}';">
                        </div>
                    `;
                }
            },
            {
                data: 'title',
                render: function (data, type, row) {
                    return `
                        <div class="fw-bold text-dark">${data}</div>
                        <div class="small text-muted font-monospace">${row.sku ? 'SKU: ' + row.sku : 'SKU: N/A'}</div>
                    `;
                }
            },
            {
                data: 'category_name',
                render: function (data, type, row) {
                    return `
                        <div><span class="badge bg-light text-dark border">${data || 'Unassigned'}</span></div>
                        <div class="small text-muted mt-1"><i class="bi bi-arrow-return-right me-1"></i>${row.subcategory_name || 'None'}</div>
                    `;
                }
            },
            {
                data: 'supplier_name',
                width: '140px',
                render: function (data) {
                    return `<span class="small fw-semibold text-secondary">${data || 'Sports Junction Direct'}</span>`;
                }
            },
            {
                data: 'min_price',
                width: '120px',
                render: function (data, type, row) {
                    const sym = row.price_unit_symbol || '₹';
                    const max = row.max_price ? parseFloat(row.max_price).toFixed(2) : parseFloat(data).toFixed(2);
                    return `
                        <div class="fw-bold text-success">${sym}${parseFloat(data).toFixed(2)}</div>
                        <div class="small text-muted">${sym}${max} / ${row.price_unit_name || 'Unit'}</div>
                    `;
                }
            },
            {
                data: 'moq_quantity',
                width: '90px',
                render: function (data, type, row) {
                    return `${data} <span class="small text-muted">${row.moq_unit_symbol || 'pcs'}</span>`;
                }
            },
            {
                data: 'is_featured',
                width: '80px',
                className: 'text-center',
                render: function (data, type, row) {
                    const isFeat = parseInt(data) === 1;
                    return `
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none btn-toggle-featured" data-id="${row.id}" data-featured="${isFeat ? 1 : 0}">
                            ${isFeat ? '<span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Featured</span>' : '<span class="badge bg-light text-muted border">Standard</span>'}
                        </button>
                    `;
                }
            },
            {
                data: 'status',
                width: '90px',
                className: 'text-center',
                render: function (data) {
                    let badgeClass = 'bg-secondary';
                    if (data === 'PUBLISHED' || data === 'ACTIVE') badgeClass = 'bg-success';
                    else if (data === 'DRAFT') badgeClass = 'bg-warning text-dark';
                    else if (data === 'INACTIVE') badgeClass = 'bg-danger';

                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                width: '120px',
                className: 'text-end',
                render: function (data, type, row) {
                    const viewUrl = `${baseUrl}/product/${row.slug || row.id}`;
                    return `
                        <div class="btn-group btn-group-sm">
                            <a href="${viewUrl}" target="_blank" class="btn btn-outline-secondary" title="View live product storefront">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <a href="${baseUrl}/admin/products/edit/${row.id}" class="btn btn-outline-primary" title="Edit product on dedicated page">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-delete-product" data-id="${row.id}" data-name="${row.title || row.name}" title="Delete product">
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

    // Bind featured toggle & delete
    function bindProductActions() {
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
