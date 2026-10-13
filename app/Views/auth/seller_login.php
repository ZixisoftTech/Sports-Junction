<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$error = Session::flash('error');
$success = Session::flash('success');
$old = Session::flash('old_input') ?? [];
?>

<div class="w-full max-w-md mx-auto">
    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10">
        <!-- Top Badges -->
        <div class="flex items-center justify-between mb-5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/60 text-emerald-800 text-[11px] font-bold uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                <span>Manufacturer &amp; Factory Portal</span>
            </span>
            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500">
                <span class="material-symbols-outlined text-[14px] text-teal-600">factory</span>
                <span>Seller Desk</span>
            </span>
        </div>

        <!-- Heading -->
        <div class="mb-6">
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight">Seller Sign In</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1.5">Access your factory catalog, inquiries, and quotation desk.</p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm flex items-start gap-2.5">
                <span class="material-symbols-outlined text-red-600 text-[18px] shrink-0 mt-0.5">error</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-start gap-2.5">
                <span class="material-symbols-outlined text-emerald-600 text-[18px] shrink-0 mt-0.5">check_circle</span>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- Seller Login Form -->
        <form action="<?= htmlspecialchars($baseUrl) ?>/seller/login" method="POST" class="space-y-4" id="sellerLoginForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">

            <!-- Business Email Field -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Email</label>
                <div class="relative">
                    <input type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="manufacturer@domain.com" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-[18px]">business</span>
                </div>
            </div>

            <!-- Password Field -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/forgot-password" class="text-xs font-semibold text-blue-900 hover:underline">Forgot Password?</a>
                </div>
                <div class="relative">
                    <input type="password" name="password" id="seller-password" required placeholder="Enter seller account password" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <button type="button" onclick="togglePass('seller-password', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-800">
                    <span class="text-xs text-slate-600 font-medium">Remember this device for 30 days</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                    <span>Login as Seller</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </div>
        </form>

        <!-- Card Footer -->
        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-600">
            Looking to sell sports equipment? 
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/register" class="font-bold text-blue-900 hover:underline">Create Seller Account</a>
            <div class="mt-2 text-slate-400">
                Buyer account? <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="text-blue-900 hover:underline font-semibold">Sign in as Buyer</a>
            </div>
        </div>
    </div>

    <!-- Security Footnote -->
    <div class="mt-6 text-center text-[11px] text-slate-500 flex items-center justify-center gap-1.5">
        <span class="material-symbols-outlined text-[14px] text-teal-600">verified_user</span>
        <span>Sports Junction Verified Manufacturer Network</span>
    </div>
</div>

<script>
function togglePass(inputId, btn) {
    const el = document.getElementById(inputId);
    const icon = btn.querySelector('.material-symbols-outlined');
    if (el.type === 'password') {
        el.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        el.type = 'password';
        icon.textContent = 'visibility';
    }
}
</script>
