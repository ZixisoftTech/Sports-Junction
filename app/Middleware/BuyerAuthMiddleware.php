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

        if ($user && isset($user['id']) && ($user['role'] ?? '') === 'SUPER_ADMIN') {
            if ($request->isAjax()) {
                return true;
            }
            $targetPath = $request->getPath();
            if (str_starts_with($targetPath, '/buyer/messages')) {
                $convId = (int) ($request->get('conversation_id') ?? $request->get('conv') ?? 0);
                $dest = $convId > 0 ? "/admin/communication/{$convId}" : "/admin/communication";
                $response->redirect($dest);
                return false;
            }
            if (str_starts_with($targetPath, '/buyer/sample-enquiries')) {
                $response->redirect('/admin/sample-enquiries');
                return false;
            }
            if (str_starts_with($targetPath, '/buyer/enquiries')) {
                $response->redirect('/admin/enquiries');
                return false;
            }
            $response->redirect('/admin/dashboard');
            return false;
        }

        if (!$user || !isset($user['id']) || ($user['role'] ?? '') !== 'BUYER') {
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => 'Unauthorized. Buyer login required.'
                ], 401);
                return false;
            }

            $targetPath = $request->getPath();
            Session::set('auth_redirect', $targetPath);
            Session::flash('error', 'Please sign in to your Buyer Account to continue.');
            $response->redirect('/login?redirect=' . urlencode($targetPath));
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
