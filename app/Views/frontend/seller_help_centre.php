<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$prefillName = $supplierDetails['contact_person'] ?? ($supplierDetails['name'] ?? '');
$prefillEmail = $supplierDetails['email'] ?? '';
$prefillPhone = $supplierDetails['phone'] ?? '';
$prefillCompany = $supplierDetails['company_name'] ?? '';
$prefillCountryId = (int)($supplierDetails['country_id'] ?? 0);
$countriesList = !empty($countries) ? $countries : \App\Models\Country::allActive();

$sellerCategories = [
    'Supplier Verification & Statutory Documents' => 'Supplier Verification & Statutory Documents (GSTIN, IEC, PAN)',
    'Catalog & Product Approvals'                 => 'Catalog & Product Approvals (Audit Status & Specs)',
    'RFQ & Custom OEM Inquiries'                  => 'RFQ & Buyer Direct Inquiries (Wholesale Pricing & Quotes)',
    'Prototype Sample Order Support'              => 'Prototype Sample Orders & Buyer Dispatch',
    'Account & Registration'                      => 'Seller Account Access & Verification Badge',
    'Payment Related'                             => 'Billing, Zero Commission & Payment Terms',
    'Technical Issue'                             => 'Technical Issue (Supplier Portal & Bulk Upload)',
    'Platform Policies & Sourcing Agreement'      => 'Seller Agreement & Code of Conduct',
    'Other'                                       => 'Other Operational Inquiry',
];
?>

<!-- Hero Header Section -->
<section class="py-6 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-extrabold uppercase tracking-widest mb-3">
                <span class="material-symbols-outlined text-sm">precision_manufacturing</span> Manufacturer Operations Hub
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">Seller &amp; Manufacturer Help Centre</h1>
            <p class="text-slate-300 text-base leading-relaxed">
                Dedicated operational assistance for Indian sports goods manufacturers, workshop tooling managers, and primary exporters on Sports Junction.
            </p>
        </div>
    </div>
</section>

