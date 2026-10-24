<?php
/**
 * Super Admin: Bulk Product Enquiries Management View
 * Receive, review, and manage submitted bulk enquiries only.
 * Note: Strictly NO chat, messaging, or communication threads.
 */
$baseUrl = !empty($baseUrl) ? $baseUrl : \App\Core\Response::baseUrl();
$enquiries = !empty($enquiries) ? $enquiries : [];
$filters = !empty($filters) ? $filters : [];
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/dashboard" class="text-decoration-none text-slate-500">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/enquiries" class="text-decoration-none text-slate-500">Enquiries</a></li>
                    <li class="breadcrumb-item active fw-semibold text-slate-800" aria-current="page">Bulk Product Enquiries</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-slate-900 mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-collection text-primary"></i>
                Bulk Product Enquiries
            </h1>
            <p class="text-muted small mb-0">
                Receive, review, and manage multi-product sourcing quotation requests submitted by buyers through the Sort List.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-outline-secondary btn-sm fw-bold">
                <i class="bi bi-chat-left-quote me-1"></i> Single Enquiries
            </a>
            <a href="<?= $baseUrl ?>/admin/rfq-oem" class="btn btn-outline-secondary btn-sm fw-bold">
                <i class="bi bi-clipboard2-check me-1"></i> RFQ / OEM
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary h-100">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Total Bulk Requests</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-slate-900"><?= (int)($counts['total'] ?? 0) ?></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary small">Multi-Product</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info h-100">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Submitted / New</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-info"><?= (int)($counts['new'] ?? 0) ?></span>
                    <span class="badge bg-info bg-opacity-10 text-info small">Action Needed</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Reviewed</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-warning"><?= (int)($counts['reviewed'] ?? 0) ?></span>
                    <span class="badge bg-warning bg-opacity-10 text-dark small">Under Audit</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Processed</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-success"><?= (int)($counts['processed'] ?? 0) ?></span>
                    <span class="badge bg-success bg-opacity-10 text-success small">Completed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card with Search & Filters -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Search & Filter Bar -->
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= $baseUrl ?>/admin/bulk-enquiries" class="row g-2 align-items-center">
                <!-- Keyword Search -->
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" class="form-control bg-light border-start-0" placeholder="Search reference, buyer name, email, company...">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="ALL">All Statuses</option>
                        <?php foreach ($statuses as $sKey => $sLabel): ?>
                            <option value="<?= htmlspecialchars($sKey) ?>" <?= (($filters['status'] ?? '') === $sKey) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-6 col-md-2">
                    <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" class="form-control form-control-sm" placeholder="From Date">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" class="form-control form-control-sm" placeholder="To Date">
                </div>

                <!-- Buttons -->
                <div class="col-6 col-md-2 d-flex gap-1.5">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3 flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="<?= $baseUrl ?>/admin/bulk-enquiries" class="btn btn-light btn-sm fw-bold border text-muted" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-3" style="width: 14%;">Ref. Number</th>
                            <th style="width: 13%;">Submission Date</th>
                            <th style="width: 18%;">Buyer Information</th>
                            <th style="width: 25%;">Shortlisted Equipment</th>
                            <th style="width: 14%;">Associated Factories</th>
                            <th style="width: 8%;" class="text-center">Status</th>
                            <th class="text-end pe-3" style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enquiries)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 text-slate-300 d-block mb-2"></i>
                                    No bulk product enquiries match your search filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enquiries as $enq): ?>
                                <?php
                                    $statusKey = strtoupper((string)($enq['status'] ?? 'SUBMITTED'));
                                    $statusBadge = match($statusKey) {
                                        'SUBMITTED' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                        'REVIEWED'  => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-30',
                                        'PROCESSED' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                        'ARCHIVED'  => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                        'CLOSED'    => 'bg-light text-muted border',
                                        default     => 'bg-light text-dark'
                                    };
                                ?>
                                <tr id="bulkRow-<?= (int)$enq['id'] ?>">
                                    <td class="ps-3">
                                        <div class="font-monospace fw-bold text-primary"><?= htmlspecialchars($enq['reference_no']) ?></div>
                                        <div class="badge bg-light text-slate-700 border text-[10px] fw-normal mt-0.5">
                                            <?= (int)$enq['product_count'] ?> Products
                                        </div>
                                    </td>

                                    <td class="small text-muted">
                                        <div class="text-slate-900 fw-semibold"><?= date('d M Y', strtotime($enq['created_at'])) ?></div>
                                        <div class="text-[11px] text-muted"><?= date('h:i A', strtotime($enq['created_at'])) ?></div>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($enq['buyer_name']) ?></div>
                                        <?php if (!empty($enq['company_name'])): ?>
                                            <div class="small text-muted font-medium"><?= htmlspecialchars($enq['company_name']) ?></div>
                                        <?php endif; ?>
                                        <div class="text-muted smaller">
                                            <a href="mailto:<?= htmlspecialchars($enq['buyer_email']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($enq['buyer_email']) ?></a>
                                            <?php if (!empty($enq['buyer_phone'])): ?>
                                                &bull; <?= htmlspecialchars($enq['buyer_phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="smaller text-slate-500 mt-0.5">
                                            <i class="bi bi-geo-alt text-primary"></i> <?= htmlspecialchars($enq['country_title'] ?? $enq['country_name'] ?? 'India') ?>
                                            <?= !empty($enq['delivery_location']) ? (' (' . htmlspecialchars($enq['delivery_location']) . ')') : '' ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="text-slate-800 small line-clamp-2 fw-medium">
                                            <?= htmlspecialchars($enq['products_summary'] ?? $enq['subject'] ?? 'Bulk Sourcing Request') ?>
                                        </div>
                                        <?php if (!empty($enq['overall_message'])): ?>
                                            <div class="smaller text-muted mt-1 text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($enq['overall_message']) ?>">
                                                <i class="bi bi-chat-quote me-1"></i><?= htmlspecialchars($enq['overall_message']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="small text-slate-700 line-clamp-2">
                                            <?= htmlspecialchars($enq['suppliers_summary'] ?? 'Multiple Suppliers') ?>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge px-2.5 py-1 fw-bold status-badge-<?= (int)$enq['id'] ?> <?= $statusBadge ?>" style="font-size: 10px;">
                                            <?= htmlspecialchars($statuses[$statusKey] ?? $statusKey) ?>
                                        </span>
                                    </td>

                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-bold" onclick="openAdminBulkDetail(<?= (int)$enq['id'] ?>)">
                                            <i class="bi bi-eye me-1"></i> Review
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="card-footer bg-white border-top py-2.5 d-flex align-items-center justify-content-between small text-muted">
                    <div>Showing Page <?= $page ?> of <?= $totalPages ?> (Total: <?= $total ?> records)</div>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SUPER ADMIN DETAIL MODAL (Review & Status Update Only - Strictly NO Chat) -->
<!-- ========================================================================= -->
<div class="modal fade" id="adminBulkDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 bg-light bg-opacity-50">
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Bulk Product Sourcing Review</div>
                    <h5 class="modal-title fw-bold text-slate-900 d-flex align-items-center gap-2 mt-0.5">
                        <span id="modalRefNo">BENQ-XXXXX</span>
                        <span id="modalStatusBadge" class="badge px-2.5 py-1" style="font-size: 11px;"></span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Buyer Details Card -->
                <div class="card border border-light-subtle bg-light bg-opacity-25 p-3 rounded-3 mb-4">
                    <h6 class="fw-bold small text-slate-800 mb-2.5 d-flex align-items-center gap-1.5 text-uppercase tracking-wider">
                        <i class="bi bi-person-badge text-primary"></i> Buyer Procurement Information
                    </h6>
                    <div class="row g-3 small">
                        <div class="col-md-3">
                            <span class="text-muted d-block smaller">Full Name</span>
                            <span class="fw-bold text-slate-900" id="mBuyerName"></span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block smaller">Company / Organization</span>
                            <span class="fw-bold text-slate-900" id="mCompanyName"></span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block smaller">Business Email</span>
                            <span class="fw-bold text-slate-900" id="mBuyerEmail"></span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block smaller">Phone / WhatsApp</span>
                            <span class="fw-bold text-slate-900" id="mBuyerPhone"></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block smaller">Destination Country</span>
                            <span class="fw-bold text-slate-900" id="mCountry"></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block smaller">Delivery City / Location</span>
                            <span class="fw-bold text-slate-900" id="mDeliveryLocation"></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block smaller">Submission Timestamp</span>
                            <span class="fw-bold text-slate-900" id="mCreatedAt"></span>
                        </div>
                    </div>
                </div>

                <!-- Overall Requirements & Attachment -->
                <div class="card border border-light-subtle bg-white p-3 rounded-3 mb-4" id="mOverallCard">
                    <div class="row g-3 small">
                        <div class="col-md-8">
                            <span class="text-muted d-block smaller fw-bold uppercase">Overall Sourcing Instructions / Notes</span>
                            <p class="mb-0 text-slate-800 mt-1 whitespace-pre-line" id="mOverallMessage">No overall notes provided.</p>
                        </div>
                        <div class="col-md-4 border-start ps-3" id="mSharedAttachmentArea">
                            <span class="text-muted d-block smaller fw-bold uppercase mb-1">Master Tech Pack Attachment</span>
                            <div id="mSharedAttachmentContent">
                                <span class="text-muted smaller">None attached.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="mb-4">
                    <h6 class="fw-bold small text-slate-800 mb-2.5 d-flex align-items-center justify-content-between text-uppercase tracking-wider">
                        <span><i class="bi bi-box-seam text-primary me-1"></i> Shortlisted Products (<span id="mProductCount">0</span>)</span>
                    </h6>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-hover align-middle mb-0 text-xs">
                            <thead class="table-light text-uppercase text-muted">
                                <tr>
                                    <th class="ps-3" style="width: 32%;">Product &amp; Manufacturer</th>
                                    <th style="width: 15%;">Requested Qty &amp; MOQ</th>
                                    <th style="width: 12%;">Target Unit Price</th>
                                    <th style="width: 26%;">Custom Specifications</th>
                                    <th class="text-end pe-3" style="width: 15%;">Product Specs File</th>
                                </tr>
                            </thead>
                            <tbody id="mItemsTableBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Status Update Section (Admin Management Only) -->
                <div class="card border border-light-subtle bg-light bg-opacity-25 p-3 rounded-3">
                    <h6 class="fw-bold small text-slate-800 mb-2 d-flex align-items-center gap-1.5 text-uppercase tracking-wider">
                        <i class="bi bi-pencil-square text-primary"></i> Administrative Status &amp; Internal Notes
                    </h6>
                    <form id="adminStatusForm" onsubmit="submitAdminStatusUpdate(event)" class="row g-3 align-items-end">
                        <input type="hidden" id="statusEnquiryId" value="">
                        <div class="col-md-4">
                            <label class="form-label smaller text-muted mb-1 fw-bold">Update Status</label>
                            <select id="statusSelect" class="form-select form-select-sm" required>
                                <?php foreach ($statuses as $sKey => $sLabel): ?>
                                    <option value="<?= htmlspecialchars($sKey) ?>"><?= htmlspecialchars($sLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label smaller text-muted mb-1 fw-bold">Internal Admin Notes (Optional)</label>
                            <input type="text" id="statusNotes" class="form-control form-control-sm" placeholder="e.g. Verified quantities with factories, sent quote to buyer">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" id="btnUpdateStatus" class="btn btn-primary btn-sm fw-bold w-100">
                                Save Status
                            </button>
                        </div>
                    </form>
                    <div id="statusAlert" class="alert alert-success d-none py-1.5 px-3 small mt-2 mb-0"></div>
                </div>
            </div>

            <div class="modal-footer border-top py-2.5">
                <button type="button" class="btn btn-secondary btn-sm fw-bold px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentEnquiryId = 0;

function openAdminBulkDetail(id) {
    currentEnquiryId = id;
    document.getElementById('statusEnquiryId').value = id;
    document.getElementById('statusAlert').classList.add('d-none');

    fetch(`<?= $baseUrl ?>/admin/bulk-enquiries/${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.enquiry) {
            alert(res.message || 'Error loading bulk enquiry details.');
            return;
        }

        const e = res.enquiry;
        document.getElementById('modalRefNo').innerText = e.reference_no;
        document.getElementById('mBuyerName').innerText = e.buyer_name;
        document.getElementById('mCompanyName').innerText = e.company_name || 'N/A';
        document.getElementById('mBuyerEmail').innerText = e.buyer_email;
        document.getElementById('mBuyerPhone').innerText = e.buyer_phone || 'N/A';
        document.getElementById('mCountry').innerText = e.resolved_country_name || e.country_name || 'India';
        document.getElementById('mDeliveryLocation').innerText = e.delivery_location || 'Not Specified';
        document.getElementById('mCreatedAt').innerText = e.created_at;
        document.getElementById('mProductCount').innerText = e.product_count;
        document.getElementById('mOverallMessage').innerText = e.overall_message || 'No additional instructions provided.';
        
        // Status
        document.getElementById('statusSelect').value = e.status;
        document.getElementById('statusNotes').value = e.admin_notes || '';

        const badge = document.getElementById('modalStatusBadge');
        badge.innerText = e.status;
        badge.className = 'badge px-2.5 py-1 ' + (e.status === 'SUBMITTED' ? 'bg-info text-white' : (e.status === 'PROCESSED' ? 'bg-success text-white' : 'bg-secondary text-white'));

        // Shared Attachment
        const attachContent = document.getElementById('mSharedAttachmentContent');
        if (e.attachment_path) {
            attachContent.innerHTML = `
                <a href="<?= $baseUrl ?>/admin/bulk-enquiries/attachment/${e.id}" class="btn btn-sm btn-outline-primary py-1 px-2 fw-bold d-inline-flex align-items-center gap-1 text-truncate" style="max-width: 100%;">
                    <i class="bi bi-download"></i>
                    <span class="text-truncate">${e.attachment_name || 'Download Tech Pack'}</span>
                </a>
            `;
        } else {
            attachContent.innerHTML = `<span class="text-muted smaller">None attached.</span>`;
        }

        // Render Product Items Table
        const tbody = document.getElementById('mItemsTableBody');
        tbody.innerHTML = '';
        (e.items || []).forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="ps-3">
                    <div class="d-flex align-items-center gap-2">
                        <img src="${item.primary_image ? (item.primary_image.indexOf('/') !== -1 ? '<?= $baseUrl ?>/' + item.primary_image.replace(/^\//, '') : '<?= $baseUrl ?>/uploads/products/' + item.primary_image) : '<?= $baseUrl ?>/assets/images/logo.jpg'}" onerror="this.onerror=null;this.src='<?= $baseUrl ?>/assets/images/logo.jpg';" class="rounded border" style="width: 36px; height: 36px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-slate-900">${item.product_name}</div>
                            <div class="text-muted smaller">Supplier: <strong class="text-primary">${item.supplier_name || 'Verified Factory'}</strong></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-bold text-slate-900">${item.target_quantity} ${item.moq_unit_name || 'units'}</div>
                    <div class="text-muted smaller">MOQ: ${item.moq_quantity}</div>
                </td>
                <td>
                    <div class="fw-bold text-slate-900">${item.target_price ? ('$' + parseFloat(item.target_price).toFixed(2)) : '<span class="text-muted">RFQ</span>'}</div>
                </td>
                <td>
                    <div class="text-slate-800 whitespace-pre-line">${item.specifications || 'Standard specifications'}</div>
                </td>
                <td class="text-end pe-3">
                    ${item.attachment_path ? `
                        <a href="<?= $baseUrl ?>/admin/bulk-enquiries/item-attachment/${item.id}" class="btn btn-xs btn-outline-secondary py-0.5 px-2" title="Download Product Specs File">
                            <i class="bi bi-download"></i> Download
                        </a>
                    ` : '<span class="text-muted smaller">—</span>'}
                </td>
            `;
            tbody.appendChild(tr);
        });

        const modal = new bootstrap.Modal(document.getElementById('adminBulkDetailModal'));
        modal.show();
    })
    .catch(() => alert('Network error loading details.'));
}

function submitAdminStatusUpdate(e) {
    e.preventDefault();
    const id = document.getElementById('statusEnquiryId').value;
    const status = document.getElementById('statusSelect').value;
    const notes = document.getElementById('statusNotes').value;
    const btn = document.getElementById('btnUpdateStatus');
    const alertBox = document.getElementById('statusAlert');

    btn.disabled = true;
    btn.innerText = 'Saving...';

    const formData = new FormData();
    formData.append('status', status);
    formData.append('admin_notes', notes);

    fetch(`<?= $baseUrl ?>/api/admin/bulk-enquiries/${id}/status`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'Save Status';
        if (res.success) {
            alertBox.className = 'alert alert-success py-1.5 px-3 small mt-2 mb-0';
            alertBox.innerText = res.message;
            alertBox.classList.remove('d-none');

            // Update row badge
            const rowBadge = document.querySelector(`.status-badge-${id}`);
            if (rowBadge) {
                rowBadge.innerText = res.label || status;
            }
        } else {
            alertBox.className = 'alert alert-danger py-1.5 px-3 small mt-2 mb-0';
            alertBox.innerText = res.message || 'Error updating status.';
            alertBox.classList.remove('d-none');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerText = 'Save Status';
        alertBox.className = 'alert alert-danger py-1.5 px-3 small mt-2 mb-0';
        alertBox.innerText = 'Network error during update.';
        alertBox.classList.remove('d-none');
    });
}
</script>
