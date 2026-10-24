<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\Exporter;
use App\Helpers\Session;
use App\Models\AuditLog;
use Throwable;

class AdminAuditTrailController extends Controller
{
    private function requireAdmin(): array
    {
        $user = $this->authUser();
        if (!$user || strtoupper($user['role'] ?? '') !== 'SUPER_ADMIN') {
            if ($this->request->isAjax() || str_contains($this->request->getUri(), '/api/')) {
                $this->json(['error' => 'Unauthorized: Super Admin access required.'], 403);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }
        return $user;
    }

    /**
     * GET /admin/audit-trail
     * Dedicated Audit Trail Management Page
     */
    public function index(): void
    {
        $admin = $this->requireAdmin();
        $filterOptions = AuditLog::getFilterOptions();
        $counts = AuditLog::getCounts();

        $this->render('admin/audit_trail', [
            'pageTitle'     => 'System Audit Trail — Super Admin',
            'activeMenu'    => 'audit_trail',
            'admin'         => $admin,
            'filterOptions' => $filterOptions,
            'totalLogs'     => $counts['total_audit_logs'] ?? 0,
        ], 'admin');
    }

    /**
     * GET/POST /api/admin/audit-trail/data
     * Server-side DataTables Endpoint
     */
    public function data(): void
    {
        $this->requireAdmin();

        try {
            $params = array_merge($_GET, $_POST);
            $result = AuditLog::getDataTable($params);

            // Format rows for presentation
            $formattedData = [];
            foreach ($result['data'] as $row) {
                $formattedData[] = [
                    'id'                => (int)$row['id'],
                    'created_at'        => date('d M Y, h:i:s A', strtotime($row['created_at'])),
                    'raw_created_at'    => $row['created_at'],
                    'user_id'           => $row['user_id'] ? (int)$row['user_id'] : null,
                    'user_name'         => $row['user_name'] ?? 'System',
                    'user_email'        => $row['user_email'] ?? null,
                    'user_role'         => $row['user_role'] ?? 'SYSTEM',
                    'action'            => strtoupper((string)$row['action']),
                    'entity_type'       => $row['entity_type'],
                    'entity_id'         => $row['entity_id'] ? (int)$row['entity_id'] : null,
                    'record_identifier' => $row['record_identifier'] ?? '—',
                    'details'           => $row['details'] ?? '—',
                    'affected_counts'   => $row['affected_counts'] ? json_decode((string)$row['affected_counts'], true) : null,
                    'result'            => strtoupper((string)($row['result'] ?: 'SUCCESS')),
                    'ip_address'        => $row['ip_address'] ?? '—',
                    'user_agent'        => $row['user_agent'] ?? null,
                ];
            }

            $result['data'] = $formattedData;
            $this->json($result);
        } catch (Throwable $e) {
            $this->json([
                'draw'            => (int)($_REQUEST['draw'] ?? 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Failed to load audit logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/admin/audit-trail/export
     * CSV Export of Filtered Audit Records
     */
    public function export(): void
    {
        $admin = $this->requireAdmin();

        try {
            $params = array_merge($_GET, $_POST);
            $records = AuditLog::getExportData($params);

            $headers = [
                'Log ID',
                'Timestamp',
                'User ID',
                'User Name',
                'User Email',
                'User Role',
                'Action',
                'Entity Module',
                'Target Record ID',
                'Target Identifier',
                'Details',
                'Result',
                'IP Address',
            ];

            $rows = [];
            foreach ($records as $r) {
                $rows[] = [
                    $r['id'],
                    $r['created_at'],
                    $r['user_id'] ?? 'SYSTEM',
                    $r['user_name'] ?? 'System',
                    $r['user_email'] ?? '—',
                    $r['user_role'] ?? 'SYSTEM',
                    $r['action'],
                    $r['entity_type'],
                    $r['entity_id'] ?? '—',
                    $r['record_identifier'] ?? '—',
                    $r['details'] ?? '—',
                    strtoupper((string)($r['result'] ?: 'SUCCESS')),
                    $r['ip_address'] ?? '—',
                ];
            }

            AuditLogger::log(
                'EXPORT',
                'AuditLog',
                null,
                'Audit Trail CSV Export',
                ['exported_count' => count($rows)],
                "Super Admin exported " . count($rows) . " audit trail records to CSV"
            );

            $filename = 'sports_junction_audit_trail_' . date('Ymd_His');
            Exporter::exportCsv($filename, $headers, $rows);
        } catch (Throwable $e) {
            Session::flash('error', 'Export failed: ' . $e->getMessage());
            $this->redirect('/admin/audit-trail');
        }
    }
}
