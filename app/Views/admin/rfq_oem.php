<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 rounded-2 fw-bold" style="font-size: 11px; color: #b45309 !important;">CENTRALIZED RFQ & OEM DESK</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">RFQ &amp; Fast OEM Customization Enquiries</h3>
        <p class="text-slate-500 small mb-0">Dedicated tracking, supplier routing, and quotation management for bulk RFQs and custom OEM manufacturing orders.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Filtered Results
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportRfqOem('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportRfqOem('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportRfqOem('pdf'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Print / Save PDF</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-chat-left-quote me-1"></i> All Enquiries Hub
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">Total RFQ &amp; OEM</div>
            <h4 class="fw-bold text-slate-900 mb-0 mt-1" id="mTotal"><?= (int)($metrics['total'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">RFQ Requests</div>
            <h4 class="fw-bold text-warning mb-0 mt-1" style="color: #b45309 !important;" id="mRfq"><?= (int)($metrics['rfq_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">Fast OEM Requests</div>
            <h4 class="fw-bold text-teal mb-0 mt-1" style="color: #0f766e !important;" id="mFastOem"><?= (int)($metrics['fast_oem_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">Pending / New</div>
            <h4 class="fw-bold text-danger mb-0 mt-1" id="mPending"><?= (int)($metrics['pending'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">In Discussion</div>
            <h4 class="fw-bold text-primary mb-0 mt-1" id="mResponded"><?= (int)($metrics['responded'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">Resolved / Converted</div>
            <h4 class="fw-bold text-success mb-0 mt-1" id="mResolved"><?= (int)($metrics['resolved'] ?? 0) ?></h4>
        </div>
    </div>
</div>

<!-- Multi-Filter & Search Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <!-- Searchable Supplier Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Filter by Supplier</label>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="supplierSearchBox" class="form-control" placeholder="Search supplier...">
                </div>
                <select id="filterSupplier" class="form-select form-select-sm">
                    <option value="ALL">All Suppliers &amp; Unassigned</option>
                    <option value="UNASSIGNED">Unassigned Enquiries Only</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= (int)$sup['id'] ?>" data-search="<?= htmlspecialchars(strtolower(($sup['company_name'] ?? '') . ' ' . ($sup['contact_person'] ?? '') . ' ' . ($sup['email'] ?? ''))) ?>">
                            <?= htmlspecialchars($sup['company_name']) ?> (#SUP-<?= (int)$sup['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Enquiry Type Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Request Type</label>
                <select id="filterSource" class="form-select form-select-sm">
                    <option value="ALL">All Types (RFQ &amp; Fast OEM)</option>
                    <option value="RFQ">RFQ (Request For Quotation)</option>
                    <option value="FAST_OEM">Fast OEM Customization</option>
                </select>
            </div>

            <!-- Enquiry Status Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <option value="SUBMITTED">Submitted / Pending</option>
                    <option value="VIEWED">Viewed</option>
                    <option value="RESPONDED">Responded</option>
                    <option value="IN_DISCUSSION">In Discussion</option>
                    <option value="RESOLVED">Resolved / Converted</option>
                    <option value="CLOSED">Closed</option>
                </select>
            </div>

            <!-- Product Category Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Category</label>
                <select id="filterCategory" class="form-select form-select-sm">
                    <option value="ALL">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Date Range (From — To)</label>
                <div class="d-flex gap-1">
                    <input type="date" id="filterStartDate" class="form-control form-control-sm" title="Start Date">
                    <input type="date" id="filterEndDate" class="form-control form-control-sm" title="End Date">
                    <button type="button" id="btnClearEnquiryFilters" class="btn btn-outline-secondary btn-sm px-2" title="Clear All Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Centralized Enquiries DataTable -->
<div class="card card-premium">
    <div class="card-body p-3 p-md-4">
        <div class="table-responsive">
            <table id="rfqOemTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th>Enquiry ID</th>
                        <th>Type</th>
                        <th>Buyer Name</th>
                        <th>Company</th>
                        <th>Email &amp; Phone</th>
                        <th>Requirement / Product</th>
                        <th>Qty Required</th>
                        <th>Target Price</th>
                        <th>Delivery Location</th>
                        <th>Assigned Supplier</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Assign Supplier Modal -->
<div class="modal fade" id="assignSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="assignSupplierForm" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" id="asEnquiryId">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-building-check text-primary me-2"></i>Route to Supplier</h5>
                    <small class="text-slate-500" id="asModalSubtitle">Assign this RFQ / OEM enquiry to a verified manufacturer</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-slate-700">Select Verified Supplier</label>
                    <select name="supplier_id" id="asSupplierSelect" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int)$sup['id'] ?>">
                                <?= htmlspecialchars($sup['company_name']) ?> (<?= htmlspecialchars($sup['email'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="updateStatusForm" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" id="usEnquiryId">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-tag text-primary me-2"></i>Update Enquiry Status</h5>
                    <small class="text-slate-500" id="usModalSubtitle">Manage inquiry progression</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-slate-700">Select Progression Status</label>
                    <select name="status" id="usStatusSelect" class="form-select">
                        <option value="SUBMITTED">Submitted / Pending</option>
                        <option value="VIEWED">Viewed</option>
                        <option value="RESPONDED">Responded</option>
                        <option value="IN_DISCUSSION">In Discussion</option>
                        <option value="RESOLVED">Resolved / Converted</option>
                        <option value="CLOSED">Closed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Update Status</button>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
let rfqOemTable;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function renderSourceBadge(src) {
    if (src === 'RFQ') {
        return `<span class="badge rounded-pill" style="background:#fef3c7;color:#b45309;"><i class="bi bi-clipboard2-check me-1"></i>RFQ Request</span>`;
    } else if (src === 'FAST_OEM') {
        return `<span class="badge rounded-pill" style="background:#ccfbf1;color:#0f766e;"><i class="bi bi-gear-wide-connected me-1"></i>Fast OEM</span>`;
    }
    return `<span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;"><i class="bi bi-megaphone me-1"></i>${escapeHtml(src)}</span>`;
}

function renderStatusBadge(st) {
    const map = {
        'PENDING':       { bg: '#fef3c7', color: '#b45309', label: 'Submitted' },
        'SUBMITTED':     { bg: '#fef3c7', color: '#b45309', label: 'Submitted' },
        'VIEWED':        { bg: '#e0e7ff', color: '#4338ca', label: 'Viewed' },
        'RESPONDED':     { bg: '#dcfce7', color: '#15803d', label: 'Responded' },
        'IN_DISCUSSION': { bg: '#dbeafe', color: '#1d4ed8', label: 'In Discussion' },
        'RESOLVED':      { bg: '#d1fae5', color: '#065f46', label: 'Resolved' },
        'CLOSED':        { bg: '#f1f5f9', color: '#475569', label: 'Closed' },
    };
    const s = map[st] || { bg: '#f1f5f9', color: '#475569', label: st };
    return `<span class="badge rounded-pill" style="background:${s.bg};color:${s.color};">${s.label}</span>`;
}

$(document).ready(function() {
    rfqOemTable = $('#rfqOemTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: true,
        ordering: true,
        order: [[10, 'desc']], // Order by Date
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        ajax: {
            url: BASE_URL + '/api/admin/rfq-oem/data',
            type: 'GET',
            data: function(d) {
                d.supplier_id    = $('#filterSupplier').val();
                d.enquiry_source = $('#filterSource').val();
                d.status         = $('#filterStatus').val();
                d.category_id    = $('#filterCategory').val();
                d.start_date     = $('#filterStartDate').val();
                d.end_date       = $('#filterEndDate').val();
            }
        },
        columns: [
            {
                data: 'id',
                render: function(data) {
                    const padded = String(data).padStart(5, '0');
                    return `<a href="${BASE_URL}/admin/enquiries/${data}" class="fw-bold text-primary text-decoration-none">#ENQ-${padded}</a>`;
                }
            },
            {
                data: 'enquiry_source',
                render: function(data) {
                    return renderSourceBadge(data);
                }
            },
            {
                data: 'buyer_name',
                render: function(data) {
                    return `<span class="fw-semibold text-slate-900">${escapeHtml(data || 'Guest')}</span>`;
                }
            },
            {
                data: 'company_name',
                render: function(data) {
                    return data ? `<span class="text-slate-700">${escapeHtml(data)}</span>` : '<span class="text-muted fst-italic">—</span>';
                }
            },
            {
                data: null,
                render: function(row) {
                    let out = `<div><a href="mailto:${escapeHtml(row.buyer_email || '')}" class="text-decoration-none text-slate-800">${escapeHtml(row.buyer_email || '')}</a></div>`;
                    if (row.buyer_phone) {
                        out += `<small class="text-muted">${escapeHtml(row.buyer_phone)}</small>`;
                    }
                    return out;
                }
            },
            {
                data: null,
                render: function(row) {
                    let title = row.product_name || row.subject || 'Custom Requirement';
                    let specs = row.specifications ? `<small class="text-muted d-block text-truncate" style="max-width:200px;" title="${escapeHtml(row.specifications)}">${escapeHtml(row.specifications)}</small>` : '';
                    return `<div><strong class="text-slate-800">${escapeHtml(title)}</strong>${specs}</div>`;
                }
            },
            {
                data: 'target_quantity',
                render: function(data, type, row) {
                    return `<span class="badge bg-light text-dark border">${data ? Number(data).toLocaleString() : '1'} ${escapeHtml(row.moq_unit_name || 'Units')}</span>`;
                }
            },
            {
                data: 'target_price',
                render: function(data) {
                    return data ? `<span class="fw-bold text-success">$${Number(data).toFixed(2)}</span>` : '<span class="text-muted">—</span>';
                }
            },
            {
                data: null,
                render: function(row) {
                    const country = row.country_name ? `<span class="badge bg-light text-dark border px-2 py-0.5 small"><i class="bi bi-geo-alt text-primary me-1"></i>${escapeHtml(row.country_name)}</span>` : '';
                    let loc = row.delivery_location || row.lead_time_req;
                    const locText = loc ? `<div class="small text-slate-700 mt-1">${escapeHtml(loc)}</div>` : '';
                    if (!country && !locText) return '<span class="text-muted">—</span>';
                    return `<div>${country}${locText}</div>`;
                }
            },
            {
                data: null,
                render: function(row) {
                    let sName = row.supplier_name;
                    if (sName) {
                        return `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">${escapeHtml(sName)}</span>`;
                    }
                    return `<button type="button" class="btn btn-outline-warning btn-sm py-0 px-2 fw-semibold" style="font-size:11px;" onclick="openAssignSupplierModal(${row.id}, null, '${escapeHtml(row.buyer_name || '')}')">
                        <i class="bi bi-person-plus me-1"></i>Assign
                    </button>`;
                }
            },
            {
                data: 'created_at',
                render: function(data) {
                    if (!data) return '';
                    return `<span class="small text-slate-600">${escapeHtml(data.substring(0, 10))}</span>`;
                }
            },
            {
                data: 'status',
                render: function(data) {
                    return renderStatusBadge(data);
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: function(row) {
                    const padded = String(row.id).padStart(5, '0');
                    return `
                        <div class="d-inline-flex gap-1">
                            <a href="${BASE_URL}/admin/enquiries/${row.id}" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Full Enquiry Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Assign / Change Supplier" onclick="openAssignSupplierModal(${row.id}, ${row.effective_supplier_id || 'null'}, '${escapeHtml(row.buyer_name || '')}')">
                                <i class="bi bi-building-check"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" title="Update Status" onclick="openUpdateStatusModal(${row.id}, '${escapeHtml(row.status)}')">
                                <i class="bi bi-tag"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            emptyTable: 'No RFQ or Fast OEM customization enquiries found.',
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading enquiries...'
        }
    });

    // Event listeners for filters
    $('#filterSupplier, #filterSource, #filterStatus, #filterCategory, #filterStartDate, #filterEndDate').on('change', function() {
        rfqOemTable.ajax.reload();
    });

    $('#supplierSearchBox').on('keyup', function() {
        const q = $(this).val().toLowerCase().trim();
        $('#filterSupplier option').each(function() {
            const searchData = $(this).data('search');
            if (!searchData || $(this).val() === 'ALL' || $(this).val() === 'UNASSIGNED') {
                $(this).show();
            } else if (searchData.includes(q)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('#btnClearEnquiryFilters').on('click', function() {
        $('#filterSupplier').val('ALL');
        $('#supplierSearchBox').val('');
        $('#filterSupplier option').show();
        $('#filterSource').val('ALL');
        $('#filterStatus').val('ALL');
        $('#filterCategory').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        rfqOemTable.ajax.reload();
    });

    // Form handlers
    $('#assignSupplierForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#asEnquiryId').val();
        const supId = $('#asSupplierSelect').val();

        $.ajax({
            url: BASE_URL + '/api/admin/enquiries/' + id + '/assign-supplier',
            type: 'POST',
            data: {
                csrf_token: CSRF_TOKEN,
                supplier_id: supId
            },
            success: function(resp) {
                bootstrap.Modal.getInstance(document.getElementById('assignSupplierModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Supplier Assigned',
                    text: resp.message || 'Supplier assignment updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                rfqOemTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Assignment Failed',
                    text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not assign supplier.'
                });
            }
        });
    });

    $('#updateStatusForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#usEnquiryId').val();
        const status = $('#usStatusSelect').val();

        $.ajax({
            url: BASE_URL + '/api/admin/enquiries/' + id + '/status',
            type: 'POST',
            data: {
                csrf_token: CSRF_TOKEN,
                status: status
            },
            success: function(resp) {
                bootstrap.Modal.getInstance(document.getElementById('updateStatusModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Status Updated',
                    text: resp.message || 'Enquiry status updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                rfqOemTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not update status.'
                });
            }
        });
    });
});

function openAssignSupplierModal(enquiryId, currentSupplierId, buyerName) {
    $('#asEnquiryId').val(enquiryId);
    $('#asModalSubtitle').text('Enquiry #ENQ-' + String(enquiryId).padStart(5, '0') + (buyerName ? ' from ' + buyerName : ''));
    $('#asSupplierSelect').val(currentSupplierId || '');
    new bootstrap.Modal(document.getElementById('assignSupplierModal')).show();
}

function openUpdateStatusModal(enquiryId, currentStatus) {
    $('#usEnquiryId').val(enquiryId);
    $('#usModalSubtitle').text('Enquiry #ENQ-' + String(enquiryId).padStart(5, '0'));
    $('#usStatusSelect').val(currentStatus || 'SUBMITTED');
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function exportRfqOem(format) {
    const params = new URLSearchParams({
        format: format,
        supplier_id: $('#filterSupplier').val() || 'ALL',
        enquiry_source: $('#filterSource').val() || 'ALL',
        status: $('#filterStatus').val() || 'ALL',
        category_id: $('#filterCategory').val() || 'ALL',
        start_date: $('#filterStartDate').val() || '',
        end_date: $('#filterEndDate').val() || '',
        search: $('#rfqOemTable_filter input').val() || ''
    });
    window.location.href = BASE_URL + '/api/admin/rfq-oem/export?' + params.toString();
}
</script>
<?php $scripts = ob_get_clean(); ?>
