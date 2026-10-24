<?php
$baseUrl = \App\Core\Response::baseUrl();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-2 fw-bold" style="font-size: 11px;">
                <i class="bi bi-shield-lock-fill me-1"></i>SECURITY &amp; AUDIT
            </span>
            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-2 small fw-semibold">
                <span id="totalLogsBadge"><?= number_format((int)($totalLogs ?? 0)) ?></span> Retained Events
            </span>
        </div>
        <h3 class="fw-bold text-dark mb-0">System Audit Trail</h3>
        <p class="text-muted small mb-0">Immutable, comprehensive log of all administrative actions, authentication attempts, data mutations, and security events across Sports Junction.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3" onclick="exportAuditTrail()">
            <i class="bi bi-filetype-csv text-success me-1"></i> Export CSV
        </button>
        <button type="button" class="btn btn-primary btn-sm fw-semibold px-3 py-2 rounded-3" onclick="reloadAuditTable()">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Logs
        </button>
    </div>
</div>

<!-- API Error Alert (Hidden by default) -->
<div class="alert alert-danger shadow-sm border-0 d-none align-items-center mb-4" id="auditTableError">
    <i class="bi bi-exclamation-triangle-fill fs-5 me-3 flex-shrink-0"></i>
    <div class="flex-grow-1">
        <strong>Error loading audit log data:</strong>
        <span id="auditTableErrorMessage">The server encountered an unexpected error while retrieving audit logs.</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger ms-3" onclick="reloadAuditTable()">Retry</button>
</div>

<!-- Main Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="auditTrailTable" style="width: 100%; font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 140px;">Timestamp</th>
                        <th style="min-width: 160px;">Actor / User</th>
                        <th style="min-width: 90px;">Role</th>
                        <th style="min-width: 110px;">Action</th>
                        <th style="min-width: 110px;">Entity</th>
                        <th style="min-width: 160px;">Target Record</th>
                        <th style="min-width: 220px;">Details &amp; Payload</th>
                        <th style="min-width: 110px;">IP Address</th>
                        <th style="min-width: 90px;">Result</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Server-side populated -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Audit Log Event Details Modal -->
<div class="modal fade" id="auditDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold text-dark fs-6">
                    <i class="bi bi-journal-check text-primary me-2"></i>Audit Log Record Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="auditDetailModalBody">
                <!-- Dynamically populated -->
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
let auditTable;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function getActionBadge(action) {
    const act = (action || '').toUpperCase();
    let badgeClass = 'bg-secondary';
    if (act.includes('CREATE')) badgeClass = 'bg-success';
    else if (act.includes('UPDATE') || act.includes('STATUS')) badgeClass = 'bg-info text-dark';
    else if (act.includes('DELETE') || act.includes('REJECT') || act.includes('SUSPEND')) badgeClass = 'bg-danger';
    else if (act.includes('APPROVE') || act.includes('REACTIVATE')) badgeClass = 'bg-primary';
    else if (act.includes('LOGIN')) badgeClass = 'bg-dark';
    else if (act.includes('LOGOUT')) badgeClass = 'bg-secondary';
    else if (act.includes('EXPORT') || act.includes('IMPORT')) badgeClass = 'bg-warning text-dark';
    
    return `<span class="badge ${badgeClass} fw-semibold">${escapeHtml(act)}</span>`;
}

function getRoleBadge(role) {
    const r = (role || 'SYSTEM').toUpperCase();
    if (r === 'SUPER_ADMIN') {
        return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-bold" style="font-size: 11px;">SUPER ADMIN</span>';
    } else if (r === 'ADMIN') {
        return '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold" style="font-size: 11px;">ADMIN</span>';
    } else if (r === 'SUPPLIER') {
        return '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold" style="font-size: 11px;">SUPPLIER</span>';
    } else if (r === 'BUYER') {
        return '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-bold" style="font-size: 11px;">BUYER</span>';
    }
    return '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fw-bold" style="font-size: 11px;">SYSTEM</span>';
}

function getResultBadge(result) {
    const res = (result || 'SUCCESS').toUpperCase();
    if (res === 'SUCCESS') {
        return '<span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>SUCCESS</span>';
    }
    return '<span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>FAILURE</span>';
}

function viewAuditDetail(rowJson) {
    try {
        const row = typeof rowJson === 'string' ? JSON.parse(decodeURIComponent(rowJson)) : rowJson;
        let html = `
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Log Record ID</label>
                    <div class="fw-bold">#${row.id}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Timestamp</label>
                    <div>${escapeHtml(row.created_at)}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Actor</label>
                    <div><strong>${escapeHtml(row.user_name)}</strong> ${row.user_id ? '<span class="text-muted">(#' + row.user_id + ')</span>' : ''}</div>
                    <small class="text-muted">${escapeHtml(row.user_email || 'No email')}</small>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Role &amp; Status</label>
                    <div class="d-flex gap-2 align-items-center mt-1">
                        ${getRoleBadge(row.user_role)}
                        ${getResultBadge(row.result)}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Action Performed</label>
                    <div class="mt-1">${getActionBadge(row.action)}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Target Entity</label>
                    <div class="fw-semibold text-primary">${escapeHtml(row.entity_type)} ${row.entity_id ? '#' + row.entity_id : ''}</div>
                </div>
                <div class="col-12">
                    <label class="text-muted small fw-bold">Target Record Identifier</label>
                    <div class="p-2 bg-light rounded border small">${escapeHtml(row.record_identifier)}</div>
                </div>
                <div class="col-12">
                    <label class="text-muted small fw-bold">Description / Details</label>
                    <div class="p-3 bg-light rounded border text-break small">${escapeHtml(row.details)}</div>
                </div>
        `;

        if (row.affected_counts) {
            html += `
                <div class="col-12">
                    <label class="text-muted small fw-bold">Affected Record Counts</label>
                    <pre class="p-2 bg-dark text-light rounded small mb-0" style="max-height: 150px; overflow-y: auto;">${escapeHtml(JSON.stringify(row.affected_counts, null, 2))}</pre>
                </div>
            `;
        }

        html += `
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Client IP Address</label>
                    <div class="font-monospace small">${escapeHtml(row.ip_address)}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small fw-bold">Browser / User Agent</label>
                    <div class="text-muted small text-truncate" title="${escapeHtml(row.user_agent || '')}">${escapeHtml(row.user_agent || '—')}</div>
                </div>
            </div>
        `;

        $('#auditDetailModalBody').html(html);
        new bootstrap.Modal(document.getElementById('auditDetailModal')).show();
    } catch (e) {
        console.error('Failed to parse audit detail:', e);
    }
}

