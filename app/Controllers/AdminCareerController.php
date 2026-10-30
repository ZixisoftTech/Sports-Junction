<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Exporter;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Throwable;

class AdminCareerController extends Controller
{
    public function index(): void
    {
        $metrics = JobApplication::getAdminMetrics();
        $jobs    = JobOpening::allForAdmin();

        $this->render('admin/careers', [
            'pageTitle'  => 'Careers & Applications Management — Sports Junction Admin',
            'metrics'    => $metrics,
            'jobs'       => $jobs,
            'activeMenu' => 'careers',
        ], 'admin');
    }

    public function applicationsData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'position'       => $this->request->get('position', 'ALL'),
            'job_opening_id' => $this->request->get('job_opening_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'experience'     => $this->request->get('experience', 'ALL'),
            'city'           => $this->request->get('city', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
        ];

        $result = JobApplication::getAdminDataTable($params);
        $this->json($result);
    }

    public function showApplication(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $app = JobApplication::findById((int)$id);

        if (!$app) {
            $this->json(['success' => false, 'message' => 'Application not found.'], 404);
            return;
        }

        $this->json([
            'success'     => true,
            'application' => $app,
        ]);
    }

    public function updateApplicationStatus(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $appId = (int)$id;
        $app = JobApplication::findById($appId);
        if (!$app) {
            $this->json(['success' => false, 'message' => 'Application not found.'], 404);
            return;
        }

        $status = strtoupper(trim((string)$this->request->post('status', '')));
        if (!JobApplication::updateStatus($appId, $status)) {
            $this->json(['success' => false, 'message' => 'Invalid application status value.'], 422);
            return;
        }

        AuditLogger::log(
            'CAREER_STATUS_CHANGE',
            'JobApplication',
            $appId,
            "Candidate application status changed to {$status}",
            ['status' => $app['status']],
            ['status' => $status]
        );

        $this->json([
            'success' => true,
            'status'  => $status,
            'metrics' => JobApplication::getAdminMetrics(),
            'message' => "Candidate status updated to {$status}.",
        ]);
    }

    public function updateApplicationNotes(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $appId = (int)$id;
        $app = JobApplication::findById($appId);
        if (!$app) {
            $this->json(['success' => false, 'message' => 'Application not found.'], 404);
            return;
        }

        $notes = (string) $this->request->post('admin_notes', '');
        JobApplication::updateAdminNotes($appId, $notes);

        AuditLogger::log('CAREER_UPDATE_NOTES', 'JobApplication', $appId, 'Updated internal candidate interview notes');

        $this->json([
            'success' => true,
            'message' => 'Candidate internal review notes saved successfully.',
        ]);
    }

    public function exportApplications(): void
    {
        $format = strtolower((string) $this->request->get('format', 'csv'));
        $params = [
            'position'     => $this->request->get('position', 'ALL'),
            'status'       => $this->request->get('status', 'ALL'),
            'start_date'   => $this->request->get('start_date', ''),
            'end_date'     => $this->request->get('end_date', ''),
            'search_query' => $this->request->get('search', ''),
        ];

        $rows = JobApplication::getAdminExportData($params);
        $headers = [
            'Application No',
            'Position Applied',
            'Full Name',
            'Email Address',
            'Phone Number',
            'City / Location',
            'Country',
            'Total Experience',
            'Current Company',
            'Current Title',
            'Expected CTC',
            'Notice Period',
            'LinkedIn Profile',
            'Portfolio URL',
            'Application Status',
            'Applied Date',
        ];

        AuditLogger::log('EXPORT', 'JobApplication', null, "Exported " . count($rows) . " candidate applications as " . strtoupper($format));

        $filename = 'sports_junction_candidates_' . date('Ymd_His');
        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }

