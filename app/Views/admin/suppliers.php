<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Supplier &amp; Factory Management</h4>
            <p class="text-muted small mb-0">Create, edit, approve, suspend, and monitor sports equipment manufacturers and vendor profiles.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm px-3 py-2 fw-semibold" onclick="openAddSupplierModal()">
                <i class="bi bi-plus-circle fs-6"></i>
                <span>Add Supplier Manually</span>
            </button>
        </div>
    </div>

    <!-- Stat Metrics Row -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        <div class="col">
            <div class="card shadow-sm border-0 border-start border-primary border-4 h-100 cursor-pointer" style="cursor: pointer;" onclick="setSupplierStatusFilter('')" title="View all suppliers">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Total Vendors</span>
                            <h3 class="fw-bold m-0 mt-1" id="metricTotalVendors"><?= (int)($metrics['total'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2.5 rounded-3">
                            <i class="bi bi-buildings fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm border-0 border-start border-warning border-4 h-100 cursor-pointer" style="cursor: pointer;" onclick="setSupplierStatusFilter('PENDING_APPROVAL')" title="Filter pending review">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-warning small fw-bold text-uppercase">Pending Review</span>
                            <h3 class="fw-bold m-0 mt-1 text-warning" id="metricPendingReview"><?= (int)($metrics['pending'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-warning-subtle text-warning p-2.5 rounded-3">
                            <i class="bi bi-hourglass-top fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100 cursor-pointer" style="cursor: pointer;" onclick="setSupplierStatusFilter('APPROVED')" title="Filter approved suppliers">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-success small fw-bold text-uppercase">Approved Facilities</span>
                            <h3 class="fw-bold m-0 mt-1 text-success" id="metricApproved"><?= (int)($metrics['approved'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-success-subtle text-success p-2.5 rounded-3">
                            <i class="bi bi-patch-check-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm border-0 border-start border-secondary border-4 h-100 cursor-pointer" style="cursor: pointer;" onclick="setSupplierStatusFilter('INACTIVE')" title="Filter inactive accounts">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase">Inactive Accounts</span>
                            <h3 class="fw-bold m-0 mt-1 text-secondary" id="metricInactive"><?= (int)($metrics['inactive'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-secondary-subtle text-secondary p-2.5 rounded-3">
                            <i class="bi bi-slash-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm border-0 border-start border-danger border-4 h-100 cursor-pointer" style="cursor: pointer;" onclick="setSupplierStatusFilter('REJECTED')" title="Filter rejected or suspended">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-danger small fw-bold text-uppercase">Rejected / Suspended</span>
                            <h3 class="fw-bold m-0 mt-1 text-danger" id="metricRejectedSuspended"><?= (int)(($metrics['rejected'] ?? 0) + ($metrics['suspended'] ?? 0)) ?></h3>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2.5 rounded-3">
                            <i class="bi bi-shield-x fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suppliers Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-lines-fill text-primary"></i>
                <span class="fw-bold small text-uppercase">Suppliers Directory</span>
            </div>

            <!-- Status Filter -->
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-semibold">Status Filter:</label>
                <select id="statusFilter" class="form-select form-select-sm" style="width: 190px;">
                    <option value="">All Statuses</option>
                    <option value="PENDING_APPROVAL">Pending Review</option>
                    <option value="APPROVED">Approved</option>
                    <option value="INACTIVE">Inactive Accounts</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="SUSPENDED">Suspended</option>
                    <option value="PENDING_VERIFICATION">Pending Verification</option>
                </select>
            </div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="suppliersTable" class="table table-hover align-middle w-100" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Business Type</th>
                            <th>Location</th>
                            <th>Email &amp; Phone</th>
                            <th>Approval Status</th>
                            <th>Products</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Supplier Add / Edit Modal -->
<div class="modal fade" id="supplierFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6" id="supplierModalTitle">Add Supplier Manually</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="supplierForm" onsubmit="submitSupplierForm(event)">
                <input type="hidden" id="formSupplierId" value="">
                <div class="modal-body p-4">
                    <div id="supplierFormAlert" class="alert alert-danger d-none py-2 px-3 small"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Company / Factory Name *</label>
                            <input type="text" id="inpCompanyName" class="form-control form-control-sm" required placeholder="e.g. Apex Sports Manufacturing Ltd">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Person *</label>
                            <input type="text" id="inpContactPerson" class="form-control form-control-sm" required placeholder="e.g. Rajesh Kumar">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Business Email *</label>
                            <input type="email" id="inpEmail" class="form-control form-control-sm" required placeholder="vendor@sportscompany.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number *</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="inpCountryCode" class="form-control" style="max-width: 70px;" value="+91">
                                <input type="text" id="inpPhone" class="form-control" required placeholder="9876543210">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Business Type *</label>
                            <select id="inpBusinessType" class="form-select form-select-sm" required>
                                <option value="">Select Business Type...</option>
                                <?php if (!empty($businessTypes)): foreach ($businessTypes as $bt): ?>
                                    <option value="<?= $bt['id'] ?>"><?= htmlspecialchars($bt['name']) ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="initialStatusCol">
                            <label class="form-label small fw-bold">Initial Approval Status</label>
                            <select id="inpInitialApproval" class="form-select form-select-sm">
                                <option value="APPROVED">Approved (Direct Activation)</option>
                                <option value="PENDING_APPROVAL">Pending Review</option>
                                <option value="PENDING_VERIFICATION">Pending Verification</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Admin creation assigns the Supplier role securely.</small>
                        </div>

                        <div class="col-md-6 d-none" id="editStatusCol">
                            <label class="form-label small fw-bold">Account Status</label>
                            <select id="inpAccountStatus" class="form-select form-select-sm">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Active suppliers can access portal and publish live products.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Street Address</label>
                            <input type="text" id="inpAddress" class="form-control form-control-sm" placeholder="Plot 42, Industrial Area, Phase II">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Country *</label>
                            <select id="inpCountry" class="form-select form-select-sm" required onchange="const p = $(this).find(':selected').data('phone'); if (p) $('#inpCountryCode').val(p);">
                                <option value="">Select Country...</option>
                                <?php if (!empty($countries)): foreach ($countries as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-flag="<?= htmlspecialchars($c['flag'] ?? '🌐') ?>" data-phone="<?= htmlspecialchars($c['phone_code'] ?? '') ?>">
                                        <?= $c['flag'] ?? '🌐' ?> <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone_code'] ?? '') ?>)
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">State / Province *</label>
                            <input type="text" id="inpState" class="form-control form-control-sm" required placeholder="e.g. Uttar Pradesh">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City *</label>
                            <input type="text" id="inpCity" class="form-control form-control-sm" required placeholder="e.g. Meerut">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Postal Code</label>
                            <input type="text" id="inpPostalCode" class="form-control form-control-sm" placeholder="250001">
                        </div>

                        <!-- Certification Tags Assignment -->
                        <div class="col-12 mt-2">
                            <label class="form-label small fw-bold">Supplier Certification Tags</label>
                            <div class="p-2.5 rounded-3 bg-light border d-flex flex-wrap gap-2">
                                <?php if (!empty($certificationTags)): foreach ($certificationTags as $tag): ?>
                                    <label class="form-check-label badge border rounded-pill d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 text-dark fw-semibold" style="cursor:pointer;background:#fff;">
                                        <input type="checkbox" class="form-check-input supplier-tag-check mt-0 me-1" value="<?= $tag['id'] ?>" id="supTag_<?= $tag['id'] ?>">
                                        <span><?= htmlspecialchars($tag['icon'] ?? '🏷️') ?> <?= htmlspecialchars($tag['name']) ?></span>
                                    </label>
                                <?php endforeach; else: ?>
                                    <span class="text-muted small">No active certification tags found. Manage them in Masters.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2.5">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveSupplier" class="btn btn-primary btn-sm px-3">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Supplier Details Modal -->
<div class="modal fade" id="supplierDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6" id="detailModalTitle">Supplier Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="supplierDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer bg-light border-top py-2.5" id="detailModalFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold fs-6">Reject Supplier Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" onsubmit="submitReject(event)">
                <input type="hidden" id="rejectSupplierId">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Please specify the exact reason for rejecting this supplier application. This feedback will be emailed to the manufacturer.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mandatory Rejection Reason *</label>
                        <textarea id="rejectionReason" class="form-control" rows="4" required placeholder="e.g. GST documentation incomplete, manufacturer address could not be verified..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2.5">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Review Supplier Profile Changes Modal -->
<div class="modal fade" id="profileReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-shield-check text-warning me-2"></i>Review Factory Profile Revision</h5>
                    <small class="text-muted" id="prModalSubtitle">Audit proposed manufacturer profile updates before approving</small>
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
                        <span class="badge bg-primary-subtle text-primary border px-2.5 py-1.5" id="credUserRole">SUPPLIER</span>
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
                            <i class="bi bi-info-circle me-1"></i>Copy this password to log in via the regular seller portal.
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
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Seller Login Page
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

<script>
let suppliersDt = null;
const csrfToken = '<?= htmlspecialchars(CSRF::token()) ?>';
const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', function() {
    suppliersDt = $('#suppliersTable').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
            url: `${baseUrl}/api/admin/suppliers/data`,
            data: function(d) {
                d.status_filter = $('#statusFilter').val();
            }
        },
        order: [[0, 'desc']],
        columns: [
            {
                data: 'company_name',
                render: function(data, type, row) {
                    return `<div class="fw-bold text-dark">${escapeHtml(data)}</div><small class="text-muted">${escapeHtml(row.address || '')}</small>`;
                }
            },
            { data: 'contact_person' },
            { 
                data: 'business_type_name',
                render: function(data) {
                    return `<span class="badge bg-light text-dark border">${escapeHtml(data || 'Manufacturer')}</span>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `${escapeHtml(row.city_name || '')}, ${escapeHtml(row.state_name || '')}<br><small class="text-muted">${escapeHtml(row.country_name || '')}</small>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `<div><i class="bi bi-envelope text-muted me-1"></i>${escapeHtml(row.email)}</div><small class="text-muted"><i class="bi bi-telephone text-muted me-1"></i>${escapeHtml(row.country_code || '')} ${escapeHtml(row.phone || '')}</small>`;
                }
            },
            {
                data: 'approval_status',
                render: function(data, type, row) {
                    let accBadge = (row.status === 'ACTIVE')
                        ? '<span class="badge rounded-pill me-1" style="background:#dcfce7;color:#15803d;"><i class="bi bi-check-circle-fill me-1"></i>ACTIVE</span>'
                        : '<span class="badge rounded-pill me-1" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-x-circle-fill me-1"></i>INACTIVE</span>';
                    
                    let appBadge = '';
                    switch (data) {
                        case 'APPROVED':
                            appBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-patch-check me-1"></i>Approved</span>';
                            break;
                        case 'PENDING_APPROVAL':
                            appBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-top me-1"></i>Pending Review</span>';
                            break;
                        case 'REJECTED':
                            appBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Rejected</span>';
                            break;
                        case 'SUSPENDED':
                            appBadge = '<span class="badge bg-secondary text-white"><i class="bi bi-pause-circle me-1"></i>Suspended</span>';
                            break;
                        default:
                            appBadge = `<span class="badge bg-light text-muted border">${escapeHtml(data)}</span>`;
                    }
                    return `<div class="d-flex flex-column gap-1">${accBadge}${appBadge}</div>`;
                }
            },
            {
                data: 'product_count',
                render: function(data) {
                    return `<span class="badge bg-info-subtle text-info border">${data || 0} items</span>`;
                }
            },
            {
                data: null,
                className: 'text-end text-nowrap',
                orderable: false,
                render: function(data, type, row) {
                    let actions = '';

                    if (row.pending_profile_change_id) {
                        actions += `<button class="btn btn-sm btn-warning text-dark fw-bold py-1 px-2.5 me-1.5 shadow-xs position-relative" onclick="openProfileReviewModal(${row.pending_profile_change_id})" title="Review pending factory profile changes" style="border-radius:6px; font-size:12px;"><i class="bi bi-clock-history me-1"></i>Review Changes<span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-white rounded-circle"></span></button>`;
                    }

                    actions += `<button class="btn btn-sm btn-outline-primary py-1 px-2 me-1" onclick="viewSupplier(${row.id})" title="View Details"><i class="bi bi-eye"></i></button>`;
                    actions += `<button class="btn btn-sm btn-outline-secondary py-1 px-2 me-1" onclick="editSupplier(${row.id})" title="Edit Details"><i class="bi bi-pencil-square"></i></button>`;
                    actions += `<button class="btn btn-sm btn-outline-primary py-1 px-2 me-1" onclick="showCredentialsModal(${row.id}, '${escapeQuotes(row.company_name)}', 'SUPPLIER')" title="View Password & Login Credentials"><i class="bi bi-key-fill"></i></button>`;

                    const isActive = row.status === 'ACTIVE';
                    const toggleTitle = isActive ? 'Deactivate Supplier Account' : 'Activate Supplier Account';
                    const toggleBtnClass = isActive ? 'btn-outline-danger' : 'btn-outline-success';
                    const toggleIcon = isActive ? 'bi-slash-circle' : 'bi-check-circle';
                    actions += `<button class="btn btn-sm ${toggleBtnClass} py-1 px-2 me-1" onclick="toggleSupplierStatus(${row.id}, '${isActive ? 'INACTIVE' : 'ACTIVE'}', '${escapeQuotes(row.company_name)}')" title="${toggleTitle}"><i class="bi ${toggleIcon}"></i></button>`;

                    if (row.approval_status === 'PENDING_APPROVAL' || row.approval_status === 'REJECTED') {
                        actions += `<button class="btn btn-sm btn-success py-1 px-2 me-1" onclick="approveSupplier(${row.id})" title="Approve"><i class="bi bi-check-lg"></i></button>`;
                    }

                    if (row.approval_status === 'PENDING_APPROVAL') {
                        actions += `<button class="btn btn-sm btn-outline-danger py-1 px-2 me-1" onclick="openRejectModal(${row.id})" title="Reject"><i class="bi bi-x-lg"></i></button>`;
                    }

                    actions += `<button class="btn btn-sm btn-outline-danger py-1 px-2" onclick="deleteSupplier(${row.id}, '${escapeQuotes(row.company_name)}')" title="Delete"><i class="bi bi-trash"></i></button>`;

                    return actions;
                }
            }
        ]
    });

    $('#statusFilter').on('change', function() {
        suppliersDt.ajax.reload();
    });
});

function escapeQuotes(str) {
    return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function loadStates(countryId, selectedStateId = null, callback = null) {
    const $st = $('#inpState');
    $st.html('<option value="">Loading states...</option>');
    $('#inpCity').html('<option value="">Select City...</option>');

    if (!countryId) {
        $st.html('<option value="">Select State...</option>');
        return;
    }

    fetch(`${baseUrl}/api/public/states-by-country/${countryId}`)
        .then(r => r.json())
        .then(res => {
            let opts = '<option value="">Select State...</option>';
            if (res.success && res.data) {
                res.data.forEach(s => {
                    const sel = (selectedStateId && selectedStateId == s.id) ? 'selected' : '';
                    opts += `<option value="${s.id}" ${sel}>${s.name}</option>`;
                });
            }
            $st.html(opts);
            if (callback) callback();
        });
}

function loadCities(stateId, selectedCityId = null) {
    const $ct = $('#inpCity');
    $ct.html('<option value="">Loading cities...</option>');

    if (!stateId) {
        $ct.html('<option value="">Select City...</option>');
        return;
    }

    fetch(`${baseUrl}/api/public/cities-by-state/${stateId}`)
        .then(r => r.json())
        .then(res => {
            let opts = '<option value="">Select City...</option>';
            if (res.success && res.data) {
                res.data.forEach(c => {
                    const sel = (selectedCityId && selectedCityId == c.id) ? 'selected' : '';
                    opts += `<option value="${c.id}" ${sel}>${c.name}</option>`;
                });
            }
            $ct.html(opts);
        });
}

function openAddSupplierModal() {
    $('#formSupplierId').val('');
    $('#supplierModalTitle').text('Add Supplier Manually');
    $('#btnSaveSupplier').text('Create Supplier');
    $('#initialStatusCol').removeClass('d-none');
    $('#editStatusCol').addClass('d-none');
    $('#supplierFormAlert').addClass('d-none').text('');

    $('#inpCompanyName').val('');
    $('#inpContactPerson').val('');
    $('#inpEmail').val('');
    $('#inpPhone').val('');
    $('#inpCountryCode').val('+91');
    $('#inpBusinessType').val('');
    $('#inpAddress').val('');
    $('#inpCountry').val('');
    $('#inpState').val('');
    $('#inpCity').val('');
    $('#inpPostalCode').val('');
    $('#inpInitialApproval').val('APPROVED');
    $('.supplier-tag-check').prop('checked', false);

    const modal = new bootstrap.Modal(document.getElementById('supplierFormModal'));
    modal.show();
}

function editSupplier(id) {
    $('#supplierFormAlert').addClass('d-none').text('');
    $('#formSupplierId').val(id);
    $('#supplierModalTitle').text('Edit Supplier Details');
    $('#btnSaveSupplier').text('Update Details');
    $('#initialStatusCol').addClass('d-none');
    $('#editStatusCol').removeClass('d-none');

    fetch(`${baseUrl}/api/admin/suppliers/${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert(res.message || 'Error fetching supplier');
                return;
            }
            const s = res.data;
            $('#inpCompanyName').val(s.company_name);
            $('#inpContactPerson').val(s.contact_person);
            $('#inpEmail').val(s.email);
            $('#inpPhone').val(s.phone || '');
            $('#inpCountryCode').val(s.country_code || '+91');
            $('#inpBusinessType').val(s.business_type_id);
            $('#inpAddress').val(s.address || '');
            $('#inpPostalCode').val(s.postal_code || '');
            $('#inpCountry').val(s.country_id);
            $('#inpState').val(s.state || s.state_name || '');
            $('#inpCity').val(s.city || s.city_name || '');
            $('#inpAccountStatus').val(s.status || 'ACTIVE');

            $('.supplier-tag-check').prop('checked', false);
            if (s.assigned_tag_ids && Array.isArray(s.assigned_tag_ids)) {
                s.assigned_tag_ids.forEach(tId => {
                    $(`#supTag_${tId}`).prop('checked', true);
                });
            }

            const modal = new bootstrap.Modal(document.getElementById('supplierFormModal'));
            modal.show();
        });
}

function submitSupplierForm(e) {
    e.preventDefault();
    const id = $('#formSupplierId').val();
    const isEdit = Boolean(id);

    const tagIds = $('.supplier-tag-check:checked').map(function() {
        return parseInt($(this).val());
    }).get();

    const payload = {
        company_name: $('#inpCompanyName').val(),
        contact_person: $('#inpContactPerson').val(),
        email: $('#inpEmail').val(),
        phone: $('#inpPhone').val(),
        country_code: $('#inpCountryCode').val(),
        business_type_id: $('#inpBusinessType').val(),
        address: $('#inpAddress').val(),
        country_id: $('#inpCountry').val(),
        state: $('#inpState').val(),
        city: $('#inpCity').val(),
        postal_code: $('#inpPostalCode').val(),
        tag_ids: tagIds,
        _csrf: csrfToken,
    };

    if (isEdit) {
        payload.status = $('#inpAccountStatus').val();
    } else {
        payload.approval_status = $('#inpInitialApproval').val();
    }

    const url = isEdit ? `${baseUrl}/api/admin/suppliers/${id}` : `${baseUrl}/api/admin/suppliers`;
    const method = 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('supplierFormModal')).hide();
            alert(res.message);
            suppliersDt.ajax.reload(null, false);
        } else {
            $('#supplierFormAlert').removeClass('d-none').text(res.message || 'Submission error');
        }
    })
    .catch(err => {
        $('#supplierFormAlert').removeClass('d-none').text('Server communication error');
    });
}

