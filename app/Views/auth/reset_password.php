<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$email = $email ?? Session::get('reset_email') ?? '';
$error = Session::flash('error');
$success = Session::flash('success');
?>

<div class="w-full max-w-md mx-auto">
    <!-- Back link -->
    <div class="mb-4 px-2">
        <a href="<?= htmlspecialchars($baseUrl) ?>/forgot-password" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-blue-900 transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Request New Code</span>
        </a>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center mx-auto mb-4 text-blue-900 shadow-sm">
            <span class="material-symbols-outlined text-[28px]">password</span>
        </div>

        <div class="text-center mb-6">
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Set New Password</h1>
            <p class="text-xs text-slate-500 mt-1.5">Enter the 6-digit recovery passcode sent to <strong class="text-slate-800"><?= htmlspecialchars($email ?: 'your email') ?></strong> and your new password.</p>
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

        <?php if (\App\Models\Otp::isStaticOtpEnabled()): ?>
            <div class="mb-5 p-3 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-950 text-xs flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600 text-[20px] shrink-0">key</span>
                    <div>
                        <span class="font-bold text-amber-900">Static Recovery Passcode:</span> 
                        Passcode is <strong class="font-mono text-sm text-blue-900 bg-amber-100/90 px-2 py-0.5 rounded">123456</strong>
                        <span class="text-slate-500 block text-[11px] mt-0.5">Static passcode active until live SMTP delivery is configured</span>
                    </div>
                </div>
                <button type="button" onclick="document.querySelector('input[name=otp]').value='123456'" class="shrink-0 text-xs bg-blue-900 hover:bg-blue-950 text-white font-bold px-3 py-1.5 rounded-lg transition-colors shadow-xs">Auto-Fill</button>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($baseUrl) ?>/reset-password" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

            <!-- 6-Digit OTP Field -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">6-Digit Recovery Passcode <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text" name="otp" required maxlength="6" inputmode="numeric" placeholder="123456" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center font-mono text-lg font-bold tracking-widest text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <span class="material-symbols-outlined absolute right-3 top-3 text-slate-400 text-[18px]">pin</span>
                </div>
            </div>

            <!-- New Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="password" id="reset-pwd" required minlength="8" placeholder="At least 8 characters" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <button type="button" onclick="togglePass('reset-pwd', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm New Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="reset-pwd-conf" required minlength="8" placeholder="Repeat new password" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <button type="button" onclick="togglePass('reset-pwd-conf', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                    <span>Save New Password &amp; Login</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </div>
        </form>
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
