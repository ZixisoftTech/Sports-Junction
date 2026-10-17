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

    <?php if (!empty($latestChange) && $latestChange['status'] === 'PENDING'): ?>
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <span class="material-symbols-outlined text-amber-600 text-[20px]">hourglass_top</span>
                <span>Factory Profile Revision Pending Administrator Approval</span>
            </div>
            <p class="text-amber-700 mb-2">You submitted updated factory details on <?= htmlspecialchars($latestChange['created_at']) ?>. These changes are awaiting Super Admin verification. Your active factory profile below remains live across the marketplace until approved.</p>
            <div class="p-3 bg-white/80 rounded-xl border border-amber-200 text-slate-700 text-xs grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div><strong>Proposed Company:</strong> <?= htmlspecialchars($latestChange['decoded_requested']['company_name'] ?? '—') ?></div>
                <div><strong>Proposed Contact:</strong> <?= htmlspecialchars($latestChange['decoded_requested']['contact_person'] ?? '—') ?></div>
                <div><strong>Proposed Phone:</strong> <?= htmlspecialchars(($latestChange['decoded_requested']['country_code'] ?? '') . ' ' . ($latestChange['decoded_requested']['phone'] ?? '—')) ?></div>
                <div><strong>Proposed Address:</strong> <?= htmlspecialchars($latestChange['decoded_requested']['address'] ?? '—') ?></div>
                <div><strong>Proposed Location:</strong> <?= htmlspecialchars(implode(', ', array_filter([$latestChange['decoded_requested']['city'] ?? '', $latestChange['decoded_requested']['state'] ?? '']))) ?: '—' ?></div>
                <div><strong>Proposed Postal Code:</strong> <?= htmlspecialchars($latestChange['decoded_requested']['postal_code'] ?? '—') ?></div>
            </div>
        </div>
    <?php elseif (!empty($latestChange) && $latestChange['status'] === 'REJECTED'): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <span class="material-symbols-outlined text-rose-600 text-[20px]">cancel</span>
                <span>Previous Profile Revision Was Rejected</span>
            </div>
            <p class="text-rose-700 mb-1"><strong>Feedback:</strong> <?= htmlspecialchars($latestChange['rejection_reason'] ?? 'Submitted details did not meet manufacturing verification standards.') ?></p>
            <small class="text-rose-500">You may update your factory details below and resubmit for approval.</small>
        </div>
    <?php elseif (!empty($latestChange) && $latestChange['status'] === 'APPROVED'): ?>
        <div class="mb-6 p-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-[18px]">verified</span>
                <span>Profile Status: <strong>Approved &amp; Live</strong></span>
            </div>
            <span class="text-slate-400 text-[11px]">Last verified: <?= htmlspecialchars(substr($latestChange['reviewed_at'] ?? $latestChange['updated_at'] ?? '', 0, 10)) ?></span>
        </div>
    <?php endif; ?>

    <!-- Facility Profile Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-8">
        <h2 class="font-headline font-bold text-base text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-900 text-[20px]">domain</span>
            <span>Business &amp; Facility Information</span>
        </h2>

        <form action="<?= htmlspecialchars($baseUrl) ?>/supplier/profile" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Supplier Logo Upload -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Supplier / Factory Logo</label>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl border border-slate-200 bg-slate-100 flex items-center justify-center overflow-hidden shrink-0">
                            <?php if (!empty($supplier['logo'])): ?>
                                <img src="<?= htmlspecialchars($baseUrl . $supplier['logo']) ?>" alt="Logo" class="w-full h-full object-contain">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-slate-400 text-3xl">domain</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 cursor-pointer">
                            <p class="text-[11px] text-slate-400 mt-1">Recommended: Square format (PNG/JPG/WebP), max 2MB.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Company / Factory Name *</label>
                    <input type="text" name="company_name" required value="<?= htmlspecialchars($supplier['company_name']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Contact Person *</label>
                    <input type="text" name="contact_person" required value="<?= htmlspecialchars($supplier['contact_person']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Email (Account ID)</label>
                    <input type="email" name="email" readonly value="<?= htmlspecialchars($supplier['email']) ?>" class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm text-slate-500 cursor-not-allowed outline-none">
                    <small class="text-muted text-[11px] text-slate-400">Primary seller account email. Contact administrative support to update registered email.</small>
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

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Factory / Business Overview</label>
                    <textarea name="description" rows="3" placeholder="Brief description of manufacturing infrastructure, production capacity, OEM/ODM services..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all"><?= htmlspecialchars($supplier['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Country *</label>
                    <select name="country_id" id="supplierCountry" required data-country-picker class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                    data-flag="<?= htmlspecialchars($c['flag'] ?? '🌐') ?>" 
                                    data-country-name="<?= htmlspecialchars($c['name']) ?>"
                                    data-phone="<?= htmlspecialchars($c['phone_code'] ?? '') ?>"
                                    <?= ((int)$supplier['country_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= $c['flag'] ?> <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone_code'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">State / Province *</label>
                    <input type="text" name="state" required value="<?= htmlspecialchars($supplier['state'] ?? $supplier['state_name'] ?? '') ?>" placeholder="e.g. Uttar Pradesh" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">City *</label>
                    <input type="text" name="city" required value="<?= htmlspecialchars($supplier['city'] ?? $supplier['city_name'] ?? '') ?>" placeholder="e.g. Meerut" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
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

<script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/country-picker.js"></script>
