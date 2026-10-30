<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-emerald-50 text-emerald-800 px-2 py-1 rounded-2 fw-bold" style="font-size: 11px; border: 1px solid #a7f3d0;">MARKETPLACE VOICE</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Suggestions &amp; Feedback Management</h3>
        <p class="text-slate-500 small mb-0">Review, categorize, and act on user feedback, platform suggestions, and bug reports.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Results
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportFeedback('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportFeedback('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/feedback-suggestions" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-box-arrow-up-right me-1"></i> Public Feedback Form
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('ALL')">
            <div class="text-slate-500 small fw-semibold">Total Submissions</div>
            <h4 class="fw-bold text-slate-900 mb-0 mt-1" id="mTotal"><?= (int)($metrics['total'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('NEW')">
            <div class="text-slate-500 small fw-semibold">New / Unread</div>
            <h4 class="fw-bold text-warning mb-0 mt-1" style="color: #b45309 !important;" id="mNew"><?= (int)($metrics['new'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('UNDER_REVIEW')">
            <div class="text-slate-500 small fw-semibold">Under Review</div>
            <h4 class="fw-bold text-primary mb-0 mt-1" id="mUnderReview"><?= (int)($metrics['under_review'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('REVIEWED')">
            <div class="text-slate-500 small fw-semibold">Reviewed</div>
            <h4 class="fw-bold text-success mb-0 mt-1" id="mReviewed"><?= (int)($metrics['reviewed'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('CLOSED')">
            <div class="text-slate-500 small fw-semibold">Closed</div>
            <h4 class="fw-bold text-secondary mb-0 mt-1" id="mClosed"><?= (int)($metrics['closed'] ?? 0) ?></h4>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <!-- Feedback Type Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Feedback Type</label>
                <select id="filterType" class="form-select form-select-sm">
                    <option value="ALL">All Feedback Types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Review Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <option value="NEW">New</option>
                    <option value="UNDER_REVIEW">Under Review</option>
                    <option value="REVIEWED">Reviewed</option>
                    <option value="CLOSED">Closed</option>
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
            <table id="feedbacksTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th>Reference ID</th>
                        <th>Sender Information</th>
                        <th>Organization</th>
                        <th>Country</th>
                        <th>Feedback Type</th>
                        <th>Subject &amp; Message</th>
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

<!-- View Details & Notes Modal -->
<div class="modal fade" id="feedbackDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900" id="fdModalTitle">Feedback Details</h5>
                    <small class="text-slate-500" id="fdModalRef"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 space-y-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Sender Name</label>
                        <div class="fw-bold text-slate-800" id="fdSenderName">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Business Email</label>
                        <div id="fdSenderEmail">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Phone / WhatsApp</label>
                        <div id="fdSenderPhone">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Company / Organization</label>
                        <div id="fdCompanyName">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Country</label>
                        <div id="fdCountryName">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Feedback Type</label>
                        <div><span class="badge bg-blue-50 text-primary border border-blue-200" id="fdFeedbackType">-</span></div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <label class="text-xs text-muted fw-bold uppercase">Subject</label>
                    <div class="fw-bold text-slate-900 mb-2" id="fdSubject">-</div>
                    <label class="text-xs text-muted fw-bold uppercase">Detailed Suggestion / Feedback</label>
                    <div class="p-3 bg-slate-50 rounded-3 border text-slate-700 text-sm whitespace-pre-wrap" id="fdMessage">-</div>
                </div>

                <div id="fdAttachmentSection" class="border-top pt-3 hidden">
                    <label class="text-xs text-muted fw-bold uppercase">Uploaded Attachment</label>
                    <div class="mt-1" id="fdAttachmentLink"></div>
                </div>

                <!-- Internal Admin Notes & Status -->
                <div class="border-top pt-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-slate-700">Update Review Status</label>
                            <select id="fdStatusSelect" class="form-select form-select-sm">
                                <option value="NEW">New</option>
                                <option value="UNDER_REVIEW">Under Review</option>
                                <option value="REVIEWED">Reviewed</option>
                                <option value="CLOSED">Closed</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-slate-700">Internal Super Admin Review Notes</label>
                            <textarea id="fdAdminNotes" rows="3" class="form-control form-control-sm" placeholder="Notes on follow-up, product roadmap link, or team assignees..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="saveFeedbackModalChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
let table;
let currentFeedbackId = 0;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function renderStatusBadge(st) {
    const map = {
        'NEW':          { bg: '#fef3c7', color: '#b45309', label: 'New' },
        'UNDER_REVIEW': { bg: '#e0f2fe', color: '#0369a1', label: 'Under Review' },
        'REVIEWED':     { bg: '#d1fae5', color: '#065f46', label: 'Reviewed' },
        'CLOSED':       { bg: '#f1f5f9', color: '#475569', label: 'Closed' },
    };
    const cfg = map[st] || { bg: '#f1f5f9', color: '#475569', label: st || 'New' };
    return `<span class="badge rounded-pill" style="background:${cfg.bg};color:${cfg.color};">${escapeHtml(cfg.label)}</span>`;
}

function setStatusFilter(st) {
    $('#filterStatus').val(st).trigger('change');
}

$(document).ready(function() {
    table = $('#feedbacksTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 15,
        order: [[7, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/feedbacks/data',
            data: function(d) {
                d.feedback_type = $('#filterType').val();
                d.status = $('#filterStatus').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            {
                data: 'reference_id',
                render: (ref, type, row) => `
                    <a href="javascript:void(0)" onclick="openFeedbackDetail(${row.id})" class="fw-bold text-primary text-decoration-none">
                        ${escapeHtml(ref || ('SFB-' + String(row.id).padStart(5, '0')))}
                    </a>
                `
            },
            {
                data: 'name',
                render: (name, type, row) => `
                    <div class="fw-bold text-slate-800">${escapeHtml(name)}</div>
                    <div class="smaller text-muted">${escapeHtml(row.email || '')}</div>
                    ${row.phone ? `<div class="smaller text-slate-600"><i class="bi bi-telephone me-1"></i>${escapeHtml(row.phone)}</div>` : ''}
                `
            },
            {
                data: 'company_name',
                render: val => val ? `<span class="fw-semibold text-slate-700">${escapeHtml(val)}</span>` : '<span class="text-muted smaller">N/A</span>'
            },
            {
                data: 'country_name',
                render: val => val ? escapeHtml(val) : '<span class="text-muted smaller">India</span>'
            },
            {
                data: 'feedback_type',
                render: val => `<span class="badge bg-light text-dark border">${escapeHtml(val || 'General')}</span>`
            },
            {
                data: 'subject',
                render: (subj, type, row) => {
                    const title = subj || 'Suggestion';
                    const msg = row.message ? `<div class="smaller text-muted text-truncate" style="max-width:200px;" title="${escapeHtml(row.message)}">${escapeHtml(row.message)}</div>` : '';
                    return `<div class="fw-semibold text-slate-800 text-truncate" style="max-width:200px;" title="${escapeHtml(title)}">${escapeHtml(title)}</div>${msg}`;
                }
            },
            {
                data: 'attachment_path',
                render: (path, type, row) => {
                    if (!path) return '<span class="text-muted smaller">None</span>';
                    return `
                        <a href="${BASE_URL}/admin/feedbacks/attachment/${row.id}" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2 rounded-2 text-decoration-none" title="Download Attachment">
                            <i class="bi bi-paperclip text-primary"></i> <span class="smaller">View</span>
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
                        <button type="button" class="btn btn-outline-primary" title="View Complete Feedback" onclick="openFeedbackDetail(${row.id})">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" title="Mark Reviewed" onclick="quickStatus(${row.id}, 'REVIEWED')">
                            <i class="bi bi-check2"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" title="Mark Closed" onclick="quickStatus(${row.id}, 'CLOSED')">
                            <i class="bi bi-archive"></i>
                        </button>
                    </div>
                `
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search by ID, name, email, company, subject..."
        }
    });

    $('#filterType, #filterStatus, #filterStartDate, #filterEndDate').on('change', function() {
        table.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#filterType').val('ALL');
        $('#filterStatus').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        table.search('').draw();
    });
});

function openFeedbackDetail(id) {
    currentFeedbackId = id;
    $.get(`${BASE_URL}/api/admin/feedbacks/${id}`, function(res) {
        if (!res.success || !res.feedback) {
            Swal.fire('Error', res.message || 'Could not load feedback.', 'error');
            return;
        }
        const fb = res.feedback;
        const ref = fb.reference_id || ('SFB-' + String(fb.id).padStart(5, '0'));

        $('#fdModalRef').text('Reference ID: ' + ref);
        $('#fdSenderName').text(fb.name || '-');
        $('#fdSenderEmail').html(`<a href="mailto:${escapeHtml(fb.email)}">${escapeHtml(fb.email)}</a>`);
        $('#fdSenderPhone').text(fb.phone || 'N/A');
        $('#fdCompanyName').text(fb.company_name || 'N/A');
        $('#fdCountryName').text(fb.country_name || 'India');
        $('#fdFeedbackType').text(fb.feedback_type || 'General Feedback');
        $('#fdSubject').text(fb.subject || '-');
        $('#fdMessage').text(fb.message || '-');
        $('#fdStatusSelect').val(fb.status || 'NEW');
        $('#fdAdminNotes').val(fb.admin_notes || '');

        if (fb.attachment_path) {
            $('#fdAttachmentSection').removeClass('hidden');
            $('#fdAttachmentLink').html(`
                <a href="${BASE_URL}/admin/feedbacks/attachment/${fb.id}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-paperclip me-1"></i> Download Attachment (${escapeHtml(fb.attachment_name || 'File')})
                </a>
            `);
        } else {
            $('#fdAttachmentSection').addClass('hidden');
        }

        new bootstrap.Modal(document.getElementById('feedbackDetailModal')).show();
    }, 'json');
}

function saveFeedbackModalChanges() {
    if (!currentFeedbackId) return;
    const newStatus = $('#fdStatusSelect').val();
    const notes = $('#fdAdminNotes').val();

    // 1. Update status
    $.post(`${BASE_URL}/api/admin/feedbacks/${currentFeedbackId}/status`, {
        csrf_token: CSRF_TOKEN,
        status: newStatus
    }, function(res) {
        if (res.success && res.metrics) {
            $('#mTotal').text(res.metrics.total ?? 0);
            $('#mNew').text(res.metrics.new ?? 0);
            $('#mUnderReview').text(res.metrics.under_review ?? 0);
            $('#mReviewed').text(res.metrics.reviewed ?? 0);
            $('#mClosed').text(res.metrics.closed ?? 0);
        }
    }, 'json');

    // 2. Update notes
    $.post(`${BASE_URL}/api/admin/feedbacks/${currentFeedbackId}/notes`, {
        csrf_token: CSRF_TOKEN,
        admin_notes: notes
    }, function(res) {
        bootstrap.Modal.getInstance(document.getElementById('feedbackDetailModal')).hide();
        Swal.fire({ icon: 'success', title: 'Saved', text: 'Feedback updates saved.', timer: 1800, showConfirmButton: false });
        table.ajax.reload(null, false);
    }, 'json');
}

function quickStatus(id, status) {
    $.post(`${BASE_URL}/api/admin/feedbacks/${id}/status`, {
        csrf_token: CSRF_TOKEN,
        status: status
    }, function(res) {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 1800, showConfirmButton: false });
            table.ajax.reload(null, false);
            if (res.metrics) {
                $('#mTotal').text(res.metrics.total ?? 0);
                $('#mNew').text(res.metrics.new ?? 0);
                $('#mUnderReview').text(res.metrics.under_review ?? 0);
                $('#mReviewed').text(res.metrics.reviewed ?? 0);
                $('#mClosed').text(res.metrics.closed ?? 0);
            }
        } else {
            Swal.fire('Error', res.message || 'Could not update status.', 'error');
        }
    }, 'json');
}

function exportFeedback(format) {
    const params = new URLSearchParams({
        format: format,
        feedback_type: $('#filterType').val(),
        status: $('#filterStatus').val(),
        start_date: $('#filterStartDate').val(),
        end_date: $('#filterEndDate').val(),
        search: table.search()
    });
    window.location.href = `${BASE_URL}/api/admin/feedbacks/export?${params.toString()}`;
}
</script>
<?php
$adminScripts = ob_get_clean();
?>
