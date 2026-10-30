<?php
$baseUrl = \App\Core\Response::baseUrl();
$csrfToken = \App\Helpers\CSRF::generate();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-purple-50 text-purple-800 px-2 py-1 rounded-2 fw-bold" style="font-size: 11px; background-color: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe;">TALENT ACQUISITION &amp; JOBS</span>
        </div>
        <h3 class="fw-bold text-slate-900 mb-0">Careers &amp; Applications Management</h3>
        <p class="text-slate-500 small mb-0">Manage published career openings and review candidate applications, resumes, and hiring stages.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-primary btn-sm fw-semibold px-3 py-2 rounded-3 shadow-sm" onclick="openCreateJobModal()">
            <i class="bi bi-plus-circle me-1"></i> Post New Job Opening
        </button>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-semibold px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Candidates
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item py-2" href="#" onclick="exportCandidates('csv'); return false;"><i class="bi bi-filetype-csv text-success me-2"></i>Export as CSV</a></li>
                <li><a class="dropdown-item py-2" href="#" onclick="exportCandidates('excel'); return false;"><i class="bi bi-file-earmark-excel text-primary me-2"></i>Export as Excel (.xls)</a></li>
            </ul>
        </div>
        <a href="<?= $baseUrl ?>/careers" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 rounded-3">
            <i class="bi bi-box-arrow-up-right me-1"></i> Public Careers Page
        </a>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 gap-2" id="careersTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active px-4 py-2 rounded-3 fw-bold d-flex align-items-center gap-2" id="tab-applications" data-bs-toggle="pill" data-bs-target="#pane-applications" type="button" role="tab">
            <i class="bi bi-people-fill"></i>
            <span>Candidate Applications</span>
            <span class="badge bg-white text-dark rounded-pill ms-1" id="tabBadgeApplications"><?= (int)($metrics['total'] ?? 0) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-4 py-2 rounded-3 fw-bold d-flex align-items-center gap-2" id="tab-jobs" data-bs-toggle="pill" data-bs-target="#pane-jobs" type="button" role="tab">
            <i class="bi bi-briefcase-fill"></i>
            <span>Job Openings Directory</span>
            <span class="badge bg-secondary text-white rounded-pill ms-1" id="tabBadgeJobs"><?= count($jobs ?? []) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="careersTabContent">
    <!-- TAB 1: CANDIDATE APPLICATIONS -->
    <div class="tab-pane fade show active" id="pane-applications" role="tabpanel">
        <!-- KPI Metrics Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('ALL')">
                    <div class="text-slate-500 small fw-semibold">Total Applicants</div>
                    <h4 class="fw-bold text-slate-900 mb-0 mt-1" id="mTotal"><?= (int)($metrics['total'] ?? 0) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('NEW')">
                    <div class="text-slate-500 small fw-semibold">New / Unscreened</div>
                    <h4 class="fw-bold text-warning mb-0 mt-1" style="color: #b45309 !important;" id="mNew"><?= (int)($metrics['new'] ?? 0) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('SHORTLISTED')">
                    <div class="text-slate-500 small fw-semibold">Shortlisted</div>
                    <h4 class="fw-bold text-primary mb-0 mt-1" style="color: #0284c7 !important;" id="mShortlisted"><?= (int)($metrics['shortlisted'] ?? 0) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('INTERVIEW_SCHEDULED')">
                    <div class="text-slate-500 small fw-semibold">Interviewing</div>
                    <h4 class="fw-bold text-purple mb-0 mt-1" style="color: #9333ea !important;" id="mInterview"><?= (int)($metrics['interview_scheduled'] ?? 0) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('SELECTED')">
                    <div class="text-slate-500 small fw-semibold">Selected / Hired</div>
                    <h4 class="fw-bold text-success mb-0 mt-1" style="color: #16a34a !important;" id="mSelected"><?= (int)($metrics['selected'] ?? 0) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card h-100 p-3" style="cursor: pointer;" onclick="setStatusFilter('REJECTED')">
                    <div class="text-slate-500 small fw-semibold">Archived / Rejected</div>
                    <h4 class="fw-bold text-secondary mb-0 mt-1" style="color: #64748b !important;" id="mRejected"><?= (int)($metrics['rejected'] ?? 0) ?></h4>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card card-premium mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-end">
                    <!-- Position Filter -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-slate-600 mb-1">Position Applied</label>
                        <select id="filterPosition" class="form-select form-select-sm">
                            <option value="ALL">All Positions &amp; Roles</option>
                            <option value="General Application">General Application</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?= htmlspecialchars($job['title']) ?>"><?= htmlspecialchars($job['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-slate-600 mb-1">Hiring Stage</label>
                        <select id="filterStatus" class="form-select form-select-sm">
                            <option value="ALL">All Stages</option>
                            <option value="NEW">New</option>
                            <option value="SHORTLISTED">Shortlisted</option>
                            <option value="INTERVIEW_SCHEDULED">Interview Scheduled</option>
                            <option value="SELECTED">Selected / Offered</option>
                            <option value="REJECTED">Rejected</option>
                        </select>
                    </div>

                    <!-- Experience Filter -->
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-slate-600 mb-1">Experience</label>
                        <select id="filterExperience" class="form-select form-select-sm">
                            <option value="ALL">All Experience Levels</option>
                            <option value="Fresher / Less than 1 Year">Fresher / &lt; 1 Year</option>
                            <option value="1-3 Years">1 - 3 Years</option>
                            <option value="3-5 Years">3 - 5 Years</option>
                            <option value="5-8 Years">5 - 8 Years</option>
                            <option value="8+ Years">8+ Years</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-slate-600 mb-1">Applied Date</label>
                        <div class="input-group input-group-sm">
                            <input type="date" id="filterStartDate" class="form-control" title="Start Date">
                            <span class="input-group-text bg-white text-muted">to</span>
                            <input type="date" id="filterEndDate" class="form-control" title="End Date">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-md-2 d-flex gap-2">
                        <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Table Card -->
        <div class="card card-premium">
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="applicationsTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th>App #</th>
                                <th>Candidate Details</th>
                                <th>Position Applied</th>
                                <th>Experience</th>
                                <th>Location</th>
                                <th>Current Employment</th>
                                <th>Notice / CTC</th>
                                <th>Resume</th>
                                <th>Applied Date</th>
                                <th>Stage</th>
                                <th class="text-end" style="min-width:110px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: JOB OPENINGS DIRECTORY -->
    <div class="tab-pane fade" id="pane-jobs" role="tabpanel">
        <div class="card card-premium">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-slate-900 mb-0">Active &amp; Past Job Postings</h5>
                    <p class="text-slate-500 small mb-0">Create, update, reorder or close job openings displayed on the public website.</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm fw-semibold px-3 py-2 rounded-3 shadow-sm" onclick="openCreateJobModal()">
                    <i class="bi bi-plus-circle me-1"></i> Add New Opening
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="jobsTable" class="table table-hover align-middle w-100 mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">Order</th>
                                <th>Role Title &amp; Department</th>
                                <th>Location &amp; Type</th>
                                <th>Experience Req.</th>
                                <th>Applications</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th class="text-end" style="min-width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="jobsTableBody">
                            <?php if (empty($jobs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No job openings created yet. Click "+ Add New Opening" to post one.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($jobs as $j): ?>
                                    <tr id="jobRow<?= (int)$j['id'] ?>">
                                        <td><span class="badge bg-light text-dark border"><?= (int)$j['sort_order'] ?></span></td>
                                        <td>
                                            <div class="fw-bold text-slate-900"><?= htmlspecialchars($j['title']) ?></div>
                                            <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($j['department']) ?></small>
                                        </td>
                                        <td>
                                            <div><i class="bi bi-geo-alt me-1 text-slate-400"></i><?= htmlspecialchars($j['location']) ?></div>
                                            <small class="badge bg-slate-100 text-slate-700 mt-1"><?= htmlspecialchars($j['employment_type']) ?></small>
                                        </td>
                                        <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><?= htmlspecialchars($j['experience_level']) ?></span></td>
                                        <td>
                                            <a href="#" onclick="filterByJob(<?= json_encode($j['title']) ?>); return false;" class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-bold text-decoration-none px-2 py-1">
                                                <i class="bi bi-person-fill me-1"></i><?= (int)($j['application_count'] ?? 0) ?> Applicants
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (($j['status'] ?? 'ACTIVE') === 'ACTIVE'): ?>
                                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25">ACTIVE</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border">CLOSED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars(substr($j['updated_at'] ?? $j['created_at'] ?? '', 0, 10)) ?></small></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary" title="Edit Opening" onclick="openEditJobModal(<?= htmlspecialchars(json_encode($j), ENT_QUOTES) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-<?= ($j['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'warning' : 'success' ?>" title="Toggle Active/Closed" onclick="toggleJobStatus(<?= (int)$j['id'] ?>)">
                                                    <i class="bi bi-power"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" title="Delete Opening" onclick="deleteJob(<?= (int)$j['id'] ?>, <?= json_encode($j['title']) ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Candidate Details & Interview Evaluation Modal -->
<div class="modal fade" id="candidateDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-slate-900" id="cdModalTitle">Candidate Application</h5>
                    <small class="text-slate-500" id="cdModalAppNo"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 space-y-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Candidate Full Name</label>
                        <div class="fw-bold text-slate-900 fs-6" id="cdFullName">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Position Applied</label>
                        <div><span class="badge bg-purple-50 text-purple-800 border border-purple-200 fs-7" id="cdPositionApplied">-</span></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Email Address</label>
                        <div id="cdEmail">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Phone / Mobile</label>
                        <div id="cdMobile">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Location / City &amp; Country</label>
                        <div id="cdLocation">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Total Work Experience</label>
                        <div class="fw-semibold text-slate-800" id="cdExperience">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Current Employment</label>
                        <div id="cdCurrentRole">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs text-muted fw-bold uppercase">Expected Salary / Notice Period</label>
                        <div id="cdNoticeAndSalary">-</div>
                    </div>
                    <div class="col-md-6" id="cdLinkedInRow">
                        <label class="text-xs text-muted fw-bold uppercase">LinkedIn Profile</label>
                        <div id="cdLinkedIn">-</div>
                    </div>
                    <div class="col-md-6" id="cdPortfolioRow">
                        <label class="text-xs text-muted fw-bold uppercase">Portfolio / Website</label>
                        <div id="cdPortfolio">-</div>
                    </div>
                </div>

                <!-- Cover Letter -->
                <div class="border-top pt-3" id="cdCoverLetterSection">
                    <label class="text-xs text-muted fw-bold uppercase">Cover Letter / Candidate Notes</label>
                    <div class="p-3 bg-slate-50 rounded-3 border text-slate-700 text-sm whitespace-pre-wrap" id="cdCoverLetter">-</div>
                </div>

                <!-- Resume Download Box -->
                <div class="border-top pt-3">
                    <label class="text-xs text-muted fw-bold uppercase mb-2 d-block">Candidate Resume / CV</label>
                    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                            <div>
                                <div class="fw-bold text-slate-900" id="cdResumeName">Resume.pdf</div>
                                <small class="text-muted" id="cdResumeSize">-</small>
                            </div>
                        </div>
                        <a id="cdResumeDownloadBtn" href="#" target="_blank" class="btn btn-sm btn-outline-primary fw-bold px-3">
                            <i class="bi bi-download me-1"></i> Download Resume
                        </a>
                    </div>
                </div>

                <!-- Hiring Stage & Admin Interview Notes -->
                <div class="border-top pt-3 bg-slate-50 p-3 rounded-3 border">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-slate-700">Update Hiring Stage</label>
                            <select id="cdStatusSelect" class="form-select form-select-sm">
                                <option value="NEW">New</option>
                                <option value="SHORTLISTED">Shortlisted</option>
                                <option value="INTERVIEW_SCHEDULED">Interview Scheduled</option>
                                <option value="SELECTED">Selected / Offered</option>
                                <option value="REJECTED">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-slate-700">Internal Evaluation &amp; Interview Notes</label>
                            <textarea id="cdAdminNotes" rows="3" class="form-control form-control-sm" placeholder="Interview score, panel feedback, offer details, or next round notes..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="saveCandidateModalChanges()">Save Application Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Create / Edit Job Opening Modal -->
<div class="modal fade" id="jobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="jobForm" onsubmit="saveJobOpening(event)">
                <input type="hidden" name="id" id="jobId" value="0">
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold mb-0 text-slate-900" id="jobModalTitle">Post New Job Opening</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 space-y-3">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-slate-700">Job Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="jobTitle" class="form-control" placeholder="e.g. Senior Sourcing Specialist" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-slate-700">Department</label>
                            <input type="text" name="department" id="jobDepartment" class="form-control" placeholder="e.g. Sourcing, Operations, Tech">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-slate-700">Location</label>
                            <input type="text" name="location" id="jobLocation" class="form-control" placeholder="e.g. New Delhi, India / Remote" value="New Delhi, India">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-slate-700">Employment Type</label>
                            <select name="employment_type" id="jobEmploymentType" class="form-select">
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Contract">Contract</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-slate-700">Experience Level</label>
                            <select name="experience_level" id="jobExperienceLevel" class="form-select">
                                <option value="Fresher / 0-1 Years">Fresher / 0-1 Years</option>
                                <option value="1-3 Years" selected>1-3 Years</option>
                                <option value="3-5 Years">3-5 Years</option>
                                <option value="5-8 Years">5-8 Years</option>
                                <option value="8+ Years">8+ Years</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-bold text-slate-700">Role Overview / Summary</label>
                        <textarea name="overview" id="jobOverview" rows="3" class="form-control" placeholder="Brief summary of the role, team, and company mission..."></textarea>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-bold text-slate-700">Key Responsibilities (One per line)</label>
                        <textarea name="responsibilities" id="jobResponsibilities" rows="3" class="form-control" placeholder="- Drive supplier onboarding and verification&#10;- Facilitate RFQ quotations between international buyers and Indian factories"></textarea>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-bold text-slate-700">Skills &amp; Requirements (One per line)</label>
                        <textarea name="requirements" id="jobRequirements" rows="3" class="form-control" placeholder="- Bachelor's degree or equivalent experience&#10;- Strong knowledge of sports goods manufacturing standards"></textarea>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-bold text-slate-700">Benefits &amp; Perks</label>
                        <textarea name="benefits" id="jobBenefits" rows="2" class="form-control" placeholder="Competitive compensation, health insurance, flexible work arrangements..."></textarea>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-slate-700">Publish Status</label>
                            <select name="status" id="jobStatus" class="form-select">
                                <option value="ACTIVE">ACTIVE (Visible on public careers page)</option>
                                <option value="CLOSED">CLOSED (Archived / Not accepting applications)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-slate-700">Display Order</label>
                            <input type="number" name="sort_order" id="jobSortOrder" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveJob" class="btn btn-primary btn-sm px-4 fw-bold">Publish Opening</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
let appTable;
let currentCandidateId = 0;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function renderStageBadge(st) {
    const map = {
        'NEW':                 { bg: '#fef3c7', color: '#b45309', label: 'New' },
        'SHORTLISTED':         { bg: '#e0f2fe', color: '#0369a1', label: 'Shortlisted' },
        'INTERVIEW_SCHEDULED': { bg: '#f3e8ff', color: '#6b21a8', label: 'Interviewing' },
        'SELECTED':            { bg: '#d1fae5', color: '#065f46', label: 'Selected' },
        'REJECTED':            { bg: '#f1f5f9', color: '#475569', label: 'Rejected' },
    };
    const cfg = map[st] || { bg: '#f1f5f9', color: '#475569', label: st || 'New' };
    return `<span class="badge rounded-pill fw-semibold" style="background:${cfg.bg};color:${cfg.color};">${escapeHtml(cfg.label)}</span>`;
}

function setStatusFilter(st) {
    $('#filterStatus').val(st).trigger('change');
}

function filterByJob(jobTitle) {
    const tabEl = document.querySelector('#tab-applications');
    const tab = new bootstrap.Tab(tabEl);
    tab.show();
    $('#filterPosition').val(jobTitle).trigger('change');
}

$(document).ready(function() {
    appTable = $('#applicationsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 15,
        order: [[8, 'desc']],
        ajax: {
            url: BASE_URL + '/api/admin/careers/applications/data',
            data: function(d) {
                d.position = $('#filterPosition').val();
                d.status = $('#filterStatus').val();
                d.experience = $('#filterExperience').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            {
                data: 'application_no',
                render: function(d, t, row) {
                    return `<span class="fw-bold font-monospace text-primary">${escapeHtml(d || ('APP-' + row.id))}</span>`;
                }
            },
            {
                data: 'full_name',
                render: function(d, t, row) {
                    const name = d || row.name || '';
                    let html = `<div class="fw-bold text-slate-900">${escapeHtml(name)}</div>`;
                    html += `<small class="text-slate-500 d-block"><i class="bi bi-envelope me-1"></i>${escapeHtml(row.email)}</small>`;
                    if (row.phone) {
                        html += `<small class="text-slate-500 d-block"><i class="bi bi-telephone me-1"></i>${escapeHtml(row.phone)}</small>`;
                    }
                    return html;
                }
            },
            {
                data: 'position_applied',
                render: function(d) {
                    return `<span class="badge bg-purple-50 text-purple-800 border border-purple-200" style="background:#f3e8ff;color:#6b21a8;">${escapeHtml(d || 'General Application')}</span>`;
                }
            },
            {
                data: 'total_experience',
                render: function(d) {
                    return `<span class="badge bg-light text-dark border">${escapeHtml(d || 'Fresher')}</span>`;
                }
            },
            {
                data: 'city',
                render: function(d, t, row) {
                    const country = row.country_name || '';
                    return `<div><i class="bi bi-geo-alt me-1 text-muted"></i>${escapeHtml(d || '')}${country ? ', ' + escapeHtml(country) : ''}</div>`;
                }
            },
            {
                data: 'current_company',
                render: function(d, t, row) {
                    const title = row.current_job_title || row.current_title || '';
                    if (!d && !title) return '<span class="text-muted small">Not Specified</span>';
                    let titleHtml = title ? escapeHtml(title) : '';
                    let comp = d ? escapeHtml(d) : '';
                    return `<div>${titleHtml ? '<strong>' + titleHtml + '</strong><br>' : ''}<small class="text-muted">${comp}</small></div>`;
                }
            },
            {
                data: 'notice_period',
                render: function(d, t, row) {
                    let out = `<small class="d-block text-slate-700">Notice: <strong>${escapeHtml(d || 'Immediate')}</strong></small>`;
                    if (row.expected_salary) {
                        out += `<small class="d-block text-muted">CTC: ${escapeHtml(row.expected_salary)}</small>`;
                    }
                    return out;
                }
            },
            {
                data: 'resume_path',
                orderable: false,
                render: function(d, t, row) {
                    if (!d) return '<span class="text-muted small">No Resume</span>';
                    return `<a href="${BASE_URL}/admin/careers/resume/${row.id}" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 fw-semibold" title="Download Resume">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Resume
                    </a>`;
                }
            },
            {
                data: 'created_at',
                render: function(d) {
                    if (!d) return '-';
                    return `<small class="text-muted">${escapeHtml(d.substring(0, 16))}</small>`;
                }
            },
            {
                data: 'status',
                render: function(d) {
                    return renderStageBadge(d);
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: function(d, t, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" title="View Application" onclick="viewCandidate(${row.id})">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            emptyTable: "No candidate applications found.",
            search: "_INPUT_",
            searchPlaceholder: "Search candidates by name, email, phone, city..."
        }
    });

    $('#filterPosition, #filterStatus, #filterExperience, #filterStartDate, #filterEndDate').on('change', function() {
        appTable.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#filterPosition').val('ALL');
        $('#filterStatus').val('ALL');
        $('#filterExperience').val('ALL');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        appTable.search('').ajax.reload();
    });
});

function exportCandidates(format) {
    const pos = $('#filterPosition').val();
    const st = $('#filterStatus').val();
    const start = $('#filterStartDate').val();
    const end = $('#filterEndDate').val();
    const search = appTable.search();

    let url = `${BASE_URL}/api/admin/careers/applications/export?format=${format}&position=${encodeURIComponent(pos)}&status=${encodeURIComponent(st)}&start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&search=${encodeURIComponent(search)}`;
    window.location.href = url;
}

function viewCandidate(id) {
    currentCandidateId = id;
    $.ajax({
        url: `${BASE_URL}/api/admin/careers/applications/${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (!res.success || !res.application) {
                alert('Could not load application details.');
                return;
            }
            const a = res.application;
            $('#cdModalAppNo').text(a.application_no || `APP-${a.id}`);
            $('#cdFullName').text(a.full_name || a.name || '-');
            $('#cdPositionApplied').text(a.position_applied || 'General Application');
            $('#cdEmail').html(`<a href="mailto:${escapeHtml(a.email)}" class="text-decoration-none text-primary">${escapeHtml(a.email)}</a>`);
            $('#cdMobile').html(a.phone ? `<a href="tel:${escapeHtml(a.phone)}" class="text-decoration-none text-slate-800">${escapeHtml(a.phone)}</a>` : '<span class="text-muted">Not provided</span>');
            
            let loc = a.city || '';
            if (a.country_name) loc += (loc ? ', ' : '') + a.country_name;
            $('#cdLocation').text(loc || '-');
            $('#cdExperience').text(a.total_experience || '-');

            let roleStr = '';
            const currTitle = a.current_job_title || a.current_title || '';
            if (currTitle) roleStr += currTitle;
            if (a.current_company) roleStr += (roleStr ? ' at ' : '') + a.current_company;
            $('#cdCurrentRole').text(roleStr || 'Not currently employed');

            let noticeStr = a.notice_period || 'Immediate';
            if (a.expected_salary) noticeStr += ` | Expected: ${a.expected_salary}`;
            $('#cdNoticeAndSalary').text(noticeStr);

            const linkedin = a.linkedin_url || a.linkedin_profile || '';
            if (linkedin) {
                $('#cdLinkedIn').html(`<a href="${escapeHtml(linkedin)}" target="_blank" class="text-primary text-decoration-none"><i class="bi bi-linkedin me-1"></i>${escapeHtml(linkedin)}</a>`);
                $('#cdLinkedInRow').removeClass('d-none');
            } else {
                $('#cdLinkedInRow').addClass('d-none');
            }

            if (a.portfolio_url) {
                $('#cdPortfolio').html(`<a href="${escapeHtml(a.portfolio_url)}" target="_blank" class="text-primary text-decoration-none"><i class="bi bi-globe me-1"></i>${escapeHtml(a.portfolio_url)}</a>`);
                $('#cdPortfolioRow').removeClass('d-none');
            } else {
                $('#cdPortfolioRow').addClass('d-none');
            }

            if (a.cover_letter) {
                $('#cdCoverLetter').text(a.cover_letter);
                $('#cdCoverLetterSection').removeClass('d-none');
            } else {
                $('#cdCoverLetterSection').addClass('d-none');
            }

            if (a.resume_path) {
                $('#cdResumeName').text(a.resume_name || 'Candidate_Resume.pdf');
                let sz = a.resume_size ? ` (${(a.resume_size / 1024).toFixed(1)} KB)` : '';
                $('#cdResumeSize').text((a.resume_mime || 'PDF Document') + sz);
                $('#cdResumeDownloadBtn').attr('href', `${BASE_URL}/admin/careers/resume/${a.id}`).removeClass('d-none');
            } else {
                $('#cdResumeName').text('No resume uploaded');
                $('#cdResumeSize').text('-');
                $('#cdResumeDownloadBtn').addClass('d-none');
            }

            $('#cdStatusSelect').val(a.status || 'NEW');
            $('#cdAdminNotes').val(a.admin_notes || '');

            const modal = new bootstrap.Modal(document.getElementById('candidateDetailModal'));
            modal.show();
        },
        error: function() {
            alert('Failed to connect to the server.');
        }
    });
}

function saveCandidateModalChanges() {
    if (!currentCandidateId) return;

    const newStatus = $('#cdStatusSelect').val();
    const adminNotes = $('#cdAdminNotes').val();

    // 1. Update Status
    $.ajax({
        url: `${BASE_URL}/api/admin/careers/applications/${currentCandidateId}/status`,
        type: 'POST',
        data: {
            csrf_token: CSRF_TOKEN,
            status: newStatus
        },
        dataType: 'json',
        success: function(res) {
            if (!res.success) {
                alert(res.message || 'Failed to update status.');
                return;
            }

            // Update KPI metric numbers on screen
            if (res.metrics) {
                $('#mTotal').text(res.metrics.total || 0);
                $('#mNew').text(res.metrics.new || 0);
                $('#mShortlisted').text(res.metrics.shortlisted || 0);
                $('#mInterview').text(res.metrics.interview_scheduled || 0);
                $('#mSelected').text(res.metrics.selected || 0);
                $('#mRejected').text(res.metrics.rejected || 0);
            }

            // 2. Update Notes
            $.ajax({
                url: `${BASE_URL}/api/admin/careers/applications/${currentCandidateId}/notes`,
                type: 'POST',
                data: {
                    csrf_token: CSRF_TOKEN,
                    admin_notes: adminNotes
                },
                dataType: 'json',
                success: function() {
                    const modalEl = document.getElementById('candidateDetailModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    appTable.ajax.reload(null, false);
                }
            });
        },
        error: function() {
            alert('Failed to save application updates.');
        }
    });
}

// --- JOB OPENINGS MANAGEMENT ---

function openCreateJobModal() {
    $('#jobForm')[0].reset();
    $('#jobId').val(0);
    $('#jobModalTitle').text('Post New Job Opening');
    $('#btnSaveJob').text('Publish Opening');
    const modal = new bootstrap.Modal(document.getElementById('jobModal'));
    modal.show();
}

function openEditJobModal(job) {
    $('#jobId').val(job.id);
    $('#jobTitle').val(job.title || '');
    $('#jobDepartment').val(job.department || '');
    $('#jobLocation').val(job.location || '');
    $('#jobEmploymentType').val(job.employment_type || 'Full-time');
    $('#jobExperienceLevel').val(job.experience_level || '1-3 Years');
    $('#jobOverview').val(job.overview || '');
    $('#jobResponsibilities').val(job.responsibilities || '');
    $('#jobRequirements').val(job.requirements || '');
    $('#jobBenefits').val(job.benefits || '');
    $('#jobStatus').val(job.status || 'ACTIVE');
    $('#jobSortOrder').val(job.sort_order || 0);

    $('#jobModalTitle').text('Edit Job Opening: ' + job.title);
    $('#btnSaveJob').text('Save Changes');
    const modal = new bootstrap.Modal(document.getElementById('jobModal'));
    modal.show();
}

function saveJobOpening(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('jobForm'));
    formData.append('csrf_token', CSRF_TOKEN);

    $.ajax({
        url: `${BASE_URL}/api/admin/careers/jobs`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                const modalEl = document.getElementById('jobModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                location.reload();
            } else {
                alert(res.message || 'Failed to save job opening.');
            }
        },
        error: function(xhr) {
            let msg = 'Failed to save job opening.';
            try {
                const json = JSON.parse(xhr.responseText);
                if (json.message) msg = json.message;
            } catch (err) {}
            alert(msg);
        }
    });
}

function toggleJobStatus(id) {
    if (!confirm('Are you sure you want to toggle the publish status of this job opening?')) return;

    $.ajax({
        url: `${BASE_URL}/api/admin/careers/jobs/${id}/toggle-status`,
        type: 'POST',
        data: { csrf_token: CSRF_TOKEN },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Failed to update job status.');
            }
        },
        error: function() {
            alert('Server error while toggling job status.');
        }
    });
}

function deleteJob(id, title) {
    if (!confirm(`Are you sure you want to permanently delete the job opening "${title}"? This cannot be undone.`)) return;

    $.ajax({
        url: `${BASE_URL}/api/admin/careers/jobs/${id}/delete`,
        type: 'POST',
        data: { csrf_token: CSRF_TOKEN },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $(`#jobRow${id}`).remove();
                location.reload();
            } else {
                alert(res.message || 'Failed to delete job opening.');
            }
        },
        error: function() {
            alert('Server error while deleting job opening.');
        }
    });
}
</script>
<?php
$extraScripts = ob_get_clean();
?>
