<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-info bg-opacity-10 text-info px-2 py-1 rounded-2 fw-bold" style="font-size: 11px; color: #0284c7 !important; background-color: #e0f2fe !important; border: 1px solid #bae6fd;">GENERAL SOURCING</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">General Enquiries</h3>
        <p class="text-slate-500 small mb-0">Broad sports goods sourcing requirements, custom manufacturing inquiries, and general B2B inquiries.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Filtered Results
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportGeneralEnquiries('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportGeneralEnquiries('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportGeneralEnquiries('pdf'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Print / Save PDF</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-speedometer2 me-1"></i> All Enquiries Hub
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('ALL')">
            <div class="text-slate-500 small fw-semibold">Total General Enquiries</div>
            <h4 class="fw-bold text-slate-900 mb-0 mt-1" id="mTotal"><?= (int)($metrics['total'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('PENDING')">
            <div class="text-slate-500 small fw-semibold">Pending Review</div>
            <h4 class="fw-bold text-warning mb-0 mt-1" style="color: #b45309 !important;" id="mPending"><?= (int)($metrics['pending'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('IN_DISCUSSION')">
            <div class="text-slate-500 small fw-semibold">In Discussion</div>
            <h4 class="fw-bold text-primary mb-0 mt-1" id="mInProgress"><?= (int)($metrics['in_progress'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('RESOLVED')">
            <div class="text-slate-500 small fw-semibold">Resolved / Completed</div>
            <h4 class="fw-bold text-success mb-0 mt-1" id="mResolved"><?= (int)($metrics['resolved'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('REJECTED')">
            <div class="text-slate-500 small fw-semibold">Rejected</div>
            <h4 class="fw-bold text-danger mb-0 mt-1" id="mRejected"><?= (int)($metrics['rejected'] ?? 0) ?></h4>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <!-- Supplier Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Assigned Supplier</label>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="supplierSearchBox" class="form-control" placeholder="Search supplier...">
                </div>
                <select id="filterSupplier" class="form-select form-select-sm">
                    <option value="ALL">All Suppliers</option>
                    <option value="UNASSIGNED">Unassigned Only</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= (int)$sup['id'] ?>" data-search="<?= htmlspecialchars(strtolower(($sup['company_name'] ?? '') . ' ' . ($sup['email'] ?? ''))) ?>">
                            <?= htmlspecialchars($sup['company_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Enquiry Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <option value="PENDING">Pending / Submitted</option>
                    <option value="VIEWED">Viewed</option>
                    <option value="IN_DISCUSSION">In Discussion</option>
                    <option value="RESPONDED">Responded</option>
                    <option value="RESOLVED">Resolved</option>
                    <option value="CLOSED">Closed</option>
                    <option value="REJECTED">Rejected</option>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="col-md-4">
                <label class="form-label small fw-bold text-slate-600 mb-1">Date Range</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="filterStartDate" class="form-control" title="Start Date">
                    <span class="input-group-text bg-white text-muted">to</span>
                    <input type="date" id="filterEndDate" class="form-control" title="End Date">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="generalEnquiriesTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th>Enquiry ID</th>
                        <th>Subject / Requirement</th>
                        <th>Buyer Information</th>
                        <th>Target Qty</th>
                        <th>Destination</th>
                        <th>Assigned Supplier</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Supplier Modal -->
<div class="modal fade" id="assignSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="assignSupplierForm" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" id="asEnquiryId">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-building-check text-primary me-2"></i>Assign Supplier</h5>
                    <small class="text-slate-500" id="asModalSubtitle"></small>
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

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
let table;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
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
        'REJECTED':      { bg: '#fee2e2', color: '#b91c1c', label: 'Rejected' },
    };
    const cfg = map[st] || { bg: '#f1f5f9', color: '#475569', label: st || 'Submitted' };
    return `<span class="badge rounded-pill" style="background:${cfg.bg};color:${cfg.color};">${escapeHtml(cfg.label)}</span>`;
}

function setStatusFilter(st) {
    $('#filterStatus').val(st).trigger('change');
}

$(document).ready(function() {
    $('#supplierSearchBox').on('input', function() {
        const q = $(this).val().toLowerCase().trim();
        $('#filterSupplier option').each(function() {
            const val = $(this).val();
            if (val === 'ALL' || val === 'UNASSIGNED') { $(this).show(); return; }
            const hay = ($(this).data('search') || $(this).text()).toLowerCase();
            if (!q || hay.includes(q)) $(this).show(); else $(this).hide();
        });
    });

    table = $('#generalEnquiriesTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 15,
        order: [[6, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/general-enquiries/data',
            data: function(d) {
                d.supplier_id = $('#filterSupplier').val();
                d.status = $('#filterStatus').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            {
                data: 'id',
                render: (id, type, row) => `
                    <a href="${BASE_URL}/admin/enquiries/${id}" class="fw-bold text-primary text-decoration-none">
                        #GEN-${String(id).padStart(5, '0')}
                    </a>
                    ${Number(row.reply_count || 0) > 0 ? `<span class="badge bg-primary bg-opacity-10 text-primary ms-1"><i class="bi bi-chat-dots"></i> ${row.reply_count}</span>` : ''}
                `
            },
            {
                data: 'subject',
                render: (subj, type, row) => {
                    const title = subj || 'General Requirement';
                    const specs = row.specifications ? `<div class="smaller text-muted text-truncate" style="max-width:220px;" title="${escapeHtml(row.specifications)}">${escapeHtml(row.specifications)}</div>` : '';
                    return `<div class="fw-semibold text-slate-800 text-truncate" style="max-width:220px;" title="${escapeHtml(title)}">${escapeHtml(title)}</div>${specs}`;
                }
            },
            {
                data: 'buyer_name',
                render: (name, type, row) => `
                    <div class="fw-bold text-slate-800">${escapeHtml(name)}</div>
                    ${row.company_name ? `<div class="small text-slate-600">${escapeHtml(row.company_name)}</div>` : ''}
                    <div class="smaller text-muted">${escapeHtml(row.buyer_email || '')}</div>
                `
            },
            {
                data: 'target_quantity',
                render: qty => `<div class="fw-bold">${Number(qty || 1).toLocaleString()} <span class="smaller text-muted">units</span></div>`
            },
            {
                data: 'delivery_location',
                render: (loc, type, row) => {
                    const country = row.country_name || '';
                    return `<div>${escapeHtml(loc || '-')}</div>${country ? `<div class="smaller text-muted">${escapeHtml(country)}</div>` : ''}`;
                }
            },
            {
                data: 'supplier_name',
                render: sName => sName ? `<span class="fw-semibold text-slate-700">${escapeHtml(sName)}</span>` : `<span class="badge bg-secondary bg-opacity-10 text-secondary">Unassigned</span>`
            },
            {
                data: 'created_at',
                render: val => val ? `<span class="small text-slate-600">${escapeHtml(val.substring(0, 16))}</span>` : '-'
            },
            {
                data: 'status',
                render: st => renderStatusBadge(st)
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: row => `
                    <div class="btn-group btn-group-sm">
                        <a href="${BASE_URL}/admin/enquiries/${row.id}" class="btn btn-outline-primary" title="View Full Details">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-outline-secondary" title="Assign / Reassign Supplier" onclick="openAssignSupplier(${row.id}, ${row.effective_supplier_id || 'null'})">
                            <i class="bi bi-building-gear"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" title="Mark Resolved" onclick="quickStatus(${row.id}, 'RESOLVED')">
                            <i class="bi bi-check2-all"></i>
                        </button>
                    </div>
                `
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search general enquiries..."
        }
    });

    $('#filterSupplier, #filterStatus, #filterStartDate, #filterEndDate').on('change', function() {
        table.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#supplierSearchBox').val('').trigger('input');
        $('#filterSupplier').val('ALL');
        $('#filterStatus').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        table.search('').draw();
    });

    $('#assignSupplierForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#asEnquiryId').val();
        $.post(`${BASE_URL}/api/admin/enquiries/${id}/assign-supplier`, $(this).serialize(), function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('assignSupplierModal')).hide();
                Swal.fire({ icon: 'success', title: 'Assigned', text: res.message, timer: 2000, showConfirmButton: false });
                table.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.message || 'Failed to assign supplier.', 'error');
            }
        }, 'json');
    });
});

function openAssignSupplier(enquiryId, currentSupplierId) {
    $('#asEnquiryId').val(enquiryId);
    $('#asModalSubtitle').text(`General Enquiry #GEN-${String(enquiryId).padStart(5, '0')}`);
    $('#asSupplierSelect').val(currentSupplierId || '');
    new bootstrap.Modal(document.getElementById('assignSupplierModal')).show();
}

function quickStatus(enquiryId, status) {
    $.post(`${BASE_URL}/api/admin/enquiries/${enquiryId}/status`, {
        csrf_token: CSRF_TOKEN,
        status: status
    }, function(res) {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 1800, showConfirmButton: false });
            table.ajax.reload(null, false);
        } else {
            Swal.fire('Error', res.message || 'Could not update status.', 'error');
        }
    }, 'json');
}

function exportGeneralEnquiries(format) {
    const params = new URLSearchParams({
        format: format,
        supplier_id: $('#filterSupplier').val(),
        status: $('#filterStatus').val(),
        start_date: $('#filterStartDate').val(),
        end_date: $('#filterEndDate').val(),
        search: table.search()
    });
    window.location.href = `${BASE_URL}/api/admin/general-enquiries/export?${params.toString()}`;
}
</script>
<?php
$adminScripts = ob_get_clean();
?>
