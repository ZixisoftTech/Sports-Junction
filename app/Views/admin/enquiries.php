<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$selectedBuyerId = !empty($filterBuyer['id']) ? (int)$filterBuyer['id'] : '';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-2 fw-bold" style="font-size: 11px;">CENTRALIZED ENQUIRY HUB</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Centralized Enquiry Management</h3>
        <p class="text-slate-500 small mb-0">View, filter, assign suppliers, and manage all Product Page, General RFQ, and Contact Us enquiries from a single interface.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Filtered Results
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportEnquiries('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportEnquiries('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportEnquiries('pdf'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Print / Save PDF</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/admin/buyers" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-people me-1"></i> Buyer Directory
        </a>
    </div>
</div>

<?php if (!empty($filterBuyer)): ?>
<div class="alert alert-primary d-flex justify-content-between align-items-center py-2 px-3 rounded-3 mb-4">
    <div>
        <i class="bi bi-funnel-fill me-2"></i>
        Showing enquiries submitted by Buyer: <strong><?= htmlspecialchars($filterBuyer['name']) ?></strong>
        (<?= htmlspecialchars($filterBuyer['email']) ?> — #BUY-<?= str_pad((string)$filterBuyer['id'], 4, '0', STR_PAD_LEFT) ?>)
    </div>
    <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-sm btn-light fw-bold">
        <i class="bi bi-x-lg me-1"></i> Clear Buyer Filter
    </a>
</div>
<?php endif; ?>

<!-- Multi-Filter & Search Card -->
<div class="card card-premium mb-4">
    <div class="card-body p-3">
        <input type="hidden" id="filterBuyerId" value="<?= htmlspecialchars((string)$selectedBuyerId) ?>">
        <div class="row g-3 align-items-end">
            <!-- Searchable Supplier Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Filter by Supplier (Searchable)</label>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="supplierSearchBox" class="form-control" placeholder="Type to filter supplier list...">
                </div>
                <select id="filterSupplier" class="form-select form-select-sm">
                    <option value="ALL">All Suppliers & Unassigned</option>
                    <option value="UNASSIGNED">Unassigned Enquiries Only</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= (int)$sup['id'] ?>" data-search="<?= htmlspecialchars(strtolower(($sup['company_name'] ?? '') . ' ' . ($sup['contact_person'] ?? '') . ' ' . ($sup['email'] ?? ''))) ?>">
                            <?= htmlspecialchars($sup['company_name']) ?> (#SUP-<?= (int)$sup['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Enquiry Source Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Enquiry Source</label>
                <select id="filterSource" class="form-select form-select-sm">
                    <option value="ALL">All Sources</option>
                    <option value="PRODUCT_PAGE">Product Page Enquiry</option>
                    <option value="RFQ">RFQ (Request For Quotation)</option>
                    <option value="FAST_OEM">Fast OEM Customization</option>
                    <option value="SAMPLE">Prototype Samples</option>
                    <option value="GENERAL">General Enquiry</option>
                    <option value="CONTACT_US">Contact Us Message</option>
                </select>
            </div>

            <!-- Enquiry Status Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Enquiry Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="ALL">All Statuses</option>
                    <option value="SUBMITTED">Submitted / Pending</option>
                    <option value="VIEWED">Viewed by Supplier</option>
                    <option value="RESPONDED">Responded</option>
                    <option value="IN_DISCUSSION">In Discussion</option>
                    <option value="RESOLVED">Resolved</option>
                    <option value="CLOSED">Closed</option>
                </select>
            </div>

            <!-- Product Category Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-slate-600 mb-1">Product Category</label>
                <select id="filterCategory" class="form-select form-select-sm">
                    <option value="ALL">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-slate-600 mb-1">Date Range (From — To)</label>
                <div class="d-flex gap-1">
                    <input type="date" id="filterStartDate" class="form-control form-control-sm" title="Start Date">
                    <input type="date" id="filterEndDate" class="form-control form-control-sm" title="End Date">
                    <button type="button" id="btnClearEnquiryFilters" class="btn btn-outline-secondary btn-sm px-2" title="Clear All Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Centralized Enquiries DataTable -->
<div class="card card-premium">
    <div class="card-body p-3 p-md-4">
        <div class="table-responsive">
            <table id="enquiriesTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th>Enquiry ID</th>
                        <th>Source</th>
                        <th>Buyer Name</th>
                        <th>Buyer Company</th>
                        <th>Buyer Email</th>
                        <th>Buyer Phone</th>
                        <th>Product / Requirement</th>
                        <th>Supplier Name</th>
                        <th>Qty</th>
                        <th>Delivery Location</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Assign Supplier & Status Modal -->
<div class="modal fade" id="assignSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="assignSupplierForm" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" id="asEnquiryId">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900"><i class="bi bi-building-check text-primary me-2"></i>Assign / Change Supplier</h5>
                    <small class="text-slate-500" id="asModalSubtitle">Route enquiry to a verified manufacturer</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-slate-700">Select Verified Supplier</label>
                    <select name="supplier_id" id="asSupplierSelect" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int)$sup['id'] ?>">
                                <?= htmlspecialchars($sup['company_name']) ?> (<?= htmlspecialchars($sup['email'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
let enquiriesTable;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function renderSourceBadge(src, subject) {
    const isSample = (src === 'SAMPLE') || (subject && /prototype sample|sample order/i.test(subject));
    if (isSample) {
        return `<span class="badge rounded-pill" style="background:#ccfbf1;color:#0f766e;border:1px solid #99f6e4;"><i class="bi bi-box-seam me-1"></i>Prototype Sample</span>`;
    } else if (src === 'PRODUCT_PAGE') {
        return `<span class="badge rounded-pill" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-box-seam me-1"></i>Product Page</span>`;
    } else if (src === 'CONTACT_US') {
        return `<span class="badge rounded-pill" style="background:#f3e8ff;color:#7e22ce;"><i class="bi bi-envelope-paper me-1"></i>Contact Us</span>`;
    } else if (src === 'RFQ') {
        return `<span class="badge rounded-pill" style="background:#fef3c7;color:#b45309;"><i class="bi bi-clipboard2-check me-1"></i>RFQ Request</span>`;
    } else if (src === 'FAST_OEM') {
        return `<span class="badge rounded-pill" style="background:#ccfbf1;color:#0f766e;"><i class="bi bi-gear-wide-connected me-1"></i>Fast OEM</span>`;
    }
    return `<span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;"><i class="bi bi-megaphone me-1"></i>General Enquiry</span>`;
}

function renderStatusBadge(st) {
    const map = {
        'PENDING':       { bg: '#fef3c7', color: '#b45309', label: 'Submitted' },
        'SUBMITTED':     { bg: '#fef3c7', color: '#b45309', label: 'Submitted' },
        'VIEWED':        { bg: '#e0e7ff', color: '#4338ca', label: 'Viewed' },
        'RESPONDED':     { bg: '#dcfce7', color: '#15803d', label: 'Responded' },
        'IN_DISCUSSION': { bg: '#dbeafe', color: '#1d4ed8', label: 'In Discussion' },
        'RESOLVED':      { bg: '#d1fae5', color: '#065f46', label: 'Resolved' },
        'CLOSED':        { bg: '#f1f5f9', color: '#475569', label: 'Closed' },
    };
    const cfg = map[st] || { bg: '#f1f5f9', color: '#475569', label: st || 'Submitted' };
    return `<span class="badge rounded-pill" style="background:${cfg.bg};color:${cfg.color};">${escapeHtml(cfg.label)}</span>`;
}

$(document).ready(function() {
    // Searchable Supplier Dropdown filter
    $('#supplierSearchBox').on('input', function() {
        const q = $(this).val().toLowerCase().trim();
        $('#filterSupplier option').each(function() {
            const val = $(this).val();
            if (val === 'ALL' || val === 'UNASSIGNED') {
                $(this).show();
                return;
            }
            const hay = ($(this).data('search') || $(this).text()).toLowerCase();
            if (!q || hay.includes(q)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    enquiriesTable = $('#enquiriesTable').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        pageLength: 15,
        order: [[11, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/enquiries/data',
            data: function(d) {
                d.supplier_id = $('#filterSupplier').val();
                d.enquiry_source = $('#filterSource').val();
                d.status = $('#filterStatus').val();
                d.category_id = $('#filterCategory').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
                d.buyer_id = $('#filterBuyerId').val();
            }
        },
        columns: [
            {
                data: 'id',
                render: (id, type, row) => `
                    <a href="${BASE_URL}/admin/enquiries/${id}" class="fw-bold text-primary text-decoration-none">
                        #ENQ-${String(id).padStart(5, '0')}
                    </a>
                    ${Number(row.reply_count || 0) > 0 ? `<span class="badge bg-primary bg-opacity-10 text-primary ms-1" title="Replies"><i class="bi bi-chat-dots"></i> ${row.reply_count}</span>` : ''}
                `
            },
            {
                data: 'enquiry_source',
                render: (src, type, row) => renderSourceBadge(src, row.subject)
            },
            {
                data: 'buyer_name',
                render: (name, type, row) => row.buyer_user_id
                    ? `<a href="${BASE_URL}/admin/enquiries?buyer_id=${row.buyer_user_id}" class="fw-semibold text-slate-900 text-decoration-none">${escapeHtml(name)}</a>`
                    : `<span class="fw-semibold text-slate-900">${escapeHtml(name)}</span>`
            },
            {
                data: 'company_name',
                render: val => val ? escapeHtml(val) : '<span class="text-muted">-</span>'
            },
            {
                data: 'buyer_email',
                render: val => `<a href="mailto:${escapeHtml(val)}" class="text-decoration-none small">${escapeHtml(val)}</a>`
            },
            {
                data: 'buyer_phone',
                render: val => val ? `<span class="small">${escapeHtml(val)}</span>` : '<span class="text-muted">-</span>'
            },
            {
                data: 'product_name',
                render: (prodName, type, row) => {
                    if (prodName) {
                        return `<div class="fw-semibold text-slate-800">${escapeHtml(prodName)}</div>
                                <small class="text-muted">ID: #${row.product_id}${row.category_name ? ' • ' + escapeHtml(row.category_name) : ''}</small>`;
                    }
                    return `<span class="text-slate-600 fst-italic">${escapeHtml(row.subject || 'General Requirement')}</span>`;
                }
            },
            {
                data: 'supplier_name',
                render: (supName, type, row) => supName
                    ? `<span class="fw-semibold text-slate-800">${escapeHtml(supName)}</span>`
                    : `<span class="badge bg-light text-secondary border">Unassigned</span>`
            },
            {
                data: 'target_quantity',
                render: (qty, type, row) => row.enquiry_source === 'CONTACT_US'
                    ? '<span class="text-muted">-</span>'
                    : `<span class="fw-bold">${escapeHtml(qty || 1)}</span> <small class="text-muted">${escapeHtml(row.moq_unit_name || 'Units')}</small>`
            },
            {
                data: 'delivery_location',
                render: (loc, type, row) => {
                    const country = row.country_name ? `<span class="badge bg-light text-dark border px-2 py-0.5 small"><i class="bi bi-geo-alt text-primary me-1"></i>${escapeHtml(row.country_name)}</span>` : '';
                    const v = loc || row.lead_time_req;
                    const locText = v ? `<div class="small text-slate-600 mt-1">${escapeHtml(v)}</div>` : '';
                    if (!country && !locText) return '<span class="text-muted">-</span>';
                    return `<div>${country}${locText}</div>`;
                }
            },
            {
                data: 'subject',
                render: (subj, type, row) => {
                    const txt = subj || row.product_name || (row.specifications ? row.specifications.substring(0, 45) + '...' : 'B2B Enquiry');
                    return `<div class="text-truncate" style="max-width:170px;" title="${escapeHtml(txt)}">${escapeHtml(txt)}</div>`;
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
                        <a href="${BASE_URL}/admin/enquiries/${row.id}" class="btn btn-outline-primary" title="View Complete Enquiry Details">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-outline-secondary" title="Assign / Change Supplier" onclick="openAssignSupplier(${row.id}, ${row.effective_supplier_id || 'null'})">
                            <i class="bi bi-building-gear"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" title="Mark Resolved" onclick="quickStatus(${row.id}, 'RESOLVED')">
                            <i class="bi bi-check2-all"></i>
                        </button>
                    </div>
                `
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Global search: ID, buyer, company, email, phone, product, supplier, subject, message, location..."
        }
    });

    let activeEnquiryCardFilter = null;

    function setEnquiryCardHighlight(cardKey) {
        $('.clickable-card').removeClass('active-card');
        if (cardKey) {
            $(`.clickable-card[data-card="${cardKey}"]`).addClass('active-card');
        }
    }

    $('.clickable-card').on('click', function () {
        const clickedCard = $(this).data('card');

        // Toggle off if clicking the currently active card
        if (activeEnquiryCardFilter === clickedCard) {
            activeEnquiryCardFilter = null;
            setEnquiryCardHighlight(null);
            $('#filterSource').val('ALL');
            $('#filterStatus').val('ALL');
            enquiriesTable.ajax.reload();
            return;
        }

        activeEnquiryCardFilter = clickedCard;
        setEnquiryCardHighlight(clickedCard);

        if (clickedCard === 'total') {
            $('#filterSource').val('ALL');
            $('#filterStatus').val('ALL');
        } else if (clickedCard === 'product_page') {
            $('#filterSource').val('PRODUCT_PAGE');
            $('#filterStatus').val('ALL');
        } else if (clickedCard === 'general_rfq') {
            $('#filterSource').val('GENERAL');
            $('#filterStatus').val('ALL');
        } else if (clickedCard === 'contact_us') {
            $('#filterSource').val('CONTACT_US');
            $('#filterStatus').val('ALL');
        } else if (clickedCard === 'pending') {
            $('#filterSource').val('ALL');
            $('#filterStatus').val('SUBMITTED');
        } else if (clickedCard === 'resolved') {
            $('#filterSource').val('ALL');
            $('#filterStatus').val('RESOLVED');
        }

        enquiriesTable.ajax.reload();
    });

    $('#filterSource, #filterStatus').on('change', function() {
        const src = $('#filterSource').val();
        const st = $('#filterStatus').val();
        if (src === 'PRODUCT_PAGE' && st === 'ALL') {
            activeEnquiryCardFilter = 'product_page';
            setEnquiryCardHighlight('product_page');
        } else if (src === 'GENERAL' && st === 'ALL') {
            activeEnquiryCardFilter = 'general_rfq';
            setEnquiryCardHighlight('general_rfq');
        } else if (src === 'CONTACT_US' && st === 'ALL') {
            activeEnquiryCardFilter = 'contact_us';
            setEnquiryCardHighlight('contact_us');
        } else if ((st === 'SUBMITTED' || st === 'PENDING') && src === 'ALL') {
            activeEnquiryCardFilter = 'pending';
            setEnquiryCardHighlight('pending');
        } else if ((st === 'RESOLVED' || st === 'CLOSED') && src === 'ALL') {
            activeEnquiryCardFilter = 'resolved';
            setEnquiryCardHighlight('resolved');
        } else if (src === 'ALL' && st === 'ALL') {
            if (activeEnquiryCardFilter !== 'total') {
                activeEnquiryCardFilter = null;
                setEnquiryCardHighlight(null);
            }
        } else {
            activeEnquiryCardFilter = null;
            setEnquiryCardHighlight(null);
        }
        enquiriesTable.ajax.reload();
    });

    $('#filterSupplier, #filterCategory, #filterStartDate, #filterEndDate').on('change', function() {
        enquiriesTable.ajax.reload();
    });

    $('#btnClearEnquiryFilters').on('click', function() {
        activeEnquiryCardFilter = null;
        setEnquiryCardHighlight(null);
        $('#supplierSearchBox').val('').trigger('input');
        $('#filterSupplier').val('ALL');
        $('#filterSource').val('ALL');
        $('#filterStatus').val('ALL');
        $('#filterCategory').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        $('#filterBuyerId').val('');
        if (window.location.search.includes('buyer_id=')) {
            window.history.replaceState({}, '', BASE_URL + '/admin/enquiries');
        }
        enquiriesTable.search('').draw();
    });

    $('#assignSupplierForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#asEnquiryId').val();
        $.post(`${BASE_URL}/api/admin/enquiries/${id}/assign-supplier`, $(this).serialize(), function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('assignSupplierModal')).hide();
                Swal.fire({ icon: 'success', title: 'Assigned', text: res.message, timer: 2000, showConfirmButton: false });
                enquiriesTable.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.message || 'Failed to assign supplier.', 'error');
            }
        }, 'json');
    });
});

function openAssignSupplier(enquiryId, currentSupplierId) {
    $('#asEnquiryId').val(enquiryId);
    $('#asModalSubtitle').text(`Enquiry #ENQ-${String(enquiryId).padStart(5, '0')}`);
    $('#asSupplierSelect').val(currentSupplierId || '');
    new bootstrap.Modal(document.getElementById('assignSupplierModal')).show();
}

function quickStatus(enquiryId, status) {
    $.post(`${BASE_URL}/api/admin/enquiries/${enquiryId}/status`, {
        csrf_token: CSRF_TOKEN,
        status: status
    }, function(res) {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 1800, showConfirmButton: false });
            enquiriesTable.ajax.reload(null, false);
            if (res.metrics) {
                $('#mTotal').text(res.metrics.total ?? 0);
                $('#mProductPage').text(res.metrics.product_page ?? 0);
                $('#mGeneral').text(res.metrics.general_rfq ?? 0);
                $('#mContactUs').text(res.metrics.contact_us ?? 0);
                $('#mPending').text(res.metrics.pending ?? 0);
                $('#mResolved').text(res.metrics.resolved ?? 0);
            }
        } else {
            Swal.fire('Error', res.message || 'Could not update status.', 'error');
        }
    }, 'json');
}

function exportEnquiries(format) {
    const params = new URLSearchParams({
        format: format,
        supplier_id: $('#filterSupplier').val(),
        enquiry_source: $('#filterSource').val(),
        status: $('#filterStatus').val(),
        category_id: $('#filterCategory').val(),
        start_date: $('#filterStartDate').val(),
        end_date: $('#filterEndDate').val(),
        buyer_id: $('#filterBuyerId').val(),
        search: enquiriesTable ? enquiriesTable.search() : ''
    });
    window.open(`${BASE_URL}/api/admin/enquiries/export?${params.toString()}`, '_blank');
}
</script>
<?php $pageScripts = ob_get_clean(); ?>
