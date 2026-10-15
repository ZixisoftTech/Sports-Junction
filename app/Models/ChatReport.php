<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ChatReport extends Model
{
    /**
     * File a new conversation report
     */
    public static function createReport(int $conversationId, int $reporterId, int $reportedId, string $reason, string $description = ''): array
    {
        $sql = "
            INSERT INTO `chat_reports` (
                `conversation_id`, `reporter_user_id`, `reported_user_id`,
                `reason`, `description`, `status`, `created_at`
            ) VALUES (
                :conversation_id, :reporter_id, :reported_id,
                :reason, :description, 'PENDING', NOW()
            )
        ";
        self::execute($sql, [
            ':conversation_id' => $conversationId,
            ':reporter_id' => $reporterId,
            ':reported_id' => $reportedId,
            ':reason' => $reason,
            ':description' => $description
        ]);

        $id = (int) self::db()->lastInsertId();
        return self::findById($id) ?? [];
    }

    /**
     * Find report by ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                r.*,
                rep.name AS reporter_first_name,
                '' AS reporter_last_name,
                rep.email AS reporter_email,
                rep.company_name AS reporter_company,
                target.name AS reported_first_name,
                '' AS reported_last_name,
                target.email AS reported_email,
                target.company_name AS reported_company
            FROM `chat_reports` r
            LEFT JOIN `users` rep ON r.reporter_user_id = rep.id
            LEFT JOIN `users` target ON r.reported_user_id = target.id
            WHERE r.id = :id
            LIMIT 1
        ";
        return self::fetch($sql, [':id' => $id]);
    }

    /**
     * Get all reports for Super Admin
     */
    public static function getAll(array $filters = []): array
    {
        $sql = "
            SELECT 
                r.*,
                rep.name AS reporter_first_name,
                '' AS reporter_last_name,
                rep.company_name AS reporter_company,
                target.name AS reported_first_name,
                '' AS reported_last_name,
                target.company_name AS reported_company,
                admin.name AS resolver_name
            FROM `chat_reports` r
            LEFT JOIN `users` rep ON r.reporter_user_id = rep.id
            LEFT JOIN `users` target ON r.reported_user_id = target.id
            LEFT JOIN `users` admin ON r.resolved_by = admin.id
            WHERE 1=1
        ";

        $params = [];
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND r.status = :status";
            $params[':status'] = strtoupper($filters['status']);
        }

        $sql .= " ORDER BY r.created_at DESC";

        return self::fetchAll($sql, $params);
    }

    /**
     * Resolve or dismiss a report
     */
    public static function resolve(int $reportId, int $adminUserId, string $status, string $adminNotes = ''): bool
    {
        $sql = "
            UPDATE `chat_reports` 
            SET `status` = :status, `admin_notes` = :notes, `resolved_by` = :admin_id, `resolved_at` = NOW(), `updated_at` = NOW()
            WHERE `id` = :id
        ";
        $stmt = self::execute($sql, [
            ':status' => $status,
            ':notes' => $adminNotes,
            ':admin_id' => $adminUserId,
            ':id' => $reportId
        ]);
        return $stmt->rowCount() > 0;
    }
}
