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
                            <span class="text-warning small fw-bold text-uppercase">Awaiting Audit</span>
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
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-inbox-fill text-primary"></i>
                <span class="fw-bold small text-uppercase" id="queueHeaderTitle">All Product Submissions (Before &amp; After Approval)</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="approvalStatusFilter" class="small text-muted text-nowrap m-0">Filter:</label>
                <select id="approvalStatusFilter" class="form-select form-select-sm" style="width: auto; min-width: 200px;" onchange="onFilterSelectChange(this.value)">
                    <option value="ALL" selected>All Products (Before &amp; After Approve)</option>
                    <option value="PENDING">Awaiting Audit (Pending Approval)</option>
                    <option value="APPROVED">Approved &amp; Live</option>
                    <option value="REJECTED">Rejected Submissions</option>
                </select>
                <button class="btn btn-sm btn-outline-secondary" onclick="productAppDt.ajax.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="productApprovalsTable" class="table table-hover align-middle w-100" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Manufacturer / Supplier</th>
                            <th>Category</th>
                            <th>Wholesale Price</th>
                            <th>MOQ</th>
                            <th>Approval &amp; Status</th>
                            <th class="text-end">View / Edit / Audit Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Full Product View & Audit Modal -->
<div class="modal fade" id="productReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6" id="reviewModalTitle">Product Details &amp; Audit</h5>
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
        ajax: {
            url: `${baseUrl}/api/admin/product-approvals/data`,
            data: function(d) {
                d.filter = document.getElementById('approvalStatusFilter').value || 'ALL';
            },
            dataSrc: 'data'
        },
        order: [[0, 'desc']],
        columns: [
            {
                data: 'title',
                render: function(data, type, row) {
                    const img = SportsJunction.formatImageUrl(row.primary_image);
                    const featBadge = parseInt(row.is_featured || 0) === 1
                        ? `<span class="badge bg-warning text-dark ms-1" title="Featured Product"><i class="bi bi-star-fill"></i> Featured</span>`
                        : '';
                    return `
                        <div class="d-flex align-items-center gap-2.5">
                            <img src="${img}" onerror="this.onerror=null;this.src='${SportsJunction.defaultLogo}';" class="rounded-3 border" style="width: 48px; height: 48px; object-fit: cover;">
                            <div>
                                <div class="fw-bold text-dark">${data} ${featBadge}</div>
                                <small class="text-muted">SKU: ${row.sku || 'N/A'}</small>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: 'supplier_name',
                render: function(data, type, row) {
                    return `<div class="fw-semibold text-dark">${data || 'Verified Supplier'}</div><small class="text-muted">${row.supplier_email || ''}</small>`;
                }
            },
            {
                data: 'category_name',
                render: function(data, type, row) {
                    return `<div>${data || '—'}</div><small class="text-muted">${row.subcategory_name || '—'}</small>`;
                }
            },
            {
                data: 'min_price',
                render: function(data, type, row) {
                    const sym = row.price_unit_symbol || '₹';
                    const max = row.max_price ? parseFloat(row.max_price).toFixed(2) : parseFloat(data).toFixed(2);
                    return `<span class="fw-bold text-primary">${sym}${parseFloat(data).toFixed(2)} - ${sym}${max}</span>`;
                }
            },
            {
                data: 'moq_quantity',
                render: function(data, type, row) {
                    return `${data} ${row.moq_unit_name || 'units'}`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let badges = '';
                    if (row.pending_changes) {
                        badges += '<span class="badge bg-warning-subtle text-warning border border-warning-subtle d-inline-block mb-1"><i class="bi bi-pencil-square me-1"></i>Edited Version Pending</span><br>';
                    }
                    if (row.approval_status === 'APPROVED') {
                        badges += '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Approved</span>';
                    } else if (row.approval_status === 'PENDING_APPROVAL') {
                        badges += '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Approval</span>';
                    } else if (row.approval_status === 'REJECTED') {
                        badges += '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Rejected</span>';
                    } else {
                        badges += `<span class="badge bg-secondary">${row.approval_status || 'DRAFT'}</span>`;
                    }
                    return badges;
                }
            },
            {
                data: null,
                className: 'text-end text-nowrap',
                orderable: false,
                render: function(data, type, row) {
                    const needsApprove = (row.approval_status !== 'APPROVED' || row.pending_changes);
                    const canReject = (row.approval_status !== 'REJECTED');
                    return `
                        <button class="btn btn-sm btn-outline-primary py-1 px-2 me-1" onclick="reviewProduct(${row.id})" title="View All Product Details">
                            <i class="bi bi-eye me-1"></i>View
                        </button>
                        <a href="${baseUrl}/admin/products/edit/${row.id}" class="btn btn-sm btn-outline-dark py-1 px-2 me-1" title="Edit All Product Details">
                            <i class="bi bi-pencil-square me-1"></i>Edit
                        </a>
                        ${needsApprove ? `
                            <button class="btn btn-sm btn-success py-1 px-2 me-1" onclick="approveProduct(${row.id})" title="Approve & Publish">
                                <i class="bi bi-check-lg me-1"></i>Approve
                            </button>
                        ` : ''}
                        ${canReject ? `
                            <button class="btn btn-sm btn-outline-danger py-1 px-2" onclick="openProductRejectModal(${row.id})" title="Reject">
                                <i class="bi bi-x-lg me-1"></i>Reject
                            </button>
                        ` : ''}
                    `;
                }
            }
        ]
    });
});

function ynBadge(val, label) {
    const active = parseInt(val || 0) === 1;
    return `<span class="badge ${active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border'} me-1 mb-1">
        <i class="bi ${active ? 'bi-check-circle-fill' : 'bi-dash-circle'} me-1"></i>${label}: ${active ? 'Yes' : 'No'}
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
            const sym = p.price_unit_symbol || '₹';

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
                                <div class="col-md-6"><strong>Category:</strong> ${p.category_name || '—'}</div>
                                <div class="col-md-6"><strong>Subcategory:</strong> ${p.subcategory_name || '—'}</div>
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
                                <div class="col-md-4"><strong>Price Unit:</strong> ${p.price_unit_name || ''} (${sym})</div>
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
                                    ${ynBadge(p.custom_logo, 'Custom Logo')}
                                    ${ynBadge(p.custom_color, 'Custom Color')}
                                    ${ynBadge(p.custom_packaging, 'Custom Packaging')}
                                    ${ynBadge(p.oem_available, 'OEM Service')}
                                    ${ynBadge(p.odm_available, 'ODM Design')}
                                    ${ynBadge(p.private_label, 'Private Label')}
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
