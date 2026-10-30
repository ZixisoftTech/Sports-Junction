<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
$prefillName = $buyerDetails['name'] ?? '';
$prefillEmail = $buyerDetails['email'] ?? '';
$prefillPhone = !empty($buyerDetails['phone']) ? (($buyerDetails['country_code'] ?? '+91') . ' ' . $buyerDetails['phone']) : '';
$prefillCountryId = (int)($buyerDetails['country_id'] ?? 0);
$countriesList = !empty($countries) ? $countries : \App\Models\Country::allActive();

$activeJobs = !empty($jobOpenings) ? $jobOpenings : \App\Models\JobOpening::allActive();
?>

<!-- Hero Header Section -->
<section class="py-8 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-extrabold uppercase tracking-widest mb-3">
                <span class="material-symbols-outlined text-sm">rocket_launch</span> Join Our Mission
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white mb-4">Careers at Sports Junction</h1>
            <p class="text-slate-300 text-base sm:text-lg leading-relaxed">
                Build your career with Sports Junction. Join our team and contribute to transforming the sports manufacturing and sourcing industry.
            </p>
            <div class="mt-6 flex flex-wrap gap-4">
                <a href="#current-openings" class="px-6 py-3 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs uppercase tracking-wider transition shadow-md flex items-center gap-2">
                    <span>Explore Openings</span>
                    <span class="material-symbols-outlined text-sm">arrow_downward</span>
                </a>
                <button type="button" onclick="openApplicationModal('General Application')" class="px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs uppercase tracking-wider transition">
                    General Application
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Why Work With Us & Culture Section -->
<section class="py-16 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-extrabold text-blue-600 uppercase tracking-widest block mb-2">Life at Sports Junction</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Why Build Your Career With Us?</h2>
            <p class="text-sm text-slate-600 mt-2">
                We combine deep industrial sports manufacturing roots with digital marketplace technology. Here is what you will experience every day:
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs hover:border-blue-300 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl">precision_manufacturing</span>
                </div>
                <h3 class="text-base font-extrabold text-slate-900 mb-2">Real Industrial Impact</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Your code, vendor negotiations, and customer interactions directly empower India's traditional manufacturing hubs in Jalandhar, Meerut, and beyond to serve the global sports economy.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs hover:border-emerald-300 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl">trending_up</span>
                </div>
                <h3 class="text-base font-extrabold text-slate-900 mb-2">Fast-Paced, Merit-Driven Growth</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    We value velocity, curiosity, clear communication, and customer empathy over hierarchy. High performers take full ownership and advance rapidly.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-700 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl">favorite</span>
                </div>
                <h3 class="text-base font-extrabold text-slate-900 mb-2">Comprehensive Wellbeing</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Competitive compensation, family health insurance, flexible working environments (hybrid/remote where applicable), and continuous learning allowances.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Current Job Openings Section -->
