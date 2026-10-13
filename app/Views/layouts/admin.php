<?php
use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$user = Session::get('auth_user') ?? ['name' => 'Super Administrator', 'email' => 'sportjunctions@gmail.com', 'role' => 'SUPER_ADMIN'];
$activeMenu = $activeMenu ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sports Junction — Super Admin Portal') ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg">
    <!-- Google Material Symbols Outlined -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link href="<?= htmlspecialchars($baseUrl) ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar Navigation -->
<aside class="sj-sidebar">
    <div class="sj-sidebar-brand">
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="d-block text-center">
            <img src="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg" alt="Sports Junction" class="img-fluid">
        </a>
    </div>

    <div class="sj-sidebar-nav">
        <div class="sj-nav-section-title">Core</div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="sj-nav-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <div class="sj-nav-section-title">Master Management</div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/countries" class="sj-nav-item <?= $activeMenu === 'countries' ? 'active' : '' ?>">
            <i class="bi bi-globe-americas"></i>
            <span>Countries</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/states" class="sj-nav-item <?= $activeMenu === 'states' ? 'active' : '' ?>">
            <i class="bi bi-map"></i>
            <span>States</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/cities" class="sj-nav-item <?= $activeMenu === 'cities' ? 'active' : '' ?>">
            <i class="bi bi-buildings"></i>
            <span>Cities</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/business-types" class="sj-nav-item <?= $activeMenu === 'business_types' ? 'active' : '' ?>">
            <i class="bi bi-briefcase"></i>
            <span>Business Types</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/price-units" class="sj-nav-item <?= $activeMenu === 'price_units' ? 'active' : '' ?>">
            <i class="bi bi-currency-rupee"></i>
            <span>Price Units</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/moq-units" class="sj-nav-item <?= $activeMenu === 'moq_units' ? 'active' : '' ?>">
            <i class="bi bi-boxes"></i>
            <span>MOQ Units</span>
        </a>

        <div class="sj-nav-section-title">Marketplace Catalog</div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/categories" class="sj-nav-item <?= $activeMenu === 'categories' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i>
            <span>Categories</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/subcategories" class="sj-nav-item <?= $activeMenu === 'subcategories' ? 'active' : '' ?>">
            <i class="bi bi-diagram-3"></i>
            <span>Subcategories</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/products" class="sj-nav-item <?= $activeMenu === 'products' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i>
            <span>Products & Catalog</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/" target="_blank" class="sj-nav-item text-info">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>View Public Store</span>
        </a>

        <div class="sj-nav-section-title">Approvals &amp; Vendors</div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/suppliers" class="sj-nav-item <?= $activeMenu === 'suppliers' ? 'active' : '' ?>">
            <i class="bi bi-person-check"></i>
            <span>Supplier Approvals</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/product-approvals" class="sj-nav-item <?= $activeMenu === 'product_approvals' ? 'active' : '' ?>">
            <i class="bi bi-patch-check"></i>
            <span>Product Approvals</span>
        </a>

        <div class="sj-nav-section-title">Buyers &amp; Enquiries</div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/buyers" class="sj-nav-item <?= ($activeMenu === 'buyers' || str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/buyers')) ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Buyer Management</span>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/enquiries" class="sj-nav-item <?= ($activeMenu === 'enquiries' || str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/enquiries')) ? 'active' : '' ?>">
            <i class="bi bi-chat-left-quote"></i>
            <span>Enquiry Management</span>
        </a>

        <div class="sj-nav-section-title">Security & Audit</div>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard#audit" class="sj-nav-item">
            <i class="bi bi-journal-text"></i>
            <span>Audit Trail</span>
        </a>
    </div>

    <!-- Sidebar Bottom User Info -->
    <div class="p-3 border-top border-secondary border-opacity-25 bg-dark">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center overflow-hidden">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 34px; height: 34px; font-weight: 700; flex-shrink: 0;">
                    <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="text-truncate">
                    <div class="text-white small fw-bold text-truncate"><?= htmlspecialchars($user['name'] ?? 'Super Admin') ?></div>
                    <span class="sj-badge-super">SUPER ADMIN</span>
                </div>
            </div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/logout" class="text-danger ms-2 fs-5" title="Logout" onclick="return confirm('Are you sure you want to log out?');">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Main Layout Container -->
