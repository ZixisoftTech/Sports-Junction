<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Session;
use App\Models\BulkProductEnquiry;

class AdminBulkEnquiryController extends Controller
{
    protected function requireAdmin(): array
    {
        $user = Session::get('auth_user');
        if (!$user || ($user['role'] ?? '') !== 'SUPER_ADMIN') {
            $this->redirect('/login');
            exit;
        }
        return $user;
    }

    /**
     * GET /admin/bulk-enquiries
     * Super Admin: View, Search & Filter Bulk Product Enquiries
     */
    public function index(): void
    {
        $admin = $this->requireAdmin();

        $search = trim((string)$this->request->get('search', ''));
        $status = trim((string)$this->request->get('status', 'ALL'));
        $dateFrom = trim((string)$this->request->get('date_from', ''));
        $dateTo = trim((string)$this->request->get('date_to', ''));
        $page = max(1, (int)$this->request->get('page', 1));

        $filters = [
            'search'    => $search,
            'status'    => $status,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo
        ];

        $data = BulkProductEnquiry::getAllPaginated($filters, $page, 15);

        // Calculate KPI Counts
        BulkProductEnquiry::ensureSchema();
        $totalCount = (int) BulkProductEnquiry::fetchColumn("SELECT COUNT(*) FROM bulk_product_enquiries");
        $newCount = (int) BulkProductEnquiry::fetchColumn("SELECT COUNT(*) FROM bulk_product_enquiries WHERE status = 'SUBMITTED'");
        $reviewedCount = (int) BulkProductEnquiry::fetchColumn("SELECT COUNT(*) FROM bulk_product_enquiries WHERE status = 'REVIEWED'");
        $processedCount = (int) BulkProductEnquiry::fetchColumn("SELECT COUNT(*) FROM bulk_product_enquiries WHERE status = 'PROCESSED'");

        $this->render('admin/bulk_enquiries', [
            'pageTitle'      => 'Bulk Product Enquiries — Super Admin',
            'activeMenu'     => 'bulk_enquiries',
            'admin'          => $admin,
            'enquiries'      => $data['records'],
            'total'          => $data['total'],
            'page'           => $data['page'],
            'perPage'        => $data['perPage'],
            'totalPages'     => $data['totalPages'],
            'filters'        => $filters,
            'statuses'       => BulkProductEnquiry::STATUSES,
            'counts'         => [
                'total'     => $totalCount,
                'new'       => $newCount,
                'reviewed'  => $reviewedCount,
                'processed' => $processedCount
            ]
        ], 'admin');
    }

    /**
     * GET /admin/bulk-enquiries/{id} or AJAX JSON
     * Super Admin: View Single Bulk Enquiry with all linked products
     */
    public function show(array $params): void
    {
        $admin = $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);

        $enquiry = BulkProductEnquiry::getDetailForAdmin($id);
        if (!$enquiry) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'Bulk enquiry not found.'], 404);
                return;
            }
            Session::flash('error', 'Bulk enquiry not found.');
            $this->redirect('/admin/bulk-enquiries');
            return;
        }

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'enquiry' => $enquiry]);
            return;
        }

        $this->render('admin/bulk_enquiries', [
            'pageTitle'      => 'Bulk Product Enquiry ' . ($enquiry['reference_no'] ?? '') . ' — Super Admin',
            'activeMenu'     => 'bulk_enquiries',
            'admin'          => $admin,
            'activeEnquiry'  => $enquiry,
            'statuses'       => BulkProductEnquiry::STATUSES
        ], 'admin');
    }

    /**
     * POST /api/admin/bulk-enquiries/{id}/status
     * Super Admin: Update status & admin notes only (strictly NO messaging/reply)
     */
    public function updateStatus(array $params): void
    {
        $admin = $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);

        $status = trim((string)$this->request->post('status', ''));
        $adminNotes = $this->request->post('admin_notes');

        if (empty($status) || !array_key_exists($status, BulkProductEnquiry::STATUSES)) {
            $this->json(['success' => false, 'message' => 'Invalid status selected.'], 422);
            return;
        }

        $ok = BulkProductEnquiry::updateStatus($id, $status, $adminNotes);
        if ($ok) {
            $this->json([
                'success' => true,
                'message' => 'Bulk enquiry status updated successfully.',
                'status'  => $status,
                'label'   => BulkProductEnquiry::STATUSES[$status] ?? $status
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update status.'], 500);
        }
    }

    /**
     * GET /admin/bulk-enquiries/attachment/{id}
     * Download shared attachment
     */
    public function downloadAttachment(array $params): void
    {
        $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);

        $enquiry = BulkProductEnquiry::getById($id);
        if (!$enquiry || empty($enquiry['attachment_path'])) {
            http_response_code(404);
            echo "Attachment not found.";
            return;
        }

        $baseDir = dirname(__DIR__, 2);
        $fullPath = $baseDir . '/' . ltrim($enquiry['attachment_path'], '/');

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "File not found on server.";
            return;
        }

        $filename = !empty($enquiry['attachment_name']) ? $enquiry['attachment_name'] : basename($fullPath);
        $mime = !empty($enquiry['attachment_mime']) ? $enquiry['attachment_mime'] : 'application/octet-stream';

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

    /**
     * GET /admin/bulk-enquiries/item-attachment/{id}
     * Download product-specific attachment
     */
    public function downloadItemAttachment(array $params): void
    {
        $this->requireAdmin();
        $itemId = (int)($params['id'] ?? 0);

        BulkProductEnquiry::ensureSchema();
        $item = BulkProductEnquiry::fetch("SELECT * FROM bulk_product_enquiry_items WHERE id = :id LIMIT 1", [':id' => $itemId]);
        if (!$item || empty($item['attachment_path'])) {
            http_response_code(404);
            echo "Item attachment not found.";
            return;
        }

        $baseDir = dirname(__DIR__, 2);
        $fullPath = $baseDir . '/' . ltrim($item['attachment_path'], '/');

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "File not found on server.";
            return;
        }

        $filename = !empty($item['attachment_name']) ? $item['attachment_name'] : basename($fullPath);
        $mime = !empty($item['attachment_mime']) ? $item['attachment_mime'] : 'application/octet-stream';

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
