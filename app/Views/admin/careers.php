<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-purple-50 text-purple-800 px-2 py-1 rounded-2 fw-bold" style="font-size: 11px; background-color: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe;">CAREERS &amp; SUBMISSIONS</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Careers Form Submissions</h3>
        <p class="text-slate-500 small mb-0">View candidate job applications and resumes submitted through the website careers form.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Submissions
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportCandidates('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportCandidates('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/careers" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-box-arrow-up-right me-1"></i> Public Careers Page
        </a>
    </div>
</div>

<!-- KPI Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">Total Submissions</div>
            <h4 class="fw-bold text-slate-900 mb-0 mt-1" id="mTotal"><?= (int)($metrics['total'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">New Submissions</div>
            <h4 class="fw-bold text-warning mb-0 mt-1" style="color: #b45309 !important;" id="mNew"><?= (int)($metrics['new'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">Reviewed / Contacted</div>
            <h4 class="fw-bold text-primary mb-0 mt-1" style="color: #0284c7 !important;">
                <?= (int)(($metrics['shortlisted'] ?? 0) + ($metrics['interview_scheduled'] ?? 0) + ($metrics['selected'] ?? 0)) ?>
            </h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100 p-3">
            <div class="text-slate-500 small fw-semibold">Total Resumes Attached</div>
            <h4 class="fw-bold text-success mb-0 mt-1" style="color: #16a34a !important;"><?= (int)($metrics['total'] ?? 0) ?></h4>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <!-- Date Range Filter -->
            <div class="col-md-5">
                <label class="form-label small fw-bold text-slate-600 mb-1">Submission Date Range</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="filterStartDate" class="form-control" title="Start Date">
                    <span class="input-group-text bg-white text-muted">to</span>
                    <input type="date" id="filterEndDate" class="form-control" title="End Date">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
                    <i class="bi bi-x-circle me-1"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Applications Table Card -->
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="applicationsTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 130px;">App #</th>
                        <th>Candidate Details</th>
                        <th>Job Role Entered</th>
                        <th>Location / City</th>
                        <th>Resume</th>
                        <th>Submitted Date</th>
                        <th class="text-end" style="min-width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Candidate Form Details Modal -->
<div class="modal fade" id="candidateDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900">Candidate Form Details</h5>
                    <small class="text-slate-500 font-monospace" id="cdModalAppNo"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 space-y-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Candidate Full Name</label>
                        <div class="fw-bold text-slate-900 fs-6" id="cdFullName">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Job Role / Position Entered</label>
                        <div>
                            <span class="badge bg-purple-50 text-purple-800 border border-purple-200 fs-7" id="cdPositionApplied" style="background:#f3e8ff;color:#6b21a8;">-</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Email Address</label>
                        <div id="cdEmail">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Mobile / WhatsApp Number</label>
                        <div id="cdMobile">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Location / City</label>
                        <div id="cdLocation">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Submitted Date &amp; Time</label>
                        <div id="cdSubmittedAt">-</div>
                    </div>
                </div>

                <!-- Resume Download Box -->
                <div class="border-top pt-3">
                    <label class="text-xs text-muted fw-bold uppercase mb-2 d-block">Uploaded Resume / CV</label>
                    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                            <div>
                                <div class="fw-bold text-slate-900" id="cdResumeName">Resume.pdf</div>
                                <small class="text-muted" id="cdResumeSize">-</small>
                            </div>
                        </div>
                        <a id="cdResumeDownloadBtn" href="#" target="_blank" class="btn btn-sm btn-primary fw-bold px-3">
                            <i class="bi bi-download me-1"></i> Download Resume
                        </a>
                    </div>
                </div>

                <!-- Message / Additional Notes -->
                <div class="border-top pt-3" id="cdCoverLetterSection">
                    <label class="text-xs text-muted fw-bold uppercase mb-1 d-block">Message / Additional Notes</label>
                    <div class="p-3 bg-slate-50 rounded-3 border text-slate-700 text-sm whitespace-pre-wrap" id="cdCoverLetter">-</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
let appTable;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

$(document).ready(function() {
    appTable = $('#applicationsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 15,
        order: [[5, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/careers/applications/data',
            data: function(d) {
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            {
                data: 'application_no',
                render: function(d, t, row) {
                    return `<span class="fw-bold font-monospace text-primary">${escapeHtml(d || ('APP-' + row.id))}</span>`;
                }
            },
            {
                data: 'full_name',
                render: function(d, t, row) {
                    const name = d || row.name || '';
                    let html = `<div class="fw-bold text-slate-900">${escapeHtml(name)}</div>`;
                    html += `<small class="text-slate-500 d-block"><i class="bi bi-envelope me-1"></i>${escapeHtml(row.email)}</small>`;
                    if (row.phone) {
                        html += `<small class="text-slate-500 d-block"><i class="bi bi-telephone me-1"></i>${escapeHtml(row.phone)}</small>`;
                    }
                    return html;
                }
            },
            {
                data: 'position_applied',
                render: function(d) {
                    return `<span class="badge bg-purple-50 text-purple-800 border border-purple-200" style="background:#f3e8ff;color:#6b21a8;font-size:12px;">${escapeHtml(d || 'General Application')}</span>`;
                }
            },
            {
                data: 'city',
                render: function(d, t, row) {
                    const country = row.country_name || '';
                    return `<div><i class="bi bi-geo-alt me-1 text-muted"></i>${escapeHtml(d || '')}${country ? ', ' + escapeHtml(country) : ''}</div>`;
                }
            },
            {
                data: 'resume_path',
                orderable: false,
                render: function(d, t, row) {
                    if (!d) return '<span class="text-muted small">No Resume</span>';
                    return `<a href="${BASE_URL}/admin/careers/resume/${row.id}" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 fw-semibold" title="Download Resume">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Resume
                    </a>`;
                }
            },
            {
                data: 'created_at',
                render: function(d) {
                    if (!d) return '-';
                    return `<small class="text-muted">${escapeHtml(d.substring(0, 16))}</small>`;
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: function(d, t, row) {
                    return `
                        <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" title="View Form Details" onclick="viewCandidate(${row.id})">
                            <i class="bi bi-eye me-1"></i> View Details
                        </button>
                    `;
                }
            }
        ],
        language: {
            emptyTable: "No candidate applications found.",
            search: "_INPUT_",
            searchPlaceholder: "Search by candidate name, email, phone, job role, city..."
        }
    });

    $('#filterStartDate, #filterEndDate').on('change', function() {
        appTable.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        appTable.search('').draw();
    });
});

function exportCandidates(format) {
    const start = $('#filterStartDate').val();
    const end = $('#filterEndDate').val();
    const search = appTable.search();

    let url = `${BASE_URL}/api/admin/careers/applications/export?format=${format}&start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&search=${encodeURIComponent(search)}`;
    window.location.href = url;
}

function viewCandidate(id) {
    $.ajax({
        url: `${BASE_URL}/api/admin/careers/applications/${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (!res.success || !res.application) {
                alert('Could not load application details.');
                return;
            }
            const a = res.application;
            $('#cdModalAppNo').text(a.application_no || `APP-${a.id}`);
            $('#cdFullName').text(a.full_name || a.name || '-');
            $('#cdPositionApplied').text(a.position_applied || 'Not specified');
            $('#cdEmail').html(`<a href="mailto:${escapeHtml(a.email)}" class="text-decoration-none text-primary fw-semibold">${escapeHtml(a.email)}</a>`);
            $('#cdMobile').html(a.phone ? `<a href="tel:${escapeHtml(a.phone)}" class="text-decoration-none text-slate-800 fw-semibold">${escapeHtml(a.phone)}</a>` : '<span class="text-muted">Not provided</span>');
            
            let loc = a.city || '';
            if (a.country_name) loc += (loc ? ', ' : '') + a.country_name;
            $('#cdLocation').text(loc || '-');
            $('#cdSubmittedAt').text(a.created_at || '-');

            if (a.cover_letter) {
                $('#cdCoverLetter').text(a.cover_letter);
                $('#cdCoverLetterSection').removeClass('d-none');
            } else {
                $('#cdCoverLetterSection').addClass('d-none');
            }

            if (a.resume_path) {
                $('#cdResumeName').text(a.resume_name || 'Candidate_Resume.pdf');
                let sz = a.resume_size ? ` (${(a.resume_size / 1024).toFixed(1)} KB)` : '';
                $('#cdResumeSize').text((a.resume_mime || 'PDF Document') + sz);
                $('#cdResumeDownloadBtn').attr('href', `${BASE_URL}/admin/careers/resume/${a.id}`).removeClass('d-none');
            } else {
                $('#cdResumeName').text('No resume uploaded');
                $('#cdResumeSize').text('-');
                $('#cdResumeDownloadBtn').addClass('d-none');
            }

            const modal = new bootstrap.Modal(document.getElementById('candidateDetailModal'));
            modal.show();
        },
        error: function() {
            alert('Failed to connect to the server.');
        }
    });
}
</script>
