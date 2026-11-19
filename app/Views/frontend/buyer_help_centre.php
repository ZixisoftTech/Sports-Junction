<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$prefillName = $buyerDetails['name'] ?? '';
$prefillEmail = $buyerDetails['email'] ?? '';
$prefillPhone = !empty($buyerDetails['phone']) ? (($buyerDetails['country_code'] ?? '+91') . ' ' . $buyerDetails['phone']) : '';
$prefillCompany = $buyerDetails['company_name'] ?? '';
$prefillCountryId = (int)($buyerDetails['country_id'] ?? 0);
$countriesList = !empty($countries) ? $countries : \App\Models\Country::allActive();

$buyerCategories = [
    'Order Related'                        => 'Order Related (Tracking, Dispatch & IncoTerms)',
    'RFQ & Custom OEM Inquiries'           => 'RFQ & Custom OEM Inquiries (Specs & Pricing)',
    'Payment Related'                      => 'Payment Related (Escrow, Invoicing & Billing)',
    'Delivery & Shipping'                  => 'Delivery & Shipping (Port Freight & Customs)',
    'Product Related'                      => 'Product Related (Material Quality & Inspection)',
    'Account & Registration'               => 'Account & Registration (Profile & Login)',
    'Technical Issue'                      => 'Technical Issue (Portal Navigation & Tools)',
    'Platform Policies & Sourcing Agreement' => 'Platform Policies & Sourcing Agreement',
    'Other'                                => 'Other General Inquiry',
];
?>

