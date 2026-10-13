<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Exporter;
use App\Helpers\Session;
use App\Models\Category;
use App\Models\ProductInquiry;
use App\Models\Supplier;
use App\Models\User;

class EnquiryManagementController extends Controller
{
    public function index(): void
    {
        $metrics    = ProductInquiry::getAdminMetrics();
        $suppliers  = Supplier::allActive();
        $categories = Category::allActive();

        $filterBuyer = null;
        $buyerIdParam = $this->request->get('buyer_id');
        if (!empty($buyerIdParam)) {
            $filterBuyer = User::getBuyerDetails((int) $buyerIdParam);
        }

        $this->render('admin/enquiries', [
            'pageTitle'   => 'Centralized Enquiry Management — Sports Junction Admin',
            'metrics'     => $metrics,
            'suppliers'   => $suppliers,
            'categories'  => $categories,
            'filterBuyer' => $filterBuyer,
        ], 'admin');
    }

    public function data(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'enquiry_source' => $this->request->get('enquiry_source', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'buyer_id'       => $this->request->get('buyer_id', ''),
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function show(array|string $params): void
    {
        $id        = is_array($params) ? ($params['id'] ?? 0) : $params;
        $enquiryId = (int) $id;
        $enquiry   = ProductInquiry::findById($enquiryId, true);

        if (!$enquiry) {
            if ($this->request->isAjax() || $this->request->get('format') === 'json') {
                $this->json(['success' => false, 'message' => 'Enquiry not found.'], 404);
                return;
            }
            Session::flash('error', 'Enquiry record not found.');
            $this->redirect('/admin/enquiries');
            return;
        }

        $replies   = ProductInquiry::getReplies($enquiryId);
        $suppliers = Supplier::allActive();

        if ($this->request->isAjax() || $this->request->get('format') === 'json') {
            $this->json([
                'success'   => true,
                'enquiry'   => $enquiry,
                'replies'   => $replies,
                'suppliers' => $suppliers,
            ]);
            return;
        }

        $this->render('admin/enquiry_detail', [
            'pageTitle' => 'Enquiry #ENQ-' . str_pad((string) $enquiry['id'], 5, '0', STR_PAD_LEFT) . ' — Sports Junction Admin',
            'enquiry'   => $enquiry,
            'replies'   => $replies,
            'suppliers' => $suppliers,
        ], 'admin');
    }

    public function assignSupplier(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $enquiryId  = (int) $id;
        $enquiry    = ProductInquiry::findById($enquiryId, true);
        if (!$enquiry) {
            $this->json(['success' => false, 'message' => 'Enquiry not found.'], 404);
            return;
        }

        $rawSupplierId = $this->request->post('supplier_id', '');
        $supplierId    = (!empty($rawSupplierId) && (int) $rawSupplierId > 0) ? (int) $rawSupplierId : null;

        $supplierName = 'Unassigned';
        if ($supplierId !== null) {
            $sup = Supplier::findById($supplierId);
            if (!$sup) {
                $this->json(['success' => false, 'message' => 'Selected supplier does not exist.'], 422);
                return;
            }
            $supplierName = $sup['company_name'];
        }

        ProductInquiry::assignSupplier($enquiryId, $supplierId);

        AuditLogger::log(
            'ASSIGN_SUPPLIER',
            'ProductInquiry',
            $enquiryId,
            "Assigned to {$supplierName}",
            ['supplier_id' => $enquiry['supplier_id']],
            ['supplier_id' => $supplierId, 'supplier_name' => $supplierName]
        );

        $this->json([
            'success'       => true,
            'supplier_id'   => $supplierId,
            'supplier_name' => $supplierName,
            'message'       => $supplierId
                ? "Enquiry #ENQ-" . str_pad((string)$enquiryId, 5, '0', STR_PAD_LEFT) . " assigned to {$supplierName}."
                : "Supplier assignment cleared.",
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

        $enquiryId = (int) $id;
        $enquiry   = ProductInquiry::findById($enquiryId, true);
        if (!$enquiry) {
            $this->json(['success' => false, 'message' => 'Enquiry not found.'], 404);
            return;
        }

        $status = strtoupper(trim((string) $this->request->post('status', '')));
        if (!ProductInquiry::updateStatus($enquiryId, $status)) {
            $this->json(['success' => false, 'message' => 'Invalid enquiry status value.'], 422);
            return;
        }

        AuditLogger::log(
            'STATUS_CHANGE',
            'ProductInquiry',
            $enquiryId,
            "Status changed to {$status}",
            ['status' => $enquiry['status']],
            ['status' => $status]
        );

        $this->json([
            'success' => true,
            'status'  => $status,
            'metrics' => ProductInquiry::getAdminMetrics(),
            'message' => "Enquiry status updated to {$status}.",
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

        $enquiryId = (int) $id;
        $enquiry   = ProductInquiry::findById($enquiryId, true);
        if (!$enquiry) {
            $this->json(['success' => false, 'message' => 'Enquiry not found.'], 404);
            return;
        }

        $notes = (string) $this->request->post('admin_notes', '');
        ProductInquiry::updateAdminNotes($enquiryId, $notes);

        AuditLogger::log(
            'UPDATE_NOTES',
            'ProductInquiry',
            $enquiryId,
            'Updated internal Super Admin notes'
        );

        $this->json([
            'success' => true,
            'message' => 'Internal Super Admin notes saved (visible only to Super Admin).',
        ]);
    }

    public function reply(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
                return;
            }
            Session::flash('error', 'Invalid security token.');
            $this->redirect("/admin/enquiries/{$id}");
            return;
        }

        $enquiryId = (int) $id;
        $enquiry   = ProductInquiry::findById($enquiryId, true);
        if (!$enquiry) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'Enquiry not found.'], 404);
                return;
            }
            Session::flash('error', 'Enquiry not found.');
            $this->redirect('/admin/enquiries');
            return;
        }

        $message = trim((string) $this->request->post('message', ''));
        if ($message === '') {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'Reply message cannot be empty.'], 422);
                return;
            }
            Session::flash('error', 'Reply message cannot be empty.');
            $this->redirect("/admin/enquiries/{$id}");
            return;
        }

        $adminUser = $this->authUser();
        $replyId   = ProductInquiry::addReply($enquiryId, (int) ($adminUser['id'] ?? 1), 'SUPER_ADMIN', $message);

        AuditLogger::log('REPLY', 'ProductInquiry', $enquiryId, 'Super Admin posted reply #' . $replyId);

        if ($this->request->isAjax()) {
            $this->json([
                'success' => true,
                'message' => 'Reply posted to enquiry conversation.',
                'replies' => ProductInquiry::getReplies($enquiryId),
                'enquiry' => ProductInquiry::findById($enquiryId, true),
            ]);
            return;
        }

        Session::flash('success', 'Reply sent successfully.');
        $this->redirect("/admin/enquiries/{$id}");
    }

    public function export(): void
    {
        $format = strtolower((string) $this->request->get('format', 'csv'));
        $params = [
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'enquiry_source' => $this->request->get('enquiry_source', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'buyer_id'       => $this->request->get('buyer_id', ''),
            'search_query'   => $this->request->get('search', ''),
        ];

        $rows = ProductInquiry::getAdminExportData($params);
        $headers = [
            'Enquiry ID',
            'Enquiry Source',
            'Buyer Name',
            'Buyer Company',
            'Buyer Email',
            'Buyer Phone',
            'Product Name / Requirement',
            'Supplier Name',
            'Quantity Required',
            'Delivery Location',
            'Enquiry Subject',
            'Message / Specifications',
            'Enquiry Status',
            'Enquiry Date',
        ];

        AuditLogger::log('EXPORT', 'ProductInquiry', null, "Exported " . count($rows) . " enquiries as " . strtoupper($format));

        $filename = 'sports_junction_enquiries_' . date('Ymd_His');
        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } elseif ($format === 'pdf') {
            Exporter::exportPdfView('Sports Junction — Centralized B2B Enquiries Report', $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }
}
