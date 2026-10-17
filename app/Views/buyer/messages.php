<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
$activeConvId = $activeConversation ? (int) $activeConversation['id'] : 0;

$offersById = [];
if (!empty($offers)) {
    foreach ($offers as $off) {
        $offersById[(int)$off['id']] = $off;
    }
}
?>

<div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 py-2 flex flex-col h-[calc(100vh-140px)] space-y-2">
    <!-- Compact Header Toolbar (Zero Page Scroll) -->
    <div class="flex items-center justify-between py-1 px-1 flex-shrink-0">
        <div class="flex items-center gap-2.5">
            <h1 class="font-headline font-bold text-sm sm:text-base text-slate-900 tracking-tight flex items-center gap-1.5">
                <span class="material-symbols-outlined text-blue-900 text-[20px]">chat</span>
                <span>Messages & Deal Negotiation</span>
            </h1>
            <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-900 text-[10px] font-bold">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span> Direct Sourcing
            </span>
        </div>

        <div class="flex items-center gap-2">
            <!-- Audio Mute Switch -->
            <button type="button" id="btnSoundToggle" onclick="toggleChatSound()" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors" title="Toggle Sound Alerts">
                <span class="material-symbols-outlined text-[16px]" id="soundIcon">volume_up</span>
                <span id="soundLabel">Sound On</span>
            </button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- 3-Pane Messaging Workspace (Freelancer Style Dual/Triple Pane) -->
    <div class="flex-1 bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-0">
        
        <!-- PANE 1: CONVERSATIONS LIST (3 Cols on Desktop) -->
        <div class="lg:col-span-3 border-r border-slate-200/80 flex flex-col bg-slate-50/50 h-full overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-3 border-b border-slate-200 bg-white space-y-2 flex-shrink-0">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2 text-slate-400 text-[18px]">search</span>
                    <input type="text" id="convSearchInput" value="<?= htmlspecialchars($searchQuery ?? '') ?>" onkeyup="filterConversations(this.value)" placeholder="Search supplier or product..." class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-blue-600 bg-slate-50/80">
                </div>

                <!-- Tabs -->
                <div class="flex items-center gap-1 overflow-x-auto text-[11px] font-bold pb-0.5">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/messages?tab=all" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'all') ? 'bg-blue-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">All</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/messages?tab=active" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'active') ? 'bg-blue-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Active</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/messages?tab=offer_pending" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'offer_pending') ? 'bg-blue-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Offers</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/messages?tab=deal_agreed" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'deal_agreed') ? 'bg-blue-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Agreed</a>
                </div>
            </div>

            <!-- Conversation Items -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100" id="convListContainer" style="overscroll-behavior: contain;">
                <?php if (empty($conversations)): ?>
                    <div class="p-8 text-center text-slate-400">
                        <span class="material-symbols-outlined text-[36px] text-slate-300 mb-2">forum</span>
                        <p class="text-xs font-semibold">No conversations yet</p>
                        <p class="text-[11px] text-slate-400 mt-1">Inquire on any product to start chatting with verified suppliers.</p>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="mt-4 inline-block px-3 py-1.5 rounded-lg bg-blue-900 text-white text-[11px] font-bold">Browse Catalog</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                        <?php 
                            $isSelected = ((int)$c['id'] === $activeConvId);
                            $unread = (int) ($c['unread_count'] ?? 0);
                        ?>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/messages?conversation_id=<?= (int)$c['id'] ?>&tab=<?= htmlspecialchars($currentTab) ?>" class="block p-3.5 transition-all hover:bg-blue-50/50 <?= $isSelected ? 'bg-blue-50/90 border-l-4 border-blue-900' : '' ?> conv-card-item" data-supplier="<?= htmlspecialchars(strtolower($c['supplier_company_name'] ?? '')) ?>" data-product="<?= htmlspecialchars(strtolower($c['product_title'] ?? '')) ?>">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex items-center gap-1.5 overflow-hidden">
                                    <span class="font-bold text-xs text-slate-900 truncate"><?= htmlspecialchars($c['supplier_company_name'] ?? 'Verified Manufacturer') ?></span>
                                    <span class="material-symbols-outlined text-teal-600 text-[14px]">verified</span>
                                </div>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap">
                                    <?= !empty($c['last_message_at']) ? date('d M, H:i', strtotime($c['last_message_at'])) : date('d M', strtotime($c['created_at'])) ?>
                                </span>
                            </div>

                            <?php if (!empty($c['product_title'])): ?>
                                <div class="text-[11px] font-semibold text-blue-900 truncate mb-1">
                                    <?= htmlspecialchars($c['product_title']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span class="truncate text-[11px] max-w-[170px]">
                                    <?= htmlspecialchars($c['last_message_body'] ?? 'Conversation opened.') ?>
                                </span>
                                <?php if ($unread > 0): ?>
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-blue-600 text-white font-bold text-[10px] min-w-[18px]">
                                        <?= $unread ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Deal Status Badge -->
                            <div class="mt-2 flex items-center justify-between text-[10px]">
                                <?php if ($c['status'] === 'DEAL_AGREED' || $c['status'] === 'DEAL_COMPLETED'): ?>
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold">Deal Agreed</span>
                                <?php elseif ($c['status'] === 'OFFER_PENDING'): ?>
                                    <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-bold">Offer Pending</span>
                                <?php elseif ($c['status'] === 'NEGOTIATION_ONGOING'): ?>
                                    <span class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-800 font-bold">Negotiating</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-medium"><?= htmlspecialchars($c['status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- PANE 2: ACTIVE CHAT THREAD (6 Cols on Desktop) -->
        <div class="lg:col-span-6 flex flex-col bg-slate-50/30 h-full overflow-hidden">
            <?php if (!$activeConversation): ?>
                <div class="flex-1 flex flex-col items-center justify-center p-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-[64px] text-slate-300 mb-3">chat_bubble_outline</span>
                    <h3 class="font-headline font-bold text-lg text-slate-700">Select a Conversation</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Choose an active conversation from the left pane or explore products to start a new negotiation.</p>
                </div>
            <?php else: ?>
                <!-- Active Header -->
                <div class="p-3 border-b border-slate-200 bg-white flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-blue-900 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                            <?= strtoupper(substr($activeConversation['supplier_company_name'] ?? 'M', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="font-headline font-bold text-sm text-slate-900"><?= htmlspecialchars($activeConversation['supplier_company_name'] ?? 'Supplier') ?></h3>
                                <span class="material-symbols-outlined text-teal-600 text-[16px]" title="Verified Manufacturer">verified</span>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span class="flex items-center gap-1 text-emerald-600 font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Direct Manufacturer SLA</span>
                                </span>
                                <span>•</span>
                                <span>Ref #SJ-CONV-<?= (int)$activeConversation['id'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 relative">
                        <button type="button" onclick="openOfferModal()" class="px-2.5 py-1.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">handshake</span>
                            <span class="hidden sm:inline">Make Counteroffer</span>
                        </button>
                        <button type="button" onclick="toggleContextMenu()" class="p-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition-colors" title="More Options">
                            <span class="material-symbols-outlined text-[20px]">more_vert</span>
                        </button>
                        <!-- Context Dropdown Menu -->
                        <div id="chatContextMenu" class="hidden absolute right-0 top-11 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-40 text-xs">
                            <button type="button" onclick="openReportModal()" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 text-slate-700 flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-600 text-[18px]">flag</span>
                                <span>Report Conversation</span>
                            </button>
                            <button type="button" onclick="toggleBlockUser(<?= (int)$activeConversation['supplier_user_id'] ?>, <?= $isBlockedByMe ? "'unblock'" : "'block'" ?>)" class="w-full text-left px-3.5 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">block</span>
                                <span><?= $isBlockedByMe ? 'Unblock Supplier' : 'Block Supplier' ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contact Policy Warning Banner -->
                <div class="px-4 py-1.5 bg-amber-50 border-b border-amber-200 text-amber-900 text-[11px] flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600 text-[15px]">security</span>
                        <span>Trade safely: Keep communications and quotation agreements on Sports Junction. Commercial contracts and payments are settled directly between buyers and verified suppliers under proforma invoice terms.</span>
                    </div>
                </div>

                <!-- Messages Stream -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chatMessagesContainer" style="overscroll-behavior: contain;">
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $isMe = ((int)$msg['sender_user_id'] === (int)$buyer['id']);
                            $type = $msg['message_type'] ?? 'TEXT';
                        ?>
                        <?php if ($type === 'SYSTEM'): ?>
                            <!-- System Pill -->
                            <div class="flex justify-center my-2">
                                <div class="px-3.5 py-1 rounded-full bg-slate-200/80 border border-slate-300 text-slate-700 text-[11px] font-semibold text-center max-w-md shadow-xs">
                                    <span class="material-symbols-outlined text-[14px] align-middle text-blue-900 mr-1">info</span>
                                    <?= htmlspecialchars($msg['message_body']) ?>
                                    <span class="text-[9px] text-slate-500 ml-1"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php elseif ($type === 'OFFER'): ?>
                            <!-- Structured Offer Card in Feed -->
                            <?php 
                                $offerMeta = !empty($msg['metadata']) ? (is_array($msg['metadata']) ? $msg['metadata'] : json_decode($msg['metadata'], true)) : [];
                                $offerId = (int)($offerMeta['offer_id'] ?? 0);
                                $currOffer = ($offerId > 0 && isset($offersById[$offerId])) ? $offersById[$offerId] : null;
                                $offerStatus = $currOffer['status'] ?? 'PENDING_ACCEPTANCE';
                            ?>
                            <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?> my-2" id="offer-card-<?= $offerId ?>">
                                <div class="max-w-md w-full rounded-2xl p-4 bg-white border-2 <?= ($offerStatus === 'ACCEPTED') ? 'border-emerald-500 shadow-md bg-emerald-50/20' : ($isMe ? 'border-blue-700 shadow-md' : 'border-emerald-600 shadow-md') ?>">
                                    <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined <?= ($offerStatus === 'ACCEPTED') ? 'text-emerald-600' : ($isMe ? 'text-blue-900' : 'text-emerald-700') ?> text-[20px]">
                                                <?= ($offerStatus === 'ACCEPTED') ? 'check_circle' : 'request_quote' ?>
                                            </span>
                                            <span class="font-headline font-bold text-xs text-slate-900">
                                                <?= $isMe ? 'Your Offer Proposal' : 'Manufacturer Formal Offer' ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Dynamic Status Badge -->
                                        <?php if ($offerStatus === 'ACCEPTED'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1" id="offer-badge-<?= $offerId ?>">
                                                <span class="material-symbols-outlined text-[12px]">done_all</span> Deal Agreed & Accepted
                                            </span>
                                        <?php elseif ($offerStatus === 'COUNTERED'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700" id="offer-badge-<?= $offerId ?>">Countered</span>
                                        <?php elseif ($offerStatus === 'REJECTED'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800" id="offer-badge-<?= $offerId ?>">Declined</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800" id="offer-badge-<?= $offerId ?>">Pending Review</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-xs space-y-1.5 text-slate-700">
                                        <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($msg['message_body']) ?></p>
                                        <?php if (!empty($currOffer['lead_time']) || !empty($offerMeta['lead_time'])): ?>
                                            <p class="text-[11px] text-slate-500">Estimated Lead Time: <span class="font-semibold text-slate-700"><?= htmlspecialchars($currOffer['lead_time'] ?? $offerMeta['lead_time']) ?></span></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action Buttons: Shown only if pending -->
                                    <?php if (!$isMe && $offerId > 0): ?>
                                        <?php if ($offerStatus === 'PENDING_ACCEPTANCE'): ?>
                                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center gap-2" id="offer-actions-<?= $offerId ?>">
                                                <button type="button" onclick="acceptOffer(<?= $offerId ?>)" class="flex-1 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1 shadow-xs">
                                                    <span class="material-symbols-outlined text-[15px]">done</span> Accept Deal
                                                </button>
                                                <button type="button" onclick="openOfferModal(<?= $offerId ?>)" class="flex-1 py-1.5 rounded-lg bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1 shadow-xs">
                                                    <span class="material-symbols-outlined text-[15px]">sync_alt</span> Counteroffer
                                                </button>
                                                <button type="button" onclick="rejectOffer(<?= $offerId ?>)" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                                    Decline
                                                </button>
                                            </div>
                                        <?php elseif ($offerStatus === 'ACCEPTED'): ?>
                                            <div class="mt-3 pt-2.5 border-t border-emerald-200 flex items-center justify-between text-xs text-emerald-800 font-bold bg-emerald-50/60 p-2 rounded-lg" id="offer-actions-<?= $offerId ?>">
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-emerald-600 text-[18px]">verified</span>
                                                    <span>Deal Officially Accepted</span>
                                                </span>
                                                <span class="text-[10px] text-emerald-700 font-semibold">Terms Binding</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($isMe && $offerStatus === 'ACCEPTED'): ?>
                                        <div class="mt-3 pt-2.5 border-t border-emerald-200 flex items-center justify-between text-xs text-emerald-800 font-bold bg-emerald-50/60 p-2 rounded-lg" id="offer-actions-<?= $offerId ?>">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-emerald-600 text-[18px]">verified</span>
                                                <span>Your Offer Accepted by Partner</span>
                                            </span>
                                            <span class="text-[10px] text-emerald-700 font-semibold">Deal Locked</span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-2 text-right text-[9px] text-slate-400">
                                        <?= date('d M Y, H:i', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Standard Message Bubble (Freelancer style: avatar on left for counterparty, clean bubbles) -->
                            <div class="flex <?= $isMe ? 'justify-end' : 'justify-start items-start gap-2.5' ?> my-1">
                                <?php if (!$isMe): ?>
                                    <div class="w-8 h-8 rounded-full bg-teal-800 text-white font-bold flex items-center justify-center text-xs flex-shrink-0 mt-0.5 shadow-2xs">
                                        <?= strtoupper(substr($activeConversation['supplier_company_name'] ?? 'M', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-xs shadow-xs relative <?= $isMe ? 'bg-blue-900 text-white rounded-br-none' : 'bg-white border border-slate-200/90 text-slate-900 rounded-bl-none' ?>">
                                    <?php if (!$isMe): ?>
                                        <div class="font-bold text-[10px] text-teal-700 mb-0.5 flex items-center gap-1">
                                            <span><?= htmlspecialchars($activeConversation['supplier_company_name'] ?? 'Supplier') ?></span>
                                            <?php if (!empty($msg['is_on_behalf_of_supplier'])): ?>
                                                <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-900 text-[9px]" title="Assisted by Marketplace Concierge">Sports Junction Official Rep</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Message Body -->
                                    <?php 
                                        $hasAttachment = !empty($msg['attachment_path']);
                                        $isAutoAttachText = $hasAttachment && str_starts_with($msg['message_body'], 'Sent an attachment:');
                                    ?>
                                    <?php if (!$isAutoAttachText && !empty(trim($msg['message_body']))): ?>
                                        <div class="leading-relaxed break-words whitespace-pre-wrap"><?= htmlspecialchars($msg['message_body']) ?></div>
                                    <?php endif; ?>

                                    <!-- Attachment if present -->
                                    <?php if ($hasAttachment): ?>
                                        <?php
                                            $ext = strtolower(pathinfo($msg['attachment_name'], PATHINFO_EXTENSION));
                                            $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                            $isPdf = ($ext === 'pdf');
                                            $fileUrl = htmlspecialchars($baseUrl . $msg['attachment_path']);
                                            $downloadUrl = htmlspecialchars($baseUrl . '/api/chat/attachments/' . (int)$msg['attachment_id'] . '/download');
                                            $fileName = htmlspecialchars($msg['attachment_name']);
                                            $fileSize = round($msg['attachment_size'] / 1024, 1) . ' KB';
                                        ?>
                                        <?php if ($isImg): ?>
                                            <div class="mt-2 rounded-xl overflow-hidden border <?= $isMe ? 'border-blue-800 bg-blue-950/40' : 'border-slate-200 bg-slate-100' ?> max-w-xs">
                                                <a href="<?= $fileUrl ?>" target="_blank" rel="noopener noreferrer" class="block group relative">
                                                    <img src="<?= $fileUrl ?>" alt="<?= $fileName ?>" class="max-h-48 w-full object-cover group-hover:opacity-95 transition-opacity">
                                                    <div class="p-1.5 <?= $isMe ? 'bg-blue-950/80 text-white' : 'bg-white text-slate-800' ?> flex items-center justify-between text-[10px]">
                                                        <span class="truncate max-w-[150px] font-medium"><?= $fileName ?></span>
                                                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                                    </div>
                                                </a>
                                                <div class="px-2 py-1 <?= $isMe ? 'bg-blue-900 text-blue-200 border-blue-800' : 'bg-slate-50 text-slate-600 border-slate-200' ?> flex items-center justify-between text-[10px] border-t">
                                                    <span><?= $fileSize ?></span>
                                                    <a href="<?= $downloadUrl ?>" class="font-bold flex items-center gap-1 hover:underline <?= $isMe ? 'text-white' : 'text-blue-900' ?>">
                                                        <span class="material-symbols-outlined text-[13px]">download</span> Download
                                                    </a>
                                                </div>
                                            </div>
                                        <?php elseif ($isPdf): ?>
                                            <div class="mt-2 p-2.5 rounded-xl border <?= $isMe ? 'bg-blue-950/70 border-blue-800 text-white' : 'bg-rose-50/70 border-rose-200 text-slate-900' ?> max-w-xs flex items-center justify-between gap-2.5 shadow-2xs">
                                                <div class="flex items-center gap-2 overflow-hidden">
                                                    <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center flex-shrink-0 font-bold text-[9px] tracking-wider shadow-xs">
                                                        PDF
                                                    </div>
                                                    <div class="truncate text-xs">
                                                        <span class="font-bold block truncate text-[11px]"><?= $fileName ?></span>
                                                        <span class="text-[9px] opacity-75"><?= $fileSize ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1 flex-shrink-0">
                                                    <a href="<?= $fileUrl ?>" target="_blank" rel="noopener noreferrer" class="px-2 py-1 rounded-lg bg-white border border-rose-200 text-rose-700 hover:bg-rose-100 font-semibold text-[10px] transition-colors flex items-center gap-0.5" title="Preview PDF in new tab">
                                                        <span class="material-symbols-outlined text-[13px]">visibility</span> View
                                                    </a>
                                                    <a href="<?= $downloadUrl ?>" class="p-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[10px] transition-colors shadow-xs" title="Download PDF">
                                                        <span class="material-symbols-outlined text-[13px]">download</span>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-2.5 p-2 rounded-xl <?= $isMe ? 'bg-blue-950/60 text-white' : 'bg-slate-50 border border-slate-200 text-slate-800' ?> flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2 overflow-hidden">
                                                    <span class="material-symbols-outlined text-[20px] <?= $isMe ? 'text-blue-300' : 'text-blue-700' ?>">attach_file</span>
                                                    <div class="truncate text-[11px]">
                                                        <span class="font-bold block truncate"><?= $fileName ?></span>
                                                        <span class="text-[9px] opacity-75"><?= strtoupper($ext) ?> • <?= $fileSize ?></span>
                                                    </div>
                                                </div>
                                                <a href="<?= $downloadUrl ?>" class="p-1.5 rounded-lg <?= $isMe ? 'bg-blue-800 hover:bg-blue-700 text-white' : 'bg-white border border-slate-200 hover:bg-slate-100 text-slate-700' ?> transition-colors" title="Download Document">
                                                    <span class="material-symbols-outlined text-[16px]">download</span>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Timestamp & Delivery Status -->
                                    <div class="mt-1 flex items-center justify-end gap-1 text-[9px] <?= $isMe ? 'text-blue-200' : 'text-slate-400' ?>">
                                        <span><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                        <?php if ($isMe): ?>
                                            <?php if ($msg['delivery_status'] === 'READ'): ?>
                                                <span class="material-symbols-outlined text-[14px] text-teal-300" title="Read">done_all</span>
                                            <?php elseif ($msg['delivery_status'] === 'DELIVERED'): ?>
                                                <span class="material-symbols-outlined text-[14px] text-blue-200" title="Delivered">done_all</span>
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-[14px] text-blue-300" title="Sent">done</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Quick Response Chips -->
                <div class="px-3 py-1.5 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto text-[11px] text-slate-600 flex-shrink-0">
                    <span class="font-bold text-[10px] uppercase text-slate-400 mr-1">Quick:</span>
                    <button type="button" onclick="insertTemplate('What is the best wholesale price for bulk order?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">Bulk Quote</button>
                    <button type="button" onclick="insertTemplate('Can you provide an official production sample before full run?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">Request Sample</button>
                    <button type="button" onclick="insertTemplate('What are your delivery lead times and packaging specifications?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">Lead Time & Specs</button>
                    <button type="button" onclick="insertTemplate('Do you provide custom logo branding and Pantone matching?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">Custom Branding</button>
                </div>

                <!-- Composer or Blocked Banner -->
                <div class="p-3 bg-white border-t border-slate-200 flex-shrink-0">
                    <?php if ($isBlockedByMe): ?>
                        <div class="p-3 bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-red-600 text-[18px]">block</span>
                                <span class="font-semibold">You have blocked this supplier. Messaging is suspended.</span>
                            </div>
                            <button type="button" onclick="toggleBlockUser(<?= (int)$activeConversation['supplier_user_id'] ?>, 'unblock')" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-xs transition-colors">
                                Unblock
                            </button>
                        </div>
                    <?php elseif ($isBlockedByOther): ?>
                        <div class="p-3 bg-slate-100 border border-slate-200 text-slate-600 text-xs rounded-xl flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-500 text-[18px]">lock</span>
                            <span>Messaging is currently disabled with this participant.</span>
                        </div>
                    <?php else: ?>
                        <form id="chatMessageForm" onsubmit="handleSendMessage(event)" class="space-y-2">
                            <input type="hidden" name="conversation_id" value="<?= $activeConvId ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

                            <!-- Warning Alert Container -->
                            <div id="contactPolicyAlert" class="hidden p-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-600 text-[18px]">warning</span>
                                <span id="contactPolicyText"></span>
                            </div>

                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-full px-3 py-1.5 focus-within:border-blue-900 focus-within:bg-white transition-all shadow-2xs">
                                <!-- Attachment Upload Button -->
                                <label class="p-1.5 rounded-full hover:bg-slate-200/80 text-slate-500 hover:text-blue-900 cursor-pointer transition-colors" title="Attach PDF or Image (Max 10MB)">
                                    <span class="material-symbols-outlined text-[20px]">attach_file</span>
                                    <input type="file" id="chatAttachmentInput" onchange="handleFileUpload(this)" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                </label>

                                <!-- Text Input -->
                                <input type="text" id="chatInputBody" placeholder="Type your message, negotiation terms, or specifications..." class="flex-1 bg-transparent border-none text-xs text-slate-800 placeholder-slate-400 focus:outline-none">

                                <!-- Send Button -->
                                <button type="submit" id="btnSendMessage" class="w-8 h-8 rounded-full bg-blue-900 hover:bg-blue-950 text-white flex items-center justify-center shadow-xs transition-colors flex-shrink-0" title="Send Message">
                                    <span class="material-symbols-outlined text-[16px]">send</span>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANE 3: PRODUCT & NEGOTIATION SIDEBAR (3 Cols on Desktop, Independent Scroll) -->
        <div class="lg:col-span-3 border-l border-slate-200/80 p-4 bg-slate-50/50 space-y-3.5 h-full overflow-y-auto" style="overscroll-behavior: contain;">
            <?php if ($activeConversation): ?>
                <!-- Supplier Info Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700 block mb-1">Manufacturer Partner</span>
                    <h4 class="font-headline font-bold text-sm text-slate-900"><?= htmlspecialchars($activeConversation['supplier_company_name'] ?? 'Supplier') ?></h4>
                    <p class="text-xs text-slate-500 mt-0.5">Contact: <?= htmlspecialchars($activeConversation['supplier_contact_person'] ?? 'Factory Rep') ?></p>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-600">
                        <span class="flex items-center gap-1 text-teal-700 font-semibold">
                            <span class="material-symbols-outlined text-[14px]">verified</span>
                            <span>Verified B2B Vendor</span>
                        </span>
                    </div>
                </div>

                <!-- Product Card -->
                <?php if (!empty($activeConversation['product_title'])): ?>
                    <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-xs space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Sourcing Product</span>
                        <div class="flex items-center gap-2.5">
                            <?php if (!empty($activeConversation['product_image'])): ?>
                                <img src="<?= htmlspecialchars(Response::baseUrl() . $activeConversation['product_image']) ?>" alt="" class="w-12 h-12 object-cover rounded-lg border border-slate-200 flex-shrink-0">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
                                    <span class="material-symbols-outlined text-[20px]">sports_cricket</span>
                                </div>
                            <?php endif; ?>
                            <div class="overflow-hidden">
                                <h5 class="font-headline font-bold text-xs text-slate-900 truncate"><?= htmlspecialchars($activeConversation['product_title']) ?></h5>
                                <div class="text-[11px] text-blue-900 font-bold mt-0.5">$<?= number_format((float)($activeConversation['product_price'] ?? 0), 2) ?></div>
                                <div class="text-[10px] text-slate-500">MOQ: <?= (int)($activeConversation['product_moq'] ?? 1) ?> units</div>
                            </div>
                        </div>
                        <?php if (!empty($activeConversation['product_slug'])): ?>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($activeConversation['product_slug']) ?>" target="_blank" class="w-full py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold text-center block transition-colors mt-2">
                                View Full Specs
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Deal Stage Progression Tracker -->
                <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-xs" id="dealProgressionTracker">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2.5">Deal Progression</span>
                    
                    <?php 
                        $stage = $activeConversation['status'] ?? 'ACTIVE';
                        $stages = [
                            'ACTIVE' => 1,
                            'NEGOTIATION_ONGOING' => 2,
                            'OFFER_PENDING' => 3,
                            'DEAL_AGREED' => 4,
                            'DEAL_COMPLETED' => 5
                        ];
                        $curStep = $stages[$stage] ?? 1;
                    ?>

                    <div class="space-y-2.5 text-xs">
                        <div id="deal-step-1" class="flex items-center gap-2 <?= $curStep >= 1 ? 'text-blue-900 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 1 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600' ?>">1</span>
                            <span>Initial Contact & Inquiry</span>
                        </div>
                        <div id="deal-step-2" class="flex items-center gap-2 <?= $curStep >= 2 ? 'text-blue-900 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 2 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600' ?>">2</span>
                            <span>Terms Negotiation</span>
                        </div>
                        <div id="deal-step-3" class="flex items-center gap-2 <?= $curStep >= 3 ? 'text-blue-900 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 3 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600' ?>">3</span>
                            <span>Formal Offer Proposal</span>
                        </div>
                        <div id="deal-step-4" class="flex items-center gap-2 <?= $curStep >= 4 ? 'text-emerald-700 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 4 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' ?>">4</span>
                            <span>Deal Agreed & Accepted</span>
                        </div>
                    </div>
                </div>

                <!-- Safety Actions -->
                <div class="pt-1 space-y-2">
                    <button type="button" onclick="openReportModal()" class="w-full py-2 rounded-xl border border-amber-300 bg-amber-50/70 hover:bg-amber-100 text-amber-800 text-xs font-bold transition-colors flex items-center justify-center gap-1.5 shadow-2xs">
                        <span class="material-symbols-outlined text-[16px]">flag</span>
                        <span>Report Misconduct</span>
                    </button>
                    <button type="button" onclick="toggleBlockUser(<?= (int)$activeConversation['supplier_user_id'] ?>, <?= $isBlockedByMe ? "'unblock'" : "'block'" ?>)" class="w-full py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors flex items-center justify-center gap-1.5 shadow-2xs">
                        <span class="material-symbols-outlined text-[16px]">block</span>
                        <span><?= $isBlockedByMe ? 'Unblock Supplier' : 'Block Supplier' ?></span>
                    </button>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-slate-400 text-xs">
                    Select a conversation to view manufacturer credentials and deal parameters.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- MODAL 1: FORMAL OFFER / COUNTEROFFER MODAL -->
<div id="offerModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-900 text-[24px]">handshake</span>
                <h3 class="font-headline font-bold text-base text-slate-900" id="offerModalTitle">Submit Formal Proposal</h3>
            </div>
            <button type="button" onclick="closeOfferModal()" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form id="offerForm" onsubmit="handleSubmitOffer(event)" class="space-y-3.5 text-xs">
            <input type="hidden" name="conversation_id" value="<?= $activeConvId ?>">
            <input type="hidden" name="parent_offer_id" id="offerParentId" value="">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Target Quantity *</label>
                    <input type="number" id="offerQty" name="quantity" min="1" value="<?= (int)($activeConversation['product_moq'] ?? 100) ?>" oninput="calculateOfferTotal()" required class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Unit Type</label>
                    <input type="text" name="unit" value="Pieces" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Proposed Unit Price ($) *</label>
                    <input type="number" id="offerUnitPrice" name="unit_price" step="0.01" min="1" value="<?= (float)($activeConversation['product_price'] ?? 500) ?>" oninput="calculateOfferTotal()" required class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900 font-bold text-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Total Estimated Value ($)</label>
                    <input type="text" id="offerTotalDisplay" readonly class="w-full px-3 py-2 rounded-xl border border-slate-100 bg-slate-50 font-extrabold text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Delivery Destination / City</label>
                    <input type="text" name="delivery_location" placeholder="e.g. Mumbai Port or Warehouse" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Required Lead Time</label>
                    <input type="text" name="lead_time" placeholder="e.g. 15-20 Days" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Customization / Packaging Requirements</label>
                <textarea name="customization_terms" rows="2" placeholder="Custom logo, special packaging, color codes..." class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeOfferModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold transition-colors">
                    Submit Proposal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: SAFETY REPORT MODAL -->
<div id="reportModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2 text-amber-600">
                <span class="material-symbols-outlined text-[24px]">flag</span>
                <h3 class="font-headline font-bold text-base text-slate-900">Report to Trust & Safety</h3>
            </div>
            <button type="button" onclick="closeReportModal()" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form id="reportForm" onsubmit="handleSubmitReport(event)" class="space-y-3.5 text-xs">
            <input type="hidden" name="conversation_id" value="<?= $activeConvId ?>">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Reason for Report *</label>
                <select name="reason" required class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900">
                    <option value="">Select a reason...</option>
                    <option value="Off-platform payment or contact solicitation">Soliciting off-platform direct contact/payment</option>
                    <option value="Fraudulent product specs or counterfeit goods">Fraudulent product claims / Counterfeit specs</option>
                    <option value="Unprofessional or abusive language">Abusive or unprofessional behavior</option>
                    <option value="Spam or unsolicited advertising">Spam or irrelevant advertising</option>
                    <option value="Other marketplace policy violation">Other policy violation</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Details & Context</label>
                <textarea name="description" rows="3" placeholder="Explain the specific issue for Super Admin investigation..." class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeReportModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold transition-colors">
                    Submit Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CLIENT JAVASCRIPT: SSE REAL-TIME, SOUND SYNTHESIS & WEBSOCKET/POLL FALLBACK -->
<script>
const activeConvId = <?= $activeConvId ?>;
const baseUrl = <?= json_encode($baseUrl) ?>;
let lastMessageId = <?= !empty($messages) ? (int) end($messages)['id'] : 0 ?>;
let soundEnabled = (localStorage.getItem('sj_chat_sound') !== 'muted');

// Web Audio API Synthesizer (Pleasant two-tone chime)
function playChatChime() {
    if (!soundEnabled) return;
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.15); // A5
        gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.35);
    } catch(e) {}
}

function toggleChatSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('sj_chat_sound', soundEnabled ? 'active' : 'muted');
    updateSoundUI();
}

function updateSoundUI() {
    const icon = document.getElementById('soundIcon');
    const label = document.getElementById('soundLabel');
    if (soundEnabled) {
        icon.innerText = 'volume_up';
        label.innerText = 'Sound On';
    } else {
        icon.innerText = 'volume_off';
        label.innerText = 'Sound Muted';
    }
}
updateSoundUI();

// Scroll chat feed to bottom
function scrollChatToBottom() {
    const c = document.getElementById('chatMessagesContainer');
    if (c) c.scrollTop = c.scrollHeight;
}
scrollChatToBottom();

// Real-Time Transport: SSE with polling fallback
let sseSource = null;
function initRealtimeStream() {
    if (!activeConvId) return;

    if (window.EventSource) {
        sseSource = new EventSource(`${baseUrl}/api/chat/stream?conversation_id=${activeConvId}&last_id=${lastMessageId}`);
        
        sseSource.addEventListener('message', function(e) {
            try {
                const msg = JSON.parse(e.data);
                if (msg && msg.id > lastMessageId) {
                    lastMessageId = msg.id;
                    appendIncomingMessage(msg);
                    playChatChime();
                }
            } catch(err) {}
        });

        sseSource.addEventListener('timeout', function(e) {
            sseSource.close();
            setTimeout(initRealtimeStream, 1000);
        });

        sseSource.onerror = function() {
            sseSource.close();
            // Start fallback polling
            setTimeout(pollNewMessages, 3000);
        };
    } else {
        setInterval(pollNewMessages, 3000);
    }
}

async function pollNewMessages() {
    if (!activeConvId) return;
    try {
        const res = await fetch(`${baseUrl}/api/chat/poll?conversation_id=${activeConvId}&last_id=${lastMessageId}`);
        const data = await res.json();
        if (data.success && data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                if (msg.id > lastMessageId) {
                    lastMessageId = msg.id;
                    appendIncomingMessage(msg);
                    playChatChime();
                }
            });
        }
    } catch(e) {}
}

function appendIncomingMessage(msg) {
    const container = document.getElementById('chatMessagesContainer');
    if (!container) return;

    // Check if element already exists
    if (msg.id && document.getElementById(`buyer-msg-${msg.id}`)) {
        return;
    }

    const isMe = (parseInt(msg.sender_user_id) === <?= (int)$buyer['id'] ?>);

    // If SYSTEM message
    if (msg.message_type === 'SYSTEM') {
        const sysDiv = document.createElement('div');
        sysDiv.id = `buyer-msg-${msg.id || Date.now()}`;
        sysDiv.className = 'flex justify-center my-2';
        sysDiv.innerHTML = `
            <div class="px-3.5 py-1 rounded-full bg-slate-200/80 border border-slate-300 text-slate-700 text-[11px] font-semibold text-center max-w-md shadow-xs">
                <span class="material-symbols-outlined text-[14px] align-middle text-blue-900 mr-1">info</span>
                ${escapeHtml(msg.message_body || '')}
                <span class="text-[9px] text-slate-500 ml-1">Just now</span>
            </div>
        `;
        container.appendChild(sysDiv);
        scrollChatToBottom();
        return;
    }

    // If OFFER or COUNTEROFFER message
    if (msg.message_type === 'OFFER') {
        const offerDiv = document.createElement('div');
        offerDiv.id = `buyer-msg-${msg.id || Date.now()}`;
        offerDiv.innerHTML = renderOfferCardHtml(msg, isMe);
        container.appendChild(offerDiv.firstElementChild);
        scrollChatToBottom();
        return;
    }

    const bubble = document.createElement('div');
    if (msg.id) bubble.id = `buyer-msg-${msg.id}`;
    bubble.className = `flex ${isMe ? 'justify-end' : 'justify-start'}`;

    let senderLabel = '';
    if (!isMe) {
        if (msg.is_on_behalf_of_supplier || msg.sender_role === 'SUPER_ADMIN') {
            senderLabel = '<div class="font-bold text-[10px] text-amber-700 mb-0.5 flex items-center gap-1"><span class="material-symbols-outlined text-[13px] text-amber-600">shield_person</span> Sports Junction Official Support</div>';
        } else {
            senderLabel = '<div class="font-bold text-[10px] text-teal-700 mb-0.5 flex items-center gap-1"><span class="material-symbols-outlined text-[13px] text-teal-600">verified</span> Supplier Partner</div>';
        }
    }

    const rawBody = msg.message_body || '';
    let meta = msg.metadata;
    if (typeof meta === 'string') {
        try { meta = JSON.parse(meta); } catch(e) { meta = {}; }
    }
    meta = meta || {};
    const hasAttach = !!msg.attachment_path || !!msg.attachment_id || !!meta.attachment_id || msg.message_type === 'ATTACHMENT';
    const isAutoAttachText = hasAttach && rawBody.startsWith('Sent an attachment:');
    const textHtml = (!isAutoAttachText && rawBody.trim()) 
        ? `<div class="leading-relaxed break-words whitespace-pre-wrap">${escapeHtml(rawBody)}</div>` 
        : '';
    const attachHtml = hasAttach ? renderBuyerAttachment(msg, isMe) : '';

    let innerContent = `
        <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-xs shadow-xs relative ${isMe ? 'bg-blue-900 text-white rounded-br-none' : 'bg-white border border-slate-200/90 text-slate-900 rounded-bl-none'}">
            ${senderLabel}
            ${textHtml}
            ${attachHtml}
            <div class="mt-1 flex items-center justify-end gap-1 text-[9px] ${isMe ? 'text-blue-200' : 'text-slate-400'}">
                <span>Just now</span>
                ${isMe ? '<span class="material-symbols-outlined text-[13px] text-teal-300">done_all</span>' : ''}
            </div>
        </div>
    `;

    bubble.innerHTML = innerContent;
    container.appendChild(bubble);
    scrollChatToBottom();
}

function renderOfferCardHtml(msg, isMe) {
    let meta = msg.metadata;
    if (typeof meta === 'string') {
        try { meta = JSON.parse(meta); } catch(e) { meta = {}; }
    }
    meta = meta || {};
    const offerId = meta.offer_id || 0;
    const leadTime = meta.lead_time || '';

    let actionsHtml = '';
    if (!isMe && offerId) {
        actionsHtml = `
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center gap-2" id="offer-actions-${offerId}">
                <button type="button" onclick="acceptOffer(${offerId})" class="flex-1 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1 shadow-xs">
                    <span class="material-symbols-outlined text-[15px]">done</span> Accept Deal
                </button>
                <button type="button" onclick="openOfferModal(${offerId})" class="flex-1 py-1.5 rounded-lg bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1 shadow-xs">
                    <span class="material-symbols-outlined text-[15px]">sync_alt</span> Counteroffer
                </button>
                <button type="button" onclick="rejectOffer(${offerId})" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                    Decline
                </button>
            </div>
        `;
    }

    return `
        <div class="flex ${isMe ? 'justify-end' : 'justify-start'} my-2" id="offer-card-${offerId}">
            <div class="max-w-md w-full rounded-2xl p-4 bg-white border-2 ${isMe ? 'border-blue-700 shadow-md' : 'border-emerald-600 shadow-md'}">
                <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined ${isMe ? 'text-blue-900' : 'text-emerald-700'} text-[20px]">request_quote</span>
                        <span class="font-headline font-bold text-xs text-slate-900">
                            ${isMe ? 'Your Offer Proposal' : 'Manufacturer Formal Offer'}
                        </span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800" id="offer-badge-${offerId}">Pending Review</span>
                </div>
                <div class="text-xs space-y-1.5 text-slate-700">
                    <p class="font-bold text-slate-900 text-sm">${escapeHtml(msg.message_body || '')}</p>
                    ${leadTime ? `<p class="text-[11px] text-slate-500">Estimated Lead Time: <span class="font-semibold text-slate-700">${escapeHtml(leadTime)}</span></p>` : ''}
                </div>
                ${actionsHtml}
                <div class="mt-2 text-right text-[9px] text-slate-400">
                    Just now
                </div>
            </div>
        </div>
    `;
}

function renderBuyerAttachment(msg, isMe) {
    let meta = msg.metadata;
    if (typeof meta === 'string') {
        try { meta = JSON.parse(meta); } catch(e) { meta = {}; }
    }
    meta = meta || {};
    const attachId = msg.attachment_id || meta.attachment_id || 0;
    let fileName = msg.attachment_name || '';
    let ext = (msg.attachment_type || '').toLowerCase();
    
    // Parse from message body if missing
    const rawBody = msg.message_body || '';
    if (!fileName) {
        const m = rawBody.match(/Sent an attachment:\s*(.*?)\s*\((.*?)\)/i);
        if (m) {
            fileName = m[1].trim();
            ext = (ext || m[2].trim()).toLowerCase();
        } else {
            fileName = 'Document';
        }
    }
    if (!ext && fileName.includes('.')) {
        ext = fileName.split('.').pop().toLowerCase();
    }

    const isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
    const isPdf = (ext === 'pdf');
    const fileUrl = msg.attachment_path ? `${baseUrl}${msg.attachment_path}` : (attachId ? `${baseUrl}/api/chat/attachments/${attachId}/download` : '#');
    const downloadUrl = attachId ? `${baseUrl}/api/chat/attachments/${attachId}/download` : fileUrl;
    const safeFileName = escapeHtml(fileName);
    const fileSize = msg.attachment_size ? `${Math.round(msg.attachment_size / 1024 * 10) / 10} KB` : '';

    if (isImg) {
        return `
            <div class="mt-2 rounded-xl overflow-hidden border ${isMe ? 'border-blue-800 bg-blue-950/40' : 'border-slate-200 bg-slate-100'} max-w-xs">
                <a href="${fileUrl}" target="_blank" rel="noopener noreferrer" class="block group relative">
                    <img src="${fileUrl}" alt="${fileName}" class="max-h-48 w-full object-cover group-hover:opacity-95 transition-opacity">
                    <div class="p-1.5 ${isMe ? 'bg-blue-950/80 text-white' : 'bg-white text-slate-800'} flex items-center justify-between text-[10px]">
                        <span class="truncate max-w-[150px] font-medium">${fileName}</span>
                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </div>
                </a>
                <div class="px-2 py-1 ${isMe ? 'bg-blue-900 text-blue-200 border-blue-800' : 'bg-slate-50 text-slate-600 border-slate-200'} flex items-center justify-between text-[10px] border-t">
                    <span>${fileSize}</span>
                    <a href="${downloadUrl}" class="font-bold flex items-center gap-1 hover:underline ${isMe ? 'text-white' : 'text-blue-900'}">
                        <span class="material-symbols-outlined text-[13px]">download</span> Download
                    </a>
                </div>
            </div>
        `;
    }

    if (isPdf) {
        return `
            <div class="mt-2 p-2.5 rounded-xl border ${isMe ? 'bg-blue-950/70 border-blue-800 text-white' : 'bg-rose-50/70 border-rose-200 text-slate-900'} max-w-xs flex items-center justify-between gap-2.5 shadow-2xs">
                <div class="flex items-center gap-2 overflow-hidden">
                    <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center flex-shrink-0 font-bold text-[9px] tracking-wider shadow-xs">
                        PDF
                    </div>
                    <div class="truncate text-xs">
                        <span class="font-bold block truncate text-[11px]">${fileName}</span>
                        <span class="text-[9px] opacity-75">${fileSize}</span>
                    </div>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <a href="${fileUrl}" target="_blank" rel="noopener noreferrer" class="px-2 py-1 rounded-lg bg-white border border-rose-200 text-rose-700 hover:bg-rose-100 font-semibold text-[10px] transition-colors flex items-center gap-0.5" title="Preview PDF in new tab">
                        <span class="material-symbols-outlined text-[13px]">visibility</span> View
                    </a>
                    <a href="${downloadUrl}" class="p-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[10px] transition-colors shadow-xs" title="Download PDF">
                        <span class="material-symbols-outlined text-[13px]">download</span>
                    </a>
                </div>
            </div>
        `;
    }

    return `
        <div class="mt-2.5 p-2 rounded-xl ${isMe ? 'bg-blue-950/60 text-white' : 'bg-slate-50 border border-slate-200 text-slate-800'} flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 overflow-hidden">
                <span class="material-symbols-outlined text-[20px] ${isMe ? 'text-blue-300' : 'text-blue-700'}">attach_file</span>
                <div class="truncate text-[11px]">
                    <span class="font-bold block truncate">${fileName}</span>
                    <span class="text-[9px] opacity-75">${ext.toUpperCase()} • ${fileSize}</span>
                </div>
            </div>
            <a href="${downloadUrl}" class="p-1.5 rounded-lg ${isMe ? 'bg-blue-800 hover:bg-blue-700 text-white' : 'bg-white border border-slate-200 hover:bg-slate-100 text-slate-700'} transition-colors" title="Download Document">
                <span class="material-symbols-outlined text-[16px]">download</span>
            </a>
        </div>
    `;
}

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Send Message Handler
async function handleSendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chatInputBody');
    const text = input.value.trim();
    if (!text) return;

    input.value = '';
    const alertBox = document.getElementById('contactPolicyAlert');
    alertBox.classList.add('hidden');

    try {
        const res = await fetch(`${baseUrl}/api/chat/messages`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                conversation_id: activeConvId,
                message_body: text,
                message_type: 'TEXT'
            })
        });
        const data = await res.json();
        if (data.success) {
            lastMessageId = data.message.id;
            appendIncomingMessage(data.message);

            if (data.has_restricted_contact && data.warning) {
                alertBox.classList.remove('hidden');
                document.getElementById('contactPolicyText').innerText = data.warning;
            }
        } else {
            alert(data.message || 'Failed to send message.');
        }
    } catch(err) {
        alert('Network connection error.');
    }
}

