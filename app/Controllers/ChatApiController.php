<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\ContactPolicy;
use App\Helpers\Session;
use App\Models\ChatAttachment;
use App\Models\ChatBlock;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatOffer;
use App\Models\ChatReport;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Throwable;

class ChatApiController extends Controller
{
    /**
     * Get authorized user or return JSON 401 error
     */
    private function requireAuth(): ?array
    {
        Session::start();
        $user = $this->authUser();
        if (!$user || empty($user['id'])) {
            $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
            return null;
        }
        return $user;
    }

    /**
     * Verify participant authorization for a conversation
     */
    private function verifyConversationAccess(array $conversation, array $user): bool
    {
        $role = strtoupper($user['role'] ?? '');
        $userId = (int) $user['id'];

        if ($role === 'SUPER_ADMIN') {
            return true;
        }

        if ($role === 'BUYER' && (int) $conversation['buyer_id'] === $userId) {
            return true;
        }

        if ($role === 'SUPPLIER') {
            $supplier = Supplier::findById((int) $conversation['supplier_id']);
            if ($supplier && (int) $supplier['user_id'] === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * GET /api/chat/conversations
     * List conversations for the logged-in user
     */
    public function conversations(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $role = strtoupper($user['role'] ?? '');
        $userId = (int) $user['id'];
        $tab = $this->request->get('tab', 'all');
        $search = $this->request->get('search', '');

        if ($role === 'BUYER') {
            $list = ChatConversation::getForBuyer($userId, ['status' => $tab, 'search' => $search]);
        } elseif ($role === 'SUPPLIER') {
            $supplier = Supplier::findByUserId($userId);
            if (!$supplier) {
                $this->json(['success' => true, 'conversations' => []]);
                return;
            }
            $list = ChatConversation::getForSupplier((int) $supplier['id'], ['tab' => $tab, 'search' => $search]);
        } elseif ($role === 'SUPER_ADMIN') {
            $list = ChatConversation::getAllForAdmin(['status' => $tab, 'search' => $search]);
        } else {
            $list = [];
        }

        $this->json(['success' => true, 'conversations' => $list]);
    }

    /**
     * POST /api/chat/conversations/start
     * Start or retrieve existing conversation for a product / supplier
     */
    public function startConversation(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $role = strtoupper($user['role'] ?? '');
        $userId = (int) $user['id'];

        $supplierId = (int) $this->request->post('supplier_id', 0);
        $productId = $this->request->post('product_id') ? (int) $this->request->post('product_id') : null;
        $enquiryId = $this->request->post('enquiry_id') ? (int) $this->request->post('enquiry_id') : null;
        $initialMessage = trim((string) $this->request->post('initial_message', ''));

        // If product given but no supplier_id, derive supplier from product
        if ($productId && !$supplierId) {
            $product = Product::findById($productId);
            if ($product && !empty($product['supplier_id'])) {
                $supplierId = (int) $product['supplier_id'];
            }
        }

        if (!$supplierId) {
            $this->json(['success' => false, 'message' => 'Supplier information is missing.'], 422);
            return;
        }

        $supplier = Supplier::findById($supplierId);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            return;
        }

        // Check if supplier is trying to chat with themselves
        if ((int) $supplier['user_id'] === $userId) {
            $this->json(['success' => false, 'message' => 'You cannot start a chat with your own supplier store.'], 422);
            return;
        }

        // If user is buyer or super admin
        $buyerId = ($role === 'BUYER') ? $userId : (int) $this->request->post('buyer_id', $userId);

        $conversation = ChatConversation::findOrCreate($buyerId, $supplierId, $productId, $enquiryId);

        // Send initial message if provided
        if (!empty($initialMessage) && !empty($conversation['id'])) {
            ChatMessage::create([
                'conversation_id' => (int) $conversation['id'],
                'sender_user_id' => $userId,
                'sender_role' => $role,
                'message_type' => 'TEXT',
                'message_body' => $initialMessage
            ]);
            $conversation = ChatConversation::findById((int) $conversation['id']);
        }

        $this->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }

    /**
     * GET /api/chat/conversations/{id}
     * Get detailed conversation thread, offers, and metadata
     */
    public function getConversation(array $params = []): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $convId = (int) ($params['id'] ?? $this->request->get('id', 0));
        $conversation = ChatConversation::findById($convId);

        if (!$conversation) {
            $this->json(['success' => false, 'message' => 'Conversation not found.'], 404);
            return;
        }

        if (!$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Access denied to this conversation.'], 403);
            return;
        }

        $isAdmin = (strtoupper($user['role'] ?? '') === 'SUPER_ADMIN');
        $beforeId = $this->request->get('before_id') ? (int) $this->request->get('before_id') : null;

        // Mark messages as read
        ChatMessage::markAsRead($convId, (int) $user['id']);

        // Fetch messages
        $messages = ChatMessage::getByConversation($convId, $beforeId, 50, $isAdmin);

        // Fetch negotiation offers
        $offers = ChatOffer::getHistory($convId);
        $latestOffer = ChatOffer::getLatest($convId);

        // Check block status
        $otherUserId = ((int) $user['id'] === (int) $conversation['buyer_id'])
            ? (int) $conversation['supplier_user_id']
            : (int) $conversation['buyer_id'];

        $isBlockedByMe = ChatBlock::isBlockedBy((int) $user['id'], $otherUserId);
        $isBlockedByOther = ChatBlock::isBlockedBy($otherUserId, (int) $user['id']);

        $this->json([
            'success' => true,
            'conversation' => $conversation,
            'messages' => $messages,
            'offers' => $offers,
            'latest_offer' => $latestOffer,
            'is_blocked_by_me' => $isBlockedByMe,
            'is_blocked_by_other' => $isBlockedByOther
        ]);
    }

    /**
     * POST /api/chat/messages
     * Send a new message
     */
    public function sendMessage(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $convId = (int) $this->request->post('conversation_id', 0);
        $messageBody = trim((string) $this->request->post('message_body', ''));
        $messageType = $this->request->post('message_type', 'TEXT');
        $metadata = $this->request->post('metadata', null);

        $conversation = ChatConversation::findById($convId);
        if (!$conversation) {
            $this->json(['success' => false, 'message' => 'Conversation not found.'], 404);
            return;
        }

        if (!$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        // Check if blocked
        $otherUserId = ((int) $user['id'] === (int) $conversation['buyer_id'])
            ? (int) $conversation['supplier_user_id']
            : (int) $conversation['buyer_id'];

        if (ChatBlock::isBlocked((int) $user['id'], $otherUserId)) {
            $this->json(['success' => false, 'message' => 'Messaging is blocked between participants.'], 403);
            return;
        }

        if (empty($messageBody) && $messageType === 'TEXT') {
            $this->json(['success' => false, 'message' => 'Message content cannot be empty.'], 422);
            return;
        }

        $senderRole = strtoupper($user['role'] ?? 'BUYER');

        // Contact policy detection
        $violations = ContactPolicy::detectViolations($messageBody);
        $hasRestricted = !empty($violations);

        $message = ChatMessage::create([
            'conversation_id' => $convId,
            'sender_user_id' => (int) $user['id'],
            'sender_role' => $senderRole,
            'message_type' => $messageType,
            'message_body' => $messageBody,
            'metadata' => $metadata
        ]);

        $response = [
            'success' => true,
            'message' => $message,
            'has_restricted_contact' => $hasRestricted
        ];

        if ($hasRestricted) {
            $response['warning'] = ContactPolicy::getWarningMessage();
            $response['violations'] = array_keys($violations);
        }

        $this->json($response);
    }

    /**
     * GET /api/chat/stream
     * Server-Sent Events (SSE) endpoint for real-time messaging
     */
    public function stream(array $params = []): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $convId = (int) $this->request->get('conversation_id', 0);
        $lastId = (int) $this->request->get('last_id', 0);

        $conversation = ChatConversation::findById($convId);
        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            http_response_code(403);
            echo "event: error\ndata: " . json_encode(['message' => 'Access denied']) . "\n\n";
            return;
        }

        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Release PHP session write lock so other tabs/requests aren't blocked
        session_write_close();

        // Disable script timeout
        set_time_limit(35);
        ignore_user_abort(false);

        $isAdmin = (strtoupper($user['role'] ?? '') === 'SUPER_ADMIN');
        $startTime = time();
        $currentLastId = $lastId;

        // Loop for up to 25 seconds before graceful disconnect/reconnect
        while (time() - $startTime < 25) {
            if (connection_aborted()) {
                break;
            }

            // Fetch new messages
            $newMessages = ChatMessage::getNewerThan($convId, $currentLastId, $isAdmin);
            if (!empty($newMessages)) {
                foreach ($newMessages as $msg) {
                    echo "event: message\n";
                    echo "data: " . json_encode($msg) . "\n\n";
                    $currentLastId = max($currentLastId, (int) $msg['id']);
                }

                // Mark newly delivered messages as read for this user
                ChatMessage::markAsRead($convId, (int) $user['id']);

                if (ob_get_level() > 0) ob_flush();
                flush();
            }

            // Heartbeat ping every 5 seconds
            echo ": ping\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();

            usleep(600000); // 0.6 seconds sleep
        }

        echo "event: timeout\ndata: " . json_encode(['last_id' => $currentLastId]) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
        exit;
    }

