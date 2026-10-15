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

<div class="flex flex-col h-[calc(100vh-100px)] space-y-2">
    <!-- Compact Header Toolbar (Zero Page Scroll) -->
    <div class="flex items-center justify-between py-1 px-1 flex-shrink-0">
        <div class="flex items-center gap-2.5">
            <h1 class="font-headline font-bold text-sm sm:text-base text-slate-900 tracking-tight flex items-center gap-1.5">
                <span class="material-symbols-outlined text-teal-700 text-[20px]">chat</span>
                <span>Buyer Messages & Deal Negotiation</span>
            </h1>
            <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-[10px] font-bold">
                <span class="w-1.5 h-1.5 rounded-full bg-teal-600 animate-pulse"></span> Trade Hub
            </span>
        </div>

        <div class="flex items-center gap-2">
            <!-- Audio Mute Switch -->
            <button type="button" id="btnSoundToggle" onclick="toggleChatSound()" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors" title="Toggle Sound Alerts">
                <span class="material-symbols-outlined text-[16px]" id="soundIcon">volume_up</span>
                <span id="soundLabel">Sound On</span>
            </button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- 3-Pane Messaging Workspace (Freelancer Style Dual/Triple Pane) -->
    <div class="flex-1 bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-0">
        
        <!-- PANE 1: CONVERSATIONS LIST & TABS (3 Cols) -->
        <div class="lg:col-span-3 border-r border-slate-200/80 flex flex-col bg-slate-50/50 h-full overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-3.5 border-b border-slate-200 bg-white space-y-2.5">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-[18px]">search</span>
                    <input type="text" id="convSearchInput" value="<?= htmlspecialchars($searchQuery ?? '') ?>" onkeyup="filterConversations(this.value)" placeholder="Search buyer or product..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-teal-600 bg-slate-50/80">
                </div>

                <!-- Specific Supplier Filter Tabs -->
                <div class="flex items-center gap-1 overflow-x-auto text-[11px] font-bold pb-0.5">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?tab=all" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'all') ? 'bg-teal-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">All</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?tab=unread" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'unread') ? 'bg-teal-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Unread</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?tab=awaiting_reply" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'awaiting_reply') ? 'bg-teal-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Awaiting Reply</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?tab=negotiations" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'negotiations') ? 'bg-teal-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Negotiations</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?tab=offers_pending" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'offers_pending') ? 'bg-teal-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Offers</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?tab=deals_accepted" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'deals_accepted') ? 'bg-teal-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Deals</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?tab=blocked" class="px-2.5 py-1 rounded-lg transition-colors <?= ($currentTab === 'blocked') ? 'bg-teal-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Blocked</a>
                </div>
            </div>

            <!-- Conversation Items -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100" id="convListContainer" style="overscroll-behavior: contain;">
                <?php if (empty($conversations)): ?>
                    <div class="p-8 text-center text-slate-400">
                        <span class="material-symbols-outlined text-[36px] text-slate-300 mb-2">inbox</span>
                        <p class="text-xs font-semibold">No buyer inquiries found</p>
                        <p class="text-[11px] text-slate-400 mt-1">Inquiries from verified buyers will appear here in real time.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                        <?php 
                            $isSelected = ((int)$c['id'] === $activeConvId);
                            $unread = (int) ($c['unread_count'] ?? 0);
                            $buyerName = trim(($c['buyer_first_name'] ?? '') . ' ' . ($c['buyer_last_name'] ?? '')) ?: 'Buyer';
                            $buyerCompany = $c['buyer_company'] ?? 'Verified Buyer';
                        ?>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages?conversation_id=<?= (int)$c['id'] ?>&tab=<?= htmlspecialchars($currentTab) ?>" class="block p-3.5 transition-all hover:bg-teal-50/50 <?= $isSelected ? 'bg-teal-50/90 border-l-4 border-teal-800' : '' ?> conv-card-item" data-buyer="<?= htmlspecialchars(strtolower($buyerName . ' ' . $buyerCompany)) ?>" data-product="<?= htmlspecialchars(strtolower($c['product_title'] ?? '')) ?>">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex items-center gap-1.5 overflow-hidden">
                                    <span class="font-bold text-xs text-slate-900 truncate"><?= htmlspecialchars($buyerCompany) ?></span>
                                    <span class="text-[10px] text-slate-500 truncate">(<?= htmlspecialchars($buyerName) ?>)</span>
                                </div>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap">
                                    <?= !empty($c['last_message_at']) ? date('d M, H:i', strtotime($c['last_message_at'])) : date('d M', strtotime($c['created_at'])) ?>
                                </span>
                            </div>

                            <?php if (!empty($c['product_title'])): ?>
                                <div class="text-[11px] font-semibold text-teal-900 truncate mb-1">
                                    <?= htmlspecialchars($c['product_title']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span class="truncate text-[11px] max-w-[170px]">
                                    <?= htmlspecialchars($c['last_message_body'] ?? 'Inquiry initiated.') ?>
                                </span>
                                <?php if ($unread > 0): ?>
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-teal-600 text-white font-bold text-[10px] min-w-[18px]">
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

                                <?php if (($c['last_sender_role'] ?? '') === 'BUYER'): ?>
                                    <span class="text-[9px] text-amber-700 font-semibold flex items-center gap-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Awaiting Reply</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- PANE 2: ACTIVE CHAT THREAD (6 Cols) -->
        <div class="lg:col-span-6 flex flex-col bg-slate-50/30 h-full overflow-hidden">
            <?php if (!$activeConversation): ?>
                <div class="flex-1 flex flex-col items-center justify-center p-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-[64px] text-slate-300 mb-3">quickreply</span>
                    <h3 class="font-headline font-bold text-lg text-slate-700">Select an Inquiry</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Select an active conversation to respond to buyer inquiries, issue custom pricing, and propose formal supply agreements.</p>
                </div>
            <?php else: ?>
                <!-- Active Header -->
                <div class="p-3.5 border-b border-slate-200 bg-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-teal-800 text-white font-bold flex items-center justify-center text-sm shadow-xs">
                            <?= strtoupper(substr($activeConversation['buyer_company'] ?? $activeConversation['buyer_first_name'] ?? 'B', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="font-headline font-bold text-sm text-slate-900"><?= htmlspecialchars($activeConversation['buyer_company'] ?: ($activeConversation['buyer_first_name'] . ' ' . $activeConversation['buyer_last_name'])) ?></h3>
                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 text-[10px] font-bold">Verified Buyer</span>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span>Contact: <?= htmlspecialchars($activeConversation['buyer_first_name'] . ' ' . $activeConversation['buyer_last_name']) ?></span>
                                <span>•</span>
                                <span>Ref #SJ-CONV-<?= (int)$activeConversation['id'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 relative">
                        <button type="button" onclick="openOfferModal()" class="px-3 py-1.5 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">add_circle</span>
                            <span class="hidden sm:inline">Create Formal Offer</span>
                        </button>
                        <button type="button" onclick="toggleContextMenu()" class="p-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition-colors" title="More Options">
                            <span class="material-symbols-outlined text-[20px]">more_vert</span>
                        </button>
                        <!-- Context Dropdown Menu -->
                        <div id="chatContextMenu" class="hidden absolute right-0 top-11 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-40 text-xs">
                            <button type="button" onclick="openReportModal()" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 text-slate-700 flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-600 text-[18px]">flag</span>
                                <span>Report Buyer</span>
                            </button>
                            <button type="button" onclick="toggleBlockUser(<?= (int)$activeConversation['buyer_id'] ?>, <?= $isBlockedByMe ? "'unblock'" : "'block'" ?>)" class="w-full text-left px-3.5 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">block</span>
                                <span><?= $isBlockedByMe ? 'Unblock Buyer' : 'Block Buyer' ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contact Policy Notice -->
                <div class="px-4 py-2 bg-amber-50 border-b border-amber-200 text-amber-900 text-[11px] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600 text-[16px]">security</span>
                        <span>Direct contact sharing restrictions are enforced to protect merchant deals and dispute resolution.</span>
                    </div>
                </div>

                <!-- Messages Stream -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3.5" id="chatMessagesContainer" style="overscroll-behavior: contain;">
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $isMe = ((int)$msg['sender_user_id'] === (int)$supplier['user_id'] || !empty($msg['is_on_behalf_of_supplier']));
                            $type = $msg['message_type'] ?? 'TEXT';
                        ?>
                        <?php if ($type === 'SYSTEM'): ?>
                            <!-- System Message -->
                            <div class="flex justify-center my-2">
                                <div class="px-3.5 py-1 rounded-full bg-slate-200/80 border border-slate-300 text-slate-700 text-[11px] font-semibold text-center max-w-md shadow-xs">
                                    <span class="material-symbols-outlined text-[14px] align-middle text-teal-800 mr-1">info</span>
                                    <?= htmlspecialchars($msg['message_body']) ?>
                                    <span class="text-[9px] text-slate-500 ml-1"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php elseif ($type === 'OFFER'): ?>
                            <!-- Structured Offer Card in Feed -->
                            <?php 
                                $offerMeta = !empty($msg['metadata']) ? (is_array($msg['metadata']) ? $msg['metadata'] : json_decode($msg['metadata'], true)) : [];
                                $currOfferId = (int)($offerMeta['offer_id'] ?? 0);
                                $currOffer = $offersById[$currOfferId] ?? null;
                                $offerStatus = $currOffer['status'] ?? 'PENDING';
                            ?>
                            <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?> my-2" id="offer-card-<?= $currOfferId ?>">
                                <div class="max-w-md w-full rounded-2xl p-4 bg-white border-2 <?= $isMe ? 'border-teal-700 shadow-md' : 'border-blue-700 shadow-md' ?>">
                                    <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined <?= $isMe ? 'text-teal-800' : 'text-blue-900' ?> text-[20px]">request_quote</span>
                                            <span class="font-headline font-bold text-xs text-slate-900">
                                                <?= $isMe ? 'Your Formal Quotation' : 'Buyer Proposed Offer' ?>
                                            </span>
                                        </div>
                                        <?php if ($offerStatus === 'ACCEPTED'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1" id="offer-badge-<?= $currOfferId ?>">
                                                <span class="material-symbols-outlined text-[13px]">check_circle</span> Deal Agreed & Accepted ✓
                                            </span>
                                        <?php elseif ($offerStatus === 'REJECTED'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300" id="offer-badge-<?= $currOfferId ?>">Declined</span>
                                        <?php elseif ($offerStatus === 'COUNTERED'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300" id="offer-badge-<?= $currOfferId ?>">Countered</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200" id="offer-badge-<?= $currOfferId ?>">Pending Review</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs space-y-1.5 text-slate-700">
                                        <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($msg['message_body']) ?></p>
                                        <?php if (!empty($offerMeta['lead_time'])): ?>
                                            <p class="text-[11px] text-slate-500">Lead Time: <span class="font-semibold text-slate-700"><?= htmlspecialchars($offerMeta['lead_time']) ?></span></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($offerStatus === 'ACCEPTED'): ?>
                                        <div class="mt-3 pt-2.5 border-t border-emerald-100 flex items-center justify-between" id="offer-actions-<?= $currOfferId ?>">
                                            <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[16px]">verified</span> Deal Officially Accepted & Locked
                                            </span>
                                            <span class="text-[10px] text-slate-500 font-medium">Ready for PO / Production</span>
                                        </div>
                                    <?php elseif (!$isMe && $currOfferId && $offerStatus === 'PENDING'): ?>
                                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center gap-2" id="offer-actions-<?= $currOfferId ?>">
                                            <button type="button" onclick="acceptOffer(<?= $currOfferId ?>)" class="flex-1 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1 shadow-xs">
                                                <span class="material-symbols-outlined text-[15px]">done</span> Accept Terms
                                            </button>
                                            <button type="button" onclick="openOfferModal(<?= $currOfferId ?>)" class="flex-1 py-1.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1 shadow-xs">
                                                <span class="material-symbols-outlined text-[15px]">sync_alt</span> Counteroffer
                                            </button>
                                            <button type="button" onclick="rejectOffer(<?= $currOfferId ?>)" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                                Decline
                                            </button>
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
                                    <div class="w-8 h-8 rounded-full bg-blue-900 text-white font-bold flex items-center justify-center text-xs flex-shrink-0 mt-0.5 shadow-2xs">
                                        <?= strtoupper(substr($activeConversation['buyer_company'] ?? $activeConversation['buyer_first_name'] ?? 'B', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-xs shadow-xs relative <?= $isMe ? 'bg-teal-800 text-white rounded-br-none' : 'bg-white border border-slate-200/90 text-slate-900 rounded-bl-none' ?>">
                                    <?php if (!$isMe): ?>
                                        <div class="font-bold text-[10px] text-blue-900 mb-0.5">
                                            <?= htmlspecialchars($activeConversation['buyer_company'] ?: $activeConversation['buyer_first_name']) ?>
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

                                    <!-- Attachment -->
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
                                            <div class="mt-2 rounded-xl overflow-hidden border <?= $isMe ? 'border-teal-700 bg-teal-950/40' : 'border-slate-200 bg-slate-100' ?> max-w-xs">
                                                <a href="<?= $fileUrl ?>" target="_blank" rel="noopener noreferrer" class="block group relative">
                                                    <img src="<?= $fileUrl ?>" alt="<?= $fileName ?>" class="max-h-48 w-full object-cover group-hover:opacity-95 transition-opacity">
                                                    <div class="p-1.5 <?= $isMe ? 'bg-teal-950/80 text-white' : 'bg-white text-slate-800' ?> flex items-center justify-between text-[10px]">
                                                        <span class="truncate max-w-[150px] font-medium"><?= $fileName ?></span>
                                                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                                    </div>
                                                </a>
                                                <div class="px-2 py-1 <?= $isMe ? 'bg-teal-900 text-teal-200 border-teal-800' : 'bg-slate-50 text-slate-600 border-slate-200' ?> flex items-center justify-between text-[10px] border-t">
                                                    <span><?= $fileSize ?></span>
                                                    <a href="<?= $downloadUrl ?>" class="font-bold flex items-center gap-1 hover:underline <?= $isMe ? 'text-white' : 'text-teal-900' ?>">
                                                        <span class="material-symbols-outlined text-[13px]">download</span> Download
                                                    </a>
                                                </div>
                                            </div>
                                        <?php elseif ($isPdf): ?>
                                            <div class="mt-2 p-2.5 rounded-xl border <?= $isMe ? 'bg-teal-950/70 border-teal-800 text-white' : 'bg-rose-50/70 border-rose-200 text-slate-900' ?> max-w-xs flex items-center justify-between gap-2.5 shadow-2xs">
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
                                            <div class="mt-2.5 p-2 rounded-xl <?= $isMe ? 'bg-teal-950/60 text-white' : 'bg-slate-50 border border-slate-200 text-slate-800' ?> flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2 overflow-hidden">
                                                    <span class="material-symbols-outlined text-[20px] <?= $isMe ? 'text-teal-300' : 'text-teal-700' ?>">attach_file</span>
                                                    <div class="truncate text-[11px]">
                                                        <span class="font-bold block truncate"><?= $fileName ?></span>
                                                        <span class="text-[9px] opacity-75"><?= strtoupper($ext) ?> • <?= $fileSize ?></span>
                                                    </div>
                                                </div>
                                                <a href="<?= $downloadUrl ?>" class="p-1.5 rounded-lg <?= $isMe ? 'bg-teal-700 hover:bg-teal-600 text-white' : 'bg-white border border-slate-200 hover:bg-slate-100 text-slate-700' ?> transition-colors" title="Download Document">
                                                    <span class="material-symbols-outlined text-[16px]">download</span>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Timestamp & Status -->
                                    <div class="mt-1 flex items-center justify-end gap-1 text-[9px] <?= $isMe ? 'text-teal-200' : 'text-slate-400' ?>">
                                        <span><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                        <?php if ($isMe): ?>
                                            <?php if ($msg['delivery_status'] === 'READ'): ?>
                                                <span class="material-symbols-outlined text-[14px] text-teal-300" title="Read">done_all</span>
                                            <?php elseif ($msg['delivery_status'] === 'DELIVERED'): ?>
                                                <span class="material-symbols-outlined text-[14px] text-teal-200" title="Delivered">done_all</span>
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-[14px] text-teal-300" title="Sent">done</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Quick Response Chips -->
                <div class="px-3 py-1.5 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto text-[11px] text-slate-600">
                    <span class="font-bold text-[10px] uppercase text-slate-400 mr-1">Quick:</span>
                    <button type="button" onclick="insertTemplate('Yes, we have full factory production capacity available.')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-teal-50 hover:text-teal-900 transition-colors whitespace-nowrap">Capacity Available</button>
                    <button type="button" onclick="insertTemplate('We can dispatch sample piece in 3-5 business days.')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-teal-50 hover:text-teal-900 transition-colors whitespace-nowrap">Sample in 3-5 Days</button>
                    <button type="button" onclick="insertTemplate('Please review the attached technical catalog and spec sheet.')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-teal-50 hover:text-teal-900 transition-colors whitespace-nowrap">Catalog Attached</button>
                    <button type="button" onclick="insertTemplate('We support full custom laser engraving and custom cartons.')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-teal-50 hover:text-teal-900 transition-colors whitespace-nowrap">OEM Supported</button>
                </div>

                <!-- Composer -->
                <?php if ($isBlockedByMe): ?>
                    <div class="p-4 bg-rose-50 border-t border-rose-200 text-center space-y-2">
                        <div class="flex items-center justify-center gap-1.5 text-rose-800 text-xs font-bold">
                            <span class="material-symbols-outlined text-[18px]">block</span>
                            <span>You have blocked this buyer. Communication is paused.</span>
                        </div>
                        <button type="button" onclick="toggleBlockUser(<?= (int)$activeConversation['buyer_id'] ?>, 'unblock')" class="px-4 py-1.5 rounded-xl bg-rose-700 hover:bg-rose-800 text-white font-bold text-xs transition-colors shadow-xs">
                            Unblock Buyer to Resume Chat
                        </button>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-white border-t border-slate-200">
                        <form id="chatMessageForm" onsubmit="handleSendMessage(event)" class="space-y-2">
                            <input type="hidden" name="conversation_id" value="<?= $activeConvId ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

                            <!-- Policy alert -->
                            <div id="contactPolicyAlert" class="hidden p-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-600 text-[18px]">warning</span>
                                <span id="contactPolicyText"></span>
                            </div>

                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-full px-3 py-1.5 focus-within:border-teal-700 focus-within:bg-white transition-all shadow-2xs">
                                <!-- Attachment Upload Button -->
                                <label class="p-1.5 rounded-full hover:bg-slate-200/80 text-slate-500 hover:text-teal-800 cursor-pointer transition-colors" title="Attach Catalog or Spec (Max 10MB)">
                                    <span class="material-symbols-outlined text-[20px]">attach_file</span>
                                    <input type="file" id="chatAttachmentInput" onchange="handleFileUpload(this)" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                </label>

                                <!-- Text Input -->
                                <input type="text" id="chatInputBody" placeholder="Type your reply, manufacturing terms, or dispatch updates..." class="flex-1 bg-transparent border-none text-xs text-slate-800 placeholder-slate-400 focus:outline-none">

                                <!-- Send Button -->
                                <button type="submit" id="btnSendMessage" class="w-8 h-8 rounded-full bg-teal-800 hover:bg-teal-900 text-white flex items-center justify-center shadow-xs transition-colors flex-shrink-0" title="Send Message">
                                    <span class="material-symbols-outlined text-[16px]">send</span>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- PANE 3: BUYER & PRODUCT DETAILS (3 Cols) -->
        <div class="lg:col-span-3 border-l border-slate-200/80 p-4 bg-slate-50/50 space-y-4 h-full overflow-y-auto" style="overscroll-behavior: contain;">
            <?php if ($activeConversation): ?>
                <!-- Buyer Info Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-900 block mb-1">Buyer Details</span>
                    <h4 class="font-headline font-bold text-sm text-slate-900"><?= htmlspecialchars($activeConversation['buyer_company'] ?: 'Individual Buyer') ?></h4>
                    <p class="text-xs text-slate-500 mt-0.5">Contact: <?= htmlspecialchars($activeConversation['buyer_first_name'] . ' ' . $activeConversation['buyer_last_name']) ?></p>
                    <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-600">
                        <span class="flex items-center gap-1 text-emerald-700 font-semibold">
                            <span class="material-symbols-outlined text-[14px]">verified</span>
                            <span>Verified B2B Account</span>
                        </span>
                    </div>
                </div>

                <!-- Product Card -->
                <?php if (!empty($activeConversation['product_title'])): ?>
                    <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-xs space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Catalog Item</span>
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
                                <div class="text-[11px] text-teal-800 font-bold mt-0.5">₹<?= number_format((float)($activeConversation['product_price'] ?? 0), 2) ?></div>
                                <div class="text-[10px] text-slate-500">MOQ: <?= (int)($activeConversation['product_moq'] ?? 1) ?> units</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Deal Stage Progression Tracker -->
                <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-xs" id="dealProgressionTracker">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-3">Deal Progression</span>
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
                    <div class="space-y-3 text-xs">
                        <div id="deal-step-1" class="flex items-center gap-2 <?= $curStep >= 1 ? 'text-teal-900 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 1 ? 'bg-teal-800 text-white' : 'bg-slate-200 text-slate-600' ?>">1</span>
                            <span>Inquiry Initiated</span>
                        </div>
                        <div id="deal-step-2" class="flex items-center gap-2 <?= $curStep >= 2 ? 'text-teal-900 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 2 ? 'bg-teal-800 text-white' : 'bg-slate-200 text-slate-600' ?>">2</span>
                            <span>Price Negotiation</span>
                        </div>
                        <div id="deal-step-3" class="flex items-center gap-2 <?= $curStep >= 3 ? 'text-teal-900 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 3 ? 'bg-teal-800 text-white' : 'bg-slate-200 text-slate-600' ?>">3</span>
                            <span>Formal Offer Issued</span>
                        </div>
                        <div id="deal-step-4" class="flex items-center gap-2 <?= $curStep >= 4 ? 'text-emerald-700 font-bold' : 'text-slate-400' ?>">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] <?= $curStep >= 4 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' ?>">4</span>
                            <span>Deal Agreed</span>
                        </div>
                    </div>
                </div>

                <!-- Safety Actions -->
                <div class="pt-2 space-y-2">
                    <button type="button" onclick="openOfferModal()" class="w-full py-2.5 rounded-xl bg-teal-800 hover:bg-teal-900 text-white text-xs font-bold transition-colors flex items-center justify-center gap-1.5 shadow-xs">
                        <span class="material-symbols-outlined text-[16px]">add_circle</span>
                        <span>Create Formal Offer</span>
                    </button>
                    <button type="button" onclick="openReportModal()" class="w-full py-2 rounded-xl border border-amber-300 bg-amber-50/50 hover:bg-amber-100 text-amber-800 text-xs font-bold transition-colors flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">flag</span>
                        <span>Report Buyer</span>
                    </button>
                    <button type="button" onclick="toggleBlockUser(<?= (int)$activeConversation['buyer_id'] ?>, <?= $isBlockedByMe ? "'unblock'" : "'block'" ?>)" class="w-full py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">block</span>
                        <span><?= $isBlockedByMe ? 'Unblock Buyer' : 'Block Buyer' ?></span>
                    </button>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-slate-400 text-xs">
                    Select an inquiry to view buyer verification status and issue formal quotations.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- MODAL 1: FORMAL OFFER CREATION MODAL -->
<div id="offerModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-teal-800 text-[24px]">handshake</span>
                <h3 class="font-headline font-bold text-base text-slate-900" id="offerModalTitle">Issue Formal Offer</h3>
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
                    <label class="block font-bold text-slate-700 mb-1">Quantity Offered *</label>
                    <input type="number" id="offerQty" name="quantity" min="1" value="<?= (int)($activeConversation['product_moq'] ?? 100) ?>" oninput="calculateOfferTotal()" required class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Unit Type</label>
                    <input type="text" name="unit" value="Pieces" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Wholesale Unit Price (₹) *</label>
                    <input type="number" id="offerUnitPrice" name="unit_price" step="0.01" min="1" value="<?= (float)($activeConversation['product_price'] ?? 500) ?>" oninput="calculateOfferTotal()" required class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800 font-bold text-teal-800">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Total Valuation (₹)</label>
                    <input type="text" id="offerTotalDisplay" readonly class="w-full px-3 py-2 rounded-xl border border-slate-100 bg-slate-50 font-extrabold text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Delivery Destination / Terms</label>
                    <input type="text" name="delivery_location" placeholder="e.g. Ex-Factory / FOB Meerut" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Manufacturing Lead Time</label>
                    <input type="text" name="lead_time" placeholder="e.g. 10-14 Days" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Included Customizations / Packaging</label>
                <textarea name="customization_terms" rows="2" placeholder="Custom logo printing included, master carton 50pcs..." class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeOfferModal()" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-bold transition-colors">
                    Issue Formal Offer
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
                <h3 class="font-headline font-bold text-base text-slate-900">Report Buyer to Super Admin</h3>
            </div>
            <button type="button" onclick="closeReportModal()" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form id="reportForm" onsubmit="handleSubmitReport(event)" class="space-y-3.5 text-xs">
            <input type="hidden" name="conversation_id" value="<?= $activeConvId ?>">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Reason *</label>
                <select name="reason" required class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800">
                    <option value="">Select a reason...</option>
                    <option value="Off-platform payment or contact solicitation">Buyer requesting off-platform contact/payment</option>
                    <option value="Unreasonable payment terms or fraudulent purchase intent">Fraudulent intent / Fake buyer inquiries</option>
                    <option value="Abusive or inappropriate communication">Abusive communication</option>
                    <option value="Spam">Spam or irrelevant solicitation</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Context for Super Admin audit..." class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-teal-800"></textarea>
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

<!-- CLIENT JAVASCRIPT -->
<script>
const activeConvId = <?= $activeConvId ?>;
const baseUrl = <?= json_encode($baseUrl) ?>;
let lastMessageId = <?= !empty($messages) ? (int) end($messages)['id'] : 0 ?>;
let soundEnabled = (localStorage.getItem('sj_chat_sound') !== 'muted');

// Sound Synthesizer
function playChatChime() {
    if (!soundEnabled) return;
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.15);
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

function scrollChatToBottom() {
    const c = document.getElementById('chatMessagesContainer');
    if (c) c.scrollTop = c.scrollHeight;
}
scrollChatToBottom();

// SSE Stream
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

    if (msg.id && document.getElementById(`supplier-msg-${msg.id}`)) return;

    const isMe = (msg.sender_role === 'SUPPLIER');

    if (msg.message_type === 'SYSTEM') {
        const sysDiv = document.createElement('div');
        sysDiv.id = `supplier-msg-${msg.id || Date.now()}`;
        sysDiv.className = 'flex justify-center my-2';
        sysDiv.innerHTML = `
            <div class="px-3.5 py-1 rounded-full bg-slate-200/80 border border-slate-300 text-slate-700 text-[11px] font-semibold text-center max-w-md shadow-xs">
                <span class="material-symbols-outlined text-[14px] align-middle text-teal-800 mr-1">info</span>
                ${escapeHtml(msg.message_body || '')}
                <span class="text-[9px] text-slate-500 ml-1">Just now</span>
            </div>
        `;
        container.appendChild(sysDiv);
        scrollChatToBottom();
        return;
    }

    if (msg.message_type === 'OFFER') {
        const offerDiv = document.createElement('div');
        offerDiv.id = `supplier-msg-${msg.id || Date.now()}`;
        offerDiv.innerHTML = renderOfferCardHtml(msg, isMe);
        container.appendChild(offerDiv.firstElementChild);
        scrollChatToBottom();
        return;
    }

    const bubble = document.createElement('div');
    if (msg.id) bubble.id = `supplier-msg-${msg.id}`;
    bubble.className = `flex ${isMe ? 'justify-end' : 'justify-start'}`;

    let senderLabel = '';
    if (!isMe) {
        if (msg.is_on_behalf_of_supplier || msg.sender_role === 'SUPER_ADMIN') {
            senderLabel = '<div class="font-bold text-[10px] text-amber-700 mb-0.5 flex items-center gap-1"><span class="material-symbols-outlined text-[13px] text-amber-600">shield_person</span> Sports Junction Support</div>';
        } else {
            senderLabel = '<div class="font-bold text-[10px] text-blue-900 mb-0.5 flex items-center gap-1"><span class="material-symbols-outlined text-[13px] text-blue-600">person</span> Buyer</div>';
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
    const attachHtml = hasAttach ? renderSupplierAttachment(msg, isMe) : '';

    let innerContent = `
        <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-xs shadow-xs relative ${isMe ? 'bg-teal-800 text-white rounded-br-none' : 'bg-white border border-slate-200/90 text-slate-900 rounded-bl-none'}">
            ${senderLabel}
            ${textHtml}
            ${attachHtml}
            <div class="mt-1 flex items-center justify-end gap-1 text-[9px] ${isMe ? 'text-teal-200' : 'text-slate-400'}">
                <span>Just now</span>
                ${isMe ? '<span class="material-symbols-outlined text-[13px] text-teal-300">done_all</span>' : ''}
            </div>
        </div>
    `;

    bubble.innerHTML = innerContent;
    container.appendChild(bubble);
    scrollChatToBottom();
}

function renderSupplierAttachment(msg, isMe) {
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
            <div class="mt-2 rounded-xl overflow-hidden border ${isMe ? 'border-teal-700 bg-teal-950/40' : 'border-slate-200 bg-slate-100'} max-w-xs">
                <a href="${fileUrl}" target="_blank" rel="noopener noreferrer" class="block group relative">
                    <img src="${fileUrl}" alt="${fileName}" class="max-h-48 w-full object-cover group-hover:opacity-95 transition-opacity">
                    <div class="p-1.5 ${isMe ? 'bg-teal-950/80 text-white' : 'bg-white text-slate-800'} flex items-center justify-between text-[10px]">
                        <span class="truncate max-w-[150px] font-medium">${fileName}</span>
                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </div>
                </a>
                <div class="px-2 py-1 ${isMe ? 'bg-teal-900 text-teal-200 border-teal-800' : 'bg-slate-50 text-slate-600 border-slate-200'} flex items-center justify-between text-[10px] border-t">
                    <span>${fileSize}</span>
                    <a href="${downloadUrl}" class="font-bold flex items-center gap-1 hover:underline ${isMe ? 'text-white' : 'text-teal-900'}">
                        <span class="material-symbols-outlined text-[13px]">download</span> Download
                    </a>
                </div>
            </div>
        `;
    }

    if (isPdf) {
        return `
            <div class="mt-2 p-2.5 rounded-xl border ${isMe ? 'bg-teal-950/70 border-teal-800 text-white' : 'bg-rose-50/70 border-rose-200 text-slate-900'} max-w-xs flex items-center justify-between gap-2.5 shadow-2xs">
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
        <div class="mt-2.5 p-2 rounded-xl ${isMe ? 'bg-teal-950/60 text-white' : 'bg-slate-50 border border-slate-200 text-slate-800'} flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 overflow-hidden">
                <span class="material-symbols-outlined text-[20px] ${isMe ? 'text-teal-300' : 'text-teal-700'}">attach_file</span>
                <div class="truncate text-[11px]">
                    <span class="font-bold block truncate">${fileName}</span>
                    <span class="text-[9px] opacity-75">${ext.toUpperCase()} • ${fileSize}</span>
                </div>
            </div>
            <a href="${downloadUrl}" class="p-1.5 rounded-lg ${isMe ? 'bg-teal-700 hover:bg-teal-600 text-white' : 'bg-white border border-slate-200 hover:bg-slate-100 text-slate-700'} transition-colors" title="Download Document">
                <span class="material-symbols-outlined text-[16px]">download</span>
            </a>
        </div>
    `;
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
                    <span class="material-symbols-outlined text-[15px]">done</span> Accept Terms
                </button>
                <button type="button" onclick="openOfferModal(${offerId})" class="flex-1 py-1.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1 shadow-xs">
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
            <div class="max-w-md w-full rounded-2xl p-4 bg-white border-2 ${isMe ? 'border-teal-700 shadow-md' : 'border-blue-700 shadow-md'}">
                <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined ${isMe ? 'text-teal-800' : 'text-blue-900'} text-[20px]">request_quote</span>
                        <span class="font-headline font-bold text-xs text-slate-900">
                            ${isMe ? 'Your Formal Quotation' : 'Buyer Proposed Offer'}
                        </span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200" id="offer-badge-${offerId}">Pending Review</span>
                </div>
                <div class="text-xs space-y-1.5 text-slate-700">
                    <p class="font-bold text-slate-900 text-sm">${escapeHtml(msg.message_body || '')}</p>
                    ${leadTime ? `<p class="text-[11px] text-slate-500">Lead Time: <span class="font-semibold text-slate-700">${escapeHtml(leadTime)}</span></p>` : ''}
                </div>
                ${actionsHtml}
                <div class="mt-2 text-right text-[9px] text-slate-400">
                    Just now
                </div>
            </div>
        </div>
    `;
}

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

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

function insertTemplate(text) {
    const input = document.getElementById('chatInputBody');
    if (input) {
        input.value = text;
        input.focus();
    }
}

function toggleContextMenu() {
    const menu = document.getElementById('chatContextMenu');
    if (menu) menu.classList.toggle('hidden');
}

function openOfferModal(parentId = null) {
    const parentInput = document.getElementById('offerParentId');
    if (parentInput) parentInput.value = parentId || '';
    const title = document.getElementById('offerModalTitle');
    if (title) {
        title.innerText = parentId ? 'Submit Counteroffer' : 'Issue Formal Offer';
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
        totalDisplay.value = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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
                el.className = 'flex items-center gap-2 text-teal-900 font-bold';
                if (badge) badge.className = 'w-5 h-5 rounded-full flex items-center justify-center text-[10px] bg-teal-800 text-white';
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
            alert(data.message || 'Failed to issue offer.');
        }
    } catch(e) {
        alert('Submission error.');
    }
}

async function acceptOffer(offerId) {
    if (!confirm('Accept this buyer offer? This confirms manufacturing production terms.')) return;
    try {
        const res = await fetch(`${baseUrl}/api/chat/offers/${offerId}/accept`, {method: 'POST'});
        const data = await res.json();
        if (data.success) {
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
    const reason = prompt('Please specify a reason for declining:');
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
            alert('Report submitted to Super Admin safety desk. Our administrators will review the communication history.');
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
    const confirmMsg = (action === 'block') ? 'Are you sure you want to block this buyer?' : 'Are you sure you want to unblock this buyer?';
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
        const buyer = item.getAttribute('data-buyer') || '';
        const prod = item.getAttribute('data-product') || '';
        if (buyer.includes(search) || prod.includes(search)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

initRealtimeStream();
</script>
