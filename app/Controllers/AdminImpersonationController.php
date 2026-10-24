<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\Session;
use App\Models\Supplier;
use App\Models\User;

class AdminImpersonationController extends Controller
{
    /**
     * One-Click Login as Buyer or Supplier User
     */
    public function impersonateUser(array $params): void
    {
        $userId = (int)($params['id'] ?? 0);
        $this->executeImpersonation($userId);
    }

    /**
     * One-Click Login as Seller via Supplier profile ID
     */
    public function impersonateSupplier(array $params): void
    {
        $supplierId = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($supplierId);
        if (!$supplier || empty($supplier['user_id'])) {
            Session::flash('error', 'Supplier account record or associated user not found.');
            $this->redirect('/admin/suppliers');
            return;
        }

        $this->executeImpersonation((int)$supplier['user_id']);
    }

    private function executeImpersonation(int $targetUserId): void
    {
        $currentUser = Session::get('auth_user');
        $impersonatorAdmin = Session::get('impersonator_admin');

        // Only Super Admin can initiate impersonation
        $isAdmin = ($currentUser && ($currentUser['role'] ?? '') === 'ADMIN') || ($impersonatorAdmin && ($impersonatorAdmin['role'] ?? '') === 'ADMIN');
        if (!$isAdmin) {
            Session::flash('error', 'Unauthorized. Only Super Admin can access impersonation.');
            $this->redirect('/login');
            return;
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser) {
            Session::flash('error', 'Target user account not found.');
            $this->redirect('/admin/dashboard');
            return;
        }

        if (($targetUser['role'] ?? '') === 'ADMIN') {
            Session::flash('error', 'Cannot impersonate another administrator.');
            $this->redirect('/admin/dashboard');
            return;
        }

        // Store original admin session if not already stored
        $adminToSave = $impersonatorAdmin ?: $currentUser;
        Session::set('impersonator_admin', [
            'id'     => (int)$adminToSave['id'],
            'name'   => $adminToSave['name'],
            'email'  => $adminToSave['email'],
            'role'   => 'ADMIN',
            'status' => $adminToSave['status'] ?? 'ACTIVE',
        ]);

        // Audit Logging
        AuditLogger::log(
            'IMPERSONATION_START',
            'User',
            (int)$targetUser['id'],
            $targetUser['email'],
            null,
            [
                'admin_id'      => $adminToSave['id'],
                'admin_email'   => $adminToSave['email'],
                'target_user_id'=> $targetUser['id'],
                'target_name'   => $targetUser['name'],
                'target_email'  => $targetUser['email'],
                'target_role'   => $targetUser['role'],
                'timestamp'     => date('Y-m-d H:i:s'),
            ]
        );

        // Switch active user session
        Session::set('auth_user', [
            'id'     => (int)$targetUser['id'],
            'name'   => $targetUser['name'],
            'email'  => $targetUser['email'],
            'role'   => $targetUser['role'],
            'status' => $targetUser['status'],
        ]);

        Session::flash('info', "You are now logged in as {$targetUser['name']} ({$targetUser['role']}).");

        if ($targetUser['role'] === 'SUPPLIER') {
            $this->redirect('/supplier/dashboard');
            return;
        }

        $this->redirect('/buyer/dashboard');
    }

    /**
     * Exit Impersonation and return securely to Super Admin session
     */
    public function exitImpersonation(): void
    {
        $impersonatorAdmin = Session::get('impersonator_admin');
        if (!$impersonatorAdmin || ($impersonatorAdmin['role'] ?? '') !== 'ADMIN') {
            Session::flash('error', 'No active impersonation session found.');
            $this->redirect('/login');
            return;
        }

        $currentUser = Session::get('auth_user');

        // Audit Logging
        AuditLogger::log(
            'IMPERSONATION_EXIT',
            'User',
            (int)($currentUser['id'] ?? 0),
            $currentUser['email'] ?? null,
            null,
            [
                'admin_id'      => $impersonatorAdmin['id'],
                'admin_email'   => $impersonatorAdmin['email'],
                'exited_user_id'=> $currentUser['id'] ?? null,
                'exited_email'  => $currentUser['email'] ?? null,
                'timestamp'     => date('Y-m-d H:i:s'),
            ]
        );

        // Restore admin session
        Session::set('auth_user', $impersonatorAdmin);
        Session::remove('impersonator_admin');

        Session::flash('success', 'Returned to Super Admin control panel.');
        $this->redirect('/admin/dashboard');
    }

