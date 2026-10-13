<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Response;

class Mailer
{
    /**
     * Send an email via configured SMTP with fallback to secure simulation log
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlContent, ?string $textContent = null): bool
    {
        $host = Env::get('MAIL_HOST', 'smtp.gmail.com');
        $port = (int) Env::get('MAIL_PORT', 587);
        $user = Env::get('MAIL_USERNAME', 'sportjunctions@gmail.com');
        $pass = Env::get('MAIL_PASSWORD', '');
        $encryption = strtolower(Env::get('MAIL_ENCRYPTION', 'tls'));
        $fromEmail = Env::get('MAIL_FROM_ADDRESS', 'sportjunctions@gmail.com');
        $fromName = Env::get('MAIL_FROM_NAME', 'Sports Junction Marketplace');

        if ($textContent === null) {
            $formattedHtml = str_replace(['<br>', '<br/>', '</p>', '</div>', '</tr>', '</li>'], "\n", $htmlContent);
            $plain = strip_tags($formattedHtml);
            $textContent = preg_replace("/[\r\n]{3,}/", "\n\n", trim($plain));
        }

        // If password is blank or host is not reachable, record to simulation log
        if (empty($pass) || empty($host)) {
            self::logEmail($toEmail, $subject, $textContent, "MAIL_PASSWORD not set in .env — simulated delivery to mail.log");
            return true;
        }

        try {
            $socket = @fsockopen(
                ($encryption === 'ssl' ? 'ssl://' : '') . $host,
                $port,
                $errno,
                $errstr,
                5.0
            );

            if (!$socket) {
                self::logEmail($toEmail, $subject, $textContent, "Socket connection failed to {$host}:{$port}: {$errstr} ({$errno})");
                return true;
            }

            stream_set_timeout($socket, 8);

            // 1. Read initial banner (expect 220)
            $resp = self::readResponse($socket);
            if ($resp['code'] !== 220) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP Banner Failed: " . $resp['message']);
                return true;
            }

            // 2. EHLO
            $hostname = gethostname() ?: 'localhost';
            $resp = self::sendCommand($socket, "EHLO " . $hostname);
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP EHLO Failed: " . $resp['message']);
                return true;
            }

            // 3. STARTTLS
            if ($encryption === 'tls') {
                $resp = self::sendCommand($socket, "STARTTLS");
                if ($resp['code'] !== 220) {
                    fclose($socket);
                    self::logEmail($toEmail, $subject, $textContent, "SMTP STARTTLS Failed: " . $resp['message']);
                    return true;
                }

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    self::logEmail($toEmail, $subject, $textContent, "SMTP TLS Crypto Handshake Failed");
                    return true;
                }

                $resp = self::sendCommand($socket, "EHLO " . $hostname);
                if ($resp['code'] !== 250) {
                    fclose($socket);
                    self::logEmail($toEmail, $subject, $textContent, "SMTP Post-TLS EHLO Failed: " . $resp['message']);
                    return true;
                }
            }

            // 4. AUTH LOGIN
            $resp = self::sendCommand($socket, "AUTH LOGIN");
            if ($resp['code'] !== 334) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP AUTH LOGIN Rejected: " . $resp['message']);
                return true;
            }

            $resp = self::sendCommand($socket, base64_encode($user));
            if ($resp['code'] !== 334) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP Username Rejected: " . $resp['message']);
                return true;
            }

            $resp = self::sendCommand($socket, base64_encode($pass));
            if ($resp['code'] !== 235) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP Authentication Failed (Check Google App Password): " . $resp['message']);
                return true;
            }

            // 5. MAIL FROM & RCPT TO
            $resp = self::sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP MAIL FROM Rejected: " . $resp['message']);
                return true;
            }

            $resp = self::sendCommand($socket, "RCPT TO:<{$toEmail}>");
            if ($resp['code'] !== 250 && $resp['code'] !== 251) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP RCPT TO Rejected: " . $resp['message']);
                return true;
            }

            // 6. DATA
            $resp = self::sendCommand($socket, "DATA");
            if ($resp['code'] !== 354) {
                fclose($socket);
                self::logEmail($toEmail, $subject, $textContent, "SMTP DATA Command Rejected: " . $resp['message']);
                return true;
            }

            $boundary = "----=_Part_" . md5(uniqid((string)mt_rand(), true));
            $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
            $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";
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
                return true;
            }

            self::sendCommand($socket, "QUIT");
            fclose($socket);

            self::logEmail($toEmail, $subject, $textContent, "SUCCESS: Live SMTP delivered via {$host}:{$port}");
            return true;
        } catch (\Throwable $e) {
            self::logEmail($toEmail, $subject, $textContent, "SMTP Exception: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Test SMTP connectivity and authentication credentials
     */
    public static function testConnection(): array
    {
        $host = Env::get('MAIL_HOST', 'smtp.gmail.com');
        $port = (int) Env::get('MAIL_PORT', 587);
        $user = Env::get('MAIL_USERNAME', 'sportjunctions@gmail.com');
        $pass = Env::get('MAIL_PASSWORD', '');
        $encryption = strtolower(Env::get('MAIL_ENCRYPTION', 'tls'));
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
                'instructions' => 'To enable real Gmail delivery: Enable 2-Step Verification on Google Account -> Generate an App Password (16 characters) -> Set MAIL_PASSWORD in .env.'
            ];
        }

        try {
            $socket = @fsockopen(
                ($encryption === 'ssl' ? 'ssl://' : '') . $host,
                $port,
                $errno,
                $errstr,
                5.0
            );

            if (!$socket) {
                return [
                    'status' => 'CONNECTION_FAILED',
                    'configured' => true,
                    'host' => $host,
                    'port' => $port,
                    'error' => "{$errstr} ({$errno})",
                    'message' => "Unable to connect to {$host}:{$port}. Check internet/firewall connectivity.",
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
                'message' => 'Gmail rejected credentials. Note: Gmail requires a 16-character App Password (not your standard Google account password).',
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

        // Split by delimiter to inspect individual email dispatches in reverse order
        $blocks = array_reverse(explode(str_repeat('-', 60), $content));

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            if ($email !== '') {
                // Must match the exact recipient
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

        // Extract 6-digit OTP code if present for fast developer inspection
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
    private static function renderTemplate(string $title, string $badge, string $contentHtml): string
    {
        $appUrl = rtrim((string) Env::get('APP_URL', 'http://localhost/Antigravity/Sports-Junction'), '/');
        $logoUrl = $appUrl . '/assets/images/logo.jpg';
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f7fc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0b1c30;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed;background-color:#f4f7fc;padding:40px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:580px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 10px 30px rgba(0,40,142,0.06);border:1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:32px 40px;background:linear-gradient(135deg, #00288e 0%, #001f6e 100%);text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">Sports Junction</h1>
                            <p style="margin:6px 0 0 0;color:#86f2e4;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;">India's Sports Manufacturing Marketplace</p>
                        </td>
                    </tr>
                    <!-- Badge Bar -->
                    <tr>
                        <td style="padding:16px 40px 0 40px;">
                            <span style="display:inline-block;padding:4px 12px;background-color:#eff4ff;color:#00288e;font-size:11px;font-weight:700;border-radius:999px;text-transform:uppercase;letter-spacing:1px;border:1px solid #dce9ff;">{$badge}</span>
                        </td>
                    </tr>
                    <!-- Content Body -->
                    <tr>
                        <td style="padding:20px 40px 36px 40px;font-size:15px;line-height:1.6;color:#334155;">
                            {$contentHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background-color:#f8fafc;border-top:1px solid #f1f5f9;text-align:center;font-size:12px;color:#64748b;">
                            <p style="margin:0 0 6px 0;">This is an automated security transmission from Sports Junction.</p>
                            <p style="margin:0;">&copy; 2026 Sports Junction B2B Marketplace. All rights reserved.</p>
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

    // 1. Buyer Email OTP
    public static function sendBuyerOtp(string $email, string $name = 'Valued Customer', string $otp = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued Customer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#0b1c30;font-size:20px;font-weight:700;'>Verify Your Buyer Account</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for registering on Sports Junction. To complete your registration and activate your verified enterprise procurement desk, please enter the single-use passcode below:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#eff4ff;border:2px dashed #00288e;border-radius:16px;font-size:32px;font-weight:800;letter-spacing:8px;color:#00288e;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>This verification code will expire in <strong>10 minutes</strong>. For your security, never share this passcode with anyone.</p>
        ";
        return self::send($email, $name, "Your Verification Passcode: {$otp}", self::renderTemplate("Email OTP Verification", "Buyer Verification", $content));
    }

    // 2. Supplier Email OTP
    public static function sendSupplierOtp(string $email, string $companyName = 'Valued Manufacturer', string $otp = ''): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#0b1c30;font-size:20px;font-weight:700;'>Verify Your Manufacturer Account</h2>
            <p>Greetings from Sports Junction,</p>
            <p>Thank you for submitting your manufacturing facility application for <strong>" . htmlspecialchars($companyName) . "</strong>. Please authenticate your business email using the 6-digit passcode below:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#eff4ff;border:2px dashed #00288e;border-radius:16px;font-size:32px;font-weight:800;letter-spacing:8px;color:#00288e;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>Once verified, your profile will be submitted to the Super Administrator for factory verification review.</p>
        ";
        return self::send($email, $companyName, "Verify Supplier Account Passcode: {$otp}", self::renderTemplate("Supplier Verification", "Supplier Verification", $content));
    }

    // 3. Password Reset OTP
    public static function sendPasswordResetOtp(string $email, string $name = 'Valued User', string $otp = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued User';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#0b1c30;font-size:20px;font-weight:700;'>Password Reset Request</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We received a request to reset your password on Sports Junction. Use the 6-digit authorization code below to establish a new password:</p>
            <div style='margin:28px 0;text-align:center;'>
                <div style='display:inline-block;padding:16px 36px;background-color:#fef2f2;border:2px dashed #dc2626;border-radius:16px;font-size:32px;font-weight:800;letter-spacing:8px;color:#dc2626;font-family:monospace;'>
                    {$otp}
                </div>
            </div>
            <p style='font-size:13px;color:#64748b;'>If you did not initiate this request, please ignore this email or contact support immediately. This code expires in 10 minutes.</p>
        ";
        return self::send($email, $name, "Password Recovery Passcode: {$otp}", self::renderTemplate("Password Recovery", "Security Authorization", $content));
    }

    // 4. Buyer Verification Success
    public static function sendBuyerVerified(string $email, string $name = 'Valued Customer'): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued Customer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#0b1c30;font-size:20px;font-weight:700;'>Welcome to Sports Junction!</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your business email has been verified successfully. Your verified Buyer status is now active.</p>
            <ul style='padding-left:20px;color:#334155;'>
                <li>Direct factory inquiry dispatch with verified manufacturers</li>
                <li>Access to wholesale price matrices and minimum order quantities</li>
                <li>Custom OEM/ODM RFP submissions</li>
            </ul>
        ";
        return self::send($email, $name, "Account Verified — Welcome to Sports Junction", self::renderTemplate("Account Activated", "Verification Complete", $content));
    }

    // 5. Supplier Application Received
    public static function sendSupplierApplicationReceived(string $email, string $companyName = 'Valued Manufacturer'): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#0b1c30;font-size:20px;font-weight:700;'>Application Under Review</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Your manufacturer registration has been received and your email is verified. Your application is currently under review by our Super Administrator team.</p>
            <p>We review factory credentials, business registrations, and product categories within 1-2 business days. You will receive an email as soon as your supplier portal access is authorized.</p>
        ";
        return self::send($email, $companyName, "Application Received — Under Admin Review", self::renderTemplate("Application Submitted", "Pending Admin Approval", $content));
    }

    // 6. Supplier Approved
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

    // 7. Supplier Rejected
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

    // 8. Product Submitted for Approval
    public static function sendProductSubmitted(string $email, string $supplierName = 'Supplier', string $productTitle = 'Product'): bool
    {
        $supplierName = trim($supplierName) !== '' ? $supplierName : 'Supplier';
        $content = "
            <h2 style='margin:0 0 12px 0;color:#0b1c30;font-size:20px;font-weight:700;'>Product Submitted for Review</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>Your product <strong>\"" . htmlspecialchars($productTitle) . "\"</strong> has been submitted and queued for Super Admin review.</p>
            <p>Once approved, it will be published across our dynamic marketplace, category listings, and search index.</p>
        ";
        return self::send($email, $supplierName, "Product Queued for Review: {$productTitle}", self::renderTemplate("Product Submitted", "Pending Review", $content));
    }

    // 9. Product Approved
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

    // 10. Product Rejected
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
}
