<?php
declare(strict_types=1);

$baseUrl = \App\Core\Response::baseUrl();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-2 fw-bold" style="font-size: 11px;">CENTRALIZED OVERSIGHT</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Communication & Deal Monitoring Center</h3>
        <p class="text-slate-500 small mb-0">Monitor all B2B buyer-supplier communications, policy compliance, price negotiation offers, and safety interventions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/admin/enquiries" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-chat-left-quote me-1"></i> Enquiry Inquiries
        </a>
        <a href="<?= $baseUrl ?>/admin/buyers" class="btn btn-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-people me-1"></i> Buyer Accounts
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border cursor-pointer clickable-card" id="cardCommTotal" data-card="total" role="button" tabindex="0" title="Click to show all conversations">
            <div class="text-slate-500 small fw-semibold mb-1">Total Chats</div>
            <h3 class="fw-bold text-slate-900 mb-0"><?= (int)($stats['total_conversations'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border cursor-pointer clickable-card" id="cardCommNegotiating" data-card="negotiating" role="button" tabindex="0" title="Click to filter negotiating conversations">
            <div class="text-slate-500 small fw-semibold mb-1">Negotiating</div>
            <h3 class="fw-bold text-primary mb-0"><?= (int)($stats['active_negotiations'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border cursor-pointer clickable-card" id="cardCommOffersPending" data-card="offers_pending" role="button" tabindex="0" title="Click to filter offer pending conversations">
            <div class="text-slate-500 small fw-semibold mb-1">Offers Pending</div>
            <h3 class="fw-bold mb-0" style="color: #d97706 !important;"><?= (int)($stats['pending_offers'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border cursor-pointer clickable-card" id="cardCommDealsAgreed" data-card="deals_agreed" role="button" tabindex="0" title="Click to filter agreed & completed deals">
            <div class="text-slate-500 small fw-semibold mb-1">Deals Agreed</div>
            <h3 class="fw-bold mb-0" style="color: #16a34a !important;"><?= (int)($stats['deals_agreed'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border cursor-pointer clickable-card" id="cardCommFlagged" data-card="flagged" role="button" tabindex="0" title="Click to filter conversations with flagged violations">
            <div class="text-slate-500 small fw-semibold mb-1">Flagged Violations</div>
            <h3 class="fw-bold mb-0" style="color: #dc2626 !important;"><?= (int)($stats['flagged_messages'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border cursor-pointer clickable-card" id="cardCommPendingReports" data-card="pending_reports" role="button" tabindex="0" title="Click to view pending safety reports">
            <div class="text-slate-500 small fw-semibold mb-1">Pending Reports</div>
            <h3 class="fw-bold mb-0" style="color: #dc2626 !important;"><?= (int)($stats['pending_reports'] ?? 0) ?></h3>
        </div>
    </div>
</div>

<style>
.clickable-card {
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #e2e8f0 !important;
    position: relative;
    user-select: none;
}
.clickable-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 40, 142, 0.12) !important;
    border-color: #cbd5e1 !important;
}
.clickable-card.active-card {
    border-color: #00288e !important;
    background-color: #f0f4ff !important;
    box-shadow: 0 0 0 3px rgba(0, 40, 142, 0.18), 0 6px 16px rgba(0, 40, 142, 0.12) !important;
    transform: translateY(-2px);
}
.clickable-card.active-card::after {
    content: "ACTIVE";
    position: absolute;
    top: 6px;
    right: 8px;
    font-size: 9px;
    font-weight: 700;
    color: #00288e;
    background: #dbeafe;
    padding: 1px 6px;
    border-radius: 999px;
    letter-spacing: 0.5px;
}
</style>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4 border-bottom" id="commTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold text-dark px-4 py-2.5" id="convs-tab" data-bs-toggle="tab" data-bs-target="#tab-convs" type="button" role="tab">
            <i class="bi bi-chat-dots me-1 text-primary"></i> All Conversations (<span id="convsTabCount"><?= count($conversations) ?></span>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold text-dark px-4 py-2.5" id="reports-tab" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button" role="tab">
            <i class="bi bi-shield-exclamation me-1 text-danger"></i> Safety &amp; Misconduct Reports (<span id="reportsTabCount"><?= count($reports) ?></span>)
        </button>
    </li>
</ul>

<div class="tab-content" id="commTabsContent">
    <!-- TAB 1: ALL CONVERSATIONS -->
    <div class="tab-pane fade show active" id="tab-convs" role="tabpanel">
        <!-- Filter Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= $baseUrl ?>/admin/communication" id="commFilterForm" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" id="commSearchInput" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search buyer, supplier, product..." class="form-control border-start-0 bg-light">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <select name="status" id="commStatusSelect" class="form-select form-select-sm bg-light">
                            <option value="all">All Statuses</option>
                            <option value="ACTIVE" <?= ($statusFilter === 'ACTIVE') ? 'selected' : '' ?>>Active</option>
                            <option value="NEGOTIATION_ONGOING" <?= ($statusFilter === 'NEGOTIATION_ONGOING') ? 'selected' : '' ?>>Negotiation Ongoing</option>
                            <option value="OFFER_PENDING" <?= ($statusFilter === 'OFFER_PENDING') ? 'selected' : '' ?>>Offer Pending</option>
                            <option value="DEAL_AGREED" <?= ($statusFilter === 'DEAL_AGREED' || $statusFilter === 'DEALS_AGREED') ? 'selected' : '' ?>>Deal Agreed / Completed</option>
                            <option value="DEAL_COMPLETED" <?= ($statusFilter === 'DEAL_COMPLETED') ? 'selected' : '' ?>>Deal Completed</option>
                            <option value="BLOCKED" <?= ($statusFilter === 'BLOCKED') ? 'selected' : '' ?>>Blocked</option>
                            <option value="UNDER_REVIEW" <?= ($statusFilter === 'UNDER_REVIEW') ? 'selected' : '' ?>>Under Review</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="supplier_id" id="commSupplierSelect" class="form-select form-select-sm bg-light">
                            <option value="">All Suppliers / Manufacturers</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= ((string)$supplierFilter === (string)$s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['company_name'] ?? 'Supplier #' . $s['id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="form-check form-check-inline small">
                            <input class="form-check-input" type="checkbox" name="flagged" value="1" id="chkFlagged" <?= ($flaggedOnly === '1') ? 'checked' : '' ?>>
                            <label class="form-check-label text-danger fw-semibold" for="chkFlagged">Flagged Only</label>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm fw-semibold w-100">Filter</button>
                        <a href="<?= $baseUrl ?>/admin/communication" id="btnResetCommFilters" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- DataTable Card -->
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="table-light text-uppercase fw-bold text-muted small" style="font-size: 11px;">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Buyer Details</th>
                            <th>Supplier Details</th>
                            <th>Product / Subject</th>
                            <th>Status & Deal Stage</th>
                            <th>Policy & Audit</th>
                            <th>Last Activity</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" id="convsTableBody">
                        <tr id="noMatchingConvsRow" style="display:none;">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-filter display-6 d-block mb-2 text-slate-300"></i>
                                No conversations match the selected filter.
                            </td>
                        </tr>
                        <?php if (empty($conversations)): ?>
                            <tr id="emptyConvsRow">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-chat-square-text display-6 d-block mb-2 text-slate-300"></i>
                                    No conversations found matching filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($conversations as $c): ?>
                                <?php 
                                    $searchStr = strtolower(
                                        ($c['buyer_company'] ?? '') . ' ' . 
                                        ($c['buyer_first_name'] ?? '') . ' ' . 
                                        ($c['buyer_last_name'] ?? '') . ' ' . 
                                        ($c['buyer_email'] ?? '') . ' ' . 
                                        ($c['supplier_company_name'] ?? '') . ' ' . 
                                        ($c['supplier_contact_person'] ?? '') . ' ' . 
                                        ($c['product_title'] ?? '') . ' ' . 
                                        ($c['last_message_body'] ?? '')
                                    );
                                ?>
                                <tr class="conv-row" 
                                    data-id="<?= (int)$c['id'] ?>"
                                    data-status="<?= htmlspecialchars($c['status']) ?>"
                                    data-flagged="<?= ((int)($c['flagged_messages_count'] ?? 0) > 0) ? '1' : '0' ?>"
                                    data-supplier="<?= (int)($c['supplier_id'] ?? 0) ?>"
                                    data-search="<?= htmlspecialchars($searchStr) ?>">
                                    <td class="ps-3 fw-bold text-slate-500">#<?= (int)$c['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($c['buyer_company'] ?: ($c['buyer_first_name'] . ' ' . $c['buyer_last_name'])) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($c['buyer_email'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-teal-800"><?= htmlspecialchars($c['supplier_company_name'] ?? 'Supplier') ?></div>
                                        <div class="text-muted small">Rep: <?= htmlspecialchars($c['supplier_contact_person'] ?? 'Factory Rep') ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['product_title'])): ?>
                                            <div class="fw-semibold text-primary text-truncate" style="max-width: 200px;"><?= htmlspecialchars($c['product_title']) ?></div>
                                            <div class="text-muted small">$<?= number_format((float)($c['product_price'] ?? 0), 2) ?></div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">General Sourcing Inquiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['status'] === 'DEAL_AGREED' || $c['status'] === 'DEAL_COMPLETED'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold">Deal Agreed</span>
                                        <?php elseif ($c['status'] === 'OFFER_PENDING'): ?>
                                            <span class="badge bg-warning bg-opacity-15 text-warning fw-bold">Offer Pending</span>
                                        <?php elseif ($c['status'] === 'NEGOTIATION_ONGOING'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">Negotiating</span>
                                        <?php elseif ($c['status'] === 'UNDER_REVIEW'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">Under Review</span>
                                        <?php elseif ($c['status'] === 'BLOCKED'): ?>
                                            <span class="badge bg-dark text-white fw-bold">Blocked</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($c['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <?php if ((int)$c['flagged_messages_count'] > 0): ?>
                                                <span class="badge bg-danger text-white small" title="Messages contain phone, email or external contact info">
                                                    <i class="bi bi-shield-slash me-1"></i> <?= (int)$c['flagged_messages_count'] ?> Policy Flag
                                                </span>
                                            <?php else: ?>
                                                <span class="text-success small"><i class="bi bi-shield-check"></i> Clean</span>
                                            <?php endif; ?>

                                            <?php if ((int)$c['active_reports_count'] > 0): ?>
                                                <span class="badge bg-warning text-dark small">
                                                    <i class="bi bi-flag-fill me-1"></i> Reported
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-slate-700"><?= !empty($c['last_message_at']) ? date('d M Y, H:i', strtotime($c['last_message_at'])) : date('d M Y', strtotime($c['created_at'])) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 150px;"><?= htmlspecialchars($c['last_message_body'] ?? 'None') ?></div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= $baseUrl ?>/admin/communication/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary fw-semibold px-2.5 py-1 rounded-2">
                                            <i class="bi bi-search me-1"></i> Audit Chat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: SAFETY & MODERATION REPORTS -->
    <div class="tab-pane fade" id="tab-reports" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="table-light text-uppercase fw-bold text-muted small" style="font-size: 11px;">
                        <tr>
                            <th class="ps-3">Report ID</th>
                            <th>Reporting Party</th>
                            <th>Reported User</th>
                            <th>Reason & Violation</th>
                            <th>Status</th>
                            <th>Submitted Date</th>
                            <th class="text-end pe-3">Resolution Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <tr id="noMatchingReportsRow" style="display:none;">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-filter display-6 d-block mb-2 text-slate-300"></i>
                                No safety reports match the selected filter.
                            </td>
                        </tr>
                        <?php if (empty($reports)): ?>
                            <tr id="emptyReportsRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check display-6 d-block mb-2 text-success"></i>
                                    No safety reports currently pending.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr class="report-row" 
                                    data-id="<?= (int)$r['id'] ?>"
                                    data-status="<?= htmlspecialchars($r['status']) ?>"
                                    data-search="<?= htmlspecialchars(strtolower(($r['reporter_company'] ?? '') . ' ' . ($r['reporter_first_name'] ?? '') . ' ' . ($r['reporter_last_name'] ?? '') . ' ' . ($r['reported_company'] ?? '') . ' ' . ($r['reported_first_name'] ?? '') . ' ' . ($r['reported_last_name'] ?? '') . ' ' . ($r['reason'] ?? '') . ' ' . ($r['description'] ?? ''))) ?>">
                                    <td class="ps-3 fw-bold text-slate-500">#<?= (int)$r['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($r['reporter_company'] ?: ($r['reporter_first_name'] . ' ' . $r['reporter_last_name'])) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['reporter_email'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-danger"><?= htmlspecialchars($r['reported_company'] ?: ($r['reported_first_name'] . ' ' . $r['reported_last_name'])) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['reported_email'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($r['reason']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($r['description'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] === 'PENDING'): ?>
                                            <span class="badge bg-danger text-white">Pending</span>
                                        <?php elseif ($r['status'] === 'RESOLVED'): ?>
                                            <span class="badge bg-success text-white">Resolved</span>
                                        <?php elseif ($r['status'] === 'DISMISSED'): ?>
                                            <span class="badge bg-secondary text-white">Dismissed</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><?= htmlspecialchars($r['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="<?= $baseUrl ?>/admin/communication/<?= (int)$r['conversation_id'] ?>" class="btn btn-sm btn-outline-primary px-2 py-1">
                                                <i class="bi bi-search"></i> Inspect Chat
                                            </a>
                                            <button type="button" onclick="openResolveReportModal(<?= (int)$r['id'] ?>)" class="btn btn-sm btn-outline-success px-2 py-1">
                                                <i class="bi bi-check-circle"></i> Resolve
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Resolve Report Modal -->
<div class="modal fade" id="resolveReportModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="resolveReportForm" action="">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Resolve Safety Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-sm space-y-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Action / Resolution Status</label>
                        <select name="status" class="form-select">
                            <option value="RESOLVED">Mark Resolved (Action Taken / Warned)</option>
                            <option value="DISMISSED">Dismiss (False Alarm / No Violation)</option>
                            <option value="UNDER_REVIEW">Keep Under Review</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Resolution Notes (Internal)</label>
                        <textarea name="admin_notes" rows="3" class="form-control" placeholder="Describe findings, warnings issued, or actions executed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Confirm Resolution</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let activeCommCardFilter = null;

function setCommCardHighlight(cardName) {
    document.querySelectorAll('.clickable-card').forEach(function(card) {
        if (cardName && card.getAttribute('data-card') === cardName) {
            card.classList.add('active-card');
        } else {
            card.classList.remove('active-card');
        }
    });
}

function filterConversationsTable() {
    const searchVal = (document.getElementById('commSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('commStatusSelect')?.value || 'all';
    const supplierVal = document.getElementById('commSupplierSelect')?.value || '';
    const flaggedOnly = document.getElementById('chkFlagged')?.checked;

    const rows = document.querySelectorAll('.conv-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowFlagged = row.getAttribute('data-flagged') === '1';
        const rowSupplier = row.getAttribute('data-supplier') || '';
        const rowSearch = row.getAttribute('data-search') || '';

        let match = true;

        if (activeCommCardFilter === 'negotiating') {
            if (rowStatus !== 'NEGOTIATION_ONGOING') match = false;
        } else if (activeCommCardFilter === 'offers_pending') {
            if (rowStatus !== 'OFFER_PENDING') match = false;
        } else if (activeCommCardFilter === 'deals_agreed') {
            if (rowStatus !== 'DEAL_AGREED' && rowStatus !== 'DEAL_COMPLETED') match = false;
        } else if (activeCommCardFilter === 'flagged') {
            if (!rowFlagged) match = false;
        } else if (activeCommCardFilter === 'total') {
            // All conversations
        } else {
            if (statusVal !== 'all') {
                if (statusVal === 'DEAL_AGREED') {
                    if (rowStatus !== 'DEAL_AGREED' && rowStatus !== 'DEAL_COMPLETED') match = false;
                } else if (rowStatus !== statusVal) {
                    match = false;
                }
            }
            if (flaggedOnly && !rowFlagged) {
                match = false;
            }
        }

        if (supplierVal && rowSupplier !== supplierVal) {
            match = false;
        }

        if (searchVal && !rowSearch.includes(searchVal)) {
            match = false;
        }

        if (match) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noRow = document.getElementById('noMatchingConvsRow');
    if (noRow) {
        noRow.style.display = (rows.length > 0 && visibleCount === 0) ? '' : 'none';
    }

    const countSpan = document.getElementById('convsTabCount');
    if (countSpan) {
        countSpan.textContent = visibleCount;
    }
}

function filterReportsTable() {
    const rows = document.querySelectorAll('.report-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
        const rowStatus = row.getAttribute('data-status') || '';
        let match = true;

        if (activeCommCardFilter === 'pending_reports') {
            if (rowStatus !== 'PENDING') match = false;
        }

        if (match) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noRow = document.getElementById('noMatchingReportsRow');
    if (noRow) {
        noRow.style.display = (rows.length > 0 && visibleCount === 0) ? '' : 'none';
    }

    const countSpan = document.getElementById('reportsTabCount');
    if (countSpan) {
        countSpan.textContent = visibleCount;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const currentStatus = "<?= htmlspecialchars($statusFilter ?? '') ?>";
    const currentFlagged = "<?= htmlspecialchars($flaggedOnly ?? '') ?>";

    if (currentFlagged === '1') {
        activeCommCardFilter = 'flagged';
        setCommCardHighlight('flagged');
    } else if (currentStatus === 'NEGOTIATION_ONGOING') {
        activeCommCardFilter = 'negotiating';
        setCommCardHighlight('negotiating');
    } else if (currentStatus === 'OFFER_PENDING') {
        activeCommCardFilter = 'offers_pending';
        setCommCardHighlight('offers_pending');
    } else if (currentStatus === 'DEAL_AGREED' || currentStatus === 'DEAL_COMPLETED') {
        activeCommCardFilter = 'deals_agreed';
        setCommCardHighlight('deals_agreed');
    }

    // Card click events
    document.querySelectorAll('.clickable-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            const cardType = this.getAttribute('data-card');

            // If already active, toggle off
            if (activeCommCardFilter === cardType) {
                activeCommCardFilter = null;
                setCommCardHighlight(null);
                const statusSelect = document.getElementById('commStatusSelect');
                if (statusSelect) statusSelect.value = 'all';
                const chk = document.getElementById('chkFlagged');
                if (chk) chk.checked = false;
                filterConversationsTable();
                filterReportsTable();
                return;
            }

            activeCommCardFilter = cardType;
            setCommCardHighlight(cardType);

            if (cardType === 'pending_reports') {
                const reportTabBtn = document.getElementById('reports-tab');
                if (reportTabBtn && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(reportTabBtn).show();
                }
                filterReportsTable();
            } else {
                const convTabBtn = document.getElementById('convs-tab');
                if (convTabBtn && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(convTabBtn).show();
                }

                const statusSelect = document.getElementById('commStatusSelect');
                const chkFlagged = document.getElementById('chkFlagged');

                if (cardType === 'total') {
                    if (statusSelect) statusSelect.value = 'all';
                    if (chkFlagged) chkFlagged.checked = false;
                } else if (cardType === 'negotiating') {
                    if (statusSelect) statusSelect.value = 'NEGOTIATION_ONGOING';
                    if (chkFlagged) chkFlagged.checked = false;
                } else if (cardType === 'offers_pending') {
                    if (statusSelect) statusSelect.value = 'OFFER_PENDING';
                    if (chkFlagged) chkFlagged.checked = false;
                } else if (cardType === 'deals_agreed') {
                    if (statusSelect) statusSelect.value = 'DEAL_AGREED';
                    if (chkFlagged) chkFlagged.checked = false;
                } else if (cardType === 'flagged') {
                    if (statusSelect) statusSelect.value = 'all';
                    if (chkFlagged) chkFlagged.checked = true;
                }

                filterConversationsTable();
            }
        });
    });

    // Form inputs live filtering
    const statusSelect = document.getElementById('commStatusSelect');
    const chkFlagged = document.getElementById('chkFlagged');
    const searchInput = document.getElementById('commSearchInput');
    const supplierSelect = document.getElementById('commSupplierSelect');

    [statusSelect, chkFlagged, supplierSelect].forEach(function(el) {
        if (el) {
            el.addEventListener('change', function() {
                const st = statusSelect ? statusSelect.value : 'all';
                const fl = chkFlagged ? chkFlagged.checked : false;

                if (fl) {
                    activeCommCardFilter = 'flagged';
                    setCommCardHighlight('flagged');
                } else if (st === 'NEGOTIATION_ONGOING') {
                    activeCommCardFilter = 'negotiating';
                    setCommCardHighlight('negotiating');
                } else if (st === 'OFFER_PENDING') {
                    activeCommCardFilter = 'offers_pending';
                    setCommCardHighlight('offers_pending');
                } else if (st === 'DEAL_AGREED' || st === 'DEAL_COMPLETED') {
                    activeCommCardFilter = 'deals_agreed';
                    setCommCardHighlight('deals_agreed');
                } else if (st === 'all') {
                    if (activeCommCardFilter !== 'total') {
                        activeCommCardFilter = null;
                        setCommCardHighlight(null);
                    }
                } else {
                    activeCommCardFilter = null;
                    setCommCardHighlight(null);
                }
                filterConversationsTable();
            });
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterConversationsTable();
        });
    }

    // Reset button
    const resetBtn = document.getElementById('btnResetCommFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            activeCommCardFilter = null;
            setCommCardHighlight(null);
            if (searchInput) searchInput.value = '';
            if (statusSelect) statusSelect.value = 'all';
            if (supplierSelect) supplierSelect.value = '';
            if (chkFlagged) chkFlagged.checked = false;
            filterConversationsTable();
            filterReportsTable();
        });
    }
});

function openResolveReportModal(reportId) {
    const form = document.getElementById('resolveReportForm');
    form.action = '<?= $baseUrl ?>/admin/communication/reports/' + reportId + '/resolve';
    new bootstrap.Modal(document.getElementById('resolveReportModal')).show();
}
</script>
