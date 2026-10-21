<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Mailer;
use App\Models\Product;
use App\Models\Supplier;

class ProductApprovalController extends Controller
{
    public function index(): void
    {
        $metrics = Product::getApprovalMetrics();

        $this->render('admin/product_approvals', [
            'pageTitle'  => 'Product Catalog Approvals — Sports Junction',
            'activeMenu' => 'product_approvals',
            'metrics'    => $metrics,
        ]);
    }

    public function data(): void
    {
        $draw = (int)($this->request->get('draw') ?? $this->request->input('draw') ?? 1);
        try {
            $filter = (string)($this->request->get('filter') ?? $this->request->input('filter') ?? 'ALL');
            $records = Product::getPendingApprovals($filter);
            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => count($records),
                'recordsFiltered' => count($records),
                'data'            => $records,
            ]);
        } catch (\Throwable $e) {
            error_log("Product approvals DataTables error: " . $e->getMessage());
            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Unable to load catalog approvals. Please retry.',
            ]);
        }
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found.'], 404);
            return;
        }

        // Decode pending_changes if present
        if (!empty($product['pending_changes'])) {
            $product['decoded_pending_changes'] = json_decode($product['pending_changes'], true);
        }

        // Attach assigned certificates
        $product['certificates'] = \App\Models\ProductCertificate::getForProduct($id);

        $this->json(['success' => true, 'data' => $product]);
    }

    public function approve(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found.'], 404);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $hasPendingChanges = !empty($product['pending_changes']);
        $ok = Product::approveProduct($id, $adminId);
        if ($ok) {
            // Send email to supplier
            $supplier = Supplier::findById((int)$product['supplier_id']);
            if ($supplier) {
                if ($hasPendingChanges) {
                    Mailer::sendProductUpdateApproved($supplier['email'], $supplier['company_name'], $product['title']);
                } else {
                    Mailer::sendProductCreationApproved($supplier['email'], $supplier['company_name'], $product['title']);
                }
            }

            AuditLogger::log('APPROVE', 'Product', $id, $product['title'], null, 'Super Admin approved product submission');
            $this->json(['success' => true, 'message' => "Product '{$product['title']}' approved and published.", 'metrics' => Product::getApprovalMetrics()]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to approve product.'], 500);
        }
    }

    public function reject(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $product = Product::findById($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found.'], 404);
            return;
        }

        $reason = trim((string)$this->request->input('reason', ''));
        if (empty($reason)) {
            $this->json(['success' => false, 'message' => 'Rejection reason is mandatory.'], 422);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $hasPendingChanges = !empty($product['pending_changes']);
        $ok = Product::rejectProduct($id, $adminId, $reason);
        if ($ok) {
            $supplier = Supplier::findById((int)$product['supplier_id']);
            if ($supplier) {
                if ($hasPendingChanges) {
                    Mailer::sendProductUpdateRejected($supplier['email'], $supplier['company_name'], $product['title'], $reason);
                } else {
                    Mailer::sendProductCreationRejected($supplier['email'], $supplier['company_name'], $product['title'], $reason);
                }
            }

            AuditLogger::log('REJECT', 'Product', $id, $product['title'], ['reason' => $reason], 'Super Admin rejected product submission');
            $this->json(['success' => true, 'message' => "Product submission rejected with feedback provided to supplier.", 'metrics' => Product::getApprovalMetrics()]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to reject product.'], 500);
        }
    }
}
