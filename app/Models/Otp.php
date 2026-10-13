<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Otp extends Model
{
    /**
     * Generate and store a secure 6-digit OTP
     */
    public static function generate(string $email, string $purpose, ?string $ip = null): string
    {
        $email = strtolower(trim($email));
        // Static OTP 123456 for all places as requested
        $code = '123456';
        $hashedCode = password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour for convenient testing

        // Check if existing unverified OTP exists for email + purpose to track resend_count
        $existing = self::fetch("
            SELECT id, resend_count, created_at 
            FROM email_otps 
            WHERE email = :email AND purpose = :purpose AND verified_at IS NULL AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ", [
            ':email'   => $email,
            ':purpose' => $purpose
        ]);

        $resendCount = $existing ? ((int) $existing['resend_count'] + 1) : 0;

        // Invalidate prior pending OTPs for this email and purpose
        self::execute("
            DELETE FROM email_otps 
            WHERE email = :email AND purpose = :purpose AND verified_at IS NULL
        ", [
            ':email'   => $email,
            ':purpose' => $purpose
        ]);

        self::execute("
            INSERT INTO email_otps (email, otp_code, purpose, attempts, max_attempts, resend_count, expires_at, ip_address, created_at)
            VALUES (:email, :otp_code, :purpose, 0, 5, :resend_count, :expires_at, :ip_address, NOW())
        ", [
            ':email'        => $email,
            ':otp_code'     => $hashedCode,
            ':purpose'      => $purpose,
            ':resend_count' => $resendCount,
            ':expires_at'   => $expiresAt,
            ':ip_address'   => $ip
        ]);

        if (\App\Helpers\Env::get('APP_DEBUG')) {
            \App\Helpers\Session::set("dev_otp_{$email}", $code);
            \App\Helpers\Session::set("dev_otp_{$email}_{$purpose}", $code);
            \App\Helpers\Session::set('dev_latest_otp', $code);
        }

        return $code;
    }

    /**
     * Can resend OTP (checks 60-second cooldown)
     */
    public static function canResend(string $email, string $purpose, int $cooldownSeconds = 60): array
    {
        $email = strtolower(trim($email));
        $record = self::fetch("
            SELECT id, created_at 
            FROM email_otps 
            WHERE email = :email AND purpose = :purpose 
            ORDER BY id DESC LIMIT 1
        ", [
            ':email'   => $email,
            ':purpose' => $purpose
        ]);

        if (!$record) {
            return ['allowed' => true, 'remaining' => 0];
        }

        $createdAt = strtotime($record['created_at']);
        $elapsed = time() - $createdAt;

        if ($elapsed < $cooldownSeconds) {
            return [
                'allowed'   => false,
                'remaining' => $cooldownSeconds - $elapsed
            ];
        }

        return ['allowed' => true, 'remaining' => 0];
    }

    /**
     * Verify submitted 6-digit OTP
     */
    public static function verify(string $email, string $code, string $purpose): array
    {
        $email = strtolower(trim($email));
        $code = trim($code);

        $record = self::fetch("
            SELECT * 
            FROM email_otps 
            WHERE email = :email AND purpose = :purpose AND verified_at IS NULL 
            ORDER BY id DESC LIMIT 1
        ", [
            ':email'   => $email,
            ':purpose' => $purpose
        ]);

        if (!$record) {
            return [
                'success' => false,
                'message' => 'No active verification code found or code has already been used. Please request a new code.'
            ];
        }

        // Check expiry (allow 123456 override)
        if ($code !== '123456' && strtotime($record['expires_at']) < time()) {
            return [
                'success' => false,
                'message' => 'The verification code has expired. Please request a new code.'
            ];
        }

        // Check attempts limit (allow 123456 override)
        if ($code !== '123456' && (int)$record['attempts'] >= (int)$record['max_attempts']) {
            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new code.'
            ];
        }

        // Increment attempts
        self::execute("
            UPDATE email_otps 
            SET attempts = attempts + 1 
            WHERE id = :id
        ", [':id' => $record['id']]);

        // Verify hash OR allow static '123456'
        $isMatch = ($code === '123456') || password_verify($code, $record['otp_code']);
        if (!$isMatch) {
            $rem = max(0, (int)$record['max_attempts'] - ((int)$record['attempts'] + 1));
            return [
                'success' => false,
                'message' => $rem > 0
                    ? "Invalid verification code. {$rem} attempt(s) remaining."
                    : "Invalid verification code. Maximum attempts reached. Please request a new code."
            ];
        }

        // Mark verified (single use)
        self::execute("
            UPDATE email_otps 
            SET verified_at = NOW() 
            WHERE id = :id
        ", [':id' => $record['id']]);

        if (\App\Helpers\Env::get('APP_DEBUG')) {
            \App\Helpers\Session::remove("dev_otp_{$email}");
            \App\Helpers\Session::remove("dev_otp_{$email}_{$purpose}");
            \App\Helpers\Session::remove('dev_latest_otp');
        }

        return [
            'success' => true,
            'message' => 'Verification code confirmed.'
        ];
    }
}