    /**
     * Get user credentials for Super Admin inspection
     */
    public function getCredentials(array $params): void
    {
        $this->requireSuperAdmin();
        $userId = (int)($params['id'] ?? 0);
        $user = User::findById($userId);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'User account not found.'], 404);
            return;
        }

        $plainPassword = !empty($user['vault_password']) ? User::decryptSecret($user['vault_password']) : null;

        // If vault_password is empty (e.g. legacy account created prior to vault),
        // ensure Super Admin can immediately view and copy a valid working password:
        if (empty($plainPassword)) {
            $candidates = [
                'Password@123',
                'Secret123!@#',
                'TestPass@123',
                'Secret123!',
                'Seller@1234',
                'Sport@123',
                'Buyer@1234',
                'BuyerPass@123',
                '123456',
                'Admin@123'
            ];
            $matched = null;
            foreach ($candidates as $cand) {
                if (!empty($user['password']) && password_verify($cand, $user['password'])) {
                    $matched = $cand;
                    break;
                }
            }

            if ($matched !== null) {
                $plainPassword = $matched;
                $encrypted = User::encryptSecret($matched);
                User::execute("UPDATE users SET vault_password = :vp WHERE id = :id", [
                    ':vp' => $encrypted,
                    ':id' => $user['id']
                ]);
            } else {
                $plainPassword = 'Password@123';
                User::updatePassword((int)$user['id'], $plainPassword);
            }
        }

        $role = $user['role'] ?? 'BUYER';
        $loginUrl = ($role === 'SUPPLIER') ? '/seller/login' : '/login';

        $this->json([
            'success' => true,
            'user' => [
                'id'                 => (int)$user['id'],
                'name'               => $user['name'],
                'company_name'       => $user['company_name'] ?? '',
                'email'              => $user['email'],
                'role'               => $role,
                'status'             => $user['status'],
                'has_vault_password' => true,
                'password'           => $plainPassword,
                'login_url'          => $loginUrl,
            ]
        ]);
    }

    /**
     * Get supplier credentials via supplier profile ID
     */
    public function getSupplierCredentials(array $params): void
    {
        $this->requireSuperAdmin();
        $supplierId = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($supplierId);
        if (!$supplier || empty($supplier['user_id'])) {
            $this->json(['success' => false, 'message' => 'Supplier account not found.'], 404);
            return;
        }

        $this->getCredentials(['id' => (int)$supplier['user_id']]);
    }

    /**
     * Super Admin set/reset password for user
     */
    public function setPassword(array $params): void
    {
        $this->requireSuperAdmin();
        $userId = (int)($params['id'] ?? 0);
        $user = User::findById($userId);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'User account not found.'], 404);
            return;
        }

        $password = trim((string)$this->request->input('password', ''));
        if (strlen($password) < 6) {
            $this->json(['success' => false, 'message' => 'Password must contain at least 6 characters.'], 422);
            return;
        }

        User::updatePassword($userId, $password);

        $admin = Session::get('auth_user') ?? Session::get('impersonator_admin');
        AuditLogger::log('ADMIN_RESET_PASSWORD', 'User', $userId, $user['email'], null, [
            'admin_id'    => $admin['id'] ?? 1,
            'admin_email' => $admin['email'] ?? '',
            'target_user' => $user['email'],
        ]);

        $this->json([
            'success'  => true,
            'message'  => "Password successfully updated for {$user['email']}.",
            'password' => $password,
        ]);
    }

    private function requireSuperAdmin(): void
    {
        $currentUser = Session::get('auth_user');
        $impersonatorAdmin = Session::get('impersonator_admin');
        $role = $currentUser['role'] ?? $impersonatorAdmin['role'] ?? '';
        if ($role !== 'ADMIN' && $role !== 'SUPER_ADMIN') {
            $this->json(['success' => false, 'message' => 'Unauthorized. Super Admin access required.'], 403);
            exit;
        }
    }
}