<section id="current-openings" class="py-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <span class="text-xs font-extrabold text-orange-600 uppercase tracking-widest block mb-1">Open Positions</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Current Job Openings</h2>
            </div>
            <div>
                <button type="button" onclick="openApplicationModal('General Application')" class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 hover:border-blue-600 text-slate-700 hover:text-blue-600 font-bold text-xs uppercase tracking-wider transition shadow-2xs">
                    Can't find a match? Submit General Application
                </button>
            </div>
        </div>

        <?php if (empty($activeJobs)): ?>
            <div class="bg-white rounded-2xl p-10 text-center border border-slate-200 shadow-xs">
                <span class="material-symbols-outlined text-slate-400 text-5xl mb-3">work_off</span>
                <h3 class="text-lg font-extrabold text-slate-800 mb-1">No Specific Openings Right Now</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                    We are always looking for exceptional talent in engineering, category operations, and customer success. Submit a general application and we'll reach out when a suitable role opens.
                </p>
                <button type="button" onclick="openApplicationModal('General Application')" class="px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-wider transition">
                    Submit General Application
                </button>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($activeJobs as $job): ?>
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-2xs hover:shadow-sm transition">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <div class="space-y-2 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-extrabold uppercase tracking-wide">
                                        <?= htmlspecialchars($job['department']) ?>
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[11px] font-bold">
                                        <?= htmlspecialchars($job['employment_type']) ?>
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold">
                                        Exp: <?= htmlspecialchars($job['experience_level']) ?>
                                    </span>
                                </div>
                                <h3 class="text-lg sm:text-xl font-extrabold text-slate-900"><?= htmlspecialchars($job['title']) ?></h3>
                                <p class="text-xs text-slate-500 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-slate-400">location_on</span>
                                    <span><?= htmlspecialchars($job['location']) ?></span>
                                </p>
                                <?php if (!empty($job['overview'])): ?>
                                    <p class="text-xs text-slate-600 leading-relaxed pt-1"><?= nl2br(htmlspecialchars($job['overview'])) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="shrink-0 flex flex-col sm:flex-row gap-3">
                                <?php if (!empty($job['responsibilities']) || !empty($job['requirements'])): ?>
                                    <button type="button" onclick="toggleJobDetails(<?= (int)$job['id'] ?>)" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition">
                                        View Details
                                    </button>
                                <?php endif; ?>
                                <button type="button" onclick="openApplicationModal('<?= htmlspecialchars(addslashes($job['title'])) ?>', <?= (int)$job['id'] ?>)" class="px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-wider transition shadow-xs flex items-center justify-center gap-1.5">
                                    <span>Apply Now</span>
                                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </button>
                            </div>
                        </div>

                        <!-- Expandable Details Section -->
                        <?php if (!empty($job['responsibilities']) || !empty($job['requirements']) || !empty($job['benefits'])): ?>
                            <div id="job-details-<?= (int)$job['id'] ?>" class="hidden mt-6 pt-6 border-t border-slate-100 text-xs text-slate-600 space-y-4">
                                <?php if (!empty($job['responsibilities'])): ?>
                                    <div>
                                        <h4 class="font-extrabold text-slate-900 mb-1.5 uppercase tracking-wider text-[11px]">Key Responsibilities:</h4>
                                        <div class="leading-relaxed whitespace-pre-line pl-1"><?= htmlspecialchars($job['responsibilities']) ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($job['requirements'])): ?>
                                    <div>
                                        <h4 class="font-extrabold text-slate-900 mb-1.5 uppercase tracking-wider text-[11px]">Requirements &amp; Qualifications:</h4>
                                        <div class="leading-relaxed whitespace-pre-line pl-1"><?= htmlspecialchars($job['requirements']) ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($job['benefits'])): ?>
                                    <div>
                                        <h4 class="font-extrabold text-slate-900 mb-1.5 uppercase tracking-wider text-[11px]">Perks &amp; Benefits:</h4>
                                        <div class="leading-relaxed whitespace-pre-line pl-1"><?= htmlspecialchars($job['benefits']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Job Application Modal Form -->
<div id="jobApplicationModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl relative my-8 border border-slate-100">
        <button type="button" onclick="closeApplicationModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 transition">
            <span class="material-symbols-outlined text-2xl">close</span>
        </button>

        <div class="mb-6">
            <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-extrabold uppercase tracking-wide">Job Application</span>
            <h3 class="text-2xl font-extrabold text-slate-900 mt-1 mb-1">Submit Your Application</h3>
            <p class="text-xs text-slate-500">Applying for: <strong id="modalPositionName" class="text-blue-900"></strong></p>
        </div>

        <form id="careerApplicationForm" action="<?= htmlspecialchars($baseUrl) ?>/careers/apply" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="job_opening_id" id="modalJobOpeningId" value="">

            <!-- Position Applied For (Dynamic Selector) -->
            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Position Applied For <span class="text-red-500">*</span></label>
                <select name="position_applied" id="modalPositionSelect" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                    <?php foreach ($activeJobs as $j): ?>
                        <option value="<?= htmlspecialchars($j['title']) ?>" data-id="<?= (int)$j['id'] ?>"><?= htmlspecialchars($j['title']) ?> (<?= htmlspecialchars($j['department']) ?>)</option>
                    <?php endforeach; ?>
                    <option value="General Application" data-id="">General Application (Open Match)</option>
                </select>
            </div>

            <!-- Full Name & Email -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($prefillName) ?>" placeholder="Your Full Name" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="name@domain.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
            </div>

            <!-- Mobile Phone & City -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" required value="<?= htmlspecialchars($prefillPhone) ?>" placeholder="+91 9876543210" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Current Location / City <span class="text-red-500">*</span></label>
                    <input type="text" name="city" required placeholder="e.g. New Delhi, Bengaluru, Jalandhar" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
            </div>

            <!-- Country & Experience -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Country <span class="text-red-500">*</span></label>
                    <select name="country_id" id="jobCountrySelect" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                        <option value="">Select Country *</option>
                        <?php foreach ($countriesList as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= ($prefillCountryId === (int)$c['id'] || (empty($prefillCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Total Work Experience <span class="text-red-500">*</span></label>
                    <select name="total_experience" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                        <option value="Fresher / Entry Level">Fresher / Entry Level (&lt; 1 Year)</option>
                        <option value="1-2 Years">1-2 Years</option>
                        <option value="3-5 Years" selected>3-5 Years</option>
                        <option value="5-8 Years">5-8 Years</option>
                        <option value="8+ Years">8+ Years</option>
                    </select>
                </div>
            </div>

            <!-- Current Company & Job Title -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Current Company</label>
                    <input type="text" name="current_company" placeholder="Present Employer (or Self-employed)" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Current Job Title</label>
                    <input type="text" name="current_job_title" placeholder="e.g. Software Engineer / Sourcing Exec" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
            </div>

            <!-- Expected Salary & Notice Period -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Expected Salary / CTC</label>
                    <input type="text" name="expected_salary" placeholder="e.g. ₹12 LPA or Negotiable" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Notice Period / Availability <span class="text-red-500">*</span></label>
                    <select name="notice_period" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none bg-white">
                        <option value="Immediate">Immediate Availability</option>
                        <option value="15 Days">15 Days</option>
                        <option value="30 Days" selected>30 Days</option>
                        <option value="60 Days">60 Days</option>
                        <option value="90 Days">90 Days</option>
                    </select>
                </div>
            </div>

            <!-- URLs: LinkedIn & Portfolio -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">LinkedIn Profile URL</label>
                    <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/username" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Portfolio / GitHub / Website</label>
                    <input type="url" name="portfolio_url" placeholder="https://yourportfolio.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
            </div>

            <!-- Resume Upload (Required) -->
            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Upload Resume (PDF, DOC, DOCX) <span class="text-red-500">*</span></label>
                <input type="file" name="resume" required accept=".pdf,.doc,.docx" class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1 bg-slate-50">
                <p class="text-[11px] text-slate-400 mt-1">Allowed formats: PDF, DOC, DOCX (Max 10MB)</p>
            </div>

            <!-- Cover Letter (Optional) -->
            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Cover Letter / Additional Information</label>
                <textarea name="cover_letter" rows="3" placeholder="Tell us why you are interested in Sports Junction and what unique strengths you bring..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
            </div>

            <!-- Privacy Policy Consent (Required) -->
            <div>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="consent_privacy" value="1" required checked class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs text-slate-600 leading-relaxed">
                        I agree to the processing of my candidate profile in accordance with the Sports Junction <a href="<?= htmlspecialchars($baseUrl) ?>/privacy-policy" target="_blank" class="text-blue-700 font-bold hover:underline">Privacy Policy</a>. <span class="text-red-500">*</span>
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeApplicationModal()" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs uppercase tracking-wider hover:bg-slate-100 transition">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitApplication" class="px-7 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-wider shadow-md transition flex items-center gap-2">
                    <span>Submit Application</span>
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleJobDetails(id) {
    const el = document.getElementById('job-details-' + id);
    if (!el) return;
    el.classList.toggle('hidden');
}

function openApplicationModal(positionTitle, openingId = '') {
    const modal = document.getElementById('jobApplicationModal');
    if (!modal) return;
    document.getElementById('modalPositionName').textContent = positionTitle;
    document.getElementById('modalJobOpeningId').value = openingId || '';

    const sel = document.getElementById('modalPositionSelect');
    if (sel) {
        let matched = false;
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === positionTitle) {
                sel.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched) {
            sel.value = 'General Application';
        }
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeApplicationModal() {
    const modal = document.getElementById('jobApplicationModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

document.getElementById('modalPositionSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('modalPositionName').textContent = this.value;
    document.getElementById('modalJobOpeningId').value = opt.dataset.id || '';
});

document.getElementById('careerApplicationForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitApplication');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Submitting...';

    try {
        const formData = new FormData(this);
        const sel = document.getElementById('jobCountrySelect');
        if (sel && sel.selectedIndex >= 0) {
            const opt = sel.options[sel.selectedIndex];
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
            closeApplicationModal();
            this.reset();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Application Received!',
                    html: `<p>${data.message || 'Thank you for applying!'}</p><p class="mt-2 text-xs text-slate-500">Application Reference: <strong>${data.application_no || ''}</strong></p>`,
                    confirmButtonColor: '#1e3a8a'
                });
            } else {
                alert(data.message || 'Application submitted successfully!');
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
            Swal.fire('Error', 'Network error while submitting your application.', 'error');
        } else {
            alert('Network error while submitting your application.');
        }
    }
});
</script>
