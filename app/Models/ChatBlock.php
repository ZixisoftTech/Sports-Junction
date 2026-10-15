<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ChatBlock extends Model
{
    /**
     * Block a user
     */
    public static function block(int $blockerId, int $blockedId): bool
    {
        $sql = "
            INSERT INTO `chat_blocks` (`blocker_user_id`, `blocked_user_id`, `status`, `created_at`, `updated_at`)
            VALUES (:blocker, :blocked, 'BLOCKED', NOW(), NOW())
            ON DUPLICATE KEY UPDATE `status` = 'BLOCKED', `updated_at` = NOW()
        ";
        $stmt = self::execute($sql, [':blocker' => $blockerId, ':blocked' => $blockedId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Unblock a user
     */
    public static function unblock(int $blockerId, int $blockedId): bool
    {
        $sql = "UPDATE `chat_blocks` SET `status` = 'UNBLOCKED', `updated_at` = NOW() WHERE `blocker_user_id` = :blocker AND `blocked_user_id` = :blocked";
        $stmt = self::execute($sql, [':blocker' => $blockerId, ':blocked' => $blockedId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if a block exists between two users in either direction
     */
    public static function isBlocked(int $userId1, int $userId2): bool
    {
        $sql = "
            SELECT COUNT(*) 
            FROM `chat_blocks` 
            WHERE status = 'BLOCKED' 
              AND (
                (blocker_user_id = :u1 AND blocked_user_id = :u2)
                OR (blocker_user_id = :u2_b AND blocked_user_id = :u1_b)
              )
        ";
        $stmt = self::execute($sql, [':u1' => $userId1, ':u2' => $userId2, ':u1_b' => $userId1, ':u2_b' => $userId2]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * Check if specifically user A blocked user B
     */
    public static function isBlockedBy(int $blockerId, int $blockedId): bool
    {
        $sql = "SELECT COUNT(*) FROM `chat_blocks` WHERE blocker_user_id = :blocker AND blocked_user_id = :blocked AND status = 'BLOCKED'";
        $stmt = self::execute($sql, [':blocker' => $blockerId, ':blocked' => $blockedId]);
        return ((int) $stmt->fetchColumn()) > 0;
    }
}