<!-- Main Form & Information Section -->
<section class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Information Cards -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">factory</span>
                        <span>Factory Support &amp; Onboarding</span>
                    </h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-600 mt-0.5">verified</span>
                            <div>
                                <div class="font-bold text-slate-900">Verification &amp; Statutory Audits</div>
                                <div>Assistance with submitting business incorporation documents, GST certificates, IEC export codes, and factory workshop photographs.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-600 mt-0.5">inventory</span>
                            <div>
                                <div class="font-bold text-slate-900">Catalog Audits &amp; Approvals</div>
                                <div>Questions regarding product spec moderation, bulk Excel upload formatting, image resolution, and category taxonomy assignment.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-600 mt-0.5">handshake</span>
                            <div>
                                <div class="font-bold text-slate-900">0% Commission Direct Trade</div>
                                <div>We never take commissions from bilateral deals. Contact our desk for contract templates, Incoterms, and buyer negotiations.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-sm border border-slate-800">
                    <h4 class="font-extrabold text-base mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-400">timer</span>
                        <span>Manufacturer Operations SLA</span>
                    </h4>
                    <p class="text-slate-300 text-xs leading-relaxed mb-4">
                        All inquiries submitted by verified or onboarding factories receive high-priority ticket tracking handled directly by our operations directors.
                    </p>
                    <div class="space-y-2 text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base">check_circle</span>
                            <span>Direct response within <strong>24 business hours</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base">check_circle</span>
                            <span>Dedicated seller onboarding specialist assigned</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base">check_circle</span>
                            <span>Status tracking in the Super Admin Help Center Desk</span>
                        </div>
                    </div>
                </div>

                <div class="bg-emerald-50/80 rounded-2xl p-5 border border-emerald-200 text-slate-800 text-xs space-y-2">
                    <div class="font-bold text-emerald-900 flex items-center gap-1.5 text-sm">
                        <span class="material-symbols-outlined text-base">mail</span>
                        <span>Manufacturer Helpdesk Contacts</span>
                    </div>
                    <p class="text-slate-600">For onboarding assistance, factory verification queries, or catalog moderation:</p>
                    <div class="font-mono text-emerald-900 font-semibold pt-1">
                        Email: <a href="mailto:support@sportsjunction.in" class="underline hover:text-emerald-700">support@sportsjunction.in</a>
                    </div>
                    <div class="text-slate-500 pt-1">
                        Operational Hours: Mon – Sat, 9:00 AM – 7:00 PM IST
                    </div>
                </div>
            </div>

            <!-- Right Form Container -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    
                    <div class="border-b border-slate-200 pb-4 mb-6">
                        <h2 class="text-xl font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">contact_support</span>
                            <span>Submit Manufacturer Support Query</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Fill out the inquiry form below. Our manufacturer operations team will review your query and respond with an official ticket update.
                        </p>
                    </div>

                    <form id="sellerHelpForm" action="<?= htmlspecialchars($baseUrl) ?>/api/help-centre/submit-query" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="source_type" value="SELLER">

                        <!-- Row 1: Contact Person & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="shName" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Contact Person / Signatory Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="shName" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="e.g. Ramesh Kumar" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            </div>

                            <div>
                                <label for="shEmail" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Business Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="shEmail" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="factory@sportsgear.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Row 2: Phone & Company Name -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="shPhone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Contact / WhatsApp Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="phone" id="shPhone" required value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="+91 98765 43210" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            </div>

                            <div>
                                <label for="shCompany" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Factory / Manufacturing Firm Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="company_name" id="shCompany" required value="<?= htmlspecialchars($prefillCompany) ?>" placeholder="e.g. Meerut Sports Works Pvt Ltd" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Row 3: Country & Category -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="shCountrySelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Country / Region
                                </label>
                                <select name="country_id" id="shCountrySelect" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none bg-white">
                                    <option value="">Select Country</option>
                                    <?php foreach ($countriesList as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= $prefillCountryId === (int)$c['id'] || $c['name'] === 'India' ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="shCategory" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Query Topic / Category <span class="text-red-500">*</span>
                                </label>
                                <select name="category" id="shCategory" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none bg-white">
                                    <option value="">Select Topic</option>
                                    <?php foreach ($sellerCategories as $catKey => $catLabel): ?>
                                        <option value="<?= htmlspecialchars($catKey) ?>">
                                            <?= htmlspecialchars($catLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Row 4: Subject Line -->
                        <div>
                            <label for="shSubject" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Subject <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="subject" id="shSubject" required placeholder="Brief summary of your inquiry (e.g. GST document re-upload for factory verification)" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        </div>

                        <!-- Row 5: Product SKU / Inquiry ID & Existing Ticket ID (Optional) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="shOrderId" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Product SKU / Inquiry Reference <span class="text-slate-400 font-normal lowercase">(optional)</span>
                                </label>
                                <input type="text" name="order_id" id="shOrderId" placeholder="e.g. SKU-FB-902 or INQ-8821" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            </div>

                            <div>
                                <label for="shTicketId" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Existing Ticket ID <span class="text-slate-400 font-normal lowercase">(if following up)</span>
                                </label>
                                <input type="text" name="existing_ticket_id" id="shTicketId" placeholder="e.g. TKT-SEL-2026-00008" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Row 6: Detailed Description -->
                        <div>
                            <label for="shDescription" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Detailed Description <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description" id="shDescription" required rows="5" placeholder="Please describe your factory verification query, catalog indexing question, RFQ inquiry, or portal issue in detail..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none"></textarea>
                        </div>

                        <!-- Row 7: Attachment Upload (Optional) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Supporting Document / Certificate <span class="text-slate-400 font-normal lowercase">(optional, max 10MB)</span>
                            </label>
                            <input type="file" name="attachment" id="shAttachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-900 hover:file:bg-emerald-100 cursor-pointer border border-slate-300 rounded-xl p-1 bg-slate-50/50">
                            <span class="text-[11px] text-slate-400 mt-1 block">Supported: GST/IEC PDFs, Workshop photos, Lab test certificates, ZIP archives.</span>
                        </div>

                        <!-- Row 8: Agreement Checkbox -->
                        <div class="pt-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="consent" required class="mt-1 rounded text-emerald-800 focus:ring-0">
                                <span class="text-xs text-slate-600 leading-relaxed">
                                    I confirm that I am an authorized representative of this manufacturing facility and agree to Sports Junction manufacturer support policies. <span class="text-red-500">*</span>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" id="shSubmitBtn" class="w-full sm:w-auto px-8 py-3.5 bg-emerald-800 hover:bg-emerald-700 text-white font-extrabold text-xs uppercase tracking-widest rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                                <span>Submit Manufacturer Query</span>
                                <span class="material-symbols-outlined text-[18px]">send</span>
                            </button>
                        </div>
                    </form>

                    <!-- Success Card Container -->
                    <div id="shSuccessCard" class="hidden text-center py-8">
                        <div class="w-16 h-16 bg-emerald-100 text-emerald-800 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-4xl">check_circle</span>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2">Query Submitted to Operations Desk!</h3>
                        <p class="text-sm text-slate-600 max-w-lg mx-auto mb-3">
                            Your manufacturer query has been logged. Our factory onboarding and catalog audit team has been notified with your reference ticket:
                        </p>
                        <div class="inline-block px-4 py-2 bg-emerald-50 rounded-xl text-xs font-bold text-emerald-800 mb-6 border border-emerald-200">
                            Ticket Reference ID: <span id="shSuccessRef" class="text-emerald-900 font-extrabold text-sm ml-1 font-mono"></span>
                        </div>
                        <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                            You can check the inquiry status in your registered email or with our Admin Operations Desk using this ticket ID.
                        </p>
                        <div>
                            <button type="button" onclick="resetSellerHelpForm()" class="px-6 py-2.5 rounded-xl bg-emerald-800 text-white font-bold text-xs uppercase tracking-wider hover:bg-emerald-700 transition cursor-pointer">
                                Submit Another Query
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<script>
document.getElementById('sellerHelpForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('shSubmitBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Submitting Query...';

    try {
        const formData = new FormData(this);
        const sel = document.getElementById('shCountrySelect');
        if (sel && sel.selectedIndex >= 0) {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.name) {
                formData.set('country_name', opt.dataset.name);
            }
        }

        const res = await fetch('<?= $baseUrl ?>/api/help-centre/submit-query', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = origHtml;

        if (data.success) {
            document.getElementById('sellerHelpForm').classList.add('hidden');
            document.getElementById('shSuccessRef').textContent = data.ticket_id || ('TKT-SEL-' + Date.now());
            document.getElementById('shSuccessCard').classList.remove('hidden');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Query Logged!',
                    text: data.message || 'Your support ticket has been generated successfully.',
                    confirmButtonColor: '#065f46'
                });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Submission Error', data.message || 'Please check your inputs.', 'error');
            } else {
                alert(data.message || 'Submission Error. Please check your inputs.');
            }
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Network error while submitting your query. Please try again.', 'error');
        } else {
            alert('Network error while submitting your query.');
        }
    }
});

function resetSellerHelpForm() {
    document.getElementById('sellerHelpForm').reset();
    document.getElementById('sellerHelpForm').classList.remove('hidden');
    document.getElementById('shSuccessCard').classList.add('hidden');
}
</script>
