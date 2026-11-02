<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Exporter;
use App\Helpers\Session;
use App\Models\SupportQuery;
use Throwable;

class AdminSupportQueryController extends Controller
{
    /**
     * GET /admin/buyer-queries
     */
    public function buyerQueries(): void
    {
        $metrics    = SupportQuery::getMetrics('BUYER');
        $categories = SupportQuery::CATEGORIES;
        $statuses   = SupportQuery::STATUSES;
        $staff      = SupportQuery::getSupportStaff();

        $this->render('admin/buyer_queries', [
            'pageTitle'   => 'Buyer Support Queries — Sports Junction Admin',
            'metrics'     => $metrics,
            'categories'  => $categories,
            'statuses'    => $statuses,
            'staff'       => $staff,
            'sourceType'  => 'BUYER',
            'activeMenu'  => 'buyer_queries',
        ], 'admin');
    }

    /**
     * GET /api/admin/buyer-queries/data
     */
    public function buyerQueriesData(): void
    {
        $params = [
            'draw'        => $this->request->get('draw', 1),
            'start'       => $this->request->get('start', 0),
            'length'      => $this->request->get('length', 15),
            'search'      => $this->request->get('search', []),
            'order'       => $this->request->get('order', []),
            'status'      => $this->request->get('status', 'ALL'),
            'category'    => $this->request->get('category', 'ALL'),
            'assigned_to' => $this->request->get('assigned_to', 'ALL'),
            'start_date'  => $this->request->get('start_date', ''),
            'end_date'    => $this->request->get('end_date', ''),
        ];

        $result = SupportQuery::getAdminDataTable($params, 'BUYER');
        $this->json($result);
    }

    /**
     * GET /api/admin/buyer-queries/export
     */
    public function buyerQueriesExport(): void
    {
        $this->exportQueries('BUYER', 'Sports_Junction_Buyer_Queries');
    }

    /**
     * GET /admin/seller-queries
     */
    public function sellerQueries(): void
    {
        $metrics    = SupportQuery::getMetrics('SELLER');
        $categories = SupportQuery::CATEGORIES;
        $statuses   = SupportQuery::STATUSES;
        $staff      = SupportQuery::getSupportStaff();

        $this->render('admin/seller_queries', [
            'pageTitle'   => 'Seller Support Queries — Sports Junction Admin',
            'metrics'     => $metrics,
            'categories'  => $categories,
            'statuses'    => $statuses,
            'staff'       => $staff,
            'sourceType'  => 'SELLER',
            'activeMenu'  => 'seller_queries',
        ], 'admin');
    }

    /**
     * GET /api/admin/seller-queries/data
     */
    public function sellerQueriesData(): void
    {
        $params = [
            'draw'        => $this->request->get('draw', 1),
            'start'       => $this->request->get('start', 0),
            'length'      => $this->request->get('length', 15),
            'search'      => $this->request->get('search', []),
            'order'       => $this->request->get('order', []),
            'status'      => $this->request->get('status', 'ALL'),
            'category'    => $this->request->get('category', 'ALL'),
            'assigned_to' => $this->request->get('assigned_to', 'ALL'),
            'start_date'  => $this->request->get('start_date', ''),
            'end_date'    => $this->request->get('end_date', ''),
        ];

        $result = SupportQuery::getAdminDataTable($params, 'SELLER');
        $this->json($result);
    }

    /**
     * GET /api/admin/seller-queries/export
     */
    public function sellerQueriesExport(): void
    {
        $this->exportQueries('SELLER', 'Sports_Junction_Seller_Queries');
    }

