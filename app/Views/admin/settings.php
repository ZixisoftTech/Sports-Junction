<?php
/**
 * Admin Website Settings View
 * Manage Website Logo, Contact Info, Social Media Handles & Footer Links
 */
$baseUrl = $baseUrl ?? '';
$logoUrl = !empty($settings['site_logo']) ? ($baseUrl . $settings['site_logo']) : ($baseUrl . '/assets/images/logo.jpg');
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Website Settings</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-slate-900 mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-gear-fill text-primary"></i>
                Website Branding, Contact & Social Settings
            </h1>
            <p class="text-muted small mb-0">Control marketplace branding, official support contact details, and social channels displayed across the website and footer.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $baseUrl ?>/admin/pages" class="btn btn-outline-primary btn-sm fw-bold">
                <i class="bi bi-file-earmark-richtext me-1"></i> Manage Footer CMS Pages
            </a>
            <a href="<?= $baseUrl ?>/" target="_blank" class="btn btn-primary btn-sm fw-bold">
                <i class="bi bi-box-arrow-up-right me-1"></i> View Live Site
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

    <form method="POST" action="<?= $baseUrl ?>/admin/settings/general" enctype="multipart/form-data">
        <div class="row g-4">
            
            <!-- LEFT COLUMN: BRANDING & CONTACT INFO -->
            <div class="col-lg-7">
                
                <!-- Card 1: Logo & Branding -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                            <i class="bi bi-image text-primary"></i>
                            Marketplace Logo & Identity
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center mb-4">
                            <div class="col-sm-4 text-center mb-3 mb-sm-0">
                                <label class="small text-muted fw-bold d-block mb-2">Current Logo</label>
                                <div class="p-2 border rounded-3 bg-light d-inline-block shadow-2xs">
                                    <img id="logoPreview" src="<?= htmlspecialchars($logoUrl) ?>" alt="Site Logo" style="max-height: 70px; max-width: 180px; object-fit: contain;">
                                </div>
                            </div>
                            <div class="col-sm-8">
                                <label class="form-label fw-bold small text-slate-800">Change Website Logo</label>
                                <input type="file" name="site_logo" id="siteLogoInput" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="form-control form-control-sm mb-1" onchange="previewLogo(this)">
                                <div class="form-text small" style="font-size: 11px;">Recommended: 240×60px (4:1 horizontal ratio), Transparent PNG, SVG, JPG, or WEBP &bull; Max size: 5MB.</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-slate-800">Website Name</label>
                                <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'Sports Junction') ?>" required class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-slate-800">Marketplace Tagline</label>
                                <input type="text" name="site_tagline" value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>" placeholder="e.g. India's Sports Manufacturing Marketplace" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Contact Information -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                            <i class="bi bi-telephone-fill text-primary"></i>
                            Official Customer & Support Contact Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-slate-800">Support Phone / WhatsApp</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" placeholder="+91 98765 43210" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-slate-800">Support Email Address</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" placeholder="support@sportsjunction.in" class="form-control">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-slate-800">Corporate & Manufacturing Hub Address</label>
                                <textarea name="contact_address" rows="2" placeholder="Factory complex, city, state, postal code" class="form-control form-control-sm"><?= htmlspecialchars($settings['contact_address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-slate-800">Operating / Business Hours</label>
                                <input type="text" name="working_hours" value="<?= htmlspecialchars($settings['working_hours'] ?? 'Mon - Sat: 9:00 AM - 7:00 PM IST') ?>" placeholder="Mon - Sat: 9:00 AM - 7:00 PM IST" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: SOCIAL MEDIA CHANNELS -->
            <div class="col-lg-5">
                
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                            <i class="bi bi-share-fill text-primary"></i>
                            Social Media Links (Footer "Stay Connected")
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">Links to official company profiles shown in the Alibaba-style footer bottom right.</p>

                        <div class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label fw-bold small text-slate-800 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-facebook text-primary fs-6"></i> Facebook URL
                                </label>
                                <input type="url" name="social_facebook" value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/yourpage" class="form-control form-control-sm">
                            </div>

                            <div>
                                <label class="form-label fw-bold small text-slate-800 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-twitter-x fs-6"></i> Twitter / X URL
                                </label>
                                <input type="url" name="social_twitter" value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>" placeholder="https://x.com/yourhandle" class="form-control form-control-sm">
                            </div>

                            <div>
                                <label class="form-label fw-bold small text-slate-800 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-instagram text-danger fs-6"></i> Instagram URL
                                </label>
                                <input type="url" name="social_instagram" value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/yourhandle" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Action Card -->
                <div class="card border-0 shadow-sm rounded-3 bg-light">
                    <div class="card-body p-3 text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-xs">
                            <i class="bi bi-check-lg me-1"></i> Save Website Settings
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </form>

    <!-- FOOTER CMS PAGES QUICK REFERENCE -->
    <div class="mt-4 card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div>
                <h6 class="fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                    <i class="bi bi-layout-text-window-reverse text-primary"></i>
                    Dynamic Footer Pages (Alibaba 5-Column Grid)
                </h6>
                <div class="text-muted small">All these links render live in the frontend footer and can be edited anytime.</div>
            </div>
            <a href="<?= $baseUrl ?>/admin/pages" class="btn btn-sm btn-primary fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Add / Edit Footer Pages
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Page Title</th>
                            <th>Category Column</th>
                            <th>Slug / Target URL</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No footer pages configured yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-slate-900">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
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
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-muted">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if (empty($p['external_url'])): ?>
                                            <a href="<?= $baseUrl ?>/page/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2" title="View Page">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= $baseUrl ?>/admin/pages" class="btn btn-sm btn-outline-primary py-1 px-2">
                                            <i class="bi bi-pencil-square"></i> Edit
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
</div>

<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