function reloadAuditTable() {
    if (auditTable) {
        auditTable.ajax.reload(null, false);
    }
}

function exportAuditTrail() {
    const searchVal = auditTable ? auditTable.search() : '';
    const params = new URLSearchParams();
    if (searchVal) {
        params.set('search', searchVal);
    }
    const query = params.toString();
    window.location.href = BASE_URL + '/api/admin/audit-trail/export' + (query ? '?' + query : '');
}

$(document).ready(function() {
    auditTable = $('#auditTrailTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/audit-trail/data',
            type: 'POST',
            error: function(xhr, error, thrown) {
                $('#auditTableError').removeClass('d-none').addClass('d-flex');
                $('#auditTableErrorMessage').text(xhr.responseJSON?.error || thrown || 'Unable to connect to the audit API.');
            }
        },
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading audit events...',
            emptyTable: `
                <div class="text-center py-5">
                    <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                    <h6 class="fw-bold text-dark mt-3 mb-1">No Audit Records Found</h6>
                    <p class="text-muted small mb-0">No historical activities match your current filter criteria or date range.</p>
                </div>
            `,
            zeroRecords: `
                <div class="text-center py-5">
                    <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                    <h6 class="fw-bold text-dark mt-3 mb-1">No Matching Audit Records</h6>
                    <p class="text-muted small mb-0">Try clearing or broadening your search filters to view logs.</p>
                </div>
            `
        },
        columns: [
            {
                data: 'created_at',
                render: (val, type, row) => {
                    return `
                        <div class="fw-semibold text-dark">${escapeHtml(val)}</div>
                        <small class="text-muted font-monospace">#LOG-${String(row.id).padStart(5, '0')}</small>
                    `;
                }
            },
            {
                data: 'user_name',
                render: (val, type, row) => {
                    const email = row.user_email ? `<small class="text-muted d-block text-truncate" style="max-width: 140px;" title="${escapeHtml(row.user_email)}">${escapeHtml(row.user_email)}</small>` : '';
                    const userId = row.user_id ? `<span class="badge bg-light text-dark border me-1">#${row.user_id}</span>` : '';
                    return `
                        <div class="fw-semibold text-dark">${userId}${escapeHtml(val || 'System')}</div>
                        ${email}
                    `;
                }
            },
            {
                data: 'user_role',
                render: val => getRoleBadge(val)
            },
            {
                data: 'action',
                render: val => getActionBadge(val)
            },
            {
                data: 'entity_type',
                render: (val, type, row) => {
                    const idStr = row.entity_id ? `<small class="text-muted ms-1">#${row.entity_id}</small>` : '';
                    return `<span class="badge bg-light text-dark border fw-medium px-2 py-1">${escapeHtml(val)}${idStr}</span>`;
                }
            },
            {
                data: 'record_identifier',
                render: val => {
                    return `<span class="text-truncate d-inline-block fw-semibold text-slate-800" style="max-width: 180px;" title="${escapeHtml(val)}">${escapeHtml(val)}</span>`;
                }
            },
            {
                data: 'details',
                render: (val, type, row) => {
                    const encoded = encodeURIComponent(JSON.stringify(row));
                    return `
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span class="text-truncate d-inline-block text-muted" style="max-width: 260px;" title="${escapeHtml(val)}">${escapeHtml(val)}</span>
                            <button type="button" class="btn btn-sm btn-link text-primary p-0 flex-shrink-0" title="Inspect Full Record" onclick="viewAuditDetail('${encoded}')">
                                <i class="bi bi-arrows-fullscreen"></i>
                            </button>
                        </div>
                    `;
                }
            },
            {
                data: 'ip_address',
                render: val => `<span class="font-monospace small text-muted">${escapeHtml(val)}</span>`
            },
            {
                data: 'result',
                render: val => getResultBadge(val)
            }
        ]
    });

    // Reset error state on fresh draw
    auditTable.on('xhr', function() {
        $('#auditTableError').addClass('d-none').removeClass('d-flex');
        const json = auditTable.ajax.json();
        if (json && json.recordsTotal !== undefined) {
            $('#totalLogsBadge').text(Number(json.recordsTotal).toLocaleString());
        }
    });
});
</script>
<?php $pageScripts = ob_get_clean(); ?>
