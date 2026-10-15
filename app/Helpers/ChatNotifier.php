<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\User;
use PDO;
use Throwable;

/**
 * ChatNotifier Helper
 * Dispatches in-app notifications, email notifications, and SMS triggers for chat and negotiation events.
 */
class ChatNotifier
{
    /**
     * Dispatch an in-app and email notification to a user
     */
    public static function notify(
        int $recipientUserId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        bool $sendEmail = true
    ): void {
        try {
            $db = Database::getConnection();

            // 1. Insert in-app notification
            $stmt = $db->prepare("
                INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`)
                VALUES (:user_id, :type, :title, :message, :link, 0, NOW())
            ");
            $stmt->execute([
                'user_id' => $recipientUserId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link
            ]);

            // 2. Send email notification if user email is known
            if ($sendEmail) {
                self::dispatchEmail($recipientUserId, $title, $message, $link);
            }
        } catch (Throwable $e) {
            error_log("ChatNotifier::notify error: " . $e->getMessage());
        }
    }

    /**
     * Dispatch an email notification to the recipient
     */
    private static function dispatchEmail(int $userId, string $title, string $message, ?string $link): void
    {
        try {
            $user = User::findById($userId);
            if (!$user || empty($user['email'])) {
                return;
            }

            $recipientName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Valued Partner';
            $actionUrl = $link ? (Env::get('APP_URL', 'http://localhost/Antigravity/Sports-Junction') . $link) : '';

            $htmlContent = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #0f172a; margin: 0;'>Sports Junction B2B</h2>
                        <p style='color: #64748b; font-size: 13px; margin: 4px 0 0;'>India's Sports Manufacturing Marketplace</p>
                    </div>
                    <div style='background-color: #f8fafc; padding: 16px; border-radius: 6px; margin-bottom: 20px;'>
                        <h3 style='color: #1e293b; margin-top: 0;'>{$title}</h3>
                        <p style='color: #334155; line-height: 1.6; margin-bottom: 0;'>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>";

            if ($actionUrl) {
                $htmlContent .= "
                    <div style='text-align: center; margin: 24px 0;'>
                        <a href='{$actionUrl}' style='display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;'>View in Sports Junction</a>
                    </div>";
            }

            $htmlContent .= "
                    <p style='color: #94a3b8; font-size: 12px; text-align: center; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 16px;'>
                        This is an automated notification from Sports Junction. Please do not reply directly to this email.
                    </p>
                </div>
            ";

            Mailer::send($user['email'], $recipientName, $title, $htmlContent);
        } catch (Throwable $e) {
            error_log("ChatNotifier::dispatchEmail error: " . $e->getMessage());
        }
    }

    /**
     * Get unread notifications count for a user
     */
    public static function getUnreadCount(int $userId): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = :uid AND `is_read` = 0");
            $stmt->execute(['uid' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Mark notifications as read
     */
    public static function markAsRead(int $userId, ?int $notificationId = null): void
    {
        try {
            $db = Database::getConnection();
            if ($notificationId !== null) {
                $stmt = $db->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `id` = :id AND `user_id` = :uid");
                $stmt->execute(['id' => $notificationId, 'uid' => $userId]);
            } else {
                $stmt = $db->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = :uid");
                $stmt->execute(['uid' => $userId]);
            }
        } catch (Throwable $e) {
            error_log("ChatNotifier::markAsRead error: " . $e->getMessage());
        }
    }
}
