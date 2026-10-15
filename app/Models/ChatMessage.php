<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\ContactPolicy;
use App\Helpers\ChatNotifier;
use PDO;

class ChatMessage extends Model
{
    /**
     * Create a new message in a conversation
     */
    public static function create(array $data): array
    {
        $conversationId = (int) $data['conversation_id'];
        $senderUserId = (int) $data['sender_user_id'];
        $senderRole = $data['sender_role']; // BUYER, SUPPLIER, SUPER_ADMIN
        $senderAdminId = isset($data['sender_admin_id']) ? (int) $data['sender_admin_id'] : null;
        $isOnBehalf = !empty($data['is_on_behalf_of_supplier']) ? 1 : 0;
        $isInternalAdminNote = !empty($data['is_internal_admin_note']) ? 1 : 0;
        $messageType = $data['message_type'] ?? 'TEXT'; // TEXT, ATTACHMENT, OFFER, SYSTEM
        $rawBody = $data['message_body'] ?? '';
        $metadata = isset($data['metadata']) ? (is_array($data['metadata']) ? json_encode($data['metadata']) : $data['metadata']) : null;

        // Check for contact policy violations
        $hasRestrictedContact = 0;
        $savedBody = $rawBody;
        if ($messageType === 'TEXT' && !$isInternalAdminNote) {
            if (ContactPolicy::containsRestrictedContact($rawBody)) {
                $hasRestrictedContact = 1;
                // If mask is enabled for participants, we keep original flagged in DB with restricted contact tag
            }
        }

        $sql = "
            INSERT INTO `chat_messages` (
                `conversation_id`, `sender_user_id`, `sender_role`, `sender_admin_id`,
                `is_on_behalf_of_supplier`, `is_internal_admin_note`, `message_type`,
                `message_body`, `delivery_status`, `has_restricted_contact`, `metadata`, `created_at`
            ) VALUES (
                :conversation_id, :sender_user_id, :sender_role, :sender_admin_id,
                :is_on_behalf, :is_internal_note, :message_type,
                :message_body, 'SENT', :has_restricted_contact, :metadata, NOW()
            )
        ";

        self::execute($sql, [
            ':conversation_id' => $conversationId,
            ':sender_user_id' => $senderUserId,
            ':sender_role' => $senderRole,
            ':sender_admin_id' => $senderAdminId,
            ':is_on_behalf' => $isOnBehalf,
            ':is_internal_note' => $isInternalAdminNote,
            ':message_type' => $messageType,
            ':message_body' => $savedBody,
            ':has_restricted_contact' => $hasRestrictedContact,
            ':metadata' => $metadata
        ]);

        $messageId = (int) self::db()->lastInsertId();

        // Immediately link attachment if present before any reader query or event notification
        $attachmentId = isset($data['attachment_id']) ? (int) $data['attachment_id'] : 0;
        if (!$attachmentId && !empty($data['metadata'])) {
            $metaArr = is_array($data['metadata']) ? $data['metadata'] : json_decode((string) $data['metadata'], true);
            if (!empty($metaArr['attachment_id'])) {
                $attachmentId = (int) $metaArr['attachment_id'];
            }
        }
        if ($attachmentId > 0) {
            ChatAttachment::linkToMessage($attachmentId, $messageId);
        }

        // Touch conversation last_message_at if not internal admin note
        if (!$isInternalAdminNote) {
            ChatConversation::touchLastMessage($conversationId);

            // Determine recipient and send notification
            $conv = ChatConversation::findById($conversationId);
            if ($conv) {
                $recipientUserId = 0;
                $recipientRole = '';
                if ($senderRole === 'BUYER') {
                    $recipientUserId = (int) $conv['supplier_user_id'];
                    $recipientRole = 'SUPPLIER';
                } elseif ($senderRole === 'SUPPLIER' || $isOnBehalf) {
                    $recipientUserId = (int) $conv['buyer_id'];
                    $recipientRole = 'BUYER';
                }

                if ($recipientUserId > 0) {
                    $title = "New message from " . ($senderRole === 'BUYER' ? ($conv['buyer_first_name'] . ' (' . ($conv['buyer_company'] ?: 'Buyer') . ')') : $conv['supplier_company_name']);
                    $snippet = mb_substr($rawBody, 0, 100);
                    if ($messageType === 'OFFER') {
                        $snippet = "Sent a formal negotiation offer.";
                    } elseif ($messageType === 'ATTACHMENT') {
                        $snippet = "Shared a business document/attachment.";
                    }
                    $link = ($recipientRole === 'SUPPLIER') ? "/supplier/messages?conversation_id={$conversationId}" : "/buyer/messages?conversation_id={$conversationId}";
                    ChatNotifier::notify($recipientUserId, 'NEW_CHAT_MESSAGE', $title, $snippet, $link);
                }
            }
        }

        return self::findById($messageId) ?? [];
    }

