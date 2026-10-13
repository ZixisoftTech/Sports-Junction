<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Factory Profile &amp; Facility Settings</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Update your manufacturing business details, location, and security credentials.</p>
    </div>

    <!-- Facility Profile Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-8">
        <h2 class="font-headline font-bold text-base text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-900 text-[20px]">domain</span>
            <span>Business &amp; Facility Information</span>
        </h2>

        <form action="<?= htmlspecialchars($baseUrl) ?>/supplier/profile" method="POST" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Company / Factory Name *</label>
                    <input type="text" name="company_name" required value="<?= htmlspecialchars($supplier['company_name']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Contact Person *</label>
                    <input type="text" name="contact_person" required value="<?= htmlspecialchars($supplier['contact_person']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Email *</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($supplier['email']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <small class="text-muted text-[11px] text-slate-400">Note: Changing email address requires OTP reverification.</small>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number *</label>
                    <div class="flex gap-2">
                        <input type="text" name="country_code" value="<?= htmlspecialchars($supplier['country_code'] ?? '+91') ?>" class="w-20 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 outline-none">
                        <input type="text" name="phone" required value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>" class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Type *</label>
                    <select name="business_type_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <?php foreach ($businessTypes as $bt): ?>
                            <option value="<?= $bt['id'] ?>" <?= ((int)$supplier['business_type_id'] === (int)$bt['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bt['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Postal Code *</label>
                    <input type="text" name="postal_code" value="<?= htmlspecialchars($supplier['postal_code'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Factory Street Address *</label>
                    <input type="text" name="address" required value="<?= htmlspecialchars($supplier['address'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Country *</label>
                    <select name="country_id" id="supplierCountry" required onchange="loadStates(this.value)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ((int)$supplier['country_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">State / Province *</label>
                    <select name="state_id" id="supplierState" required onchange="loadCities(this.value)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <option value="<?= (int)$supplier['state_id'] ?>" selected><?= htmlspecialchars($supplier['state_name'] ?? 'Current State') ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">City *</label>
                    <select name="city_id" id="supplierCity" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <option value="<?= (int)$supplier['city_id'] ?>" selected><?= htmlspecialchars($supplier['city_name'] ?? 'Current City') ?></option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Security & Password Change Card -->
    <div id="security" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <h2 class="font-headline font-bold text-base text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-900 text-[20px]">lock_reset</span>
            <span>Security &amp; Password</span>
        </h2>

        <form action="<?= htmlspecialchars($baseUrl) ?>/supplier/change-password" method="POST" class="space-y-4 max-w-md">
            <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Current Password *</label>
                <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Password *</label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                <small class="text-muted text-[11px] text-slate-400">Minimum 8 characters.</small>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm New Password *</label>
                <input type="password" name="password_confirmation" required minlength="8" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>

            <div class="pt-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-md transition-all">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loadStates(countryId) {
    if (!countryId) return;
    fetch('<?= htmlspecialchars($baseUrl) ?>/api/public/states-by-country/' + countryId)
        .then(r => r.json())
        .then(res => {
            let opts = '<option value="">Select State...</option>';
            if (res.success && res.data) {
                res.data.forEach(s => {
                    opts += `<option value="${s.id}">${s.name}</option>`;
                });
            }
            $('#supplierState').html(opts);
            $('#supplierCity').html('<option value="">Select City...</option>');
        });
}

function loadCities(stateId) {
    if (!stateId) return;
    fetch('<?= htmlspecialchars($baseUrl) ?>/api/public/cities-by-state/' + stateId)
        .then(r => r.json())
        .then(res => {
            let opts = '<option value="">Select City...</option>';
            if (res.success && res.data) {
                res.data.forEach(c => {
                    opts += `<option value="${c.id}">${c.name}</option>`;
                });
            }
            $('#supplierCity').html(opts);
        });
}
</script>
