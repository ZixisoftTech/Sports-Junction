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
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Buyers</div>
                    <div class="stat-number text-slate-900" id="metricTotalBuyers"><?= (int)($metrics['total'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#eff6ff;color:#2563eb;">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Active Accounts</div>
                    <div class="stat-number text-success" id="metricActiveBuyers"><?= (int)($metrics['active'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Email Verified</div>
                    <div class="stat-number text-primary" id="metricVerifiedBuyers"><?= (int)($metrics['verified'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#e0e7ff;color:#4f46e5;">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Inactive / Suspended</div>
                    <div class="stat-number text-danger" id="metricInactiveBuyers"><?= (int)($metrics['inactive'] ?? 0) ?></div>
                </div>
                <div class="stat-icon" style="background:#fee2e2;color:#dc2626;">
                    <i class="bi bi-person-slash"></i>
                </div>
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
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-slate-700">Country</label>
                        <select name="country_id" id="ebCountryId" class="form-select" onchange="const p = $(this).find(':selected').data('phone'); if (p) $('#ebCountryCode').val(p);">
                            <option value="">-- Select Country --</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" data-flag="<?= htmlspecialchars($c['flag'] ?? '🌐') ?>" data-phone="<?= htmlspecialchars($c['phone_code'] ?? '') ?>">
                                    <?= $c['flag'] ?? '🌐' ?> <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone_code'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-slate-700">State / Province</label>
                        <input type="text" name="state" id="ebState" class="form-control" placeholder="e.g. California / Maharashtra">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-slate-700">City</label>
                        <input type="text" name="city" id="ebCity" class="form-control" placeholder="e.g. Los Angeles / Mumbai">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-slate-700">Account Status</label>
                    <select name="status" id="ebStatus" class="form-select">
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="INACTIVE">INACTIVE</option>
                        <option value="PENDING_VERIFICATION">PENDING_VERIFICATION</option>
                    </select>
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

<!-- Review Profile Changes Modal -->
<div class="modal fade" id="profileReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-shield-check text-warning me-2"></i>Review Profile Update Request</h5>
                    <small class="text-slate-500" id="prModalSubtitle">Audit proposed profile changes before approving or rejecting</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="prModalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between" id="prModalFooter">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2" id="prModalActions"></div>
            </div>
        </div>
    </div>
</div>

<!-- User Credentials & Password Inspection Modal -->
<div class="modal fade" id="userCredentialsModal" tabindex="-1" aria-labelledby="userCredentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fs-6 fw-bold" id="userCredentialsModalLabel">
                    <i class="bi bi-key-fill text-warning me-2"></i>Account Access &amp; Credentials
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="credLoadingState" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading credentials...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Decrypting account credentials securely...</p>
                </div>

                <div id="credErrorState" class="alert alert-danger py-2 d-none"></div>

                <div id="credContentState" class="d-none">
                    <!-- User Header Info -->
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="fw-bold mb-0 text-slate-900" id="credUserName">-</h6>
                            <span class="text-muted small" id="credUserCompany">-</span>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border px-2.5 py-1.5" id="credUserRole">BUYER</span>
                    </div>

                    <!-- Email Field -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold mb-1">Email / Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="text" class="form-control font-monospace" id="credEmail" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyCredValue('credEmail', 'Email copied!')" title="Copy Email">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3" id="vaultPasswordSection">
                        <label class="form-label text-muted small fw-semibold mb-1">Account Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control font-monospace" id="credPassword" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="btnToggleCredPass" onclick="toggleCredPasswordVisibility()" title="Show/Hide Password">
                                <i class="bi bi-eye" id="toggleCredPassIcon"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyCredValue('credPassword', 'Password copied!')" title="Copy Password">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i>Copy this password to log in via the regular buyer login page.
                        </div>
                    </div>

                    <!-- No Vault Password Notice -->
                    <div class="alert alert-warning py-2 mb-3 d-none" id="noVaultPasswordAlert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning mt-0.5"></i>
                            <div class="small">
                                <strong>Original password unavailable:</strong> This account was created prior to the password vault. You can set a new password below to log in immediately.
                            </div>
                        </div>
                    </div>

                    <!-- Direct Login Portal Link -->
                    <div class="mb-4 p-3 bg-light rounded-3 border text-center">
                        <span class="text-muted small d-block mb-2">Use the standard login page to sign in:</span>
                        <a href="#" target="_blank" id="credLoginLink" class="btn btn-outline-dark btn-sm px-3 fw-bold">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Buyer Login Page
                        </a>
                    </div>

                    <!-- Reset / Set Password Section -->
                    <div class="border-top pt-3">
                        <a class="text-decoration-none small fw-bold text-primary d-flex align-items-center justify-content-between cursor-pointer" data-bs-toggle="collapse" href="#collapseSetPassword">
                            <span><i class="bi bi-gear-fill me-1"></i>Change / Set Account Password</span>
                            <i class="bi bi-chevron-down"></i>
                        </a>
                        <div class="collapse mt-2" id="collapseSetPassword">
                            <form id="adminSetPasswordForm" onsubmit="handleAdminSetPassword(event)">
                                <input type="hidden" id="credTargetUserId" value="">
                                <div class="mb-2">
                                    <label class="form-label text-muted small mb-1">New Password (min 6 characters)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="newAdminPasswordInput" placeholder="Enter new password" required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" onclick="generateRandomCredPassword()">Generate</button>
                                        <button class="btn btn-primary" type="submit" id="btnSubmitAdminPassword">Update</button>
                                    </div>
                                </div>
                                <div id="setPasswordFeedback" class="small mt-1"></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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
        stateSave: true,
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
                render: (name, type, row) => {
                    return `
                        <div class="fw-bold text-slate-900">${escapeHtml(name)}</div>
                        <small class="text-slate-500">${Number(row.total_enquiries || 0)} Enquir${Number(row.total_enquiries || 0) === 1 ? 'y' : 'ies'}</small>
                    `;
                }
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
                render: val => {
                    const country = val || 'India';
                    return `<span class="badge-country"><i class="bi bi-geo-alt-fill"></i><span>${escapeHtml(country)}</span></span>`;
                }
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
                className: 'text-end text-nowrap',
                render: row => {
                    const isActive = row.status === 'ACTIVE';
                    const toggleTitle = isActive ? 'Deactivate Buyer' : 'Activate Buyer';
                    const toggleBtnClass = isActive ? 'btn-outline-danger' : 'btn-outline-success';
                    const toggleIcon = isActive ? 'bi-person-slash' : 'bi-person-check';

                    let revBtn = row.pending_profile_change_id
                        ? `<button type="button" class="btn btn-warning text-dark fw-bold me-1.5 position-relative shadow-xs" title="Review submitted profile edits" onclick="openProfileReviewModal(${row.pending_profile_change_id})" style="border-radius:6px; font-size:12px;"><i class="bi bi-clock-history me-1"></i>Review Changes<span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-white rounded-circle"></span></button>`
                        : '';

                    return `
                        <div class="d-inline-flex align-items-center">
                            ${revBtn}
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
                                <button type="button" class="btn btn-outline-primary" title="View Password & Login Credentials" onclick="showCredentialsModal(${row.id}, '${escapeHtml(row.name)}', 'BUYER')">
                                    <i class="bi bi-key-fill"></i>
                                </button>
                                <button type="button" class="btn ${toggleBtnClass}" title="${toggleTitle}" onclick="toggleBuyerStatus(${row.id}, '${isActive ? 'INACTIVE' : 'ACTIVE'}', '${escapeHtml(row.name)}')">
                                    <i class="bi ${toggleIcon}"></i>
                                </button>
                            </div>
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
        const cName = b.country_name || 'India';
        const locParts = [b.city, b.state, cName].filter(Boolean);
        $('#vbCountry').html(`<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="bi bi-geo-alt-fill me-1"></i>${escapeHtml(cName)}</span>${locParts.length > 1 ? ` <span class="small text-slate-500 ms-1">(${escapeHtml([b.city, b.state].filter(Boolean).join(', '))})</span>` : ''}`);
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

        let cId = b.country_id;
        if (!cId && b.country_code) {
            const opt = $(`#ebCountryId option[data-phone="${b.country_code}"]`);
            if (opt.length) cId = opt.val();
        }
        $('#ebCountryId').val(cId || '1');
        $('#ebState').val(b.state || '');
        $('#ebCity').val(b.city || '');
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

function openProfileReviewModal(requestId) {
    const modalEl = document.getElementById('profileReviewModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    $('#prModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    $('#prModalActions').html('');

    $.getJSON(`${BASE_URL}/api/admin/profile-requests/${requestId}`, function(res) {
        if (!res.success) {
            $('#prModalBody').html(`<div class="alert alert-danger">${escapeHtml(res.message || 'Error loading request.')}</div>`);
            return;
        }

        const r = res.data;
        const cur = r.decoded_current || {};
        const req = r.decoded_requested || {};

        $('#prModalSubtitle').text(`Revision #${r.id} for ${r.user_name || r.user_email} (Submitted ${r.created_at})`);

        let diffRows = '';
        const allKeys = Array.from(new Set([...Object.keys(cur), ...Object.keys(req)]));

        allKeys.forEach(k => {
            const oldVal = cur[k] !== undefined && cur[k] !== null ? String(cur[k]) : '—';
            const newVal = req[k] !== undefined && req[k] !== null ? String(req[k]) : '—';
            const changed = (oldVal !== newVal);
            const label = k.replace(/_/g, ' ').toUpperCase();

            diffRows += `
                <tr class="${changed ? 'table-warning' : ''}">
                    <td class="fw-bold text-slate-700">${escapeHtml(label)}</td>
                    <td class="text-slate-600">${escapeHtml(oldVal)}</td>
                    <td class="${changed ? 'fw-bold text-primary' : 'text-slate-600'}">
                        ${escapeHtml(newVal)}
                        ${changed ? '<span class="badge bg-warning text-dark ms-1">Modified</span>' : ''}
                    </td>
                </tr>
            `;
        });

        const html = `
            <div class="mb-3">
                <span class="badge bg-secondary mb-2">${escapeHtml(r.role)} ACCOUNT</span>
                <p class="text-slate-600 small mb-0">The user submitted updated profile information. Review the side-by-side comparison below:</p>
            </div>
            <div class="table-responsive rounded-3 border mb-3">
                <table class="table table-sm table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Field</th>
                            <th style="width: 37.5%;">Current Approved Value</th>
                            <th style="width: 37.5%;">Proposed Value</th>
                        </tr>
                    </thead>
                    <tbody>${diffRows}</tbody>
                </table>
            </div>
            <div class="mb-2">
                <label class="form-label small fw-bold text-slate-700">Rejection Feedback (Required only if rejecting):</label>
                <input type="text" id="prRejectReason" class="form-control form-control-sm" placeholder="e.g. Please provide valid registered business details.">
            </div>
        `;

        $('#prModalBody').html(html);

        $('#prModalActions').html(`
            <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="rejectProfileRequest(${r.id})">
                <i class="bi bi-x-circle me-1"></i> Reject Revision
            </button>
            <button type="button" class="btn btn-success btn-sm px-3 fw-bold" onclick="approveProfileRequest(${r.id})">
                <i class="bi bi-check-circle me-1"></i> Approve &amp; Publish Changes
            </button>
        `);
    });
}

function approveProfileRequest(requestId) {
    if (!confirm('Approve and apply these profile changes immediately?')) return;

    $.ajax({
        url: `${BASE_URL}/api/admin/profile-requests/${requestId}/approve`,
        method: 'POST',
        headers: { 'X-CSRF-Token': CSRF_TOKEN },
        data: { _csrf: CSRF_TOKEN },
        success: function(res) {
            if (res.success) {
                Swal.fire('Approved', res.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('profileReviewModal')).hide();
                buyersTable.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.message || 'Approval failed.', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Server error.', 'error');
        }
    });
}

function rejectProfileRequest(requestId) {
    const reason = $('#prRejectReason').val();
    if (!reason || reason.trim() === '') {
        alert('Please specify a rejection reason for user feedback.');
        $('#prRejectReason').focus();
        return;
    }

    $.ajax({
        url: `${BASE_URL}/api/admin/profile-requests/${requestId}/reject`,
        method: 'POST',
        headers: { 'X-CSRF-Token': CSRF_TOKEN },
        data: { _csrf: CSRF_TOKEN, reason: reason },
        success: function(res) {
            if (res.success) {
                Swal.fire('Rejected', res.message, 'info');
                bootstrap.Modal.getInstance(document.getElementById('profileReviewModal')).hide();
                buyersTable.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.message || 'Rejection failed.', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Server error.', 'error');
        }
    });
}

// Prevent 'Blocked aria-hidden on an element because its descendant retained focus'
$(document).on('hide.bs.modal', '.modal', function () {
    if (document.activeElement && this.contains(document.activeElement)) {
        document.activeElement.blur();
    }
});

function showCredentialsModal(id, nameOrCompany, type) {
    const apiBase = (typeof BASE_URL !== 'undefined') ? BASE_URL : (typeof baseUrl !== 'undefined' ? baseUrl : '');
    const modalEl = document.getElementById('userCredentialsModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

    // Reset initial UI state
    document.getElementById('credLoadingState').classList.remove('d-none');
    document.getElementById('credContentState').classList.add('d-none');
    document.getElementById('credErrorState').classList.add('d-none');
    document.getElementById('setPasswordFeedback').innerHTML = '';
    document.getElementById('newAdminPasswordInput').value = '';

    const collapseEl = document.getElementById('collapseSetPassword');
    const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
    if (bsCollapse) {
        bsCollapse.hide();
    } else {
        collapseEl.classList.remove('show');
    }

    // Reset password visibility
    const passInput = document.getElementById('credPassword');
    passInput.type = 'password';
    const passIcon = document.getElementById('toggleCredPassIcon');
    if (passIcon) {
        passIcon.className = 'bi bi-eye';
    }

    modal.show();

    const endpoint = (type === 'SUPPLIER') 
        ? `${apiBase}/api/admin/suppliers/${id}/credentials` 
        : `${apiBase}/api/admin/users/${id}/credentials`;

    fetch(endpoint, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('credLoadingState').classList.add('d-none');
        if (!data.success || !data.user) {
            document.getElementById('credErrorState').textContent = data.message || 'Unable to retrieve user credentials.';
            document.getElementById('credErrorState').classList.remove('d-none');
            return;
        }

        const user = data.user;
        document.getElementById('credUserName').textContent = user.name || nameOrCompany || 'Account';
        document.getElementById('credUserCompany').textContent = user.company_name ? `(${user.company_name})` : '';
        document.getElementById('credUserRole').textContent = (user.role === 'BUYER') ? 'BUYER' : (user.role || 'USER');
        document.getElementById('credEmail').value = user.email || '';
        document.getElementById('credTargetUserId').value = user.id;

        const loginUrl = user.login_url ? `${apiBase}${user.login_url}` : `${apiBase}/login`;
        document.getElementById('credLoginLink').setAttribute('href', loginUrl);

        if (user.has_vault_password && user.password) {
            passInput.value = user.password;
            document.getElementById('vaultPasswordSection').classList.remove('d-none');
            document.getElementById('noVaultPasswordAlert').classList.add('d-none');
        } else {
            passInput.value = '';
            document.getElementById('vaultPasswordSection').classList.add('d-none');
            document.getElementById('noVaultPasswordAlert').classList.remove('d-none');
            // Auto-expand password reset form
            new bootstrap.Collapse(collapseEl, { show: true });
        }

        document.getElementById('credContentState').classList.remove('d-none');
    })
    .catch(err => {
        document.getElementById('credLoadingState').classList.add('d-none');
        document.getElementById('credErrorState').textContent = 'Network or server error loading credentials.';
        document.getElementById('credErrorState').classList.remove('d-none');
    });
}

function copyCredValue(elementId, successMsg) {
    const val = document.getElementById(elementId).value;
    if (!val) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(val).then(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: successMsg || 'Copied to clipboard',
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                alert(successMsg || 'Copied!');
            }
        });
    } else {
        const input = document.getElementById(elementId);
        input.select();
        document.execCommand('copy');
        alert(successMsg || 'Copied!');
    }
}

function toggleCredPasswordVisibility() {
    const input = document.getElementById('credPassword');
    const icon = document.getElementById('toggleCredPassIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function generateRandomCredPassword() {
    const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
    let pass = '';
    for (let i = 0; i < 12; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('newAdminPasswordInput').value = pass;
}

function handleAdminSetPassword(e) {
    e.preventDefault();
    const userId = document.getElementById('credTargetUserId').value;
    const newPass = document.getElementById('newAdminPasswordInput').value.trim();
    const feedback = document.getElementById('setPasswordFeedback');
    const submitBtn = document.getElementById('btnSubmitAdminPassword');

    if (!newPass || newPass.length < 6) {
        feedback.innerHTML = '<span class="text-danger">Password must be at least 6 characters.</span>';
        return;
    }

    feedback.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Updating password...</span>';
    submitBtn.disabled = true;

    const apiBase = (typeof BASE_URL !== 'undefined') ? BASE_URL : (typeof baseUrl !== 'undefined' ? baseUrl : '');
    const token = (typeof CSRF_TOKEN !== 'undefined') ? CSRF_TOKEN : (typeof csrfToken !== 'undefined' ? csrfToken : '');

    fetch(`${apiBase}/api/admin/users/${userId}/set-password`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': token
        },
        body: new URLSearchParams({
            password: newPass,
            csrf_token: token
        })
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.disabled = false;
        if (data.success) {
            feedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Password updated successfully!</span>';
            const passInput = document.getElementById('credPassword');
            passInput.value = newPass;
            document.getElementById('vaultPasswordSection').classList.remove('d-none');
            document.getElementById('noVaultPasswordAlert').classList.add('d-none');
            
            // Auto copy new password
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(newPass);
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Password updated & copied to clipboard!',
                    showConfirmButton: false,
                    timer: 2500
                });
            }
        } else {
            feedback.innerHTML = `<span class="text-danger">${escapeHtml(data.message || 'Failed to update password.')}</span>`;
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        feedback.innerHTML = '<span class="text-danger">Network error while updating password.</span>';
    });
}
</script>
<?php $pageScripts = ob_get_clean(); ?>
