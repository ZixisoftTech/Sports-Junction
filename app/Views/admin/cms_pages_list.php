<?php
/**
 * Admin CMS Pages List & Editor View
 * Manage dynamic footer pages and content
 */
$baseUrl = $baseUrl ?? '';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/settings" class="text-decoration-none">Website Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Footer CMS Pages</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-slate-900 mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-richtext text-primary"></i>
                Dynamic Footer & CMS Pages
            </h1>
            <p class="text-muted small mb-0">Create, edit, and organize pages displayed under the 5 columns of the marketplace footer.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm fw-bold shadow-xs" data-bs-toggle="modal" data-bs-target="#createPageModal">
                <i class="bi bi-plus-lg me-1"></i> Create New Page
            </button>
            <a href="<?= $baseUrl ?>/admin/settings" class="btn btn-outline-secondary btn-sm fw-bold">
                <i class="bi bi-gear me-1"></i> Website Settings
            </a>
        </div>
    </div>

    <?php if ($success = \App\Helpers\Session::getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div><?= htmlspecialchars($success) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error = \App\Helpers\Session::getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= htmlspecialchars($error) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Table of Pages -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0 text-slate-900">Configured Footer Links & Pages (<?= count($pages) ?>)</h6>
            <div class="small text-muted">Grouped by Footer Column</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Title</th>
                            <th>Footer Column</th>
                            <th>Slug / Target URL</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No footer pages configured. Click "Create New Page" to add one.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-slate-900"><?= htmlspecialchars($p['title']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold" style="font-size: 11px;">
                                            <?= htmlspecialchars($categories[$p['column_category']] ?? $p['column_category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code class="text-primary small">
                                            <?= !empty($p['external_url']) ? htmlspecialchars($p['external_url']) : ('/page/' . htmlspecialchars($p['slug'])) ?>
                                        </code>
                                    </td>
                                    <td><?= (int)$p['sort_order'] ?></td>
                                    <td>
                                        <?php if (!empty($p['is_active'])): ?>
                                            <span class="badge bg-success" style="font-size: 10px;">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" style="font-size: 10px;">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('d M Y', strtotime($p['updated_at'] ?? $p['created_at'])) ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-1.5">
                                            <?php if (empty($p['external_url'])): ?>
                                                <a href="<?= $baseUrl ?>/page/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2" title="View live page">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-bold" onclick='openEditModal(<?= json_encode($p) ?>)' title="Edit page content">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <form method="POST" action="<?= $baseUrl ?>/admin/pages/delete/<?= (int)$p['id'] ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this footer page?');">
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

<!-- Modal: Create New Page -->
<div class="modal fade" id="createPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= $baseUrl ?>/admin/pages/create">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-slate-900 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-plus text-primary"></i>
                        Create Footer & CMS Page
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-slate-800">Page Title *</label>
                            <input type="text" name="title" required placeholder="e.g. Export Compliance & Customs Guide" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Footer Column Category *</label>
                            <select name="column_category" class="form-select form-select-sm" required>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <option value="<?= htmlspecialchars($catKey) ?>"><?= htmlspecialchars($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">URL Slug (leave blank to auto-generate)</label>
                            <input type="text" name="slug" placeholder="e.g. export-compliance-guide" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Custom Redirection URL (Optional)</label>
                            <input type="text" name="external_url" placeholder="e.g. /seller/login or https://..." class="form-control form-control-sm">
                            <div class="form-text small" style="font-size: 11px;">If provided, clicking this link navigates directly to this URL instead of the CMS page.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Display Order</label>
                            <input type="number" name="sort_order" value="0" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Visibility Status</label>
                            <select name="is_active" class="form-select form-select-sm">
                                <option value="1">Active (Visible in Footer)</option>
                                <option value="0">Draft (Hidden)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-slate-800">Page Content (HTML / Rich Text)</label>
                            <textarea name="content" rows="8" placeholder="<h2>Your Page Title</h2><p>Provide detailed guidance or policy documentation here...</p>" class="form-control text-sm font-monospace"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5">
                    <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                        <i class="bi bi-check-lg me-1"></i> Save New Page
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Existing Page -->
<div class="modal fade" id="editPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form id="editPageForm" method="POST" action="">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-slate-900 d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-primary"></i>
                        Edit Footer & CMS Page
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-slate-800">Page Title *</label>
                            <input type="text" name="title" id="editPageTitle" required class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-slate-800">Footer Column Category *</label>
                            <select name="column_category" id="editPageCategory" class="form-select form-select-sm" required>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <option value="<?= htmlspecialchars($catKey) ?>"><?= htmlspecialchars($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">URL Slug</label>
                            <input type="text" name="slug" id="editPageSlug" required class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Custom Redirection URL (Optional)</label>
                            <input type="text" name="external_url" id="editPageUrl" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Display Order</label>
                            <input type="number" name="sort_order" id="editPageOrder" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-800">Visibility Status</label>
                            <select name="is_active" id="editPageActive" class="form-select form-select-sm">
                                <option value="1">Active (Visible in Footer)</option>
                                <option value="0">Draft (Hidden)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-slate-800">Page Content (HTML / Rich Text)</label>
                            <textarea name="content" id="editPageContent" rows="9" class="form-control text-sm font-monospace"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5">
                    <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                        <i class="bi bi-check-lg me-1"></i> Update Page
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(page) {
    const form = document.getElementById('editPageForm');
    form.action = `<?= $baseUrl ?>/admin/pages/edit/${page.id}`;
    document.getElementById('editPageTitle').value = page.title || '';
    document.getElementById('editPageCategory').value = page.column_category || 'ABOUT';
    document.getElementById('editPageSlug').value = page.slug || '';
    document.getElementById('editPageUrl').value = page.external_url || '';
    document.getElementById('editPageOrder').value = page.sort_order || 0;
    document.getElementById('editPageActive').value = page.is_active ? '1' : '0';
    document.getElementById('editPageContent').value = page.content || '';

    const modal = new bootstrap.Modal(document.getElementById('editPageModal'));
    modal.show();
}
</script>