    /**
     * Secure Resume Download (Super Admin Protected)
     * Do not expose candidate resumes or personal information publicly.
     */
    public function downloadResume(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $appId = (int)$id;

        $authUser = $this->authUser();
        if (!$authUser || ($authUser['role'] ?? '') !== 'SUPER_ADMIN') {
            http_response_code(403);
            echo "Forbidden: Super Admin authentication required to access candidate resumes.";
            exit;
        }

        $app = JobApplication::findById($appId);
        if (!$app || empty($app['resume_path'])) {
            http_response_code(404);
            echo "Resume record not found.";
            exit;
        }

        $baseDir = dirname(__DIR__, 2);
        $fullPath = $baseDir . '/' . ltrim($app['resume_path'], '/');

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            http_response_code(404);
            echo "Resume file not found on storage server.";
            exit;
        }

        $filename = !empty($app['resume_name']) ? $app['resume_name'] : basename($fullPath);
        $mime = !empty($app['resume_mime']) ? $app['resume_mime'] : 'application/pdf';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    // --- Job Openings Management (Super Admin) ---

    public function jobsData(): void
    {
        $jobs = JobOpening::allForAdmin();
        $this->json(['success' => true, 'data' => $jobs]);
    }

    public function saveJob(): void
    {
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $id = (int)$this->request->post('id', 0);
        $title = trim((string)$this->request->post('title', ''));
        if (strlen($title) < 3) {
            $this->json(['success' => false, 'message' => 'Job title is required (at least 3 characters).'], 422);
            return;
        }

        $data = [
            'title'            => $title,
            'department'       => trim((string)$this->request->post('department', 'General')),
            'location'         => trim((string)$this->request->post('location', 'Remote')),
            'employment_type'  => trim((string)$this->request->post('employment_type', 'Full-time')),
            'experience_level' => trim((string)$this->request->post('experience_level', '1-3 Years')),
            'overview'         => trim((string)$this->request->post('overview', '')),
            'responsibilities' => trim((string)$this->request->post('responsibilities', '')),
            'requirements'     => trim((string)$this->request->post('requirements', '')),
            'benefits'         => trim((string)$this->request->post('benefits', '')),
            'status'           => strtoupper(trim((string)$this->request->post('status', 'ACTIVE'))),
            'sort_order'       => (int)$this->request->post('sort_order', 0),
        ];

        if ($id > 0) {
            JobOpening::update($id, $data);
            AuditLogger::log('JOB_UPDATE', 'JobOpening', $id, "Updated job opening: {$title}");
            $this->json(['success' => true, 'message' => 'Job opening updated successfully.']);
        } else {
            $newId = JobOpening::create($data);
            AuditLogger::log('JOB_CREATE', 'JobOpening', $newId, "Created new job opening: {$title}");
            $this->json(['success' => true, 'id' => $newId, 'message' => 'Job opening published successfully.']);
        }
    }

    public function deleteJob(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $jobId = (int)$id;
        $job = JobOpening::findById($jobId);
        if (!$job) {
            $this->json(['success' => false, 'message' => 'Job opening not found.'], 404);
            return;
        }

        JobOpening::delete($jobId);
        AuditLogger::log('JOB_DELETE', 'JobOpening', $jobId, "Deleted job opening: " . ($job['title'] ?? ''));

        $this->json(['success' => true, 'message' => 'Job opening deleted successfully.']);
    }

    public function toggleJobStatus(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $jobId = (int)$id;
        if (!JobOpening::toggleStatus($jobId)) {
            $this->json(['success' => false, 'message' => 'Job opening not found.'], 404);
            return;
        }

        $job = JobOpening::findById($jobId);
        AuditLogger::log('JOB_TOGGLE_STATUS', 'JobOpening', $jobId, "Toggled status to " . ($job['status'] ?? ''));

        $this->json([
            'success' => true,
            'status'  => $job['status'] ?? 'ACTIVE',
            'message' => "Job status updated to " . ($job['status'] ?? 'ACTIVE') . ".",
        ]);
    }
}
