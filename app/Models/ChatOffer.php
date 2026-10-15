<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\ChatNotifier;
use InvalidArgumentException;
use RuntimeException;
use PDO;

class ChatOffer extends Model
{
    /**
     * Create a new formal offer or counteroffer
     */
    public static function createOffer(array $data): array
    {
        $conversationId = (int) $data['conversation_id'];
        $createdByUserId = (int) $data['created_by_user_id'];
        $createdByRole = strtoupper($data['created_by_role']);
        $productId = isset($data['product_id']) ? (int) $data['product_id'] : null;
        $parentOfferId = isset($data['parent_offer_id']) && $data['parent_offer_id'] > 0 ? (int) $data['parent_offer_id'] : null;

        $quantity = (int) ($data['quantity'] ?? 1);
        $unit = trim($data['unit'] ?? 'Pieces');
        $unitPrice = (float) ($data['unit_price'] ?? 0.0);
        $currency = trim($data['currency'] ?? 'INR');
        $totalAmount = $quantity * $unitPrice;

        $deliveryLocation = trim($data['delivery_location'] ?? '');
        $leadTime = trim($data['lead_time'] ?? '');
        $customizationTerms = trim($data['customization_terms'] ?? '');
        $additionalTerms = trim($data['additional_terms'] ?? '');
        $expiryDate = !empty($data['expiry_date']) ? $data['expiry_date'] : date('Y-m-d', strtotime('+14 days'));

        if ($quantity <= 0) {
            throw new InvalidArgumentException("Quantity must be greater than zero.");
        }
        if ($unitPrice <= 0) {
            throw new InvalidArgumentException("Unit price must be greater than zero.");
        }

        // Validate MOQ if product is linked
        if ($productId) {
            $product = Product::findById($productId);
            if ($product && !empty($product['moq_quantity'])) {
                $moq = (int) $product['moq_quantity'];
                if ($quantity < $moq) {
                    // Allow flexible negotiations
                }
            }
        }

        $version = 1;
        if ($parentOfferId) {
            $parentOffer = self::findById($parentOfferId);
            if ($parentOffer) {
                $version = ((int) $parentOffer['offer_version']) + 1;
                // Mark parent offer as COUNTERED
                self::execute("UPDATE `chat_offers` SET `status` = 'COUNTERED', `updated_at` = NOW() WHERE `id` = :id", [
                    ':id' => $parentOfferId
                ]);
            }
        }

        $sql = "
            INSERT INTO `chat_offers` (
                `conversation_id`, `product_id`, `created_by_user_id`, `created_by_role`,
                `parent_offer_id`, `offer_version`, `quantity`, `unit`, `unit_price`,
                `currency`, `total_amount`, `delivery_location`, `lead_time`,
                `customization_terms`, `additional_terms`, `status`, `expiry_date`, `created_at`
            ) VALUES (
                :conversation_id, :product_id, :created_by_user_id, :created_by_role,
                :parent_offer_id, :offer_version, :quantity, :unit, :unit_price,
                :currency, :total_amount, :delivery_location, :lead_time,
                :customization_terms, :additional_terms, 'PENDING_ACCEPTANCE', :expiry_date, NOW()
            )
        ";

        self::execute($sql, [
            ':conversation_id' => $conversationId,
            ':product_id' => $productId,
            ':created_by_user_id' => $createdByUserId,
            ':created_by_role' => $createdByRole,
            ':parent_offer_id' => $parentOfferId,
            ':offer_version' => $version,
            ':quantity' => $quantity,
            ':unit' => $unit,
            ':unit_price' => $unitPrice,
            ':currency' => $currency,
            ':total_amount' => $totalAmount,
            ':delivery_location' => $deliveryLocation,
            ':lead_time' => $leadTime,
            ':customization_terms' => $customizationTerms,
            ':additional_terms' => $additionalTerms,
            ':expiry_date' => $expiryDate
        ]);

        $offerId = (int) self::db()->lastInsertId();

        // Update conversation status to OFFER_PENDING
        ChatConversation::updateStatus($conversationId, 'OFFER_PENDING', 'OFFER_MADE');

        // Create structured chat message representing this offer
        $offerSummary = ($parentOfferId ? "Counteroffer (v{$version})" : "Formal Offer (v{$version})") . ": {$quantity} {$unit} @ ₹" . number_format($unitPrice, 2) . " (Total: ₹" . number_format($totalAmount, 2) . ")";
        if ($leadTime) {
            $offerSummary .= " | Lead Time: {$leadTime}";
        }

        $msgMetadata = [
            'offer_id' => $offerId,
            'version' => $version,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'delivery_location' => $deliveryLocation,
            'lead_time' => $leadTime,
            'expiry_date' => $expiryDate
        ];

        ChatMessage::create([
            'conversation_id' => $conversationId,
            'sender_user_id' => $createdByUserId,
            'sender_role' => $createdByRole,
            'message_type' => 'OFFER',
            'message_body' => $offerSummary,
            'metadata' => $msgMetadata
        ]);

        return self::findById($offerId) ?? [];
    }

