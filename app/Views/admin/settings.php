<?php
/**
 * Admin Website Settings View
 * Manage Website Logo, Contact Info, Social Media Handles & Footer Links
 */
$baseUrl = !empty($baseUrl) ? $baseUrl : \App\Core\Response::baseUrl();
$logoUrl = \App\Models\SiteSetting::getLogoUrl();
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
                                    <img id="logoPreview" src="<?= htmlspecialchars($logoUrl) ?>" alt="Site Logo" style="max-height: 70px; max-width: 180px; object-fit: contain;" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.png';">
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

                <!-- Card 4: Email & Passcode Delivery -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock-fill text-primary"></i>
                            Passcode & Email Authentication Mode
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">Control whether the platform uses static passcode verification (<code class="fw-bold text-primary">123456</code>) or dynamic codes delivered through live SMTP.</p>

                        <?php $currentStatic = \App\Models\Otp::isStaticOtpEnabled(); ?>
                        <div class="p-3 rounded-3 <?= $currentStatic ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200' ?> mb-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge <?= $currentStatic ? 'bg-amber-600' : 'bg-success' ?>">
                                    <?= $currentStatic ? 'STATIC PASSCODE (123456) ACTIVE' : 'LIVE SMTP DELIVERY ACTIVE' ?>
                                </span>
                            </div>
                            <div class="small text-slate-700">
                                <?= $currentStatic 
                                    ? 'Static passcode <strong class="text-blue-900 font-monospace">123456</strong> is enabled for Buyer Registration, Supplier Registration, and Password Reset.' 
                                    : 'Live random 6-digit OTP codes are generated and dispatched to the user’s inbox via SMTP.' ?>
                            </div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="useStaticOtpSwitch" name="use_static_otp" value="1" <?= $currentStatic ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold small text-slate-800" for="useStaticOtpSwitch">
                                Use static passcode <span class="badge bg-secondary">123456</span> until SMTP is set up
                            </label>
                            <div class="form-text small text-muted">
                                Uncheck this once your SMTP host, port, and credentials are configured in your environment to enable real email deliveries.
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

    <!-- HOMEPAGE CATEGORY DISPLAY CONFIGURATION -->
    <?php
    $selectedIdsList = [];
    if (!empty($homepageCategoriesSelected)) {
        $selectedIdsList = array_filter(array_map('intval', explode(',', (string)$homepageCategoriesSelected)));
    }
    $hasExplicitSelection = !empty($selectedIdsList);
    $selectedLookup = array_flip($selectedIdsList);

    // Prepare sorted categories
    $sortedCategories = $allCategories ?? [];
    usort($sortedCategories, function($a, $b) use ($selectedLookup, $hasExplicitSelection) {
        $idA = (int)$a['id'];
        $idB = (int)$b['id'];
        if ($hasExplicitSelection) {
            $inA = isset($selectedLookup[$idA]);
            $inB = isset($selectedLookup[$idB]);
            if ($inA && $inB) {
                return $selectedLookup[$idA] <=> $selectedLookup[$idB];
            }
            if ($inA) return -1;
            if ($inB) return 1;
        }
        return ((int)($a['display_order'] ?? 0)) <=> ((int)($b['display_order'] ?? 0));
    });

    $maxDisplay = !empty($homepageCategoriesMax) ? (int)$homepageCategoriesMax : 8;
    ?>
    <div class="mt-4 card border-0 shadow-sm rounded-3" id="homepage-categories-card">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                    <i class="bi bi-grid-3x3-gap-fill text-primary"></i>
                    Homepage Category Display Configuration
                </h6>
                <div class="text-muted small">Manage which categories appear in the "Popular Sports Sourcing Categories" grid on the homepage, along with display limits and custom ordering.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $baseUrl ?>/#categories" target="_blank" class="btn btn-sm btn-outline-primary fw-bold">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Preview On Homepage
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="<?= $baseUrl ?>/admin/settings/homepage-categories">
                <!-- Top Controls: Enable Switch & Max Limit -->
                <div class="row g-4 mb-4 pb-3 border-bottom align-items-center">
                    <div class="col-md-6">
                        <div class="form-check form-switch ps-0 d-flex align-items-center gap-3">
                            <input class="form-check-input ms-0 fs-4" type="checkbox" role="switch" id="homepage_categories_enabled" name="homepage_categories_enabled" value="1" <?= ($homepageCategoriesEnabled !== '0') ? 'checked' : '' ?>>
                            <div>
                                <label class="form-check-label fw-bold text-slate-900 d-block cursor-pointer" for="homepage_categories_enabled">
                                    Enable Homepage Categories Section
                                </label>
                                <span class="text-muted small">When turned OFF, the sourcing categories section is completely and cleanly hidden from the public homepage.</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-md-end gap-3">
                            <label class="form-label fw-bold small text-slate-800 mb-0 whitespace-nowrap">
                                Max Categories to Display:
                            </label>
                            <input type="number" name="homepage_categories_max" min="1" max="50" value="<?= $maxDisplay ?>" class="form-control form-control-sm text-center" style="width: 80px;" required>
                            <span class="text-muted small">(Default: 8)</span>
                        </div>
                    </div>
                </div>

                <!-- Category Selection & Ordering Table -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold small text-slate-800">Select & Reorder Categories:</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllCategories(true)">Select All</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllCategories(false)">Deselect All</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectTopN(8)">Select Top 8</button>
                        </div>
                    </div>
                    <div style="max-width: 250px;" class="w-100">
                        <input type="text" id="categoryFilterInput" placeholder="Quick search category..." class="form-control form-control-sm" onkeyup="filterCategoryTable(this.value)">
                    </div>
                </div>

                <div class="table-responsive border rounded-3 mb-4" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 text-sm" id="homepageCategoriesTable">
                        <thead class="table-light text-uppercase small text-muted sticky-top">
                            <tr>
                                <th style="width: 48px;" class="text-center">Select</th>
                                <th style="width: 90px;" class="text-center">Order</th>
                                <th>Category Name & Slug</th>
                                <th style="width: 140px;" class="text-center">Active Products</th>
                                <th style="width: 110px;" class="text-center">Reorder</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sortedCategories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No active categories found in database.</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                foreach ($sortedCategories as $idx => $cat): 
                                    $cid = (int)$cat['id'];
                                    $isSelected = $hasExplicitSelection 
                                        ? isset($selectedLookup[$cid]) 
                                        : ($idx < $maxDisplay);
                                    $orderValue = $hasExplicitSelection && isset($selectedLookup[$cid]) 
                                        ? ($selectedLookup[$cid] + 1) 
                                        : ($idx + 1);
                                ?>
                                    <tr class="category-row <?= $isSelected ? 'table-active-subtle' : '' ?>" data-id="<?= $cid ?>">
                                        <td class="text-center">
                                            <input type="checkbox" name="selected_categories[]" value="<?= $cid ?>" class="form-check-input cat-checkbox" <?= $isSelected ? 'checked' : '' ?>>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="category_order[<?= $cid ?>]" value="<?= $orderValue ?>" min="1" max="999" class="form-control form-control-sm text-center cat-order-input px-1" style="width: 65px; margin: 0 auto;">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded bg-light border p-1 text-center" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <span class="material-symbols-outlined text-primary fs-6"><?= htmlspecialchars($cat['icon'] ?? 'sports_tennis') ?></span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-slate-900 cat-name"><?= htmlspecialchars($cat['name']) ?></div>
                                                    <div class="text-muted small" style="font-size: 11px;">/category/<?= htmlspecialchars($cat['slug']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php $pCount = (int)($cat['product_count'] ?? 0); ?>
                                            <span class="badge <?= $pCount > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' ?>">
                                                <?= $pCount ?> product<?= $pCount === 1 ? '' : 's' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light border py-0 px-2" title="Move Up" onclick="moveCategoryRow(this, -1)">
                                                    <i class="bi bi-arrow-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-light border py-0 px-2" title="Move Down" onclick="moveCategoryRow(this, 1)">
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons: Save & Reset -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2">
                    <button type="submit" name="action" value="reset" class="btn btn-outline-danger fw-bold btn-sm" onclick="return confirm('Are you sure you want to reset homepage categories to system defaults?');">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default Order
                    </button>
                    <button type="submit" name="action" value="save" class="btn btn-primary fw-bold px-4 shadow-xs">
                        <i class="bi bi-check-lg me-1"></i> Save Homepage Category Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

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

function moveCategoryRow(btn, direction) {
    const row = btn.closest('tr');
    const tbody = row.parentElement;
    if (direction === -1 && row.previousElementSibling) {
        tbody.insertBefore(row, row.previousElementSibling);
    } else if (direction === 1 && row.nextElementSibling) {
        tbody.insertBefore(row.nextElementSibling, row);
    }
    renumberCategoryOrders();
}

function renumberCategoryOrders() {
    const rows = document.querySelectorAll('#homepageCategoriesTable tbody tr.category-row');
    rows.forEach((r, idx) => {
        const orderInput = r.querySelector('.cat-order-input');
        if (orderInput) {
            orderInput.value = idx + 1;
        }
    });
}

function selectAllCategories(status) {
    document.querySelectorAll('#homepageCategoriesTable .cat-checkbox').forEach(cb => {
        cb.checked = status;
    });
}

function selectTopN(n) {
    const checkboxes = document.querySelectorAll('#homepageCategoriesTable .cat-checkbox');
    checkboxes.forEach((cb, idx) => {
        cb.checked = (idx < n);
    });
}

function filterCategoryTable(query) {
    const term = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#homepageCategoriesTable tbody tr.category-row');
    rows.forEach(r => {
        const nameEl = r.querySelector('.cat-name');
        const text = nameEl ? nameEl.textContent.toLowerCase() : '';
        r.style.display = text.includes(term) ? '' : 'none';
    });
}
</script>
