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

<div class="w-full max-w-2xl mx-auto">
    <!-- Main Registration Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10">
        <!-- Top Badge -->
        <div class="text-center mb-4">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-teal-50 border border-teal-200/60 text-teal-800 text-xs font-bold uppercase tracking-wider">
                <span class="material-symbols-outlined text-[15px] text-teal-600">shield_lock</span>
                <span>Direct Factory Sourcing</span>
            </span>
        </div>

        <!-- Heading -->
        <div class="text-center mb-6">
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight">Create Your Buyer Account</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-md mx-auto">Join the enterprise network and connect directly with verified tier-1 Indian sports manufacturers.</p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm flex items-start gap-3">
                <span class="material-symbols-outlined text-red-600 text-[20px] shrink-0 mt-0.5">error</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-start gap-3">
                <span class="material-symbols-outlined text-emerald-600 text-[20px] shrink-0 mt-0.5">check_circle</span>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="<?= htmlspecialchars($baseUrl) ?>/register" method="POST" class="space-y-4 sm:space-y-5" id="buyerRegisterForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">

            <!-- Row 1: Full Name & Company Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="name" required value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="Sarah Jenkins" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-[18px]">person</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Company Name <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="company_name" required value="<?= htmlspecialchars($old['company_name'] ?? '') ?>" placeholder="Apex Fitness Equipment LLC" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-[18px]">domain</span>
                    </div>
                </div>
            </div>

            <!-- Row 2: Business Email & Mobile Number -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Email <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="sarah@apexfit.com" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-[18px]">mail</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mobile Number <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <select name="country_code" class="w-28 shrink-0 py-2.5 px-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-blue-700 outline-none">
                            <option value="+91" <?= ($old['country_code'] ?? '+91') === '+91' ? 'selected' : '' ?>>+91 (IN)</option>
                            <option value="+1" <?= ($old['country_code'] ?? '') === '+1' ? 'selected' : '' ?>>+1 (US)</option>
                            <option value="+44" <?= ($old['country_code'] ?? '') === '+44' ? 'selected' : '' ?>>+44 (UK)</option>
                            <option value="+971" <?= ($old['country_code'] ?? '') === '+971' ? 'selected' : '' ?>>+971 (AE)</option>
                            <option value="+61" <?= ($old['country_code'] ?? '') === '+61' ? 'selected' : '' ?>>+61 (AU)</option>
                            <option value="+49" <?= ($old['country_code'] ?? '') === '+49' ? 'selected' : '' ?>>+49 (DE)</option>
                        </select>
                        <div class="relative flex-1">
                            <input type="tel" name="phone" required value="<?= htmlspecialchars($old['phone'] ?? '') ?>" placeholder="98765 43210" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                            <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-[18px]">call</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Procurement Country / HQ Region -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Procurement Country / HQ Region <span class="text-red-500">*</span></label>
                <div class="relative">
                    <select name="country_id" required class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all appearance-none cursor-pointer">
                        <option value="">Select registered country location</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($old['country_id'] ?? 1) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['iso_alpha2']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-[18px] pointer-events-none">public</span>
                </div>
            </div>

            <!-- Row 4: Password & Confirm Password -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="reg-password" required minlength="8" placeholder="At least 8 characters" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <button type="button" onclick="togglePassVisibility('reg-password', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="reg-password-conf" required minlength="8" placeholder="Repeat password" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <button type="button" onclick="togglePassVisibility('reg-password-conf', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Terms & Conditions Checkbox -->
            <div class="pt-1">
                <label class="flex items-start gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-800">
                    <span class="text-xs text-slate-600 leading-relaxed">
                        I agree to the <a href="#" class="font-semibold text-blue-900 hover:underline">Terms &amp; Conditions</a> and <a href="#" class="font-semibold text-blue-900 hover:underline">Privacy Policy</a> governing enterprise sourcing transactions.
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                    <span>Create Account</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </div>
        </form>

        <!-- Card Footer -->
        <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div class="text-slate-600">
                Already have an account? 
                <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="font-bold text-blue-900 hover:text-blue-700">Login</a>
            </div>
            <div class="text-slate-500 font-medium">
                Are you a sports manufacturer? 
                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/register" class="font-bold text-teal-700 hover:text-teal-900">Sell on Sports Junction</a>
            </div>
        </div>
    </div>

    <!-- Bottom Feature Badges -->
    <div class="mt-8 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-blue-800">description</span> Factory Audits on Demand</span>
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-teal-600">currency_exchange</span> Direct Currency Settlements</span>
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-indigo-700">verified_user</span> Trade Assurance Guarantee</span>
    </div>
</div>

<script>
function togglePassVisibility(inputId, btn) {
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
