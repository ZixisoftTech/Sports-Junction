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

        if (($user['status'] ?? '') !== 'ACTIVE') {
            Session::destroy();
            if ($request->isAjax()) {
                $response->json([
                    'success' => false,
                    'message' => 'Account is unverified or suspended.'
                ], 403);
                return false;
            }

            Session::flash('error', 'Account is not active. Please complete email verification.');
            $response->redirect('/login');
            return false;
        }

        return true;
    }
}