    /**
     * Find message by ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                m.*,
                u.name AS sender_first_name,
                '' AS sender_last_name,
                u.company_name AS sender_company,
                a.id AS attachment_id,
                a.file_name AS attachment_name,
                a.file_path AS attachment_path,
                a.file_type AS attachment_type,
                a.file_size AS attachment_size,
                a.mime_type AS attachment_mime
            FROM `chat_messages` m
            LEFT JOIN `users` u ON m.sender_user_id = u.id
            LEFT JOIN `chat_attachments` a ON (a.message_id = m.id OR a.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(m.metadata, '$.attachment_id')) AS UNSIGNED))
            WHERE m.id = :id
            LIMIT 1
        ";
        return self::fetch($sql, [':id' => $id]);
    }

    /**
     * Get messages for a conversation with cursor pagination
     */
    public static function getByConversation(
        int $conversationId,
        ?int $beforeId = null,
        int $limit = 50,
        bool $includeAdminNotes = false
    ): array {
        $sql = "
            SELECT 
                m.*,
                u.name AS sender_first_name,
                '' AS sender_last_name,
                u.company_name AS sender_company,
                a.id AS attachment_id,
                a.file_name AS attachment_name,
                a.file_path AS attachment_path,
                a.file_type AS attachment_type,
                a.file_size AS attachment_size,
                a.mime_type AS attachment_mime
            FROM `chat_messages` m
            LEFT JOIN `users` u ON m.sender_user_id = u.id
            LEFT JOIN `chat_attachments` a ON (a.message_id = m.id OR a.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(m.metadata, '$.attachment_id')) AS UNSIGNED))
            WHERE m.conversation_id = :conv_id
        ";

        $params = [':conv_id' => $conversationId];

        if (!$includeAdminNotes) {
            $sql .= " AND m.is_internal_admin_note = 0";
        }

        if ($beforeId !== null && $beforeId > 0) {
            $sql .= " AND m.id < :before_id";
            $params[':before_id'] = $beforeId;
        }

        $sql .= " ORDER BY m.id DESC LIMIT " . (int) $limit;

        $rows = self::fetchAll($sql, $params);
        // Reverse array so messages appear in ascending chronological order for the client UI
        return array_reverse($rows);
    }

    /**
     * Get new messages after a given message ID (used by SSE and polling)
     */
    public static function getNewerThan(int $conversationId, int $afterId, bool $includeAdminNotes = false): array
    {
        $sql = "
            SELECT 
                m.*,
                u.name AS sender_first_name,
                '' AS sender_last_name,
                u.company_name AS sender_company,
                a.id AS attachment_id,
                a.file_name AS attachment_name,
                a.file_path AS attachment_path,
                a.file_type AS attachment_type,
                a.file_size AS attachment_size,
                a.mime_type AS attachment_mime
            FROM `chat_messages` m
            LEFT JOIN `users` u ON m.sender_user_id = u.id
            LEFT JOIN `chat_attachments` a ON (a.message_id = m.id OR a.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(m.metadata, '$.attachment_id')) AS UNSIGNED))
            WHERE m.conversation_id = :conv_id AND m.id > :after_id
        ";

        $params = [':conv_id' => $conversationId, ':after_id' => $afterId];

        if (!$includeAdminNotes) {
            $sql .= " AND m.is_internal_admin_note = 0";
        }

        $sql .= " ORDER BY m.id ASC";

        return self::fetchAll($sql, $params);
    }

    /**
     * Mark messages in conversation as READ by the reader user
     */
    public static function markAsRead(int $conversationId, int $readerUserId): int
    {
        $sql = "
            UPDATE `chat_messages`
            SET `delivery_status` = 'READ', `read_at` = NOW()
            WHERE `conversation_id` = :conv_id
              AND `sender_user_id` != :reader_id
              AND `delivery_status` != 'READ'
        ";
        $stmt = self::execute($sql, [
            ':conv_id' => $conversationId,
            ':reader_id' => $readerUserId
        ]);
        return $stmt->rowCount();
    }
}
