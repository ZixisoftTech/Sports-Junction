<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class SiteFeedback extends Model
{
    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `site_feedbacks` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(150) NOT NULL,
                    `email` VARCHAR(191) NOT NULL,
                    `user_role` VARCHAR(50) NOT NULL DEFAULT 'BUYER',
                    `category` VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
                    `rating` TINYINT NOT NULL DEFAULT 5,
                    `subject` VARCHAR(255) NOT NULL,
                    `message` TEXT NOT NULL,
                    `ip_address` VARCHAR(45) NULL,
                    `status` ENUM('NEW', 'REVIEWED', 'ARCHIVED') NOT NULL DEFAULT 'NEW',
                    `admin_notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_feedback_status (`status`),
                    INDEX idx_feedback_created (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Throwable $e) {
            @error_log("SiteFeedback::ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Create new user feedback submission
     */
    public static function create(array $data): int
    {
        self::ensureSchema();
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
