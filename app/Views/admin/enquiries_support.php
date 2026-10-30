<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-2 fw-bold" style="font-size: 11px; color: #dc2626 !important; background-color: #fef2f2 !important; border: 1px solid #fecaca;">SUPPORT DESK</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Help Centre &amp; Support Enquiries</h3>
        <p class="text-slate-500 small mb-0">Support tickets submitted through the Help Centre, order assistance, and buyer resolution desk.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Filtered Results
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportSupportEnquiries('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportSupportEnquiries('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportSupportEnquiries('pdf'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Print / Save PDF</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Filters Card (No Irrelevant Product or Supplier Dropdowns) -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <!-- Status Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Ticket Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <option value="PENDING">Pending / Open</option>
                    <option value="VIEWED">Viewed</option>
                    <option value="IN_DISCUSSION">In Discussion</option>
                    <option value="RESPONDED">Responded</option>
                    <option value="RESOLVED">Resolved</option>
                    <option value="CLOSED">Closed</option>
                    <option value="REJECTED">Dismissed</option>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="col-md-6">
                <label class="form-label small fw-bold text-slate-600 mb-1">Date Range</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="filterStartDate" class="form-control" title="Start Date">
                    <span class="input-group-text bg-white text-muted">to</span>
                    <input type="date" id="filterEndDate" class="form-control" title="End Date">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-3 d-flex gap-2">
                <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
                    <i class="bi bi-x-circle me-1"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="supportEnquiriesTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th>Ticket ID</th>
                        <th>Requester Information</th>
                        <th>Organization</th>
                        <th>Country / Location</th>
                        <th>Category / Subject</th>
                        <th>Issue Details</th>
                        <th>Attachment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width:110px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

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
        'PENDING':       { bg: '#fef3c7', color: '#b45309', label: 'Open' },
        'SUBMITTED':     { bg: '#fef3c7', color: '#b45309', label: 'Open' },
        'VIEWED':        { bg: '#e0e7ff', color: '#4338ca', label: 'Viewed' },
        'RESPONDED':     { bg: '#dcfce7', color: '#15803d', label: 'Responded' },
        'IN_DISCUSSION': { bg: '#dbeafe', color: '#1d4ed8', label: 'In Discussion' },
        'RESOLVED':      { bg: '#d1fae5', color: '#065f46', label: 'Resolved' },
        'CLOSED':        { bg: '#f1f5f9', color: '#475569', label: 'Closed' },
        'REJECTED':      { bg: '#fee2e2', color: '#b91c1c', label: 'Dismissed' },
    };
    const cfg = map[st] || { bg: '#f1f5f9', color: '#475569', label: st || 'Open' };
    return `<span class="badge rounded-pill" style="background:${cfg.bg};color:${cfg.color};">${escapeHtml(cfg.label)}</span>`;
}

function setStatusFilter(st) {
    $('#filterStatus').val(st).trigger('change');
}

$(document).ready(function() {
    table = $('#supportEnquiriesTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 15,
        order: [[7, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/support-enquiries/data',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            {
                data: 'id',
                render: (id, type, row) => `
                    <a href="${BASE_URL}/admin/enquiries/${id}" class="fw-bold text-danger text-decoration-none">
                        #SUP-${String(id).padStart(5, '0')}
                    </a>
                    ${Number(row.reply_count || 0) > 0 ? `<span class="badge bg-primary bg-opacity-10 text-primary ms-1"><i class="bi bi-chat-dots"></i> ${row.reply_count}</span>` : ''}
                `
            },
            {
                data: 'buyer_name',
                render: (name, type, row) => `
                    <div class="fw-bold text-slate-800">${escapeHtml(name)}</div>
                    <div class="smaller text-muted">${escapeHtml(row.buyer_email || '')}</div>
                    ${row.buyer_phone ? `<div class="smaller text-slate-600"><i class="bi bi-telephone me-1"></i>${escapeHtml(row.buyer_phone)}</div>` : ''}
                `
            },
            {
                data: 'company_name',
                render: val => val ? `<span class="fw-semibold text-slate-700">${escapeHtml(val)}</span>` : '<span class="text-muted smaller">Individual</span>'
            },
            {
                data: 'country_name',
                render: (country, type, row) => {
                    const loc = row.delivery_location || '';
                    return `<div>${escapeHtml(country || 'N/A')}</div>${loc ? `<div class="smaller text-muted">${escapeHtml(loc)}</div>` : ''}`;
                }
            },
            {
                data: 'subject',
                render: subj => `<div class="fw-semibold text-slate-800 text-truncate" style="max-width:180px;" title="${escapeHtml(subj || 'Support Ticket')}">${escapeHtml(subj || 'Support Ticket')}</div>`
            },
            {
                data: 'specifications',
                render: val => `<div class="small text-muted text-truncate" style="max-width:230px;" title="${escapeHtml(val || '')}">${escapeHtml(val || '-')}</div>`
            },
            {
                data: 'attachment_path',
                render: (path, type, row) => {
                    if (!path) return '<span class="text-muted smaller">None</span>';
                    const name = row.attachment_name || 'Attachment';
                    return `
                        <a href="${BASE_URL}/admin/enquiries/attachment/${row.id}" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2 rounded-2 text-decoration-none" title="Download ${escapeHtml(name)}">
                            <i class="bi bi-paperclip text-primary"></i> <span class="smaller">File</span>
                        </a>
                    `;
                }
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
                        <a href="${BASE_URL}/admin/enquiries/${row.id}" class="btn btn-outline-primary" title="View Full Ticket & Conversation">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-outline-success" title="Mark Resolved" onclick="quickStatus(${row.id}, 'RESOLVED')">
                            <i class="bi bi-check2-all"></i>
                        </button>
                    </div>
                `
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search support tickets..."
        }
    });

    $('#filterStatus, #filterStartDate, #filterEndDate').on('change', function() {
        table.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#filterStatus').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        table.search('').draw();
    });
});

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

function exportSupportEnquiries(format) {
    const params = new URLSearchParams({
        format: format,
        status: $('#filterStatus').val(),
        start_date: $('#filterStartDate').val(),
        end_date: $('#filterEndDate').val(),
        search: table.search()
    });
    window.location.href = `${BASE_URL}/api/admin/support-enquiries/export?${params.toString()}`;
}
</script>