// Attachment File Upload
async function handleFileUpload(fileInput) {
    if (!fileInput.files || !fileInput.files[0]) return;
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('conversation_id', activeConvId);
    formData.append('attachment', file);

    try {
        const res = await fetch(`${baseUrl}/api/chat/attachments/upload`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            lastMessageId = data.message.id;
            appendIncomingMessage(data.message);
        } else {
            alert(data.message || 'Attachment upload failed.');
        }
    } catch(e) {
        alert('Upload error.');
    } finally {
        fileInput.value = '';
    }
}

// Quick templates
function insertTemplate(text) {
    const input = document.getElementById('chatInputBody');
    if (input) {
        input.value = text;
        input.focus();
    }
}

// Modals & Menu logic
function toggleContextMenu() {
    const menu = document.getElementById('chatContextMenu');
    menu.classList.toggle('hidden');
}

function openOfferModal(parentId = null) {
    const parentInput = document.getElementById('offerParentId');
    if (parentInput) parentInput.value = parentId || '';
    const title = document.getElementById('offerModalTitle');
    if (title) {
        title.innerText = parentId ? 'Submit Counteroffer' : 'Submit Formal Proposal';
    }
    calculateOfferTotal();
    const modal = document.getElementById('offerModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    }
}

function closeOfferModal() {
    const modal = document.getElementById('offerModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
}

function calculateOfferTotal() {
    const qty = parseFloat(document.getElementById('offerQty')?.value) || 0;
    const price = parseFloat(document.getElementById('offerUnitPrice')?.value) || 0;
    const total = qty * price;
    const totalDisplay = document.getElementById('offerTotalDisplay');
    if (totalDisplay) {
        totalDisplay.value = '$' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

function updateDealPipelineStep(step) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById(`deal-step-${i}`);
        if (!el) continue;
        const badge = el.querySelector('span:first-child');
        if (i <= step) {
            if (i === 4 && step >= 4) {
                el.className = 'flex items-center gap-2 text-emerald-700 font-bold';
                if (badge) badge.className = 'w-5 h-5 rounded-full flex items-center justify-center text-[10px] bg-emerald-600 text-white';
            } else {
                el.className = 'flex items-center gap-2 text-blue-900 font-bold';
                if (badge) badge.className = 'w-5 h-5 rounded-full flex items-center justify-center text-[10px] bg-blue-900 text-white';
            }
        } else {
            el.className = 'flex items-center gap-2 text-slate-400';
            if (badge) badge.className = 'w-5 h-5 rounded-full flex items-center justify-center text-[10px] bg-slate-200 text-slate-600';
        }
    }
}

async function handleSubmitOffer(e) {
    e.preventDefault();
    const form = document.getElementById('offerForm');
    const formData = new FormData(form);

    try {
        const res = await fetch(`${baseUrl}/api/chat/offers`, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });
        const data = await res.json();
        if (data.success) {
            closeOfferModal();
            if (data.message) {
                lastMessageId = data.message.id;
                appendIncomingMessage(data.message);
            }
            updateDealPipelineStep(3);
        } else {
            alert(data.message || 'Failed to submit offer.');
        }
    } catch(e) {
        alert('Submission error.');
    }
}

async function acceptOffer(offerId) {
    if (!confirm('Accept this deal? This creates an official purchase agreement with the manufacturer.')) return;
    try {
        const res = await fetch(`${baseUrl}/api/chat/offers/${offerId}/accept`, {method: 'POST'});
        const data = await res.json();
        if (data.success) {
            // Update offer card instantly in DOM without waiting for full page reload
            const badge = document.getElementById(`offer-badge-${offerId}`);
            if (badge) {
                badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1';
                badge.innerHTML = '<span class="material-symbols-outlined text-[13px]">check_circle</span> Deal Agreed & Accepted ✓';
            }
            const actions = document.getElementById(`offer-actions-${offerId}`);
            if (actions) {
                actions.className = 'mt-3 pt-2.5 border-t border-emerald-100 flex items-center justify-between';
                actions.innerHTML = `
                    <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">verified</span> Deal Officially Accepted & Locked
                    </span>
                    <span class="text-[10px] text-slate-500 font-medium">Ready for PO / Production</span>
                `;
            }
            updateDealPipelineStep(4);
            if (data.message) {
                appendIncomingMessage(data.message);
            }
        } else {
            alert(data.message || 'Could not accept offer.');
        }
    } catch(e) {
        alert('Action error.');
    }
}

async function rejectOffer(offerId) {
    const reason = prompt('Please specify a reason for declining this offer (optional):');
    if (reason === null) return;
    try {
        const res = await fetch(`${baseUrl}/api/chat/offers/${offerId}/reject`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({reason: reason})
        });
        const data = await res.json();
        if (data.success) {
            const badge = document.getElementById(`offer-badge-${offerId}`);
            if (badge) {
                badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300';
                badge.innerHTML = 'Declined';
            }
            const actions = document.getElementById(`offer-actions-${offerId}`);
            if (actions) {
                actions.remove();
            }
            if (data.message) {
                appendIncomingMessage(data.message);
            }
        } else {
            alert(data.message || 'Could not decline offer.');
        }
    } catch(e) {
        alert('Action error.');
    }
}

function openReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    }
    const menu = document.getElementById('chatContextMenu');
    if (menu) menu.classList.add('hidden');
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
}

async function handleSubmitReport(e) {
    e.preventDefault();
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);

    try {
        const res = await fetch(`${baseUrl}/api/chat/report`, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });
        const data = await res.json();
        if (data.success) {
            alert('Report submitted to Sports Junction Trust & Safety Team. Our administrators will review the communication history.');
            closeReportModal();
            form.reset();
        } else {
            alert(data.message || 'Error filing report.');
        }
    } catch(e) {
        alert('Report submission error.');
    }
}

function toggleContextMenu() {
    const menu = document.getElementById('chatContextMenu');
    if (menu) menu.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('chatContextMenu');
    const btn = e.target.closest('button[onclick*="toggleContextMenu"]');
    if (menu && !menu.classList.contains('hidden') && !menu.contains(e.target) && !btn) {
        menu.classList.add('hidden');
    }
});

async function toggleBlockUser(targetUserId, action) {
    const confirmMsg = (action === 'block') ? 'Block this supplier? You will no longer exchange messages with them.' : 'Unblock this supplier?';
    if (!confirm(confirmMsg)) return;

    try {
        const res = await fetch(`${baseUrl}/api/chat/block`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                target_user_id: targetUserId || 0,
                conversation_id: <?= $activeConvId ?>,
                action: action
            })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Block action failed.');
        }
    } catch(e) {
        alert('Network error.');
    }
}

function filterConversations(val) {
    const search = val.toLowerCase().trim();
    const items = document.querySelectorAll('.conv-card-item');
    items.forEach(item => {
        const sup = item.getAttribute('data-supplier') || '';
        const prod = item.getAttribute('data-product') || '';
        if (sup.includes(search) || prod.includes(search)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Start Stream
initRealtimeStream();
</script>