    /**
     * Accept an offer
     */
    public static function acceptOffer(int $offerId, int $userId): array
    {
        $offer = self::findById($offerId);
        if (!$offer) {
            throw new RuntimeException("Offer not found.");
        }

        if ($offer['status'] !== 'PENDING_ACCEPTANCE') {
            throw new RuntimeException("This offer is no longer pending acceptance (status: {$offer['status']}).");
        }

        // Validate that user is not accepting their own offer
        if ((int) $offer['created_by_user_id'] === $userId) {
            throw new RuntimeException("You cannot accept your own offer.");
        }

        // Mark offer as ACCEPTED
        self::execute("
            UPDATE `chat_offers` 
            SET `status` = 'ACCEPTED', `accepted_by_user_id` = :uid, `accepted_at` = NOW(), `updated_at` = NOW()
            WHERE `id` = :id
        ", [
            ':uid' => $userId,
            ':id' => $offerId
        ]);

        // Update conversation status
        ChatConversation::updateStatus((int) $offer['conversation_id'], 'DEAL_AGREED', 'DEAL_ACCEPTED');

        // Insert System Message
        $systemMessage = "Offer (v{$offer['offer_version']}) accepted! Deal agreed for {$offer['quantity']} {$offer['unit']} at ₹" . number_format((float) $offer['unit_price'], 2) . "/unit (Total: ₹" . number_format((float) $offer['total_amount'], 2) . ").";
        ChatMessage::create([
            'conversation_id' => (int) $offer['conversation_id'],
            'sender_user_id' => $userId,
            'sender_role' => 'SYSTEM',
            'message_type' => 'SYSTEM',
            'message_body' => $systemMessage,
            'metadata' => ['offer_id' => $offerId, 'event' => 'OFFER_ACCEPTED']
        ]);

        // Notify creator
        ChatNotifier::notify(
            (int) $offer['created_by_user_id'],
            'OFFER_ACCEPTED',
            "Offer Accepted! Deal Agreed",
            "Your offer for {$offer['quantity']} {$offer['unit']} has been accepted. View your deal details in the conversation.",
            "/buyer/messages?conversation_id=" . $offer['conversation_id']
        );

