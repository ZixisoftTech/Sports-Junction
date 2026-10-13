<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Mailer;
use App\Helpers\Validator;
use App\Models\BusinessType;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Supplier;
use App\Models\User;

class SupplierApprovalController extends Controller
{
    public function index(): void
    {
        $metrics = Supplier::getMetrics();
        $countries = Country::allActive();
        $businessTypes = BusinessType::allActive();

        $this->render('admin/suppliers', [
            'pageTitle'     => 'Supplier & Factory Management — Sports Junction',
            'activeMenu'    => 'suppliers',
            'metrics'       => $metrics,
            'countries'     => $countries,
            'businessTypes' => $businessTypes,
        ]);
    }

    public function data(): void
    {
        $params = $this->request->all();
        $result = Supplier::getDataTable($params);
        $this->json($result);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($id);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        $deps = Supplier::checkDependencies($id);
        $supplier['dependencies'] = $deps;

        $this->json(['success' => true, 'data' => $supplier]);
    }

    public function store(): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'company_name'     => 'required|max:150',
            'contact_person'   => 'required|max:100',
            'email'            => 'required|email|max:191',
            'phone'            => 'required|max:30',
            'business_type_id' => 'required|integer',
            'country_id'       => 'required|integer',
            'state_id'         => 'required|integer',
            'city_id'          => 'required|integer',
        ];

        if (!$validator->validate($rules)) {
            $this->json(['success' => false, 'message' => $validator->firstError() ?? 'Validation failed.'], 422);
            return;
        }

        $email = strtolower(trim((string)$input['email']));
        if (User::emailExists($email)) {
            $this->json(['success' => false, 'message' => 'An account with this email address already exists.'], 422);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);
        $initialApproval = in_array($input['approval_status'] ?? '', ['APPROVED', 'PENDING_APPROVAL', 'PENDING_VERIFICATION']) 
            ? $input['approval_status'] 
            : 'APPROVED';

        // Generate temporary secure password
        $tempPassword = bin2hex(random_bytes(6)) . 'A1!';

        try {
            $userId = User::create([
                'name'                 => trim($input['contact_person']),
                'company_name'         => trim($input['company_name']),
                'email'                => $email,
                'password'             => $tempPassword,
                'role'                 => 'SUPPLIER',
                'status'               => ($initialApproval === 'APPROVED') ? 'ACTIVE' : 'INACTIVE',
                'must_change_password' => 1,
                'phone'                => trim($input['phone']),
                'country_code'         => $input['country_code'] ?? '+91',
                'country_id'           => (int)$input['country_id'],
                'email_verified_at'    => ($initialApproval === 'APPROVED') ? date('Y-m-d H:i:s') : null,
            ]);

            $supplierId = Supplier::create([
                'user_id'          => $userId,
                'business_type_id' => (int)$input['business_type_id'],
                'country_id'       => (int)$input['country_id'],
                'state_id'         => (int)$input['state_id'],
                'city_id'          => (int)$input['city_id'],
                'company_name'     => trim($input['company_name']),
                'contact_person'   => trim($input['contact_person']),
                'email'            => $email,
                'phone'            => trim($input['phone']),
                'country_code'     => $input['country_code'] ?? '+91',
                'address'          => trim($input['address'] ?? ''),
                'postal_code'      => trim($input['postal_code'] ?? ''),
                'status'           => ($initialApproval === 'APPROVED') ? 'ACTIVE' : 'INACTIVE',
                'approval_status'  => $initialApproval,
            ]);

            AuditLogger::log('CREATE', 'Supplier', $supplierId, trim($input['company_name']), null, "Admin manually created supplier account with status {$initialApproval}");

            $this->json([
                'success' => true,
                'message' => "Supplier '{$input['company_name']}' created successfully.",
                'data'    => ['id' => $supplierId]
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to create supplier: ' . $e->getMessage()], 500);
        }
    }

    public function update(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($id);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'company_name'     => 'required|max:150',
            'contact_person'   => 'required|max:100',
            'email'            => 'required|email|max:191',
            'phone'            => 'required|max:30',
            'business_type_id' => 'required|integer',
            'country_id'       => 'required|integer',
            'state_id'         => 'required|integer',
            'city_id'          => 'required|integer',
        ];

        if (!$validator->validate($rules)) {
            $this->json(['success' => false, 'message' => $validator->firstError() ?? 'Validation failed.'], 422);
            return;
        }

        $email = strtolower(trim((string)$input['email']));
        if (User::emailExists($email, (int)($supplier['user_id'] ?? 0))) {
            $this->json(['success' => false, 'message' => 'This email address is already assigned to another account.'], 422);
            return;
        }

        $ok = Supplier::updateProfile($id, $input);
        if ($ok) {
            AuditLogger::log('UPDATE', 'Supplier', $id, trim($input['company_name']), null, 'Super Admin edited supplier profile');
            $this->json(['success' => true, 'message' => "Supplier details updated successfully."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update supplier profile.'], 500);
        }
    }

    public function approve(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($id);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $ok = Supplier::updateApprovalStatus($id, 'APPROVED', null, $adminId);
        if ($ok) {
            Mailer::sendSupplierApproved($supplier['email'], $supplier['company_name']);
            AuditLogger::log('APPROVE', 'Supplier', $id, $supplier['company_name'], null, 'Super Admin approved supplier application');
            $this->json(['success' => true, 'message' => "Supplier '{$supplier['company_name']}' approved successfully."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update approval status.'], 500);
        }
    }

    public function reject(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($id);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        $reason = trim((string)$this->request->input('reason', ''));
        if (empty($reason)) {
            $this->json(['success' => false, 'message' => 'Rejection reason is mandatory.'], 422);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $ok = Supplier::updateApprovalStatus($id, 'REJECTED', $reason, $adminId);
        if ($ok) {
            Mailer::sendSupplierRejected($supplier['email'], $supplier['company_name'], $reason);
            AuditLogger::log('REJECT', 'Supplier', $id, $supplier['company_name'], ['reason' => $reason], 'Super Admin rejected supplier application');
            $this->json(['success' => true, 'message' => "Supplier application rejected."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update approval status.'], 500);
        }
    }

    public function suspend(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($id);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        $reason = trim((string)$this->request->input('reason', 'Account suspended by administrator'));
        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $ok = Supplier::updateApprovalStatus($id, 'SUSPENDED', $reason, $adminId);
        if ($ok) {
            AuditLogger::log('SUSPEND', 'Supplier', $id, $supplier['company_name'], ['reason' => $reason], 'Super Admin suspended supplier');
            $this->json(['success' => true, 'message' => "Supplier '{$supplier['company_name']}' suspended."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to suspend supplier.'], 500);
        }
    }

    public function reactivate(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($id);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        $adminUser = $this->authUser();
        $adminId = (int)($adminUser['id'] ?? 1);

        $ok = Supplier::updateApprovalStatus($id, 'APPROVED', null, $adminId);
        if ($ok) {
            AuditLogger::log('REACTIVATE', 'Supplier', $id, $supplier['company_name'], null, 'Super Admin reactivated supplier');
            $this->json(['success' => true, 'message' => "Supplier '{$supplier['company_name']}' reactivated."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to reactivate supplier.'], 500);
        }
    }

    public function delete(array $params): void
    {
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $id = (int)($params['id'] ?? 0);
        $supplier = Supplier::findById($id);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        $deps = Supplier::checkDependencies($id);
        if (!$deps['can_delete']) {
            $msg = "Cannot delete supplier '{$supplier['company_name']}': ";
            $parts = [];
            if ($deps['products'] > 0) $parts[] = "{$deps['products']} product(s)";
            if ($deps['inquiries'] > 0) $parts[] = "{$deps['inquiries']} customer enquiry/enquiries";
            $msg .= implode(' and ', $parts) . " exist. Deactivate or suspend the supplier instead to preserve business integrity.";
            
            $this->json(['success' => false, 'message' => $msg, 'dependencies' => $deps], 409);
            return;
        }

        $ok = Supplier::delete($id);
        if ($ok) {
            AuditLogger::log('DELETE', 'Supplier', $id, $supplier['company_name'], null, 'Super Admin permanently deleted supplier');
            $this->json(['success' => true, 'message' => "Supplier '{$supplier['company_name']}' deleted successfully."]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to delete supplier.'], 500);
        }
    }
}
