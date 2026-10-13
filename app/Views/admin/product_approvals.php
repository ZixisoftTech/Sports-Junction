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
            <p class="text-muted small mb-0">Every newly submitted or edited sports equipment requires Super Admin approval before appearing publicly on the B2B marketplace.</p>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-warning border-4">
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
            <div class="card shadow-sm border-0 border-start border-success border-4">
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
            <div class="card shadow-sm border-0 border-start border-danger border-4">
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
            <div class="card shadow-sm border-0 border-start border-primary border-4">
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

    <!-- Pending Approvals Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-inbox-fill text-warning"></i>
                <span class="fw-bold small text-uppercase">Queue of Pending Product Submissions</span>
            </div>
            <button class="btn btn-sm btn-outline-secondary" onclick="productAppDt.ajax.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
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
                            <th>Submission Type</th>
                            <th class="text-end">Audit Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Product Review Modal -->
<div class="modal fade" id="productReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6" id="reviewModalTitle">Review Product Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="reviewModalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer bg-light border-top py-2.5" id="reviewModalFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
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

document.addEventListener('DOMContentLoaded', function() {
    productAppDt = $('#productApprovalsTable').DataTable({
        processing: true,
        ajax: {
            url: `${baseUrl}/api/admin/product-approvals/data`,
            dataSrc: 'data'
        },
        order: [[0, 'desc']],
        columns: [
            {
                data: 'title',
                render: function(data, type, row) {
                    const img = SportsJunction.formatImageUrl(row.primary_image);
                    return `
                        <div class="d-flex align-items-center gap-2.5">
                            <img src="${img}" onerror="this.onerror=null;this.src='${SportsJunction.defaultLogo}';" class="rounded-3 border" style="width: 48px; height: 48px; object-fit: cover;">
                            <div>
                                <div class="fw-bold text-dark">${data}</div>
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
                    return `<span class="fw-bold text-primary">${row.price_unit_symbol || '₹'}${parseFloat(data).toFixed(2)}</span>`;
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
                    if (row.pending_changes) {
                        return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-pencil-square me-1"></i>Edited Version</span>';
                    }
                    return '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-plus-circle me-1"></i>New Product</span>';
                }
            },
            {
                data: null,
                className: 'text-end',
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-primary py-1 px-2.5 me-1" onclick="reviewProduct(${row.id})" title="Review Details">
                            <i class="bi bi-eye me-1"></i>Review
                        </button>
                        <button class="btn btn-sm btn-success py-1 px-2.5 me-1" onclick="approveProduct(${row.id})" title="Approve">
                            <i class="bi bi-check-lg me-1"></i>Approve
                        </button>
                        <button class="btn btn-sm btn-outline-danger py-1 px-2.5" onclick="openProductRejectModal(${row.id})" title="Reject">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    `;
                }
            }
        ]
    });
});

function reviewProduct(id) {
    const modal = new bootstrap.Modal(document.getElementById('productReviewModal'));
    modal.show();
    $('#reviewModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');

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

            $('#reviewModalTitle').text(`Audit Product: ${p.title}`);
            let html = `
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <img src="${img}" onerror="this.onerror=null;this.src='${SportsJunction.defaultLogo}';" class="img-fluid rounded-4 border shadow-sm mb-3" style="max-height: 260px; object-fit: contain;">
                        <div class="p-3 bg-light rounded-3 text-start small">
                            <div><strong>Supplier:</strong> ${p.supplier_name || 'N/A'}</div>
                            <div><strong>Category:</strong> ${p.category_name} / ${p.subcategory_name || ''}</div>
                            <div><strong>Lead Time:</strong> ${p.lead_time || 'N/A'}</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h5 class="fw-bold text-dark">${p.title}</h5>
                        <div class="d-flex align-items-center gap-3 my-2">
                            <span class="fs-4 fw-bold text-primary">${p.price_unit_symbol || '₹'}${parseFloat(p.min_price).toFixed(2)} - ${parseFloat(p.max_price).toFixed(2)}</span>
                            <span class="badge bg-light text-dark border">MOQ: ${p.moq_quantity} ${p.moq_unit_name || 'units'}</span>
                        </div>
                        <hr class="my-3">
                        <div class="mb-3">
                            <label class="fw-bold small text-muted text-uppercase">Short Summary</label>
                            <p class="small text-muted">${p.short_description || '—'}</p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small text-muted text-uppercase">Full Technical Specifications</label>
                            <div class="p-3 bg-light rounded-3 small" style="white-space: pre-wrap;">${p.description || '—'}</div>
                        </div>
                    </div>
                </div>
            `;

            if (changes) {
                html += `
                    <div class="alert alert-warning mt-4 p-3 border-warning">
                        <h6 class="fw-bold text-warning-emphasis mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>Proposed Edits Pending Approval</h6>
                        <p class="small text-muted mb-2">The supplier has submitted modifications. Approving will publish these changes live:</p>
                        <ul class="small mb-0">
                            ${changes.name ? `<li><strong>New Title:</strong> ${changes.name}</li>` : ''}
                            ${changes.min_price ? `<li><strong>New Price Range:</strong> ₹${changes.min_price} - ₹${changes.max_price}</li>` : ''}
                            ${changes.moq ? `<li><strong>New MOQ:</strong> ${changes.moq}</li>` : ''}
                            ${changes.description ? `<li><strong>Updated Specs:</strong> Provided in review</li>` : ''}
                        </ul>
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
