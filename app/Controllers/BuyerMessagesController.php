<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatOffer;
use App\Models\ChatBlock;
use App\Models\User;

class BuyerMessagesController extends Controller
{
    private function getBuyer(): array
    {
        $user = $this->authUser();
        if (!$user || !in_array($user['role'] ?? '', ['BUYER', 'SUPPLIER', 'ADMIN', 'SUPER_ADMIN'])) {
            $this->redirect('/login');
            exit;
        }
        $fullUser = User::findById((int) $user['id']);
        return $fullUser ?: $user;
    }

    public function index(): void
    {
        $buyer = $this->getBuyer();
        $buyerId = (int) $buyer['id'];

        $tab = $this->request->get('tab', 'all');
        $search = $this->request->get('search', '');
        $requestedConvId = $this->request->get('conversation_id') ? (int) $this->request->get('conversation_id') : 0;

        $conversations = ChatConversation::getForBuyer($buyerId, ['status' => $tab, 'search' => $search]);

        $activeConversation = null;
        $messages = [];
        $offers = [];
        $latestOffer = null;
        $isBlockedByMe = false;
        $isBlockedByOther = false;

        if ($requestedConvId > 0) {
            foreach ($conversations as $c) {
                if ((int) $c['id'] === $requestedConvId) {
                    $activeConversation = ChatConversation::findById($requestedConvId);
                    break;
                }
            }
            if (!$activeConversation) {
                // Check if user is participant
                $cand = ChatConversation::findById($requestedConvId);
                if ($cand && (int) $cand['buyer_id'] === $buyerId) {
                    $activeConversation = $cand;
                }
            }
        } elseif (!empty($conversations)) {
            $activeConversation = ChatConversation::findById((int) $conversations[0]['id']);
        }

        if ($activeConversation) {
            $convId = (int) $activeConversation['id'];
            ChatMessage::markAsRead($convId, $buyerId);
            $messages = ChatMessage::getByConversation($convId, null, 50, false);
            $offers = ChatOffer::getHistory($convId);
            $latestOffer = ChatOffer::getLatest($convId);

            $supplierUserId = (int) ($activeConversation['supplier_user_id'] ?? 0);
            if ($supplierUserId > 0) {
                $isBlockedByMe = ChatBlock::isBlockedBy($buyerId, $supplierUserId);
                $isBlockedByOther = ChatBlock::isBlockedBy($supplierUserId, $buyerId);
            }
        }

        $unreadTotal = ChatConversation::getTotalUnreadCountForUser($buyerId, 'BUYER');

        $this->render('buyer/messages', [
            'pageTitle' => 'Negotiations & Messages — Sports Junction Buyer Portal',
            'buyer' => $buyer,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
            'offers' => $offers,
            'latestOffer' => $latestOffer,
            'isBlockedByMe' => $isBlockedByMe,
            'isBlockedByOther' => $isBlockedByOther,
            'unreadTotal' => $unreadTotal,
            'currentTab' => $tab,
            'searchQuery' => $search
        ], 'frontend');
    }
}