    /**
     * GET /api/admin/support-queries/{id}
     * Get single query details with activities, linked tickets, and order references
     */
    public function getQueryDetail(array|string $params): void
    {
        $id = (int) (is_array($params) ? ($params['id'] ?? 0) : $params);
        $query = SupportQuery::findById($id);

        if (!$query) {
            $this->json(['success' => false, 'message' => 'Query not found.'], 404);
            return;
        }

        $activities = SupportQuery::getActivities($id);

        // Check if existing_ticket_id refers to another ticket
        $linkedTicket = null;
        if (!empty($query['existing_ticket_id'])) {
            $prev = SupportQuery::findByTicketId($query['existing_ticket_id']);
            if ($prev) {
                $linkedTicket = [
                    'id'         => $prev['id'],
                    'ticket_id'  => $prev['ticket_id'],
                    'subject'    => $prev['subject'],
                    'status'     => $prev['status'],
                    'created_at' => $prev['created_at'],
                ];
            }
        }

        // Check any follow-up tickets that point to this query
        $followUpTickets = SupportQuery::fetchAll("
            SELECT id, ticket_id, subject, status, created_at
            FROM support_queries
            WHERE existing_ticket_id = :tid AND id != :qid
            ORDER BY id DESC
        ", [':tid' => $query['ticket_id'], ':qid' => $id]);

        $this->json([
            'success'          => true,
            'query'            => $query,
            'activities'       => $activities,
            'linked_ticket'    => $linkedTicket,
            'follow_up_tickets'=> $followUpTickets,
            'status_label'     => SupportQuery::STATUSES[$query['status']] ?? $query['status'],
        ]);
    }

    /**
     * POST /api/admin/support-queries/{id}/status
     */
    public function updateStatus(array|string $params): void
    {
        $id = (int) (is_array($params) ? ($params['id'] ?? 0) : $params);
        $query = SupportQuery::findById($id);

        if (!$query) {
            $this->json(['success' => false, 'message' => 'Query not found.'], 404);
            return;
        }

        $status = strtoupper(trim((string) $this->request->post('status', '')));
        $notes = trim((string) $this->request->post('notes', ''));

        if (!array_key_exists($status, SupportQuery::STATUSES)) {
            $this->json(['success' => false, 'message' => 'Invalid status selected.'], 422);
            return;
        }

        $authUser = $this->authUser();
        $adminId = $authUser ? (int)$authUser['id'] : null;

        $ok = SupportQuery::updateStatus($id, $status, $adminId, $notes);

        if ($ok) {
            $updated = SupportQuery::findById($id);
            $activities = SupportQuery::getActivities($id);
            $metrics = SupportQuery::getMetrics($query['source_type']);

            $this->json([
                'success'      => true,
                'message'      => "Status updated to " . SupportQuery::STATUSES[$status] . ".",
                'status'       => $status,
                'status_label' => SupportQuery::STATUSES[$status],
                'internal_notes' => $updated['internal_notes'],
                'activities'   => $activities,
                'metrics'      => $metrics,
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update query status.'], 500);
        }
    }

    /**
     * POST /api/admin/support-queries/{id}/assign
     */
    public function assign(array|string $params): void
    {
        $id = (int) (is_array($params) ? ($params['id'] ?? 0) : $params);
        $query = SupportQuery::findById($id);

        if (!$query) {
            $this->json(['success' => false, 'message' => 'Query not found.'], 404);
            return;
        }

        $rawAssignee = $this->request->post('assigned_to');
        $assigneeId = ($rawAssignee !== '' && $rawAssignee !== null) ? (int)$rawAssignee : null;
        if ($assigneeId === 0) $assigneeId = null;

        $authUser = $this->authUser();
        $adminId = $authUser ? (int)$authUser['id'] : null;

        $ok = SupportQuery::assignTo($id, $assigneeId, $adminId);

        if ($ok) {
            $updated = SupportQuery::findById($id);
            $activities = SupportQuery::getActivities($id);

            $this->json([
                'success'            => true,
                'message'            => $assigneeId ? "Query assigned successfully." : "Query marked as unassigned.",
                'assigned_to'        => $updated['assigned_to'],
                'assigned_user_name' => $updated['assigned_user_name'] ?? 'Unassigned',
                'activities'         => $activities,
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update assignment.'], 500);
        }
    }

    /**
     * POST /api/admin/support-queries/{id}/notes
     */
    public function addNote(array|string $params): void
    {
        $id = (int) (is_array($params) ? ($params['id'] ?? 0) : $params);
        $query = SupportQuery::findById($id);

        if (!$query) {
            $this->json(['success' => false, 'message' => 'Query not found.'], 404);
            return;
        }

        $note = trim((string) $this->request->post('note', ''));
        if (empty($note)) {
            $this->json(['success' => false, 'message' => 'Internal note text cannot be empty.'], 422);
            return;
        }

        $authUser = $this->authUser();
        $adminId = $authUser ? (int)$authUser['id'] : null;

        $ok = SupportQuery::addInternalNote($id, $note, $adminId);

        if ($ok) {
            $updated = SupportQuery::findById($id);
            $activities = SupportQuery::getActivities($id);

            $this->json([
                'success'        => true,
                'message'        => 'Internal note saved.',
                'internal_notes' => $updated['internal_notes'],
                'activities'     => $activities,
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to save internal note.'], 500);
        }
    }

    /**
     * GET /admin/support-queries/{id}/attachment
     */
    public function downloadAttachment(array|string $params): void
    {
        $id = (int) (is_array($params) ? ($params['id'] ?? 0) : $params);
        $query = SupportQuery::findById($id);

        if (!$query || empty($query['attachment_path'])) {
            http_response_code(404);
            echo "Attachment not found.";
            return;
        }

        $filePath = dirname(__DIR__, 2) . '/' . ltrim($query['attachment_path'], '/');
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Attachment file does not exist on disk.";
            return;
        }

        $downloadName = !empty($query['attachment_name']) ? $query['attachment_name'] : basename($filePath);
        $mime = !empty($query['attachment_mime']) ? $query['attachment_mime'] : 'application/octet-stream';

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Export helper
     */
    private function exportQueries(string $sourceType, string $filenamePrefix): void
    {
        $format = strtolower((string)$this->request->get('format', 'csv'));

        $params = [
            'status'      => $this->request->get('status', 'ALL'),
            'category'    => $this->request->get('category', 'ALL'),
            'assigned_to' => $this->request->get('assigned_to', 'ALL'),
            'start_date'  => $this->request->get('start_date', ''),
            'end_date'    => $this->request->get('end_date', ''),
            'search_query'=> $this->request->get('search', ''),
        ];

        $data = SupportQuery::getExportData($params, $sourceType);

        $headers = [
            'Ticket ID',
            'Source Type',
            'Name',
            'Email',
            'Phone',
            'Category',
            'Subject',
            'Order ID',
            'Existing Ticket ID',
            'Status',
            'Assigned To',
            'Description',
            'Has Attachment',
            'Submission Date',
            'Last Updated'
        ];

        $rows = [];
        foreach ($data as $q) {
            $rows[] = [
                $q['ticket_id'],
                $q['source_type'],
                $q['name'],
                $q['email'],
                $q['phone'],
                $q['category'],
                $q['subject'],
                $q['order_id'] ?? '—',
                $q['existing_ticket_id'] ?? '—',
                SupportQuery::STATUSES[$q['status']] ?? $q['status'],
                $q['assigned_user_name'] ?? 'Unassigned',
                $q['description'],
                !empty($q['attachment_path']) ? 'Yes (' . ($q['attachment_name'] ?? 'file') . ')' : 'No',
                $q['created_at'],
                $q['updated_at'],
            ];
        }

        $filename = $filenamePrefix . '_' . date('Ymd_His');

        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }
}
