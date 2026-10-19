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
                    <h3 class="text-lg font-extrabold text-slate-900 mb-4">How Sports Junction Works</h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">schedule</span>
                            <div>
                                <div class="font-bold text-slate-900">Rapid Response Time</div>
                                <div>Our team responds within 24–48 business hours.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">factory</span>
                            <div>
                                <div class="font-bold text-slate-900">Direct Factory Connections</div>
                                <div>For urgent sourcing inquiries, register as a buyer to connect directly with manufacturers.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">verified_user</span>
                            <div>
                                <div class="font-bold text-slate-900">Verified Quality Standards</div>
                                <div>All listed manufacturing units comply with verified industrial and export specifications.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-950 text-white rounded-2xl p-6 shadow-sm">
                    <h4 class="font-extrabold text-base mb-2">Looking for a Custom Factory Quote?</h4>
                    <p class="text-slate-300 text-xs leading-relaxed mb-4">
                        All inquiries submitted through this form are logged directly into our Centralized Enquiry Management system with a unique tracking ID.
                    </p>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/register" class="inline-block text-center w-full py-2.5 px-4 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs uppercase tracking-wider transition">
                        Register as Buyer
                    </a>
                </div>
            </div>

            <!-- Right Contact Form -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <h2 class="text-xl font-extrabold text-slate-900 mb-1">Send Us a Message</h2>
                    <p class="text-xs text-slate-500 mb-6">Fields marked with <span class="text-red-500">*</span> are required.</p>

                    <?php if (\App\Helpers\Session::hasFlash('success')): ?>
                        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                            <span><?= htmlspecialchars(\App\Helpers\Session::getFlash('success')) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (\App\Helpers\Session::hasFlash('error')): ?>
                        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold flex items-center gap-2">
                            <span class="material-symbols-outlined text-rose-600">error</span>
                            <span><?= htmlspecialchars(\App\Helpers\Session::getFlash('error')) ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="contactUsForm" action="<?= htmlspecialchars($baseUrl) ?>/contact" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
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
                                <input type="email" name="buyer_email" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="Enter your business email" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Phone / WhatsApp Number</label>
                                <input type="text" name="buyer_phone" value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="Enter contact number with country code" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
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