function setSupplierStatusFilter(status) {
    $('#statusFilter').val(status).trigger('change');
}

function updateSupplierMetrics(metrics) {
    if (!metrics) return;
    if (metrics.total !== undefined) $('#metricTotalVendors').text(metrics.total);
    if (metrics.pending !== undefined) $('#metricPendingReview').text(metrics.pending);
    if (metrics.approved !== undefined) $('#metricApproved').text(metrics.approved);
    if (metrics.inactive !== undefined) $('#metricInactive').text(metrics.inactive);
    if (metrics.rejected !== undefined || metrics.suspended !== undefined) {
        const rej = parseInt(metrics.rejected || 0, 10);
        const susp = parseInt(metrics.suspended || 0, 10);
        $('#metricRejectedSuspended').text(rej + susp);
    }
}

function toggleSupplierStatus(id, targetStatus, companyName) {
    const isDeactivating = targetStatus === 'INACTIVE';
    const actionText = isDeactivating ? 'Deactivate' : 'Activate';
    if (!confirm(`${actionText} supplier "${companyName}"? ${isDeactivating ? 'This will immediately block product publishing and supplier portal access.' : 'This will restore full portal access and product visibility.'}`)) {
        return;
    }

    fetch(`${baseUrl}/api/admin/suppliers/${id}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ status: targetStatus })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            if (res.metrics) {
                updateSupplierMetrics(res.metrics);
            }
            suppliersDt.ajax.reload(null, false);
        } else {
            alert(res.message || 'Error updating supplier status.');
        }
    })
    .catch(err => {
        alert('Server communication error.');
    });
}

function viewSupplier(id) {
    const modal = new bootstrap.Modal(document.getElementById('supplierDetailModal'));
    modal.show();

    $('#supplierDetailBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');

    fetch(`${baseUrl}/api/admin/suppliers/${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                $('#supplierDetailBody').html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }

            const s = res.data;
            $('#detailModalTitle').text(`${s.company_name} — Profile`);

            let html = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Company Name</label>
                        <div class="fw-bold">${s.company_name}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Contact Person</label>
                        <div>${s.contact_person}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Business Email</label>
                        <div>${s.email}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Phone Number</label>
                        <div>${s.country_code || '+91'} ${s.phone || 'N/A'}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Business Type</label>
                        <div>${s.business_type_name || 'Manufacturer'}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Approval Status</label>
                        <div><span class="badge bg-primary">${s.approval_status}</span></div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small fw-bold">Factory Street Address</label>
                        <div>${s.address || 'N/A'}, ${s.city_name || s.city || ''}, ${s.state_name || s.state || ''}, ${s.country_name || ''} - ${s.postal_code || ''}</div>
                    </div>
                    ${s.rejection_reason ? `
                    <div class="col-12">
                        <div class="alert alert-danger p-2 small mb-0">
                            <strong>Rejection / Suspension Reason:</strong> ${s.rejection_reason}
                        </div>
                    </div>` : ''}
                </div>
            `;
            $('#supplierDetailBody').html(html);
        });
}

function approveSupplier(id) {
    if (!confirm('Are you sure you want to APPROVE this manufacturer? They will be granted full product management access.')) {
        return;
    }

    fetch(`${baseUrl}/api/admin/suppliers/${id}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            if (res.metrics) updateSupplierMetrics(res.metrics);
            suppliersDt.ajax.reload(null, false);
        } else {
            alert(res.message || 'Error approving supplier.');
        }
    });
}

