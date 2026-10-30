<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Exporter;
use App\Helpers\Session;
use App\Models\SiteFeedback;
use Throwable;

class AdminFeedbackController extends Controller
{
    public function index(): void
    {
        $metrics = SiteFeedback::getAdminMetrics();
        $types   = SiteFeedback::VALID_TYPES;

        $this->render('admin/feedbacks', [
            'pageTitle'  => 'Suggestions & Feedback Management — Sports Junction Admin',
            'metrics'    => $metrics,
            'types'      => $types,
            'activeMenu' => 'feedbacks',
        ], 'admin');
    }

    public function data(): void
    {
        $params = [
            'draw'          => $this->request->get('draw', 1),
            'start'         => $this->request->get('start', 0),
            'length'        => $this->request->get('length', 15),
            'search'        => $this->request->get('search', []),
            'order'         => $this->request->get('order', []),
            'feedback_type' => $this->request->get('feedback_type', 'ALL'),
            'status'        => $this->request->get('status', 'ALL'),
            'start_date'    => $this->request->get('start_date', ''),
            'end_date'      => $this->request->get('end_date', ''),
        ];

        $result = SiteFeedback::getAdminDataTable($params);
        $this->json($result);
    }

    public function show(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $feedback = SiteFeedback::findById((int)$id);

        if (!$feedback) {
            $this->json(['success' => false, 'message' => 'Feedback record not found.'], 404);
            return;
        }

        $this->json([
            'success'  => true,
            'feedback' => $feedback,
        ]);
    }

    public function updateStatus(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $feedbackId = (int)$id;
        $feedback = SiteFeedback::findById($feedbackId);
        if (!$feedback) {
            $this->json(['success' => false, 'message' => 'Feedback not found.'], 404);
            return;
        }

        $status = strtoupper(trim((string)$this->request->post('status', '')));
        if (!SiteFeedback::updateStatus($feedbackId, $status)) {
            $this->json(['success' => false, 'message' => 'Invalid status value.'], 422);
            return;
        }

        AuditLogger::log(
            'FEEDBACK_STATUS_CHANGE',
            'SiteFeedback',
            $feedbackId,
            "Feedback status changed to {$status}",
            ['status' => $feedback['status']],
            ['status' => $status]
        );

        $this->json([
            'success' => true,
            'status'  => $status,
            'metrics' => SiteFeedback::getAdminMetrics(),
            'message' => "Feedback status updated to {$status}.",
        ]);
    }

    public function updateNotes(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $feedbackId = (int)$id;
        $feedback = SiteFeedback::findById($feedbackId);
        if (!$feedback) {
            $this->json(['success' => false, 'message' => 'Feedback not found.'], 404);
            return;
        }

        $notes = (string) $this->request->post('admin_notes', '');
        SiteFeedback::updateAdminNotes($feedbackId, $notes);

        AuditLogger::log('FEEDBACK_UPDATE_NOTES', 'SiteFeedback', $feedbackId, 'Updated internal feedback review notes');

        $this->json([
            'success' => true,
            'message' => 'Internal review notes saved successfully.',
        ]);
    }

    public function export(): void
    {
        $format = strtolower((string) $this->request->get('format', 'csv'));
        $params = [
            'feedback_type' => $this->request->get('feedback_type', 'ALL'),
            'status'        => $this->request->get('status', 'ALL'),
            'start_date'    => $this->request->get('start_date', ''),
            'end_date'      => $this->request->get('end_date', ''),
            'search_query'  => $this->request->get('search', ''),
        ];

        $rows = SiteFeedback::getAdminExportData($params);
        $headers = [
            'Reference ID',
            'Full Name',
            'Company / Organization',
            'Email Address',
            'Phone / WhatsApp',
            'Country',
            'Feedback Type',
            'Subject',
            'Feedback / Suggestion',
            'Has Attachment',
            'Status',
            'Submission Date',
        ];

        AuditLogger::log('EXPORT', 'SiteFeedback', null, "Exported " . count($rows) . " feedback submissions as " . strtoupper($format));

        $filename = 'sports_junction_feedback_' . date('Ymd_His');
        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }

    /**
     * Secure Attachment Download (Super Admin Protected)
     */
    public function downloadAttachment(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $feedbackId = (int)$id;

        $authUser = $this->authUser();
        if (!$authUser || ($authUser['role'] ?? '') !== 'SUPER_ADMIN') {
            http_response_code(403);
            echo "Forbidden: Super Admin access required.";
            exit;
        }

        $feedback = SiteFeedback::findById($feedbackId);
        if (!$feedback || empty($feedback['attachment_path'])) {
            http_response_code(404);
            echo "Attachment not found.";
            exit;
        }

        $baseDir = dirname(__DIR__, 2);
        $fullPath = $baseDir . '/' . ltrim($feedback['attachment_path'], '/');

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            http_response_code(404);
            echo "Attachment file not found on disk.";
            exit;
        }

        $filename = !empty($feedback['attachment_name']) ? $feedback['attachment_name'] : basename($fullPath);
        $mime = !empty($feedback['attachment_mime']) ? $feedback['attachment_mime'] : 'application/octet-stream';

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
}
