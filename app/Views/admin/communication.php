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
        <div class="stat-card h-100 bg-white p-3 rounded-3 border">
            <div class="text-slate-500 small fw-semibold mb-1">Total Chats</div>
            <h3 class="fw-bold text-slate-900 mb-0"><?= (int)($stats['total_conversations'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border">
            <div class="text-slate-500 small fw-semibold mb-1">Negotiating</div>
            <h3 class="fw-bold text-primary mb-0"><?= (int)($stats['active_negotiations'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border">
            <div class="text-slate-500 small fw-semibold mb-1">Offers Pending</div>
            <h3 class="fw-bold text-warning mb-0"><?= (int)($stats['pending_offers'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border">
            <div class="text-slate-500 small fw-semibold mb-1">Deals Agreed</div>
            <h3 class="fw-bold text-success mb-0"><?= (int)($stats['deals_agreed'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border">
            <div class="text-slate-500 small fw-semibold mb-1">Flagged Violations</div>
            <h3 class="fw-bold text-danger mb-0"><?= (int)($stats['flagged_messages'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card h-100 bg-white p-3 rounded-3 border">
            <div class="text-slate-500 small fw-semibold mb-1">Pending Reports</div>
            <h3 class="fw-bold text-danger mb-0"><?= (int)($stats['pending_reports'] ?? 0) ?></h3>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4 border-bottom" id="commTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold text-dark px-4 py-2.5" id="convs-tab" data-bs-toggle="tab" data-bs-target="#tab-convs" type="button" role="tab">
            <i class="bi bi-chat-dots me-1 text-primary"></i> All Conversations (<?= count($conversations) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold text-dark px-4 py-2.5" id="reports-tab" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button" role="tab">
            <i class="bi bi-shield-exclamation me-1 text-danger"></i> Safety & Misconduct Reports (<?= count($reports) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="commTabsContent">
    <!-- TAB 1: ALL CONVERSATIONS -->
    <div class="tab-pane fade show active" id="tab-convs" role="tabpanel">
        <!-- Filter Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= $baseUrl ?>/admin/communication" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search buyer, supplier, product..." class="form-control border-start-0 bg-light">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm bg-light">
                            <option value="all">All Statuses</option>
                            <option value="ACTIVE" <?= ($statusFilter === 'ACTIVE') ? 'selected' : '' ?>>Active</option>
                            <option value="NEGOTIATION_ONGOING" <?= ($statusFilter === 'NEGOTIATION_ONGOING') ? 'selected' : '' ?>>Negotiation Ongoing</option>
                            <option value="OFFER_PENDING" <?= ($statusFilter === 'OFFER_PENDING') ? 'selected' : '' ?>>Offer Pending</option>
                            <option value="DEAL_AGREED" <?= ($statusFilter === 'DEAL_AGREED') ? 'selected' : '' ?>>Deal Agreed</option>
                            <option value="DEAL_COMPLETED" <?= ($statusFilter === 'DEAL_COMPLETED') ? 'selected' : '' ?>>Deal Completed</option>
                            <option value="BLOCKED" <?= ($statusFilter === 'BLOCKED') ? 'selected' : '' ?>>Blocked</option>
                            <option value="UNDER_REVIEW" <?= ($statusFilter === 'UNDER_REVIEW') ? 'selected' : '' ?>>Under Review</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="supplier_id" class="form-select form-select-sm bg-light">
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
                        <a href="<?= $baseUrl ?>/admin/communication" class="btn btn-outline-secondary btn-sm">Reset</a>
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
                    <tbody class="divide-y">
                        <?php if (empty($conversations)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-chat-square-text display-6 d-block mb-2 text-slate-300"></i>
                                    No conversations found matching filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($conversations as $c): ?>
                                <tr>
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
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check display-6 d-block mb-2 text-success"></i>
                                    No safety reports currently pending.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
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
function openResolveReportModal(reportId) {
    const form = document.getElementById('resolveReportForm');
    form.action = '<?= $baseUrl ?>/admin/communication/reports/' + reportId + '/resolve';
    new bootstrap.Modal(document.getElementById('resolveReportModal')).show();
}
</script>
