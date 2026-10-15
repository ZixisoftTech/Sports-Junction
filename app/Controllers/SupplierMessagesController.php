<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatOffer;
use App\Models\ChatBlock;
use App\Models\Supplier;

class SupplierMessagesController extends Controller
{
    private function getSupplier(): array
    {
        $user = $this->authUser();
        $supplier = Supplier::findByUserId((int) ($user['id'] ?? 0));
        if (!$supplier) {
            $this->redirect('/login');
            exit;
        }
        return $supplier;
    }

    public function index(): void
    {
        $supplier = $this->getSupplier();
        $supplierId = (int) $supplier['id'];
        $supplierUserId = (int) $supplier['user_id'];

        $tab = $this->request->get('tab', 'all');
        $search = $this->request->get('search', '');
        $requestedConvId = $this->request->get('conversation_id') ? (int) $this->request->get('conversation_id') : 0;

        $conversations = ChatConversation::getForSupplier($supplierId, ['tab' => $tab, 'search' => $search]);

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
                $cand = ChatConversation::findById($requestedConvId);
                if ($cand && (int) $cand['supplier_id'] === $supplierId) {
                    $activeConversation = $cand;
                }
            }
        } elseif (!empty($conversations)) {
            $activeConversation = ChatConversation::findById((int) $conversations[0]['id']);
        }

        if ($activeConversation) {
            $convId = (int) $activeConversation['id'];
            ChatMessage::markAsRead($convId, $supplierUserId);
            $messages = ChatMessage::getByConversation($convId, null, 50, false);
            $offers = ChatOffer::getHistory($convId);
            $latestOffer = ChatOffer::getLatest($convId);

            $buyerUserId = (int) $activeConversation['buyer_id'];
            if ($buyerUserId > 0) {
                $isBlockedByMe = ChatBlock::isBlockedBy($supplierUserId, $buyerUserId);
                $isBlockedByOther = ChatBlock::isBlockedBy($buyerUserId, $supplierUserId);
            }
        }

        $unreadTotal = ChatConversation::getTotalUnreadCountForUser($supplierUserId, 'SUPPLIER');

        $this->render('supplier/messages', [
            'pageTitle' => 'Buyer Inquiries & Negotiations — Supplier Partner Portal',
            'supplier' => $supplier,
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
        ], 'supplier');
    }
}