function openRejectModal(id) {
    $('#rejectSupplierId').val(id);
    $('#rejectionReason').val('');
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

function submitReject(e) {
    e.preventDefault();
    const id = $('#rejectSupplierId').val();
    const reason = $('#rejectionReason').val();

    fetch(`${baseUrl}/api/admin/suppliers/${id}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
            alert(res.message);
            if (res.metrics) updateSupplierMetrics(res.metrics);
            suppliersDt.ajax.reload(null, false);
        } else {
            alert(res.message || 'Error rejecting supplier.');
        }
    });
}

function suspendSupplier(id) {
    const reason = prompt('Please enter the reason for suspending this supplier:');
    if (!reason) return;

    fetch(`${baseUrl}/api/admin/suppliers/${id}/suspend`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            if (res.metrics) updateSupplierMetrics(res.metrics);
            suppliersDt.ajax.reload(null, false);
        } else {
            alert(res.message || 'Error suspending supplier.');
        }
    });
}

function reactivateSupplier(id) {
    if (!confirm('Reactivate this supplier account?')) return;

    fetch(`${baseUrl}/api/admin/suppliers/${id}/reactivate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            if (res.metrics) updateSupplierMetrics(res.metrics);
            suppliersDt.ajax.reload(null, false);
        } else {
            alert(res.message || 'Error reactivating supplier.');
        }
    });
}

