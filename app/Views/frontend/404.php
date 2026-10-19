<?php
$baseUrl = !empty($baseUrl) ? $baseUrl : \App\Core\Response::baseUrl();
?>

<div class="min-h-[70vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-md w-full text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-blue-100 text-blue-700 mb-6 shadow-sm">
            <span class="material-symbols-outlined text-5xl">find_in_page</span>
        </div>
        <div class="text-xs font-bold uppercase tracking-wider text-blue-700 mb-2">Error 404</div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-3 tracking-tight">Page Not Found</h1>
        <p class="text-slate-600 text-sm mb-8 leading-relaxed">
            The documentation or page you are looking for might have been moved, renamed, or is temporarily unavailable.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-sm shadow-sm transition-colors">
                <span class="material-symbols-outlined text-lg">home</span>
                Back to Homepage
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-sm shadow-xs transition-colors">
                <span class="material-symbols-outlined text-lg">category</span>
                Browse Products
            </a>
        </div>
    </div>
</div>
