<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Catalog Submissions &amp; Approvals</h4>
            <p class="text-muted small mb-0">View, audit, and edit all product details both before and after approval across the B2B marketplace.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/products/create" class="btn btn-sj-primary btn-sm shadow-sm px-3">
                <i class="bi bi-plus-lg me-1"></i> Add Product
            </a>
        </div>
    </div>

    <!-- Clickable Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-warning border-4 cursor-pointer" style="cursor: pointer;" onclick="setApprovalFilter('PENDING')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-warning small fw-bold text-uppercase">Awaiting Approval</span>
                            <h3 class="fw-bold m-0 mt-1 text-warning"><?= (int)($metrics['pending'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-warning-subtle text-warning p-2.5 rounded-3">
                            <i class="bi bi-hourglass-top fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-success border-4 cursor-pointer" style="cursor: pointer;" onclick="setApprovalFilter('APPROVED')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-success small fw-bold text-uppercase">Approved &amp; Live</span>
                            <h3 class="fw-bold m-0 mt-1 text-success"><?= (int)($metrics['approved'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-success-subtle text-success p-2.5 rounded-3">
                            <i class="bi bi-check2-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-danger border-4 cursor-pointer" style="cursor: pointer;" onclick="setApprovalFilter('REJECTED')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-danger small fw-bold text-uppercase">Rejected Submissions</span>
                            <h3 class="fw-bold m-0 mt-1 text-danger"><?= (int)($metrics['rejected'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2.5 rounded-3">
                            <i class="bi bi-x-octagon fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4 cursor-pointer" style="cursor: pointer;" onclick="setApprovalFilter('ALL')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Total Catalog Records</span>
                            <h3 class="fw-bold m-0 mt-1"><?= (int)($metrics['total'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2.5 rounded-3">
                            <i class="bi bi-boxes fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Submissions & Catalog Table Card -->
    <div class="sj-table-card mb-4">
        <div class="sj-table-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-inbox-fill text-primary fs-5"></i>
                <div>
                    <h6 class="m-0 fw-bold text-dark fs-6" id="queueHeaderTitle">All Product Submissions (Before &amp; After Approval)</h6>
                    <span class="text-muted small">Manufacturer catalog audits, version approvals, and rejection controls</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="approvalStatusFilter" class="small text-muted text-nowrap m-0 fw-semibold">Filter:</label>
                <select id="approvalStatusFilter" class="form-select form-select-sm" style="width: auto; min-width: 220px;" onchange="onFilterSelectChange(this.value)">
                    <option value="ALL" selected>All Products (Before &amp; After Approve)</option>
                    <option value="PENDING">Awaiting Approval</option>
                    <option value="APPROVED">Approved &amp; Live</option>
                    <option value="REJECTED">Rejected Submissions</option>
                </select>
                <button class="btn btn-sm btn-outline-secondary" onclick="productAppDt.ajax.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <div class="p-0">
            <div class="table-responsive">
                <table id="productApprovalsTable" class="table sj-table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">Product Details</th>
                            <th style="min-width: 180px;">Manufacturer / Supplier</th>
                            <th style="min-width: 160px;">Category</th>
                            <th style="min-width: 140px;">Wholesale Price</th>
                            <th style="min-width: 90px;">MOQ</th>
                            <th style="min-width: 150px;">Approval Status</th>
                            <th style="min-width: 210px;" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Full Product View & Approval Modal -->
<div class="modal fade" id="productReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6" id="reviewModalTitle">Product Details &amp; Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="reviewModalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer bg-light border-top py-2.5 d-flex justify-content-between" id="reviewModalFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <div id="reviewModalActionBtns" class="d-flex gap-2"></div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="productRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold fs-6">Reject Product Submission</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productRejectForm" onsubmit="submitProductReject(event)">
                <input type="hidden" id="rejectProductId">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Provide detailed feedback explaining why this product cannot be approved or what changes are required from the manufacturer.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mandatory Rejection Feedback *</label>
                        <textarea id="productRejectionReason" class="form-control" rows="4" required placeholder="e.g. Image resolution insufficient, pricing matrix inconsistent with MOQ tier..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2.5">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let productAppDt = null;
const csrfToken = '<?= htmlspecialchars(CSRF::token()) ?>';
const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';

function setApprovalFilter(val) {
    const sel = document.getElementById('approvalStatusFilter');
    if (sel) {
        sel.value = val;
        onFilterSelectChange(val);
    }
}

function onFilterSelectChange(val) {
    const titles = {
        'ALL': 'All Product Submissions (Before & After Approval)',
        'PENDING': 'Queue of Pending Product Submissions (Before Approval)',
        'APPROVED': 'Approved & Live Marketplace Products (After Approval)',
        'REJECTED': 'Rejected Product Submissions'
    };
    const titleEl = document.getElementById('queueHeaderTitle');
    if (titleEl) titleEl.textContent = titles[val] || titles['ALL'];
    if (productAppDt) {
        productAppDt.ajax.reload();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    productAppDt = $('#productApprovalsTable').DataTable({
        processing: true,
        responsive: false,
        autoWidth: false,
        ajax: {
            url: `${baseUrl}/api/admin/product-approvals/data`,
            data: function(d) {
                d.filter = document.getElementById('approvalStatusFilter').value || 'ALL';
            },
            dataSrc: function(json) {
                if (json.error) {
                    console.error("Product approvals data warning:", json.error);
                }
                return json.data || [];
            },
            error: function(xhr, error, thrown) {
                console.error("DataTables AJAX error:", xhr.status, thrown);
            }
        },
        order: [[0, 'desc']],
        columns: [
            {
                data: 'title',
                render: function(data, type, row) {
                    const img = SportsJunction.formatImageUrl(row.primary_image);
                    const featBadge = parseInt(row.is_featured || 0) === 1
                        ? `<span class="badge-pill-featured ms-1" title="Featured Product"><i class="bi bi-star-fill text-warning"></i>Featured</span>`
                        : '';
                    return `
                        <div class="d-flex align-items-center gap-2.5">
                            <img src="${img}" alt="${data || 'Product'}" class="table-product-thumb" onerror="this.onerror=null;this.src='${SportsJunction.defaultLogo}';">
                            <div style="max-width: 300px;">
                                <a href="${baseUrl}/admin/products/edit/${row.id}" class="text-decoration-none">
                                    <div class="table-product-title" title="${data}">${data}</div>
                                </a>
                                <div class="d-flex align-items-center gap-2 mt-0.5">
                                    <span class="table-product-sku">${row.sku ? 'SKU: ' + row.sku : 'SKU: N/A'}</span>
                                    ${featBadge}
                                </div>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: 'supplier_name',
                render: function(data, type, row) {
                    return `
                        <div>
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 180px;">${data || 'Verified Supplier'}</div>
                            <small class="text-muted text-truncate d-block" style="max-width: 180px;"><i class="bi bi-envelope me-1"></i>${row.supplier_email || 'N/A'}</small>
                        </div>
                    `;
                }
            },
            {
                data: 'category_name',
                render: function(data, type, row) {
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
                data: 'min_price',
                render: function(data, type, row) {
                    const minNum = parseFloat(data || 0);
                    const minFormatted = minNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const maxNum = row.max_price ? parseFloat(row.max_price) : 0;
                    const maxFormatted = maxNum > minNum ? maxNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : null;
                    const priceStr = maxFormatted ? `$${minFormatted} – $${maxFormatted}` : `$${minFormatted}`;
                    return `
                        <div>
                            <div class="fw-bold text-success">${priceStr}</div>
                        </div>
                    `;
                }
            },
            {
                data: 'moq_quantity',
                render: function(data, type, row) {
                    const qty = parseInt(data || 0).toLocaleString('en-IN');
                    const unit = row.moq_unit_symbol || row.moq_unit_name || 'units';
                    return `
                        <div>
                            <span class="fw-bold text-dark">${qty}</span>
                            <span class="small text-muted d-block">${unit}</span>
                        </div>
                    `;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let badges = '';
                    if (row.pending_changes) {
                        badges += '<div class="mb-1"><span class="badge badge-soft-warning px-2.5 py-1 rounded-pill"><i class="bi bi-pencil-square me-1"></i>Revision Pending</span></div>';
                    }
                    if (row.approval_status === 'APPROVED') {
                        badges += '<span class="badge badge-soft-success px-2.5 py-1 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Approved & Live</span>';
                    } else if (row.approval_status === 'PENDING_APPROVAL') {
                        badges += '<span class="badge badge-soft-warning px-2.5 py-1 rounded-pill"><i class="bi bi-hourglass-split me-1"></i>Awaiting Approval</span>';
                    } else if (row.approval_status === 'REJECTED') {
                        badges += '<span class="badge badge-soft-danger px-2.5 py-1 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>';
                    } else {
                        badges += `<span class="badge badge-soft-secondary px-2.5 py-1 rounded-pill">${row.approval_status || 'DRAFT'}</span>`;
                    }
                    return badges;
                }
            },
            {
                data: null,
                className: 'text-end text-nowrap pe-3',
                orderable: false,
                render: function(data, type, row) {
                    const needsApprove = (row.approval_status !== 'APPROVED' || row.pending_changes);
                    const canReject = (row.approval_status !== 'REJECTED');
                    return `
                        <div class="d-flex align-items-center justify-content-end gap-1.5">
                            <button class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1" onclick="reviewProduct(${row.id})" title="View Complete Product Details">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <a href="${baseUrl}/admin/products/edit/${row.id}" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1" title="Edit All Details">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            ${needsApprove ? `
                                <button class="btn btn-sm btn-success py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1 shadow-sm" onclick="approveProduct(${row.id})" title="Approve & Publish">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            ` : ''}
                            ${canReject ? `
                                <button class="btn btn-sm btn-outline-danger py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1" onclick="openProductRejectModal(${row.id})" title="Reject Submission">
                                    <i class="bi bi-x-lg"></i> Reject
                                </button>
                            ` : ''}
                        </div>
                    `;
                }
            }
        ]
    });
});

function ynBadge(val, label) {
    const active = parseInt(val || 0) === 1;
    if (!active) return '';
    return `<span class="badge bg-success-subtle text-success border border-success-subtle me-1.5 mb-1 py-1 px-2.5">
        <i class="bi bi-check-circle-fill me-1"></i>${label}
    </span>`;
}

function reviewProduct(id) {
    const modal = new bootstrap.Modal(document.getElementById('productReviewModal'));
    modal.show();
    $('#reviewModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    $('#reviewModalActionBtns').html('');

    fetch(`${baseUrl}/api/admin/product-approvals/${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                $('#reviewModalBody').html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }
            const p = res.data;
            const changes = p.decoded_pending_changes;
            const img = SportsJunction.formatImageUrl(p.primary_image);
            const sym = '$';

            $('#reviewModalTitle').text(`Product Complete Details: ${p.title}`);


            // Populate Modal Footer Buttons (Edit All Details + Approve/Reject)
            const needsApprove = (p.approval_status !== 'APPROVED' || changes);
            let footerBtns = `
                <a href="${baseUrl}/admin/products/edit/${p.id}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i>Edit All Product Details
                </a>
            `;
            if (needsApprove) {
                footerBtns += `
                    <button type="button" class="btn btn-success btn-sm" onclick="approveProduct(${p.id})">
                        <i class="bi bi-check-lg me-1"></i>Approve &amp; Publish
                    </button>
                `;
            }
            if (p.approval_status !== 'REJECTED') {
                footerBtns += `
                    <button type="button" class="btn btn-danger btn-sm" onclick="openProductRejectModal(${p.id})">
                        <i class="bi bi-x-lg me-1"></i>Reject
                    </button>
                `;
            }
            $('#reviewModalActionBtns').html(footerBtns);

            // Gallery thumbnails
            let galleryHtml = '';
            if (p.images && p.images.length > 0) {
                galleryHtml = '<div class="d-flex flex-wrap gap-2 mt-2">';
                p.images.forEach(g => {
                    const gUrl = SportsJunction.formatImageUrl(g.image_url || g.image_path);
                    galleryHtml += `<img src="${gUrl}" onerror="this.onerror=null;this.src='${SportsJunction.defaultLogo}';" class="rounded border" style="width: 64px; height: 64px; object-fit: cover;">`;
                });
                galleryHtml += '</div>';
            } else {
                galleryHtml = '<div class="text-muted small">No additional gallery images uploaded.</div>';
            }

            let html = `
                <div class="row g-4">
                    <!-- Left Column: Media & Status Summary -->
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded-4 border mb-3">
                            <img src="${img}" onerror="this.onerror=null;this.src='${SportsJunction.defaultLogo}';" class="img-fluid rounded-3" style="max-height: 240px; object-fit: contain;">
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small text-uppercase text-muted d-block mb-1">Gallery Photos</label>
                            ${galleryHtml}
                        </div>

                        <div class="p-3 bg-light rounded-3 border small space-y-1">
                            <div class="mb-1"><strong>Approval Status:</strong> <span class="badge ${p.approval_status === 'APPROVED' ? 'bg-success' : (p.approval_status === 'REJECTED' ? 'bg-danger' : 'bg-warning text-dark')}">${p.approval_status}</span></div>
                            <div class="mb-1"><strong>Publishing Status:</strong> <span class="badge bg-secondary">${p.status}</span></div>
                            <div class="mb-1"><strong>Featured Product:</strong> ${parseInt(p.is_featured || 0) === 1 ? `<span class="badge bg-warning text-dark">Yes (Priority: ${p.featured_order || 0})</span>` : 'No'}</div>
                            <div class="mb-1"><strong>Supplier:</strong> ${p.supplier_name || 'N/A'}</div>
                            <div class="mb-1"><strong>Supplier Email:</strong> ${p.supplier_email || 'N/A'}</div>
                            <div class="mb-1"><strong>Supplier Phone:</strong> ${p.supplier_phone || 'N/A'}</div>
                            ${p.video_url ? `<div class="mt-2"><a href="${p.video_url}" target="_blank" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-youtube me-1"></i>Watch Demo Video</a></div>` : ''}
                        </div>
                    </div>

                    <!-- Right Column: All 5 Step Details -->
                    <div class="col-md-8">
                        <!-- Step 1: Basic Info -->
                        <div class="border rounded-3 p-3 mb-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-1"></i>1. Basic Product Information</h6>
                            <div class="row g-2 small">
                                <div class="col-md-8"><strong>Title:</strong> ${p.title || '—'}</div>
                                <div class="col-md-4"><strong>SKU:</strong> ${p.sku || 'N/A'}</div>
                                <div class="col-md-6"><strong>Category:</strong> ${p.all_category_names || p.category_name || '—'}</div>
                                <div class="col-md-6"><strong>Subcategory:</strong> ${p.all_subcategory_names || p.subcategory_name || '—'}</div>
                                <div class="col-md-6"><strong>Brand Name:</strong> ${p.brand_name || '—'}</div>
                                <div class="col-md-6"><strong>URL Slug:</strong> <code>${p.slug || '—'}</code></div>
                                <div class="col-12 mt-2"><strong>Short Description:</strong><div class="text-muted">${p.short_description || '—'}</div></div>
                                <div class="col-12 mt-2"><strong>Detailed Technical Description:</strong><div class="p-2 bg-light rounded border mt-1" style="white-space: pre-wrap;">${p.description || '—'}</div></div>
                            </div>
                        </div>

                        <!-- Step 2: Pricing & MOQ -->
                        <div class="border rounded-3 p-3 mb-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-tag me-1"></i>2. Wholesale Pricing &amp; MOQ</h6>
                            <div class="row g-2 small">
                                <div class="col-md-4"><strong>Min Price:</strong> <span class="text-success fw-bold">${sym}${parseFloat(p.min_price || 0).toFixed(2)}</span></div>
                                <div class="col-md-4"><strong>Max Price:</strong> <span class="text-success fw-bold">${sym}${parseFloat(p.max_price || 0).toFixed(2)}</span></div>
                                <div class="col-md-4"><strong>Currency:</strong> USD ($)</div>
                                <div class="col-md-4"><strong>MOQ:</strong> ${p.moq_quantity} ${p.moq_unit_name || 'units'}</div>
                                <div class="col-md-4"><strong>Available Ready Stock:</strong> ${p.available_quantity ?? 0}</div>
                                <div class="col-md-4"><strong>Sample Available:</strong> ${parseInt(p.sample_available || 0) === 1 ? `Yes (${sym}${parseFloat(p.sample_price || 0).toFixed(2)})` : 'No'}</div>
                            </div>
                        </div>

                        <!-- Step 3: Specifications & Customization -->
                        <div class="border rounded-3 p-3 mb-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-sliders me-1"></i>3. Specifications &amp; Factory Customization</h6>
                            <div class="row g-2 small">
                                <div class="col-md-6"><strong>Material:</strong> ${p.material || '—'}</div>
                                <div class="col-md-6"><strong>Available Colorways:</strong> ${p.colors || '—'}</div>
                                <div class="col-md-6"><strong>Dimensions / Size:</strong> ${p.size || '—'}</div>
                                <div class="col-md-6"><strong>Unit Weight:</strong> ${p.weight || '—'}</div>
                                <div class="col-12 mt-2">
                                    <strong class="d-block mb-1">OEM / ODM Capabilities:</strong>
                                    ${[
                                        ynBadge(p.custom_logo, 'Custom Logo'),
                                        ynBadge(p.custom_color, 'Custom Color'),
                                        ynBadge(p.custom_packaging, 'Custom Packaging'),
                                        ynBadge(p.oem_available, 'OEM Service'),
                                        ynBadge(p.odm_available, 'ODM Design'),
                                        ynBadge(p.private_label, 'Private Label')
                                    ].filter(Boolean).join('') || '<span class="text-muted">None specified</span>'}
                                </div>
                                <div class="col-12 mt-2">
                                    <strong class="d-block mb-1">Product Certificates:</strong>
                                    ${(p.certificates && p.certificates.length > 0) ? p.certificates.map(c => `
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1.5 mb-1 py-1 px-2.5">
                                            <i class="bi bi-patch-check-fill me-1"></i>${c.name} ${c.code ? `(${c.code})` : ''}
                                        </span>
                                    `).join('') : '<span class="text-muted">No certificates assigned</span>'}
                                </div>
                                <div class="col-12 mt-2"><strong>Customization Details:</strong><div class="text-muted">${p.customization_details || '—'}</div></div>
                            </div>
                        </div>

                        <!-- Step 4: Shipping & Origin -->
                        <div class="border rounded-3 p-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-truck me-1"></i>4. Shipping &amp; Logistics</h6>
                            <div class="row g-2 small">
                                <div class="col-md-6"><strong>Origin Country:</strong> ${p.country_name || 'India'}</div>
                                <div class="col-md-6"><strong>Lead Time:</strong> ${p.lead_time || '—'}</div>
                                <div class="col-12"><strong>Shipping &amp; Freight Terms:</strong> ${p.shipping_info || '—'}</div>
                                <div class="col-12"><strong>Export Packaging:</strong> ${p.export_packaging || '—'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (changes) {
                html += `
                    <div class="alert alert-warning mt-4 p-3 border-warning">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-warning-emphasis m-0"><i class="bi bi-exclamation-triangle-fill me-1"></i>Pending Supplier Revision (Awaiting Approval)</h6>
                            <a href="${baseUrl}/admin/products/edit/${p.id}" class="btn btn-sm btn-warning fw-bold"><i class="bi bi-pencil-square me-1"></i>Edit Pending Revision</a>
                        </div>
                        <p class="small text-muted mb-2">The supplier submitted updated product details. Approving will publish these changes live:</p>
                        <div class="row g-2 small">
                            <div class="col-md-6"><strong>Proposed Title:</strong> ${changes.name || p.title}</div>
                            <div class="col-md-6"><strong>Proposed SKU / Brand:</strong> ${changes.sku || '—'} / ${changes.brand_name || '—'}</div>
                            <div class="col-md-4"><strong>Proposed Price:</strong> ${sym}${changes.min_price ?? p.min_price} - ${sym}${changes.max_price ?? p.max_price}</div>
                            <div class="col-md-4"><strong>Proposed MOQ:</strong> ${changes.moq ?? p.moq_quantity}</div>
                            <div class="col-md-4"><strong>Proposed Lead Time:</strong> ${changes.lead_time || p.lead_time || '—'}</div>
                            <div class="col-md-6"><strong>Proposed Material:</strong> ${changes.material || '—'}</div>
                            <div class="col-md-6"><strong>Proposed Colors / Size:</strong> ${changes.colors || '—'} / ${changes.size || '—'}</div>
                            <div class="col-12"><strong>Proposed Export Packaging:</strong> ${changes.export_packaging || '—'}</div>
                            <div class="col-12"><strong>Proposed Short Description:</strong> ${changes.short_description || '—'}</div>
                            <div class="col-12"><strong>Proposed Full Description:</strong><div class="p-2 bg-white rounded border mt-1" style="white-space: pre-wrap;">${changes.description || '—'}</div></div>
                        </div>
                    </div>
                `;
            }

            $('#reviewModalBody').html(html);
        });
}

function approveProduct(id) {
    if (!confirm('Approve this product and publish it immediately across the public marketplace?')) {
        return;
    }

    fetch(`${baseUrl}/api/admin/product-approvals/${id}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            productAppDt.ajax.reload();
            if ($('#productReviewModal').hasClass('show')) {
                bootstrap.Modal.getInstance(document.getElementById('productReviewModal')).hide();
            }
        } else {
            alert(res.message || 'Error approving product.');
        }
    });
}

function openProductRejectModal(id) {
    $('#rejectProductId').val(id);
    $('#productRejectionReason').val('');
    const modal = new bootstrap.Modal(document.getElementById('productRejectModal'));
    modal.show();
}

function submitProductReject(e) {
    e.preventDefault();
    const id = $('#rejectProductId').val();
    const reason = $('#productRejectionReason').val();

    fetch(`${baseUrl}/api/admin/product-approvals/${id}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('productRejectModal')).hide();
            if ($('#productReviewModal').hasClass('show')) {
                bootstrap.Modal.getInstance(document.getElementById('productReviewModal')).hide();
            }
            alert(res.message);
            productAppDt.ajax.reload();
        } else {
            alert(res.message || 'Error rejecting product.');
        }
    });
}
</script>
