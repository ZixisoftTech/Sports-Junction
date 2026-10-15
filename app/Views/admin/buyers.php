<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-2 fw-bold" style="font-size: 11px;">MARKETPLACE GOVERNANCE</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Buyer Management</h3>
        <p class="text-slate-500 small mb-0">View, search, verify, edit, and manage all registered B2B buyer accounts across Sports Junction.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Buyers
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportBuyers('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportBuyers('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportBuyers('pdf'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Print / Save PDF</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-chat-left-quote me-1"></i> View All Enquiries
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-slate-500 small fw-semibold mb-1">Total Buyers</div>
                    <h3 class="fw-bold text-slate-900 mb-0" id="metricTotalBuyers"><?= (int)($metrics['total'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-slate-500 small fw-semibold mb-1">Active Accounts</div>
                    <h3 class="fw-bold text-success mb-0" id="metricActiveBuyers"><?= (int)($metrics['active'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-person-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-slate-500 small fw-semibold mb-1">Email Verified</div>
                    <h3 class="fw-bold text-primary mb-0" id="metricVerifiedBuyers"><?= (int)($metrics['verified'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon" style="background:#e0e7ff;color:#4f46e5;"><i class="bi bi-patch-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-slate-500 small fw-semibold mb-1">Inactive / Suspended</div>
                    <h3 class="fw-bold text-danger mb-0" id="metricInactiveBuyers"><?= (int)($metrics['inactive'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-person-slash"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Account Status</label>
                <select id="filterAccountStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive / Deactivated</option>
                    <option value="PENDING_VERIFICATION">Pending Verification</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Email Verification</label>
                <select id="filterVerificationStatus" class="form-select form-select-sm">
                    <option value="ALL">All Verification States</option>
                    <option value="VERIFIED">Verified</option>
                    <option value="UNVERIFIED">Unverified</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Country</label>
                <select id="filterCountry" class="form-select form-select-sm">
                    <option value="ALL">All Countries</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="button" id="btnResetBuyerFilters" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Buyers DataTable Card -->
<div class="card card-premium">
    <div class="card-body p-3 p-md-4">
        <div class="table-responsive">
            <table id="buyersTable" class="table sj-table table-hover align-middle w-100 mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px;">Buyer ID</th>
                        <th>Full Name</th>
                        <th>Company Name</th>
                        <th>Business Email</th>
                        <th>Mobile Number</th>
                        <th>Country</th>
                        <th>Email Verification</th>
                        <th>Account Status</th>
                        <th>Registration Date</th>
                        <th class="text-end" style="min-width:170px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Buyer Profile Modal -->
<div class="modal fade" id="viewBuyerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-person-Vcard text-primary me-2"></i>Buyer Profile Details</h5>
                    <small class="text-slate-500" id="viewBuyerSubtitle">Complete buyer account overview & submitted enquiries</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="text-slate-500 small fw-semibold">Full Name & ID</div>
                            <div class="fw-bold text-slate-900 fs-6 mt-1" id="vbName">-</div>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary mt-1" id="vbId">#-</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="text-slate-500 small fw-semibold">Company / Organization</div>
                            <div class="fw-bold text-slate-900 fs-6 mt-1" id="vbCompany">-</div>
                            <span class="text-slate-500 small" id="vbCountry">-</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="text-slate-500 small fw-semibold">Business Email & Verification</div>
                            <div class="fw-semibold text-slate-900 mt-1" id="vbEmail">-</div>
                            <div class="mt-1" id="vbVerificationBadge"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="text-slate-500 small fw-semibold">Mobile Number & Account Status</div>
                            <div class="fw-semibold text-slate-900 mt-1" id="vbPhone">-</div>
                            <div class="mt-1" id="vbStatusBadge"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-slate-500">Registered On: <strong class="text-slate-800" id="vbCreated">-</strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-slate-500">Last Login: <strong class="text-slate-800" id="vbLastLogin">-</strong></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-slate-900 mb-0"><i class="bi bi-chat-square-text me-1 text-primary"></i>Enquiries Submitted by This Buyer</h6>
                    <a href="#" id="vbAllEnquiriesLink" class="btn btn-sm btn-outline-primary fw-semibold">
                        Open in Enquiry Management <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Enquiry ID</th>
                                <th>Source</th>
                                <th>Product / Requirement</th>
                                <th>Supplier</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="vbEnquiriesTbody">
                            <tr><td colspan="8" class="text-center text-muted py-3">No enquiries submitted yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Buyer Modal -->
<div class="modal fade" id="editBuyerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editBuyerForm" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? \App\Helpers\CSRF::token()) ?>">
            <input type="hidden" name="buyer_id" id="ebId">
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Buyer Details</h5>
                    <small class="text-slate-500">Update buyer profile, contact details, or account status</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info small py-2 px-3 mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i> Changing the buyer's Business Email will automatically reset their email verification status to <strong>Pending Verification</strong>.
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-slate-700">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="ebName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-slate-700">Company Name</label>
                    <input type="text" name="company_name" id="ebCompany" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-slate-700">Business Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="ebEmail" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label small fw-bold text-slate-700">Dial Code</label>
                        <input type="text" name="country_code" id="ebCountryCode" class="form-control" value="+91">
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold text-slate-700">Mobile Number</label>
                        <input type="text" name="phone" id="ebPhone" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-slate-700">Country</label>
                        <select name="country_id" id="ebCountryId" class="form-select">
                            <option value="">-- Select Country --</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-slate-700">Account Status</label>
                        <select name="status" id="ebStatus" class="form-select">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                            <option value="PENDING_VERIFICATION">PENDING_VERIFICATION</option>
                        </select>
                    </div>
                </div>
                <div class="form-check mt-2" id="ebVerifyCheckWrap">
                    <input class="form-check-input" type="checkbox" value="1" id="ebMarkVerified" name="mark_email_verified">
                    <label class="form-check-label small fw-semibold text-slate-700" for="ebMarkVerified">
                        Mark Email as Verified manually
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold" id="ebSubmitBtn">
                    <i class="bi bi-check2-circle me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken ?? \App\Helpers\CSRF::token()) ?>;
let buyersTable;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function updateMetrics(m) {
    if (!m) return;
    $('#metricTotalBuyers').text(m.total ?? 0);
    $('#metricActiveBuyers').text(m.active ?? 0);
    $('#metricVerifiedBuyers').text(m.verified ?? 0);
    $('#metricInactiveBuyers').text(m.inactive ?? 0);
}

$(document).ready(function() {
    buyersTable = $('#buyersTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        order: [[8, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/buyers/data',
            data: function(d) {
                d.account_status = $('#filterAccountStatus').val();
                d.verification_status = $('#filterVerificationStatus').val();
                d.country_id = $('#filterCountry').val();
            }
        },
        columns: [
            {
                data: 'id',
                render: id => `<span class="fw-bold text-slate-800">#BUY-${String(id).padStart(4, '0')}</span>`
            },
            {
                data: 'name',
                render: (name, type, row) => `
                    <div class="fw-bold text-slate-900">${escapeHtml(name)}</div>
                    <small class="text-slate-500">${Number(row.total_enquiries || 0)} Enquir${Number(row.total_enquiries || 0) === 1 ? 'y' : 'ies'}</small>
                `
            },
            {
                data: 'company_name',
                render: val => val ? `<span class="fw-semibold text-slate-700">${escapeHtml(val)}</span>` : '<span class="text-muted small">Individual / Unspecified</span>'
            },
            {
                data: 'email',
                render: val => `<a href="mailto:${escapeHtml(val)}" class="text-decoration-none text-slate-800">${escapeHtml(val)}</a>`
            },
            {
                data: 'phone',
                render: (phone, type, row) => phone ? `<span class="text-slate-700">${escapeHtml((row.country_code || '+91') + ' ' + phone)}</span>` : '<span class="text-muted">-</span>'
            },
            {
                data: 'country_name',
                render: val => val ? `<span class="badge bg-light text-slate-700 border">${escapeHtml(val)}</span>` : '<span class="text-muted">-</span>'
            },
            {
                data: 'email_verified_at',
                render: val => val
                    ? `<span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>`
                    : `<span class="badge rounded-pill" style="background:#fef3c7;color:#b45309;"><i class="bi bi-clock-history me-1"></i>Unverified</span>`
            },
            {
                data: 'status',
                render: status => {
                    if (status === 'ACTIVE') {
                        return `<span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;">ACTIVE</span>`;
                    } else if (status === 'INACTIVE') {
                        return `<span class="badge rounded-pill" style="background:#fee2e2;color:#b91c1c;">INACTIVE</span>`;
                    }
                    return `<span class="badge rounded-pill" style="background:#fef3c7;color:#b45309;">${escapeHtml(status)}</span>`;
                }
            },
            {
                data: 'created_at',
                render: val => val ? `<span class="small text-slate-600">${escapeHtml(val.substring(0, 10))}</span>` : '-'
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: row => {
                    const isActive = row.status === 'ACTIVE';
                    const toggleTitle = isActive ? 'Deactivate Buyer' : 'Activate Buyer';
                    const toggleBtnClass = isActive ? 'btn-outline-danger' : 'btn-outline-success';
                    const toggleIcon = isActive ? 'bi-person-slash' : 'bi-person-check';
                    return `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" title="View Buyer Profile" onclick="viewBuyer(${row.id})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" title="Edit Buyer Details" onclick="openEditBuyer(${row.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="${BASE_URL}/admin/enquiries?buyer_id=${row.id}" class="btn btn-outline-info" title="View Submitted Enquiries (${Number(row.total_enquiries || 0)})">
                                <i class="bi bi-chat-left-text"></i>
                            </a>
                            <button type="button" class="btn ${toggleBtnClass}" title="${toggleTitle}" onclick="toggleBuyerStatus(${row.id}, '${isActive ? 'INACTIVE' : 'ACTIVE'}', '${escapeHtml(row.name)}')">
                                <i class="bi ${toggleIcon}"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search buyer name, company, email, phone, ID, country..."
        }
    });

    $('#filterAccountStatus, #filterVerificationStatus, #filterCountry').on('change', function() {
        buyersTable.ajax.reload();
    });

    $('#btnResetBuyerFilters').on('click', function() {
        $('#filterAccountStatus').val('ALL');
        $('#filterVerificationStatus').val('ALL');
        $('#filterCountry').val('ALL');
        buyersTable.search('').draw();
    });

    $('#editBuyerForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#ebId').val();
        const btn = $('#ebSubmitBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: `${BASE_URL}/api/admin/buyers/${id}/update`,
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save Changes');
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editBuyerModal')).hide();
                    Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 2500, showConfirmButton: false });
                    buyersTable.ajax.reload(null, false);
                    updateMetrics(res.metrics);
                } else {
                    Swal.fire('Validation Error', res.message || 'Could not update buyer.', 'error');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save Changes');
                const msg = xhr.responseJSON?.message || 'Failed to update buyer details.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });
});

function viewBuyer(id) {
    $.getJSON(`${BASE_URL}/api/admin/buyers/${id}`, function(res) {
        if (!res.success) {
            Swal.fire('Error', res.message || 'Could not load buyer profile.', 'error');
            return;
        }
        const b = res.buyer;
        $('#vbId').text(`#BUY-${String(b.id).padStart(4, '0')}`);
        $('#vbName').text(b.name || '-');
        $('#vbCompany').text(b.company_name || 'Individual Buyer');
        $('#vbCountry').text(b.country_name ? `Country: ${b.country_name}` : 'Country: Not specified');
        $('#vbEmail').text(b.email || '-');
        $('#vbPhone').text(b.phone ? `${b.country_code || '+91'} ${b.phone}` : 'Not provided');
        $('#vbCreated').text(b.created_at || '-');
        $('#vbLastLogin').text(b.last_login_at || 'Never logged in');
        $('#vbAllEnquiriesLink').attr('href', `${BASE_URL}/admin/enquiries?buyer_id=${b.id}`);

        $('#vbVerificationBadge').html(b.email_verified_at
            ? `<span class="badge bg-success bg-opacity-10 text-success">Verified (${escapeHtml(b.email_verified_at)})</span>`
            : `<span class="badge bg-warning bg-opacity-10 text-warning">Pending Email Verification</span>`
        );

        $('#vbStatusBadge').html(b.status === 'ACTIVE'
            ? `<span class="badge bg-success">ACTIVE</span>`
            : `<span class="badge bg-danger">${escapeHtml(b.status)}</span>`
        );

        const tbody = $('#vbEnquiriesTbody');
        tbody.empty();
        if (!res.enquiries || res.enquiries.length === 0) {
            tbody.append(`<tr><td colspan="8" class="text-center text-muted py-3">No enquiries submitted by this buyer.</td></tr>`);
        } else {
            res.enquiries.forEach(enq => {
                const srcLabel = enq.enquiry_source === 'CONTACT_US' ? 'Contact Us' : (enq.enquiry_source === 'GENERAL' ? 'General RFQ' : 'Product Page');
                const prodOrSubj = enq.product_name || enq.subject || 'General Requirement';
                tbody.append(`
                    <tr>
                        <td class="fw-bold">#ENQ-${String(enq.id).padStart(5, '0')}</td>
                        <td><span class="badge bg-light text-dark border">${escapeHtml(srcLabel)}</span></td>
                        <td>${escapeHtml(prodOrSubj)}</td>
                        <td>${escapeHtml(enq.supplier_name || 'Unassigned')}</td>
                        <td>${escapeHtml(enq.target_quantity || 1)}</td>
                        <td><span class="badge bg-secondary">${escapeHtml(enq.status)}</span></td>
                        <td class="small">${escapeHtml((enq.created_at || '').substring(0, 10))}</td>
                        <td class="text-end">
                            <a href="${BASE_URL}/admin/enquiries/${enq.id}" class="btn btn-xs btn-outline-primary py-0 px-2">View</a>
                        </td>
                    </tr>
                `);
            });
        }

        new bootstrap.Modal(document.getElementById('viewBuyerModal')).show();
    });
}

function openEditBuyer(id) {
    $.getJSON(`${BASE_URL}/api/admin/buyers/${id}`, function(res) {
        if (!res.success) {
            Swal.fire('Error', res.message || 'Could not load buyer details.', 'error');
            return;
        }
        const b = res.buyer;
        $('#ebId').val(b.id);
        $('#ebName').val(b.name || '');
        $('#ebCompany').val(b.company_name || '');
        $('#ebEmail').val(b.email || '');
        $('#ebCountryCode').val(b.country_code || '+91');
        $('#ebPhone').val(b.phone || '');
        $('#ebCountryId').val(b.country_id || '');
        $('#ebStatus').val(b.status || 'ACTIVE');
        $('#ebMarkVerified').prop('checked', false);
        if (b.email_verified_at) {
            $('#ebVerifyCheckWrap').hide();
        } else {
            $('#ebVerifyCheckWrap').show();
        }

        new bootstrap.Modal(document.getElementById('editBuyerModal')).show();
    });
}

function toggleBuyerStatus(id, targetStatus, buyerName) {
    const isDeactivating = targetStatus === 'INACTIVE';
    Swal.fire({
        title: isDeactivating ? 'Deactivate Buyer Account?' : 'Activate Buyer Account?',
        html: isDeactivating
            ? `Deactivating <strong>${escapeHtml(buyerName)}</strong> will immediately block login and protected marketplace actions. Past enquiry history will be preserved.`
            : `Reactivating <strong>${escapeHtml(buyerName)}</strong> will restore full buyer portal access.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: isDeactivating ? '#dc2626' : '#16a34a',
        confirmButtonText: isDeactivating ? 'Yes, Deactivate' : 'Yes, Activate'
    }).then(result => {
        if (!result.isConfirmed) return;
        $.post(`${BASE_URL}/api/admin/buyers/${id}/toggle-status`, {
            csrf_token: CSRF_TOKEN,
            status: targetStatus
        }, function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Status Updated', text: res.message, timer: 2200, showConfirmButton: false });
                buyersTable.ajax.reload(null, false);
                updateMetrics(res.metrics);
            } else {
                Swal.fire('Error', res.message || 'Could not change status.', 'error');
            }
        }, 'json');
    });
}

function exportBuyers(format) {
    const params = new URLSearchParams({
        format: format,
        account_status: $('#filterAccountStatus').val(),
        verification_status: $('#filterVerificationStatus').val(),
        country_id: $('#filterCountry').val(),
        search: buyersTable ? buyersTable.search() : ''
    });
    window.open(`${BASE_URL}/api/admin/buyers/export?${params.toString()}`, '_blank');
}
</script>
<?php $pageScripts = ob_get_clean(); ?>