    /**
     * GET /api/chat/poll
     * Polling fallback endpoint for clients where SSE isn't active
     */
    public function poll(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $convId = (int) $this->request->get('conversation_id', 0);
        $lastId = (int) $this->request->get('last_id', 0);

        $conversation = ChatConversation::findById($convId);
        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        $isAdmin = (strtoupper($user['role'] ?? '') === 'SUPER_ADMIN');
        $newMessages = ChatMessage::getNewerThan($convId, $lastId, $isAdmin);

        if (!empty($newMessages)) {
            ChatMessage::markAsRead($convId, (int) $user['id']);
        }

        $latestOffer = ChatOffer::getLatest($convId);

        $this->json([
            'success' => true,
            'messages' => $newMessages,
            'latest_offer' => $latestOffer,
            'conversation_status' => $conversation['status']
        ]);
    }

    /**
     * POST /api/chat/attachments/upload
     * Upload an attachment and attach to conversation
     */
    public function uploadAttachment(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $convId = (int) $this->request->post('conversation_id', 0);
        $conversation = ChatConversation::findById($convId);

        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        if (empty($_FILES['attachment'])) {
            $this->json(['success' => false, 'message' => 'No file provided.'], 422);
            return;
        }

        try {
            $attachment = ChatAttachment::createFromUpload($_FILES['attachment'], $convId, (int) $user['id']);

            // Create attachment message
            $fileName = $attachment['file_name'];
            $fileType = strtoupper($attachment['file_type']);
            $message = ChatMessage::create([
                'conversation_id' => $convId,
                'sender_user_id' => (int) $user['id'],
                'sender_role' => strtoupper($user['role'] ?? 'BUYER'),
                'message_type' => 'ATTACHMENT',
                'message_body' => "Sent an attachment: {$fileName} ({$fileType})",
                'attachment_id' => (int) $attachment['id'],
                'metadata' => ['attachment_id' => (int) $attachment['id']]
            ]);

            // Link attachment to message
            ChatAttachment::linkToMessage((int) $attachment['id'], (int) $message['id']);

            $fullMessage = ChatMessage::findById((int) $message['id']);

            $this->json([
                'success' => true,
                'attachment' => $attachment,
                'message' => $fullMessage
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/chat/attachments/{id}/download
     * Secure download route with strict conversation access check
     */
    public function downloadAttachment(array $params = []): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $attachId = (int) ($params['id'] ?? 0);
        $attachment = ChatAttachment::findById($attachId);

        if (!$attachment) {
            $this->json(['success' => false, 'message' => 'Attachment not found.'], 404);
            return;
        }

        $conversation = ChatConversation::findById((int) $attachment['conversation_id']);
        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Unauthorized to download this file.'], 403);
            return;
        }

        $filePath = dirname(__DIR__, 2) . '/public' . $attachment['file_path'];
        if (!file_exists($filePath)) {
            $this->json(['success' => false, 'message' => 'File not found on server.'], 404);
            return;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($attachment['file_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * POST /api/chat/offers
     * Create formal offer or counteroffer
     */
    public function createOffer(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $convId = (int) $this->request->post('conversation_id', 0);
        $conversation = ChatConversation::findById($convId);

        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        try {
            $data = [
                'conversation_id' => $convId,
                'product_id' => $conversation['product_id'] ?? $this->request->post('product_id'),
                'created_by_user_id' => (int) $user['id'],
                'created_by_role' => strtoupper($user['role'] ?? 'BUYER'),
                'parent_offer_id' => $this->request->post('parent_offer_id'),
                'quantity' => (int) $this->request->post('quantity', 1),
                'unit' => trim((string) $this->request->post('unit', 'Pieces')),
                'unit_price' => (float) $this->request->post('unit_price', 0),
                'delivery_location' => trim((string) $this->request->post('delivery_location', '')),
                'lead_time' => trim((string) $this->request->post('lead_time', '')),
                'customization_terms' => trim((string) $this->request->post('customization_terms', '')),
                'additional_terms' => trim((string) $this->request->post('additional_terms', '')),
                'expiry_date' => $this->request->post('expiry_date')
            ];

            $offer = ChatOffer::createOffer($data);
            $this->json(['success' => true, 'offer' => $offer]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/chat/offers/{id}/accept
     * Accept a pending offer
     */
    public function acceptOffer(array $params = []): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $offerId = (int) ($params['id'] ?? 0);
        $offer = ChatOffer::findById($offerId);

        if (!$offer) {
            $this->json(['success' => false, 'message' => 'Offer not found.'], 404);
            return;
        }

        $conversation = ChatConversation::findById((int) $offer['conversation_id']);
        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        try {
            $accepted = ChatOffer::acceptOffer($offerId, (int) $user['id']);
            $this->json(['success' => true, 'offer' => $accepted]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/chat/offers/{id}/reject
     * Reject an offer
     */
    public function rejectOffer(array $params = []): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $offerId = (int) ($params['id'] ?? 0);
        $reason = trim((string) $this->request->post('reason', ''));
        $offer = ChatOffer::findById($offerId);

        if (!$offer) {
            $this->json(['success' => false, 'message' => 'Offer not found.'], 404);
            return;
        }

        $conversation = ChatConversation::findById((int) $offer['conversation_id']);
        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        try {
            $rejected = ChatOffer::rejectOffer($offerId, (int) $user['id'], $reason);
            $this->json(['success' => true, 'offer' => $rejected]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/chat/offers/{id}/withdraw
     * Withdraw an offer
     */
    public function withdrawOffer(array $params = []): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $offerId = (int) ($params['id'] ?? 0);
        try {
            $withdrawn = ChatOffer::withdrawOffer($offerId, (int) $user['id']);
            $this->json(['success' => true, 'offer' => $withdrawn]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/chat/block
     * Block or unblock a user
     */
    public function toggleBlock(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $targetUserId = (int) $this->request->post('target_user_id', 0);
        $convId = (int) $this->request->post('conversation_id', 0);
        $action = $this->request->post('action', 'block'); // 'block' or 'unblock'

        if (!$targetUserId && $convId > 0) {
            $conversation = ChatConversation::findById($convId);
            if ($conversation) {
                $targetUserId = ((int) $user['id'] === (int) $conversation['buyer_id'])
                    ? (int) ($conversation['supplier_user_id'] ?? 0)
                    : (int) $conversation['buyer_id'];

                if (!$targetUserId && !empty($conversation['supplier_id'])) {
                    $supplier = Supplier::findById((int) $conversation['supplier_id']);
                    if ($supplier && !empty($supplier['user_id'])) {
                        $targetUserId = (int) $supplier['user_id'];
                    }
                }
            }
        }

        if (!$targetUserId || $targetUserId === (int) $user['id']) {
            $this->json(['success' => false, 'message' => 'Invalid target participant for block action.'], 422);
            return;
        }

        if ($action === 'block') {
            ChatBlock::block((int) $user['id'], $targetUserId);
            $this->json(['success' => true, 'message' => 'User blocked successfully.', 'is_blocked' => true]);
        } else {
            ChatBlock::unblock((int) $user['id'], $targetUserId);
            $this->json(['success' => true, 'message' => 'User unblocked successfully.', 'is_blocked' => false]);
        }
    }

    /**
     * POST /api/chat/report
     * Submit a report against conversation
     */
    public function submitReport(): void
    {
        $user = $this->requireAuth();
        if (!$user) return;

        $convId = (int) $this->request->post('conversation_id', 0);
        $reason = trim((string) $this->request->post('reason', ''));
        $description = trim((string) $this->request->post('description', ''));

        $conversation = ChatConversation::findById($convId);
        if (!$conversation || !$this->verifyConversationAccess($conversation, $user)) {
            $this->json(['success' => false, 'message' => 'Conversation not found.'], 404);
            return;
        }

        if (empty($reason)) {
            $this->json(['success' => false, 'message' => 'Please select a reason for reporting.'], 422);
            return;
        }

        $otherUserId = ((int) $user['id'] === (int) $conversation['buyer_id'])
            ? (int) ($conversation['supplier_user_id'] ?? 0)
            : (int) $conversation['buyer_id'];

        if (!$otherUserId && !empty($conversation['supplier_id'])) {
            $supplier = Supplier::findById((int) $conversation['supplier_id']);
            if ($supplier && !empty($supplier['user_id'])) {
                $otherUserId = (int) $supplier['user_id'];
            }
        }

        $report = ChatReport::createReport($convId, (int) $user['id'], $otherUserId, $reason, $description);

        // Update conversation to UNDER_REVIEW
        ChatConversation::updateStatus($convId, 'UNDER_REVIEW');

        $this->json(['success' => true, 'message' => 'Report submitted to Sports Junction Trust & Safety Team.', 'report' => $report]);
    }

    /**
     * POST /api/chat/admin/reply
     * Super Admin reply options: Support Reply, On Behalf of Supplier, or Internal Note
     */
    public function adminReply(): void
    {
        $user = $this->requireAuth();
        if (!$user || strtoupper($user['role'] ?? '') !== 'SUPER_ADMIN') {
            $this->json(['success' => false, 'message' => 'Super Admin privileges required.'], 403);
            return;
        }

        $convId = (int) $this->request->post('conversation_id', 0);
        $replyMode = $this->request->post('reply_mode', 'SUPPORT'); // SUPPORT, ON_BEHALF_SUPPLIER, INTERNAL_NOTE
        $body = trim((string) $this->request->post('message_body', ''));

        $conversation = ChatConversation::findById($convId);
        if (!$conversation) {
            $this->json(['success' => false, 'message' => 'Conversation not found.'], 404);
            return;
        }

        if (empty($body)) {
            $this->json(['success' => false, 'message' => 'Reply content cannot be empty.'], 422);
            return;
        }

        $data = [
            'conversation_id' => $convId,
            'message_body' => $body,
            'message_type' => 'TEXT'
        ];

        if ($replyMode === 'INTERNAL_NOTE') {
            $data['sender_user_id'] = (int) $user['id'];
            $data['sender_role'] = 'SUPER_ADMIN';
            $data['is_internal_admin_note'] = 1;
        } elseif ($replyMode === 'ON_BEHALF_SUPPLIER') {
            $data['sender_user_id'] = (int) $conversation['supplier_user_id'];
            $data['sender_role'] = 'SUPPLIER';
            $data['sender_admin_id'] = (int) $user['id'];
            $data['is_on_behalf_of_supplier'] = 1;
        } else {
            // Official Super Admin Support
            $data['sender_user_id'] = (int) $user['id'];
            $data['sender_role'] = 'SUPER_ADMIN';
        }

        $msg = ChatMessage::create($data);
        $this->json(['success' => true, 'message' => $msg]);
    }
}
