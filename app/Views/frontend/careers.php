<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$prefillName = $buyerDetails['name'] ?? '';
$prefillEmail = $buyerDetails['email'] ?? '';
$prefillPhone = !empty($buyerDetails['phone']) ? (($buyerDetails['country_code'] ?? '+91') . ' ' . $buyerDetails['phone']) : '';
$prefillCountryId = (int)($buyerDetails['country_id'] ?? 0);
$countriesList = !empty($countries) ? $countries : \App\Models\Country::allActive();

$activeJobs = !empty($jobOpenings) ? $jobOpenings : \App\Models\JobOpening::allActive();

$flashError = \App\Helpers\Session::flash('error');
$flashSuccess = \App\Helpers\Session::flash('success');
?>

<!-- Hero Header Section -->
<section class="py-8 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-extrabold uppercase tracking-widest mb-3">
            <span class="material-symbols-outlined text-sm">work</span> Careers &amp; Opportunities
        </span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-2">Careers at Sports Junction</h1>
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
            Build your career with Sports Junction. Join our team and contribute to transforming the sports manufacturing and sourcing industry. Fill out the application form below.
        </p>
    </div>
</section>

<!-- Main Form Section -->
<section class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

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
                Thank you for applying. Your application has been registered with our talent acquisition team.
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
            <div class="border-b border-slate-100 pb-5 mb-8">
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-700">badge</span>
                    <span>Candidate Job Application Form</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Please provide accurate contact details and experience information. Fields marked with <span class="text-red-500 font-bold">*</span> are mandatory.
                </p>
            </div>

            <form id="careerApplicationForm" action="<?= htmlspecialchars($baseUrl) ?>/careers/apply" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="job_opening_id" id="jobOpeningId" value="">

                <!-- Section 1: Role Applied For -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-extrabold flex items-center justify-center">1</span>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800">Position / Job Role</h3>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="positionSelect" class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
                                Select Position Applied For <span class="text-red-500">*</span>
                            </label>
                            <select name="position_applied" id="positionSelect" required class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white font-medium text-slate-800">
                                <option value="">-- Select a Job Opening / Role --</option>
                                <?php if (!empty($activeJobs)): ?>
                                    <optgroup label="Current Active Openings">
                                        <?php foreach ($activeJobs as $j): ?>
                                            <option value="<?= htmlspecialchars($j['title']) ?>" data-id="<?= (int)$j['id'] ?>">
                                                <?= htmlspecialchars($j['title']) ?> (<?= htmlspecialchars($j['department']) ?> &bull; <?= htmlspecialchars($j['location']) ?> &bull; <?= htmlspecialchars($j['employment_type']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <optgroup label="Other Options">
                                    <option value="General Application" data-id="">General Application (Any Open Position / Talent Pool)</option>
                                    <option value="Other" data-id="">Other / Custom Role (Specify Below)</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Custom position input if 'Other' selected -->
                        <div id="otherPositionWrapper" class="hidden">
                            <label for="otherPositionInput" class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
                                Specify Your Desired Position / Role <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="otherPositionInput" placeholder="e.g. Quality Assurance Lead, Sports Goods Merchandiser..." class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Personal & Contact Information -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-extrabold flex items-center justify-center">2</span>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800">Personal &amp; Contact Details</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="e.g. John Doe" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="john.doe@example.com" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Mobile Number / WhatsApp <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" required value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="+91 9876543210" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Current Location / City <span class="text-red-500">*</span></label>
                            <input type="text" name="city" required placeholder="e.g. New Delhi, Jalandhar, Bengaluru" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Country <span class="text-red-500">*</span></label>
                            <select name="country_id" id="jobCountrySelect" required class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                <option value="">Select Country *</option>
                                <?php foreach ($countriesList as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= ($prefillCountryId === (int)$c['id'] || (empty($prefillCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                        <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Professional Experience & Availability -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-extrabold flex items-center justify-center">3</span>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800">Professional Experience</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Total Work Experience <span class="text-red-500">*</span></label>
                            <select name="total_experience" required class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                <option value="">Select Experience Level</option>
                                <option value="Fresher / Entry Level">Fresher / Entry Level (&lt; 1 Year)</option>
                                <option value="1-2 Years">1 - 2 Years</option>
                                <option value="3-5 Years" selected>3 - 5 Years</option>
                                <option value="5-8 Years">5 - 8 Years</option>
                                <option value="8-10 Years">8 - 10 Years</option>
                                <option value="10+ Years">10+ Years</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Notice Period / Availability <span class="text-red-500">*</span></label>
                            <select name="notice_period" required class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                                <option value="Immediate">Immediate Availability</option>
                                <option value="15 Days">15 Days</option>
                                <option value="30 Days" selected>30 Days</option>
                                <option value="60 Days">60 Days</option>
                                <option value="90 Days">90 Days</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Current / Most Recent Company</label>
                            <input type="text" name="current_company" placeholder="e.g. Apex Sports Ltd (or Freelance)" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Current Job Title</label>
                            <input type="text" name="current_job_title" placeholder="e.g. Senior Merchandiser / Developer" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Expected Salary / CTC</label>
                            <input type="text" name="expected_salary" placeholder="e.g. ₹8,00,000 PA / Negotiable" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 4: Online Profiles & Portfolio -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-extrabold flex items-center justify-center">4</span>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800">Online Profiles (Optional)</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">LinkedIn Profile URL</label>
                            <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/username" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Portfolio / GitHub / Personal Website</label>
                            <input type="url" name="portfolio_url" placeholder="https://myportfolio.com" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 5: Resume & Cover Letter -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-extrabold flex items-center justify-center">5</span>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800">Resume &amp; Additional Information</h3>
                    </div>

                    <div class="space-y-5">
                        <!-- Resume File Upload -->
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
                                Upload Resume / CV <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl p-6 text-center bg-slate-50 hover:bg-blue-50/30 transition cursor-pointer" id="resumeDropzone">
                                <input type="file" name="resume" id="resumeFileInput" required accept=".pdf,.doc,.docx" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div class="flex flex-col items-center justify-center pointer-events-none">
                                    <span class="material-symbols-outlined text-4xl text-blue-600 mb-2" id="resumeIcon">cloud_upload</span>
                                    <div class="text-sm font-bold text-slate-800" id="resumeFileLabel">
                                        Click to browse or drag and drop your resume here
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1" id="resumeFileInfo">
                                        Accepted formats: PDF, DOC, DOCX (Max size: 10MB)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cover Letter -->
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
                                Cover Letter / Why Sports Junction? (Optional)
                            </label>
                            <textarea name="cover_letter" rows="4" placeholder="Briefly describe your interest in Sports Junction, key achievements, or relevant expertise..." class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                        </div>

                        <!-- Privacy Policy Consent -->
                        <div class="pt-2">
                            <label class="flex items-start gap-3 cursor-pointer select-none">
                                <input type="checkbox" name="consent_privacy" value="1" required checked class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs text-slate-600 leading-relaxed">
                                    I confirm that the information provided is accurate, and I consent to Sports Junction processing my candidate application in accordance with the <a href="<?= htmlspecialchars($baseUrl) ?>/privacy-policy" target="_blank" class="text-blue-700 font-bold hover:underline">Privacy Policy</a>. <span class="text-red-500">*</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Action -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-slate-500">
                        Our recruitment team typically responds within 3-5 business days.
                    </p>
                    <button type="submit" id="btnSubmitApplication" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-sm uppercase tracking-wider shadow-md transition flex items-center justify-center gap-2">
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
    const positionSelect = document.getElementById('positionSelect');
    const otherWrapper = document.getElementById('otherPositionWrapper');
    const otherInput = document.getElementById('otherPositionInput');
    const jobOpeningIdInput = document.getElementById('jobOpeningId');
    const form = document.getElementById('careerApplicationForm');
    const resumeInput = document.getElementById('resumeFileInput');
    const resumeLabel = document.getElementById('resumeFileLabel');
    const resumeInfo = document.getElementById('resumeFileInfo');
    const resumeIcon = document.getElementById('resumeIcon');

    // Handle URL parameters if coming from a direct link like /careers?job=Software+Engineer
    const urlParams = new URLSearchParams(window.location.search);
    const requestedJob = urlParams.get('job') || urlParams.get('position');
    const requestedId = urlParams.get('id');

    if (positionSelect) {
        if (requestedId || requestedJob) {
            for (let i = 0; i < positionSelect.options.length; i++) {
                const opt = positionSelect.options[i];
                if ((requestedId && opt.dataset.id == requestedId) ||
                    (requestedJob && opt.value.toLowerCase().includes(requestedJob.toLowerCase()))) {
                    positionSelect.selectedIndex = i;
                    break;
                }
            }
        }

        // Toggle other input
        function handlePositionChange() {
            const selectedOpt = positionSelect.options[positionSelect.selectedIndex];
            if (positionSelect.value === 'Other') {
                otherWrapper.classList.remove('hidden');
                otherInput.required = true;
                jobOpeningIdInput.value = '';
            } else {
                otherWrapper.classList.add('hidden');
                otherInput.required = false;
                jobOpeningIdInput.value = selectedOpt ? (selectedOpt.dataset.id || '') : '';
            }
        }

        positionSelect.addEventListener('change', handlePositionChange);
        handlePositionChange();
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
                    resumeLabel.textContent = 'Click to browse or drag and drop your resume here';
                    resumeInfo.textContent = 'Accepted formats: PDF, DOC, DOCX (Max size: 10MB)';
                    resumeIcon.textContent = 'cloud_upload';
                    resumeIcon.classList.remove('text-emerald-600');
                    resumeIcon.classList.add('text-blue-600');
                    return;
                }
                resumeLabel.textContent = file.name;
                resumeInfo.textContent = `Size: ${sizeMB} MB &bull; File ready for upload`;
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

            // Validate custom position if selected
            if (positionSelect.value === 'Other' && !otherInput.value.trim()) {
                alert('Please specify the position you are applying for.');
                otherInput.focus();
                return;
            }

            const btn = document.getElementById('btnSubmitApplication');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Submitting Application...';

            try {
                const formData = new FormData(form);

                // If "Other" role was typed, set that as position_applied
                if (positionSelect.value === 'Other') {
                    formData.set('position_applied', otherInput.value.trim() || 'Custom Position');
                }

                // Append country name
                const countrySel = document.getElementById('jobCountrySelect');
                if (countrySel && countrySel.selectedIndex >= 0) {
                    const opt = countrySel.options[countrySel.selectedIndex];
                    if (opt && opt.dataset.name) {
                        formData.set('country_name', opt.dataset.name);
                    }
                }

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
                    if (resumeLabel) resumeLabel.textContent = 'Click to browse or drag and drop your resume here';
                    if (resumeInfo) resumeInfo.textContent = 'Accepted formats: PDF, DOC, DOCX (Max size: 10MB)';
                    if (resumeIcon) {
                        resumeIcon.textContent = 'cloud_upload';
                        resumeIcon.classList.remove('text-emerald-600');
                        resumeIcon.classList.add('text-blue-600');
                    }
                    if (otherWrapper) otherWrapper.classList.add('hidden');

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
    window.scrollTo({ top: 200, behavior: 'smooth' });
}
</script>
