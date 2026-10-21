<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-teal bg-opacity-10 text-teal px-2 py-1 rounded-2 fw-bold" style="font-size: 11px; color: #0f766e !important; background-color: #f0fdfa !important; border: 1px solid #ccfbf1;">PROTOTYPE EVALUATION DESK</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Prototype Sample Enquiries (5-7 Days Dispatch)</h3>
        <p class="text-slate-500 small mb-0">Dedicated tracking, courier dispatch coordination, and prototype specifications for physical product samples.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Filtered Results
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportSampleEnquiries('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportSampleEnquiries('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportSampleEnquiries('pdf'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Print / Save PDF</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-chat-left-quote me-1"></i> All Enquiries Hub
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('ALL')">
            <div class="text-slate-500 small fw-semibold">Total Sample Requests</div>
            <h4 class="fw-bold text-slate-900 mb-0 mt-1" id="mTotal"><?= (int)($metrics['total'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('PENDING')">
            <div class="text-slate-500 small fw-semibold">Pending Review</div>
            <h4 class="fw-bold text-warning mb-0 mt-1" style="color: #b45309 !important;" id="mPending"><?= (int)($metrics['pending'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('IN_PRODUCTION')">
            <div class="text-slate-500 small fw-semibold">In Production</div>
            <h4 class="fw-bold text-primary mb-0 mt-1" id="mInProduction"><?= (int)($metrics['in_production'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('SAMPLE_SHIPPED')">
            <div class="text-slate-500 small fw-semibold">Sample Shipped</div>
            <h4 class="fw-bold text-teal mb-0 mt-1" style="color: #0f766e !important;" id="mShipped"><?= (int)($metrics['shipped'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('DELIVERED')">
            <div class="text-slate-500 small fw-semibold">Delivered</div>
            <h4 class="fw-bold text-success mb-0 mt-1" id="mDelivered"><?= (int)($metrics['delivered'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('REJECTED')">
            <div class="text-slate-500 small fw-semibold">Rejected / Cancelled</div>
            <h4 class="fw-bold text-danger mb-0 mt-1" id="mRejected"><?= (int)($metrics['rejected'] ?? 0) ?></h4>
        </div>
    </div>
</div>

<!-- Multi-Filter & Search Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <!-- Searchable Supplier Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Filter by Supplier</label>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="supplierSearchBox" class="form-control" placeholder="Search supplier...">
                </div>
                <select id="filterSupplier" class="form-select form-select-sm">
                    <option value="ALL">All Suppliers &amp; Unassigned</option>
                    <option value="UNASSIGNED">Unassigned Enquiries Only</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= (int)$sup['id'] ?>" data-search="<?= htmlspecialchars(strtolower(($sup['company_name'] ?? '') . ' ' . ($sup['contact_person'] ?? '') . ' ' . ($sup['email'] ?? ''))) ?>">
                            <?= htmlspecialchars($sup['company_name']) ?> (#SUP-<?= (int)$sup['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Enquiry Status Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Sample Order Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <option value="PENDING">Pending / New</option>
                    <option value="IN_PRODUCTION">In Production</option>
                    <option value="SAMPLE_SHIPPED">Sample Shipped</option>
                    <option value="DELIVERED">Delivered</option>
                    <option value="REJECTED">Rejected</option>
                </select>
            </div>

            <!-- Date Range Filters -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">From Date</label>
                <input type="date" id="filterStartDate" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">To Date</label>
                <input type="date" id="filterEndDate" class="form-control form-control-sm">
            </div>

            <!-- Actions -->
            <div class="col-md-2 d-flex gap-2">
                <button type="button" id="btnApplyFilters" class="btn btn-primary btn-sm w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
                <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm fw-semibold" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card card-premium">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="sampleEnquiriesTable" class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">Sample ID</th>
                        <th style="width: 110px;">Date</th>
                        <th>Buyer &amp; Company</th>
                        <th>Product</th>
                        <th>Manufacturer</th>
                        <th style="width: 90px;">Qty</th>
                        <th>Delivery Address</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 80px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sample Details & Dispatch Modal -->
<div class="modal fade" id="sampleDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div>
                    <span class="badge bg-teal text-white fw-bold px-2 py-0.5 rounded-pill mb-1" style="background-color: #0f766e !important;">PROTOTYPE SAMPLE REQUEST</span>
                    <h5 class="modal-title fw-bold text-slate-900 mb-0" id="detailModalTitle">Sample Order Details</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-teal" role="status"></div>
                    <div class="text-muted small mt-2">Loading sample specifications...</div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2" id="detailModalActions"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?= $baseUrl ?>';
    const csrfToken = '<?= $csrfToken ?>';

    // Supplier Search in Dropdown
    const supplierSearchBox = document.getElementById('supplierSearchBox');
    const filterSupplier = document.getElementById('filterSupplier');
    if (supplierSearchBox && filterSupplier) {
        supplierSearchBox.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            Array.from(filterSupplier.options).forEach(opt => {
                if (opt.value === 'ALL' || opt.value === 'UNASSIGNED') {
                    opt.style.display = '';
                    return;
                }
                const searchTxt = (opt.dataset.search || opt.textContent).toLowerCase();
                opt.style.display = searchTxt.includes(query) ? '' : 'none';
            });
        });
    }

    // Initialize DataTable
    const table = $('#sampleEnquiriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: `${baseUrl}/api/admin/sample-enquiries/data`,
            type: 'GET',
            data: function(d) {
                d.supplier_id = $('#filterSupplier').val();
                d.status      = $('#filterStatus').val();
                d.start_date  = $('#filterStartDate').val();
                d.end_date    = $('#filterEndDate').val();
            }
        },
        columns: [
            {
                data: 'id',
                render: function(data) {
                    const idStr = String(data).padStart(5, '0');
                    return `<span class="badge bg-light text-dark border font-monospace fw-bold">#SMP-${idStr}</span>`;
                }
            },
            {
                data: 'created_at',
                render: function(data) {
                    if (!data) return '—';
                    const dt = new Date(data.replace(' ', 'T'));
                    return `<div class="small fw-semibold text-slate-700">${dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</div><div class="text-muted" style="font-size: 11px;">${dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>`;
                }
            },
            {
                data: null,
                render: function(row) {
                    const name = row.buyer_name || 'Guest Buyer';
                    const company = row.company_name ? `<div class="text-slate-500 small"><i class="bi bi-building me-1"></i>${escapeHtml(row.company_name)}</div>` : '';
                    const email = row.buyer_email ? `<div class="text-muted" style="font-size: 11px;"><i class="bi bi-envelope me-1"></i>${escapeHtml(row.buyer_email)}</div>` : '';
                    const phone = row.buyer_phone ? `<div class="text-muted" style="font-size: 11px;"><i class="bi bi-telephone me-1"></i>${escapeHtml(row.buyer_phone)}</div>` : '';
                    return `<div class="fw-bold text-slate-800">${escapeHtml(name)}</div>${company}${email}${phone}`;
                }
            },
            {
                data: null,
                render: function(row) {
                    const title = row.product_name || row.subject || 'Custom Prototype';
                    const link = row.product_slug ? `<a href="${baseUrl}/products/${escapeHtml(row.product_slug)}" target="_blank" class="fw-semibold text-primary text-decoration-none">${escapeHtml(title)}</a>` : `<span class="fw-semibold text-slate-800">${escapeHtml(title)}</span>`;
                    const img = row.primary_image ? `<img src="${baseUrl}/${escapeHtml(row.primary_image)}" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;" onerror="this.style.display='none'">` : '';
                    return `<div class="d-flex align-items-center">${img}<div>${link}</div></div>`;
                }
            },
            {
                data: null,
                render: function(row) {
                    const supName = row.supplier_name || '<span class="badge bg-warning text-dark">Unassigned Factory</span>';
                    return `<div class="fw-semibold text-slate-800">${supName}</div>`;
                }
            },
            {
                data: null,
                render: function(row) {
                    const qty = row.target_quantity || 1;
                    const price = row.target_price ? `<div class="text-muted" style="font-size: 11px;">$${parseFloat(row.target_price).toFixed(2)}/pc</div>` : '';
                    return `<div class="fw-bold text-slate-800">${qty} pcs</div>${price}`;
                }
            },
            {
                data: null,
                render: function(row) {
                    const loc = row.delivery_location || row.country_name || 'Not provided';
                    return `<div class="small text-slate-700" style="max-width: 180px; white-space: normal;" title="${escapeHtml(loc)}"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${escapeHtml(loc)}</div>`;
                }
            },
            {
                data: 'status',
                render: function(status) {
                    const st = (status || 'PENDING').toUpperCase();
                    let cls = 'bg-secondary';
                    let label = st.replace('_', ' ');
                    if (st === 'PENDING' || st === 'SUBMITTED') {
                        cls = 'bg-warning text-dark';
                        label = 'Pending Review';
                    } else if (st === 'IN_PRODUCTION') {
                        cls = 'bg-primary text-white';
                        label = 'In Production';
                    } else if (st === 'SAMPLE_SHIPPED') {
                        cls = 'bg-teal text-white" style="background-color: #0f766e !important;';
                        label = 'Sample Shipped';
                    } else if (st === 'DELIVERED') {
                        cls = 'bg-success text-white';
                        label = 'Delivered';
                    } else if (st === 'REJECTED') {
                        cls = 'bg-danger text-white';
                        label = 'Rejected';
                    }
                    return `<span class="badge ${cls} fw-semibold">${label}</span>`;
                }
            },
            {
                data: 'id',
                orderable: false,
                className: 'text-end',
                render: function(id) {
                    return `
                        <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1 rounded-2" onclick="openSampleDetail(${id})" title="View Prototype Specifications & Tracking">
                            <i class="bi bi-eye"></i> View
                        </button>
                    `;
                }
            }
        ],
        order: [[1, 'desc']],
        pageLength: 15,
        language: {
            emptyTable: 'No prototype sample requests found matching the selected criteria.',
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading sample requests...'
        }
    });

    // Filter controls
    $('#btnApplyFilters').on('click', function() {
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#filterSupplier').val('ALL');
        $('#filterStatus').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        $('#supplierSearchBox').val('').trigger('input');
        table.ajax.reload();
    });

    window.setStatusFilter = function(status) {
        $('#filterStatus').val(status);
        table.ajax.reload();
    };

    window.exportSampleEnquiries = function(format) {
        const sup = $('#filterSupplier').val();
        const st  = $('#filterStatus').val();
        const sd  = $('#filterStartDate').val();
        const ed  = $('#filterEndDate').val();
        const q   = table.search();
        const url = `${baseUrl}/api/admin/sample-enquiries/export?format=${format}&supplier_id=${encodeURIComponent(sup)}&status=${encodeURIComponent(st)}&start_date=${encodeURIComponent(sd)}&end_date=${encodeURIComponent(ed)}&search=${encodeURIComponent(q)}`;
        window.location.href = url;
    };

    window.openSampleDetail = function(id) {
        const modal = new bootstrap.Modal(document.getElementById('sampleDetailModal'));
        const modalBody = document.getElementById('detailModalBody');
        const modalTitle = document.getElementById('detailModalTitle');
        const modalActions = document.getElementById('detailModalActions');

        modalTitle.textContent = `Sample Order #SMP-${String(id).padStart(5, '0')}`;
        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-teal" role="status"></div>
                <div class="text-muted small mt-2">Loading sample specifications...</div>
            </div>
        `;
        modalActions.innerHTML = '';
        modal.show();

        fetch(`${baseUrl}/api/admin/enquiries/${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.enquiry) {
                modalBody.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(data.message || 'Error loading sample order details.')}</div>`;
                return;
            }
            const enq = data.enquiry;
            const suppliers = data.suppliers || [];

            let supplierOptions = `<option value="">-- Unassigned / Direct Hub --</option>`;
            suppliers.forEach(s => {
                const sel = (parseInt(s.id) === parseInt(enq.effective_supplier_id || enq.supplier_id)) ? 'selected' : '';
                supplierOptions += `<option value="${s.id}" ${sel}>${escapeHtml(s.company_name)} (#SUP-${s.id})</option>`;
            });

            let attachHtml = '<span class="text-muted">None uploaded</span>';
            if (enq.attachment_path) {
                attachHtml = `
                    <a href="${baseUrl}/admin/enquiries/attachment/${enq.id}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-paperclip"></i>
                        <span>Download Tech Pack / Spec File</span>
                    </a>
                `;
            }

            modalBody.innerHTML = `
                <div class="row g-4">
                    <!-- Left: Buyer & Delivery -->
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-slate-900 mb-3"><i class="bi bi-person-circle text-teal me-2"></i>Buyer & Delivery Address</h6>
                        <table class="table table-sm table-borderless small mb-4">
                            <tr><td class="text-muted" style="width: 130px;">Buyer Name:</td><td class="fw-semibold">${escapeHtml(enq.buyer_name || 'N/A')}</td></tr>
                            <tr><td class="text-muted">Company / Brand:</td><td class="fw-semibold">${escapeHtml(enq.company_name || 'N/A')}</td></tr>
                            <tr><td class="text-muted">Email:</td><td><a href="mailto:${escapeHtml(enq.buyer_email)}">${escapeHtml(enq.buyer_email || 'N/A')}</a></td></tr>
                            <tr><td class="text-muted">Phone / WhatsApp:</td><td class="fw-semibold">${escapeHtml(enq.buyer_phone || 'N/A')}</td></tr>
                            <tr><td class="text-muted">Destination Country:</td><td class="fw-semibold">${escapeHtml(enq.country_name || 'N/A')}</td></tr>
                            <tr><td class="text-muted">Courier Address:</td><td class="fw-semibold text-danger">${escapeHtml(enq.delivery_location || 'Not provided')}</td></tr>
                        </table>

                        <h6 class="fw-bold text-slate-900 mb-2"><i class="bi bi-box-seam text-primary me-2"></i>Product Prototype</h6>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="fw-bold text-slate-800">${escapeHtml(enq.product_name || enq.subject || 'Custom Prototype')}</div>
                            <div class="text-muted small mt-1">Requested Quantity: <span class="fw-bold text-dark">${enq.target_quantity || 1} pcs</span></div>
                            ${enq.target_price ? `<div class="text-muted small">Sample Price: <span class="fw-bold text-dark">$${parseFloat(enq.target_price).toFixed(2)}/pc</span></div>` : ''}
                        </div>

                        <h6 class="fw-bold text-slate-900 mb-2"><i class="bi bi-paperclip text-secondary me-2"></i>Attachment</h6>
                        <div>${attachHtml}</div>
                    </div>

                    <!-- Right: Prototype Specs & Routing -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-slate-900 mb-2"><i class="bi bi-card-text text-teal me-2"></i>Prototype Testing Specifications</h6>
                        <div class="p-3 bg-light rounded-3 small text-slate-800 mb-3 font-monospace" style="white-space: pre-wrap; max-height: 160px; overflow-y: auto;">
                            ${escapeHtml(enq.specifications || 'No specific notes provided.')}
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-slate-700">Assigned Manufacturer / Factory</label>
                            <div class="input-group input-group-sm">
                                <select id="modalAssignSupplier" class="form-select">
                                    ${supplierOptions}
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="assignSupplierToSample(${enq.id})">Assign</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-slate-700">Update Sample Status</label>
                            <div class="input-group input-group-sm">
                                <select id="modalUpdateStatus" class="form-select">
                                    <option value="PENDING" ${enq.status === 'PENDING' ? 'selected' : ''}>Pending Review</option>
                                    <option value="IN_PRODUCTION" ${enq.status === 'IN_PRODUCTION' ? 'selected' : ''}>In Production</option>
                                    <option value="SAMPLE_SHIPPED" ${enq.status === 'SAMPLE_SHIPPED' ? 'selected' : ''}>Sample Shipped</option>
                                    <option value="DELIVERED" ${enq.status === 'DELIVERED' ? 'selected' : ''}>Delivered</option>
                                    <option value="REJECTED" ${enq.status === 'REJECTED' ? 'selected' : ''}>Rejected / Cancelled</option>
                                </select>
                                <button type="button" class="btn btn-teal text-white" style="background-color: #0f766e;" onclick="updateSampleOrderStatus(${enq.id})">Update</button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label small fw-bold text-slate-700">Courier Tracking Notes / Internal Dispatch Log</label>
                            <textarea id="modalAdminNotes" rows="3" class="form-control form-control-sm font-monospace" placeholder="e.g. Dispatched via DHL Express Tracking #12345678...">${escapeHtml(enq.admin_notes || '')}</textarea>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2 w-100" onclick="saveSampleAdminNotes(${enq.id})">
                                <i class="bi bi-save me-1"></i> Save Tracking / Internal Notes
                            </button>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger mb-0">Connection error loading sample details.</div>`;
        });
    };

    window.assignSupplierToSample = function(id) {
        const supId = document.getElementById('modalAssignSupplier').value;
        const formData = new FormData();
        formData.append('supplier_id', supId);
        formData.append('csrf_token', csrfToken);

        fetch(`${baseUrl}/api/admin/enquiries/${id}/assign-supplier`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Supplier assigned successfully.');
                table.ajax.reload(null, false);
            } else {
                alert(data.message || 'Failed to assign supplier.');
            }
        });
    };

    window.updateSampleOrderStatus = function(id) {
        const st = document.getElementById('modalUpdateStatus').value;
        const formData = new FormData();
        formData.append('status', st);
        formData.append('csrf_token', csrfToken);

        fetch(`${baseUrl}/api/admin/enquiries/${id}/status`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Sample status updated successfully.');
                table.ajax.reload(null, false);
            } else {
                alert(data.message || 'Failed to update status.');
            }
        });
    };

    window.saveSampleAdminNotes = function(id) {
        const notes = document.getElementById('modalAdminNotes').value;
        const formData = new FormData();
        formData.append('admin_notes', notes);
        formData.append('csrf_token', csrfToken);

        fetch(`${baseUrl}/api/admin/enquiries/${id}/notes`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Tracking notes saved.');
            } else {
                alert(data.message || 'Failed to save notes.');
            }
        });
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
