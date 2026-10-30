<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$prefillName = $buyerDetails['name'] ?? '';
$prefillEmail = $buyerDetails['email'] ?? '';
$prefillPhone = !empty($buyerDetails['phone']) ? (($buyerDetails['country_code'] ?? '+91') . ' ' . $buyerDetails['phone']) : '';
$prefillCompany = $buyerDetails['company_name'] ?? '';
$prefillCountryId = (int)($buyerDetails['country_id'] ?? 0);
$countriesList = !empty($countries) ? $countries : \App\Models\Country::allActive();

$feedbackTypes = \App\Models\SiteFeedback::VALID_TYPES;
?>

<!-- Hero Header Section -->
<section class="py-6 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-extrabold uppercase tracking-widest mb-3">
                <span class="material-symbols-outlined text-sm">rate_review</span> Continuous Innovation
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">Suggestions &amp; Feedback</h1>
            <p class="text-slate-300 text-base leading-relaxed">
                We value your feedback. Help us improve Sports Junction by sharing your suggestions, experience, and ideas. Your feedback helps us build a better sports manufacturing and sourcing marketplace.
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
                        <span class="material-symbols-outlined text-emerald-600">psychology</span>
                        <span>Why Your Voice Matters</span>
                    </h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">rocket_launch</span>
                            <div>
                                <div class="font-bold text-slate-900">Feature Prioritization</div>
                                <div>Trader and manufacturer suggestions directly shape our product roadmap and quarterly platform feature releases.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">verified</span>
                            <div>
                                <div class="font-bold text-slate-900">Friction-Free Sourcing</div>
                                <div>Encountered an issue with RFQ creation, MOQ calculations, or catalog navigation? Your report helps us remove bottlenecks immediately.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 mt-0.5">mark_chat_read</span>
                            <div>
                                <div class="font-bold text-slate-900">Direct Team Review</div>
                                <div>Every submission generates a tracked reference ID reviewed by our product managers and operations directors within 48 business hours.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-sm border border-slate-800">
                    <h4 class="font-extrabold text-base mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-400">shield</span>
                        <span>Privacy &amp; Fair Feedback Commitment</span>
                    </h4>
                    <p class="text-slate-300 text-xs leading-relaxed mb-4">
                        Your honest feedback is kept strictly confidential within our leadership team. We never disclose buyer or supplier comments publicly without explicit written consent.
                    </p>
                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/80 text-xs text-slate-300 flex items-center gap-2">
                        <span class="material-symbols-outlined text-orange-400 text-lg">info</span>
                        <span>Need urgent order assistance? Visit our <a href="<?= htmlspecialchars($baseUrl) ?>/support" class="text-emerald-400 font-bold hover:underline">Help &amp; Support Desk</a>.</span>
                    </div>
                </div>
            </div>

            <!-- Right Suggestions Form -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-xl font-extrabold text-slate-900">Share Your Ideas &amp; Feedback</h2>
                        <span class="text-xs text-slate-400 font-semibold">* Required fields</span>
                    </div>
                    <p class="text-xs text-slate-500 mb-6">Complete the form below. You can also upload screenshots, documents, or mockups.</p>

                    <form id="suggestionFeedbackForm" action="<?= htmlspecialchars($baseUrl) ?>/feedback-suggestions" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                        <!-- Full Name & Company -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="sfName" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="e.g. Rahul Sharma" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                    Business / Company Name
                                </label>
                                <input type="text" name="company_name" id="sfCompany" value="<?= htmlspecialchars($prefillCompany) ?>" placeholder="Academy, Enterprise, or Brand" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Email & Phone -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                    Business Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="sfEmail" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="name@company.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                    Phone / WhatsApp Number
                                </label>
                                <input type="text" name="phone" id="sfPhone" value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="e.g. +91 9876543210" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Country & Feedback Type -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                    Country <span class="text-red-500">*</span>
                                </label>
                                <select name="country_id" id="sfCountrySelect" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                    <option value="">Select Your Country *</option>
                                    <?php foreach ($countriesList as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= ($prefillCountryId === (int)$c['id'] || (empty($prefillCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                            <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                    Feedback Type <span class="text-red-500">*</span>
                                </label>
                                <select name="feedback_type" id="sfType" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                    <?php foreach ($feedbackTypes as $ft): ?>
                                        <option value="<?= htmlspecialchars($ft) ?>"><?= htmlspecialchars($ft) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                Subject <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="subject" id="sfSubject" required placeholder="Brief summary of your suggestion or feedback" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>

                        <!-- Your Suggestion / Feedback -->
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                Your Suggestion / Feedback <span class="text-red-500">*</span>
                            </label>
                            <textarea name="message" id="sfMessage" rows="5" required placeholder="Describe your experience, recommendations, proposed features, or observations in detail..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                        </div>

                        <!-- Attachment (Optional) -->
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                Attachment (Optional Image/PDF)
                            </label>
                            <input type="file" name="attachment" id="sfAttachment" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1 bg-slate-50">
                            <p class="text-[11px] text-slate-400 mt-1">Allowed formats: PDF, JPG, PNG, WEBP (Max 10MB)</p>
                        </div>

                        <!-- Consent Checkbox -->
                        <div class="pt-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="consent_contact" value="1" required checked class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs text-slate-600 leading-relaxed">
                                    I agree that the Sports Junction product &amp; operations team may contact me regarding this feedback to clarify suggestions or provide platform updates. <span class="text-red-500">*</span>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" id="sfSubmitBtn" class="w-full sm:w-auto px-8 py-3.5 bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-widest rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <span>Submit Feedback</span>
                                <span class="material-symbols-outlined text-[18px]">send</span>
                            </button>
                        </div>
                    </form>

                    <!-- Success Card Container -->
                    <div id="sfSuccessCard" class="hidden text-center py-8">
                        <div class="w-16 h-16 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-4xl">check_circle</span>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2">Thank You for Your Valuable Feedback!</h3>
                        <p class="text-sm text-slate-600 max-w-lg mx-auto mb-3">
                            Your suggestions have been recorded and forwarded directly to the Sports Junction leadership and product team.
                        </p>
                        <div class="inline-block px-4 py-2 bg-slate-100 rounded-xl text-xs font-bold text-slate-700 mb-6 border border-slate-200">
                            Feedback Reference ID: <span id="sfSuccessRef" class="text-blue-900 font-extrabold"></span>
                        </div>
                        <div>
                            <button type="button" onclick="resetSuggestionForm()" class="px-6 py-2.5 rounded-xl bg-blue-900 text-white font-bold text-xs uppercase tracking-wider hover:bg-blue-800 transition">
                                Submit Another Suggestion
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<script>
document.getElementById('suggestionFeedbackForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('sfSubmitBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Submitting...';

    try {
        const formData = new FormData(this);
        const sel = document.getElementById('sfCountrySelect');
        if (sel && sel.selectedIndex >= 0) {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.name) {
                formData.set('country_name', opt.dataset.name);
            }
        }

        const res = await fetch('<?= $baseUrl ?>/api/feedback', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = origHtml;

        if (data.success) {
            document.getElementById('suggestionFeedbackForm').classList.add('hidden');
            document.getElementById('sfSuccessRef').textContent = data.reference_id || ('SFB-' + data.id);
            document.getElementById('sfSuccessCard').classList.remove('hidden');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Feedback Received!',
                    text: data.message || 'Thank you for your valuable feedback!',
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
            Swal.fire('Error', 'Network error while submitting your feedback.', 'error');
        } else {
            alert('Network error while submitting your feedback.');
        }
    }
});

function resetSuggestionForm() {
    document.getElementById('suggestionFeedbackForm').reset();
    document.getElementById('suggestionFeedbackForm').classList.remove('hidden');
    document.getElementById('sfSuccessCard').classList.add('hidden');
}
</script>
