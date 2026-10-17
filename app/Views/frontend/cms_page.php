<?php
/**
 * CMS Page View
 * Sports Junction B2B Marketplace
 */
$baseUrl = $baseUrl ?? '';
$siteName = $siteSettings['site_name'] ?? 'Sports Junction';
$phone = $siteSettings['contact_phone'] ?? '+91 98765 43210';
$email = $siteSettings['contact_email'] ?? 'support@sportsjunction.in';
$hours = $siteSettings['working_hours'] ?? 'Mon - Sat: 9:00 AM - 7:00 PM IST';
?>

<div class="bg-surface-container-lowest min-h-screen py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
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
                            <span>Official Marketplace Documentation</span>
                        </div>
                        <h1 class="font-headline font-black text-2xl sm:text-3xl text-slate-900 leading-tight">
                            <?= htmlspecialchars($page['title']) ?>
                        </h1>
                        <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
                            <span>Published on <?= date('d M Y', strtotime($page['created_at'])) ?></span>
                            <span>•</span>
                            <span>Updated <?= date('d M Y', strtotime($page['updated_at'])) ?></span>
                        </div>
                    </header>

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

                    <?php if (!empty($page['external_url'])): ?>
                        <div class="mt-8 pt-6 border-t border-surface-container flex items-center justify-start gap-4">
                            <a href="<?= htmlspecialchars($baseUrl . $page['external_url']) ?>" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-white font-bold text-xs shadow-sm transition-all flex items-center gap-2">
                                <span>Proceed to <?= htmlspecialchars($page['title']) ?></span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    <?php endif; ?>

                </article>
    </div>
</div>
