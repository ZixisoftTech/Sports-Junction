<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$prefillName = $buyerDetails['name'] ?? '';
$prefillEmail = $buyerDetails['email'] ?? '';
$prefillPhone = !empty($buyerDetails['phone']) ? (($buyerDetails['country_code'] ?? '+91') . ' ' . $buyerDetails['phone']) : '';
$prefillCompany = $buyerDetails['company_name'] ?? '';
$prefillCountryId = (int)($buyerDetails['country_id'] ?? 0);
$countriesList = !empty($countries) ? $countries : \App\Models\Country::allActive();
?>

<section class="py-5 bg-slate-900 text-white border-bottom border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 text-xs font-extrabold uppercase tracking-widest mb-3">
                <span class="material-symbols-outlined text-sm">support_agent</span> Direct B2B Desk
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">Contact Sports Junction</h1>
            <p class="text-slate-300 text-base leading-relaxed">
                Have a sports manufacturing requirement, bulk procurement question, or vendor partnership enquiry? Send a message directly to our centralized B2B Trade Desk.
            </p>
        </div>
    </div>
</section>

<section class="py-12 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Info Cards -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-extrabold text-slate-900 mb-4">B2B Trade & Support Desk</h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-orange-600 mt-0.5">location_on</span>
                            <div>
                                <div class="font-bold text-slate-900">Manufacturing Hub Office</div>
                                <div>Sports Complex Industrial Area, Jalandhar & Meerut Clusters, India</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-orange-600 mt-0.5">mail</span>
                            <div>
                                <div class="font-bold text-slate-900">Official Trade Email</div>
                                <?php $tradeEmail = !empty($siteSettings['contact_email']) ? $siteSettings['contact_email'] : 'sportjunctions@gmail.com'; ?>
                                <a href="mailto:<?= htmlspecialchars($tradeEmail) ?>" class="text-blue-700 font-semibold hover:underline"><?= htmlspecialchars($tradeEmail) ?></a>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-orange-600 mt-0.5">schedule</span>
                            <div>
                                <div class="font-bold text-slate-900">Response SLA</div>
                                <div>Within 2.4 Business Hours (Mon – Sat, 9:00 AM – 8:00 PM IST)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Official Social Channels (Task 11: Exactly Facebook, X, Instagram) -->
                <?php 
                    $siteSettings = $siteSettings ?? \App\Models\SiteSetting::getAllKeyed();
                    $hasFacebook = !empty(trim($siteSettings['social_facebook'] ?? ''));
                    $hasTwitter = !empty(trim($siteSettings['social_twitter'] ?? ''));
                    $hasInstagram = !empty(trim($siteSettings['social_instagram'] ?? ''));
                    $hasAnySocial = $hasFacebook || $hasTwitter || $hasInstagram;
                ?>
                <?php if ($hasAnySocial): ?>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <h3 class="text-base font-extrabold text-slate-900 mb-1">Official Social Channels</h3>
                    <p class="text-xs text-slate-500 mb-4">Connect with Sports Junction across our verified channels:</p>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <?php if ($hasFacebook): ?>
                            <a href="<?= htmlspecialchars(trim($siteSettings['social_facebook'])) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 hover:bg-blue-600 hover:text-white text-slate-700 text-xs font-bold transition-all shadow-xs" aria-label="Facebook">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                <span>Facebook</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($hasTwitter): ?>
                            <a href="<?= htmlspecialchars(trim($siteSettings['social_twitter'])) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 hover:bg-black hover:text-white text-slate-700 text-xs font-bold transition-all shadow-xs" aria-label="X">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                <span>X (Twitter)</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($hasInstagram): ?>
                            <a href="<?= htmlspecialchars(trim($siteSettings['social_instagram'])) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 hover:bg-pink-600 hover:text-white text-slate-700 text-xs font-bold transition-all shadow-xs" aria-label="Instagram">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                <span>Instagram</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-blue-950 text-white rounded-2xl p-6 shadow-sm">
                    <h4 class="font-extrabold text-base mb-2">Looking for a Custom Factory Quote?</h4>
                    <p class="text-slate-300 text-xs leading-relaxed mb-4">
                        All messages submitted through this Contact Us page are logged directly into our Centralized Enquiry Management system with a unique tracking ID.
                    </p>
                    <button type="button" onclick="openInquiryModal(null, 'General Bulk Requirement / RFQ')" class="w-full py-2.5 px-4 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs uppercase tracking-wider transition">
                        Open Bulk RFQ Form
                    </button>
                </div>
            </div>

            <!-- Right Contact Form -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <h2 class="text-xl font-extrabold text-slate-900 mb-1">Send Us a Message</h2>
                    <p class="text-xs text-slate-500 mb-6">Fields marked with <span class="text-red-500">*</span> are required.</p>

                    <form id="contactUsForm" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="buyer_name" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="Your Full Name" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Company / Organization</label>
                                <input type="text" name="company_name" value="<?= htmlspecialchars($prefillCompany) ?>" placeholder="Academy, Club, or Enterprise" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Business Email <span class="text-red-500">*</span></label>
                                <input type="email" name="buyer_email" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="you@company.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Phone / WhatsApp Number</label>
                                <input type="text" name="buyer_phone" value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="+91 98765 43210" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Country / Territory <span class="text-red-500">*</span></label>
                                <select name="country_id" id="contactCountrySelect" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                    <option value="">Select Country *</option>
                                    <?php foreach ($countriesList as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= ($prefillCountryId === (int)$c['id'] || (empty($prefillCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                            <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">City / Delivery Location</label>
                                <input type="text" name="delivery_location" placeholder="e.g., Mumbai, Maharashtra" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Enquiry Subject <span class="text-red-500">*</span></label>
                            <input type="text" name="subject" required placeholder="e.g., Bulk Cricket Kit Procurement / Partnership" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Message / Requirement Details <span class="text-red-500">*</span></label>
                            <textarea name="specifications" rows="5" required placeholder="Describe your enquiry, product requirements, quantities, or support request..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit" id="contactSubmitBtn" class="w-full sm:w-auto px-8 py-3.5 bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-widest rounded-xl shadow-md transition">
                                Submit Contact Enquiry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('contactUsForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('contactSubmitBtn');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Sending Message...';

    try {
        const formData = new FormData(this);
        const sel = document.getElementById('contactCountrySelect');
        if (sel && sel.selectedIndex >= 0) {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.name) {
                formData.set('country_name', opt.dataset.name);
            }
        }

        const res = await fetch('<?= $baseUrl ?>/api/contact', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = origText;

        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Message Received!',
                    text: data.message,
                    confirmButtonColor: '#1e3a8a'
                });
            } else {
                alert(data.message || 'Thank you! Your message has been received.');
            }
            this.reset();
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Submission Error', data.message || 'Please check your inputs.', 'error');
            } else {
                alert(data.message || 'Submission Error. Please check your inputs.');
            }
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Network error while submitting your message.', 'error');
        } else {
            alert('Network error while submitting your message.');
        }
    }
});
</script>
