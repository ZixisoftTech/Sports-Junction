<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class SiteFeedback extends Model
{
    /**
     * Create new user feedback submission
     */
    public static function create(array $data): int
    {
        try {
            $name = trim((string)($data['name'] ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $role = strtoupper(trim((string)($data['user_role'] ?? 'BUYER')));
            $category = strtoupper(trim((string)($data['category'] ?? 'GENERAL')));
            $rating = max(1, min(5, (int)($data['rating'] ?? 5)));
            $subject = trim((string)($data['subject'] ?? ''));
            $message = trim((string)($data['message'] ?? ''));
            $ip = (string)($data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

            self::execute("
                INSERT INTO `site_feedbacks` (
                    `name`, `email`, `user_role`, `category`, 
                    `rating`, `subject`, `message`, `ip_address`, 
                    `status`, `created_at`
                ) VALUES (
                    :name, :email, :role, :category, 
                    :rating, :subject, :message, :ip, 
                    'NEW', NOW()
                )
            ", [
                ':name'     => $name,
                ':email'    => $email,
                ':role'     => $role,
                ':category' => $category,
                ':rating'   => $rating,
                ':subject'  => $subject,
                ':message'  => $message,
                ':ip'       => $ip
            ]);

            $feedbackId = (int) self::lastInsertId();

            // Notify Super Admins
            Notification::notifySuperAdmins(
                'FEEDBACK_RECEIVED',
                "New Feedback from {$name} ({$rating}★ - {$category})",
                mb_substr($message, 0, 120),
                '/admin/settings',
                'FEEDBACK',
                $feedbackId
            );

            return $feedbackId;
        } catch (Throwable $e) {
            error_log("SiteFeedback::create error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Alias for create
     */
    public static function submit(array $data): int
    {
        return self::create($data);
    }

    /**
     * Get recent feedback entries
     */
    public static function all(int $limit = 50): array
    {
        try {
            return self::fetchAll("SELECT * FROM `site_feedbacks` ORDER BY `id` DESC LIMIT " . (int)$limit);
        } catch (Throwable $e) {
            return [];
        }
    }
}
