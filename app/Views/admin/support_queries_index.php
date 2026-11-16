<?php
/**
 * Shared Admin Support Queries View Partial
 * Used by Buyer Queries and Seller Queries
 */
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$isSeller = ($sourceType === 'SELLER');
$title = $isSeller ? 'Seller Support Queries' : 'Buyer Support Queries';
$badgeText = $isSeller ? 'SELLER HELP CENTRE' : 'BUYER HELP CENTRE';
$badgeClass = $isSeller ? 'text-purple-700 bg-purple-50 border-purple-200' : 'text-blue-700 bg-blue-50 border-blue-200';
$dataUrl = $baseUrl . ($isSeller ? '/api/admin/seller-queries/data' : '/api/admin/buyer-queries/data');
$exportUrl = $baseUrl . ($isSeller ? '/api/admin/seller-queries/export' : '/api/admin/buyer-queries/export');
?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge px-2.5 py-1 rounded-2 fw-bold" style="font-size: 11px; <?= $isSeller ? 'color: #6b21a8; background: #faf5ff; border: 1px solid #e9d5ff;' : 'color: #1e40af; background: #eff6ff; border: 1px solid #bfdbfe;' ?>">
                <i class="bi <?= $isSeller ? 'bi-shop-window' : 'bi-person-badge' ?> me-1"></i><?= $badgeText ?>
            </span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0"><?= htmlspecialchars($title) ?></h3>
        <p class="text-slate-500 small mb-0">Manage, assign, and resolve support requests submitted through the <?= $isSeller ? 'Seller' : 'Buyer' ?> Help Centre.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Queries
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportQueries('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportQueries('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- KPI Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px;">Total Queries</div>
            <div class="h4 fw-black text-slate-900 mb-0 mt-1" id="kpiTotal"><?= number_format($metrics['total'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-warning cursor-pointer" onclick="filterByStatus('NEW')">
            <div class="text-warning small fw-bold text-uppercase" style="font-size: 10px;">New / Open</div>
            <div class="h4 fw-black text-warning mb-0 mt-1" id="kpiNew"><?= number_format($metrics['new'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-info cursor-pointer" onclick="filterByStatus('IN_PROGRESS')">
            <div class="text-info small fw-bold text-uppercase" style="font-size: 10px;">In Progress</div>
            <div class="h4 fw-black text-info mb-0 mt-1" id="kpiInProgress"><?= number_format($metrics['in_progress'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-secondary cursor-pointer" onclick="filterByStatus('AWAITING_CUSTOMER_RESPONSE')">
            <div class="text-secondary small fw-bold text-uppercase" style="font-size: 10px;">Awaiting Response</div>
            <div class="h4 fw-black text-secondary mb-0 mt-1" id="kpiAwaiting"><?= number_format($metrics['awaiting'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-success cursor-pointer" onclick="filterByStatus('RESOLVED')">
            <div class="text-success small fw-bold text-uppercase" style="font-size: 10px;">Resolved</div>
            <div class="h4 fw-black text-success mb-0 mt-1" id="kpiResolved"><?= number_format($metrics['resolved'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-dark cursor-pointer" onclick="filterByStatus('CLOSED')">
            <div class="text-dark small fw-bold text-uppercase" style="font-size: 10px;">Closed</div>
            <div class="h4 fw-black text-dark mb-0 mt-1" id="kpiClosed"><?= number_format($metrics['closed'] ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <!-- Status Filter -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <?php foreach ($statuses as $stKey => $stLabel): ?>
                        <option value="<?= htmlspecialchars($stKey) ?>"><?= htmlspecialchars($stLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Query Category</label>
                <select id="filterCategory" class="form-select form-select-sm">
                    <option value="ALL">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Assigned To Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Assigned To</label>
                <select id="filterAssignedTo" class="form-select form-select-sm">
                    <option value="ALL">All Staff</option>
                    <option value="UNASSIGNED">Unassigned</option>
                    <?php foreach ($staff as $stf): ?>
                        <option value="<?= (int)$stf['id'] ?>"><?= htmlspecialchars($stf['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Date Range</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="filterStartDate" class="form-control" title="Start Date">
                    <span class="input-group-text bg-white text-muted">to</span>
                    <input type="date" id="filterEndDate" class="form-control" title="End Date">
                </div>
            </div>

            <!-- Reset Filter Button -->
            <div class="col-12 col-md-1">
                <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm w-100 fw-semibold" title="Reset all filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Data Table Card -->
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="supportQueriesTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 140px;">Query ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Order ID</th>
                        <th>Existing Ticket</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Submission Date</th>
                        <th class="text-end" style="min-width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Complete Query Detail & Action Modal -->
<div class="modal fade" id="queryDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-slate-900 text-white px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10 h-10 rounded-3 bg-white bg-opacity-10 d-flex align-items-center justify-center text-white">
                        <i class="bi bi-headset fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold text-white mb-0" id="modalTicketId">Ticket Details</h5>
                            <span class="badge rounded-pill" id="modalStatusBadge"></span>
                            <span class="badge bg-light text-dark text-uppercase small" id="modalSourceBadge"></span>
                        </div>
                        <div class="text-slate-400 small" id="modalSubject"></div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-slate-50">
                <div class="row g-4">
                    <!-- Left Column: Requester, Issue, Attachment & Activities -->
                    <div class="col-lg-7 space-y-4">
                        
                        <!-- Requester Details Card -->
                        <div class="card border-0 shadow-xs rounded-3 bg-white p-3.5 mb-3">
                            <h6 class="fw-bold text-slate-800 mb-3 d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-person-circle text-primary"></i> Requester Information
                            </h6>
                            <div class="row g-2 text-xs">
                                <div class="col-sm-6">
                                    <div class="text-muted small">Full Name</div>
                                    <div class="fw-bold text-slate-900" id="modalName">—</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Contact Number</div>
                                    <div class="fw-bold text-slate-900" id="modalPhone">—</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Email Address</div>
                                    <div class="fw-bold text-slate-900" id="modalEmail">—</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Submission Timestamp</div>
                                    <div class="fw-bold text-slate-900" id="modalCreatedAt">—</div>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Details Card -->
                        <div class="card border-0 shadow-xs rounded-3 bg-white p-3.5 mb-3">
                            <h6 class="fw-bold text-slate-800 mb-3 d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-file-earmark-text text-primary"></i> Query Details &amp; References
                            </h6>
                            <div class="row g-2 text-xs mb-3">
                                <div class="col-sm-4">
                                    <div class="text-muted small">Category</div>
                                    <div class="fw-bold text-slate-900" id="modalCategory">—</div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="text-muted small">Order Reference</div>
                                    <div class="fw-bold text-slate-900" id="modalOrderId">—</div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="text-muted small">Existing Ticket Ref</div>
                                    <div class="fw-bold text-slate-900" id="modalExistingTicketId">—</div>
                                </div>
                            </div>
                            
                            <div class="border-top pt-3">
                                <div class="text-muted small mb-1 fw-bold">Detailed Description of Issue:</div>
                                <div class="p-3 rounded-3 bg-slate-50 border border-slate-200 text-slate-800 small leading-relaxed" id="modalDescription" style="white-space: pre-wrap;"></div>
                            </div>

                            <!-- Attachment Section -->
                            <div id="modalAttachmentSection" class="mt-3 pt-3 border-top d-none">
                                <div class="text-muted small mb-1 fw-bold">Uploaded Attachment:</div>
                                <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 bg-blue-50 border border-blue-200">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <i class="bi bi-paperclip fs-5 text-primary"></i>
                                        <div class="text-truncate">
                                            <div class="fw-bold text-slate-900 small text-truncate" id="modalAttachmentName">file</div>
                                            <div class="text-slate-500" style="font-size: 10px;" id="modalAttachmentSize"></div>
                                        </div>
                                    </div>
                                    <a href="#" id="modalAttachmentDownloadBtn" class="btn btn-sm btn-primary shrink-0 fw-bold px-3">
                                        <i class="bi bi-download me-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Complete Activity / History Log -->
                        <div class="card border-0 shadow-xs rounded-3 bg-white p-3.5">
                            <h6 class="fw-bold text-slate-800 mb-3 d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-clock-history text-primary"></i> Activity &amp; Audit History Log
                            </h6>
                            <div id="modalActivityTimeline" class="space-y-3" style="max-height: 260px; overflow-y: auto;">
                                <!-- Rendered dynamically -->
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Admin Actions & Notes -->
                    <div class="col-lg-5 space-y-4">

                        <!-- Update Status Form -->
                        <div class="card border-0 shadow-xs rounded-3 bg-white p-3.5 mb-3">
                            <h6 class="fw-bold text-slate-800 mb-2 d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-arrow-repeat text-primary"></i> Update Ticket Status
                            </h6>
                            <form id="formUpdateStatus" class="space-y-2">
                                <input type="hidden" id="actionQueryId" value="">
                                <div>
                                    <label class="form-label small fw-bold text-slate-600 mb-1">Select New Status</label>
                                    <select id="modalUpdateStatusSelect" class="form-select form-select-sm" required>
                                        <?php foreach ($statuses as $stKey => $stLabel): ?>
                                            <option value="<?= htmlspecialchars($stKey) ?>"><?= htmlspecialchars($stLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label small fw-bold text-slate-600 mb-1">Status Remarks / Notes</label>
                                    <textarea id="modalStatusRemarks" class="form-control form-control-sm" rows="2" placeholder="Optional remark explaining this status change..."></textarea>
                                </div>
                                <button type="submit" id="btnSubmitStatus" class="btn btn-sm btn-primary w-100 fw-bold mt-2">
                                    <i class="bi bi-check2-circle me-1"></i> Update Status
                                </button>
                            </form>
                        </div>

                        <!-- Assign Query Form -->
                        <div class="card border-0 shadow-xs rounded-3 bg-white p-3.5 mb-3">
                            <h6 class="fw-bold text-slate-800 mb-2 d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-person-plus text-primary"></i> Assign to Team Member
                            </h6>
                            <form id="formAssignQuery">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-slate-600 mb-1">Assignee</label>
                                    <select id="modalAssigneeSelect" class="form-select form-select-sm">
                                        <option value="">— Unassigned —</option>
                                        <?php foreach ($staff as $stf): ?>
                                            <option value="<?= (int)$stf['id'] ?>"><?= htmlspecialchars($stf['name']) ?> (<?= htmlspecialchars($stf['email']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" id="btnSubmitAssign" class="btn btn-sm btn-outline-primary w-100 fw-bold">
                                    <i class="bi bi-person-check me-1"></i> Save Assignment
                                </button>
                            </form>
                        </div>

                        <!-- Internal Notes & Remarks -->
                        <div class="card border-0 shadow-xs rounded-3 bg-white p-3.5">
                            <h6 class="fw-bold text-slate-800 mb-2 d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-journal-text text-primary"></i> Internal Notes &amp; Remarks
                            </h6>
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-slate-600 mb-1">Existing Notes</label>
                                <div id="modalInternalNotesDisplay" class="p-2.5 rounded-3 bg-slate-50 border text-slate-700 small leading-relaxed" style="max-height: 140px; overflow-y: auto; white-space: pre-wrap; font-size: 11px;">
                                    No internal notes yet.
                                </div>
                            </div>
                            <form id="formAddNote">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-slate-600 mb-1">Add New Note</label>
                                    <textarea id="modalNewNoteText" class="form-control form-control-sm" rows="2" placeholder="Write internal remarks or discussion notes..."></textarea>
                                </div>
                                <button type="submit" id="btnSubmitNote" class="btn btn-sm btn-outline-secondary w-100 fw-bold">
                                    <i class="bi bi-plus-circle me-1"></i> Append Note
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white px-4 py-2.5">
                <button type="button" class="btn btn-secondary btn-sm fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
var BASE_URL = <?= json_encode($baseUrl) ?>;
var CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
const DATA_URL = <?= json_encode($dataUrl) ?>;
const EXPORT_URL = <?= json_encode($exportUrl) ?>;
const SOURCE_TYPE = <?= json_encode($sourceType) ?>;
let table;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function renderStatusBadge(st) {
    const map = {
        'NEW':                        { bg: '#fef3c7', color: '#b45309', label: 'New' },
        'IN_PROGRESS':                { bg: '#e0e7ff', color: '#4338ca', label: 'In Progress' },
        'AWAITING_CUSTOMER_RESPONSE': { bg: '#f1f5f9', color: '#475569', label: 'Awaiting Response' },
        'RESOLVED':                   { bg: '#d1fae5', color: '#065f46', label: 'Resolved' },
        'CLOSED':                     { bg: '#e2e8f0', color: '#334155', label: 'Closed' },
    };
    const cfg = map[st] || { bg: '#f1f5f9', color: '#475569', label: st || 'New' };
    return `<span class="badge rounded-pill fw-bold" style="background:${cfg.bg};color:${cfg.color};">${escapeHtml(cfg.label)}</span>`;
}

function filterByStatus(st) {
    $('#filterStatus').val(st).trigger('change');
}

function exportQueries(format) {
    const params = new URLSearchParams({
        format: format,
        status: $('#filterStatus').val(),
        category: $('#filterCategory').val(),
        assigned_to: $('#filterAssignedTo').val(),
        start_date: $('#filterStartDate').val(),
        end_date: $('#filterEndDate').val(),
        search: table ? table.search() : ''
    });
    window.location.href = EXPORT_URL + '?' + params.toString();
}

function openQueryDetail(id) {
    $('#actionQueryId').val(id);
    $.ajax({
        url: BASE_URL + '/api/admin/support-queries/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (!res || !res.success) {
                alert(res.message || 'Failed to fetch query details.');
                return;
            }
            const q = res.query;
            $('#modalTicketId').text('#' + q.ticket_id);
            $('#modalSubject').text(q.subject);
            $('#modalStatusBadge').html(renderStatusBadge(q.status));
            $('#modalSourceBadge').text(q.source_type + ' QUERY');

            $('#modalName').text(q.name);
            $('#modalPhone').html(`<a href="tel:${escapeHtml(q.phone)}" class="text-decoration-none text-primary"><i class="bi bi-telephone me-1"></i>${escapeHtml(q.phone)}</a>`);
            $('#modalEmail').html(`<a href="mailto:${escapeHtml(q.email)}" class="text-decoration-none text-primary"><i class="bi bi-envelope me-1"></i>${escapeHtml(q.email)}</a>`);
            $('#modalCreatedAt').text(q.created_at);

            $('#modalCategory').text(q.category);
            
            // Order ID link
            if (q.order_id) {
                $('#modalOrderId').html(`
                    <span class="badge bg-blue-50 text-primary border border-blue-200">
                        <i class="bi bi-box-seam me-1"></i>${escapeHtml(q.order_id)}
                    </span>
                    <a href="${BASE_URL}/admin/enquiries?search=${encodeURIComponent(q.order_id)}" target="_blank" class="ms-1 small text-primary text-decoration-none" title="Search related order/enquiry">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                `);
            } else {
                $('#modalOrderId').text('—');
            }

            // Existing Ticket ID link
            if (q.existing_ticket_id) {
                if (res.linked_ticket) {
                    $('#modalExistingTicketId').html(`
                        <button type="button" onclick="openQueryDetail(${res.linked_ticket.id})" class="btn btn-xs btn-outline-primary py-0 px-2 fw-bold" title="Open linked ticket">
                            <i class="bi bi-link-45deg me-1"></i>${escapeHtml(q.existing_ticket_id)}
                        </button>
                    `);
                } else {
                    $('#modalExistingTicketId').html(`
                        <span class="badge bg-slate-100 text-slate-700 font-monospace">
                            ${escapeHtml(q.existing_ticket_id)}
                        </span>
                    `);
                }
            } else {
                $('#modalExistingTicketId').text('—');
            }

            $('#modalDescription').text(q.description);

            // Attachment
            if (q.attachment_path) {
                $('#modalAttachmentSection').removeClass('d-none');
                $('#modalAttachmentName').text(q.attachment_name || 'Download Attachment');
                const sizeKb = q.attachment_size ? (q.attachment_size / 1024).toFixed(1) + ' KB' : '';
                $('#modalAttachmentSize').text(sizeKb);
                $('#modalAttachmentDownloadBtn').attr('href', BASE_URL + '/admin/support-queries/' + q.id + '/attachment');
            } else {
                $('#modalAttachmentSection').addClass('d-none');
            }

            // Form Pre-fills
            $('#modalUpdateStatusSelect').val(q.status);
            $('#modalStatusRemarks').val('');
            $('#modalAssigneeSelect').val(q.assigned_to || '');
            $('#modalInternalNotesDisplay').text(q.internal_notes || 'No internal notes recorded yet.');
            $('#modalNewNoteText').val('');

            // Activities Log
            renderActivityTimeline(res.activities || []);

            const modal = new bootstrap.Modal(document.getElementById('queryDetailModal'));
            modal.show();
        },
        error: function() {
            alert('A network or server error occurred while retrieving query details.');
        }
    });
}

function renderActivityTimeline(activities) {
    const container = $('#modalActivityTimeline');
    container.empty();

    if (!activities || activities.length === 0) {
        container.html('<div class="text-muted small text-center py-2">No activity recorded yet.</div>');
        return;
    }

    const typeIcons = {
        'CREATED':       'bi-plus-circle text-success',
        'STATUS_CHANGE': 'bi-arrow-repeat text-primary',
        'ASSIGNED':      'bi-person-check text-info',
        'NOTE_ADDED':    'bi-journal-text text-secondary',
        'LINKED':        'bi-link-45deg text-purple',
    };

    activities.forEach(act => {
        const icon = typeIcons[act.action_type] || 'bi-circle text-slate-400';
        const actor = act.actor_name ? `<strong>${escapeHtml(act.actor_name)}</strong>` : 'System / User';
        const item = `
            <div class="d-flex gap-2.5 pb-2.5 border-bottom border-slate-100">
                <i class="bi ${icon} fs-6 shrink-0 mt-0.5"></i>
                <div class="text-xs w-100">
                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                        <span class="text-slate-900">${actor}</span>
                        <span class="text-slate-400" style="font-size: 10px;">${escapeHtml(act.created_at)}</span>
                    </div>
                    <div class="text-slate-600">${escapeHtml(act.description)}</div>
                </div>
            </div>
        `;
        container.append(item);
    });
}

$(document).ready(function() {
    table = $('#supportQueriesTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 15,
        order: [[10, 'desc']],
        ajax: {
            url: DATA_URL,
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.category = $('#filterCategory').val();
                d.assigned_to = $('#filterAssignedTo').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            // 0: Ticket ID
            {
                data: 'ticket_id',
                render: function(data, type, row) {
                    return `
                        <a href="javascript:void(0)" onclick="openQueryDetail(${row.id})" class="fw-bold text-primary text-decoration-none font-monospace">
                            ${escapeHtml(data)}
                        </a>
                    `;
                }
            },
            // 1: Name
            {
                data: 'name',
                render: function(data) {
                    return `<span class="fw-bold text-slate-900">${escapeHtml(data)}</span>`;
                }
            },
            // 2: Email
            {
                data: 'email',
                render: function(data) {
                    return `<a href="mailto:${escapeHtml(data)}" class="text-slate-600 text-decoration-none small text-truncate d-inline-block" style="max-width: 150px;">${escapeHtml(data)}</a>`;
                }
            },
            // 3: Phone
            {
                data: 'phone',
                render: function(data) {
                    return `<a href="tel:${escapeHtml(data)}" class="text-slate-600 text-decoration-none small">${escapeHtml(data)}</a>`;
                }
            },
            // 4: Category
            {
                data: 'category',
                render: function(data) {
                    return `<span class="badge bg-slate-100 text-slate-700 border border-slate-200 small">${escapeHtml(data)}</span>`;
                }
            },
            // 5: Subject
            {
                data: 'subject',
                render: function(data) {
                    return `<span class="fw-semibold text-slate-800 text-truncate d-inline-block" style="max-width: 180px;" title="${escapeHtml(data)}">${escapeHtml(data)}</span>`;
                }
            },
            // 6: Order ID
            {
                data: 'order_id',
                render: function(data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return `
                        <span class="badge bg-blue-50 text-primary border border-blue-100 font-monospace">
                            ${escapeHtml(data)}
                        </span>
                    `;
                }
            },
            // 7: Existing Ticket ID
            {
                data: 'existing_ticket_id',
                render: function(data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return `<span class="badge bg-warning bg-opacity-10 text-dark border border-warning font-monospace" style="font-size:10px;">${escapeHtml(data)}</span>`;
                }
            },
            // 8: Assigned To
            {
                data: 'assigned_user_name',
                render: function(data) {
                    if (!data) return '<span class="badge bg-secondary bg-opacity-10 text-secondary">Unassigned</span>';
                    return `<span class="badge bg-info bg-opacity-10 text-info fw-bold"><i class="bi bi-person me-1"></i>${escapeHtml(data)}</span>`;
                }
            },
            // 9: Status
            {
                data: 'status',
                render: function(data) {
                    return renderStatusBadge(data);
                }
            },
            // 10: Submission Date
            {
                data: 'created_at',
                render: function(data) {
                    return `<span class="text-slate-500 small">${escapeHtml(data)}</span>`;
                }
            },
            // 11: Action
            {
                data: 'id',
                orderable: false,
                className: 'text-end',
                render: function(data) {
                    return `
                        <button type="button" onclick="openQueryDetail(${data})" class="btn btn-sm btn-outline-primary px-2.5 py-1 fw-bold rounded-2">
                            <i class="bi bi-eye me-1"></i> View / Edit
                        </button>
                    `;
                }
            }
        ],
        language: {
            emptyTable: 'No support queries found matching the selected filters.',
            processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading queries...'
        }
    });

    // Filter Change Listeners
    $('#filterStatus, #filterCategory, #filterAssignedTo, #filterStartDate, #filterEndDate').on('change', function() {
        table.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#filterStatus').val('ALL');
        $('#filterCategory').val('ALL');
        $('#filterAssignedTo').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        if (table) {
            table.search('').ajax.reload();
        }
    });

    // Handle Status Update Submit
    $('#formUpdateStatus').on('submit', function(e) {
        e.preventDefault();
        const qid = $('#actionQueryId').val();
        const status = $('#modalUpdateStatusSelect').val();
        const notes = $('#modalStatusRemarks').val();
        const btn = $('#btnSubmitStatus');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        $.ajax({
            url: BASE_URL + '/api/admin/support-queries/' + qid + '/status',
            type: 'POST',
            data: { status: status, notes: notes, csrf_token: CSRF_TOKEN },
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    $('#modalStatusBadge').html(renderStatusBadge(res.status));
                    if (res.internal_notes) {
                        $('#modalInternalNotesDisplay').text(res.internal_notes);
                    }
                    if (res.activities) {
                        renderActivityTimeline(res.activities);
                    }
                    if (res.metrics) {
                        $('#kpiTotal').text(res.metrics.total);
                        $('#kpiNew').text(res.metrics.new);
                        $('#kpiInProgress').text(res.metrics.in_progress);
                        $('#kpiAwaiting').text(res.metrics.awaiting);
                        $('#kpiResolved').text(res.metrics.resolved);
                        $('#kpiClosed').text(res.metrics.closed);
                    }
                    $('#modalStatusRemarks').val('');
                    if (table) table.ajax.reload(null, false);
                    alert('Query status updated successfully.');
                } else {
                    alert(res.message || 'Failed to update status.');
                }
            },
            error: function() {
                alert('Server error occurred while updating query status.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Update Status');
            }
        });
    });

    // Handle Assign Submit
    $('#formAssignQuery').on('submit', function(e) {
        e.preventDefault();
        const qid = $('#actionQueryId').val();
        const assignedTo = $('#modalAssigneeSelect').val();
        const btn = $('#btnSubmitAssign');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: BASE_URL + '/api/admin/support-queries/' + qid + '/assign',
            type: 'POST',
            data: { assigned_to: assignedTo, csrf_token: CSRF_TOKEN },
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    if (res.activities) {
                        renderActivityTimeline(res.activities);
                    }
                    if (table) table.ajax.reload(null, false);
                    alert(res.message || 'Assignment updated successfully.');
                } else {
                    alert(res.message || 'Failed to update assignment.');
                }
            },
            error: function() {
                alert('Server error occurred while updating assignment.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-person-check me-1"></i> Save Assignment');
            }
        });
    });

    // Handle Add Note Submit
    $('#formAddNote').on('submit', function(e) {
        e.preventDefault();
        const qid = $('#actionQueryId').val();
        const note = $('#modalNewNoteText').val().trim();
        const btn = $('#btnSubmitNote');

        if (!note) {
            alert('Please enter a note before submitting.');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: BASE_URL + '/api/admin/support-queries/' + qid + '/notes',
            type: 'POST',
            data: { note: note, csrf_token: CSRF_TOKEN },
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    $('#modalInternalNotesDisplay').text(res.internal_notes);
                    $('#modalNewNoteText').val('');
                    if (res.activities) {
                        renderActivityTimeline(res.activities);
                    }
                    alert('Internal note added successfully.');
                } else {
                    alert(res.message || 'Failed to add note.');
                }
            },
            error: function() {
                alert('Server error occurred while adding internal note.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-plus-circle me-1"></i> Append Note');
            }
        });
    });
});
</script>
