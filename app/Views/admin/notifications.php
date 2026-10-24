<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
$currentTab = strtolower(trim($filters['tab'] ?? 'all'));
$searchQuery = trim($filters['search'] ?? '');
$dateFrom = trim($filters['date_from'] ?? '');
$dateTo = trim($filters['date_to'] ?? '');

function buildTabUrl(string $tab, array $filters, string $baseUrl): string {
    $params = ['tab' => $tab];
    if (!empty($filters['search'])) $params['search'] = $filters['search'];
    if (!empty($filters['date_from'])) $params['date_from'] = $filters['date_from'];
    if (!empty($filters['date_to'])) $params['date_to'] = $filters['date_to'];
    return $baseUrl . '/admin/notifications?' . http_build_query($params);
}
?>

<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill small fw-bold text-uppercase">
                    <i class="bi bi-bell-fill me-1"></i>Real-Time Activity
                </span>
                <span class="text-muted small">&bull; Super Admin Center</span>
            </div>
            <h3 class="fw-extrabold text-dark tracking-tight mb-1">Super Admin Notification Center</h3>
            <p class="text-muted small m-0">Monitor and respond promptly to incoming buyer enquiries, RFQs, chat messages, prototype requests, and factory verifications.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-2xs fw-semibold px-3 py-2 rounded-3" id="btnPageMarkAllRead">
                <i class="bi bi-check2-all text-primary"></i>
                <span>Mark All as Read</span>
            </button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/enquiries" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold px-3 py-2 rounded-3">
                <i class="bi bi-chat-left-quote"></i>
                <span>Enquiry Management</span>
            </a>
        </div>
    </div>

    <!-- Quick Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="<?= buildTabUrl('all', $filters, $baseUrl) ?>" class="card border-0 shadow-2xs rounded-3 p-3 text-decoration-none text-dark h-100 hover-lift <?= $currentTab === 'all' ? 'border-primary border-2 border' : '' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Alerts</span>
                    <span class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-bell text-secondary"></i>
                    </span>
                </div>
                <div class="fs-4 fw-extrabold text-dark"><?= number_format((int)($counts['total'] ?? 0)) ?></div>
                <div class="small text-muted mt-1">Logged event notifications</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= buildTabUrl('unread', $filters, $baseUrl) ?>" class="card border-0 shadow-2xs rounded-3 p-3 text-decoration-none text-dark h-100 hover-lift <?= $currentTab === 'unread' ? 'border-danger border-2 border' : '' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-danger small fw-bold text-uppercase">Unread</span>
                    <span class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-envelope-exclamation text-danger"></i>
                    </span>
                </div>
                <div class="fs-4 fw-extrabold text-danger"><?= number_format((int)($counts['unread'] ?? 0)) ?></div>
                <div class="small text-muted mt-1">Requires immediate attention</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= buildTabUrl('enquiries', $filters, $baseUrl) ?>" class="card border-0 shadow-2xs rounded-3 p-3 text-decoration-none text-dark h-100 hover-lift <?= $currentTab === 'enquiries' ? 'border-primary border-2 border' : '' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-primary small fw-bold text-uppercase">Enquiries &amp; RFQs</span>
                    <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-chat-left-quote text-primary"></i>
                    </span>
                </div>
                <div class="fs-4 fw-extrabold text-primary"><?= number_format((int)($counts['enquiries'] ?? 0)) ?></div>
                <div class="small text-muted mt-1">RFQs, OEM &amp; Prototypes</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= buildTabUrl('messages', $filters, $baseUrl) ?>" class="card border-0 shadow-2xs rounded-3 p-3 text-decoration-none text-dark h-100 hover-lift <?= $currentTab === 'messages' ? 'border-success border-2 border' : '' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-success small fw-bold text-uppercase">Chat Messages</span>
                    <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-chat-dots-fill text-success"></i>
                    </span>
                </div>
                <div class="fs-4 fw-extrabold text-success"><?= number_format((int)($counts['messages'] ?? 0)) ?></div>
                <div class="small text-muted mt-1">Buyer &amp; Supplier negotiations</div>
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <!-- Filter Tabs -->
        <div class="border-bottom bg-light px-3 pt-3">
            <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto gap-1" id="notifTabs">
                <li class="nav-item">
                    <a class="nav-link <?= $currentTab === 'all' ? 'active fw-bold' : 'text-muted' ?>" href="<?= buildTabUrl('all', $filters, $baseUrl) ?>">
                        <i class="bi bi-grid me-1"></i>All Notifications
                        <span class="badge bg-secondary rounded-pill ms-1"><?= (int)($counts['total'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentTab === 'unread' ? 'active fw-bold' : 'text-muted' ?>" href="<?= buildTabUrl('unread', $filters, $baseUrl) ?>">
                        <i class="bi bi-envelope me-1"></i>Unread
                        <span class="badge bg-danger rounded-pill ms-1"><?= (int)($counts['unread'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentTab === 'enquiries' ? 'active fw-bold' : 'text-muted' ?>" href="<?= buildTabUrl('enquiries', $filters, $baseUrl) ?>">
                        <i class="bi bi-chat-left-quote me-1"></i>Enquiries &amp; RFQs
                        <span class="badge bg-primary rounded-pill ms-1"><?= (int)($counts['enquiries'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentTab === 'messages' ? 'active fw-bold' : 'text-muted' ?>" href="<?= buildTabUrl('messages', $filters, $baseUrl) ?>">
                        <i class="bi bi-chat-dots me-1"></i>Messages
                        <span class="badge bg-success rounded-pill ms-1"><?= (int)($counts['messages'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentTab === 'products' ? 'active fw-bold' : 'text-muted' ?>" href="<?= buildTabUrl('products', $filters, $baseUrl) ?>">
                        <i class="bi bi-patch-check me-1"></i>Product Approvals
                        <span class="badge bg-warning text-dark rounded-pill ms-1"><?= (int)($counts['products'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentTab === 'suppliers' ? 'active fw-bold' : 'text-muted' ?>" href="<?= buildTabUrl('suppliers', $filters, $baseUrl) ?>">
                        <i class="bi bi-person-check me-1"></i>Supplier Approvals
                        <span class="badge bg-info text-dark rounded-pill ms-1"><?= (int)($counts['suppliers'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentTab === 'reports' ? 'active fw-bold' : 'text-muted' ?>" href="<?= buildTabUrl('reports', $filters, $baseUrl) ?>">
                        <i class="bi bi-shield-exclamation me-1"></i>Reports
                        <span class="badge bg-danger rounded-pill ms-1"><?= (int)($counts['reports'] ?? 0) ?></span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="p-3 border-bottom bg-white">
            <form method="GET" action="<?= htmlspecialchars($baseUrl) ?>/admin/notifications" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($currentTab) ?>">

                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search title, sender, product, or keyword..." value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                </div>

                <div class="col-6 col-md-2.5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted small">From</span>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                </div>

                <div class="col-6 col-md-2.5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted small">To</span>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <?php if (!empty($searchQuery) || !empty($dateFrom) || !empty($dateTo) || $currentTab !== 'all'): ?>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/notifications" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Notifications Feed List -->
        <div class="list-group list-group-flush" id="notificationListGroup">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5 my-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center p-4 mb-3 text-secondary">
                        <i class="bi bi-bell-slash fs-1 opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No notifications found</h5>
                    <p class="text-muted small mb-3">No activity logs or messages match your current filter criteria.</p>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/notifications" class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-semibold">
                        View All Notifications
                    </a>
                </div>
                <?php foreach ($notifications as $n): 
                    $targetUrl = htmlspecialchars($baseUrl) . '/admin/notifications/' . (int)$n['id'] . '/go';
                    $btnLabel = 'View Record';
                    if (str_contains($n['type'], 'CHAT')) {
                        $btnLabel = 'Reply in Chat';
                    } elseif (str_contains($n['type'], 'ENQUIRY')) {
                        $btnLabel = 'View Enquiry';
                    } elseif (str_contains($n['type'], 'PRODUCT')) {
                        $btnLabel = 'Review Product';
                    } elseif (str_contains($n['type'], 'SUPPLIER')) {
                        $btnLabel = 'Review Supplier';
                    } elseif (str_contains($n['type'], 'REPORT')) {
                        $btnLabel = 'Inspect Report';
                    }
                ?>
                    <div class="list-group-item p-3.5 transition-colors border-bottom notif-clickable-row <?= $n['is_read'] ? 'bg-white' : 'bg-primary-subtle bg-opacity-10' ?>" 
                         id="notif-row-<?= (int)$n['id'] ?>" 
                         data-target-url="<?= $targetUrl ?>"
                         style="cursor: pointer;">
                        <div class="d-flex align-items-start gap-3">
                            <!-- Type Icon -->
                            <div class="rounded-circle d-flex align-items-center justify-content-center shrink-0 mt-0.5 <?= $n['badge_class'] ?>" style="width: 40px; height: 40px;">
                                <i class="bi <?= htmlspecialchars($n['icon']) ?> fs-5"></i>
                            </div>

                            <!-- Notification Content -->
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1.5">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge <?= $n['badge_class'] ?> rounded-pill small fw-bold">
                                            <?= htmlspecialchars($n['type_label']) ?>
                                        </span>
                                        <?php if (!$n['is_read']): ?>
                                            <span class="badge bg-danger rounded-pill small fw-semibold" id="badge-unread-<?= (int)$n['id'] ?>">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-muted small">
                                        <i class="bi bi-clock"></i>
                                        <span><?= htmlspecialchars($n['time_ago']) ?></span>
                                        <span class="d-none d-sm-inline opacity-50">&bull;</span>
                                        <span class="d-none d-sm-inline"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></span>
                                    </div>
                                </div>

                                <h6 class="fw-bold mb-1">
                                    <a href="<?= $targetUrl ?>" class="text-dark text-decoration-none hover-primary d-inline-block">
                                        <?= htmlspecialchars($n['title']) ?>
                                    </a>
                                </h6>
                                <p class="text-secondary small mb-2 text-break" style="line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars($n['message'])) ?>
                                </p>

                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 notif-row-actions">
                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-primary fw-semibold px-3 py-1 rounded-2 d-inline-flex align-items-center gap-1 shadow-2xs">
                                        <span><?= $btnLabel ?></span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>

                                    <?php if ($n['is_read']): ?>
                                        <button type="button" class="btn btn-sm btn-light border fw-semibold px-2.5 py-1 text-muted btn-toggle-read" onclick="toggleReadStatus(event, <?= (int)$n['id'] ?>, false)">
                                            <i class="bi bi-envelope me-1"></i>Mark Unread
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-light border fw-semibold px-2.5 py-1 text-dark btn-toggle-read" onclick="toggleReadStatus(event, <?= (int)$n['id'] ?>, true)">
                                            <i class="bi bi-check-lg text-success me-1"></i>Mark as Read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
            <div class="p-3 border-top bg-light d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                <small class="text-muted">
                    Showing <strong><?= min($total, ($currentPage - 1) * $perPage + 1) ?></strong> to <strong><?= min($total, $currentPage * $perPage) ?></strong> of <strong><?= $total ?></strong> notifications
                </small>
                <nav aria-label="Notification Center Pagination">
                    <ul class="pagination pagination-sm m-0">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/notifications?<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">Previous</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Previous</span></li>
                        <?php endif; ?>

                        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/notifications?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/notifications?<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">Next</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Next</span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function toggleReadStatus(e, id, markRead) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const url = '<?= $baseUrl ?>/api/admin/notifications/' + id + '/' + (markRead ? 'read' : 'unread');
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': window.SportsJunction.csrfToken || ''
            }
        });
        const data = await res.json();
        if (data && data.success) {
            location.reload();
        }
    } catch (err) {
        console.error('Error toggling read status:', err);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Clickable notification rows: navigate to target source page
    document.querySelectorAll('.notif-clickable-row').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target.closest('.btn-toggle-read')) {
                return;
            }
            const targetUrl = row.getAttribute('data-target-url');
            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });
    });

    const btnAllRead = document.getElementById('btnPageMarkAllRead');
    if (btnAllRead) {
        btnAllRead.addEventListener('click', async () => {
            btnAllRead.disabled = true;
            btnAllRead.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Marking...';
            try {
                const res = await fetch('<?= $baseUrl ?>/api/admin/notifications/mark-all-read?tab=<?= urlencode($currentTab) ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': window.SportsJunction.csrfToken || ''
                    }
                });
                const data = await res.json();
                if (data && data.success) {
                    location.reload();
                }
            } catch (err) {
                console.error('Failed to mark all as read:', err);
            } finally {
                btnAllRead.disabled = false;
                btnAllRead.innerHTML = '<i class="bi bi-check2-all text-primary"></i><span>Mark All as Read</span>';
            }
        });
    }
});
</script>
