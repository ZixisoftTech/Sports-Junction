<?php
/**
 * CMS Page View
 * Sports Junction B2B Marketplace
 */
$baseUrl = $baseUrl ?? \App\Core\Response::baseUrl();
$siteName = $siteSettings['site_name'] ?? 'Sports Junction';
$phone = $siteSettings['contact_phone'] ?? '+91 98765 43210';
$email = $siteSettings['contact_email'] ?? 'support@sportsjunction.in';
$hours = $siteSettings['working_hours'] ?? 'Mon - Sat: 9:00 AM - 7:00 PM IST';
$slug = strtolower(trim((string)($page['slug'] ?? '')));
$isFeedback = ($slug === 'feedback-suggestions');
$isSitemap = ($slug === 'sitemap' || $slug === 'xml-sitemap');
?>

<div class="bg-surface-container-lowest min-h-screen py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (!empty($isDraftPreview)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">visibility</span>
                    <div>
                        <div class="font-bold text-sm">Super Admin Preview Mode</div>
                        <div class="text-xs text-amber-700">This CMS page is currently in <strong>Draft / Unpublished</strong> status and is not visible to the general public.</div>
                    </div>
                </div>
                <a href="<?= $baseUrl ?>/admin/pages" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-colors shrink-0">
                    Return to CMS
                </a>
            </div>
        <?php endif; ?>

        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-on-surface-variant mb-6 font-medium">
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="hover:text-primary transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">home</span> Home
            </a>
            <span class="text-outline-variant">/</span>
            <span class="text-on-surface font-semibold"><?= htmlspecialchars(preg_replace('/^Column\s*\d+\s*[-—:]\s*/i', '', (string)$categoryName)) ?></span>
            <span class="text-outline-variant">/</span>
            <span class="text-primary font-bold truncate max-w-xs"><?= htmlspecialchars($page['title']) ?></span>
        </nav>

        <!-- Main Content Area -->
        <article class="bg-white rounded-2xl border border-surface-container p-6 sm:p-10 shadow-xs">
            
            <header class="mb-8 pb-6 border-b border-surface-container">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-primary text-[11px] font-bold mb-3">
                    <span class="material-symbols-outlined text-[14px]">verified</span>
                    <span><?= htmlspecialchars($categoryName) ?></span>
                </div>
                <h1 class="font-headline font-black text-2xl sm:text-3xl text-slate-900 leading-tight">
                    <?= htmlspecialchars($page['title']) ?>
                </h1>
                <div class="flex flex-wrap items-center gap-3 mt-3 text-xs text-slate-500">
                    <span>Published on <?= date('d M Y', strtotime($page['created_at'])) ?></span>
                    <span>•</span>
                    <span>Updated <?= date('d M Y', strtotime($page['updated_at'] ?? $page['created_at'])) ?></span>
                    <?php if (!empty($page['show_in_footer'])): ?>
                        <span>•</span>
                        <span class="text-teal-700 font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">link</span>
                            <span>Footer Link Active</span>
                        </span>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (!empty($page['featured_image'])): ?>
                <div class="mb-8 rounded-xl overflow-hidden border border-slate-100 max-h-96">
                    <img src="<?= htmlspecialchars($page['featured_image']) ?>" alt="<?= htmlspecialchars($page['title']) ?>" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>

            <!-- Rich Content Body -->
            <style>
                .cms-content h1, .cms-content h2, .cms-content h3, .cms-content h4 {
                    color: #0f172a;
                    font-weight: 700;
                    margin-top: 1.5rem;
                    margin-bottom: 0.75rem;
                    line-height: 1.3;
                }
                .cms-content h1 { font-size: 1.75rem; }
                .cms-content h2 { font-size: 1.35rem; }
                .cms-content h3 { font-size: 1.15rem; }
                .cms-content p {
                    margin-bottom: 1rem;
                    line-height: 1.7;
                    color: #334155;
                }
                .cms-content ul, .cms-content ol {
                    margin-left: 1.5rem;
                    margin-bottom: 1rem;
                }
                .cms-content ul { list-style-type: disc; }
                .cms-content ol { list-style-type: decimal; }
                .cms-content li { margin-bottom: 0.35rem; color: #334155; }
                .cms-content a { color: #00288e; text-decoration: underline; font-weight: 600; }
                .cms-content blockquote {
                    border-left: 4px solid #00288e;
                    padding-left: 1rem;
                    margin: 1.25rem 0;
                    font-style: italic;
                    color: #475569;
                    background: #f8f9ff;
                    padding: 0.75rem 1rem;
                    border-radius: 0 0.5rem 0.5rem 0;
                }
                .cms-content table {
                    width: 100%;
                    margin-bottom: 1.5rem;
                    border-collapse: collapse;
                }
                .cms-content th, .cms-content td {
                    padding: 0.75rem 1rem;
                    border: 1px solid #e2e8f0;
                }
                .cms-content th {
                    background-color: #f8fafc;
                    font-weight: 700;
                    text-align: left;
                }
            </style>

            <div class="prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed space-y-4 cms-content">
                <?= $page['content'] ?>
            </div>

            <!-- Specialized Interactive Widget: Feedback & Suggestions -->
            <?php if ($isFeedback): ?>
                <div class="mt-10 pt-8 border-t border-slate-200">
                    <div class="bg-slate-50 rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-2xs" id="feedbackFormSection">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-primary text-2xl">rate_review</span>
                            <h3 class="font-bold text-slate-900 text-lg m-0">Submit Your Feedback &amp; Suggestions</h3>
                        </div>
                        <p class="text-xs text-slate-600 mb-6">Let us know how we can improve your sourcing or manufacturing experience on Sports Junction.</p>

                        <form id="publicFeedbackForm" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Your Full Name *</label>
                                    <input type="text" name="name" id="fbName" required placeholder="e.g. Rahul Sharma" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Corporate / Business Email *</label>
                                    <input type="email" name="email" id="fbEmail" required placeholder="e.g. rahul@premiersports.in" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-primary">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Your Primary Role *</label>
                                    <select name="user_role" id="fbRole" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-primary">
                                        <option value="BUYER">Procurement Buyer</option>
                                        <option value="SUPPLIER">Manufacturer / Supplier</option>
                                        <option value="ACADEMY">Sports Academy / Coach</option>
                                        <option value="DISTRIBUTOR">Wholesale Distributor</option>
                                        <option value="OTHER">Other / Industry Observer</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Topic / Category *</label>
                                    <select name="category" id="fbCategory" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-primary">
                                        <option value="GENERAL">General Platform Experience</option>
                                        <option value="RFQ">RFQ &amp; Sourcing Workflow</option>
                                        <option value="CATALOG">Product Catalog &amp; Filters</option>
                                        <option value="CHAT">Live Chat &amp; Negotiation</option>
                                        <option value="SUPPLIER_ONBOARDING">Supplier Verification</option>
                                        <option value="BUG">Bug or Usability Issue</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Platform Satisfaction</label>
                                    <select name="rating" id="fbRating" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-primary">
                                        <option value="5">★★★★★ — Excellent (5/5)</option>
                                        <option value="4">★★★★☆ — Very Good (4/5)</option>
                                        <option value="3">★★★☆☆ — Average (3/5)</option>
                                        <option value="2">★★☆☆☆ — Needs Work (2/5)</option>
                                        <option value="1">★☆☆☆☆ — Poor (1/5)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Subject (Optional)</label>
                                <input type="text" name="subject" id="fbSubject" placeholder="e.g. Suggestion for cricket bat leather grip filter" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-primary">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Detailed Feedback &amp; Suggestions *</label>
                                <textarea name="message" id="fbMessage" rows="4" required placeholder="Describe your observations, suggestions, or feature ideas in detail..." class="w-full bg-white border border-slate-300 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:border-primary"></textarea>
                            </div>

                            <div id="feedbackAlert" class="hidden p-3 rounded-xl text-xs font-bold"></div>

                            <button type="submit" id="btnSubmitFeedback" class="px-6 py-2.5 rounded-full bg-primary hover:bg-primary-container text-white font-bold text-xs shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                <span>Transmit Feedback</span>
                                <span class="material-symbols-outlined text-[16px]">send</span>
                            </button>
                        </form>

                        <div id="feedbackSuccessCard" class="hidden text-center py-8">
                            <div class="w-14 h-14 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-3xl">check_circle</span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-base mb-1">Thank You For Your Feedback!</h4>
                            <p class="text-xs text-slate-600 max-w-md mx-auto mb-4">Your suggestions have been recorded and forwarded directly to the Sports Junction operations and product team.</p>
                            <button type="button" onclick="resetFeedbackForm()" class="px-4 py-2 rounded-full bg-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-300 transition-colors">
                                Submit Another Suggestion
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                document.getElementById('publicFeedbackForm')?.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btnSubmitFeedback');
                    const alertBox = document.getElementById('feedbackAlert');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';

                    const formData = new FormData(this);
                    try {
                        const res = await fetch('<?= $baseUrl ?>/api/feedback', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });
                        const data = await res.json();
                        if (data && data.success) {
                            document.getElementById('publicFeedbackForm').classList.add('hidden');
                            document.getElementById('feedbackSuccessCard').classList.remove('hidden');
                        } else {
                            alertBox.className = 'p-3 rounded-xl text-xs font-bold bg-rose-50 border border-rose-200 text-rose-800';
                            alertBox.innerText = data.message || 'Failed to submit feedback. Please check required fields.';
                            alertBox.classList.remove('hidden');
                        }
                    } catch (err) {
                        alertBox.className = 'p-3 rounded-xl text-xs font-bold bg-rose-50 border border-rose-200 text-rose-800';
                        alertBox.innerText = 'Network error while transmitting feedback. Please try again.';
                        alertBox.classList.remove('hidden');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = '<span>Transmit Feedback</span><span class="material-symbols-outlined text-[16px]">send</span>';
                    }
                });

                function resetFeedbackForm() {
                    document.getElementById('publicFeedbackForm').reset();
                    document.getElementById('publicFeedbackForm').classList.remove('hidden');
                    document.getElementById('feedbackSuccessCard').classList.add('hidden');
                    document.getElementById('feedbackAlert').classList.add('hidden');
                }
                </script>
            <?php endif; ?>

            <!-- Specialized Interactive Widget: Human-Readable HTML Sitemap -->
            <?php if ($isSitemap && !empty($categoriesWithSubs)): ?>
                <div class="mt-10 pt-8 border-t border-slate-200">
                    <h3 class="font-bold text-slate-900 text-lg mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">category</span>
                        <span>Marketplace Equipment Categories</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-8">
                        <?php foreach ($categoriesWithSubs as $cat): ?>
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                <a href="<?= $baseUrl ?>/category/<?= htmlspecialchars($cat['slug']) ?>" class="font-bold text-sm text-slate-900 hover:text-primary transition-colors block mb-2">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                                <?php if (!empty($cat['subcategories'])): ?>
                                    <ul class="space-y-1 text-xs text-slate-600">
                                        <?php foreach ($cat['subcategories'] as $sub): ?>
                                            <li>
                                                <a href="<?= $baseUrl ?>/category/<?= htmlspecialchars($cat['slug']) ?>?sub=<?= htmlspecialchars($sub['slug']) ?>" class="hover:text-primary transition-colors">
                                                    • <?= htmlspecialchars($sub['name']) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($cmsPagesByColumn)): ?>
                        <h3 class="font-bold text-slate-900 text-lg mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">menu_book</span>
                            <span>Platform &amp; Legal Documentation Directory</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                            <?php foreach ($cmsPagesByColumn as $colKey => $colPages): ?>
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-2.5">
                                        <?= htmlspecialchars(\App\Models\CmsPage::CATEGORIES[$colKey] ?? $colKey) ?>
                                    </h4>
                                    <ul class="space-y-1.5 text-xs">
                                        <?php foreach ($colPages as $cp): ?>
                                            <li>
                                                <a href="<?= $baseUrl ?>/page/<?= htmlspecialchars($cp['slug']) ?>" class="text-slate-700 hover:text-primary font-medium transition-colors block truncate">
                                                    <?= htmlspecialchars($cp['footer_label'] ?: $cp['title']) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Related Pages In Column Navigation -->
            <?php if (!empty($relatedPages) && count($relatedPages) > 1): ?>
                <div class="mt-12 pt-8 border-t border-slate-200">
                    <h4 class="font-bold text-xs text-slate-500 uppercase tracking-wider mb-4">
                        Also in <?= htmlspecialchars($categoryName) ?>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($relatedPages as $rp): ?>
                            <?php if ((int)$rp['id'] === (int)$page['id']) continue; ?>
                            <a href="<?= $baseUrl ?>/page/<?= htmlspecialchars($rp['slug']) ?>" class="p-3.5 bg-slate-50 hover:bg-blue-50/60 rounded-xl border border-slate-200 hover:border-blue-300 transition-all text-xs font-semibold text-slate-800 hover:text-primary flex items-center justify-between">
                                <span class="truncate"><?= htmlspecialchars($rp['footer_label'] ?: $rp['title']) ?></span>
                                <span class="material-symbols-outlined text-[16px] text-slate-400">chevron_right</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </article>
    </div>
</div>
