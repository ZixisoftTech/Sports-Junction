<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\SiteSetting;
use Throwable;

class Mailer
{
    /**
     * In-memory cache of last dispatched OTPs for test suites and dev verification (never persisted to disk)
     */
    private static array $lastOtpSent = [];

    public static function recordSentOtp(string $email, string $otp): void
    {
        if (trim($otp) !== '') {
            self::$lastOtpSent[strtolower(trim($email))] = trim($otp);
        }
    }

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
        // 1. Check if mail delivery is globally enabled
        $mailEnabled = filter_var(Env::get('MAIL_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
        if (!$mailEnabled) {
            self::logDiagnostic($toEmail, $subject, 'SKIPPED', 'Email delivery disabled (MAIL_ENABLED=false)');
            return false;
        }

        // 2. Validate recipient email address
        $toEmail = trim($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            self::logDiagnostic($toEmail, $subject, 'FAILED', 'Invalid recipient email address format');
            return false;
        }

        // 3. Load & sanitize SMTP configuration exclusively from environment
        $host = trim((string) Env::get('MAIL_HOST', 'smtp.gmail.com'));
        if ($host === '') {
            $host = 'smtp.gmail.com';
        }
        $port = (int) Env::get('MAIL_PORT', 587);
        if ($port <= 0) {
            $port = 587;
        }
        $user = trim((string) Env::get('MAIL_USERNAME', 'noreply@sportsjunction.in'));
        $pass = trim((string) Env::get('MAIL_PASSWORD', ''));
        $encryption = strtolower((string) Env::get('MAIL_ENCRYPTION', 'tls'));
        if ($encryption === '') {
            $encryption = 'tls';
        }
        $fromEmail = trim((string) Env::get('MAIL_FROM_ADDRESS', 'noreply@sportsjunction.in'), '<> ');
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = 'noreply@sportsjunction.in';
        }
        $fromName = trim((string) Env::get('MAIL_FROM_NAME', 'Sports Junction'));
        if ($fromName === '') {
            $fromName = 'Sports Junction';
        }
        $driver = strtolower((string) Env::get('MAIL_DRIVER', 'smtp'));
        $mode = strtolower((string) Env::get('MAIL_MODE', 'live'));

        if ($textContent === null) {
            $formattedHtml = str_replace(['<br>', '<br/>', '</p>', '</div>', '</tr>', '</li>', '</h1>', '</h2>', '</h3>'], "\n", $htmlContent);
            $plain = strip_tags($formattedHtml);
            $textContent = preg_replace("/[\r\n]{3,}/", "\n\n", trim($plain));
        }

        if ($driver === 'log' || $mode === 'simulation') {
            self::logDiagnostic($toEmail, $subject, 'SIMULATED', "Simulated delivery (driver={$driver}, mode={$mode})");
            return true;
        }

        // 4. Validate credentials before initiating connection
        if (empty($pass)) {
            self::logDiagnostic($toEmail, $subject, 'FAILED', 'MAIL_PASSWORD is blank or not configured in .env');
            return false;
        }

        if (empty($user)) {
            self::logDiagnostic($toEmail, $subject, 'FAILED', 'MAIL_USERNAME is blank or not configured in .env');
            return false;
        }

        try {
            $timeout = (float) Env::get('MAIL_TIMEOUT', 15.0);
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
                self::logDiagnostic($toEmail, $subject, 'FAILED', "Connection failed to {$remoteSocket}: {$errstr} ({$errno})");
                return false;
            }

            stream_set_timeout($socket, (int)$timeout);

            // 1. Read initial banner (expect 220)
            $resp = self::readResponse($socket);
            if ($resp['code'] !== 220) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP Banner Failed: ' . $resp['message']);
                return false;
            }

