<?php
declare(strict_types=1);

use App\Core\Response;
$baseUrl = Response::baseUrl();
$status = $supplier['approval_status'] ?? 'PENDING_APPROVAL';
$isRejected = ($status === 'REJECTED');
?>

<div class="w-full max-w-xl mx-auto">
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10 text-center">
        <!-- Icon -->
        <?php if ($isRejected): ?>
            <div class="w-20 h-20 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-5 shadow-sm">
                <span class="material-symbols-outlined text-[42px]">cancel</span>
            </div>
            <div class="mb-3">
                <span class="inline-flex items-center gap-1.5 px-4 py-1 rounded-full bg-red-50 border border-red-200 text-red-800 text-xs font-bold uppercase tracking-wider">
                    <span>Application Not Approved</span>
                </span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Application Requires Attention</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-md mx-auto">
                The compliance team reviewed your manufacturing facility application for <strong><?= htmlspecialchars($supplier['company_name'] ?? 'your company') ?></strong> and has requested adjustments.
            </p>
            <?php if (!empty($supplier['rejection_reason'])): ?>
                <div class="mt-6 p-4 rounded-2xl bg-red-50 border border-red-100 text-left">
                    <span class="text-xs font-bold text-red-900 uppercase tracking-wider">Feedback / Reason:</span>
                    <p class="text-xs text-red-800 mt-1"><?= nl2br(htmlspecialchars($supplier['rejection_reason'])) ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="w-20 h-20 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-5 border border-amber-200 shadow-sm animate-pulse">
                <span class="material-symbols-outlined text-[42px]">hourglass_top</span>
            </div>
            <div class="mb-3">
                <span class="inline-flex items-center gap-1.5 px-4 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold uppercase tracking-wider">
                    <span>Application Pending Admin Review</span>
                </span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Verification Under Review</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-md mx-auto">
                Thank you for verifying your business email. Your supplier account for <strong><?= htmlspecialchars($supplier['company_name'] ?? 'your company') ?></strong> is currently being reviewed by the Super Administrator.
            </p>

            <!-- Application Details Summary -->
            <div class="mt-6 rounded-2xl bg-slate-50 border border-slate-200/80 p-5 text-left space-y-2.5 text-xs">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <span class="font-bold text-slate-700">Company Name:</span>
                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($supplier['company_name'] ?? '') ?></span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <span class="font-bold text-slate-700">Contact Person:</span>
                    <span class="text-slate-800"><?= htmlspecialchars($supplier['contact_person'] ?? '') ?></span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <span class="font-bold text-slate-700">Business Type:</span>
                    <span class="text-slate-800"><?= htmlspecialchars($supplier['business_type_name'] ?? 'Manufacturer') ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700">Submitted On:</span>
                    <span class="text-slate-500 font-mono"><?= htmlspecialchars($supplier['created_at'] ?? date('Y-m-d')) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/pending-approval" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-blue-900 text-white font-bold text-xs hover:bg-blue-950 transition-colors flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">refresh</span>
                <span>Refresh Status</span>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/logout" class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors">
                Sign Out
            </a>
        </div>
    </div>
</div>