<!-- Hero Header Section -->
<section class="py-6 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-extrabold uppercase tracking-widest mb-3">
                <span class="material-symbols-outlined text-sm">support_agent</span> Verified Buyer Support Desk
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">Buyer Help Centre</h1>
            <p class="text-slate-300 text-base leading-relaxed">
                Dedicated trade and operational support for institutional buyers, retail chains, and sports brands sourcing directly from verified Indian manufacturers.
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
                        <span class="material-symbols-outlined text-blue-700">verified_user</span>
                        <span>Institutional Procurement Support</span>
                    </h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">request_quote</span>
                            <div>
                                <div class="font-bold text-slate-900">RFQ &amp; Direct Factory Quotes</div>
                                <div>Need help formulating technical OEM specifications or volume discount matrices? Our trade desk assists with supplier matchmaking.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">fact_check</span>
                            <div>
                                <div class="font-bold text-slate-900">Factory Verification &amp; Audits</div>
                                <div>Inquire about statutory GSTIN, IEC, ISO certification status, and pre-shipment quality inspection protocols for eligible goods.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">local_shipping</span>
                            <div>
                                <div class="font-bold text-slate-900">Port Logistics &amp; IncoTerms</div>
                                <div>Questions regarding Ex-Works, FOB Nhava Sheva / Mundra, or express international air courier for prototype samples.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-sm border border-slate-800">
                    <h4 class="font-extrabold text-base mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-400">timer</span>
                        <span>Support SLA &amp; Ticket Tracking</span>
                    </h4>
                    <p class="text-slate-300 text-xs leading-relaxed mb-4">
                        Every inquiry is logged with an official <strong>Ticket Reference ID</strong> tracked directly on our Super Admin Desk.
                    </p>
                    <div class="space-y-2 text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base">check_circle</span>
                            <span>Initial review &amp; assignment within <strong>12 business hours</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base">check_circle</span>
                            <span>Direct factory coordination &amp; resolution within <strong>24–48 hours</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base">check_circle</span>
                            <span>Email &amp; internal dashboard notifications on status changes</span>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50/80 rounded-2xl p-5 border border-blue-200 text-slate-800 text-xs space-y-2">
                    <div class="font-bold text-blue-900 flex items-center gap-1.5 text-sm">
                        <span class="material-symbols-outlined text-base">mail</span>
                        <span>Direct Sourcing Desk Contacts</span>
                    </div>
                    <p class="text-slate-600">For enterprise contracts, institutional bulk orders, or container freight inquiries:</p>
                    <div class="font-mono text-blue-900 font-semibold pt-1">
                        Email: <a href="mailto:support@sportsjunction.in" class="underline hover:text-blue-700">support@sportsjunction.in</a>
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
                            <span class="material-symbols-outlined text-blue-700">contact_support</span>
                            <span>Submit Buyer Support Inquiry</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Complete the details below. Our sourcing support team will review your inquiry and follow up promptly.
                        </p>
                    </div>

                    <form id="buyerHelpForm" action="<?= htmlspecialchars($baseUrl) ?>/api/help-centre/submit-query" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="source_type" value="BUYER">

                        <!-- Row 1: Name & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="bhName" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="bhName" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="e.g. Johnathan Smith" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>

                            <div>
                                <label for="bhEmail" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="bhEmail" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="procurement@company.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Row 2: Phone & Company -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="bhPhone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Phone / WhatsApp <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="phone" id="bhPhone" required value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="+1 555-0199 or +91 98765 43210" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>

                            <div>
                                <label for="bhCompany" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Company / Retail Chain Name
                                </label>
                                <input type="text" name="company_name" id="bhCompany" value="<?= htmlspecialchars($prefillCompany) ?>" placeholder="e.g. Pacific Athletics Inc." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Row 3: Country & Query Category -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="bhCountrySelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Country / Region
                                </label>
                                <select name="country_id" id="bhCountrySelect" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                    <option value="">Select Country</option>
                                    <?php foreach ($countriesList as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= $prefillCountryId === (int)$c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="bhCategory" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Query Topic / Category <span class="text-red-500">*</span>
                                </label>
                                <select name="category" id="bhCategory" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                    <option value="">Select Topic</option>
                                    <?php foreach ($buyerCategories as $catKey => $catLabel): ?>
                                        <option value="<?= htmlspecialchars($catKey) ?>">
                                            <?= htmlspecialchars($catLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Row 4: Subject Line -->
                        <div>
                            <label for="bhSubject" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Subject <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="subject" id="bhSubject" required placeholder="Brief summary of your inquiry (e.g. RFQ assistance for 500 leather cricket balls)" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>

                        <!-- Row 5: Reference / Order ID & Existing Ticket ID (Optional) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="bhOrderId" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Order / RFQ Reference ID <span class="text-slate-400 font-normal lowercase">(optional)</span>
                                </label>
                                <input type="text" name="order_id" id="bhOrderId" placeholder="e.g. RFQ-2026-0042 or ORD-1092" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>

                            <div>
                                <label for="bhTicketId" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Existing Ticket ID <span class="text-slate-400 font-normal lowercase">(if following up)</span>
                                </label>
                                <input type="text" name="existing_ticket_id" id="bhTicketId" placeholder="e.g. TKT-BUY-2026-00012" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Row 6: Detailed Description -->
                        <div>
                            <label for="bhDescription" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Detailed Description <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description" id="bhDescription" required rows="5" placeholder="Please describe your sourcing requirements, quotation question, factory verification request, or order issue in detail..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                        </div>

                        <!-- Row 7: Attachment Upload (Optional) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Supporting Document / Screenshot <span class="text-slate-400 font-normal lowercase">(optional, max 10MB)</span>
                            </label>
                            <input type="file" name="attachment" id="bhAttachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 cursor-pointer border border-slate-300 rounded-xl p-1 bg-slate-50/50">
                            <span class="text-[11px] text-slate-400 mt-1 block">Supported: PDF, Images (JPG, PNG), Excel/Word sheets, ZIP archives.</span>
                        </div>

                        <!-- Row 8: Agreement Checkbox -->
                        <div class="pt-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="consent" required class="mt-1 rounded text-blue-900 focus:ring-0">
                                <span class="text-xs text-slate-600 leading-relaxed">
                                    I confirm that the details provided are accurate and authorize Sports Junction to process this inquiry and coordinate with verified manufacturers. <span class="text-red-500">*</span>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" id="bhSubmitBtn" class="w-full sm:w-auto px-8 py-3.5 bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-widest rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                                <span>Submit Buyer Inquiry</span>
                                <span class="material-symbols-outlined text-[18px]">send</span>
                            </button>
                        </div>
                    </form>

                    <!-- Success Card Container -->
                    <div id="bhSuccessCard" class="hidden text-center py-8">
                        <div class="w-16 h-16 bg-blue-100 text-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-4xl">check_circle</span>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2">Inquiry Submitted Successfully!</h3>
                        <p class="text-sm text-slate-600 max-w-lg mx-auto mb-3">
                            Your inquiry has been registered in the Sports Junction procurement desk. A confirmation has been logged with your unique reference ticket:
                        </p>
                        <div class="inline-block px-4 py-2 bg-blue-50 rounded-xl text-xs font-bold text-blue-900 mb-6 border border-blue-200">
                            Ticket Reference ID: <span id="bhSuccessRef" class="text-blue-900 font-extrabold text-sm ml-1 font-mono"></span>
                        </div>
                        <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                            You can view status updates directly in the Admin Panel Help Center or via your registered email address.
                        </p>
                        <div>
                            <button type="button" onclick="resetBuyerHelpForm()" class="px-6 py-2.5 rounded-xl bg-blue-900 text-white font-bold text-xs uppercase tracking-wider hover:bg-blue-800 transition cursor-pointer">
                                Submit Another Inquiry
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<script>
document.getElementById('buyerHelpForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('bhSubmitBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Submitting Inquiry...';

    try {
        const formData = new FormData(this);
        const sel = document.getElementById('bhCountrySelect');
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
            document.getElementById('buyerHelpForm').classList.add('hidden');
            document.getElementById('bhSuccessRef').textContent = data.ticket_id || ('TKT-BUY-' + Date.now());
            document.getElementById('bhSuccessCard').classList.remove('hidden');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Inquiry Logged!',
                    text: data.message || 'Your support ticket has been generated successfully.',
                    confirmButtonColor: '#1e3a8a'
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
            Swal.fire('Error', 'Network error while submitting your inquiry. Please try again.', 'error');
        } else {
            alert('Network error while submitting your inquiry.');
        }
    }
});

function resetBuyerHelpForm() {
    document.getElementById('buyerHelpForm').reset();
    document.getElementById('buyerHelpForm').classList.remove('hidden');
    document.getElementById('bhSuccessCard').classList.add('hidden');
}
</script>
