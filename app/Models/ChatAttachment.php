<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class ChatAttachment extends Model
{
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/webp'
    ];
    public const MAX_FILE_SIZE = 10485760; // 10MB

    /**
     * Store uploaded file and create record
     */
    public static function createFromUpload(array $file, int $conversationId, int $uploaderUserId, ?int $messageId = null): array
    {
        if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload failed with error code: " . ($file['error'] ?? 'unknown'));
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new RuntimeException("File size exceeds 10MB limit.");
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException("Invalid file type. Only PDF and images (JPG, PNG, WebP) are allowed.");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException("Invalid file extension.");
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/chat';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $uniqueName = 'chat_' . bin2hex(random_bytes(8)) . '_' . $fileName . '.' . $extension;
        $destination = $uploadDir . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException("Failed to move uploaded attachment.");
        }

        $relativePath = '/uploads/chat/' . $uniqueName;

        $sql = "
            INSERT INTO `chat_attachments` (
                `message_id`, `conversation_id`, `uploader_user_id`,
                `file_name`, `file_path`, `file_type`, `file_size`, `mime_type`, `created_at`
            ) VALUES (
                :message_id, :conversation_id, :uploader_user_id,
                :file_name, :file_path, :file_type, :file_size, :mime_type, NOW()
            )
        ";

        self::execute($sql, [
            ':message_id' => $messageId,
            ':conversation_id' => $conversationId,
            ':uploader_user_id' => $uploaderUserId,
            ':file_name' => $file['name'],
            ':file_path' => $relativePath,
            ':file_type' => $extension,
            ':file_size' => (int) $file['size'],
            ':mime_type' => $mime
        ]);

        $id = (int) self::db()->lastInsertId();
        return self::findById($id) ?? [];
    }

    /**
     * Find attachment by ID
     */
    public static function findById(int $id): ?array
    {
        return self::fetch("SELECT * FROM `chat_attachments` WHERE `id` = :id LIMIT 1", [':id' => $id]);
    }

    /**
     * Link attachment to message
     */
    public static function linkToMessage(int $attachmentId, int $messageId): void
    {
        self::execute("UPDATE `chat_attachments` SET `message_id` = :msg_id WHERE `id` = :id", [
            ':msg_id' => $messageId,
            ':id' => $attachmentId
        ]);
    }
}
