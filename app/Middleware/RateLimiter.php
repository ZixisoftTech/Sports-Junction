<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use PDO;

class RateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    public static function isLocked(string $ip, string $email): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT attempts, locked_until, last_attempt_at 
                FROM login_attempts 
                WHERE ip_address = :ip AND email = :email
                LIMIT 1
            ");
            $stmt->execute([':ip' => $ip, ':email' => $email]);
            $record = $stmt->fetch();

            if (!$record) {
                return false;
            }

            if (!empty($record['locked_until'])) {
                $lockedUntil = strtotime($record['locked_until']);
                if ($lockedUntil > time()) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getRemainingLockoutSeconds(string $ip, string $email): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT locked_until 
                FROM login_attempts 
                WHERE ip_address = :ip AND email = :email
                LIMIT 1
            ");
            $stmt->execute([':ip' => $ip, ':email' => $email]);
            $record = $stmt->fetch();

            if ($record && !empty($record['locked_until'])) {
                $rem = strtotime($record['locked_until']) - time();
                return max(0, $rem);
            }
        } catch (\Throwable) {}

        return 0;
    }

    public static function recordFailedAttempt(string $ip, string $email): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT id, attempts 
                FROM login_attempts 
                WHERE ip_address = :ip AND email = :email
                LIMIT 1
            ");
            $stmt->execute([':ip' => $ip, ':email' => $email]);
            $record = $stmt->fetch();

            $now = date('Y-m-d H:i:s');

            if ($record) {
                $newAttempts = (int)$record['attempts'] + 1;
                $lockedUntil = null;
                if ($newAttempts >= self::MAX_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_SECONDS);
                }

                $update = $db->prepare("
                    UPDATE login_attempts 
                    SET attempts = :attempts, last_attempt_at = :last, locked_until = :locked
                    WHERE id = :id
                ");
                $update->execute([
                    ':attempts' => $newAttempts,
                    ':last'     => $now,
                    ':locked'   => $lockedUntil,
                    ':id'       => $record['id']
                ]);
            } else {
                $insert = $db->prepare("
                    INSERT INTO login_attempts (ip_address, email, attempts, last_attempt_at, locked_until)
                    VALUES (:ip, :email, 1, :last, NULL)
                ");
                $insert->execute([
                    ':ip'    => $ip,
                    ':email' => $email,
                    ':last'  => $now
                ]);
            }
        } catch (\Throwable $e) {
            error_log("RateLimiter failed attempt log error: " . $e->getMessage());
        }
    }

    public static function clearAttempts(string $ip, string $email): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = :ip AND email = :email");
            $stmt->execute([':ip' => $ip, ':email' => $email]);
        } catch (\Throwable $e) {
            error_log("RateLimiter clear error: " . $e->getMessage());
        }
    }
}
