<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * ContactPolicy Helper
 * Enforces marketplace contact detail restrictions to prevent off-platform disintermediation.
 * Detects, flags, and redacts phone numbers, email addresses, and external chat handles,
 * while preserving legitimate product dimensions, pricing, and quantities.
 */
class ContactPolicy
{
    /**
     * Check if text contains prohibited contact details
     */
    public static function containsRestrictedContact(string $text): bool
    {
        $violations = self::detectViolations($text);
        return !empty($violations);
    }

    /**
     * Detect specific violations in the text
     * Returns an array of detected violation types and snippets
     */
    public static function detectViolations(string $text): array
    {
        $violations = [];

        // 1. Email detection (standard & obfuscated like name [at] domain [dot] com)
        if (preg_match_all('/\b[A-Za-z0-9._%+-]+(?:\s*@\s*|\s*\[at\]\s*|\s*\(at\)\s*)[A-Za-z0-9.-]+(?:\s*\.\s*|\s*\[dot\]\s*|\s*\(dot\)\s*)[A-Za-z]{2,}\b/i', $text, $matches)) {
            $violations['emails'] = $matches[0];
        }

        // 2. URLs with direct messaging links (wa.me, t.me, chat.whatsapp.com, etc.)
        if (preg_match_all('/\b(?:https?:\/\/)?(?:wa\.me|t\.me|telegram\.me|api\.whatsapp\.com|chat\.whatsapp\.com)\/[a-zA-Z0-9_+%-]+/i', $text, $matches)) {
            $violations['chat_links'] = $matches[0];
        }

        // 3. Phone numbers
        // Matches standard international formats, +91, 10-digit Indian numbers, space/dash separated numbers
        // We filter out false positives such as dimensions (e.g. 10x20x30), prices, and dates
        $cleanText = preg_replace('/(?:\d+(?:\.\d+)?)\s*(?:cm|mm|m|inch|inches|kg|gm|gms|pcs|pieces|units|rs|inr|₹|\/piece|\/pc)/i', '', $text);
        $cleanText = preg_replace('/\b\d{1,4}\s*[xX*]\s*\d{1,4}(?:\s*[xX*]\s*\d{1,4})?\b/', '', $cleanText);

        $phonePatterns = [
            // +91 or +international followed by 9-11 digits
            '/(?:\+|00)\d{1,3}[\s.-]?[6-9]\d{4}[\s.-]?\d{5}\b/',
            // 10-digit mobile starting with 6-9
            '/\b[6-9]\d{2}[\s.-]?\d{3}[\s.-]?\d{4}\b/',
            // Spaced out sequence of 10 or more digits (e.g. 9 8 7 6 5 4 3 2 1 0)
            '/(?:(?:\b\d\s*){10,}\b)/'
        ];

        $detectedPhones = [];
        foreach ($phonePatterns as $pattern) {
            if (preg_match_all($pattern, $cleanText, $matches)) {
                foreach ($matches[0] as $match) {
                    $digitsOnly = preg_replace('/\D/', '', $match);
                    if (strlen($digitsOnly) >= 10 && strlen($digitsOnly) <= 15) {
                        $detectedPhones[] = trim($match);
                    }
                }
            }
        }

        if (!empty($detectedPhones)) {
            $violations['phones'] = array_values(array_unique($detectedPhones));
        }

        // 4. Social messaging handles with explicit keywords (e.g. "my whatsapp is...", "skype: ...")
        if (preg_match_all('/\b(?:whatsapp|telegram|skype|wechat|call\s*me|reach\s*me|contact\s*me)\s*(?:is|at|:|on)?\s*[:\-]?\s*([@+A-Za-z0-9._%+-]{3,30})\b/i', $text, $matches)) {
            $violations['external_handles'] = $matches[0];
        }

        return $violations;
    }

    /**
     * Mask / Redact restricted contact info from text
     */
    public static function maskRestrictedContact(string $text): string
    {
        $redactedNotice = '[Contact information hidden to comply with marketplace policy]';

        // Redact emails
        $text = preg_replace(
            '/\b[A-Za-z0-9._%+-]+(?:\s*@\s*|\s*\[at\]\s*|\s*\(at\)\s*)[A-Za-z0-9.-]+(?:\s*\.\s*|\s*\[dot\]\s*|\s*\(dot\)\s*)[A-Za-z]{2,}\b/i',
            $redactedNotice,
            $text
        );

        // Redact chat links
        $text = preg_replace(
            '/\b(?:https?:\/\/)?(?:wa\.me|t\.me|telegram\.me|api\.whatsapp\.com|chat\.whatsapp\.com)\/[a-zA-Z0-9_+%-]+/i',
            $redactedNotice,
            $text
        );

        // Redact phones
        $text = preg_replace_callback(
            '/(?:(?:\+|00)\d{1,3}[\s.-]?)?[6-9]\d{2}[\s.-]?\d{3}[\s.-]?\d{4}\b|(?:(?:\b\d\s*){10,}\b)/',
            function ($matches) use ($redactedNotice) {
                $digits = preg_replace('/\D/', '', $matches[0]);
                if (strlen($digits) >= 10 && strlen($digits) <= 15) {
                    return $redactedNotice;
                }
                return $matches[0];
            },
            $text
        );

        return $text;
    }

    /**
     * Warning message to show users when a restricted contact is detected
     */
    public static function getWarningMessage(): string
    {
        return 'Notice: Sharing personal contact information (phone numbers, email addresses, external messaging handles) outside the Sports Junction platform is restricted to protect your trade security and deal guarantees.';
    }
}
