<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$prefillName = $buyerDetails['name'] ?? '';
$prefillEmail = $buyerDetails['email'] ?? '';
$prefillPhone = !empty($buyerDetails['phone']) ? (($buyerDetails['country_code'] ?? '+91') . ' ' . $buyerDetails['phone']) : '';

$flashError = \App\Helpers\Session::flash('error');
$flashSuccess = \App\Helpers\Session::flash('success');
?>

<!-- Hero Header Section -->
<section class="py-8 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-extrabold uppercase tracking-widest mb-3">
            <span class="material-symbols-outlined text-sm">work</span> Careers &amp; Opportunities
        </span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-2">Careers at Sports Junction</h1>
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-xl mx-auto">
            Enter your desired job role and contact details below. Our talent acquisition team will review your application.
        </p>
    </div>
</section>

<!-- Main Form Section -->
<section class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if (!empty($flashError)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">error</span>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashSuccess)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <!-- Inline Submission Success Notification (AJAX) -->
        <div id="applicationSuccessCard" class="hidden mb-8 p-8 rounded-3xl bg-emerald-50 border border-emerald-200 text-center shadow-xs">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 mx-auto flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-3xl">check_circle</span>
            </div>
            <h3 class="text-2xl font-extrabold text-slate-900 mb-2">Application Submitted Successfully!</h3>
            <p class="text-sm text-slate-600 mb-4" id="successMessageText">
                Thank you for applying. Your application has been registered with our team.
            </p>
            <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-emerald-300 text-emerald-900 font-bold text-sm shadow-2xs">
                <span>Application Reference ID:</span>
                <span id="successRefNo" class="font-mono text-base font-extrabold text-blue-900"></span>
            </div>
            <div class="mt-6">
                <button type="button" onclick="resetFormForNewSubmission()" class="px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-bold text-xs uppercase tracking-wider transition">
                    Submit Another Application
                </button>
            </div>
        </div>

        <!-- Application Form Container -->
        <div id="applicationFormWrapper" class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-slate-200">
            <div class="border-b border-slate-100 pb-5 mb-6">
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-700">badge</span>
                    <span>Job Application Form</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Fill in the form below to submit your profile for any job role.
                </p>
            </div>

            <form id="careerApplicationForm" action="<?= htmlspecialchars($baseUrl) ?>/careers/apply" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- 1. Enter Job Role -->
                <div>
                    <label for="positionInput" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                        Enter Job Role / Position <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="position_applied" id="positionInput" required placeholder="e.g. Sales Executive, Sourcing Specialist, Full-Stack Developer..." class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white font-medium text-slate-800">
                </div>

                <!-- 2. Full Name & Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="Your Full Name" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="name@domain.com" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                </div>

                <!-- 3. Mobile Number & City -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                            Mobile Number / WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="phone" required value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="+91 9876543210" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                            Current Location / City <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="city" required placeholder="e.g. New Delhi, Jalandhar, Bengaluru" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                </div>

                <!-- 4. Upload Resume -->
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                        Upload Resume / CV (PDF, DOC, DOCX) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl p-5 text-center bg-slate-50 hover:bg-blue-50/30 transition cursor-pointer" id="resumeDropzone">
                        <input type="file" name="resume" id="resumeFileInput" required accept=".pdf,.doc,.docx" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="flex flex-col items-center justify-center pointer-events-none">
                            <span class="material-symbols-outlined text-3xl text-blue-600 mb-1" id="resumeIcon">cloud_upload</span>
                            <div class="text-xs sm:text-sm font-bold text-slate-800" id="resumeFileLabel">
                                Click or drag and drop your resume file here
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5" id="resumeFileInfo">
                                Allowed formats: PDF, DOC, DOCX (Max size: 10MB)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Message / Notes (Optional) -->
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                        Message / Additional Notes <span class="text-slate-400 font-normal lowercase">(optional)</span>
                    </label>
                    <textarea name="cover_letter" rows="3" placeholder="Any details, work experience, or note you would like to share..." class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" id="btnSubmitApplication" class="w-full sm:w-auto px-8 py-3 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-sm uppercase tracking-wider shadow-md transition flex items-center justify-center gap-2">
                        <span>Submit Application</span>
                        <span class="material-symbols-outlined text-lg">send</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const positionInput = document.getElementById('positionInput');
    const form = document.getElementById('careerApplicationForm');
    const resumeInput = document.getElementById('resumeFileInput');
    const resumeLabel = document.getElementById('resumeFileLabel');
    const resumeInfo = document.getElementById('resumeFileInfo');
    const resumeIcon = document.getElementById('resumeIcon');

    // Handle URL parameters if coming from a direct link like /careers?job=Sales+Executive
    const urlParams = new URLSearchParams(window.location.search);
    const requestedJob = urlParams.get('job') || urlParams.get('position') || urlParams.get('role');
    if (positionInput && requestedJob) {
        positionInput.value = requestedJob;
    }

    // Resume file selection display
    if (resumeInput) {
        resumeInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                if (file.size > 10 * 1024 * 1024) {
                    alert('The selected file exceeds the 10MB limit. Please choose a smaller file.');
                    this.value = '';
                    resumeLabel.textContent = 'Click or drag and drop your resume file here';
                    resumeInfo.textContent = 'Allowed formats: PDF, DOC, DOCX (Max size: 10MB)';
                    resumeIcon.textContent = 'cloud_upload';
                    resumeIcon.classList.remove('text-emerald-600');
                    resumeIcon.classList.add('text-blue-600');
                    return;
                }
                resumeLabel.textContent = file.name;
                resumeInfo.textContent = `Size: ${sizeMB} MB • File ready for submission`;
                resumeIcon.textContent = 'task';
                resumeIcon.classList.remove('text-blue-600');
                resumeIcon.classList.add('text-emerald-600');
            }
        });
    }

    // Form submission via AJAX
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnSubmitApplication');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Submitting...';

            try {
                const formData = new FormData(form);

                const res = await fetch('<?= $baseUrl ?>/api/careers/apply', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = origHtml;

                if (data.success) {
                    form.reset();
                    if (resumeLabel) resumeLabel.textContent = 'Click or drag and drop your resume file here';
                    if (resumeInfo) resumeInfo.textContent = 'Allowed formats: PDF, DOC, DOCX (Max size: 10MB)';
                    if (resumeIcon) {
                        resumeIcon.textContent = 'cloud_upload';
                        resumeIcon.classList.remove('text-emerald-600');
                        resumeIcon.classList.add('text-blue-600');
                    }

                    // Show success block and scroll to top
                    const successCard = document.getElementById('applicationSuccessCard');
                    const formWrapper = document.getElementById('applicationFormWrapper');
                    const refEl = document.getElementById('successRefNo');
                    const msgEl = document.getElementById('successMessageText');

                    if (refEl) refEl.textContent = data.application_no || 'APP-RECEIVED';
                    if (msgEl && data.message) msgEl.textContent = data.message;
                    if (successCard) successCard.classList.remove('hidden');
                    if (formWrapper) formWrapper.classList.add('hidden');

                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Application Received!',
                            html: `<p>${data.message || 'Thank you for applying!'}</p><p class="mt-2 text-xs text-slate-500">Application Reference ID: <strong>${data.application_no || ''}</strong></p>`,
                            confirmButtonColor: '#00288e'
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Application Error', data.message || 'Please review your inputs.', 'error');
                    } else {
                        alert(data.message || 'Application Error. Please review your inputs.');
                    }
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Network Error', 'There was a problem submitting your application. Please try again.', 'error');
                } else {
                    alert('Network error while submitting your application. Please try again.');
                }
            }
        });
    }
});

function resetFormForNewSubmission() {
    const successCard = document.getElementById('applicationSuccessCard');
    const formWrapper = document.getElementById('applicationFormWrapper');
    if (successCard) successCard.classList.add('hidden');
    if (formWrapper) formWrapper.classList.remove('hidden');
    window.scrollTo({ top: 150, behavior: 'smooth' });
}
</script>