function deleteSupplier(id, companyName) {
    if (!confirm(`Are you sure you want to delete supplier "${companyName}"?\nNote: Deletion will be rejected if this supplier owns products or customer enquiries.`)) {
        return;
    }

    fetch(`${baseUrl}/api/admin/suppliers/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            if (res.metrics) updateSupplierMetrics(res.metrics);
            suppliersDt.ajax.reload(function () {
                const info = suppliersDt.page.info();
                if (info && info.pages > 0 && info.page >= info.pages) {
                    suppliersDt.page(Math.max(0, info.pages - 1)).draw('page');
                }
            }, false);
        } else {
            alert(res.message || 'Error deleting supplier.');
        }
    });
}

function openProfileReviewModal(requestId) {
    const modalEl = document.getElementById('profileReviewModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    $('#prModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    $('#prModalActions').html('');

    $.ajax({
        url: `${baseUrl}/api/admin/profile-requests/${requestId}`,
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        dataType: 'json',
        success: function(res) {
            if (!res.success || !res.data) {
                $('#prModalBody').html(`<div class="alert alert-danger">${escapeHtml(res.message || 'Error loading request.')}</div>`);
                return;
            }

            const r = res.data;
            const cur = r.decoded_current || {};
            const req = r.decoded_requested || {};

            $('#prModalSubtitle').text(`Revision #${r.id} for ${r.supplier_company || r.user_name || r.user_email} (Submitted ${r.created_at})`);

            let diffRows = '';
            const allKeys = Array.from(new Set([...Object.keys(cur), ...Object.keys(req)]));

            allKeys.forEach(k => {
                let oldVal = cur[k] !== undefined && cur[k] !== null ? String(cur[k]) : '—';
                let newVal = req[k] !== undefined && req[k] !== null ? String(req[k]) : '—';
                const changed = (oldVal !== newVal);
                const label = k.replace(/_/g, ' ').toUpperCase();

                if (k === 'logo') {
                    if (oldVal !== '—') oldVal = `<img src="${baseUrl}${oldVal}" style="max-height:40px;" class="rounded border">`;
                    if (newVal !== '—') newVal = `<img src="${baseUrl}${newVal}" style="max-height:40px;" class="rounded border">`;
                }

                diffRows += `
                    <tr class="${changed ? 'table-warning' : ''}">
                        <td class="fw-bold text-dark">${escapeHtml(label)}</td>
                        <td class="text-muted">${k === 'logo' ? oldVal : escapeHtml(oldVal)}</td>
                        <td class="${changed ? 'fw-bold text-primary' : 'text-muted'}">
                            ${k === 'logo' ? newVal : escapeHtml(newVal)}
                            ${changed ? '<span class="badge bg-warning text-dark ms-1">Modified</span>' : ''}
                        </td>
                    </tr>
                `;
            });

            const html = `
                <div class="mb-3">
                    <span class="badge bg-secondary mb-2">${escapeHtml(r.role)} FACTORY ACCOUNT</span>
                    <p class="text-muted small mb-0">The manufacturer submitted updated business profile details. Review the side-by-side comparison below:</p>
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
                    <label class="form-label small fw-bold text-dark">Rejection Feedback (Required only if rejecting):</label>
                    <input type="text" id="prRejectReason" class="form-control form-control-sm" placeholder="e.g. Please provide valid business registration and facility address.">
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
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message || 'Failed to load profile revision details.';
            $('#prModalBody').html(`<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>${escapeHtml(msg)}</div>`);
        }
    });
}

function approveProfileRequest(requestId) {
    Swal.fire({
        title: 'Approve Profile Changes?',
        text: 'This will publish the factory profile changes live immediately.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Approve & Publish'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: `${baseUrl}/api/admin/profile-requests/${requestId}/approve`,
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: { _csrf: csrfToken },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire('Approved!', res.message || 'Profile changes published live.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('profileReviewModal')).hide();
                    suppliersDt.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', res.message || 'Approval failed.', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Server error occurred during approval.', 'error');
            }
        });
    });
}

function rejectProfileRequest(requestId) {
    const reason = $('#prRejectReason').val();
    if (!reason || reason.trim() === '') {
        Swal.fire('Notice', 'Please specify a rejection reason for user feedback.', 'warning');
        $('#prRejectReason').focus();
        return;
    }

    Swal.fire({
        title: 'Reject Profile Changes?',
        text: 'The factory profile will remain unchanged, and feedback will be recorded.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Reject Revision'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: `${baseUrl}/api/admin/profile-requests/${requestId}/reject`,
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: { _csrf: csrfToken, reason: reason },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire('Rejected', res.message || 'Profile revision rejected.', 'info');
                    bootstrap.Modal.getInstance(document.getElementById('profileReviewModal')).hide();
                    suppliersDt.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', res.message || 'Rejection failed.', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Server error occurred during rejection.', 'error');
            }
        });
    });
}

// Prevent 'Blocked aria-hidden on an element because its descendant retained focus'
$(document).on('hide.bs.modal', '.modal', function () {
    if (document.activeElement && this.contains(document.activeElement)) {
        document.activeElement.blur();
    }
});

function showCredentialsModal(id, nameOrCompany, type) {
    const apiBase = (typeof baseUrl !== 'undefined') ? baseUrl : '';
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

    // Default password visibility to plain text so Admin immediately sees the password
    const passInput = document.getElementById('credPassword');
    passInput.type = 'text';
    const passIcon = document.getElementById('toggleCredPassIcon');
    if (passIcon) {
        passIcon.className = 'bi bi-eye-slash';
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
        document.getElementById('credUserRole').textContent = (user.role === 'SUPPLIER') ? 'SELLER / SUPPLIER' : (user.role || 'USER');
        document.getElementById('credEmail').value = user.email || '';
        document.getElementById('credTargetUserId').value = user.id;

        const loginUrl = user.login_url ? `${apiBase}${user.login_url}` : `${apiBase}/seller/login`;
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

    const apiBase = (typeof baseUrl !== 'undefined') ? baseUrl : '';
    const token = (typeof csrfToken !== 'undefined') ? csrfToken : '';

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
            feedback.innerHTML = `<span class="text-danger">${escapeQuotes(data.message || 'Failed to update password.')}</span>`;
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        feedback.innerHTML = '<span class="text-danger">Network error while updating password.</span>';
    });
}
</script>
