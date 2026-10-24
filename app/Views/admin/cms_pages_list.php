<?php
/**
 * Admin CMS Pages List & Editor View
 * Manage dynamic footer pages, 5 columns, and rich content
 */
$baseUrl = !empty($baseUrl) ? $baseUrl : \App\Core\Response::baseUrl();

$totalPages = count($pages);
$activePagesCount = 0;
$draftPagesCount = 0;
$inFooterCount = 0;
foreach ($pages as $p) {
    if (!empty($p['is_active'])) $activePagesCount++;
    else $draftPagesCount++;
    if (!empty($p['show_in_footer'])) $inFooterCount++;
}

$categoryCounts = [
    'ALL' => $totalPages,
    'ABOUT' => 0,
    'PROTECTION' => 0,
    'HELP' => 0,
    'SOURCING' => 0,
    'LEGAL' => 0
];
foreach ($pages as $p) {
    $c = strtoupper((string)($p['column_category'] ?? ''));
    if (isset($categoryCounts[$c])) {
        $categoryCounts[$c]++;
    }
}
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/dashboard" class="text-decoration-none text-slate-500">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/settings" class="text-decoration-none text-slate-500">Website Settings</a></li>
                    <li class="breadcrumb-item active fw-semibold text-slate-800" aria-current="page">Footer & CMS Pages</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-slate-900 mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-layout-text-window-reverse text-primary"></i>
                Footer CMS & Platform Content Management
            </h1>
            <p class="text-muted small mb-0">
                Organize public pages across the 5 marketplace footer sections. Manage content, SEO, footer visibility, and URLs dynamically.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary btn-sm fw-bold shadow-xs px-3 d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#createPageModal">
                <i class="bi bi-plus-lg"></i> Create New Page
            </button>
            <a href="<?= $baseUrl ?>/admin/settings" class="btn btn-outline-secondary btn-sm fw-bold d-flex align-items-center gap-1.5">
                <i class="bi bi-gear"></i> Website Settings
            </a>
            <a href="<?= $baseUrl ?>/sitemap" target="_blank" class="btn btn-outline-primary btn-sm fw-bold d-flex align-items-center gap-1.5" title="View Public Sitemap Directory">
                <i class="bi bi-diagram-3"></i> View Sitemap
            </a>
        </div>
    </div>

    <?php if ($success = \App\Helpers\Session::getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div><?= htmlspecialchars($success) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error = \App\Helpers\Session::getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <div><?= htmlspecialchars($error) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-primary">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Total CMS Pages</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-slate-900"><?= $totalPages ?></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary small">5 Sections</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-success">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Live & Published</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-success"><?= $activePagesCount ?></span>
                    <span class="badge bg-success bg-opacity-10 text-success small">Publicly Accessible</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-info">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Displayed in Footer</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-info"><?= $inFooterCount ?></span>
                    <span class="badge bg-info bg-opacity-10 text-info small">Active Links</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-secondary">
                <div class="text-muted small fw-semibold text-uppercase tracking-wider">Draft / Unlisted</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="fs-3 fw-bold text-secondary"><?= $draftPagesCount ?></span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">Admin Preview Only</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Filter Tabs / Pills -->
        <div class="card-header bg-white border-bottom pt-3 pb-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Column Category Filter Pills -->
                <ul class="nav nav-pills gap-1" id="columnFilterPills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-1.5 px-3 small fw-bold rounded-pill" data-column="ALL">
                            All Pages <span class="badge bg-dark bg-opacity-10 text-dark ms-1"><?= $categoryCounts['ALL'] ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1.5 px-3 small fw-bold rounded-pill text-slate-700" data-column="ABOUT">
                            About Sports Junction <span class="badge bg-primary bg-opacity-10 text-primary ms-1"><?= $categoryCounts['ABOUT'] ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1.5 px-3 small fw-bold rounded-pill text-slate-700" data-column="PROTECTION">
                            Buyer & Seller Protection <span class="badge bg-success bg-opacity-10 text-success ms-1"><?= $categoryCounts['PROTECTION'] ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1.5 px-3 small fw-bold rounded-pill text-slate-700" data-column="HELP">
                            Help & Support <span class="badge bg-warning bg-opacity-10 text-warning ms-1"><?= $categoryCounts['HELP'] ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1.5 px-3 small fw-bold rounded-pill text-slate-700" data-column="SOURCING">
                            Sourcing & Logistics <span class="badge bg-info bg-opacity-10 text-info ms-1"><?= $categoryCounts['SOURCING'] ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1.5 px-3 small fw-bold rounded-pill text-slate-700" data-column="LEGAL">
                            Legal & Policies <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1"><?= $categoryCounts['LEGAL'] ?></span>
                        </button>
                    </li>
                </ul>

                <!-- Search and Status Quick Filter -->
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 240px;">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Filter by title, slug...">
                    </div>
                    <select id="statusFilterSelect" class="form-select form-select-sm" style="width: 140px;">
                        <option value="ALL">All Status</option>
                        <option value="ACTIVE">Live Only</option>
                        <option value="DRAFT">Draft Only</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm" id="cmsPagesTable">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4" style="width: 28%;">Page Title & Footer Label</th>
                            <th style="width: 20%;">Section / Column</th>
                            <th style="width: 20%;">Public URL / Route</th>
                            <th style="width: 8%;" class="text-center">Order</th>
                            <th style="width: 8%;" class="text-center">In Footer</th>
                            <th style="width: 8%;" class="text-center">Status</th>
                            <th class="text-end pe-4" style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cmsTableBody">
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder2-open fs-2 text-slate-300 d-block mb-2"></i>
                                    No footer pages configured. Click "Create New Page" to add one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $p): ?>
                                <?php
                                    $colKey = strtoupper((string)($p['column_category'] ?? ''));
                                    $badgeClass = match($colKey) {
                                        'ABOUT' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20',
                                        'PROTECTION' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-20',
                                        'HELP' => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-30',
                                        'SOURCING' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-20',
                                        'LEGAL' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-20',
                                        default => 'bg-light text-dark'
                                    };
                                    $targetUrl = !empty($p['external_url']) 
                                        ? $p['external_url'] 
                                        : ('/' . ltrim($p['slug'], '/'));
                                    $fullLiveUrl = !empty($p['external_url'])
                                        ? (str_starts_with($p['external_url'], 'http') ? $p['external_url'] : ($baseUrl . $p['external_url']))
                                        : ($baseUrl . '/' . ltrim($p['slug'], '/'));
                                ?>
                                <tr class="page-row" 
                                    data-category="<?= htmlspecialchars($colKey) ?>" 
                                    data-status="<?= !empty($p['is_active']) ? 'ACTIVE' : 'DRAFT' ?>"
                                    data-footer="<?= !empty($p['show_in_footer']) ? '1' : '0' ?>"
                                    data-search="<?= htmlspecialchars(strtolower($p['title'] . ' ' . ($p['footer_label'] ?? '') . ' ' . $p['slug'])) ?>">
                                    
                                    <td class="ps-4">
                                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($p['title']) ?></div>
                                        <?php if (!empty($p['footer_label']) && $p['footer_label'] !== $p['title']): ?>
                                            <div class="text-muted small" style="font-size: 11px;">
                                                <i class="bi bi-arrow-return-right me-1"></i>Footer: <span class="fw-medium text-slate-700"><?= htmlspecialchars($p['footer_label']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge px-2.5 py-1 fw-bold <?= $badgeClass ?>" style="font-size: 11px;">
                                            <?= htmlspecialchars($categories[$p['column_category']] ?? $p['column_category']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <code class="text-primary small fw-semibold text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($targetUrl) ?>">
                                                <?= htmlspecialchars($targetUrl) ?>
                                            </code>
                                            <a href="<?= htmlspecialchars($fullLiveUrl) ?>" target="_blank" class="text-slate-400 hover-text-primary small" title="Open live URL">
                                                <i class="bi bi-box-arrow-up-right" style="font-size: 11px;"></i>
                                            </a>
                                        </div>
                                    </td>

                                    <td class="text-center font-monospace small"><?= (int)$p['sort_order'] ?></td>

                                    <!-- Quick Toggle: In Footer -->
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-link p-0 text-decoration-none toggle-footer-btn" 
                                                data-id="<?= (int)$p['id'] ?>"
                                                data-state="<?= !empty($p['show_in_footer']) ? '1' : '0' ?>"
                                                title="Click to toggle footer display">
                                            <?php if (!empty($p['show_in_footer'])): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-2 py-1" style="font-size: 10px;">
                                                    <i class="bi bi-check2 me-0.5"></i> Visible
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 10px;">
                                                    <i class="bi bi-dash me-0.5"></i> Hidden
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </td>

                                    <!-- Quick Toggle: Status -->
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-link p-0 text-decoration-none toggle-status-btn" 
                                                data-id="<?= (int)$p['id'] ?>"
                                                data-state="<?= !empty($p['is_active']) ? '1' : '0' ?>"
                                                title="Click to toggle publish status">
                                            <?php if (!empty($p['is_active'])): ?>
                                                <span class="badge bg-success px-2 py-1" style="font-size: 10px;">Live</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary px-2 py-1" style="font-size: 10px;">Draft</span>
                                            <?php endif; ?>
                                        </button>
                                    </td>

                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-1.5">
                                            <a href="<?= htmlspecialchars($fullLiveUrl) ?><?= empty($p['is_active']) ? '?preview=1' : '' ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2" title="Preview page">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-bold" onclick='openEditModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit content & settings">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <form method="POST" action="<?= $baseUrl ?>/admin/pages/delete/<?= (int)$p['id'] ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete the page &quot;<?= htmlspecialchars(addslashes($p['title'])) ?>&quot;?');">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete page">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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

<!-- Include Quill Rich Text Editor Stylesheet -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<style>
.ql-toolbar.ql-snow {
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
    background-color: #f8fafc;
    border-color: #e2e8f0;
}
.ql-container.ql-snow {
    border-bottom-left-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
    border-color: #e2e8f0;
    font-size: 14px;
    font-family: inherit;
}
.nav-pills .nav-link {
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
}
.nav-pills .nav-link.active {
    background-color: #0f172a;
    color: #fff !important;
}
.nav-pills .nav-link:not(.active):hover {
    background-color: #f1f5f9;
}
#createPageModal .modal-dialog,
#editPageModal .modal-dialog {
    max-height: calc(100vh - 2rem);
}
#createPageModal .modal-content,
#editPageModal .modal-content {
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#createPageModal .modal-body,
#editPageModal .modal-body {
    overflow-y: auto !important;
    max-height: calc(100vh - 170px) !important;
    -webkit-overflow-scrolling: touch;
}
</style>

