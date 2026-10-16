<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$enqIdFormatted = '#ENQ-' . str_pad((string)$enquiry['id'], 5, '0', STR_PAD_LEFT);
$source = $enquiry['enquiry_source'] ?? 'PRODUCT_PAGE';
$status = $enquiry['status'] ?? 'SUBMITTED';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/enquiries" class="text-decoration-none">Centralized Enquiries</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($enqIdFormatted) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h3 class="fw-bold text-slate-900 mb-0">Enquiry <?= htmlspecialchars($enqIdFormatted) ?></h3>
            <?php if ($source === 'PRODUCT_PAGE'): ?>
                <span class="badge rounded-pill" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-box-seam me-1"></i>Product Page Enquiry</span>
            <?php elseif ($source === 'CONTACT_US'): ?>
                <span class="badge rounded-pill" style="background:#f3e8ff;color:#7e22ce;"><i class="bi bi-envelope-paper me-1"></i>Contact Us Message</span>
            <?php else: ?>
                <span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;"><i class="bi bi-megaphone me-1"></i>General Requirement / RFQ</span>
            <?php endif; ?>
            <span class="badge bg-dark" id="currentStatusBadge"><?= htmlspecialchars($status) ?></span>
        </div>
        <p class="text-slate-500 small mb-0 mt-1">Submitted on <?= htmlspecialchars($enquiry['created_at'] ?? '') ?> • IP: <?= htmlspecialchars($enquiry['ip_address'] ?? 'N/A') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Enquiries
        </a>
        <?php if ($status !== 'RESOLVED'): ?>
        <button type="button" class="btn btn-success btn-sm fw-semibold px-3 py-2 rounded-3" onclick="updateEnquiryStatus('RESOLVED')">
            <i class="bi bi-check2-circle me-1"></i> Mark as Resolved
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Buyer, Product/Requirement, and Conversation Thread -->
    <div class="col-lg-8">
        <!-- 1. Buyer Information -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-slate-900 mb-0"><i class="bi bi-person-badge text-primary me-2"></i>Buyer Information</h6>
                <?php if (!empty($enquiry['buyer_user_id'])): ?>
                    <a href="<?= $baseUrl ?>/admin/enquiries?buyer_id=<?= (int)$enquiry['buyer_user_id'] ?>" class="badge bg-primary bg-opacity-10 text-primary text-decoration-none px-3 py-2">
                        <i class="bi bi-link-45deg me-1"></i>Registered Buyer Account (#BUY-<?= str_pad((string)$enquiry['buyer_user_id'], 4, '0', STR_PAD_LEFT) ?>)
                    </a>
                <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">Guest / Public Submission</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-slate-500 small">Buyer Full Name</div>
                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($enquiry['buyer_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-slate-500 small">Company / Organization</div>
                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($enquiry['company_name'] ?: ($enquiry['registered_buyer_company'] ?? 'Not specified')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-slate-500 small">Business Email</div>
                        <div><a href="mailto:<?= htmlspecialchars($enquiry['buyer_email'] ?? '') ?>" class="fw-semibold text-primary text-decoration-none"><?= htmlspecialchars($enquiry['buyer_email'] ?? 'N/A') ?></a></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-slate-500 small">Phone / Mobile Number</div>
                        <div class="fw-semibold text-slate-800"><?= htmlspecialchars($enquiry['buyer_phone'] ?: 'Not provided') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Product / Requirement / Contact Us Details -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h6 class="fw-bold text-slate-900 mb-0">
                    <i class="bi bi-clipboard2-data text-primary me-2"></i>
                    <?= $source === 'CONTACT_US' ? 'Contact Us Message Details' : 'Product & Requirement Information' ?>
                </h6>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($enquiry['product_id']) && !empty($enquiry['product_name'])): ?>
                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border mb-4">
                    <?php
                    $imgUrl = !empty($enquiry['primary_image'])
                        ? (str_starts_with($enquiry['primary_image'], 'http') ? $enquiry['primary_image'] : $baseUrl . '/' . ltrim($enquiry['primary_image'], '/'))
                        : 'https://placehold.co/80x80/e2e8f0/1e293b?text=Product';
                    ?>
                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Product" class="rounded-3 border" style="width:72px;height:72px;object-fit:cover;">
                    <div class="flex-grow-1">
                        <div class="small text-primary fw-bold">
                            Product ID #<?= (int)$enquiry['product_id'] ?>
                            <?php if (!empty($enquiry['category_name'])): ?>
                                • <?= htmlspecialchars($enquiry['category_name']) ?>
                                <?= !empty($enquiry['subcategory_name']) ? ' / ' . htmlspecialchars($enquiry['subcategory_name']) : '' ?>
                            <?php endif; ?>
                        </div>
                        <h6 class="fw-bold text-slate-900 mb-1"><?= htmlspecialchars($enquiry['product_name']) ?></h6>
                        <?php if (!empty($enquiry['product_slug'])): ?>
                            <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($enquiry['product_slug']) ?>" target="_blank" class="small text-decoration-none">
                                View Public Product Page <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="text-slate-500 small">Subject / Requirement Title</div>
                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($enquiry['subject'] ?: ($enquiry['product_name'] ?: 'General B2B Enquiry')) ?></div>
                    </div>
                    <?php if ($source !== 'CONTACT_US'): ?>
                    <div class="col-md-3">
                        <div class="text-slate-500 small">Quantity Required</div>
                        <div class="fw-bold text-slate-900"><?= (int)($enquiry['target_quantity'] ?? 1) ?> <?= htmlspecialchars($enquiry['moq_unit_name'] ?? 'Units') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-slate-500 small">Target Price</div>
                        <div class="fw-bold text-slate-900">
                            <?= !empty($enquiry['target_price']) ? '$' . number_format((float)$enquiry['target_price'], 2) : 'Market Quote' ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-slate-500 small">Delivery Location / Lead Time</div>
                        <div class="fw-semibold text-slate-800"><?= htmlspecialchars($enquiry['delivery_location'] ?: ($enquiry['lead_time_req'] ?: 'Standard Dispatch')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-slate-500 small">Required Delivery Date</div>
                        <div class="fw-semibold text-slate-800"><?= htmlspecialchars($enquiry['required_delivery_date'] ?: 'Flexible / Immediate') ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-3">
                    <div class="text-slate-500 small fw-bold mb-1">Buyer Message / Technical Specifications / Customization Details</div>
                    <div class="p-3 rounded-3 bg-light border text-slate-800" style="white-space: pre-wrap;"><?= htmlspecialchars($enquiry['specifications'] ?? '') ?></div>
                </div>
            </div>
        </div>

        <!-- 3. Conversation / Reply History -->
        <div class="card card-premium">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-slate-900 mb-0"><i class="bi bi-chat-dots text-primary me-2"></i>Conversation & Reply Thread</h6>
                <span class="badge bg-light text-slate-700 border"><?= count($replies) ?> Message(s)</span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($replies)): ?>
                    <div class="text-center text-muted py-4 border rounded-3 bg-light mb-4">
                        <i class="bi bi-chat-square-quote fs-3 d-block mb-1"></i>
                        No replies have been posted on this enquiry thread yet.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($replies as $rep): ?>
                            <?php
                            $role = $rep['sender_role'] ?? 'BUYER';
                            $badgeClass = $role === 'SUPER_ADMIN' ? 'bg-danger' : ($role === 'SUPPLIER' ? 'bg-primary' : 'bg-success');
                            ?>
                            <div class="p-3 rounded-3 border <?= $role === 'BUYER' ? 'bg-white' : 'bg-light' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($role) ?></span>
                                        <strong class="text-slate-900 small"><?= htmlspecialchars($rep['sender_name'] ?: $role) ?></strong>
                                    </div>
                                    <small class="text-slate-500"><?= htmlspecialchars($rep['created_at'] ?? '') ?></small>
                                </div>
                                <div class="text-slate-800 small" style="white-space: pre-wrap;"><?= htmlspecialchars($rep['message'] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="<?= $baseUrl ?>/admin/enquiries/<?= (int)$enquiry['id'] ?>/reply" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label class="form-label small fw-bold text-slate-700">Post Official Reply (Visible to Buyer & Assigned Supplier)</label>
                    <textarea name="message" rows="3" class="form-control mb-3" placeholder="Write response or instructions for this enquiry..." required></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                            <i class="bi bi-send me-1"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Supplier Assignment, Status Lifecycle, and Private Admin Notes -->
    <div class="col-lg-4">
        <!-- Supplier Information & Assignment -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h6 class="fw-bold text-slate-900 mb-0"><i class="bi bi-building text-primary me-2"></i>Supplier Assignment</h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <div class="text-slate-500 small">Currently Assigned Supplier</div>
                    <div class="fw-bold text-slate-900 fs-6 mt-1" id="assignedSupplierDisplay">
                        <?= !empty($enquiry['supplier_name']) ? htmlspecialchars($enquiry['supplier_name']) : '<span class="text-muted">Unassigned</span>' ?>
                    </div>
                    <?php if (!empty($enquiry['supplier_email'])): ?>
                        <div class="small text-slate-500"><?= htmlspecialchars($enquiry['supplier_email']) ?></div>
                    <?php endif; ?>
                </div>

                <label class="form-label small fw-bold text-slate-700">Assign or Reassign Supplier</label>
                <select id="detailSupplierSelect" class="form-select form-select-sm mb-3">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= (int)$sup['id'] ?>" <?= ((int)($enquiry['effective_supplier_id'] ?? 0) === (int)$sup['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sup['company_name']) ?> (#SUP-<?= (int)$sup['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-semibold" onclick="saveSupplierAssignment()">
                    <i class="bi bi-building-check me-1"></i> Update Assigned Supplier
                </button>
            </div>
        </div>

        <!-- Enquiry Status Control -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h6 class="fw-bold text-slate-900 mb-0"><i class="bi bi-sliders text-primary me-2"></i>Enquiry Lifecycle Status</h6>
            </div>
            <div class="card-body p-4">
                <select id="detailStatusSelect" class="form-select form-select-sm mb-3">
                    <?php foreach (['SUBMITTED', 'VIEWED', 'RESPONDED', 'IN_DISCUSSION', 'RESOLVED', 'CLOSED'] as $stOpt): ?>
                        <option value="<?= $stOpt ?>" <?= ($status === $stOpt || ($status === 'PENDING' && $stOpt === 'SUBMITTED')) ? 'selected' : '' ?>>
                            <?= $stOpt ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold" onclick="updateEnquiryStatus($('#detailStatusSelect').val())">
                    <i class="bi bi-arrow-repeat me-1"></i> Update Status
                </button>
            </div>
        </div>

        <!-- Internal Super Admin Notes (Private) -->
        <div class="card card-premium border-warning">
            <div class="card-header bg-warning bg-opacity-10 border-bottom py-3 px-4">
                <h6 class="fw-bold text-slate-900 mb-0"><i class="bi bi-shield-lock-fill text-warning me-2"></i>Internal Super Admin Notes</h6>
                <small class="text-slate-600" style="font-size:11px;">Strictly private — never shown to Buyers, Suppliers, or Exports</small>
            </div>
            <div class="card-body p-4">
                <textarea id="adminNotesInput" rows="5" class="form-control form-control-sm mb-3" placeholder="Add internal verification notes, follow-up reminders, or pricing remarks..."><?= htmlspecialchars($enquiry['admin_notes'] ?? '') ?></textarea>
                <button type="button" class="btn btn-dark btn-sm w-100 fw-semibold" onclick="saveAdminNotes()">
                    <i class="bi bi-floppy me-1"></i> Save Private Notes
                </button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
const ENQUIRY_ID = <?= (int)$enquiry['id'] ?>;

function saveSupplierAssignment() {
    const supplierId = $('#detailSupplierSelect').val();
    $.post(`${BASE_URL}/api/admin/enquiries/${ENQUIRY_ID}/assign-supplier`, {
        csrf_token: CSRF_TOKEN,
        supplier_id: supplierId
    }, function(res) {
        if (res.success) {
            $('#assignedSupplierDisplay').text(res.supplier_name || 'Unassigned');
            Swal.fire({ icon: 'success', title: 'Supplier Updated', text: res.message, timer: 2000, showConfirmButton: false });
        } else {
            Swal.fire('Error', res.message || 'Could not update supplier.', 'error');
        }
    }, 'json');
}

function updateEnquiryStatus(status) {
    $.post(`${BASE_URL}/api/admin/enquiries/${ENQUIRY_ID}/status`, {
        csrf_token: CSRF_TOKEN,
        status: status
    }, function(res) {
        if (res.success) {
            $('#currentStatusBadge').text(res.status);
            $('#detailStatusSelect').val(res.status);
            Swal.fire({ icon: 'success', title: 'Status Updated', text: res.message, timer: 1800, showConfirmButton: false });
        } else {
            Swal.fire('Error', res.message || 'Failed to update status.', 'error');
        }
    }, 'json');
}

function saveAdminNotes() {
    $.post(`${BASE_URL}/api/admin/enquiries/${ENQUIRY_ID}/notes`, {
        csrf_token: CSRF_TOKEN,
        admin_notes: $('#adminNotesInput').val()
    }, function(res) {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Saved', text: res.message, timer: 1800, showConfirmButton: false });
        } else {
            Swal.fire('Error', res.message || 'Failed to save notes.', 'error');
        }
    }, 'json');
}
</script>
<?php $pageScripts = ob_get_clean(); ?>
