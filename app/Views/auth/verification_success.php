<?php
declare(strict_types=1);

use App\Core\Response;
$baseUrl = Response::baseUrl();
$sessionId = '#SJ-' . strtoupper(substr(session_id() ?: bin2hex(random_bytes(4)), 0, 8));
$isSupplier = ($role ?? '') === 'SUPPLIER';
?>

<div class="w-full max-w-xl mx-auto">
    <!-- Top Utility Bar -->
    <div class="flex items-center justify-between mb-4 px-2">
        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-teal-800">
            <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
            <span>Authentication Complete</span>
        </span>
        <span class="font-mono text-xs text-slate-400 font-semibold uppercase">
            Session ID: <?= htmlspecialchars($sessionId) ?>
        </span>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10 text-center">
        <!-- Top Verified Icon -->
        <div class="w-20 h-20 rounded-2xl bg-teal-700 flex items-center justify-center mx-auto mb-5 relative text-white shadow-lg shadow-teal-700/20">
            <span class="material-symbols-outlined text-[42px]">check</span>
            <span class="material-symbols-outlined text-[16px] text-teal-700 bg-white rounded-full p-0.5 absolute -bottom-1 -right-1 shadow-xs">verified</span>
        </div>

        <?php if ($isSupplier): ?>
            <!-- Supplier specific state -->
            <div class="mb-3">
                <span class="inline-flex items-center gap-1.5 px-4 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[14px] text-amber-600">hourglass_top</span>
                    <span>Supplier Application Awaiting Admin Approval</span>
                </span>
            </div>

            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight">Email Verified Successfully</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-md mx-auto">
                Your manufacturer profile is under review by the Super Administrator. Once approved, you will receive full access to publish sports equipment products.
            </p>

            <div class="mt-8 space-y-3">
                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/pending-approval" class="w-full py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                    <span>View Application Status</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>
            </div>
        <?php else: ?>
            <!-- Buyer specific state (Exact Stitch Screen 3) -->
            <div class="mb-3">
                <span class="inline-flex items-center gap-1.5 px-4 py-1 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-xs font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[14px] text-teal-600">verified</span>
                    <span>Tier 1 Buyer Status Activated</span>
                </span>
            </div>

            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight">Email Verified Successfully</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-md mx-auto">
                Your buyer account is ready. You now have verified access to factory-direct pricing, custom OEM quotes, and escrow-protected inquiries.
            </p>

            <!-- Unlocked Privileges Box -->
            <div class="mt-6 rounded-2xl bg-blue-50/60 border border-blue-100 p-5 text-left space-y-3">
                <div class="flex items-center justify-between border-b border-blue-100 pb-2.5">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-950">UNLOCKED BUYER PRIVILEGES</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-200/60 text-blue-900">Institutional Clearance</span>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2 text-slate-700 font-medium">
                        <span class="material-symbols-outlined text-[18px] text-teal-600">task_alt</span>
                        <span>Direct factory inquiry dispatch</span>
                    </span>
                    <span class="text-[11px] font-semibold text-slate-500">Priority SLA</span>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2 text-slate-700 font-medium">
                        <span class="material-symbols-outlined text-[18px] text-teal-600">task_alt</span>
                        <span>Tiered volume pricing calculator</span>
                    </span>
                    <span class="text-[11px] font-semibold text-slate-500">Live MOQs</span>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2 text-slate-700 font-medium">
                        <span class="material-symbols-outlined text-[18px] text-teal-600">task_alt</span>
                        <span>Instant supplier chat &amp; RFQ tracking</span>
                    </span>
                    <span class="text-[11px] font-semibold text-slate-500">Escrow Ready</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 space-y-3">
                <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="w-full py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                    <span>Continue to Marketplace</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/" class="block text-center text-xs font-bold text-slate-600 hover:text-blue-900 py-1 transition-colors">
                    View My Dashboard
                </a>
            </div>

            <!-- Auto-navigation progress indicator -->
            <div class="mt-6 p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-left">
                <div class="flex items-center justify-between text-[11px] text-slate-600 font-medium mb-1.5">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px] text-blue-900 animate-spin">sync</span>
                        <span>Redirecting to marketplace catalog in <strong id="countdownSec">3</strong>s...</span>
                    </span>
                    <span class="font-mono text-[10px] font-bold text-slate-400">AUTO-NAV</span>
                </div>
                <div class="w-full bg-slate-200 h-1 rounded-full overflow-hidden">
                    <div id="navProgressBar" class="bg-blue-900 h-full w-0 transition-all duration-1000 ease-linear"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Protocol Footnote -->
    <div class="mt-6 flex flex-wrap items-center justify-center gap-4 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-teal-600">verified</span> ISO 9001 Audited Suppliers</span>
        <span>•</span>
        <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-blue-700">lock</span> 256-Bit Escrow Vault</span>
        <span>•</span>
        <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-indigo-700">desk</span> Procurement Desk Assigned</span>
    </div>
</div>

<script>
<?php if (!$isSupplier): ?>
let seconds = 3;
const secSpan = document.getElementById('countdownSec');
const bar = document.getElementById('navProgressBar');
bar.style.width = '33%';

const interval = setInterval(() => {
    seconds--;
    if (seconds >= 0) {
        secSpan.textContent = seconds;
        bar.style.width = (100 - seconds * 33) + '%';
    }
    if (seconds <= 0) {
        clearInterval(interval);
        window.location.href = '<?= htmlspecialchars($baseUrl) ?>/products';
    }
}, 1000);
<?php endif; ?>
</script>
