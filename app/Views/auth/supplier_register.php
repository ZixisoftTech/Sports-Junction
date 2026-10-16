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

<div class="w-full max-w-3xl mx-auto">
    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10">
        <!-- Top Badge -->
        <div class="text-center mb-4">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-teal-50 border border-teal-200/60 text-teal-800 text-xs font-bold uppercase tracking-wider">
                <span class="material-symbols-outlined text-[15px] text-teal-600">precision_manufacturing</span>
                <span>Manufacturer &amp; Factory Onboarding</span>
            </span>
        </div>

        <!-- Heading -->
        <div class="text-center mb-6">
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight">Register Sports Manufacturing Facility</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-lg mx-auto">
                Connect directly with international wholesalers, retail chains, and sports brands with 0% intermediary markup.
            </p>
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

        <!-- Form -->
        <form action="<?= htmlspecialchars($baseUrl) ?>/supplier/register" method="POST" class="space-y-6" id="supplierRegisterForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">

            <!-- Section 1: Facility Profile -->
            <div>
                <h3 class="text-xs font-extrabold uppercase tracking-widest text-blue-900 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">factory</span>
                    <span>1. Manufacturing Entity Profile</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Company / Business Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" required value="<?= htmlspecialchars($old['company_name'] ?? '') ?>" placeholder="e.g. Meerut Sports Gear Industries Pvt Ltd" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Contact Person Name <span class="text-red-500">*</span></label>
                        <input type="text" name="contact_person" required value="<?= htmlspecialchars($old['contact_person'] ?? '') ?>" placeholder="e.g. Rajesh Sharma" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="contact@meerutsports.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Type <span class="text-red-500">*</span></label>
                        <select name="business_type_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                            <option value="">Select Business Type</option>
                            <?php foreach ($businessTypes as $bt): ?>
                                <option value="<?= (int)$bt['id'] ?>" <?= ((int)($old['business_type_id'] ?? 0) === (int)$bt['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bt['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mobile / Phone Number <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <select name="country_code" id="supCountryCode" class="w-32 shrink-0 py-2.5 px-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-blue-700 outline-none">
                                <option value="+91" selected>+91</option>
                                <?php foreach ($countries as $c): if (!empty($c['phone_code'])): ?>
                                    <option value="<?= htmlspecialchars($c['phone_code']) ?>" <?= (($old['country_code'] ?? '+91') === $c['phone_code']) ? 'selected' : '' ?>>
                                        <?= $c['flag'] ?> <?= htmlspecialchars($c['phone_code']) ?>
                                    </option>
                                <?php endif; endforeach; ?>
                            </select>
                            <input type="tel" name="phone" required value="<?= htmlspecialchars($old['phone'] ?? '') ?>" placeholder="98970 12345" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Factory Location -->
            <div>
                <h3 class="text-xs font-extrabold uppercase tracking-widest text-blue-900 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">location_on</span>
                    <span>2. Facility Location &amp; Address</span>
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Plant / Factory Street Address <span class="text-red-500">*</span></label>
                        <input type="text" name="address" required value="<?= htmlspecialchars($old['address'] ?? '') ?>" placeholder="Plot No. 42, Sports Goods Complex, Delhi Road" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Country <span class="text-red-500">*</span></label>
                            <select name="country_id" id="supCountrySelect" required data-country-picker class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" 
                                            data-flag="<?= htmlspecialchars($c['flag'] ?? '🌐') ?>" 
                                            data-phone="<?= htmlspecialchars($c['phone_code'] ?? '') ?>"
                                            <?= ((int)($old['country_id'] ?? 1) === (int)$c['id']) ? 'selected' : '' ?>>
                                        <?= $c['flag'] ?> <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone_code'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">State / Province <span class="text-red-500">*</span></label>
                            <input type="text" name="state" id="supStateInput" required value="<?= htmlspecialchars($old['state'] ?? '') ?>" placeholder="e.g. Uttar Pradesh" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">City <span class="text-red-500">*</span></label>
                            <input type="text" name="city" id="supCityInput" required value="<?= htmlspecialchars($old['city'] ?? '') ?>" placeholder="e.g. Meerut" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Postal / PIN Code <span class="text-red-500">*</span></label>
                            <input type="text" name="postal_code" required value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>" placeholder="250002" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Credentials -->
            <div>
                <h3 class="text-xs font-extrabold uppercase tracking-widest text-blue-900 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">lock</span>
                    <span>3. Account Credentials</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Portal Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password" id="sup-password" required minlength="8" placeholder="At least 8 characters" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                            <button type="button" onclick="togglePassVisibility('sup-password', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="sup-password-confirm" required minlength="8" placeholder="Repeat password" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                            <button type="button" onclick="togglePassVisibility('sup-password-confirm', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms -->
            <div class="pt-1">
                <label class="flex items-start gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-800">
                    <span class="text-xs text-slate-600 leading-relaxed">
                        I confirm that all facility registration documents and manufacturing credentials submitted are authentic, and agree to the <a href="#" class="font-semibold text-blue-900 hover:underline">Seller Agreement</a>.
                    </span>
                </label>
            </div>

            <!-- Submit -->
            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                    <span>Submit Manufacturer Application</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-600">
            Already registered as a supplier? 
            <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="font-bold text-blue-900 hover:underline">Sign In Here</a>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/country-picker.js"></script>
<script>
function togglePassVisibility(inputId, btn) {
    const el = document.getElementById(inputId);
    const icon = btn.querySelector('.material-symbols-outlined');
    if (!el || !icon) return;
    if (el.type === 'password') {
        el.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        el.type = 'password';
        icon.textContent = 'visibility';
    }
}
</script>