            // 2. EHLO
            $hostname = gethostname() ?: 'localhost';
            $resp = self::sendCommand($socket, "EHLO " . $hostname);
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP EHLO Failed: ' . $resp['message']);
                return false;
            }

            // 3. STARTTLS if tls mode on port 587
            if ($encryption === 'tls') {
                $resp = self::sendCommand($socket, "STARTTLS");
                if ($resp['code'] !== 220) {
                    fclose($socket);
                    self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP STARTTLS Failed: ' . $resp['message']);
                    return false;
                }

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP TLS Crypto Handshake Failed');
                    return false;
                }

                $resp = self::sendCommand($socket, "EHLO " . $hostname);
                if ($resp['code'] !== 250) {
                    fclose($socket);
                    self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP Post-TLS EHLO Failed: ' . $resp['message']);
                    return false;
                }
            }

            // 4. AUTH LOGIN
            $resp = self::sendCommand($socket, "AUTH LOGIN");
            if ($resp['code'] !== 334) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP AUTH LOGIN Rejected: ' . $resp['message']);
                return false;
            }

            $resp = self::sendCommand($socket, base64_encode($user));
            if ($resp['code'] !== 334) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP Username Rejected: ' . $resp['message']);
                return false;
            }

            $resp = self::sendCommand($socket, base64_encode($pass));
            if ($resp['code'] !== 235) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP Authentication Rejected: ' . $resp['message']);
                return false;
            }

            // 5. Envelope
            $cleanFrom = trim($fromEmail, '<> ');
            $cleanTo = trim($toEmail, '<> ');

            $resp = self::sendCommand($socket, "MAIL FROM:<{$cleanFrom}>");
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP MAIL FROM Rejected: ' . $resp['message']);
                return false;
            }

            $resp = self::sendCommand($socket, "RCPT TO:<{$cleanTo}>");
            if ($resp['code'] !== 250 && $resp['code'] !== 251) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP RCPT TO Rejected: ' . $resp['message']);
                return false;
            }

            // 6. DATA
            $resp = self::sendCommand($socket, "DATA");
            if ($resp['code'] !== 354) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP DATA Command Rejected: ' . $resp['message']);
                return false;
            }

            // 7. Message assembly & RFC compliant headers
            $boundary = "----=_Part_" . bin2hex(random_bytes(16));
            $domain = parse_url((string)Env::get('APP_URL', 'https://sportsjunction.in'), PHP_URL_HOST) ?: 'sportsjunction.in';
            $messageId = sprintf("<%s.%s@%s>", bin2hex(random_bytes(8)), (string)time(), $domain);

            $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$cleanFrom}>\r\n";
            $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$cleanTo}>\r\n";

            if (!empty($replyToEmail) && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $rName = $replyToName ?? $replyToEmail;
                $headers .= "Reply-To: =?UTF-8?B?" . base64_encode($rName) . "?= <" . trim($replyToEmail, '<> ') . ">\r\n";
            } elseif (!empty($defaultReplyTo = trim((string)Env::get('MAIL_REPLY_TO', ''), '<> '))) {
                if (filter_var($defaultReplyTo, FILTER_VALIDATE_EMAIL)) {
                    $headers .= "Reply-To: <{$defaultReplyTo}>\r\n";
                }
            }

            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "Message-ID: {$messageId}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "X-Mailer: SportsJunction Mailer\r\n";

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
            $payload = preg_replace('/^\./m', '..', $payload) . "\r\n.\r\n";

            // Ensure complete transmission across TLS socket buffer
            if (!self::writeAll($socket, $payload)) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'Socket write failure during payload transmission');
                return false;
            }

            $resp = self::readResponse($socket);
            if ($resp['code'] !== 250) {
                fclose($socket);
                self::logDiagnostic($toEmail, $subject, 'FAILED', 'SMTP Message Body Rejected: ' . $resp['message']);
                return false;
            }

            self::sendCommand($socket, "QUIT");
            fclose($socket);

            self::logDiagnostic($toEmail, $subject, 'SUCCESS', "Live SMTP delivered via {$host}:{$port}");
            return true;
        } catch (\Throwable $e) {
            self::logDiagnostic($toEmail, $subject, 'EXCEPTION', $e->getMessage());
            return false;
        }
    }

    /**
     * Test SMTP connectivity and authentication credentials
     */
    public static function testConnection(): array
    {
        $host = trim((string) Env::get('MAIL_HOST', 'smtp.gmail.com'));
        if ($host === '') {
            $host = 'smtp.gmail.com';
        }
        $port = (int) Env::get('MAIL_PORT', 587);
        if ($port <= 0) {
            $port = 587;
        }
        $user = trim((string) Env::get('MAIL_USERNAME', 'noreply@sportsjunction.in'));
        if ($user === '') {
            $user = 'noreply@sportsjunction.in';
        }
        $pass = trim((string) Env::get('MAIL_PASSWORD', ''));
        $encryption = strtolower((string) Env::get('MAIL_ENCRYPTION', 'tls'));
        if ($encryption === '') {
            $encryption = 'tls';
        }
        $logFile = dirname(__DIR__, 2) . '/storage/logs/mail.log';

        if (empty($pass)) {
            return [
                'status' => 'NOT_CONFIGURED',
                'configured' => false,
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'message' => 'MAIL_PASSWORD is blank or not configured in .env. Please configure your Google App Password to enable live email delivery.',
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
                8.0,
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

            stream_set_timeout($socket, 10);
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
                'message' => 'SMTP credentials rejected by host. Please verify your Google App Password.',
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
     * Retrieve the latest session OTP for local testing / development
     */
    public static function getLastSentOtp(string $email = ''): ?string
    {
        $key = strtolower(trim($email));
        if ($key !== '' && isset(self::$lastOtpSent[$key])) {
            return self::$lastOtpSent[$key];
        }
        if ($key === '' && !empty(self::$lastOtpSent)) {
            return end(self::$lastOtpSent);
        }
        if (!Env::get('APP_DEBUG', false)) {
            return null;
        }
        if ($email !== '' && Session::get("dev_otp_{$email}")) {
            return (string) Session::get("dev_otp_{$email}");
        }
        return Session::get('dev_latest_otp') ? (string) Session::get('dev_latest_otp') : null;
    }

    /**
     * Write complete payload to socket buffer
     */
    private static function writeAll($socket, string $data): bool
    {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $bytes = fwrite($socket, substr($data, $written));
            if ($bytes === false || $bytes === 0) {
                return false;
            }
            $written += $bytes;
        }
        return true;
    }

    private static function sendCommand($socket, string $command): array
    {
        self::writeAll($socket, $command . "\r\n");
        return self::readResponse($socket);
    }

    private static function readResponse($socket): array
    {
        $lines = [];
        $lastCode = 0;
        while (($line = fgets($socket, 1024)) !== false) {
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

    /**
     * Secure diagnostic logger — records metadata only without exposing credentials or OTP passcodes
     */
    private static function logDiagnostic(string $toEmail, string $subject, string $status, ?string $diagnostic = null): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/mail.log';

        // Redact any potential credentials/passwords from diagnostic text
        $cleanDiag = $diagnostic ? preg_replace('/(pass|password|pwd|token|otp)[\s\:\=\'\"]+[^\s\,\;]+/i', '$1=***', $diagnostic) : '';

        $entry = sprintf(
            "[%s] [Sports Junction Mailer] STATUS: %s | TO: %s | SUBJECT: %s%s\n",
            date('Y-m-d H:i:s'),
            $status,
            $toEmail,
            $subject,
            $cleanDiag !== '' ? " | DETAILS: {$cleanDiag}" : ""
        );
        @file_put_contents($logFile, $entry, FILE_APPEND);

        if (filter_var(Env::get('MAIL_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
            @error_log("[Mailer DEBUG] " . trim($entry));
        }
    }

    /**
     * Backward-compatible alias for logEmail
     */
    public static function logEmail(string $to, string $subject, string $body, ?string $note = null): void
    {
        self::logDiagnostic($to, $subject, 'INFO', $note);
    }

    /**
     * Shared Base Email Layout (White Card on Light #f1f5f9 Background, Navy #00288e & Teal #00D084 branding)
     */
    public static function renderTemplate(
        string $title,
        string $badge,
        string $contentHtml,
        ?string $ctaUrl = null,
        ?string $ctaText = null,
        string $badgeColor = '#00288e',
        string $badgeBg = '#e0e7ff'
    ): string {
        // Resolve the public HTTPS base URL for links in outbound emails
        $publicAppUrl = 'https://sportsjunction.in';
        $rawAppUrl = trim((string) Env::get('APP_URL', ''));
        if (!empty($rawAppUrl) && !str_contains($rawAppUrl, 'localhost') && !str_contains($rawAppUrl, '127.0.0.1')) {
            $publicAppUrl = rtrim($rawAppUrl, '/');
            if (str_starts_with($publicAppUrl, 'http://')) {
                $publicAppUrl = 'https://' . substr($publicAppUrl, 7);
            }
        } elseif (!empty($_SERVER['HTTP_HOST']) && !str_contains($_SERVER['HTTP_HOST'], 'localhost') && !str_contains($_SERVER['HTTP_HOST'], '127.0.0.1')) {
            $publicAppUrl = 'https://' . preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']);
        }

        // Always dynamically resolve the active site logo from SiteSetting
        $logoUrl = SiteSetting::getEmailLogoUrl();

        $ctaHtml = '';
        if (!empty($ctaUrl) && !empty($ctaText)) {
            $ctaFullUrl = (str_starts_with($ctaUrl, 'http://') || str_starts_with($ctaUrl, 'https://')) 
                ? $ctaUrl 
                : ($publicAppUrl . '/' . ltrim($ctaUrl, '/'));
            $ctaHtml = "
            <table border='0' cellpadding='0' cellspacing='0' style='margin: 28px 0;'>
                <tr>
                    <td align='center' style='border-radius: 8px; background-color: #00288e;'>
                        <a href='{$ctaFullUrl}' target='_blank' style='display: inline-block; padding: 13px 32px; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 8px; letter-spacing: 0.3px;'>{$ctaText} &rarr;</a>
                    </td>
                </tr>
            </table>
            ";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;-webkit-font-smoothing:antialiased;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed;background-color:#f1f5f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);border:1px solid #e2e8f0;">
                    <!-- Brand Header -->
                    <tr>
                        <td style="padding:28px 36px 20px 36px;background-color:#ffffff;border-bottom:3px solid #00288e;text-align:left;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td valign="middle">
                                        <a href="{$publicAppUrl}" target="_blank" style="text-decoration:none;display:inline-block;">
                                            <img src="{$logoUrl}" alt="Sports Junction" width="180" border="0" style="display:block;width:180px;max-width:180px;height:auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:18px;font-weight:800;color:#00288e;border:0;outline:none;text-decoration:none;" />
                                        </a>
                                    </td>
                                    <td valign="middle" align="right" style="font-size:11px;font-weight:700;color:#00288e;letter-spacing:1px;text-transform:uppercase;">
                                        B2B Marketplace
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Badge Row -->
                    <tr>
                        <td style="padding:24px 36px 0 36px;">
                            <span style="display:inline-block;padding:5px 14px;background-color:{$badgeBg};color:{$badgeColor};font-size:11px;font-weight:800;border-radius:999px;text-transform:uppercase;letter-spacing:0.8px;">{$badge}</span>
                        </td>
                    </tr>
                    <!-- Content Body -->
                    <tr>
                        <td style="padding:16px 36px 32px 36px;font-size:15px;line-height:1.65;color:#334155;">
                            {$contentHtml}
                            {$ctaHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 36px;background-color:#f8fafc;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;line-height:1.6;">
                            <p style="margin:0 0 6px 0;font-weight:600;color:#0f172a;">Sports Junction &mdash; India's Sports Manufacturing Marketplace</p>
                            <p style="margin:0 0 6px 0;">This is an automated transmission from Sports Junction B2B Marketplace. Please do not reply directly to this email unless specified.</p>
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

    /**
     * Helper to render styled OTP passcode box
     */
    public static function renderOtpBox(string $otp, string $expiryMinutes = '10'): string
    {
        return "
        <div style='margin:26px 0;text-align:center;'>
            <div style='display:inline-block;padding:16px 38px;background-color:#f0fdf4;border:2px dashed #00D084;border-radius:12px;font-size:32px;font-weight:800;letter-spacing:8px;color:#047857;font-family:Consolas, Monaco, monospace;'>
                {$otp}
            </div>
            <p style='margin:10px 0 0 0;font-size:12px;color:#64748b;'>Valid for <strong>{$expiryMinutes} minutes</strong> &bull; Do not share this single-use code</p>
        </div>
        ";
    }

    /**
     * Helper to render styled security feedback or reason callout box
     */
    public static function renderCalloutBox(string $title, string $text, string $theme = 'warning'): string
    {
        $bg = ($theme === 'danger' || $theme === 'warning') ? '#fef2f2' : '#f8fafc';
        $border = ($theme === 'danger' || $theme === 'warning') ? '#ef4444' : '#00288e';
        $titleColor = ($theme === 'danger' || $theme === 'warning') ? '#991b1b' : '#0f172a';
        $textColor = ($theme === 'danger' || $theme === 'warning') ? '#7f1d1d' : '#334155';

        return "
        <div style='margin:18px 0;padding:16px 20px;background-color:{$bg};border-left:4px solid {$border};border-radius:8px;'>
            <strong style='display:block;margin-bottom:6px;color:{$titleColor};font-size:13px;text-transform:uppercase;letter-spacing:0.5px;'>{$title}</strong>
            <div style='color:{$textColor};font-size:14px;line-height:1.6;'>" . nl2br(htmlspecialchars($text)) . "</div>
        </div>
        ";
    }

    // =========================================================================
    // 27 COMPLETE TRANSACTIONAL EMAIL TEMPLATES (A through AA)
    // =========================================================================

    // -------------------------------------------------------------------------
    // Template A: Buyer Signup OTP
    // -------------------------------------------------------------------------
    public static function sendBuyerSignupOtp(string $email, string $name, string $otp): bool
    {
        self::recordSentOtp($email, $otp);
        $name = trim($name) !== '' ? $name : 'Valued Buyer';
        $subject = "Verify Your Email Address — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Verify Your Buyer Account</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Welcome to <strong>Sports Junction</strong>! To complete your buyer account registration and access factory-direct quotations from verified manufacturers across India, please enter the single-use passcode below:</p>
            " . self::renderOtpBox($otp) . "
            <p style='font-size:13px;color:#64748b;'>Once verified, you will be able to submit wholesale RFQs, request custom OEM/ODM production, and negotiate directly with manufacturing facilities.</p>
        ";
        return self::send($email, $name, $subject, self::renderTemplate("Account Verification", "Email Verification", $content));
    }

    // -------------------------------------------------------------------------
    // Template B: Supplier Signup OTP
    // -------------------------------------------------------------------------
    public static function sendSupplierSignupOtp(string $email, string $companyName, string $otp): bool
    {
        self::recordSentOtp($email, $otp);
        $companyName = trim($companyName) !== '' ? $companyName : 'Manufacturing Facility';
        $subject = "Verify Your Manufacturer Account — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Verify Your Manufacturer Account</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Thank you for initiating your manufacturer registration on Sports Junction. To verify your corporate email address and submit your factory profile for Super Admin verification, please use the passcode below:</p>
            " . self::renderOtpBox($otp) . "
            <p style='font-size:13px;color:#64748b;'>Once verified, your corporate registration, business categories, and factory credentials will be queued for Super Admin review. Passcode expires in 10 minutes.</p>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Manufacturer Verification", "Facility Authentication", $content));
    }

    // -------------------------------------------------------------------------
    // Template C: OTP Resend (Buyer & Supplier Variants)
    // -------------------------------------------------------------------------
    public static function sendOtpResend(string $email, string $recipientName, string $otp, bool $isSupplier = false): bool
    {
        self::recordSentOtp($email, $otp);
        $name = trim($recipientName) !== '' ? $recipientName : ($isSupplier ? 'Manufacturer' : 'Valued Buyer');
        $subject = "Your New Verification Code — Sports Junction";
        $portal = $isSupplier ? "manufacturer onboarding" : "buyer account";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Your New Verification Passcode</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>You requested a fresh verification passcode for your Sports Junction {$portal}. Any previously generated codes have been invalidated. Please use your new single-use code below:</p>
            " . self::renderOtpBox($otp) . "
            <p style='font-size:13px;color:#64748b;'>If you did not request this new code, you can safely ignore this email.</p>
        ";
        return self::send($email, $name, $subject, self::renderTemplate("New Passcode", "Verification Code", $content));
    }

    // -------------------------------------------------------------------------
    // Template D: New Supplier Profile Under Review
    // -------------------------------------------------------------------------
    public static function sendSupplierApplicationReceived(string $email, string $companyName): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Your Supplier Profile Is Under Review — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Supplier Profile Under Admin Review</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Your official email address has been verified and your manufacturer registration has been received by Sports Junction.</p>
            <p>Your factory profile is currently queued for review by our Super Administrator compliance desk. We evaluate business registrations, tax documentation, and manufacturing capabilities to maintain a trustworthy B2B ecosystem.</p>
            <p style='font-size:14px;color:#475569;'>Standard review turnaround is <strong>1 to 2 business days</strong>. You will receive an email as soon as your supplier dashboard access is activated.</p>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Profile Under Review", "Compliance Review", $content, "/login", "Check Status"));
    }

    // -------------------------------------------------------------------------
    // Template E: Supplier Profile Approved — Creation
    // -------------------------------------------------------------------------
    public static function sendSupplierApproved(string $email, string $companyName): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Your Supplier Profile Has Been Approved — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#15803d;font-size:22px;font-weight:700;'>Supplier Application Approved!</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Congratulations! Your Sports Junction manufacturer account has been reviewed and approved by the Super Administrator.</p>
            <p>Your factory store is now fully authorized. You can immediately access your Supplier Portal to:</p>
            <ul style='padding-left:20px;line-height:1.8;color:#334155;'>
                <li>Publish sports products, equipment, and apparel listings</li>
                <li>Receive factory-direct wholesale inquiries and RFQs from verified buyers</li>
                <li>Negotiate order quantities, FOB/CIF pricing, and custom specifications</li>
            </ul>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Application Approved", "Approved Facility", $content, "/supplier/dashboard", "Access Supplier Dashboard", '#15803d', '#f0fdf4'));
    }

    // -------------------------------------------------------------------------
    // Template F: Supplier Profile Rejected — Creation
    // -------------------------------------------------------------------------
    public static function sendSupplierRejected(string $email, string $companyName, string $reason): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Update Required for Your Supplier Profile — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#b91c1c;font-size:22px;font-weight:700;'>Supplier Application Update Required</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Thank you for your interest in partnering with Sports Junction. Our compliance review desk evaluated your manufacturer registration, and we require additional details before your profile can be approved:</p>
            " . self::renderCalloutBox("Reason for Review Feedback", $reason, "danger") . "
            <p>Please log in to your account, update your company profile and uploaded documentation, and resubmit for verification.</p>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Action Required", "Application Feedback", $content, "/login", "Update Supplier Profile", '#b91c1c', '#fef2f2'));
    }

    // -------------------------------------------------------------------------
    // Template G: Supplier Profile Update Under Review
    // -------------------------------------------------------------------------
    public static function sendSupplierProfileUpdateUnderReview(string $email, string $companyName): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Your Supplier Profile Update Is Under Review — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Profile Modifications Under Review</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>We have received your requested updates to your factory company profile and certifications.</p>
            <p>To preserve marketplace trust, all revisions to manufacturer credentials, addresses, and business details are reviewed by our Super Admin team.</p>
            <p style='font-size:14px;color:#475569;'><strong>Note:</strong> Your existing live profile remains fully active for buyers while this revision is being evaluated. You will receive an email once approved.</p>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Profile Update Queued", "Admin Review", $content, "/supplier/profile", "View Supplier Profile"));
    }

    // -------------------------------------------------------------------------
    // Template H: Supplier Profile Update Approved
    // -------------------------------------------------------------------------
    public static function sendSupplierProfileUpdateApproved(string $email, string $companyName): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Your Supplier Profile Update Has Been Approved — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#15803d;font-size:22px;font-weight:700;'>Profile Update Approved &amp; Published</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Great news! The Super Administrator has approved your requested factory profile modifications.</p>
            <p>Your updated corporate information, facility capabilities, and contact points are now live across Sports Junction.</p>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Profile Update Approved", "Published Live", $content, "/supplier/profile", "View Live Profile", '#15803d', '#f0fdf4'));
    }

    // -------------------------------------------------------------------------
    // Template I: Supplier Profile Update Rejected
    // -------------------------------------------------------------------------
    public static function sendSupplierProfileUpdateRejected(string $email, string $companyName, string $reason): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Changes to Your Supplier Profile Need Attention — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#b91c1c;font-size:22px;font-weight:700;'>Profile Revision Needs Attention</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>Our compliance team reviewed your recent profile change submission and could not approve the changes in their current form:</p>
            " . self::renderCalloutBox("Feedback from Super Admin", $reason, "danger") . "
            <p>Your previous verified profile details remain active. Please review the feedback, adjust your profile data in the Supplier Portal, and resubmit.</p>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Revision Feedback", "Profile Review", $content, "/supplier/profile", "Review Profile", '#b91c1c', '#fef2f2'));
    }

    // -------------------------------------------------------------------------
    // Template J: Buyer Password Reset OTP
    // -------------------------------------------------------------------------
    public static function sendBuyerPasswordResetOtp(string $email, string $name, string $otp): bool
    {
        self::recordSentOtp($email, $otp);
        $name = trim($name) !== '' ? $name : 'Valued Buyer';
        $subject = "Password Reset Verification — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Password Recovery Code</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We received a request to reset the password for your Sports Junction buyer account. Please enter the single-use recovery code below:</p>
            " . self::renderOtpBox($otp) . "
            <p style='font-size:13px;color:#64748b;'>This code expires in 10 minutes. If you did not initiate this request, your account remains secure and you may safely ignore this message.</p>
        ";
        return self::send($email, $name, $subject, self::renderTemplate("Password Recovery", "Security Alert", $content, "/reset-password", "Enter Reset Code"));
    }

    // -------------------------------------------------------------------------
    // Template K: Supplier Password Reset OTP
    // -------------------------------------------------------------------------
    public static function sendSupplierPasswordResetOtp(string $email, string $companyName, string $otp): bool
    {
        self::recordSentOtp($email, $otp);
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Password Reset Verification — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Manufacturer Password Recovery</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>A password reset request was initiated for your Sports Junction manufacturer account. Use the single-use passcode below to authorize your password update:</p>
            " . self::renderOtpBox($otp) . "
            <p style='font-size:13px;color:#64748b;'>This recovery code will expire in 10 minutes. If you did not request this, please verify your account security.</p>
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Password Recovery", "Security Alert", $content, "/reset-password", "Enter Reset Code"));
    }

    // -------------------------------------------------------------------------
    // Template L: Buyer Password Updated Confirmation
    // -------------------------------------------------------------------------
    public static function sendBuyerPasswordUpdated(string $email, string $name): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued Buyer';
        $subject = "Your Sports Junction Password Has Been Updated";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#15803d;font-size:22px;font-weight:700;'>Password Successfully Changed</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>The password for your Sports Junction buyer account was successfully updated on " . date('d M Y, h:i A T') . ".</p>
            " . self::renderCalloutBox("Security Reminder", "If you authorized this change, no further action is required. If you did NOT initiate this password reset, please contact support immediately to lock unauthorized access.", "warning") . "
        ";
        return self::send($email, $name, $subject, self::renderTemplate("Password Changed", "Security Notice", $content, "/login", "Sign In to Your Account"));
    }

    // -------------------------------------------------------------------------
    // Template M: Supplier Password Updated Confirmation
    // -------------------------------------------------------------------------
    public static function sendSupplierPasswordUpdated(string $email, string $companyName): bool
    {
        $companyName = trim($companyName) !== '' ? $companyName : 'Valued Manufacturer';
        $subject = "Your Sports Junction Password Has Been Updated";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#15803d;font-size:22px;font-weight:700;'>Supplier Password Updated</h2>
            <p>Dear <strong>" . htmlspecialchars($companyName) . "</strong>,</p>
            <p>The security credentials for your Sports Junction manufacturer account were updated on " . date('d M Y, h:i A T') . ".</p>
            " . self::renderCalloutBox("Important Notice", "If this update was authorized by your team, you may safely disregard this message. If unauthorized, notify platform administration immediately.", "warning") . "
        ";
        return self::send($email, $companyName, $subject, self::renderTemplate("Password Changed", "Security Notice", $content, "/seller/login", "Sign In to Supplier Portal"));
    }

    // -------------------------------------------------------------------------
    // Template N: RFQ Buyer Confirmation
    // -------------------------------------------------------------------------
    public static function sendRfqBuyerConfirmation(array $inquiry): bool
    {
        $toEmail = trim((string)($inquiry['buyer_email'] ?? ''));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ref = '#RFQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'Valued Buyer'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $subject = trim((string)($inquiry['subject'] ?? 'Request for Quotation'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));
        $attachment = !empty($inquiry['attachment_name']) ? htmlspecialchars($inquiry['attachment_name']) : 'None';

        $emailSubject = "RFQ Received — Reference {$ref}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Request for Quotation Transmitted</h2>
            <p>Dear <strong>" . htmlspecialchars($buyerName) . "</strong>,</p>
            <p>Thank you for submitting your Request for Quotation (RFQ) on Sports Junction. Our factory trade desk and verified manufacturers have received your technical specifications:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:14px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;width:150px;'>Reference No:</td><td style='padding:8px 0;font-weight:700;color:#0f172a;'>{$ref}</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Requirement:</td><td style='padding:8px 0;font-weight:600;color:#0f172a;'>" . htmlspecialchars($subject) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Target Quantity:</td><td style='padding:8px 0;color:#0f172a;'>" . number_format($qty) . " Units</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Destination:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($country) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Attachment:</td><td style='padding:8px 0;color:#0f172a;'>{$attachment}</td></tr>
                <tr><td style='padding:8px 0;color:#64748b;vertical-align:top;'>Specifications:</td><td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td></tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>Our manufacturing engineering teams typically provide direct factory FOB / CIF quotations within 2.4 business hours.</p>
        ";

        return self::send($toEmail, $buyerName, $emailSubject, self::renderTemplate("RFQ Confirmation", "Enquiry {$ref}", $content, "/buyer/dashboard", "View in Buyer Portal"));
    }

    // -------------------------------------------------------------------------
    // Template O: RFQ Supplier / Admin Notification
    // -------------------------------------------------------------------------
    public static function sendRfqSupplierOrAdminNotification(array $inquiry, ?array $supplier = null, ?string $adminEmail = null): bool
    {
        $ref = '#RFQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'Procurement Buyer'));
        $buyerCompany = trim((string)($inquiry['company_name'] ?? ''));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $subject = trim((string)($inquiry['subject'] ?? 'Request for Quotation'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));
        $emailSubject = "New RFQ Received — {$ref}";

        $recipientEmail = !empty($supplier['email']) ? $supplier['email'] : ($adminEmail ?? Env::get('SUPER_ADMIN_EMAIL', 'sportjunctions@gmail.com'));
        $recipientName = !empty($supplier['company_name']) ? $supplier['company_name'] : "Super Admin Trade Desk";

        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>New Direct Factory RFQ — {$ref}</h2>
            <p>Dear <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
            <p>A new Request for Quotation has been submitted by a procurement buyer on Sports Junction:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:14px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;width:150px;'>Reference No:</td><td style='padding:8px 0;font-weight:700;color:#0f172a;'>{$ref}</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Buyer Company:</td><td style='padding:8px 0;font-weight:600;color:#0f172a;'>" . htmlspecialchars($buyerCompany ?: $buyerName) . " (" . htmlspecialchars($country) . ")</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Product Requirement:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($subject) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Target Volume:</td><td style='padding:8px 0;font-weight:700;color:#00288e;'>" . number_format($qty) . " Units</td></tr>
                <tr><td style='padding:8px 0;color:#64748b;vertical-align:top;'>Specifications:</td><td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td></tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>Please review the buyer's technical specifications and respond with factory pricing.</p>
        ";

        return self::send($recipientEmail, $recipientName, $emailSubject, self::renderTemplate("New RFQ Opportunity", "RFQ Lead", $content, "/seller/login", "Review RFQ"));
    }

    // -------------------------------------------------------------------------
    // Template P: OEM Buyer Confirmation
    // -------------------------------------------------------------------------
    public static function sendOemBuyerConfirmation(array $inquiry): bool
    {
        $toEmail = trim((string)($inquiry['buyer_email'] ?? ''));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ref = '#OEM-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'Valued Buyer'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $subject = trim((string)($inquiry['subject'] ?? 'Custom Manufacturing / OEM Enquiry'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));

        $emailSubject = "Your OEM/Customization Enquiry Has Been Received";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>OEM / ODM Project Received</h2>
            <p>Dear <strong>" . htmlspecialchars($buyerName) . "</strong>,</p>
            <p>Thank you for submitting your custom manufacturing inquiry on Sports Junction. Our OEM engineering specialists and certified sports manufacturers have received your project details:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:14px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;width:150px;'>Project Reference:</td><td style='padding:8px 0;font-weight:700;color:#0f172a;'>{$ref}</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Custom Product:</td><td style='padding:8px 0;font-weight:600;color:#0f172a;'>" . htmlspecialchars($subject) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Production Run:</td><td style='padding:8px 0;color:#0f172a;'>" . number_format($qty) . " Units</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Destination:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($country) . "</td></tr>
                <tr><td style='padding:8px 0;color:#64748b;vertical-align:top;'>Custom Specs:</td><td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td></tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>Our OEM engineering leads will evaluate tooling feasibility, sample development timelines, and minimum order parameters within 2.4 business hours.</p>
        ";

        return self::send($toEmail, $buyerName, $emailSubject, self::renderTemplate("OEM Project Received", "OEM Enquiry", $content, "/buyer/dashboard", "Track OEM Project"));
    }

    // -------------------------------------------------------------------------
    // Template Q: OEM Supplier / Admin Notification
    // -------------------------------------------------------------------------
    public static function sendOemSupplierOrAdminNotification(array $inquiry, ?array $supplier = null, ?string $adminEmail = null): bool
    {
        $ref = '#OEM-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'OEM Client'));
        $buyerCompany = trim((string)($inquiry['company_name'] ?? ''));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $subject = trim((string)($inquiry['subject'] ?? 'Custom Manufacturing'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));

        $recipientEmail = !empty($supplier['email']) ? $supplier['email'] : ($adminEmail ?? Env::get('SUPER_ADMIN_EMAIL', 'sportjunctions@gmail.com'));
        $recipientName = !empty($supplier['company_name']) ? $supplier['company_name'] : "Sports Junction OEM Desk";

        $emailSubject = "New OEM/Customization Enquiry — {$ref}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>New OEM / ODM Custom Lead — {$ref}</h2>
            <p>Dear <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
            <p>A procurement buyer has submitted an OEM custom manufacturing inquiry:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:14px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;width:150px;'>Reference:</td><td style='padding:8px 0;font-weight:700;color:#0f172a;'>{$ref}</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Buyer:</td><td style='padding:8px 0;font-weight:600;color:#0f172a;'>" . htmlspecialchars($buyerCompany ?: $buyerName) . " (" . htmlspecialchars($country) . ")</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Custom Item:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($subject) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Production Target:</td><td style='padding:8px 0;font-weight:700;color:#00288e;'>" . number_format($qty) . " Units</td></tr>
                <tr><td style='padding:8px 0;color:#64748b;vertical-align:top;'>Specifications:</td><td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td></tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>Please check manufacturing capacity and connect with the buyer regarding tooling and proto samples.</p>
        ";

        return self::send($recipientEmail, $recipientName, $emailSubject, self::renderTemplate("OEM Manufacturing Lead", "OEM Lead", $content, "/seller/login", "Open OEM Project"));
    }

    // -------------------------------------------------------------------------
    // Template R: Product Enquiry Buyer Confirmation
    // -------------------------------------------------------------------------
    public static function sendProductEnquiryBuyerConfirmation(array $inquiry): bool
    {
        $toEmail = trim((string)($inquiry['buyer_email'] ?? ''));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $buyerName = trim((string)($inquiry['buyer_name'] ?? 'Valued Buyer'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $qty = (int)($inquiry['target_quantity'] ?? 1);
        $subject = trim((string)($inquiry['subject'] ?? 'Product Enquiry'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));

        $emailSubject = "Your Product Enquiry Has Been Submitted";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Product Enquiry Confirmed</h2>
            <p>Dear <strong>" . htmlspecialchars($buyerName) . "</strong>,</p>
            <p>Your product enquiry on Sports Junction has been successfully recorded and dispatched to the manufacturer:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:14px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;width:150px;'>Enquiry Ref:</td><td style='padding:8px 0;font-weight:700;color:#0f172a;'>{$ref}</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Product:</td><td style='padding:8px 0;font-weight:600;color:#0f172a;'>" . htmlspecialchars($subject) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Target Quantity:</td><td style='padding:8px 0;color:#0f172a;'>" . number_format($qty) . " Units</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Destination:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($country) . "</td></tr>
                <tr><td style='padding:8px 0;color:#64748b;vertical-align:top;'>Requirements:</td><td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td></tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>The verified supplier will review your enquiry and get back to you with quotation details.</p>
        ";

        return self::send($toEmail, $buyerName, $emailSubject, self::renderTemplate("Product Enquiry Submitted", "Inquiry {$ref}", $content, "/buyer/dashboard", "View My Enquiries"));
    }

    // -------------------------------------------------------------------------
    // Template S: Product Enquiry Supplier Notification
    // -------------------------------------------------------------------------
    public static function sendProductEnquirySupplierNotification(array $inquiry, array $supplier): bool
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
        $subject = trim((string)($inquiry['subject'] ?? 'Product Catalog Item'));
        $specs = trim((string)($inquiry['specifications'] ?? ''));

        $productName = $subject;
        $emailSubject = "New Buyer Enquiry for {$productName}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>New Inquiry on Your Product — {$ref}</h2>
            <p>Dear <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>A buyer has sent a wholesale purchase inquiry on your catalog product:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:14px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;width:150px;'>Reference:</td><td style='padding:8px 0;font-weight:700;color:#0f172a;'>{$ref}</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Buyer Company:</td><td style='padding:8px 0;font-weight:600;color:#0f172a;'>" . htmlspecialchars($buyerCompany) . " (" . htmlspecialchars($country) . ")</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Catalog Product:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($productName) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Target Quantity:</td><td style='padding:8px 0;font-weight:700;color:#00288e;'>" . number_format($qty) . " Units</td></tr>
                <tr><td style='padding:8px 0;color:#64748b;vertical-align:top;'>Specifications:</td><td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($specs)) . "</td></tr>
            </table>

            <p style='font-size:13px;color:#64748b;'>Sign in to your Supplier Portal to submit your formal quotation and negotiate directly.</p>
        ";

        return self::send($supplierEmail, $supplierName, $emailSubject, self::renderTemplate("New Buyer Lead", "Product Lead", $content, "/seller/login", "Reply to Buyer"));
    }

    // -------------------------------------------------------------------------
    // Template T: Contact Us Confirmation
    // -------------------------------------------------------------------------
    public static function sendContactUsConfirmationToUser(array $inquiry): bool
    {
        $toEmail = trim((string)($inquiry['buyer_email'] ?? ''));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $name = trim((string)($inquiry['buyer_name'] ?? 'Valued Customer'));
        $subject = trim((string)($inquiry['subject'] ?? 'Message Received'));
        $message = trim((string)($inquiry['specifications'] ?? ''));

        $emailSubject = "We Have Received Your Message — Sports Junction";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Thank You for Contacting Sports Junction</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We have successfully received your message (Reference: <strong>{$ref}</strong>). Our sourcing consultants and customer support team will review your enquiry promptly.</p>
            
            " . self::renderCalloutBox("Your Message Details (" . htmlspecialchars($subject) . ")", $message, "info") . "
            
            <p style='font-size:13px;color:#64748b;'>Average response time: less than 2.4 business hours.</p>
        ";

        return self::send($toEmail, $name, $emailSubject, self::renderTemplate("Contact Request Received", "Customer Support", $content));
    }

    // -------------------------------------------------------------------------
    // Template U: Contact Us Internal Notification
    // -------------------------------------------------------------------------
    public static function sendContactUsNotificationToAdmin(array $inquiry, ?string $adminEmail = null): bool
    {
        $targetEmail = $adminEmail ?? Env::get('SUPER_ADMIN_EMAIL', 'sportjunctions@gmail.com');
        $ref = '#ENQ-' . str_pad((string)($inquiry['id'] ?? 0), 5, '0', STR_PAD_LEFT);
        $name = trim((string)($inquiry['buyer_name'] ?? 'Website Visitor'));
        $email = trim((string)($inquiry['buyer_email'] ?? ''));
        $phone = trim((string)($inquiry['buyer_phone'] ?? 'N/A'));
        $company = trim((string)($inquiry['company_name'] ?? 'N/A'));
        $country = trim((string)($inquiry['country_name'] ?? 'International'));
        $subject = trim((string)($inquiry['subject'] ?? 'General Inquiry'));
        $message = trim((string)($inquiry['specifications'] ?? ''));

        $emailSubject = "New Contact Us Enquiry — {$ref}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>New Public Contact Desk Message — {$ref}</h2>
            <p>A new visitor message has been submitted via the Sports Junction contact portal:</p>
            
            <table style='width:100%;margin:20px 0;border-collapse:collapse;font-size:14px;'>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;width:150px;'>Submitter:</td><td style='padding:8px 0;font-weight:700;color:#0f172a;'>" . htmlspecialchars($name) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Email:</td><td style='padding:8px 0;color:#0f172a;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Phone:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($phone) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Company:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($company) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Country:</td><td style='padding:8px 0;color:#0f172a;'>" . htmlspecialchars($country) . "</td></tr>
                <tr style='border-bottom:1px solid #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Subject:</td><td style='padding:8px 0;font-weight:600;color:#0f172a;'>" . htmlspecialchars($subject) . "</td></tr>
                <tr><td style='padding:8px 0;color:#64748b;vertical-align:top;'>Message:</td><td style='padding:8px 0;color:#334155;'>" . nl2br(htmlspecialchars($message)) . "</td></tr>
            </table>
        ";

        return self::send($targetEmail, "Sports Junction Trade Desk", $emailSubject, self::renderTemplate("Contact Desk Message", "Support Alert", $content, "/admin/dashboard", "View in Admin Portal", '#00288e', '#e0e7ff'));
    }

    // -------------------------------------------------------------------------
    // Template V: New Buyer Chat Notification to Supplier (1st message only)
    // -------------------------------------------------------------------------
    public static function sendBuyerChatNotificationToSupplier(
        string $supplierEmail,
        string $supplierName,
        string $buyerName,
        string $buyerCompany,
        string $messageSnippet,
        int $conversationId
    ): bool {
        $subject = "New Buyer Message — Sports Junction";
        $senderLabel = !empty($buyerCompany) ? "{$buyerName} ({$buyerCompany})" : $buyerName;
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>A Buyer Started a Conversation</h2>
            <p>Dear <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p><strong>" . htmlspecialchars($senderLabel) . "</strong> has initiated a new direct negotiation thread with your manufacturing facility on Sports Junction.</p>
            
            " . self::renderCalloutBox("Message Excerpt", $messageSnippet, "info") . "
            
            <p style='font-size:13px;color:#64748b;'>Timely responses significantly increase quotation conversion rates on Sports Junction.</p>
        ";

        return self::send(
            $supplierEmail,
            $supplierName,
            $subject,
            self::renderTemplate("New Conversation", "Buyer Message", $content, "/supplier/messages?conversation_id={$conversationId}", "Open Message & Reply")
        );
    }

    // -------------------------------------------------------------------------
    // Template W: Product Creation Approved
    // -------------------------------------------------------------------------
    public static function sendProductCreationApproved(string $supplierEmail, string $supplierName, string $productName): bool
    {
        $subject = "Your Product Has Been Approved — {$productName}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#15803d;font-size:22px;font-weight:700;'>Product Approved &amp; Published!</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>Great news! Your newly submitted product <strong>\"" . htmlspecialchars($productName) . "\"</strong> has been reviewed and approved by the Super Admin.</p>
            <p>It is now published live on Sports Junction, indexed across category listings, and accessible to domestic and global wholesale buyers.</p>
        ";
        return self::send(
            $supplierEmail,
            $supplierName,
            $subject,
            self::renderTemplate("Product Live", "Approved & Live", $content, "/supplier/products", "View in Catalog", '#15803d', '#f0fdf4')
        );
    }

    // -------------------------------------------------------------------------
    // Template X: Product Creation Rejected
    // -------------------------------------------------------------------------
    public static function sendProductCreationRejected(string $supplierEmail, string $supplierName, string $productName, string $reason): bool
    {
        $subject = "Action Required: Product Submission Not Approved";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#b91c1c;font-size:22px;font-weight:700;'>Product Submission Feedback</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>Your product submission <strong>\"" . htmlspecialchars($productName) . "\"</strong> requires modifications before it can be published in the marketplace:</p>
            
            " . self::renderCalloutBox("Reason for Rejection / Modification Required", $reason, "danger") . "
            
            <p>Please edit the product specifications, pricing, or media in your Supplier Portal and resubmit for approval.</p>
        ";
        return self::send(
            $supplierEmail,
            $supplierName,
            $subject,
            self::renderTemplate("Submission Feedback", "Review Required", $content, "/supplier/products", "Edit & Resubmit Product", '#b91c1c', '#fef2f2')
        );
    }

    // -------------------------------------------------------------------------
    // Template Y: Product Update Under Review
    // -------------------------------------------------------------------------
    public static function sendProductUpdateUnderReview(string $supplierEmail, string $supplierName, string $productName): bool
    {
        $subject = "Your Product Update Is Under Review — {$productName}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Product Modifications Queued for Review</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>Your updated specifications, pricing, or images for <strong>\"" . htmlspecialchars($productName) . "\"</strong> have been submitted for Super Admin review.</p>
            <p style='font-size:14px;color:#475569;'><strong>Note:</strong> The currently live catalog version of this product remains visible to buyers until your changes are reviewed and approved.</p>
        ";
        return self::send(
            $supplierEmail,
            $supplierName,
            $subject,
            self::renderTemplate("Update Queued", "Pending Review", $content, "/supplier/products", "Manage Products")
        );
    }

    // -------------------------------------------------------------------------
    // Template Z: Product Update Approved
    // -------------------------------------------------------------------------
    public static function sendProductUpdateApproved(string $supplierEmail, string $supplierName, string $productName): bool
    {
        $subject = "Your Product Update Has Been Approved — {$productName}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#15803d;font-size:22px;font-weight:700;'>Product Modifications Approved!</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>The Super Admin has reviewed and approved your modifications for <strong>\"" . htmlspecialchars($productName) . "\"</strong>.</p>
            <p>Your new specifications, updated wholesale tier pricing, and media assets are now live in the public catalog.</p>
        ";
        return self::send(
            $supplierEmail,
            $supplierName,
            $subject,
            self::renderTemplate("Product Updated", "Approved & Live", $content, "/supplier/products", "View Live Listing", '#15803d', '#f0fdf4')
        );
    }

    // -------------------------------------------------------------------------
    // Template AA: Product Update Rejected
    // -------------------------------------------------------------------------
    public static function sendProductUpdateRejected(string $supplierEmail, string $supplierName, string $productName, string $reason): bool
    {
        $subject = "Changes to Your Product Need Attention — {$productName}";
        $content = "
            <h2 style='margin:0 0 14px 0;color:#b91c1c;font-size:22px;font-weight:700;'>Product Modifications Not Approved</h2>
            <p>Hello <strong>" . htmlspecialchars($supplierName) . "</strong>,</p>
            <p>The Super Admin reviewed your recent modifications for <strong>\"" . htmlspecialchars($productName) . "\"</strong> and could not approve them:</p>
            
            " . self::renderCalloutBox("Modification Feedback", $reason, "danger") . "
            
            <p>Your previous live catalog listing remains active without disruption. You may revise the pending changes and resubmit whenever ready.</p>
        ";
        return self::send(
            $supplierEmail,
            $supplierName,
            $subject,
            self::renderTemplate("Update Feedback", "Review Required", $content, "/supplier/products", "Review Product Changes", '#b91c1c', '#fef2f2')
        );
    }

    // =========================================================================
    // BACKWARD COMPATIBLE ALIASES & UTILITIES
    // =========================================================================

    public static function sendBuyerOtp(string $email, string $name = 'Valued Buyer', string $otp = ''): bool
    {
        return self::sendBuyerSignupOtp($email, $name, $otp);
    }

    public static function sendSupplierOtp(string $email, string $companyName = 'Manufacturing Facility', string $otp = ''): bool
    {
        return self::sendSupplierSignupOtp($email, $companyName, $otp);
    }

    public static function sendPasswordResetOtp(string $email, string $name = 'Valued User', string $otp = ''): bool
    {
        return self::sendBuyerPasswordResetOtp($email, $name, $otp);
    }

    public static function sendBuyerVerified(string $email, string $name = 'Valued Customer'): bool
    {
        $name = trim($name) !== '' ? $name : 'Valued Customer';
        $content = "
            <h2 style='margin:0 0 14px 0;color:#15803d;font-size:22px;font-weight:700;'>Account Verified Successfully!</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your email has been verified and your buyer account is fully activated.</p>
            <ul style='padding-left:20px;line-height:1.8;color:#475569;'>
                <li>Access wholesale price matrices and minimum order quantities</li>
                <li>Submit direct RFQs to verified manufacturing facilities</li>
                <li>Request custom OEM / ODM private labeling</li>
            </ul>
        ";
        return self::send($email, $name, "Account Verified — Welcome to Sports Junction", self::renderTemplate("Account Activated", "Verification Complete", $content, "/buyer/dashboard", "Access Buyer Portal", '#15803d', '#f0fdf4'));
    }

    public static function sendProductSubmitted(string $email, string $supplierName = 'Supplier', string $productTitle = 'Product'): bool
    {
        return self::sendProductUpdateUnderReview($email, $supplierName, $productTitle);
    }

    public static function sendProductApproved(string $email, string $supplierName = 'Supplier', string $productTitle = 'Product'): bool
    {
        return self::sendProductCreationApproved($email, $supplierName, $productTitle);
    }

    public static function sendProductRejected(string $email, string $supplierName = 'Supplier', string $productTitle = 'Product', string $reason = 'Specifications review criteria not met'): bool
    {
        return self::sendProductCreationRejected($email, $supplierName, $productTitle, $reason);
    }

    public static function sendEnquiryConfirmationToBuyer(array $inquiry): bool
    {
        $source = $inquiry['enquiry_source'] ?? 'GENERAL';
        if ($source === 'RFQ') {
            return self::sendRfqBuyerConfirmation($inquiry);
        } elseif ($source === 'FAST_OEM') {
            return self::sendOemBuyerConfirmation($inquiry);
        }
        return self::sendProductEnquiryBuyerConfirmation($inquiry);
    }

    public static function sendEnquiryNotificationToSupplier(array $inquiry, array $supplier): bool
    {
        $source = $inquiry['enquiry_source'] ?? 'GENERAL';
        if ($source === 'RFQ') {
            return self::sendRfqSupplierOrAdminNotification($inquiry, $supplier);
        } elseif ($source === 'FAST_OEM') {
            return self::sendOemSupplierOrAdminNotification($inquiry, $supplier);
        }
        return self::sendProductEnquirySupplierNotification($inquiry, $supplier);
    }

    public static function sendEnquiryNotificationToAdmin(array $inquiry, ?string $adminEmail = null): bool
    {
        return self::sendContactUsNotificationToAdmin($inquiry, $adminEmail);
    }

    public static function sendProfileEmailChangeOtp(string $email, string $name = 'User', string $otp = ''): bool
    {
        self::recordSentOtp($email, $otp);
        $name = trim($name) !== '' ? $name : 'User';
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>Verify Your New Email Address</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>You requested to update your account email to <strong>" . htmlspecialchars($email) . "</strong>. Please enter the verification code below to confirm this change:</p>
            " . self::renderOtpBox($otp) . "
            <p style='font-size:13px;color:#64748b;'>Your account email remains unchanged until this code is successfully verified.</p>
        ";
        return self::send($email, $name, "Verify Your New Email Address", self::renderTemplate("Email Address Change", "Profile Verification", $content));
    }

    public static function sendSecurityNoticeEmail(string $email, string $name = 'User', string $event = 'Security Update', string $details = ''): bool
    {
        $name = trim($name) !== '' ? $name : 'User';
        $content = "
            <h2 style='margin:0 0 14px 0;color:#0f172a;font-size:22px;font-weight:700;'>" . htmlspecialchars($event) . "</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>This is a security alert regarding your Sports Junction account.</p>
            " . self::renderCalloutBox("Security Details", $details, "info") . "
            <p style='font-size:13px;color:#64748b;'>If you did not authorize this action, please contact support immediately.</p>
        ";
        return self::send($email, $name, "Security Notice: {$event}", self::renderTemplate("Security Notice", "Account Alert", $content));
    }

    // =========================================================================
    // TEMPLATE INVENTORY REGISTRY (Used by Super Admin Preview & Test-Send Tool)
    // =========================================================================

    public static function getTemplateInventory(): array
    {
        return [
            'template_a' => [
                'code' => 'A',
                'name' => 'Buyer Signup OTP',
                'category' => 'Authentication & Onboarding',
                'subject' => 'Verify Your Email Address — Sports Junction',
                'badge' => 'Email Verification',
                'description' => 'Sent to buyers registering an account with a 6-digit OTP passcode.',
                'sample_recipient' => 'buyer@example.com',
                'sample_name' => 'Rahul Sharma',
                'sample_data' => ['name' => 'Rahul Sharma', 'otp' => '482915'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendBuyerSignupOtp($email, $data['name'], $data['otp']);
                }
            ],
            'template_b' => [
                'code' => 'B',
                'name' => 'Supplier Signup OTP',
                'category' => 'Authentication & Onboarding',
                'subject' => 'Verify Your Manufacturer Account — Sports Junction',
                'badge' => 'Facility Authentication',
                'description' => 'Sent to manufacturers registering a factory store with a 6-digit OTP passcode.',
                'sample_recipient' => 'factory@apexgears.com',
                'sample_name' => 'Apex Sports Gear Ltd',
                'sample_data' => ['company_name' => 'Apex Sports Gear Ltd', 'otp' => '719304'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierSignupOtp($email, $data['company_name'], $data['otp']);
                }
            ],
            'template_c' => [
                'code' => 'C',
                'name' => 'OTP Resend (Buyer / Supplier)',
                'category' => 'Authentication & Onboarding',
                'subject' => 'Your New Verification Code — Sports Junction',
                'badge' => 'Verification Code',
                'description' => 'Sent when a user requests a fresh verification passcode.',
                'sample_recipient' => 'user@example.com',
                'sample_name' => 'Priya Patel',
                'sample_data' => ['name' => 'Priya Patel', 'otp' => '654321', 'is_supplier' => false],
                'send_callable' => function (string $email, array $data) {
                    return self::sendOtpResend($email, $data['name'], $data['otp'], (bool)($data['is_supplier'] ?? false));
                }
            ],
            'template_d' => [
                'code' => 'D',
                'name' => 'Supplier Profile Under Review',
                'category' => 'Supplier Compliance',
                'subject' => 'Your Supplier Profile Is Under Review — Sports Junction',
                'badge' => 'Compliance Review',
                'description' => 'Sent to newly verified suppliers informing them their factory profile is queued for Super Admin review.',
                'sample_recipient' => 'operations@olympicsports.in',
                'sample_name' => 'Olympic Sports Industries',
                'sample_data' => ['company_name' => 'Olympic Sports Industries'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierApplicationReceived($email, $data['company_name']);
                }
            ],
            'template_e' => [
                'code' => 'E',
                'name' => 'Supplier Profile Approved (Creation)',
                'category' => 'Supplier Compliance',
                'subject' => 'Your Supplier Profile Has Been Approved — Sports Junction',
                'badge' => 'Approved Facility',
                'description' => 'Sent when the Super Admin approves a newly onboarded supplier profile.',
                'sample_recipient' => 'management@titanmanufacturing.com',
                'sample_name' => 'Titan Manufacturing Works',
                'sample_data' => ['company_name' => 'Titan Manufacturing Works'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierApproved($email, $data['company_name']);
                }
            ],
            'template_f' => [
                'code' => 'F',
                'name' => 'Supplier Profile Rejected (Creation)',
                'category' => 'Supplier Compliance',
                'subject' => 'Update Required for Your Supplier Profile — Sports Junction',
                'badge' => 'Application Feedback',
                'description' => 'Sent when a supplier registration needs modification before approval.',
                'sample_recipient' => 'sales@novafabrics.in',
                'sample_name' => 'Nova Fabrics & Gear',
                'sample_data' => [
                    'company_name' => 'Nova Fabrics & Gear',
                    'reason' => 'Uploaded GST registration certificate is blurry and tax ID does not match company legal name. Please re-upload clear government registration docs.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierRejected($email, $data['company_name'], $data['reason']);
                }
            ],
            'template_g' => [
                'code' => 'G',
                'name' => 'Supplier Profile Update Under Review',
                'category' => 'Supplier Compliance',
                'subject' => 'Your Supplier Profile Update Is Under Review — Sports Junction',
                'badge' => 'Admin Review',
                'description' => 'Sent when an existing supplier submits edits to their company credentials.',
                'sample_recipient' => 'admin@proathletic.com',
                'sample_name' => 'Pro Athletic Facilities',
                'sample_data' => ['company_name' => 'Pro Athletic Facilities'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierProfileUpdateUnderReview($email, $data['company_name']);
                }
            ],
            'template_h' => [
                'code' => 'H',
                'name' => 'Supplier Profile Update Approved',
                'category' => 'Supplier Compliance',
                'subject' => 'Your Supplier Profile Update Has Been Approved — Sports Junction',
                'badge' => 'Published Live',
                'description' => 'Sent when the Super Admin approves an existing supplier profile update.',
                'sample_recipient' => 'admin@proathletic.com',
                'sample_name' => 'Pro Athletic Facilities',
                'sample_data' => ['company_name' => 'Pro Athletic Facilities'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierProfileUpdateApproved($email, $data['company_name']);
                }
            ],
            'template_i' => [
                'code' => 'I',
                'name' => 'Supplier Profile Update Rejected',
                'category' => 'Supplier Compliance',
                'subject' => 'Changes to Your Supplier Profile Need Attention — Sports Junction',
                'badge' => 'Profile Review',
                'description' => 'Sent when the Super Admin rejects requested supplier profile changes.',
                'sample_recipient' => 'admin@proathletic.com',
                'sample_name' => 'Pro Athletic Facilities',
                'sample_data' => [
                    'company_name' => 'Pro Athletic Facilities',
                    'reason' => 'The revised factory manufacturing address does not have accompanying utility bill proof. Please upload proof of physical facility premises.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierProfileUpdateRejected($email, $data['company_name'], $data['reason']);
                }
            ],
            'template_j' => [
                'code' => 'J',
                'name' => 'Buyer Password Reset OTP',
                'category' => 'Security',
                'subject' => 'Password Reset Verification — Sports Junction',
                'badge' => 'Security Alert',
                'description' => 'Sent to a buyer requesting account password recovery.',
                'sample_recipient' => 'buyer@retailprocure.com',
                'sample_name' => 'Aman Gupta',
                'sample_data' => ['name' => 'Aman Gupta', 'otp' => '820461'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendBuyerPasswordResetOtp($email, $data['name'], $data['otp']);
                }
            ],
            'template_k' => [
                'code' => 'K',
                'name' => 'Supplier Password Reset OTP',
                'category' => 'Security',
                'subject' => 'Password Reset Verification — Sports Junction',
                'badge' => 'Security Alert',
                'description' => 'Sent to a supplier requesting facility account password recovery.',
                'sample_recipient' => 'security@strikersports.in',
                'sample_name' => 'Striker Sports Pvt Ltd',
                'sample_data' => ['company_name' => 'Striker Sports Pvt Ltd', 'otp' => '391084'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierPasswordResetOtp($email, $data['company_name'], $data['otp']);
                }
            ],
            'template_l' => [
                'code' => 'L',
                'name' => 'Buyer Password Updated',
                'category' => 'Security',
                'subject' => 'Your Sports Junction Password Has Been Updated',
                'badge' => 'Security Notice',
                'description' => 'Sent when a buyer password has been successfully reset or updated.',
                'sample_recipient' => 'buyer@retailprocure.com',
                'sample_name' => 'Aman Gupta',
                'sample_data' => ['name' => 'Aman Gupta'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendBuyerPasswordUpdated($email, $data['name']);
                }
            ],
            'template_m' => [
                'code' => 'M',
                'name' => 'Supplier Password Updated',
                'category' => 'Security',
                'subject' => 'Your Sports Junction Password Has Been Updated',
                'badge' => 'Security Notice',
                'description' => 'Sent when a supplier password has been successfully updated.',
                'sample_recipient' => 'security@strikersports.in',
                'sample_name' => 'Striker Sports Pvt Ltd',
                'sample_data' => ['company_name' => 'Striker Sports Pvt Ltd'],
                'send_callable' => function (string $email, array $data) {
                    return self::sendSupplierPasswordUpdated($email, $data['company_name']);
                }
            ],
            'template_n' => [
                'code' => 'N',
                'name' => 'RFQ Buyer Confirmation',
                'category' => 'Enquiries & RFQ',
                'subject' => 'RFQ Received — Reference #RFQ-00108',
                'badge' => 'RFQ Lead',
                'description' => 'Sent to a buyer confirming successful transmission of a Request for Quotation.',
                'sample_recipient' => 'procurement@globalfitness.com',
                'sample_name' => 'Sarah Jenkins',
                'sample_data' => [
                    'id' => 108,
                    'buyer_email' => 'procurement@globalfitness.com',
                    'buyer_name' => 'Sarah Jenkins',
                    'country_name' => 'United States',
                    'target_quantity' => 2500,
                    'subject' => 'Pro Grade Polyurethane Match Foot balls',
                    'specifications' => 'Hand-stitched FIFA Quality Pro compliant soccer balls, 32-panel dual bonded construction, customized logo debossing.'
                ],
                'send_callable' => function (string $email, array $data) {
                    $data['buyer_email'] = $email;
                    return self::sendRfqBuyerConfirmation($data);
                }
            ],
            'template_o' => [
                'code' => 'O',
                'name' => 'RFQ Supplier / Admin Notification',
                'category' => 'Enquiries & RFQ',
                'subject' => 'New RFQ Received — #RFQ-00108',
                'badge' => 'RFQ Lead',
                'description' => 'Sent to suppliers or admin with complete RFQ procurement details.',
                'sample_recipient' => 'sales@vanguardballs.in',
                'sample_name' => 'Vanguard Ball Co',
                'sample_data' => [
                    'id' => 108,
                    'buyer_name' => 'Sarah Jenkins',
                    'company_name' => 'Global Fitness Corp',
                    'country_name' => 'United States',
                    'target_quantity' => 2500,
                    'subject' => 'Pro Grade Polyurethane Match Foot balls',
                    'specifications' => 'Hand-stitched FIFA Quality Pro compliant soccer balls, 32-panel dual bonded construction, customized logo debossing.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendRfqSupplierOrAdminNotification($data, ['email' => $email, 'company_name' => 'Vanguard Ball Co']);
                }
            ],
            'template_p' => [
                'code' => 'P',
                'name' => 'OEM Buyer Confirmation',
                'category' => 'Enquiries & RFQ',
                'subject' => 'Your OEM/Customization Enquiry Has Been Received',
                'badge' => 'OEM Enquiry',
                'description' => 'Sent to buyer submitting an OEM custom molding/branding requirement.',
                'sample_recipient' => 'oem@zenithsports.com',
                'sample_name' => 'David Wong',
                'sample_data' => [
                    'id' => 205,
                    'buyer_email' => 'oem@zenithsports.com',
                    'buyer_name' => 'David Wong',
                    'country_name' => 'Australia',
                    'target_quantity' => 5000,
                    'subject' => 'Custom Molded Carbon Composite Pickleball Paddles',
                    'specifications' => 'Toray T700 carbon fiber surface, thermoformed unibody with foam injected walls, customized pantone edge guard.'
                ],
                'send_callable' => function (string $email, array $data) {
                    $data['buyer_email'] = $email;
                    return self::sendOemBuyerConfirmation($data);
                }
            ],
            'template_q' => [
                'code' => 'Q',
                'name' => 'OEM Supplier / Admin Notification',
                'category' => 'Enquiries & RFQ',
                'subject' => 'New OEM/Customization Enquiry — #OEM-00205',
                'badge' => 'OEM Lead',
                'description' => 'Sent to suppliers/admin regarding custom tooling and proto development requests.',
                'sample_recipient' => 'trade@compositesport.in',
                'sample_name' => 'Composite Sports India',
                'sample_data' => [
                    'id' => 205,
                    'buyer_name' => 'David Wong',
                    'company_name' => 'Zenith Athletic Gear Australia',
                    'country_name' => 'Australia',
                    'target_quantity' => 5000,
                    'subject' => 'Custom Molded Carbon Composite Pickleball Paddles',
                    'specifications' => 'Toray T700 carbon fiber surface, thermoformed unibody with foam injected walls, customized pantone edge guard.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendOemSupplierOrAdminNotification($data, ['email' => $email, 'company_name' => 'Composite Sports India']);
                }
            ],
            'template_r' => [
                'code' => 'R',
                'name' => 'Product Enquiry Buyer Confirmation',
                'category' => 'Enquiries & RFQ',
                'subject' => 'Your Product Enquiry Has Been Submitted',
                'badge' => 'Product Enquiry',
                'description' => 'Sent to a buyer submitting an inquiry directly from a catalog product page.',
                'sample_recipient' => 'vikram@delhisportsclub.in',
                'sample_name' => 'Vikram Malhotra',
                'sample_data' => [
                    'id' => 312,
                    'buyer_email' => 'vikram@delhisportsclub.in',
                    'buyer_name' => 'Vikram Malhotra',
                    'country_name' => 'India',
                    'target_quantity' => 100,
                    'subject' => 'Grade 1 English Willow Cricket Bats',
                    'specifications' => 'Need 100 pcs adult full size (Short Handle), grains 8-11, weight range 2.8 - 2.9 lbs with custom club branding on back sticker.'
                ],
                'send_callable' => function (string $email, array $data) {
                    $data['buyer_email'] = $email;
                    return self::sendProductEnquiryBuyerConfirmation($data);
                }
            ],
            'template_s' => [
                'code' => 'S',
                'name' => 'Product Enquiry Supplier Notification',
                'category' => 'Enquiries & RFQ',
                'subject' => 'New Buyer Enquiry for Grade 1 English Willow Cricket Bats',
                'badge' => 'Product Lead',
                'description' => 'Sent to the product owner when a buyer submits an inquiry.',
                'sample_recipient' => 'sales@meerutwillow.com',
                'sample_name' => 'Meerut Willow Crafts',
                'sample_data' => [
                    'id' => 312,
                    'buyer_name' => 'Vikram Malhotra',
                    'company_name' => 'Delhi Sports Club',
                    'country_name' => 'India',
                    'target_quantity' => 100,
                    'subject' => 'Grade 1 English Willow Cricket Bats',
                    'specifications' => 'Need 100 pcs adult full size (Short Handle), grains 8-11, weight range 2.8 - 2.9 lbs with custom club branding on back sticker.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendProductEnquirySupplierNotification($data, ['email' => $email, 'company_name' => 'Meerut Willow Crafts']);
                }
            ],
            'template_t' => [
                'code' => 'T',
                'name' => 'Contact Us Confirmation',
                'category' => 'Enquiries & RFQ',
                'subject' => 'We Have Received Your Message — Sports Junction',
                'badge' => 'Customer Support',
                'description' => 'Sent to visitors submitting a public Contact Us desk ticket.',
                'sample_recipient' => 'enquiry@sportsclub.in',
                'sample_name' => 'Ananya Sen',
                'sample_data' => [
                    'id' => 415,
                    'buyer_email' => 'enquiry@sportsclub.in',
                    'buyer_name' => 'Ananya Sen',
                    'subject' => 'Wholesale Partnership & Export Assistance',
                    'specifications' => 'Looking to connect with certified boxing ring and boxing glove manufacturers in Jalandhar. Please advise on export compliance assistance.'
                ],
                'send_callable' => function (string $email, array $data) {
                    $data['buyer_email'] = $email;
                    return self::sendContactUsConfirmationToUser($data);
                }
            ],
            'template_u' => [
                'code' => 'U',
                'name' => 'Contact Us Internal Notification',
                'category' => 'Enquiries & RFQ',
                'subject' => 'New Contact Us Enquiry — #ENQ-00415',
                'badge' => 'Support Alert',
                'description' => 'Sent to platform administrators when a contact form is received.',
                'sample_recipient' => 'sportjunctions@gmail.com',
                'sample_name' => 'Sports Junction Support Desk',
                'sample_data' => [
                    'id' => 415,
                    'buyer_name' => 'Ananya Sen',
                    'buyer_email' => 'ananya@bengalboxing.org',
                    'buyer_phone' => '+91 98300 12345',
                    'company_name' => 'Bengal Boxing Federation',
                    'country_name' => 'India',
                    'subject' => 'Wholesale Partnership & Export Assistance',
                    'specifications' => 'Looking to connect with certified boxing ring and boxing glove manufacturers in Jalandhar. Please advise on export compliance assistance.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendContactUsNotificationToAdmin($data, $email);
                }
            ],
            'template_v' => [
                'code' => 'V',
                'name' => 'New Buyer Chat Notification (1st Message)',
                'category' => 'Messaging & Communication',
                'subject' => 'New Buyer Message — Sports Junction',
                'badge' => 'Buyer Message',
                'description' => 'Sent to supplier ONLY on the first message of a newly initiated conversation thread.',
                'sample_recipient' => 'orders@punjabsports.com',
                'sample_name' => 'Punjab Sports Industries',
                'sample_data' => [
                    'supplier_email' => 'orders@punjabsports.com',
                    'supplier_name' => 'Punjab Sports Industries',
                    'buyer_name' => 'Marcus Vance',
                    'buyer_company' => 'Olympus Athletics Ltd (UK)',
                    'message_snippet' => 'Hello, we reviewed your catalog of leather boxing gloves. We require 1,200 pairs in 12oz and 14oz with customized foil embossing. Can you confirm lead times for CIF London?',
                    'conversation_id' => 42
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendBuyerChatNotificationToSupplier(
                        $email,
                        $data['supplier_name'],
                        $data['buyer_name'],
                        $data['buyer_company'],
                        $data['message_snippet'],
                        $data['conversation_id']
                    );
                }
            ],
            'template_w' => [
                'code' => 'W',
                'name' => 'Product Creation Approved',
                'category' => 'Catalog & Products',
                'subject' => 'Your Product Has Been Approved — Pro Tournament Football Goal Net',
                'badge' => 'Approved & Live',
                'description' => 'Sent when a newly created product submission is approved and published live.',
                'sample_recipient' => 'catalog@netmaker.in',
                'sample_name' => 'NetMaker Sporting Goods',
                'sample_data' => [
                    'supplier_name' => 'NetMaker Sporting Goods',
                    'product_name' => 'Pro Tournament Football Goal Net (4mm Braided HDPE)'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendProductCreationApproved($email, $data['supplier_name'], $data['product_name']);
                }
            ],
            'template_x' => [
                'code' => 'X',
                'name' => 'Product Creation Rejected',
                'category' => 'Catalog & Products',
                'subject' => 'Action Required: Product Submission Not Approved',
                'badge' => 'Review Required',
                'description' => 'Sent when a new product submission is rejected by Super Admin with feedback.',
                'sample_recipient' => 'catalog@netmaker.in',
                'sample_name' => 'NetMaker Sporting Goods',
                'sample_data' => [
                    'supplier_name' => 'NetMaker Sporting Goods',
                    'product_name' => 'Indoor Futsal Ball Model X',
                    'reason' => 'Primary product photo has watermarked third-party logos. Please upload high-resolution original factory photography on a clean background.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendProductCreationRejected($email, $data['supplier_name'], $data['product_name'], $data['reason']);
                }
            ],
            'template_y' => [
                'code' => 'Y',
                'name' => 'Product Update Under Review',
                'category' => 'Catalog & Products',
                'subject' => 'Your Product Update Is Under Review — Pro Tournament Football Goal Net',
                'badge' => 'Pending Review',
                'description' => 'Sent when an existing product has been modified by the supplier and queued for review.',
                'sample_recipient' => 'catalog@netmaker.in',
                'sample_name' => 'NetMaker Sporting Goods',
                'sample_data' => [
                    'supplier_name' => 'NetMaker Sporting Goods',
                    'product_name' => 'Pro Tournament Football Goal Net'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendProductUpdateUnderReview($email, $data['supplier_name'], $data['product_name']);
                }
            ],
            'template_z' => [
                'code' => 'Z',
                'name' => 'Product Update Approved',
                'category' => 'Catalog & Products',
                'subject' => 'Your Product Update Has Been Approved — Pro Tournament Football Goal Net',
                'badge' => 'Approved & Live',
                'description' => 'Sent when Super Admin approves modifications made to an existing live product.',
                'sample_recipient' => 'catalog@netmaker.in',
                'sample_name' => 'NetMaker Sporting Goods',
                'sample_data' => [
                    'supplier_name' => 'NetMaker Sporting Goods',
                    'product_name' => 'Pro Tournament Football Goal Net'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendProductUpdateApproved($email, $data['supplier_name'], $data['product_name']);
                }
            ],
            'template_aa' => [
                'code' => 'AA',
                'name' => 'Product Update Rejected',
                'category' => 'Catalog & Products',
                'subject' => 'Changes to Your Product Need Attention — Pro Tournament Football Goal Net',
                'badge' => 'Review Required',
                'description' => 'Sent when modifications to an existing live product are rejected.',
                'sample_recipient' => 'catalog@netmaker.in',
                'sample_name' => 'NetMaker Sporting Goods',
                'sample_data' => [
                    'supplier_name' => 'NetMaker Sporting Goods',
                    'product_name' => 'Pro Tournament Football Goal Net',
                    'reason' => 'Price tiers submitted violate platform minimum order requirements. Minimum tier quantity cannot exceed maximum tier quantity.'
                ],
                'send_callable' => function (string $email, array $data) {
                    return self::sendProductUpdateRejected($email, $data['supplier_name'], $data['product_name'], $data['reason']);
                }
            ],
        ];
    }
}
