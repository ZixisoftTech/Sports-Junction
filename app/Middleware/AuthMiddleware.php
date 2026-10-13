<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Session;

class AuthMiddleware
{
    public function handle(Request $request, Response $response): bool
    {
        Session::start();
        $user = Session::get('auth_user');

        if (!$user || !isset($user['id']) || ($user['role'] ?? '') !== 'SUPER_ADMIN') {
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => 'Unauthorized. Super Admin authentication required.'
                ], 401);
                return false;
            }

            Session::flash('error', 'Please log in as Super Admin to access this page.');
            $response->redirect('/admin/login');
            return false;
        }

        // Active status check
        $userStatus = $user['status'] ?? 'ACTIVE';
        if ($userStatus !== 'ACTIVE') {
            Session::destroy();
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.'
                ], 403);
                return false;
            }

            Session::flash('error', 'Your account has been deactivated.');
            $response->redirect('/admin/login');
            return false;
        }

        return true;
    }
}