<div class="sj-main-layout">
    <!-- Top Bar -->
    <header class="sj-topbar">
        <div class="d-flex align-items-center">
            <!-- Mobile Toggle Hamburger -->
            <button class="btn btn-sm btn-light border d-lg-none me-2" type="button" id="sidebarToggleBtn" aria-label="Toggle Navigation">
                <i class="bi bi-list fs-5 text-dark"></i>
            </button>
            <div>
                <h5 class="m-0 fw-bold text-dark"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h5>
                <small class="text-muted d-none d-md-inline"><?= htmlspecialchars($metaDescription ?? "India's Sports Manufacturing Marketplace Portal") ?></small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-sm-flex align-items-center bg-light px-3 py-1.5 rounded-pill border small">
                <i class="bi bi-shield-fill-check text-success me-1.5"></i>
                <span class="fw-semibold me-1">Role:</span>
                <span class="badge bg-primary text-white">SUPER_ADMIN</span>
            </div>

            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center border" type="button" data-bs-override="" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1.5 text-primary"></i>
                    <span class="small fw-semibold"><?= htmlspecialchars($user['email'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold small"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                    </li>
                    <li><a class="dropdown-item py-2 small" href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard"><i class="bi bi-speedometer2 me-2 text-muted"></i>Dashboard</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2 small text-danger" href="<?= htmlspecialchars($baseUrl) ?>/admin/logout">
                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Dynamic Content -->
    <main class="sj-content">
        <?php
        $flashSuccess = Session::flash('success');
        $flashError = Session::flash('error');
        ?>
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($flashSuccess) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($flashError) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="sj-footer">
        <div>
            &copy; <?= date('Y') ?> <strong>Sports Junction</strong> — India's Premier Sports Manufacturing Marketplace. All rights reserved.
        </div>
        <div class="text-muted">
            <span class="badge bg-secondary-subtle text-secondary border me-2">v2.4.0 Production</span>
            <span>Security: High</span>
        </div>
    </footer>
</div>

<!-- Toast Notifications Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 10050;" id="toastContainer"></div>

<!-- ========================================== -->
<!-- UNIVERSAL MODALS FOR MASTER CRUD OPERATION -->
<!-- ========================================== -->

<!-- 1. Universal Status Toggle Modal -->
<div class="modal fade" id="statusConfirmModal" tabindex="-1" aria-labelledby="statusConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="statusConfirmModalLabel"><i class="bi bi-arrow-repeat me-1.5 text-primary"></i>Confirm Status Change</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="d-flex align-items-start">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle me-3 flex-shrink-0">
                        <i class="bi bi-question-diamond-fill fs-3"></i>
                    </div>
                    <div class="w-100">
                        <h6 class="fw-bold mb-1" id="statusTargetName">Change Status</h6>
                        <p class="text-muted small mb-0" id="statusConfirmText">Are you sure you want to update the status of this record?</p>
                        <div id="statusDependencyWarning" class="alert alert-warning mt-3 p-3 small d-none border-warning"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-end gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-outline-warning text-dark px-3 fw-semibold d-none" id="btnForceStatusBtn">
                    <i class="bi bi-pause-circle me-1"></i> Deactivate Parent Only
                </button>
                <button type="button" class="btn btn-sm btn-danger px-3 fw-semibold d-none" id="btnCascadeStatusBtn">
                    <i class="bi bi-diagram-3-fill me-1"></i> Cascade Deactivate All
                </button>
                <button type="button" class="btn btn-sm btn-sj-primary px-3" id="confirmStatusChangeBtn">
                    Yes, Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Universal Permanent Delete & Dependency Safety Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold" id="deleteConfirmModalLabel"><i class="bi bi-trash3-fill me-2"></i>Permanent Hard Delete</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <!-- Record details -->
                <div id="deleteModalLoading" class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="small text-muted mt-2">Checking foreign-key and business dependencies...</p>
                </div>

                <div id="deleteModalContent" class="d-none">
                    <div class="mb-3">
                        <h6 class="fw-bold mb-1" id="deleteTargetTitle">Delete Record</h6>
                        <p class="text-muted small mb-0">Hard delete permanently removes this record from the database. This action cannot be undone.</p>
                    </div>

                    <!-- Protected Business Entities Warning (BLOCKS deletion) -->
                    <div id="deleteProtectedAlert" class="alert alert-danger d-none py-2.5 px-3 small">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-shield-x fs-5 me-2 text-danger"></i>
                            <div>
                                <strong class="d-block">Deletion Blocked: Protected Business Records</strong>
                                <span id="deleteProtectedMsg">This record is currently referenced by active marketplace accounts or catalog products.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-Master Dependencies (Cascade Allowed with Explicit DELETE Type Confirmation) -->
                    <div id="deleteCascadeSection" class="alert alert-warning d-none py-2.5 px-3 small">
                        <strong class="d-block mb-1"><i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>Dependent Records Found:</strong>
                        <ul id="deleteDependencyList" class="mb-2 ps-3"></ul>
                        <div class="border-top pt-2">
                            <label class="form-label fw-bold text-dark mb-1">To confirm cascading deletion, type <code class="text-danger fw-bold">DELETE</code> below:</label>
                            <input type="text" class="form-control form-control-sm border-danger" id="cascadeConfirmInput" placeholder="Type DELETE to confirm">
                        </div>
                    </div>

                    <!-- Clean Direct Delete (No dependencies) -->
                    <div id="deleteCleanAlert" class="alert alert-secondary d-none py-2.5 px-3 small">
                        <i class="bi bi-check2-circle text-success me-1"></i> No foreign dependencies detected. This record is safe to permanently delete.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger px-3" id="executePermanentDeleteBtn" disabled>
                    <i class="bi bi-trash3 me-1"></i> Permanently Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Core Scripts: jQuery, Bootstrap 5, DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- Mobile Sidebar Backdrop -->
<div class="sj-sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Global Application Settings & Helper JS -->
<script>
    window.SportsJunction = {
        baseUrl: '<?= htmlspecialchars($baseUrl) ?>',
        csrfToken: '<?= CSRF::getToken() ?>',
        defaultLogo: '<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg',
        formatImageUrl: function (path) {
            if (!path || path.trim() === '') return this.defaultLogo;
            path = path.trim();
            if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) {
                return path;
            }
            var b = this.baseUrl || '';
            var clean = path.startsWith('/') ? path : '/' + path;
            if (b && clean.startsWith(b + '/')) {
                return clean;
            }
            return b + clean;
        }
    };

    // Mobile Sidebar Toggle Handling
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebar = document.querySelector('.sj-sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (toggleBtn && sidebar && backdrop) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });

            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }
    });
</script>
<script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/master-module.js"></script>
</body>
</html>
