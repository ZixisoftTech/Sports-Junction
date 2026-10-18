<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Response;

class Mailer
{
    /**
     * Send an email via configured SMTP with fallback to secure simulation log
     */
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        ?string $textContent = null,
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool {
        $host = Env::get('MAIL_HOST', 'smtp.hostinger.com');
        $port = (int) Env::get('MAIL_PORT', 465);
        $user = Env::get('MAIL_USERNAME', 'no-reply@sportsjunction.in');
        $pass = Env::get('MAIL_PASSWORD', '');
        $encryption = strtolower(Env::get('MAIL_ENCRYPTION', 'ssl'));
        $fromEmail = Env::get('MAIL_FROM_ADDRESS', 'no-reply@sportsjunction.in');
        $fromName = Env::get('MAIL_FROM_NAME', 'Sports Junction');
        $driver = strtolower((string) Env::get('MAIL_DRIVER', 'smtp'));
        $mode = strtolower((string) Env::get('MAIL_MODE', 'live'));

        if ($textContent === null) {
            $formattedHtml = str_replace(['<br>', '<br/>', '</p>', '</div>', '</tr>', '</li>'], "\n", $htmlContent);
            $plain = strip_tags($formattedHtml);
            $textContent = preg_replace("/[\r\n]{3,}/", "\n\n", trim($plain));
        }

        // Safe development / simulation mode (or when static OTP mode is active without live SMTP configured)
        $isStaticOtp = \App\Models\Otp::isStaticOtpEnabled();
        $isSmtpConfigured = filter_var(Env::get('SMTP_CONFIGURED', false), FILTER_VALIDATE_BOOLEAN);

        if ($driver === 'log' || $mode === 'simulation' || empty($pass) || empty($host) || ($isStaticOtp && !$isSmtpConfigured)) {
            self::logEmail($toEmail, $subject, $textContent, "Simulated delivery (driver={$driver}, mode={$mode}, static_otp=" . ($isStaticOtp ? 'active' : 'inactive') . ")");
            return true;
        }

        try {
            $timeout = (float) Env::get('MAIL_TIMEOUT', 10.0);
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer'       => (bool) Env::get('MAIL_VERIFY_PEER', false),
                    'verify_peer_name'  => (bool) Env::get('MAIL_VERIFY_PEER_NAME', false),
                    'allow_self_signed' => (bool) Env::get('MAIL_ALLOW_SELF_SIGNED', true),
                ]
            ]);

            $remoteSocket = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
            $socket = @stream_socket_client(
                $remoteSocket,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$socket) {
                self::logEmail($toEmail, $subject, $textContent, "SMTP Connection Failed to {$remoteSocket}: {$errstr} ({$errno})");
                return false;
            }

            stream_set_timeout($socket, 10);

            // 1. Read initial banner (expect 220)
            $resp = self::readResponse($socket);
            if ($resp['code'] !== 220) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP Banner Failed: " . $resp['message']);
                return false;
            }

            // 2. EHLO
            $hostname = gethostname() ?: 'localhost';
            $resp = self::sendCommand($socket, "EHLO " . $hostname);
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP EHLO Failed: " . $resp['message']);
                return false;
            }

            // 3. STARTTLS if tls mode on port 587
            if ($encryption === 'tls') {
                $resp = self::sendCommand($socket, "STARTTLS");
                if ($resp['code'] !== 220) {
                    fclose($socket);
                    self::logEmail($toEmail, $subject, $textContent, "SMTP STARTTLS Failed: " . $resp['message']);
                    return false;
                }

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    self::logEmail($toEmail, $subject, $textContent, "SMTP TLS Crypto Handshake Failed");
                    return false;
                }

                $resp = self::sendCommand($socket, "EHLO " . $hostname);
                if ($resp['code'] !== 250) {
                    fclose($socket);
                    self::logEmail($toEmail, $subject, $textContent, "SMTP Post-TLS EHLO Failed: " . $resp['message']);
                    return false;
                }
            }

            // 4. AUTH LOGIN
            $resp = self::sendCommand($socket, "AUTH LOGIN");
            if ($resp['code'] !== 334) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP AUTH LOGIN Rejected: " . $resp['message']);
                return false;
            }

            $resp = self::sendCommand($socket, base64_encode($user));
            if ($resp['code'] !== 334) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP Username Rejected: " . $resp['message']);
                return false;
            }

            $resp = self::sendCommand($socket, base64_encode($pass));
            if ($resp['code'] !== 235) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP Authentication Failed: " . $resp['message']);
                return false;
            }

            // 5. MAIL FROM & RCPT TO
            $resp = self::sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP MAIL FROM Rejected: " . $resp['message']);
                return false;
            }

            $resp = self::sendCommand($socket, "RCPT TO:<{$toEmail}>");
            if ($resp['code'] !== 250 && $resp['code'] !== 251) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP RCPT TO Rejected: " . $resp['message']);
                return false;
            }

            // 6. DATA
            $resp = self::sendCommand($socket, "DATA");
            if ($resp['code'] !== 354) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP DATA Command Rejected: " . $resp['message']);
                return false;
            }

            $boundary = "----=_Part_" . md5(uniqid((string)mt_rand(), true));
            $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
            $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";

            // Reply-To header support
            if (!empty($replyToEmail)) {
                $rName = $replyToName ?? $replyToEmail;
                $headers .= "Reply-To: =?UTF-8?B?" . base64_encode($rName) . "?= <{$replyToEmail}>\r\n";
            } elseif (!empty($defaultReplyTo = Env::get('MAIL_REPLY_TO'))) {
                $headers .= "Reply-To: <{$defaultReplyTo}>\r\n";
            }

            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

            $body  = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($textContent)) . "\r\n";

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($htmlContent)) . "\r\n";

            $body .= "--{$boundary}--\r\n";

            // Dot-stuffing for RFC 5321 compliance
            $payload = $headers . "\r\n" . $body;
            $payload = preg_replace('/^\./m', '..', $payload) . "\r\n.";

            $resp = self::sendCommand($socket, $payload);
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP Message Body Rejected: " . $resp['message']);
                return false;
            }

            self::sendCommand($socket, "QUIT");
            fclose($socket);

            self::logEmail($toEmail, $subject, $textContent, "SUCCESS: Live SMTP delivered via {$host}:{$port}");
            return true;
        } catch (\Throwable $e) {
            self::logEmail($toEmail, $subject, $textContent, "SMTP Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test SMTP connectivity and authentication credentials
     */
    public static function testConnection(): array
    {
        $host = Env::get('MAIL_HOST', 'smtp.hostinger.com');
        $port = (int) Env::get('MAIL_PORT', 465);
        $user = Env::get('MAIL_USERNAME', 'no-reply@sportsjunction.in');
        $pass = Env::get('MAIL_PASSWORD', '');
        $encryption = strtolower(Env::get('MAIL_ENCRYPTION', 'ssl'));
        $logFile = dirname(__DIR__, 2) . '/storage/logs/mail.log';

        if (empty($pass) || empty($host)) {
            return [
                'status' => 'SIMULATION_MODE',
                'configured' => false,
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'message' => 'MAIL_PASSWORD is blank in .env. Emails are simulated and safely logged to storage/logs/mail.log.',
                'log_file' => $logFile,
            ];
        }

        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ]
            ]);

            $remoteSocket = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
            $socket = @stream_socket_client(
                $remoteSocket,
                $errno,
                $errstr,
                5.0,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$socket) {
                return [
                    'status' => 'CONNECTION_FAILED',
                    'configured' => true,
                    'host' => $host,
                    'port' => $port,
                    'error' => "{$errstr} ({$errno})",
                    'message' => "Unable to connect to {$remoteSocket}. Check internet/firewall connectivity.",
                    'log_file' => $logFile
                ];
            }

            stream_set_timeout($socket, 8);
            $banner = self::readResponse($socket);
            if ($banner['code'] !== 220) {
                fclose($socket);
                return ['status' => 'BANNER_FAILED', 'code' => $banner['code'], 'message' => $banner['message']];
            }

            $hostname = gethostname() ?: 'localhost';
            self::sendCommand($socket, "EHLO " . $hostname);

            if ($encryption === 'tls') {
                $tlsResp = self::sendCommand($socket, "STARTTLS");
                if ($tlsResp['code'] !== 220) {
                    fclose($socket);
                    return ['status' => 'STARTTLS_FAILED', 'code' => $tlsResp['code'], 'message' => $tlsResp['message']];
                }
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return ['status' => 'TLS_CRYPTO_FAILED', 'message' => 'TLS cryptographic handshake failed.'];
                }
                self::sendCommand($socket, "EHLO " . $hostname);
            }

            $authResp = self::sendCommand($socket, "AUTH LOGIN");
            self::sendCommand($socket, base64_encode($user));
            $passResp = self::sendCommand($socket, base64_encode($pass));

            self::sendCommand($socket, "QUIT");
            fclose($socket);

            if ($passResp['code'] === 235) {
                return [
                    'status' => 'AUTHENTICATED_AND_READY',
                    'configured' => true,
                    'host' => $host,
                    'port' => $port,
                    'user' => $user,
                    'message' => 'SMTP authentication successful! Real emails will be delivered to recipients.',
                    'log_file' => $logFile
                ];
            }

            return [
                'status' => 'AUTH_FAILED',
                'configured' => true,
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'code' => $passResp['code'],
                'server_response' => $passResp['message'],
                'message' => 'SMTP credentials rejected by host.',
                'log_file' => $logFile
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'EXCEPTION',
                'configured' => true,
                'message' => $e->getMessage(),
                'log_file' => $logFile
            ];
        }
    }

    /**
     * Retrieve the latest logged OTP passcode for testing / dev convenience
     */
    public static function getLastSentOtp(string $email = ''): ?string
    {
        $logFile = dirname(__DIR__, 2) . '/storage/logs/mail.log';
        if (!file_exists($logFile)) {
            return null;
        }
        $content = @file_get_contents($logFile);
        if (!$content) {
            return null;
        }

        $blocks = array_reverse(explode(str_repeat('-', 60), $content));

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            if ($email !== '') {
                if (stripos($block, "TO: {$email}") === false && stripos($block, "TO: " . trim($email)) === false) {
                    continue;
                }
            }

            if (preg_match('/\[PASSCODE:\s*(\d{6})\]/', $block, $m)) {
                return $m[1];
            }
            if (preg_match('/(?:Passcode|Code|OTP|passcode)[\s\:\*\#]+(\d{6})/i', $block, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    private static function sendCommand($socket, string $command): array
    {
        fwrite($socket, $command . "\r\n");
        return self::readResponse($socket);
    }

    private static function readResponse($socket): array
    {
        $lines = [];
        $lastCode = 0;
        while ($line = fgets($socket, 1024)) {
            $trimmed = trim($line);
            $lines[] = $trimmed;
            if (strlen($trimmed) >= 3 && is_numeric(substr($trimmed, 0, 3))) {
                $lastCode = (int) substr($trimmed, 0, 3);
                if (!isset($trimmed[3]) || $trimmed[3] === ' ') {
                    break;
                }
            } else {
                break;
            }
        }
        return [
            'code' => $lastCode,
            'message' => implode(" ", $lines),
            'raw' => $lines
        ];
    }

    private static function logEmail(string $to, string $subject, string $body, ?string $note = null): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/mail.log';

        preg_match('/(?:Passcode|Code|OTP|passcode)[\s\:\*\#]+(\d{6})/i', $subject . "\n" . $body, $otpMatch);
        $otpStr = isset($otpMatch[1]) ? " [PASSCODE: {$otpMatch[1]}]" : "";

        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s%s %s\nBODY:\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $otpStr,
            $note ? "($note)" : "[LOGGED/SIMULATED]",
            $body,
            str_repeat('-', 60)
        );
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }

    /**
     * Shared Base Email Layout
     */
    public static function renderTemplate(string $title, string $badge, string $contentHtml): string
    {
        $appUrl = rtrim((string) Env::get('APP_URL', 'http://localhost/Antigravity/Sports-Junction'), '/');
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin:0;padding:0;background-color:#0b1329;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0b1c30;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed;background-color:#0b1329;padding:40px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 12px 36px rgba(0,0,0,0.25);border:1px solid #1e293b;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:32px 40px;background:linear-gradient(135deg, #030712 0%, #0f172a 100%);text-align:center;border-bottom:3px solid #00D084;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">SPORTS JUNCTION</h1>
                            <p style="margin:6px 0 0 0;color:#00D084;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;">India's Sports Manufacturing Marketplace</p>
                        </td>
                    </tr>
                    <!-- Badge Bar -->
                    <tr>
                        <td style="padding:20px 40px 0 40px;">
                            <span style="display:inline-block;padding:5px 14px;background-color:#f0fdf4;color:#15803d;font-size:11px;font-weight:800;border-radius:999px;text-transform:uppercase;letter-spacing:1px;border:1px solid #bbf7d0;">{$badge}</span>
                        </td>
                    </tr>
                    <!-- Content Body -->
                    <tr>
                        <td style="padding:20px 40px 36px 40px;font-size:14px;line-height:1.65;color:#334155;">
                            {$contentHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:22px 40px;background-color:#030712;text-align:center;font-size:11px;color:#94a3b8;border-top:1px solid #1e293b;">
                            <p style="margin:0 0 5px 0;">This is an automated transmission from Sports Junction B2B Marketplace.</p>
                            <p style="margin:0;">&copy; 2026 Sports Junction | All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    // =========================================================================
    // TRANSACTIONAL EMAIL METHODS (1-18)
    // =========================================================================

    // 1. Buyer Email OTP
    public static function sendBuyerOtp(string $email, string $name = 'Valued Buyer', string $otp = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued Buyer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Verify Your Buyer Account</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for registering with Sports Junction. To complete your account activation and access factory direct quotations, please enter the single-use passcode below:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#f0fdf4;border:2px dashed #00D084;border-radius:14px;font-size:32px;font-weight:800;letter-spacing:8px;color:#047857;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>This passcode is valid for <strong>10 minutes</strong>. For your security, do not share this code with anyone.</p>
        ";
        return self::send($email, $name, "Verify Your Sports Junction Account", self::renderTemplate("Account Verification", "Email Verification", $content));
    }

    // 2. Supplier Email OTP
    public static function sendSupplierOtp(string $email, string $companyName = 'Manufacturing Facility', string $otp = ''): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Manufacturing Facility';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Verify Your Manufacturer Account</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>To finalize your factory profile registration on Sports Junction, please verify your official corporate email using the passcode below:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#f0fdf4;border:2px dashed #00D084;border-radius:14px;font-size:32px;font-weight:800;letter-spacing:8px;color:#047857;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>Once your email is confirmed, your manufacturer credentials will be submitted for Super Admin review. This passcode expires in 10 minutes.</p>
        ";
        return self::send($email, $companyName, "Verify Your Manufacturer Account", self::renderTemplate("Manufacturer Verification", "Facility Authentication", $content));
    }

    // 3. Login Security OTP
    public static function sendLoginOtp(string $email, string $name = 'User', string $otp = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'User';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Sign-In Security Verification</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>A sign-in request was initiated for your Sports Junction account. Please enter the one-time passcode below to authorize your session:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#eff6ff;border:2px dashed #2563eb;border-radius:14px;font-size:32px;font-weight:800;letter-spacing:8px;color:#1d4ed8;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>This code expires in <strong>10 minutes</strong>. If you did not initiate this sign-in attempt, please reset your password immediately.</p>
        ";
        return self::send($email, $name, "Sports Junction Sign-In Verification Code", self::renderTemplate("Sign-In Security", "Account Security", $content));
    }

    // 4. Password Reset OTP
    public static function sendPasswordResetOtp(string $email, string $name = 'Valued User', string $otp = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued User';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Password Recovery Request</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We received a request to reset your password on Sports Junction. Use the single-use passcode below to set a new password:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#fef2f2;border:2px dashed #ef4444;border-radius:14px;font-size:32px;font-weight:800;letter-spacing:8px;color:#b91c1c;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>This code will expire in <strong>10 minutes</strong>. If you did not request this recovery, you can safely ignore this email.</p>
        ";
        return self::send($email, $name, "Sports Junction Password Recovery Code", self::renderTemplate("Password Recovery", "Security Alert", $content));
    }

    // 5. Profile Email Change OTP
    public static function sendProfileEmailChangeOtp(string $email, string $name = 'User', string $otp = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'User';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Verify Your New Email Address</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>You requested to update your account email to <strong>" . htmlspecialchars($email) . "</strong>. Please enter the verification code below to confirm this change:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#eff6ff;border:2px dashed #2563eb;border-radius:14px;font-size:32px;font-weight:800;letter-spacing:8px;color:#1d4ed8;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>This code will expire in <strong>10 minutes</strong>. Your account email will remain unchanged until this code is verified.</p>
        ";
        return self::send($email, $name, "Verify Your New Email Address", self::renderTemplate("Email Address Change", "Profile Verification", $content));
    }

    // 6. Security Notice Email
    public static function sendSecurityNoticeEmail(string $email, string $name = 'User', string $event = 'Security Update', string $details = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'User';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>" . htmlspecialchars($event) . "</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>This is a security alert regarding your Sports Junction account.</p>
            <div style='margin:16px 0;padding:16px;background-color:#f8fafc;border-left:4px solid #00288e;border-radius:8px;'>
                <p style='margin:0;color:#334155;font-size:14px;'>" . nl2br(htmlspecialchars($details)) . "</p>
            </div>
            <p style='font-size:13px;color:#64748b;'>If you did not authorize this action, please contact support immediately.</p>
        ";
        return self::send($email, $name, "Security Notice: {$event}", self::renderTemplate("Security Notice", "Account Alert", $content));
    }

    // 7. Buyer Verified Confirmation
    public static function sendBuyerVerified(string $email, string $name = 'Valued Customer'): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued Customer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#15803d;font-size:20px;font-weight:700;'>Account Verified Successfully!</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your business email has been verified. Your buyer account is now fully active.</p>
            <ul style='padding-left:20px;line-height:1.8;color:#475569;'>
                <li>Direct factory inquiry dispatch with verified manufacturers</li>
                <li>Access to wholesale price matrices and minimum order quantities</li>
                <li>Custom OEM/ODM RFP submissions</li>
            </ul>
        ";
        return self::send($email, $name, "Account Verified — Welcome to Sports Junction", self::renderTemplate("Account Activated", "Verification Complete", $content));
    }

    // 8. Supplier Application Received
    public static function sendSupplierApplicationReceived(string $email, string $companyName = 'Valued Manufacturer'): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Application Under Review</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Your manufacturer registration has been received and your email is verified. Your application is currently under review by our Super Administrator team.</p>
            <p>We review factory credentials, business registrations, and product categories within 1-2 business days. You will receive an email as soon as your supplier portal access is authorized.</p>
        ";
        return self::send($email, $companyName, "Application Received — Under Admin Review", self::renderTemplate("Application Submitted", "Pending Admin Approval", $content));
    }

    // 9. Supplier Approved
    public static function sendSupplierApproved(string $email, string $companyName = 'Valued Manufacturer'): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#15803d;font-size:20px;font-weight:700;'>Supplier Application Approved!</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Congratulations! Your Sports Junction manufacturer account has been approved by the Super Administrator.</p>
            <p>You can now sign in to your Supplier Dashboard, list sports gear products, submit specs for admin review, and receive direct wholesale inquiries from domestic and international buyers.</p>
        ";
        return self::send($email, $companyName, "Supplier Account Approved — Start Publishing", self::renderTemplate("Supplier Approved", "Approved Facility", $content));
    }

    // 10. Supplier Rejected
    public static function sendSupplierRejected(string $email, string $companyName = 'Valued Manufacturer', string $reason = 'Profile review criteria not met'): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#b91c1c;font-size:20px;font-weight:700;'>Supplier Application Status Update</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Thank you for your interest in Sports Junction. After reviewing your application, our compliance team was unable to approve your account at this time.</p>
            <div style='margin:16px 0;padding:16px;background-color:#fef2f2;border-left:4px solid #ef4444;border-radius:8px;'>
                <strong style='color:#991b1b;'>Reason:</strong>
                <p style='margin:4px 0 0 0;color:#7f1d1d;font-size:14px;'>" . nl2br(htmlspecialchars($reason)) . "</p>
            </div>
            <p style='font-size:13px;color:#64748b;'>You may update your profile or contact administrative support for re-evaluation.</p>
        ";
        return self::send($email, $companyName, "Application Status: Sports Junction Marketplace", self::renderTemplate("Application Decision", "Application Review", $content));
    }

    // 11. Product Submitted for Approval
    public static function sendProductSubmitted(string $email, string $supplierName = 'Supplier', string $productTitle = 'Product'): bool
    {
        $supplierName = trim($supplierName) !== '' ? $supplierName : 'Supplier';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Product Submitted for Review</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>Your product <strong>\"" . htmlspecialchars($productTitle) . "\"</strong> has been submitted and queued for Super Admin review.</p>
            <p>Once approved, it will be published across our dynamic marketplace, category listings, and search index.</p>
        ";
        return self::send($email, $supplierName, "Product Queued for Review: {$productTitle}", self::renderTemplate("Product Submitted", "Pending Review", $content));
    }

    // 12. Product Approved
    public static function sendProductApproved(string $email, string $supplierName = 'Supplier', string $productTitle = 'Product'): bool
    {
        $supplierName = trim($supplierName) !== '' ? $supplierName : 'Supplier';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#15803d;font-size:20px;font-weight:700;'>Product Approved &amp; Published!</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>Great news! Your product <strong>\"" . htmlspecialchars($productTitle) . "\"</strong> has been approved by the Super Admin and is now live on Sports Junction.</p>
        ";
        return self::send($email, $supplierName, "Product Published: {$productTitle}", self::renderTemplate("Product Live", "Approved & Live", $content));
    }

    // 13. Product Rejected
    public static function sendProductRejected(string $email, string $supplierName = 'Supplier', string $productTitle = 'Product', string $reason = 'Specifications review criteria not met'): bool
    {
        $supplierName = trim($supplierName) !== '' ? $supplierName : 'Supplier';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#b91c1c;font-size:20px;font-weight:700;'>Product Submission Feedback</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>Your product submission <strong>\"" . htmlspecialchars($productTitle) . "\"</strong> requires modifications before it can be published.</p>
            <div style='margin:16px 0;padding:16px;background-color:#fef2f2;border-left:4px solid #ef4444;border-radius:8px;'>
                <strong style='color:#991b1b;'>Reason for Rejection / Changes Required:</strong>
                <p style='margin:4px 0 0 0;color:#7f1d1d;font-size:14px;'>" . nl2br(htmlspecialchars($reason)) . "</p>
            </div>
            <p>You can edit this product and resubmit it for approval from your Supplier Dashboard.</p>
        ";
        return self::send($email, $supplierName, "Action Required on Product: {$productTitle}", self::renderTemplate("Submission Feedback", "Review Required", $content));
    }

    // 14. Buyer Enquiry Confirmation
    public static function sendEnquiryConfirmationToBuyer(array $inquiry): bool
    {
        $toEmail = trim((string)($inquiry['buyer_email'] ?? ''));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'Valued Buyer'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $subject = trim((string)($inquiry['subject'] ?? 'Product Enquiry / RFQ'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));

        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Enquiry Received — Reference {$ref}</h2>
            <p>Dear <strong>" . htmlspecialchars($buyerName) . "</strong>,</p>
            <p>Thank you for submitting your manufacturing enquiry on Sports Junction. Our factory trade desk and verified suppliers have received your requirements.</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:13px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;width:140px;'>Reference No:</td>
                    <td style='padding:8px 0;font-weight:700;color:#030712;'>{$ref}</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Requirement:</td>
                    <td style='padding:8px 0;font-weight:600;color:#030712;'>" . htmlspecialchars($subject) . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Target Quantity:</td>
                    <td style='padding:8px 0;color:#030712;'>" . number_format($qty) . " Units</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Destination Country:</td>
                    <td style='padding:8px 0;color:#030712;'>" . htmlspecialchars($country) . "</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#64748b;vertical-align:top;'>Specifications:</td>
                    <td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td>
                </tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>Our manufacturing engineering team typically responds within 2.4 business hours with direct FOB / CIF factory quotations.</p>
        ";

        return self::send(
            $toEmail,
            $buyerName,
            "Enquiry Received [{$ref}] — Sports Junction",
            self::renderTemplate("Enquiry Confirmation", "Enquiry {$ref}", $content)
        );
    }

    // 15. Supplier Enquiry Notification
    public static function sendEnquiryNotificationToSupplier(array $inquiry, array $supplier): bool
    {
        $supplierEmail = trim((string)($supplier['email'] ?? ''));
        if (empty($supplierEmail) || !filter_var($supplierEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $supplierName = trim((string)($supplier['company_name'] ?? $supplier['name'] ?? 'Valued Supplier'));
        $buyerCompany = trim((string)($inquiry['company_name'] ?? $inquiry['buyer_name'] ?? 'Procurement Buyer'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $subject = trim((string)($inquiry['subject'] ?? 'Product Enquiry'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));
        $buyerEmail = trim((string)($inquiry['buyer_email'] ?? ''));
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'Buyer'));

        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>New Direct Factory Enquiry — {$ref}</h2>
            <p>Dear <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>A buyer has submitted a direct manufacturing inquiry for your catalog products:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:13px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;width:140px;'>Reference No:</td>
                    <td style='padding:8px 0;font-weight:700;color:#030712;'>{$ref}</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Buyer Company:</td>
                    <td style='padding:8px 0;font-weight:600;color:#030712;'>" . htmlspecialchars($buyerCompany) . " (" . htmlspecialchars($country) . ")</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Product / Item:</td>
                    <td style='padding:8px 0;color:#030712;'>" . htmlspecialchars($subject) . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Target Quantity:</td>
                    <td style='padding:8px 0;color:#030712;'>" . number_format($qty) . " Units</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#64748b;vertical-align:top;'>Specifications:</td>
                    <td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td>
                </tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>Please log in to your Supplier Portal to submit your quotation matrix and chat directly with the buyer.</p>
        ";

        return self::send(
            $supplierEmail,
            $supplierName,
            "New Direct Factory Enquiry [{$ref}] from {$buyerCompany}",
            self::renderTemplate("New Buyer Enquiry", "Direct Order Lead", $content),
            null,
            $buyerEmail ?: null,
            $buyerName ?: null
        );
    }

    // 16. Super Admin Enquiry Notification
    public static function sendEnquiryNotificationToAdmin(array $inquiry, ?string $adminEmail = null): bool
    {
        $targetEmail = $adminEmail ?? Env::get('SUPER_ADMIN_EMAIL', 'support@sportsjunction.com');
        if (empty($targetEmail)) {
            $targetEmail = 'support@sportsjunction.com';
        }

        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'Buyer'));
        $buyerCompany = trim((string)($inquiry['company_name'] ?? ''));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $source = (string)($inquiry['enquiry_source'] ?? 'PRODUCT_PAGE');
        $subject = trim((string)($inquiry['subject'] ?? 'Enquiry'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));

        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Admin Alert: New Marketplace Enquiry {$ref}</h2>
            <p>A new wholesale inquiry was submitted via the Sports Junction platform:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:13px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;width:140px;'>Reference:</td>
                    <td style='padding:8px 0;font-weight:700;color:#030712;'>{$ref} ({$source})</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Buyer:</td>
                    <td style='padding:8px 0;font-weight:600;color:#030712;'>" . htmlspecialchars($buyerName) . (!empty($buyerCompany) ? " (" . htmlspecialchars($buyerCompany) . ")" : "") . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Country:</td>
                    <td style='padding:8px 0;color:#030712;'>" . htmlspecialchars($country) . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Quantity:</td>
                    <td style='padding:8px 0;color:#030712;'>" . number_format($qty) . " Units</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#64748b;vertical-align:top;'>Specifications:</td>
                    <td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td>
                </tr>
            </table>
        ";

        return self::send(
            $targetEmail,
            "Super Admin",
            "Admin Alert: New Enquiry {$ref} ({$source})",
            self::renderTemplate("Admin Enquiry Notification", "Admin Dispatch", $content)
        );
    }

    // 17. Contact Us Confirmation to Submitter
    public static function sendContactUsConfirmationToUser(array $inquiry): bool
    {
        $toEmail = trim((string)($inquiry['buyer_email'] ?? ''));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $name = trim((string)($inquiry['buyer_name'] ?? 'Valued Customer'));
        $subject = trim((string)($inquiry['subject'] ?? 'Message Received'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $message = trim((string)($inquiry['specifications'] ?? ''));

        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>Thank You for Contacting Sports Junction</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We have successfully received your inquiry (Reference: <strong>{$ref}</strong>). Our sourcing consultants and customer support team will review your message and reply promptly.</p>
            
            <div style='margin:16px 0;padding:16px;background-color:#f8fafc;border-left:4px solid #00D084;border-radius:8px;'>
                <strong style='color:#030712;'>Subject:</strong> " . htmlspecialchars($subject) . "<br>
                <strong style='color:#030712;'>Country:</strong> " . htmlspecialchars($country) . "<br>
                <strong style='color:#030712;'>Your Message:</strong>
                <p style='margin:6px 0 0 0;color:#334155;font-size:13px;'>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
            <p style='font-size:13px;color:#64748b;'>Average response time: less than 2.4 business hours.</p>
        ";

        return self::send(
            $toEmail,
            $name,
            "We received your message [{$ref}] — Sports Junction Support",
            self::renderTemplate("Contact Request Received", "Support Request", $content)
        );
    }

    // 18. Contact Us Notification to Admin / Official Trade Desk
    public static function sendContactUsNotificationToAdmin(array $inquiry, ?string $adminEmail = null): bool
    {
        $targetEmail = $adminEmail ?? Env::get('SUPER_ADMIN_EMAIL', 'support@sportsjunction.com');
        if (empty($targetEmail)) {
            $targetEmail = 'support@sportsjunction.com';
        }

        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $name = trim((string)($inquiry['buyer_name'] ?? 'Website Visitor'));
        $email = trim((string)($inquiry['buyer_email'] ?? ''));
        $phone = trim((string)($inquiry['buyer_phone'] ?? 'N/A'));
        $company = trim((string)($inquiry['company_name'] ?? 'N/A'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $subject = trim((string)($inquiry['subject'] ?? 'General Inquiry'));
        $message = trim((string)($inquiry['specifications'] ?? ''));

        $content = "
            <h2 style='margin:0 0 12px 0;color:#030712;font-size:20px;font-weight:700;'>New Contact Us Submission — {$ref}</h2>
            <p>A new communication has been submitted via the public Contact Us desk:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:13px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;width:140px;'>Submitter:</td>
                    <td style='padding:8px 0;font-weight:700;color:#030712;'>" . htmlspecialchars($name) . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Email:</td>
                    <td style='padding:8px 0;color:#030712;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Phone:</td>
                    <td style='padding:8px 0;color:#030712;'>" . htmlspecialchars($phone) . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Company:</td>
                    <td style='padding:8px 0;color:#030712;'>" . htmlspecialchars($company) . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Country:</td>
                    <td style='padding:8px 0;color:#030712;'>" . htmlspecialchars($country) . "</td>
                </tr>
                <tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:8px 0;color:#64748b;'>Subject:</td>
                    <td style='padding:8px 0;font-weight:600;color:#030712;'>" . htmlspecialchars($subject) . "</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#64748b;vertical-align:top;'>Message:</td>
                    <td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($message)) . "</td>
                </tr>
            </table>
        ";

        return self::send(
            $targetEmail,
            "Sports Junction Trade Desk",
            "Contact Us: [{$ref}] " . $subject,
            self::renderTemplate("Contact Us Message", "Customer Support", $content),
            null,
            $email ?: null,
            $name ?: null
        );
    }
}
