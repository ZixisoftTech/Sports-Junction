<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Models\Notification;
use Throwable;

class AdminNotificationController extends Controller
{
    private function requireAdmin(): array
    {
        $user = $this->authUser();
        if (!$user || strtoupper($user['role'] ?? '') !== 'SUPER_ADMIN') {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'Unauthorized access'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }
        return $user;
    }

    /**
     * GET /admin/notifications
     * Dedicated Notification Center
     */
    public function index(): void
    {
        $admin = $this->requireAdmin();
        $adminId = (int) $admin['id'];

        // Synchronize actual recent business events so admin immediately has accurate notifications
        Notification::syncExistingRecentEvents($adminId);

        $tab = $this->request->get('tab', 'all');
        $search = $this->request->get('search', '');
        $dateFrom = $this->request->get('date_from', '');
        $dateTo = $this->request->get('date_to', '');
        $page = max(1, (int) $this->request->get('page', 1));

        $filters = [
            'tab'       => $tab,
            'search'    => $search,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];

        $result = Notification::getAdminNotifications($adminId, $filters, $page, 15);

        $this->render('admin/notifications', [
            'pageTitle'       => 'Notification Center — Sports Junction Super Admin',
            'metaDescription' => 'Monitor real-time enquiries, RFQs, buyer messages, and pending approval alerts in one centralized dashboard.',
            'activeMenu'      => 'notifications',
            'notifications'   => $result['data'],
            'total'           => $result['total'],
            'currentPage'     => $result['page'],
            'totalPages'      => $result['total_pages'],
            'perPage'         => $result['per_page'],
            'counts'          => $result['counts'],
            'filters'         => $filters,
        ], 'admin');
    }

    /**
     * GET /api/admin/notifications/poll
     * Real-time polling endpoint for bell badge and recent dropdown
     */
    public function poll(): void
    {
        $admin = $this->requireAdmin();
        $adminId = (int) $admin['id'];

        $unreadCount = Notification::getUnreadCount($adminId);
        $recent = Notification::getRecentForAdmin($adminId, 8);
        $counts = Notification::getCategoryCounts($adminId);

        $this->json([
            'success'       => true,
            'unread_count'  => $unreadCount,
            'notifications' => $recent,
            'counts'        => $counts,
            'polled_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * GET /admin/notifications/{id}/go
     * 1-Click navigation: marks notification as read and redirects to target record
     */
    public function go(array $params): void
    {
        $admin = $this->requireAdmin();
        $adminId = (int) $admin['id'];
        $id = (int) ($params['id'] ?? 0);

        if ($id > 0) {
            $notif = Notification::findById($id, $adminId);
            if (!$notif) {
                $raw = Notification::fetch("SELECT * FROM `notifications` WHERE `id` = :id LIMIT 1", [':id' => $id]);
                if ($raw) {
                    $dest = Notification::resolveAdminLink($raw);
                    Notification::markAsRead($adminId, $id);
                    if (!empty($raw['related_id']) && in_array(strtoupper((string)($raw['related_type'] ?? '')), ['ENQUIRY', 'INQUIRY'])) {
                        Notification::markEnquiryAsRead((int)$raw['related_id'], $adminId);
                    }
                    $this->redirect($dest);
                    return;
                }
            }

            if ($notif) {
                Notification::markAsRead($adminId, $id);
                if (!empty($notif['related_id']) && in_array(strtoupper((string)($notif['related_type'] ?? '')), ['ENQUIRY', 'INQUIRY'])) {
                    Notification::markEnquiryAsRead((int)$notif['related_id'], $adminId);
                }
                $dest = Notification::resolveAdminLink($notif);
                $this->redirect($dest);
                return;
            }
        }

        $this->redirect('/admin/notifications');
    }

    /**
     * POST /api/admin/notifications/{id}/read
     */
    public function markRead(array $params): void
    {
        $admin = $this->requireAdmin();
        $adminId = (int) $admin['id'];
        $id = (int) ($params['id'] ?? $this->request->get('id', 0));

        $ok = ($id > 0) ? Notification::markAsRead($adminId, $id) : false;
        if ($id > 0) {
            $notif = Notification::fetch("SELECT related_type, related_id FROM `notifications` WHERE `id` = :id LIMIT 1", [':id' => $id]);
            if ($notif && !empty($notif['related_id']) && in_array(strtoupper((string)($notif['related_type'] ?? '')), ['ENQUIRY', 'INQUIRY'])) {
                Notification::markEnquiryAsRead((int)$notif['related_id'], $adminId);
            }
        }
        $unreadCount = Notification::getUnreadCount($adminId);

        $this->json([
            'success'      => $ok,
            'id'           => $id,
            'unread_count' => $unreadCount,
            'message'      => $ok ? 'Notification marked as read.' : 'Failed to update notification.',
        ]);
    }

    /**
     * POST /api/admin/notifications/{id}/unread
     */
    public function markUnread(array $params): void
    {
        $admin = $this->requireAdmin();
        $adminId = (int) $admin['id'];
        $id = (int) ($params['id'] ?? $this->request->get('id', 0));

        $ok = ($id > 0) ? Notification::markAsUnread($adminId, $id) : false;
        $unreadCount = Notification::getUnreadCount($adminId);

        $this->json([
            'success'      => $ok,
            'id'           => $id,
            'unread_count' => $unreadCount,
            'message'      => $ok ? 'Notification marked as unread.' : 'Failed to update notification.',
        ]);
    }

    /**
     * POST /api/admin/notifications/mark-all-read
     */
    public function markAllRead(): void
    {
        $admin = $this->requireAdmin();
        $adminId = (int) $admin['id'];
        $tab = $this->request->get('tab');

        $affected = Notification::markAllAsRead($adminId, $tab);
        $unreadCount = Notification::getUnreadCount($adminId);

        $this->json([
            'success'      => true,
            'affected'     => $affected,
            'unread_count' => $unreadCount,
            'message'      => 'All notifications marked as read.',
        ]);
    }
}
