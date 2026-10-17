<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\Session;
use PDO;

require_once __DIR__ . '/Session.php';
require_once dirname(__DIR__) . '/Core/Request.php';

class AuditLogger
{
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $recordIdentifier = null,
        ?array $affectedCounts = null,
        array|string|null $details = null
    ): void {
        try {
            $detailsStr = is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details;
            $db = Database::getConnection();
            $user = Session::get('auth_user');
            $userId = $user['id'] ?? null;

            $request = new Request();
            $ip = $request->getIp();
            $userAgent = substr($request->getUserAgent(), 0, 255);

            $stmt = $db->prepare("
                INSERT INTO audit_logs (
                    user_id, action, entity_type, entity_id, record_identifier, affected_counts, details, ip_address, user_agent, created_at
                ) VALUES (
                    :user_id, :action, :entity_type, :entity_id, :record_identifier, :affected_counts, :details, :ip_address, :user_agent, NOW()
                )
            ");

            $stmt->execute([
                ':user_id'           => $userId,
                ':action'            => strtoupper($action),
                ':entity_type'       => $entityType,
                ':entity_id'         => $entityId,
                ':record_identifier' => $recordIdentifier,
                ':affected_counts'   => $affectedCounts !== null ? json_encode($affectedCounts) : null,
                ':details'           => $detailsStr,
                ':ip_address'        => $ip,
                ':user_agent'        => $userAgent,
            ]);
        } catch (\Throwable $e) {
            // Fail safely: never block primary transactions due to secondary audit log exceptions
            error_log("Audit log failure: " . $e->getMessage());
        }
    }
}