        return self::findById($offerId) ?? [];
    }

    /**
     * Reject an offer
     */
    public static function rejectOffer(int $offerId, int $userId, string $reason = ''): array
    {
        $offer = self::findById($offerId);
        if (!$offer) {
            throw new RuntimeException("Offer not found.");
        }

        if ($offer['status'] !== 'PENDING_ACCEPTANCE') {
            throw new RuntimeException("This offer is no longer pending.");
        }

        self::execute("
            UPDATE `chat_offers` 
            SET `status` = 'REJECTED', `additional_terms` = CONCAT(COALESCE(additional_terms, ''), ' [Rejection reason: ', :reason, ']'), `updated_at` = NOW()
            WHERE `id` = :id
        ", [
            ':reason' => $reason,
            ':id' => $offerId
        ]);

        // Revert status to NEGOTIATION_ONGOING
        ChatConversation::updateStatus((int) $offer['conversation_id'], 'NEGOTIATION_ONGOING', 'OFFER_REJECTED');

        // System message
        $systemMessage = "Offer (v{$offer['offer_version']}) declined." . ($reason ? " Reason: {$reason}" : "");
        ChatMessage::create([
            'conversation_id' => (int) $offer['conversation_id'],
            'sender_user_id' => $userId,
            'sender_role' => 'SYSTEM',
            'message_type' => 'SYSTEM',
            'message_body' => $systemMessage,
            'metadata' => ['offer_id' => $offerId, 'event' => 'OFFER_REJECTED']
        ]);

        return self::findById($offerId) ?? [];
    }

    /**
     * Withdraw an offer by its creator
     */
    public static function withdrawOffer(int $offerId, int $userId): array
    {
        $offer = self::findById($offerId);
        if (!$offer) {
            throw new RuntimeException("Offer not found.");
        }

        if ((int) $offer['created_by_user_id'] !== $userId) {
            throw new RuntimeException("Only the creator can withdraw this offer.");
        }

        if ($offer['status'] !== 'PENDING_ACCEPTANCE') {
            throw new RuntimeException("Only pending offers can be withdrawn.");
        }

        self::execute("UPDATE `chat_offers` SET `status` = 'WITHDRAWN', `updated_at` = NOW() WHERE `id` = :id", [
            ':id' => $offerId
        ]);

        ChatConversation::updateStatus((int) $offer['conversation_id'], 'NEGOTIATION_ONGOING', 'OFFER_WITHDRAWN');

        ChatMessage::create([
            'conversation_id' => (int) $offer['conversation_id'],
            'sender_user_id' => $userId,
            'sender_role' => 'SYSTEM',
            'message_type' => 'SYSTEM',
            'message_body' => "Offer (v{$offer['offer_version']}) was withdrawn by creator.",
            'metadata' => ['offer_id' => $offerId, 'event' => 'OFFER_WITHDRAWN']
        ]);

        return self::findById($offerId) ?? [];
    }

    /**
     * Find offer by ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                o.*,
                p.title AS product_title,
                p.slug AS product_slug,
                p.moq_quantity AS product_moq,
                u.name AS creator_first_name,
                '' AS creator_last_name,
                u.company_name AS creator_company
            FROM `chat_offers` o
            LEFT JOIN `products` p ON o.product_id = p.id
            LEFT JOIN `users` u ON o.created_by_user_id = u.id
            WHERE o.id = :id
            LIMIT 1
        ";
        return self::fetch($sql, [':id' => $id]);
    }

    /**
     * Get all offers in a conversation (offer history and negotiation versions)
     */
    public static function getHistory(int $conversationId): array
    {
        $sql = "
            SELECT 
                o.*,
                p.title AS product_title,
                u.name AS creator_first_name,
                '' AS creator_last_name,
                u.company_name AS creator_company
            FROM `chat_offers` o
            LEFT JOIN `products` p ON o.product_id = p.id
            LEFT JOIN `users` u ON o.created_by_user_id = u.id
            WHERE o.conversation_id = :conv_id
            ORDER BY o.id ASC
        ";
        return self::fetchAll($sql, [':conv_id' => $conversationId]);
    }

    /**
     * Get latest active or pending offer in a conversation
     */
    public static function getLatest(int $conversationId): ?array
    {
        $sql = "
            SELECT 
                o.*,
                p.title AS product_title,
                u.name AS creator_first_name,
                '' AS creator_last_name,
                u.company_name AS creator_company
            FROM `chat_offers` o
            LEFT JOIN `products` p ON o.product_id = p.id
            LEFT JOIN `users` u ON o.created_by_user_id = u.id
            WHERE o.conversation_id = :conv_id
            ORDER BY o.id DESC
            LIMIT 1
        ";
        return self::fetch($sql, [':conv_id' => $conversationId]);
    }
}
