<?php
/**
 * Super Admin — Dedicated Seller Agreement Management
 */
$baseUrl = !empty($baseUrl) ? $baseUrl : \App\Core\Response::baseUrl();
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/pages" class="text-decoration-none">Website &amp; CMS</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Seller Agreement</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-slate-900 mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text-fill text-primary"></i>
                <span>Seller &amp; Manufacturer Agreement</span>
            </h1>
            <p class="text-muted small mb-0">Manage official B2B manufacturing terms, compliance standards, and legal agreement required for seller onboarding.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $baseUrl ?>/page/seller-agreement" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
                <i class="bi bi-box-arrow-up-right me-1"></i> View Public Agreement
            </a>
            <a href="<?= $baseUrl ?>/supplier/register" target="_blank" class="btn btn-outline-secondary btn-sm fw-bold">
                <i class="bi bi-person-plus me-1"></i> Seller Registration
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

    <form action="<?= $baseUrl ?>/admin/seller-agreement" method="POST" id="sellerAgreementForm">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\CSRF::token() ?>">

        <div class="row g-4">
            <!-- Left Column: Agreement Content Editor -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="fw-bold text-slate-900">
                            <i class="bi bi-pencil-square me-1 text-primary"></i> Agreement Document Content
                        </div>
                        <div class="small text-muted" id="wordCountBadge">
                            Calculating words...
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Agreement Document Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" required value="<?= htmlspecialchars($page['title'] ?? 'Seller & Manufacturer Agreement') ?>" class="form-control form-control-lg fw-semibold" placeholder="e.g. Seller & Manufacturer Master Agreement">
                        </div>

                        <!-- Quick Format Toolbar -->
                        <div class="mb-2 p-2 bg-light border rounded-top d-flex flex-wrap gap-1 align-items-center">
                            <span class="small text-muted me-2 fw-bold">Insert Formatting:</span>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2" onclick="insertTag('h2')">&lt;h2&gt;</button>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2" onclick="insertTag('h3')">&lt;h3&gt;</button>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2" onclick="insertTag('p')">&lt;p&gt;</button>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2 fw-bold" onclick="insertTag('strong')">&lt;b&gt;</button>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2 fst-italic" onclick="insertTag('em')">&lt;i&gt;</button>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2" onclick="insertTag('ul')">&lt;ul&gt;</button>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2" onclick="insertTag('li')">&lt;li&gt;</button>
                            <button type="button" class="btn btn-sm btn-white border shadow-2xs py-1 px-2" onclick="insertTag('blockquote')">&lt;quote&gt;</button>
                            <span class="ms-auto small text-muted">Safe HTML tags supported</span>
                        </div>

                        <div class="mb-3">
                            <textarea name="content" id="agreementContent" rows="18" required class="form-control font-monospace text-sm" style="line-height: 1.6;"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings & Publishing Controls -->
            <div class="col-lg-4">
                <!-- Publishing Status Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom fw-bold text-slate-900">
                        <i class="bi bi-broadcast me-1 text-primary"></i> Publishing Status
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted d-block">Publication State</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= (!empty($page['is_active'])) ? 'selected' : '' ?>>Published (Live on Public Portal)</option>
                                <option value="0" <?= (empty($page['is_active'])) ? 'selected' : '' ?>>Draft / Hidden</option>
                            </select>
                            <div class="form-text small">When published, this agreement is linked directly from the Seller Registration form.</div>
                        </div>

                        <div class="mb-3 pb-3 border-bottom text-xs text-muted">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Public Slug:</span>
                                <span class="font-monospace text-dark fw-bold">/page/seller-agreement</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Short URL:</span>
                                <span class="font-monospace text-dark fw-bold">/seller-agreement</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Last Updated:</span>
                                <span class="text-dark fw-bold"><?= !empty($page['updated_at']) ? date('M d, Y H:i', strtotime($page['updated_at'])) : 'Recently' ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check2-circle fs-5"></i>
                            <span>Save &amp; Publish Agreement</span>
                        </button>
                    </div>
                </div>

                <!-- SEO & Metadata Card -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-bottom fw-bold text-slate-900">
                        <i class="bi bi-search me-1 text-primary"></i> SEO &amp; Meta Information
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Meta Title</label>
                            <input type="text" name="meta_title" value="<?= htmlspecialchars($page['meta_title'] ?? '') ?>" class="form-control" placeholder="Meta title for search engines">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Meta Description</label>
                            <textarea name="meta_description" rows="4" class="form-control text-sm" placeholder="Brief summary of seller agreement for search engine results..."><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function insertTag(tag) {
    const textarea = document.getElementById('agreementContent');
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    const replacement = `<${tag}>${selectedText || 'Content here'}</${tag}>`;
    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
    updateWordCount();
}

function updateWordCount() {
    const textarea = document.getElementById('agreementContent');
    const badge = document.getElementById('wordCountBadge');
    if (!textarea || !badge) return;
    const text = textarea.value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    const words = text ? text.split(' ').length : 0;
    badge.textContent = `${words} words`;
}

document.addEventListener('DOMContentLoaded', () => {
    updateWordCount();
    const textarea = document.getElementById('agreementContent');
    if (textarea) {
        textarea.addEventListener('input', updateWordCount);
    }
});
</script>
