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
        $filter = (string)$this->request->query('filter', 'ALL');
        $records = Product::getPendingApprovals($filter);
        $this->json([
            'draw'            => 1,
            'recordsTotal'    => count($records),
            'recordsFiltered' => count($records),
            'data'            => $records,
        ]);
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

        $ok = Product::approveProduct($id, $adminId);
        if ($ok) {
            // Send email to supplier
            $supplier = Supplier::findById((int)$product['supplier_id']);
            if ($supplier) {
                Mailer::sendProductApproved($supplier['email'], $supplier['company_name'], $product['title']);
            }

            AuditLogger::log('APPROVE', 'Product', $id, $product['title'], null, 'Super Admin approved product submission');
            $this->json(['success' => true, 'message' => "Product '{$product['title']}' approved and published."]);
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

        $ok = Product::rejectProduct($id, $adminId, $reason);
        if ($ok) {
            $supplier = Supplier::findById((int)$product['supplier_id']);
            if ($supplier) {
                Mailer::sendProductRejected($supplier['email'], $supplier['company_name'], $product['title'], $reason);
            }

            AuditLogger::log('REJECT', 'Product', $id, $product['title'], ['reason' => $reason], 'Super Admin rejected product submission');
            $this->json(['success' => true, 'message' => "Product submission rejected with feedback provided to supplier."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to reject product.'], 500);
        }
    }
}
