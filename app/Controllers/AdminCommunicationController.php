<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatOffer;
use App\Models\ChatReport;
use App\Models\Supplier;

class AdminCommunicationController extends Controller
{
    private function requireAdmin(): array
    {
        $user = $this->authUser();
        if (!$user || strtoupper($user['role'] ?? '') !== 'SUPER_ADMIN') {
            $this->redirect('/admin/login');
            exit;
        }
        return $user;
    }

    /**
     * GET /admin/communication
     * Super Admin Communication & Negotiation Center
     */
    public function index(): void
    {
        $admin = $this->requireAdmin();

        $convParam = (int) ($this->request->get('conv') ?? $this->request->get('conversation_id') ?? 0);
        if ($convParam > 0) {
            $this->redirect("/admin/communication/{$convParam}");
            return;
        }

        $stats = ChatConversation::getStats();
        $statusFilter = $this->request->get('status', 'all');
        $supplierFilter = $this->request->get('supplier_id', '');
        $flaggedOnly = $this->request->get('flagged', '0');
        $reportedOnly = $this->request->get('reported', '0');
        $search = $this->request->get('search', '');

        $conversations = ChatConversation::getAllForAdmin([
            'status' => $statusFilter,
            'supplier_id' => $supplierFilter,
            'flagged' => $flaggedOnly,
            'reported' => $reportedOnly,
            'search' => $search
        ]);

        $suppliers = Supplier::all();
        $reports = ChatReport::getAll();

        $this->render('admin/communication', [
            'pageTitle' => 'Communication & Deal Monitoring Center — Super Admin',
            'activeMenu' => 'communication',
            'stats' => $stats,
            'conversations' => $conversations,
            'suppliers' => $suppliers,
            'reports' => $reports,
            'statusFilter' => $statusFilter,
            'supplierFilter' => $supplierFilter,
            'flaggedOnly' => $flaggedOnly,
            'reportedOnly' => $reportedOnly,
            'search' => $search
        ], 'admin');
    }

    /**
     * GET /admin/communication/{id}
     * Comprehensive Conversation Audit, History & Intervention View
     */
    public function show(array $params = []): void
    {
        $admin = $this->requireAdmin();
        $convId = (int) ($params['id'] ?? 0);

        $conversation = ChatConversation::findById($convId);
        if (!$conversation) {
            Session::flash('error', 'Conversation not found.');
            $this->redirect('/admin/communication');
            return;
        }

        $messages = ChatMessage::getByConversation($convId, null, 200, true);
        $offers = ChatOffer::getHistory($convId);
        $latestOffer = ChatOffer::getLatest($convId);
        $reports = ChatReport::getAll();
        $convReports = array_filter($reports, fn($r) => (int) $r['conversation_id'] === $convId);

        $this->render('admin/communication_detail', [
            'pageTitle' => "Conversation Audit #{$convId} — Super Admin",
            'activeMenu' => 'communication',
            'conversation' => $conversation,
            'messages' => $messages,
            'offers' => $offers,
            'latestOffer' => $latestOffer,
            'reports' => $convReports,
            'admin' => $admin
        ], 'admin');
    }

    /**
     * POST /admin/communication/reports/{id}/resolve
     * Resolve or dismiss safety report
     */
    public function resolveReport(array $params = []): void
    {
        $admin = $this->requireAdmin();
        $reportId = (int) ($params['id'] ?? 0);
        $status = $this->request->post('status', 'RESOLVED');
        $notes = trim((string) $this->request->post('admin_notes', ''));

        ChatReport::resolve($reportId, (int) $admin['id'], $status, $notes);

        Session::flash('success', "Report #{$reportId} status updated to {$status}.");
        $this->redirect('/admin/communication');
    }
}