<!-- Modal: Create New Page -->
<div class="modal fade" id="createPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="createPageForm" method="POST" action="<?= $baseUrl ?>/admin/pages/create" class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 flex-shrink-0">
                <h5 class="modal-title fw-bold text-slate-900 d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-plus text-primary"></i>
                    Create Footer & CMS Page
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small text-slate-800">Page Title *</label>
                            <input type="text" name="title" id="createPageTitle" required placeholder="e.g. Production Monitoring & Inspection" class="form-control form-control-sm">
                            <div class="form-text small text-muted">Primary heading displayed at the top of the page.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-slate-800">Footer Column Category *</label>
                            <select name="column_category" class="form-select form-select-sm" required>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <option value="<?= htmlspecialchars($catKey) ?>"><?= htmlspecialchars($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small text-muted">Which of the 5 footer columns this page belongs to.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Footer Link Label (Optional)</label>
                            <input type="text" name="footer_label" id="createPageFooterLabel" placeholder="e.g. Production Monitoring & Inspection*" class="form-control form-control-sm">
                            <div class="form-text small text-muted">Short label shown in the footer. Defaults to Page Title if empty.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">URL Slug (leave blank to auto-generate)</label>
                            <input type="text" name="slug" id="createPageSlug" placeholder="e.g. production-monitoring-inspection" class="form-control form-control-sm">
                            <div class="form-text small text-muted">Unique clean URL (e.g. /production-monitoring-inspection).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Custom Redirection / External URL (Optional)</label>
                            <input type="text" name="external_url" placeholder="e.g. /buyer/messages or /sitemap.xml" class="form-control form-control-sm">
                            <div class="form-text small text-muted">If set, clicking the footer link navigates here instead of the CMS page.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Featured Hero Image URL (Optional)</label>
                            <input type="text" name="featured_image" placeholder="e.g. https://... or /assets/images/..." class="form-control form-control-sm">
                            <div class="form-text small text-muted">Banner or hero image displayed at the top of the page.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Display Order</label>
                            <input type="number" name="sort_order" value="10" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Show in Footer?</label>
                            <select name="show_in_footer" class="form-select form-select-sm">
                                <option value="1" selected>Yes — Display in Footer</option>
                                <option value="0">No — Standalone CMS Page</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Publication Status</label>
                            <select name="is_active" class="form-select form-select-sm">
                                <option value="1" selected>Published & Live</option>
                                <option value="0">Draft (Admin Only)</option>
                            </select>
                        </div>

                        <!-- SEO Metadata -->
                        <div class="col-12">
                            <div class="card border border-light-subtle bg-light bg-opacity-50 p-3 rounded-3">
                                <h6 class="fw-bold small text-slate-800 mb-2 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-search text-primary"></i> Search Engine Optimization (SEO)
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">SEO Meta Title</label>
                                        <input type="text" name="meta_title" placeholder="e.g. Production Monitoring & Inspection | Sports Junction" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">SEO Meta Description</label>
                                        <input type="text" name="meta_description" placeholder="Brief 150-character summary for Google search results..." class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- WYSIWYG Content Editor -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-slate-800">Page Content (Rich Text WYSIWYG)</label>
                            <div id="createQuillContainer" style="height: 320px;"></div>
                            <input type="hidden" name="content" id="createPageContent">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 flex-shrink-0">
                    <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                        <i class="bi bi-check-lg me-1"></i> Save New Page
                    </button>
                </div>
            </form>
        </div>
</div>

<!-- Modal: Edit Existing Page -->
<div class="modal fade" id="editPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="editPageForm" method="POST" action="" class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 flex-shrink-0">
                <h5 class="modal-title fw-bold text-slate-900 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-primary"></i>
                    Edit Footer & CMS Page
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small text-slate-800">Page Title *</label>
                            <input type="text" name="title" id="editPageTitle" required class="form-control form-control-sm">
                            <div class="form-text small text-muted">Primary heading displayed at the top of the page.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-slate-800">Footer Column Category *</label>
                            <select name="column_category" id="editPageCategory" class="form-select form-select-sm" required>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <option value="<?= htmlspecialchars($catKey) ?>"><?= htmlspecialchars($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small text-muted">Which of the 5 footer columns this page belongs to.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Footer Link Label (Optional)</label>
                            <input type="text" name="footer_label" id="editPageFooterLabel" placeholder="Short text for footer..." class="form-control form-control-sm">
                            <div class="form-text small text-muted">Short label shown in the footer. Defaults to Page Title if empty.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">URL Slug</label>
                            <input type="text" name="slug" id="editPageSlug" required class="form-control form-control-sm">
                            <div class="form-text small text-muted">Unique clean URL (e.g. /production-monitoring-inspection).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Custom Redirection / External URL (Optional)</label>
                            <input type="text" name="external_url" id="editPageUrl" placeholder="e.g. /buyer/messages or /sitemap.xml" class="form-control form-control-sm">
                            <div class="form-text small text-muted">If set, clicking the footer link navigates here instead of the CMS page.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Featured Hero Image URL (Optional)</label>
                            <input type="text" name="featured_image" id="editPageFeaturedImage" placeholder="e.g. https://... or /assets/images/..." class="form-control form-control-sm">
                            <div class="form-text small text-muted">Banner or hero image displayed at the top of the page.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Display Order</label>
                            <input type="number" name="sort_order" id="editPageOrder" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Show in Footer?</label>
                            <select name="show_in_footer" id="editPageShowInFooter" class="form-select form-select-sm">
                                <option value="1">Yes — Display in Footer</option>
                                <option value="0">No — Standalone CMS Page</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Publication Status</label>
                            <select name="is_active" id="editPageActive" class="form-select form-select-sm">
                                <option value="1">Published & Live</option>
                                <option value="0">Draft (Admin Only)</option>
                            </select>
                        </div>

                        <!-- SEO Metadata -->
                        <div class="col-12">
                            <div class="card border border-light-subtle bg-light bg-opacity-50 p-3 rounded-3">
                                <h6 class="fw-bold small text-slate-800 mb-2 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-search text-primary"></i> Search Engine Optimization (SEO)
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">SEO Meta Title</label>
                                        <input type="text" name="meta_title" id="editPageMetaTitle" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">SEO Meta Description</label>
                                        <input type="text" name="meta_description" id="editPageMetaDesc" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- WYSIWYG Content Editor -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-slate-800">Page Content (Rich Text WYSIWYG)</label>
                            <div id="editQuillContainer" style="height: 340px;"></div>
                            <input type="hidden" name="content" id="editPageContent">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 d-flex justify-content-between flex-shrink-0">
                    <div>
                        <button type="button" class="btn btn-outline-info btn-sm fw-bold" id="btnLivePreview">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Preview Live
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                            <i class="bi bi-check-lg me-1"></i> Update Page
                        </button>
                    </div>
                </div>
            </form>
        </div>
</div>

<!-- Include Quill Rich Text Editor Script -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
let createQuill = null;
let editQuill = null;

const quillToolbarOptions = [
    [{ 'header': [1, 2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'align': [] }],
    ['link', 'blockquote', 'code-block'],
    ['clean']
];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Create Quill
    createQuill = new Quill('#createQuillContainer', {
        theme: 'snow',
        placeholder: 'Compose rich page content here, include headings, formatted text, and bullet points...',
        modules: {
            toolbar: quillToolbarOptions
        }
    });

    // Initialize Edit Quill
    editQuill = new Quill('#editQuillContainer', {
        theme: 'snow',
        placeholder: 'Edit page content here...',
        modules: {
            toolbar: quillToolbarOptions
        }
    });

    // Auto slug on Create modal
    const createTitle = document.getElementById('createPageTitle');
    const createSlug = document.getElementById('createPageSlug');
    if (createTitle && createSlug) {
        createTitle.addEventListener('blur', function() {
            if (!createSlug.value) {
                createSlug.value = createTitle.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    }

    // Sync Create Form
    const createForm = document.getElementById('createPageForm');
    if (createForm) {
        createForm.addEventListener('submit', function() {
            document.getElementById('createPageContent').value = createQuill.root.innerHTML;
        });
    }

    // Sync Edit Form
    const editForm = document.getElementById('editPageForm');
    if (editForm) {
        editForm.addEventListener('submit', function() {
            document.getElementById('editPageContent').value = editQuill.root.innerHTML;
        });
    }

    // Live Preview Button in Edit Modal
    const previewBtn = document.getElementById('btnLivePreview');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            const slug = document.getElementById('editPageSlug').value;
            const extUrl = document.getElementById('editPageUrl').value;
            if (extUrl && extUrl.trim().length > 0) {
                window.open(extUrl.startsWith('http') ? extUrl : '<?= $baseUrl ?>' + extUrl, '_blank');
            } else if (slug && slug.trim().length > 0) {
                window.open('<?= $baseUrl ?>/' + encodeURIComponent(slug) + '?preview=1', '_blank');
            } else {
                alert('Please provide a slug or save the page to preview.');
            }
        });
    }

    // Filter Pills Handler
    let activeCategory = 'ALL';
    let searchKeyword = '';
    let statusFilter = 'ALL';

    const filterPills = document.querySelectorAll('#columnFilterPills button');
    filterPills.forEach(btn => {
        btn.addEventListener('click', function() {
            filterPills.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.getAttribute('data-column') || 'ALL';
            applyFilters();
        });
    });

    // Search Input Handler
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchKeyword = this.value.trim().toLowerCase();
            applyFilters();
        });
    }

    // Status Filter Handler
    const statusSelect = document.getElementById('statusFilterSelect');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            statusFilter = this.value;
            applyFilters();
        });
    }

    function applyFilters() {
        const rows = document.querySelectorAll('.page-row');
        rows.forEach(row => {
            const cat = row.getAttribute('data-category') || '';
            const status = row.getAttribute('data-status') || '';
            const searchText = row.getAttribute('data-search') || '';

            const matchCat = (activeCategory === 'ALL' || cat === activeCategory);
            const matchStatus = (statusFilter === 'ALL' || status === statusFilter);
            const matchSearch = (!searchKeyword || searchText.includes(searchKeyword));

            if (matchCat && matchStatus && matchSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Toggle Status Quick Button
    document.querySelectorAll('.toggle-status-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const pageId = this.getAttribute('data-id');
            const self = this;
            fetch(`<?= $baseUrl ?>/api/admin/pages/${pageId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = self.closest('.page-row');
                    if (data.is_active) {
                        self.setAttribute('data-state', '1');
                        self.innerHTML = '<span class="badge bg-success px-2 py-1" style="font-size: 10px;">Live</span>';
                        if (row) row.setAttribute('data-status', 'ACTIVE');
                    } else {
                        self.setAttribute('data-state', '0');
                        self.innerHTML = '<span class="badge bg-secondary px-2 py-1" style="font-size: 10px;">Draft</span>';
                        if (row) row.setAttribute('data-status', 'DRAFT');
                    }
                    applyFilters();
                } else {
                    alert(data.message || 'Failed to update status');
                }
            })
            .catch(() => alert('Network error while toggling status.'));
        });
    });

    // Toggle Footer Visibility Quick Button
    document.querySelectorAll('.toggle-footer-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const pageId = this.getAttribute('data-id');
            const self = this;
            fetch(`<?= $baseUrl ?>/api/admin/pages/${pageId}/toggle-footer`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = self.closest('.page-row');
                    if (data.show_in_footer) {
                        self.setAttribute('data-state', '1');
                        self.innerHTML = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-2 py-1" style="font-size: 10px;"><i class="bi bi-check2 me-0.5"></i> Visible</span>';
                        if (row) row.setAttribute('data-footer', '1');
                    } else {
                        self.setAttribute('data-state', '0');
                        self.innerHTML = '<span class="badge bg-light text-muted border px-2 py-1" style="font-size: 10px;"><i class="bi bi-dash me-0.5"></i> Hidden</span>';
                        if (row) row.setAttribute('data-footer', '0');
                    }
                } else {
                    alert(data.message || 'Failed to update footer visibility');
                }
            })
            .catch(() => alert('Network error while toggling footer visibility.'));
        });
    });
});

function openEditModal(page) {
    const form = document.getElementById('editPageForm');
    form.action = `<?= $baseUrl ?>/admin/pages/edit/${page.id}`;
    document.getElementById('editPageTitle').value = page.title || '';
    document.getElementById('editPageFooterLabel').value = page.footer_label || '';
    document.getElementById('editPageCategory').value = page.column_category || 'ABOUT';
    document.getElementById('editPageSlug').value = page.slug || '';
    document.getElementById('editPageUrl').value = page.external_url || '';
    document.getElementById('editPageFeaturedImage').value = page.featured_image || '';
    document.getElementById('editPageOrder').value = page.sort_order || 0;
    document.getElementById('editPageShowInFooter').value = (page.show_in_footer !== undefined && page.show_in_footer !== null) ? page.show_in_footer : '1';
    document.getElementById('editPageActive').value = page.is_active ? '1' : '0';
    document.getElementById('editPageMetaTitle').value = page.meta_title || '';
    document.getElementById('editPageMetaDesc').value = page.meta_description || '';
    
    // Set Quill HTML content
    if (editQuill) {
        editQuill.root.innerHTML = page.content || '';
    } else {
        document.getElementById('editPageContent').value = page.content || '';
    }

    const modal = new bootstrap.Modal(document.getElementById('editPageModal'));
    modal.show();
}
</script>
