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
            'activeMenu'  => 'enquiries',
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

    public function rfqOem(): void
    {
        $metrics    = ProductInquiry::getRfqOemMetrics();
        $suppliers  = Supplier::allActive();
        $categories = Category::allActive();

        $this->render('admin/rfq_oem', [
            'pageTitle'   => 'RFQ & Fast OEM Customization Enquiries — Sports Junction Admin',
            'metrics'     => $metrics,
            'suppliers'   => $suppliers,
            'categories'  => $categories,
            'activeMenu'  => 'rfq_oem',
        ], 'admin');
    }

    public function rfqOemData(): void
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
            'rfq_oem_only'   => 1,
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function rfqOemExport(): void
    {
        $format = strtolower((string) $this->request->get('format', 'csv'));
        $params = [
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'enquiry_source' => $this->request->get('enquiry_source', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'search_query'   => $this->request->get('search', ''),
            'rfq_oem_only'   => 1,
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

        AuditLogger::log('EXPORT', 'ProductInquiry', null, "Exported " . count($rows) . " RFQ & OEM enquiries as " . strtoupper($format));

        $filename = 'sports_junction_rfq_oem_' . date('Ymd_His');
        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } elseif ($format === 'pdf') {
            Exporter::exportPdfView('Sports Junction — RFQ & Fast OEM Customization Enquiries', $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }

    public function sampleEnquiries(): void
    {
        $metrics    = ProductInquiry::getSampleMetrics();
        $suppliers  = Supplier::allActive();
        $categories = Category::allActive();

        $this->render('admin/sample_enquiries', [
            'pageTitle'   => 'Dedicated Prototype Sample Enquiries — Sports Junction Admin',
            'metrics'     => $metrics,
            'suppliers'   => $suppliers,
            'categories'  => $categories,
            'activeMenu'  => 'sample_enquiries',
        ], 'admin');
    }

    public function sampleEnquiriesData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'sample_only'    => 1,
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function sampleEnquiriesExport(): void
    {
        $format = strtolower((string) $this->request->get('format', 'csv'));
        $params = [
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'search_query'   => $this->request->get('search', ''),
            'sample_only'    => 1,
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
            'Country',
            'Delivery Location',
            'Enquiry Subject',
            'Message / Specifications',
            'Enquiry Status',
            'Enquiry Date',
        ];

        AuditLogger::log('EXPORT', 'ProductInquiry', null, "Exported " . count($rows) . " prototype sample enquiries as " . strtoupper($format));

        $filename = 'sports_junction_sample_enquiries_' . date('Ymd_His');
        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } elseif ($format === 'pdf') {
            Exporter::exportPdfView('Sports Junction — Prototype Sample Order Enquiries', $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }

    /**
     * 1. Product Page Enquiries (Strict Source: PRODUCT_PAGE)
     */
    public function productEnquiries(): void
    {
        $metrics    = ProductInquiry::getSourceMetrics('PRODUCT_PAGE');
        $suppliers  = Supplier::allActive();
        $categories = Category::allActive();

        $this->render('admin/enquiries_product', [
            'pageTitle'   => 'Product Page Enquiries — Sports Junction Admin',
            'metrics'     => $metrics,
            'suppliers'   => $suppliers,
            'categories'  => $categories,
            'activeMenu'  => 'enquiries_product',
        ], 'admin');
    }

    public function productEnquiriesData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'strict_source'  => 'PRODUCT_PAGE',
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function productEnquiriesExport(): void
    {
        $this->exportForSource('PRODUCT_PAGE', 'Product Page Enquiries', 'sports_junction_product_enquiries');
    }

    /**
     * 2. RFQ Enquiries (Strict Source: RFQ)
     */
    public function rfqEnquiries(): void
    {
        $metrics    = ProductInquiry::getSourceMetrics('RFQ');
        $suppliers  = Supplier::allActive();
        $categories = Category::allActive();

        $this->render('admin/enquiries_rfq', [
            'pageTitle'   => 'RFQ Enquiries (Request for Quotation) — Sports Junction Admin',
            'metrics'     => $metrics,
            'suppliers'   => $suppliers,
            'categories'  => $categories,
            'activeMenu'  => 'enquiries_rfq',
        ], 'admin');
    }

    public function rfqEnquiriesData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'strict_source'  => 'RFQ',
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function rfqEnquiriesExport(): void
    {
        $this->exportForSource('RFQ', 'RFQ Enquiries', 'sports_junction_rfq_enquiries');
    }

    /**
     * 3. Fast OEM Customization Enquiries (Strict Source: FAST_OEM)
     */
    public function fastOemEnquiries(): void
    {
        $metrics    = ProductInquiry::getSourceMetrics('FAST_OEM');
        $suppliers  = Supplier::allActive();
        $categories = Category::allActive();

        $this->render('admin/enquiries_fast_oem', [
            'pageTitle'   => 'Fast OEM Customization Enquiries — Sports Junction Admin',
            'metrics'     => $metrics,
            'suppliers'   => $suppliers,
            'categories'  => $categories,
            'activeMenu'  => 'enquiries_fast_oem',
        ], 'admin');
    }

    public function fastOemEnquiriesData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'strict_source'  => 'FAST_OEM',
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function fastOemEnquiriesExport(): void
    {
        $this->exportForSource('FAST_OEM', 'Fast OEM Customization Enquiries', 'sports_junction_fast_oem_enquiries');
    }

    /**
     * 5. General Enquiries (Strict Source: GENERAL)
     */
    public function generalEnquiries(): void
    {
        $metrics    = ProductInquiry::getSourceMetrics('GENERAL');
        $suppliers  = Supplier::allActive();
        $categories = Category::allActive();

        $this->render('admin/enquiries_general', [
            'pageTitle'   => 'General Sourcing Enquiries — Sports Junction Admin',
            'metrics'     => $metrics,
            'suppliers'   => $suppliers,
            'categories'  => $categories,
            'activeMenu'  => 'enquiries_general',
        ], 'admin');
    }

    public function generalEnquiriesData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'strict_source'  => 'GENERAL',
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function generalEnquiriesExport(): void
    {
        $this->exportForSource('GENERAL', 'General Enquiries', 'sports_junction_general_enquiries');
    }

    /**
     * 6. Contact Us Enquiries (Strict Source: CONTACT_US)
     */
    public function contactEnquiries(): void
    {
        $metrics   = ProductInquiry::getSourceMetrics('CONTACT_US');
        $countries = \App\Models\Country::allActive();

        $this->render('admin/enquiries_contact', [
            'pageTitle'   => 'Contact Us Messages & Enquiries — Sports Junction Admin',
            'metrics'     => $metrics,
            'countries'   => $countries,
            'activeMenu'  => 'enquiries_contact',
        ], 'admin');
    }

    public function contactEnquiriesData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'status'         => $this->request->get('status', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'strict_source'  => 'CONTACT_US',
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function contactEnquiriesExport(): void
    {
        $this->exportForSource('CONTACT_US', 'Contact Us Enquiries', 'sports_junction_contact_us_enquiries');
    }

    /**
     * 7. Support Enquiries (Strict Source: SUPPORT)
     */
    public function supportEnquiries(): void
    {
        $metrics   = ProductInquiry::getSourceMetrics('SUPPORT');
        $countries = \App\Models\Country::allActive();

        $this->render('admin/enquiries_support', [
            'pageTitle'   => 'Help Centre & Support Tickets — Sports Junction Admin',
            'metrics'     => $metrics,
            'countries'   => $countries,
            'activeMenu'  => 'enquiries_support',
        ], 'admin');
    }

    public function supportEnquiriesData(): void
    {
        $params = [
            'draw'           => $this->request->get('draw', 1),
            'start'          => $this->request->get('start', 0),
            'length'         => $this->request->get('length', 15),
            'search'         => $this->request->get('search', []),
            'order'          => $this->request->get('order', []),
            'status'         => $this->request->get('status', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'strict_source'  => 'SUPPORT',
        ];

        $result = ProductInquiry::getAdminDataTable($params);
        $this->json($result);
    }

    public function supportEnquiriesExport(): void
    {
        $this->exportForSource('SUPPORT', 'Support Enquiries', 'sports_junction_support_enquiries');
    }

    /**
     * Shared export handler for dedicated source-specific pages
     */
    private function exportForSource(string $source, string $reportTitle, string $filenamePrefix): void
    {
        $format = strtolower((string) $this->request->get('format', 'csv'));
        $params = [
            'supplier_id'    => $this->request->get('supplier_id', 'ALL'),
            'status'         => $this->request->get('status', 'ALL'),
            'category_id'    => $this->request->get('category_id', 'ALL'),
            'start_date'     => $this->request->get('start_date', ''),
            'end_date'       => $this->request->get('end_date', ''),
            'search_query'   => $this->request->get('search', ''),
            'strict_source'  => $source,
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
            'Country',
            'Delivery Location',
            'Enquiry Subject',
            'Message / Specifications',
            'Enquiry Status',
            'Enquiry Date',
        ];

        AuditLogger::log('EXPORT', 'ProductInquiry', null, "Exported " . count($rows) . " {$source} enquiries as " . strtoupper($format));

        $filename = $filenamePrefix . '_' . date('Ymd_His');
        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } elseif ($format === 'pdf') {
            Exporter::exportPdfView("Sports Junction — {$reportTitle}", $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }

    /**
     * Secure Attachment Download (Task 13)
     * GET /admin/enquiries/attachment/{id}
     */
    public function downloadAttachment(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $enquiryId = (int) $id;

        $authUser = $this->authUser();
        if (!$authUser) {
            http_response_code(401);
            echo "Unauthorized: Please log in to download enquiry attachments.";
            exit;
        }

        $role = strtoupper($authUser['role'] ?? '');
        $enquiry = ProductInquiry::findById($enquiryId, true);
        if (!$enquiry || empty($enquiry['attachment_path'])) {
            http_response_code(404);
            echo "Attachment not found.";
            exit;
        }

        // Strict Server-Side RBAC Enforcement (Task 13 & 14)
        $isSuperAdmin = ($role === 'SUPER_ADMIN');
        $isOwnerBuyer = ($role === 'BUYER' && (int)($enquiry['buyer_user_id'] ?? 0) === (int)$authUser['id']);
        $isAssignedSupplier = ($role === 'SUPPLIER' && (int)($enquiry['effective_supplier_id'] ?? 0) === (int)($authUser['supplier_id'] ?? 0));

        if (!$isSuperAdmin && !$isOwnerBuyer && !$isAssignedSupplier) {
            http_response_code(403);
            echo "Forbidden: You do not have permission to access this attachment.";
            exit;
        }

        $baseDir = dirname(__DIR__, 2);
        $fullPath = $baseDir . '/' . ltrim($enquiry['attachment_path'], '/');

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            http_response_code(404);
            echo "File not found on storage server.";
            exit;
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
}
