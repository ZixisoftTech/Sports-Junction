<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Session;
use App\Models\Supplier;

class SupplierAuthMiddleware
{
    public function handle(Request $request, Response $response): bool
    {
        Session::start();
        $user = Session::get('auth_user');

        if ($user && isset($user['id']) && ($user['role'] ?? '') === 'SUPER_ADMIN') {
            $targetPath = $request->getPath();
            if (str_starts_with($targetPath, '/supplier/messages')) {
                $convId = (int) ($request->get('conversation_id') ?? $request->get('conv') ?? 0);
                $dest = $convId > 0 ? "/admin/communication/{$convId}" : "/admin/communication";
                $response->redirect($dest);
                return false;
            }
            if (str_starts_with($targetPath, '/supplier/products')) {
                $response->redirect('/admin/products');
                return false;
            }
            if (str_starts_with($targetPath, '/supplier/sample-enquiries')) {
                $response->redirect('/admin/sample-enquiries');
                return false;
            }
            if (str_starts_with($targetPath, '/supplier/enquiries')) {
                $response->redirect('/admin/enquiries');
                return false;
            }
            $response->redirect('/admin/dashboard');
            return false;
        }

        if (!$user || !isset($user['id']) || ($user['role'] ?? '') !== 'SUPPLIER') {
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => 'Unauthorized. Supplier authentication required.'
                ], 401);
                return false;
            }

            Session::flash('error', 'Please sign in to access the Supplier Portal.');
            $response->redirect('/login');
            return false;
        }

        // Check supplier profile and approval status
        $supplier = Supplier::findByUserId((int)$user['id']);
        if (!$supplier) {
            Session::flash('error', 'No supplier profile linked to this account.');
            $response->redirect('/login');
            return false;
        }

        // Store supplier profile in session for convenience
        Session::set('auth_supplier', $supplier);

        // Verification & Approval Check
        $status = $supplier['approval_status'] ?? 'PENDING_VERIFICATION';

        if ($status !== 'APPROVED' || ($supplier['status'] ?? '') !== 'ACTIVE') {
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => "Supplier access restricted. Status: {$status}."
                ], 403);
                return false;
            }

            if (($supplier['status'] ?? '') === 'INACTIVE') {
                Session::flash('error', 'Your supplier account has been deactivated by an administrator. Please contact support.');
                $response->redirect('/login');
                return false;
            }

            if ($status === 'SUSPENDED') {
                Session::flash('error', 'Your manufacturer account has been suspended. Please contact administrative support.');
                $response->redirect('/login');
                return false;
            }

            // Pending approval or rejected -> redirect to application status page
            $response->redirect('/supplier/pending-approval');
            return false;
        }

        return true;
    }
}
