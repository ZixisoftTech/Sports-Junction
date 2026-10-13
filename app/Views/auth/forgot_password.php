<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$error = Session::flash('error');
$success = Session::flash('success');
?>

<div class="w-full max-w-md mx-auto">
    <!-- Back link -->
    <div class="mb-4 px-2">
        <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-blue-900 transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Login</span>
        </a>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center mx-auto mb-4 text-blue-900 shadow-sm">
            <span class="material-symbols-outlined text-[28px]">lock_reset</span>
        </div>

        <div class="text-center mb-6">
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Forgot Password</h1>
            <p class="text-xs text-slate-500 mt-1.5">Enter your verified email address to receive a 6-digit password recovery passcode.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs flex items-start gap-2.5">
                <span class="material-symbols-outlined text-red-600 text-[18px] shrink-0 mt-0.5">error</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-2.5">
                <span class="material-symbols-outlined text-emerald-600 text-[18px] shrink-0 mt-0.5">check_circle</span>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($baseUrl) ?>/forgot-password" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Email Address</label>
                <div class="relative">
                    <input type="email" name="email" required placeholder="name@company.com" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-[18px]">mail</span>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                    <span>Send Recovery Passcode</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-600">
            Remember your credentials? 
            <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="font-bold text-blue-900 hover:underline">Sign In</a>
        </div>
    </div>
</div>
