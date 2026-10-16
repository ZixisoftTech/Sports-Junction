<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$success = Session::flash('success');
$error = Session::flash('error');
$info = Session::flash('info');
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="inline-flex items-center gap-1 text-xs font-bold text-blue-900 hover:underline mb-2">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Buyer Dashboard</span>
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">My Account &amp; Profile</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage your personal information, company details, and account security.</p>
            </div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/logout" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition-colors">
                <span class="material-symbols-outlined text-[16px]">logout</span>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2.5">
            <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm flex items-center gap-2.5">
            <span class="material-symbols-outlined text-red-600 text-[20px]">error</span>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <!-- Personal & Company Information -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-8">
        <h2 class="font-headline font-bold text-base text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-900 text-[20px]">badge</span>
            <span>Personal &amp; Company Information</span>
        </h2>

        <form action="<?= htmlspecialchars($baseUrl) ?>/buyer/profile" method="POST" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($buyer['name']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Company / Organization Name *</label>
                    <input type="text" name="company_name" required value="<?= htmlspecialchars($buyer['company_name'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Work Email Address *</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($buyer['email']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    <small class="text-[11px] text-slate-400">Changing your email will require OTP reverification.</small>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mobile / Phone Number *</label>
                    <div class="flex gap-2">
                        <input type="text" name="country_code" value="<?= htmlspecialchars($buyer['country_code'] ?? '+91') ?>" class="w-20 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 outline-none">
                        <input type="text" name="phone" required value="<?= htmlspecialchars($buyer['phone'] ?? '') ?>" class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Country / Region *</label>
                    <select name="country_id" required data-country-picker class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                    data-flag="<?= htmlspecialchars($c['flag'] ?? '🌐') ?>" 
                                    data-phone="<?= htmlspecialchars($c['phone_code'] ?? '') ?>"
                                    <?= ((int)($buyer['country_id'] ?? 1) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= $c['flag'] ?> <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone_code'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">State / Province</label>
                    <input type="text" name="state" value="<?= htmlspecialchars($buyer['state'] ?? '') ?>" placeholder="e.g. California / Maharashtra" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($buyer['city'] ?? '') ?>" placeholder="e.g. Los Angeles / Mumbai" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div id="security" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <h2 class="font-headline font-bold text-base text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-900 text-[20px]">lock</span>
            <span>Change Password</span>
        </h2>

        <form action="<?= htmlspecialchars($baseUrl) ?>/buyer/change-password" method="POST" class="space-y-4 max-w-md">
            <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Current Password *</label>
                <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Password *</label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
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
