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
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Total Vendors</span>
                            <h3 class="fw-bold m-0 mt-1"><?= (int)($metrics['total'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2.5 rounded-3">
                            <i class="bi bi-buildings fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-warning small fw-bold text-uppercase">Pending Review</span>
                            <h3 class="fw-bold m-0 mt-1 text-warning"><?= (int)($metrics['pending'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-warning-subtle text-warning p-2.5 rounded-3">
                            <i class="bi bi-hourglass-top fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-success small fw-bold text-uppercase">Approved Facilities</span>
                            <h3 class="fw-bold m-0 mt-1 text-success"><?= (int)($metrics['approved'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-success-subtle text-success p-2.5 rounded-3">
                            <i class="bi bi-patch-check-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-danger small fw-bold text-uppercase">Rejected / Suspended</span>
                            <h3 class="fw-bold m-0 mt-1 text-danger"><?= (int)(($metrics['rejected'] ?? 0) + ($metrics['suspended'] ?? 0)) ?></h3>
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
                <select id="statusFilter" class="form-select form-select-sm" style="width: 180px;">
                    <option value="">All Statuses</option>
                    <option value="PENDING_APPROVAL">Pending Review</option>
                    <option value="APPROVED">Approved</option>
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

                        <div class="col-12">
                            <label class="form-label small fw-bold">Street Address</label>
                            <input type="text" id="inpAddress" class="form-control form-control-sm" placeholder="Plot 42, Industrial Area, Phase II">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Country *</label>
                            <select id="inpCountry" class="form-select form-select-sm" required onchange="loadStates(this.value)">
                                <option value="">Select Country...</option>
                                <?php if (!empty($countries)): foreach ($countries as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">State / Province *</label>
                            <select id="inpState" class="form-select form-select-sm" required onchange="loadCities(this.value)">
                                <option value="">Select State...</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City *</label>
                            <select id="inpCity" class="form-select form-select-sm" required>
                                <option value="">Select City...</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Postal Code</label>
                            <input type="text" id="inpPostalCode" class="form-control form-control-sm" placeholder="250001">
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

<script>
let suppliersDt = null;
const csrfToken = '<?= htmlspecialchars(CSRF::token()) ?>';
const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';

document.addEventListener('DOMContentLoaded', function() {
    suppliersDt = $('#suppliersTable').DataTable({
        processing: true,
        serverSide: true,
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
                    return `<div class="fw-bold text-dark">${data}</div><small class="text-muted">${row.address || ''}</small>`;
                }
            },
            { data: 'contact_person' },
            { 
                data: 'business_type_name',
                render: function(data) {
                    return `<span class="badge bg-light text-dark border">${data || 'Manufacturer'}</span>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.city_name || ''}, ${row.state_name || ''}<br><small class="text-muted">${row.country_name || ''}</small>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `<div><i class="bi bi-envelope text-muted me-1"></i>${row.email}</div><small class="text-muted"><i class="bi bi-telephone text-muted me-1"></i>${row.country_code || ''} ${row.phone || ''}</small>`;
                }
            },
            {
                data: 'approval_status',
                render: function(data) {
                    switch (data) {
                        case 'APPROVED':
                            return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Approved</span>';
                        case 'PENDING_APPROVAL':
                            return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-top me-1"></i>Pending Review</span>';
                        case 'REJECTED':
                            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Rejected</span>';
                        case 'SUSPENDED':
                            return '<span class="badge bg-secondary text-white"><i class="bi bi-pause-circle me-1"></i>Suspended</span>';
                        default:
                            return `<span class="badge bg-light text-muted border">${data}</span>`;
                    }
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
                    let actions = `<button class="btn btn-sm btn-outline-primary py-1 px-2 me-1" onclick="viewSupplier(${row.id})" title="View Details"><i class="bi bi-eye"></i></button>`;
                    actions += `<button class="btn btn-sm btn-outline-secondary py-1 px-2 me-1" onclick="editSupplier(${row.id})" title="Edit Details"><i class="bi bi-pencil-square"></i></button>`;

                    if (row.approval_status === 'PENDING_APPROVAL' || row.approval_status === 'REJECTED') {
                        actions += `<button class="btn btn-sm btn-success py-1 px-2 me-1" onclick="approveSupplier(${row.id})" title="Approve"><i class="bi bi-check-lg"></i></button>`;
                    }

                    if (row.approval_status === 'PENDING_APPROVAL') {
                        actions += `<button class="btn btn-sm btn-outline-danger py-1 px-2 me-1" onclick="openRejectModal(${row.id})" title="Reject"><i class="bi bi-x-lg"></i></button>`;
                    }

                    if (row.approval_status === 'APPROVED') {
                        actions += `<button class="btn btn-sm btn-warning py-1 px-2 me-1" onclick="suspendSupplier(${row.id})" title="Suspend"><i class="bi bi-pause-circle"></i></button>`;
                    }

                    if (row.approval_status === 'SUSPENDED') {
                        actions += `<button class="btn btn-sm btn-success py-1 px-2 me-1" onclick="reactivateSupplier(${row.id})" title="Reactivate"><i class="bi bi-play-circle"></i></button>`;
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
    $('#supplierFormAlert').addClass('d-none').text('');

    $('#inpCompanyName').val('');
    $('#inpContactPerson').val('');
    $('#inpEmail').val('');
    $('#inpPhone').val('');
    $('#inpCountryCode').val('+91');
    $('#inpBusinessType').val('');
    $('#inpAddress').val('');
    $('#inpCountry').val('');
    $('#inpState').html('<option value="">Select State...</option>');
    $('#inpCity').html('<option value="">Select City...</option>');
    $('#inpPostalCode').val('');
    $('#inpInitialApproval').val('APPROVED');

    const modal = new bootstrap.Modal(document.getElementById('supplierFormModal'));
    modal.show();
}

function editSupplier(id) {
    $('#supplierFormAlert').addClass('d-none').text('');
    $('#formSupplierId').val(id);
    $('#supplierModalTitle').text('Edit Supplier Details');
    $('#btnSaveSupplier').text('Update Details');
    $('#initialStatusCol').addClass('d-none'); // Don't alter approval state via profile edit

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

            loadStates(s.country_id, s.state_id, function() {
                loadCities(s.state_id, s.city_id);
            });

            const modal = new bootstrap.Modal(document.getElementById('supplierFormModal'));
            modal.show();
        });
}

function submitSupplierForm(e) {
    e.preventDefault();
    const id = $('#formSupplierId').val();
    const isEdit = Boolean(id);

    const payload = {
        company_name: $('#inpCompanyName').val(),
        contact_person: $('#inpContactPerson').val(),
        email: $('#inpEmail').val(),
        phone: $('#inpPhone').val(),
        country_code: $('#inpCountryCode').val(),
        business_type_id: $('#inpBusinessType').val(),
        address: $('#inpAddress').val(),
        country_id: $('#inpCountry').val(),
        state_id: $('#inpState').val(),
        city_id: $('#inpCity').val(),
        postal_code: $('#inpPostalCode').val(),
        _csrf: csrfToken,
    };

    if (!isEdit) {
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
            suppliersDt.ajax.reload();
        } else {
            $('#supplierFormAlert').removeClass('d-none').text(res.message || 'Submission error');
        }
    })
    .catch(err => {
        $('#supplierFormAlert').removeClass('d-none').text('Server communication error');
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
                        <div>${s.address || 'N/A'}, ${s.city_name || ''}, ${s.state_name || ''}, ${s.country_name || ''} - ${s.postal_code || ''}</div>
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
            suppliersDt.ajax.reload();
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
            suppliersDt.ajax.reload();
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
            suppliersDt.ajax.reload();
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
            suppliersDt.ajax.reload();
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
            suppliersDt.ajax.reload();
        } else {
            alert(res.message || 'Error deleting supplier.');
        }
    });
}
</script>
