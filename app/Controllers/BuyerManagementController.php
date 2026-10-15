<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\AuditLogger;
use App\Helpers\CSRF;
use App\Helpers\Exporter;
use App\Models\Country;
use App\Models\ProductInquiry;
use App\Models\User;

class BuyerManagementController extends Controller
{
    public function index(): void
    {
        $metrics   = User::getBuyerMetrics();
        $countries = Country::all();

        $this->render('admin/buyers', [
            'pageTitle'  => 'Buyer Management — Sports Junction Admin',
            'activeMenu' => 'buyers',
            'metrics'    => $metrics,
            'countries'  => $countries,
            'csrfToken'  => CSRF::token(),
        ], 'admin');
    }

    public function data(): void
    {
        $params = [
            'draw'                => $this->request->get('draw', 1),
            'start'               => $this->request->get('start', 0),
            'length'              => $this->request->get('length', 10),
            'search'              => $this->request->get('search', []),
            'order'               => $this->request->get('order', []),
            'account_status'      => $this->request->get('account_status', 'ALL'),
            'verification_status' => $this->request->get('verification_status', 'ALL'),
            'country_id'          => $this->request->get('country_id', 'ALL'),
        ];

        $result = User::getBuyersDataTable($params);
        $this->json($result);
    }

    public function show(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $buyer = User::getBuyerDetails((int) $id);
        if (!$buyer) {
            $this->json(['success' => false, 'message' => 'Buyer account not found.'], 404);
            return;
        }

        $enquiries = ProductInquiry::getBuyerEnquiries((int) $buyer['id'], $buyer['email']);

        $this->json([
            'success'   => true,
            'buyer'     => $buyer,
            'enquiries' => $enquiries,
        ]);
    }

    public function update(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $buyerId = (int) $id;
        $buyer = User::getBuyerDetails($buyerId);
        if (!$buyer) {
            $this->json(['success' => false, 'message' => 'Buyer account not found.'], 404);
            return;
        }

        $name  = trim((string) $this->request->post('name', ''));
        $email = strtolower(trim((string) $this->request->post('email', '')));

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Valid Full Name and Business Email are required.'], 422);
            return;
        }

        $payload = [
            'name'                => $name,
            'company_name'        => $this->request->post('company_name', ''),
            'email'               => $email,
            'phone'               => $this->request->post('phone', ''),
            'country_code'        => $this->request->post('country_code', '+91'),
            'country_id'          => $this->request->post('country_id', null),
            'status'              => $this->request->post('status', $buyer['status']),
            'mark_email_verified' => (bool) $this->request->post('mark_email_verified', false),
        ];

        $result = User::updateBuyerByAdmin($buyerId, $payload);
        if (!$result['success']) {
            $this->json($result, 422);
            return;
        }

        AuditLogger::log(
            'UPDATE',
            'Buyer',
            $buyerId,
            $email,
            [
                'old_email'  => $buyer['email'],
                'old_status' => $buyer['status'],
            ],
            [
                'new_email'     => $email,
                'new_status'    => $result['new_status'],
                'email_changed' => $result['email_changed'],
            ]
        );

        $msg = 'Buyer profile updated successfully.';
        if (!empty($result['email_changed'])) {
            $msg .= ' Since the email address was changed, email verification status has been reset to Pending Verification.';
        }

        $this->json([
            'success' => true,
            'message' => $msg,
            'metrics' => User::getBuyerMetrics(),
        ]);
    }

    public function toggleStatus(array|string $params): void
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $csrf = (string) ($this->request->post('csrf_token') ?? $this->request->header('X-CSRF-Token') ?? '');
        if (!CSRF::validate($csrf)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $buyerId = (int) $id;
        $buyer = User::getBuyerDetails($buyerId);
        if (!$buyer) {
            $this->json(['success' => false, 'message' => 'Buyer account not found.'], 404);
            return;
        }

        $requestedStatus = strtoupper(trim((string) $this->request->post('status', '')));
        if (!in_array($requestedStatus, ['ACTIVE', 'INACTIVE'], true)) {
            $requestedStatus = ($buyer['status'] === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        }

        User::setBuyerStatus($buyerId, $requestedStatus);

        AuditLogger::log(
            'STATUS_CHANGE',
            'Buyer',
            $buyerId,
            $buyer['email'],
            ['status' => $buyer['status']],
            ['status' => $requestedStatus]
        );

        $this->json([
            'success'    => true,
            'new_status' => $requestedStatus,
            'message'    => $requestedStatus === 'ACTIVE'
                ? "Buyer account ({$buyer['name']}) has been activated."
                : "Buyer account ({$buyer['name']}) has been deactivated. Login and protected actions are now blocked.",
            'metrics'    => User::getBuyerMetrics(),
        ]);
    }

    public function export(): void
    {
        $format = strtolower((string) $this->request->get('format', 'csv'));
        $params = [
            'account_status'      => $this->request->get('account_status', 'ALL'),
            'verification_status' => $this->request->get('verification_status', 'ALL'),
            'country_id'          => $this->request->get('country_id', 'ALL'),
            'search'              => $this->request->get('search', ''),
        ];

        $rows = User::getBuyersForExport($params);
        $headers = [
            'Buyer ID',
            'Full Name',
            'Company Name',
            'Business Email',
            'Mobile Number',
            'Country',
            'Email Verification Status',
            'Account Status',
            'Registration Date',
        ];

        AuditLogger::log('EXPORT', 'Buyer', null, "Exported " . count($rows) . " buyers as " . strtoupper($format));

        $filename = 'sports_junction_buyers_' . date('Ymd_His');
        if ($format === 'excel' || $format === 'xls') {
            Exporter::exportExcelXml($filename, $headers, $rows);
        } elseif ($format === 'pdf') {
            Exporter::exportPdfView('Sports Junction — Registered Buyers Directory', $headers, $rows);
        } else {
            Exporter::exportCsv($filename, $headers, $rows);
        }
    }
}
