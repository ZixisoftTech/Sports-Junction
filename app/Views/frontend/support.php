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
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-extrabold uppercase tracking-widest mb-3">
                <span class="material-symbols-outlined text-sm">headset_mic</span> Help Centre &amp; Support Desk
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">Submit a Support Ticket</h1>
            <p class="text-slate-300 text-base leading-relaxed">
                Need assistance with bulk sourcing, prototype samples, supplier communications, or account management? Our dedicated support team is here to assist you.
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
                    <h3 class="text-lg font-extrabold text-slate-900 mb-4">Support &amp; Resolution Process</h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">confirmation_number</span>
                            <div>
                                <div class="font-bold text-slate-900">Dedicated Ticket ID</div>
                                <div>Every support request receives a unique tracking ticket logged into our Super Admin desk.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">timer</span>
                            <div>
                                <div class="font-bold text-slate-900">Priority Response Times</div>
                                <div>Standard enquiries answered within 24 hours. Critical order/sampling tickets are prioritized.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">handshake</span>
                            <div>
                                <div class="font-bold text-slate-900">Supplier Mediation</div>
                                <div>We assist buyers and suppliers directly in resolving technical specs, sampling milestones, and logistics.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-sm border border-slate-800">
                    <h4 class="font-extrabold text-base mb-2">Looking to Place an RFQ or Order a Sample?</h4>
                    <p class="text-slate-300 text-xs leading-relaxed mb-4">
                        For specific manufacturing requirements, custom OEM orders, or sample requests, you can submit directly from the product catalog or use our RFQ builder.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="inline-block text-center flex-1 py-2.5 px-4 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs uppercase tracking-wider transition">
                            Browse Products
                        </a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="inline-block text-center flex-1 py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs uppercase tracking-wider transition border border-slate-700">
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Support Form -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <h2 class="text-xl font-extrabold text-slate-900 mb-1">Open a Support Request</h2>
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

                    <form id="supportForm" action="<?= htmlspecialchars($baseUrl) ?>/support" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="enquiry_source" value="SUPPORT">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="buyer_name" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="Your Full Name" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Company / Organization</label>
                                <input type="text" name="company_name" value="<?= htmlspecialchars($prefillCompany) ?>" placeholder="Academy, Club, or Business" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="buyer_email" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="name@company.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Phone / WhatsApp Number</label>
                                <input type="text" name="buyer_phone" value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="e.g. +91 9876543210" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Country / Territory <span class="text-red-500">*</span></label>
                                <select name="country_id" id="supportCountrySelect" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                    <option value="">Select Country *</option>
                                    <?php foreach ($countriesList as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= ($prefillCountryId === (int)$c['id'] || (empty($prefillCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                            <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">City / Location</label>
                                <input type="text" name="delivery_location" placeholder="e.g. New Delhi / London" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Support Category <span class="text-red-500">*</span></label>
                                <select name="support_category" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                    <option value="General Assistance">General Assistance</option>
                                    <option value="Sourcing &amp; RFQ Support">Sourcing &amp; RFQ Support</option>
                                    <option value="Prototype Sample Support">Prototype Sample Support</option>
                                    <option value="Supplier Communication">Supplier Communication</option>
                                    <option value="Buyer Account &amp; Access">Buyer Account &amp; Access</option>
                                    <option value="Technical Issue / Bug">Technical Issue / Bug</option>
                                    <option value="Dispute &amp; Mediation">Dispute &amp; Mediation</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Subject / Issue Summary <span class="text-red-500">*</span></label>
                                <input type="text" name="subject" required placeholder="Brief description of your issue" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Detailed Description <span class="text-red-500">*</span></label>
                            <textarea name="specifications" rows="5" required placeholder="Please provide all relevant details, product codes, or enquiry reference numbers..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Attachment (Optional)</label>
                            <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-[11px] text-slate-400 mt-1">Allowed formats: PDF, DOC, DOCX, JPG, PNG, WEBP (Max 10MB)</p>
                        </div>

                        <div class="pt-2">
                            <button type="submit" id="supportSubmitBtn" class="w-full sm:w-auto px-8 py-3.5 bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-widest rounded-xl shadow-md transition">
                                Submit Support Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('supportForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('supportSubmitBtn');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Submitting Ticket...';

    try {
        const formData = new FormData(this);
        const sel = document.getElementById('supportCountrySelect');
        if (sel && sel.selectedIndex >= 0) {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.name) {
                formData.set('country_name', opt.dataset.name);
            }
        }

        const res = await fetch('<?= $baseUrl ?>/api/support', {
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
                    title: 'Support Ticket Created!',
                    text: data.message,
                    confirmButtonColor: '#1e3a8a'
                });
            } else {
                alert(data.message || 'Thank you! Your ticket has been received.');
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
            Swal.fire('Error', 'Network error while submitting your ticket.', 'error');
        } else {
            alert('Network error while submitting your ticket.');
        }
    }
});
</script>
