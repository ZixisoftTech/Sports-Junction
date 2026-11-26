<?php
declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

class Uploader
{
    private const ALLOWED_MIME = [
        'image/jpeg'   => 'jpg',
        'image/jpg'    => 'jpg',
        'image/pjpeg'  => 'jpg',
        'image/png'    => 'png',
        'image/x-png'  => 'png',
        'image/webp'   => 'webp',
        'image/gif'    => 'gif',
    ];

    private const MAX_SIZE = 8 * 1024 * 1024; // 8MB

    /**
     * Upload an image file from $_FILES array with robust MIME detection and permissions handling
     *
     * @param array $file $_FILES['input_name']
     * @param string $subfolder 'categories' or 'products'
     * @return string Relative URL path to uploaded file
     */
    public static function uploadImage(array $file, string $subfolder = 'categories'): string
    {
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $errMessages = [
                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize limit in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
                UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
            ];
            $msg = $errMessages[$file['error']] ?? ('Upload error code: ' . $file['error']);
            throw new RuntimeException($msg);
        }

        if (empty($file['tmp_name']) || (!is_uploaded_file($file['tmp_name']) && (PHP_SAPI !== 'cli' || !file_exists($file['tmp_name'])))) {
            throw new RuntimeException('No file uploaded or invalid upload.');
        }

        if ($file['size'] > self::MAX_SIZE) {
            throw new RuntimeException('File exceeds maximum allowed size of 8MB.');
        }

        // 1. Detect MIME via finfo
        $ext = null;
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = strtolower((string)$finfo->file($file['tmp_name']));
            if (array_key_exists($mime, self::ALLOWED_MIME)) {
                $ext = self::ALLOWED_MIME[$mime];
            }
        }

        // 2. Fallback detection via getimagesize()
        if ($ext === null) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo !== false && !empty($imageInfo[2])) {
                $ext = match ($imageInfo[2]) {
                    IMAGETYPE_JPEG => 'jpg',
                    IMAGETYPE_PNG  => 'png',
                    IMAGETYPE_WEBP => 'webp',
                    IMAGETYPE_GIF  => 'gif',
                    default        => null,
                };
            }
        }

        // 3. Fallback based on original client extension if image verification passed
        if ($ext === null && !empty($file['name'])) {
            $clientExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($clientExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $check = @getimagesize($file['tmp_name']);
                if ($check !== false) {
                    $ext = ($clientExt === 'jpeg') ? 'jpg' : $clientExt;
                }
            }
        }

        if ($ext === null) {
            throw new RuntimeException('Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP, GIF.');
        }

        $filename = sprintf('%s_%s.%s', bin2hex(random_bytes(10)), time(), $ext);

        $uploadBase = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($uploadBase)) {
            @mkdir($uploadBase, 0777, true);
        }
        @chmod($uploadBase, 0777);

        $uploadDir = $uploadBase . '/' . trim($subfolder, '/');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        @chmod($uploadDir, 0777);

        $targetPath = $uploadDir . '/' . $filename;

        // Attempt move_uploaded_file with copy fallback
        $moved = @move_uploaded_file($file['tmp_name'], $targetPath);
        if (!$moved) {
            $moved = @copy($file['tmp_name'], $targetPath);
        }

        if (!$moved || !file_exists($targetPath)) {
            throw new RuntimeException('Failed to save uploaded file on server. Check folder write permissions.');
        }

        @chmod($targetPath, 0666);

        return '/uploads/' . trim($subfolder, '/') . '/' . $filename;
    }

    /**
     * Generate a URL slug from title/name
     */
    public static function slugify(string $text): string
    {
        // Replace non-alphanumeric with hyphen
        $slug = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Transliterate
        $slug = iconv('utf-8', 'us-ascii//TRANSLIT', (string)$slug);
        // Remove unwanted characters
        $slug = preg_replace('~[^-\w]+~', '', (string)$slug);
        // Trim hyphens
        $slug = trim((string)$slug, '-');
        // Lowercase
        $slug = strtolower($slug);
        return empty($slug) ? 'n-a-' . time() : $slug;
    }

    /**
     * Check if a given URL or string is a Google Drive link
     */
    public static function isGoogleDriveUrl(?string $url): bool
    {
        if (empty($url)) return false;
        $trimmed = trim($url, "<>'\" \t\n\r\0\x0B");
        return (bool) preg_match('/(?:drive\.google\.com|docs\.google\.com|googleusercontent\.com)/i', $trimmed);
    }

    /**
     * Extract Google Drive File ID from various link structures:
     * - https://drive.google.com/file/d/FILE_ID/view?usp=sharing
     * - https://drive.google.com/file/d/FILE_ID
     * - https://drive.google.com/open?id=FILE_ID
     * - https://drive.google.com/uc?id=FILE_ID
     * - https://docs.google.com/file/d/FILE_ID/edit
     * - drive.google.com/file/d/FILE_ID
     * - https://lh3.googleusercontent.com/d/FILE_ID
     * - https://drive.google.com/drive/folders/FILE_ID
     */
    public static function extractGoogleDriveFileId(?string $url): ?string
    {
        if (empty($url)) return null;
        $url = trim($url, "<>'\" \t\n\r\0\x0B");

        // Pattern 1: /file/d/{id} or /d/{id} or /file/u/0/d/{id}
        if (preg_match('#(?:/file/d/|/d/|/file/u/\d+/d/)([a-zA-Z0-9_-]{20,})#i', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 2: id={id} in query parameters
        if (preg_match('#[?&]id=([a-zA-Z0-9_-]{20,})#i', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 3: googleusercontent.com/d/{id}
        if (preg_match('#googleusercontent\.com/d/([a-zA-Z0-9_-]{20,})#i', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 4: /folders/{id}
        if (preg_match('#/folders/([a-zA-Z0-9_-]{20,})#i', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Convert any Google Drive link to a direct embed image CDN URL
     * Returns: https://lh3.googleusercontent.com/d/{fileId}
     */
    public static function formatGoogleDriveUrl(?string $url): ?string
    {
        $fileId = self::extractGoogleDriveFileId($url);
        if ($fileId !== null) {
            return "https://lh3.googleusercontent.com/d/{$fileId}";
        }
        return null;
    }

    /**
     * Resolves an image path to an absolute application URL, falling back to brand logo if missing.
     * Automatically converts Google Drive share links to direct embeddable image URLs.
     */
    public static function imageUrl(?string $path): string
    {
        $baseUrl = \App\Core\Response::baseUrl();
        $defaultLogo = $baseUrl . '/assets/images/logo.jpg';
        if (empty($path) || trim($path) === '') {
            return $defaultLogo;
        }
        $trimmed = trim($path, "<>'\" \t\n\r\0\x0B");

        // Seamlessly convert Google Drive links (with or without http/https)
        $driveDirectUrl = self::formatGoogleDriveUrl($trimmed);
        if ($driveDirectUrl !== null) {
            return $driveDirectUrl;
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }
        $clean = '/' . ltrim($trimmed, '/');
        if (!empty($baseUrl) && str_starts_with($clean, $baseUrl . '/')) {
            return $clean;
        }
        return $baseUrl . $clean;
    }
}
