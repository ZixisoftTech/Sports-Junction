<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Mailer;
use App\Models\ProfileChangeRequest;

class AdminProfileChangeController extends Controller
{
    public function index(): void
    {
        $role = (string)($this->request->get('role') ?? '');
        $requests = ProfileChangeRequest::getPendingRequests($role !== '' ? $role : null);
        $this->json(['success' => true, 'data' => $requests]);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $req = ProfileChangeRequest::findById($id);
        if (!$req) {
            $this->json(['success' => false, 'message' => 'Profile change request not found.'], 404);
            return;
        }

        $this->json(['success' => true, 'data' => $req]);
    }

    public function approve(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $req = ProfileChangeRequest::findById($id);
        if (!$req) {
            $this->json(['success' => false, 'message' => 'Profile change request not found.'], 404);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $ok = ProfileChangeRequest::approve($id, $adminId);
        if ($ok) {
            if (($req['role'] ?? '') === 'SUPPLIER') {
                $email = $req['user_email'] ?? '';
                $company = $req['supplier_company'] ?: ($req['user_name'] ?? 'Valued Manufacturer');
                if ($email) {
                    Mailer::sendSupplierProfileUpdateApproved($email, $company);
                }
            }
            $this->json(['success' => true, 'message' => 'Profile revision approved and published live.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to approve profile revision.'], 500);
        }
    }

    public function reject(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $req = ProfileChangeRequest::findById($id);
        if (!$req) {
            $this->json(['success' => false, 'message' => 'Profile change request not found.'], 404);
            return;
        }

        $reason = trim((string)($this->request->input('reason') ?? ''));
        if (empty($reason)) {
            $this->json(['success' => false, 'message' => 'Rejection feedback reason is required.'], 422);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $ok = ProfileChangeRequest::reject($id, $adminId, $reason);
        if ($ok) {
            if (($req['role'] ?? '') === 'SUPPLIER') {
                $email = $req['user_email'] ?? '';
                $company = $req['supplier_company'] ?: ($req['user_name'] ?? 'Valued Manufacturer');
                if ($email) {
                    Mailer::sendSupplierProfileUpdateRejected($email, $company, $reason);
                }
            }
            $this->json(['success' => true, 'message' => 'Profile change request rejected with feedback.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to reject profile revision.'], 500);
        }
    }
}
