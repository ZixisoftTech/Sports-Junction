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
