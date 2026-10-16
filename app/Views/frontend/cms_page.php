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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-on-surface-variant mb-6 font-medium">
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="hover:text-primary transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">home</span> Home
            </a>
            <span class="text-outline-variant">/</span>
            <span class="text-on-surface font-semibold"><?= htmlspecialchars($categoryName) ?></span>
            <span class="text-outline-variant">/</span>
            <span class="text-primary font-bold truncate max-w-xs"><?= htmlspecialchars($page['title']) ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Left Sidebar: Related Pages in this Category -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl border border-surface-container p-5 shadow-xs">
                    <h3 class="font-headline font-bold text-sm text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-surface-container flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">menu_book</span>
                        <span><?= htmlspecialchars($categoryName) ?></span>
                    </h3>
                    <ul class="space-y-1.5 text-xs">
                        <?php foreach ($relatedPages as $rp): ?>
                            <?php 
                                $isCurrent = ($rp['slug'] === $page['slug']);
                                $linkUrl = !empty($rp['external_url']) ? htmlspecialchars($baseUrl . $rp['external_url']) : htmlspecialchars($baseUrl . '/page/' . $rp['slug']);
                            ?>
                            <li>
                                <a href="<?= $linkUrl ?>" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all <?= $isCurrent ? 'bg-primary text-white font-bold shadow-2xs' : 'text-slate-600 hover:bg-slate-50 hover:text-primary font-medium' ?>">
                                    <span class="truncate"><?= htmlspecialchars($rp['title']) ?></span>
                                    <span class="material-symbols-outlined text-[14px] <?= $isCurrent ? 'text-white' : 'text-slate-400' ?>">chevron_right</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Support & Help Widget -->
                <div class="bg-gradient-to-br from-blue-900 to-indigo-950 rounded-2xl p-5 text-white shadow-md">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-teal-400 text-[22px]">support_agent</span>
                        <h4 class="font-headline font-bold text-xs uppercase tracking-wider">Need Assistance?</h4>
                    </div>
                    <p class="text-xs text-blue-100 mb-4 leading-relaxed">
                        Our verified factory relationship managers are available to assist with your B2B sourcing requirements.
                    </p>
                    <div class="space-y-2 text-xs text-blue-200">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[15px] text-teal-300">mail</span>
                            <span class="font-semibold text-white"><?= htmlspecialchars($email) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[15px] text-teal-300">schedule</span>
                            <span><?= htmlspecialchars($hours) ?></span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-blue-800/80">
                        <button type="button" onclick="openProductChat({title: 'General Support & Sourcing Help', supplier_id: 1, supplier_name: 'Sports Junction Official Desk'})" class="w-full py-2 bg-teal-500 hover:bg-teal-600 text-slate-950 font-bold rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5 shadow-xs">
                            <span class="material-symbols-outlined text-[16px]">chat</span>
                            <span>Launch Live Chat</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-3">
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

    </div>
</div>
