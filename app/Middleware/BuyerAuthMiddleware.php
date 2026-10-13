<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Session;

class BuyerAuthMiddleware
{
    public function handle(Request $request, Response $response): bool
    {
        Session::start();
        $user = Session::get('auth_user');

        if (!$user || !isset($user['id']) || ($user['role'] ?? '') !== 'BUYER') {
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => 'Unauthorized. Buyer login required.'
                ], 401);
                return false;
            }

            Session::flash('error', 'Please sign in to your Buyer Account to continue.');
            $response->redirect('/login');
            return false;
        }

        $liveUser = \App\Models\User::findById((int)$user['id']);
        $liveStatus = $liveUser['status'] ?? '';

        if (!$liveUser || ($liveUser['role'] ?? '') !== 'BUYER' || $liveStatus !== 'ACTIVE') {
            Session::destroy();
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => 'Account is deactivated, unverified, or suspended.'
                ], 403);
                return false;
            }

            Session::flash('error', $liveStatus === 'INACTIVE'
                ? 'Your account has been deactivated by an administrator.'
                : 'Account is not active. Please complete email verification.');
            $response->redirect('/login');
            return false;
        }

        // Keep session synced with latest name/email/status
        Session::set('auth_user', [
            'id'     => (int)$liveUser['id'],
            'name'   => $liveUser['name'],
            'email'  => $liveUser['email'],
            'role'   => $liveUser['role'],
            'status' => $liveUser['status'],
        ]);

        return true;
    }
}
