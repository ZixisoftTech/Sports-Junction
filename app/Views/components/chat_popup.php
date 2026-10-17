<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$authUser = Session::get('auth_user');
$isLoggedIn = !empty($authUser);
$userRole = strtoupper($authUser['role'] ?? '');
$currentUserId = (int) ($authUser['id'] ?? 0);
$currentSupplierId = \App\Models\Supplier::currentSupplierId();
?>

<!-- Floating Sticky Chat Widget (Fixed Bottom-Right) -->
<div id="sjFloatingChatWidget" class="fixed bottom-5 right-5 z-50 font-sans" style="<?= ($isLoggedIn && $userRole === 'BUYER') ? 'display: block;' : 'display: none;' ?>">
    
    <!-- MINIMIZED LAUNCHER BUTTON -->
    <button type="button" id="chatLauncherBtn" onclick="toggleFloatingChat()" class="px-4 py-3 rounded-full bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-xs shadow-2xl flex items-center gap-2.5 transition-all transform hover:scale-105 border-2 border-white/20">
        <span class="material-symbols-outlined text-[20px]">chat</span>
        <span id="chatLauncherLabel"><?= ($isLoggedIn && $userRole === 'BUYER') ? 'Supplier Messages' : 'Chat with Factory' ?></span>
        <span id="chatLauncherBadge" class="hidden px-2 py-0.5 rounded-full bg-amber-500 text-slate-900 font-extrabold text-[10px]">0</span>
    </button>

    <!-- EXPANDED CHAT WINDOW (MODERN DUAL PANE, STICKY, INDEPENDENT SCROLLING) -->
    <div id="chatExpandedWindow" class="hidden w-[780px] max-w-[calc(100vw-24px)] h-[590px] max-h-[calc(100vh-70px)] bg-white rounded-2xl shadow-2xl border border-slate-200/90 flex flex-col overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-200">
        
        <!-- TOP MASTER HEADER -->
        <div class="px-4 py-2.5 bg-blue-900 text-white flex items-center justify-between shadow-xs select-none">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 text-white shadow-2xs">
                    <span class="material-symbols-outlined text-[18px]">forum</span>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-headline font-bold text-xs tracking-wide">Sports Junction Messenger</span>
                        <span class="px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 text-[9px] font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Live
                        </span>
                    </div>
                    <div class="text-[10px] text-blue-200 truncate">Verified B2B Direct Factory Communication</div>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button type="button" id="popupToggleThreadsBtn" onclick="toggleThreadsPaneDesktop()" class="p-1.5 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors" title="Toggle Threads Sidebar">
                    <span class="material-symbols-outlined text-[18px]">view_sidebar</span>
                </button>
                <a id="popupFullScreenLink" href="<?= $baseUrl ?>/buyer/messages" class="p-1.5 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors" title="Open Fullscreen Messages">
                    <span class="material-symbols-outlined text-[18px]">open_in_full</span>
                </a>
                <button type="button" onclick="minimizeFloatingChat()" class="p-1.5 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors" title="Minimize">
                    <span class="material-symbols-outlined text-[18px]">remove</span>
                </button>
                <button type="button" onclick="closeFloatingChat()" class="p-1.5 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors" title="Close">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </div>

        <!-- MAIN SPLIT BODY -->
        <div class="flex-1 flex overflow-hidden relative">
            
            <?php if (!$isLoggedIn): ?>
                <!-- Not Logged In State -->
                <div class="flex-1 p-8 flex flex-col items-center justify-center text-center space-y-3 bg-slate-50/50">
                    <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-900 flex items-center justify-center mb-1 shadow-xs">
                        <span class="material-symbols-outlined text-[32px]">lock</span>
                    </div>
                    <h5 class="font-headline font-bold text-base text-slate-900">Sign In to Chat with Factory</h5>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-sm">
                        Direct B2B negotiation, wholesale pricing inquiries, and official contract sharing are restricted to verified accounts.
                    </p>
                    <div class="pt-3 w-full max-w-xs space-y-2">
                        <a id="popupLoginLink" href="<?= $baseUrl ?>/login" class="w-full py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs block transition-colors shadow-xs text-center">
                            Sign In / Register as Buyer
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <!-- PANE 1: THREADS / CONVERSATIONS LIST (LEFT SIDE, INDEPENDENT SCROLL) -->
                <div id="popupThreadsPane" class="w-full md:w-[280px] bg-slate-50 border-r border-slate-200/90 flex flex-col h-full overflow-hidden flex-shrink-0 transition-all duration-200" style="overscroll-behavior: contain;">
                    <!-- Threads Search & Filter Bar -->
                    <div class="p-2.5 border-b border-slate-200 bg-white">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-2.5 top-2 text-slate-400 text-[16px]">search</span>
                            <input type="text" id="popupThreadsSearchInput" oninput="filterPopupThreads()" placeholder="Search suppliers or products..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-blue-900">
                        </div>
                    </div>

                    <!-- Threads Scrollable List (Cursor on this scrolls left independently) -->
                    <div id="popupThreadsList" class="flex-1 overflow-y-auto divide-y divide-slate-100 p-1.5 space-y-1" style="overscroll-behavior: contain;">
                        <div class="p-6 text-center text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-[24px] animate-spin mb-1 text-blue-900">progress_activity</span>
                            <p>Loading conversations...</p>
                        </div>
                    </div>
                </div>

                <!-- PANE 2: ACTIVE CHAT THREAD (RIGHT SIDE, INDEPENDENT SCROLL) -->
                <div id="popupChatPane" class="flex-1 flex flex-col h-full bg-white overflow-hidden relative">
                    
                    <!-- Chat Sub-Header: Active Supplier & Controls -->
                    <div class="px-3.5 py-2.5 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <!-- Mobile back to threads button -->
                            <button type="button" onclick="showThreadsMobile()" class="md:hidden p-1 rounded-lg hover:bg-slate-200 text-slate-700 transition-colors" title="Back to conversations">
                                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                            </button>

                            <div class="w-8 h-8 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs flex-shrink-0" id="popupSupplierAvatar">
                                S
                            </div>
                            <div class="truncate">
                                <div class="flex items-center gap-1">
                                    <span class="font-headline font-bold text-xs text-slate-900 truncate" id="popupSupplierName">Supplier Partner</span>
                                    <span class="material-symbols-outlined text-teal-600 text-[14px]" title="Verified Indian Factory">verified</span>
                                </div>
                                <div class="text-[10px] text-slate-500 truncate" id="popupProductSub">Sports Manufacturing Direct</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span id="popupChatStatusBadge">Direct SLA</span>
                            </span>
                        </div>
                    </div>

                    <!-- Product Mini Context Card (Shown if tied to a product) -->
                    <div id="popupProductCard" class="px-3 py-2 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between gap-2.5 flex-shrink-0">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <img id="popupProductImg" src="" alt="" class="w-9 h-9 object-cover rounded-lg border border-slate-200 flex-shrink-0">
                            <div class="overflow-hidden">
                                <h6 id="popupProductTitle" class="font-bold text-xs text-slate-900 truncate max-w-[240px]">Product Inquiry</h6>
                                <div class="flex items-center gap-2 text-[10px] text-slate-500">
                                    <span id="popupProductPrice" class="font-bold text-blue-900">$0.00</span>
                                    <span>•</span>
                                    <span id="popupProductMoq">MOQ: 1 unit</span>
                                </div>
                            </div>
                        </div>
                        <a id="popupProductLink" href="#" target="_blank" class="px-2 py-1 rounded bg-white border border-slate-200 hover:bg-slate-100 text-[10px] font-semibold text-slate-700 transition-colors flex items-center gap-0.5 whitespace-nowrap">
                            <span>View Specs</span>
                            <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                        </a>
                    </div>

                    <!-- Message Feed (Cursor on this scrolls messages independently) -->
                    <div id="popupMessagesContainer" class="flex-1 p-3.5 overflow-y-auto space-y-3 bg-slate-50/40" style="overscroll-behavior: contain;">
                        <!-- Messages dynamically rendered here -->
                    </div>

                    <!-- Quick Response Chips -->
                    <div class="px-3 py-1.5 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto text-[10px] text-slate-600 flex-shrink-0">
                        <span class="font-bold uppercase text-slate-400 text-[9px] mr-0.5">Quick:</span>
                        <button type="button" onclick="popupInsertTemplate('Inquire about MOQ and bulk wholesale pricing')" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">MOQ Inquiry</button>
                        <button type="button" onclick="popupInsertTemplate('Can you provide an official production sample?')" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">Request Sample</button>
                        <button type="button" onclick="popupInsertTemplate('What are your delivery lead times for 500+ units?')" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">Lead Time</button>
                        <button type="button" onclick="popupInsertTemplate('Do you provide custom logo branding and export packaging?')" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-900 transition-colors whitespace-nowrap">Custom Branding</button>
                    </div>

                    <!-- Composer Form -->
                    <div class="p-2.5 bg-white border-t border-slate-200 flex-shrink-0">
                        <!-- Warning alert if off-platform contact entered -->
                        <div id="popupWarningAlert" class="hidden mb-2 p-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[10px] flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-amber-600 text-[14px]">warning</span>
                            <span id="popupWarningText">Policy restriction applied.</span>
                        </div>

                        <form id="popupChatForm" onsubmit="handlePopupSend(event)" class="flex items-center gap-1.5">
                            <!-- Attachment Upload (PDF, PNG, JPG) -->
                            <label class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 hover:text-blue-900 cursor-pointer transition-colors relative flex-shrink-0" title="Attach Document or Image (PDF, JPG, PNG, WEBP max 10MB)">
                                <span class="material-symbols-outlined text-[18px]">attach_file</span>
                                <input type="file" id="popupAttachInput" onchange="handlePopupFileUpload(this)" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            </label>

                            <input type="text" id="popupInputText" placeholder="Ask question, negotiate price or quantity..." autocomplete="off" class="flex-1 px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50/50">

                            <button type="submit" id="popupSendBtn" class="p-2 rounded-xl bg-blue-900 hover:bg-blue-950 text-white transition-colors flex-shrink-0 shadow-xs" title="Send Message">
                                <span class="material-symbols-outlined text-[18px]">send</span>
                            </button>
                        </form>
                    </div>

                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
let currentPopupProduct = null;
let currentPopupConversationId = null;
let popupLastMessageId = 0;
let popupSse = null;
let popupPollInterval = null;
let popupThreadsListCache = [];
let popupOffersMap = {};
let isChatWindowOpen = false;

const popupBaseUrl = <?= json_encode($baseUrl) ?>;
const isUserLoggedIn = <?= json_encode($isLoggedIn) ?>;
const currentUserId = <?= json_encode($currentUserId) ?>;
const currentUserRole = <?= json_encode($userRole) ?>;
const currentSupplierId = <?= json_encode($currentSupplierId) ?>;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (isUserLoggedIn && currentUserRole === 'BUYER') {
        loadPopupThreads(false);
        // Refresh unread counts every 10 seconds for real-time badge updates
        setInterval(() => loadPopupThreads(false), 10000);
    }
});

// Open or initialize chat popup for a product
function openProductChat(productData) {
    if (currentUserRole === 'SUPPLIER' && currentSupplierId > 0 && productData) {
        const prodSupplierId = parseInt(productData.supplier_id || productData.supplierId || 0);
        if (prodSupplierId > 0 && prodSupplierId === currentSupplierId) {
            alert('You cannot start a chat about your own product.');
            return;
        }
    }
    currentPopupProduct = productData;
    const widget = document.getElementById('sjFloatingChatWidget');
    if (widget) widget.style.display = 'block';

    if (!isUserLoggedIn) {
        const loginLink = document.getElementById('popupLoginLink');
        if (loginLink) {
            loginLink.href = `${popupBaseUrl}/login?redirect=${encodeURIComponent(window.location.href)}`;
        }
        maximizeFloatingChat();
        return;
    }

    maximizeFloatingChat();
    startOrLoadConversation(productData);
}

// Start or load conversation for a product
async function startOrLoadConversation(productData) {
    updateProductCardUI(productData);
    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/conversations/start`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                supplier_id: productData.supplier_id,
                product_id: productData.id || productData.product_id || '',
                initial_message: ''
            })
        });
        const data = await res.json();
        if (data.success && data.conversation) {
            selectPopupConversation(data.conversation.id, data.conversation);
            loadPopupThreads(true);
        }
    } catch(e) {}
}

// Load Threads list from API
async function loadPopupThreads(highlightCurrent = true) {
    if (!isUserLoggedIn) return;
    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/conversations`);
        const data = await res.json();
        if (data.success && Array.isArray(data.conversations)) {
            popupThreadsListCache = data.conversations;
            renderThreadsList(popupThreadsListCache);

            // Update unread badge on launcher & top navigation in real-time
            let totalUnread = 0;
            data.conversations.forEach(c => {
                totalUnread += parseInt(c.unread_count || 0);
            });

            const launcherBadge = document.getElementById('chatLauncherBadge');
            if (launcherBadge) {
                if (totalUnread > 0) {
                    launcherBadge.innerText = totalUnread > 99 ? '99+' : totalUnread;
                    launcherBadge.classList.remove('hidden');
                } else {
                    launcherBadge.classList.add('hidden');
                }
            }

            const topNavBadge = document.getElementById('topNavMessagesBadge');
            if (topNavBadge) {
                if (totalUnread > 0) {
                    topNavBadge.innerText = totalUnread > 99 ? '99+' : totalUnread;
                    topNavBadge.classList.remove('hidden');
                } else {
                    topNavBadge.classList.add('hidden');
                }
            }

            // If no active conversation selected yet and we have conversations, select the first one
            if (!currentPopupConversationId && data.conversations.length > 0 && isChatWindowOpen) {
                selectPopupConversation(data.conversations[0].id, data.conversations[0]);
            }
        }
    } catch(e) {}
}

// Render Threads list in Left Pane
function renderThreadsList(threads) {
    const container = document.getElementById('popupThreadsList');
    if (!container) return;

    if (!threads || threads.length === 0) {
        container.innerHTML = `
            <div class="p-6 text-center text-slate-400 text-xs">
                <span class="material-symbols-outlined text-[32px] text-slate-300 mb-1">chat_bubble_outline</span>
                <p class="font-bold text-slate-600">No conversations yet</p>
                <p class="text-[11px] mt-1">Browse products and click "Chat with Factory" to start direct negotiation.</p>
            </div>
        `;
        return;
    }

    let html = '';
    threads.forEach(t => {
        const isSelected = (currentPopupConversationId && parseInt(t.id) === parseInt(currentPopupConversationId));
        const supplierName = escapePopupHtml(t.supplier_company_name || t.supplier_name || 'Manufacturer');
        const productTitle = escapePopupHtml(t.product_name || 'Direct Wholesale Inquiry');
        const lastMsg = escapePopupHtml(t.last_message_body || 'Start negotiation...');
        const unread = parseInt(t.unread_count || 0);
        const timeStr = formatPopupTime(t.last_message_at || t.updated_at);

        html += `
            <div onclick="selectPopupConversation(${t.id})" class="p-2.5 rounded-xl cursor-pointer transition-all ${isSelected ? 'bg-blue-50 border-l-4 border-blue-900 shadow-2xs' : 'hover:bg-slate-100/80 border-l-4 border-transparent'}">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-headline font-bold text-xs text-slate-900 truncate max-w-[170px]">${supplierName}</span>
                    <span class="text-[10px] text-slate-400">${timeStr}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1">
                    <span class="truncate max-w-[190px] text-blue-900/80 font-medium">${productTitle}</span>
                    ${unread > 0 ? `<span class="px-1.5 py-0.2 rounded-full bg-blue-900 text-white font-extrabold text-[9px]">${unread}</span>` : ''}
                </div>
                <p class="text-[11px] text-slate-500 truncate leading-tight">${lastMsg}</p>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Filter Threads list by search input
function filterPopupThreads() {
    const input = document.getElementById('popupThreadsSearchInput');
    const q = (input ? input.value : '').toLowerCase().trim();
    if (!q) {
        renderThreadsList(popupThreadsListCache);
        return;
    }
    const filtered = popupThreadsListCache.filter(t => {
        const sName = (t.supplier_company_name || t.supplier_name || '').toLowerCase();
        const pName = (t.product_name || '').toLowerCase();
        const lMsg = (t.last_message_body || '').toLowerCase();
        return sName.includes(q) || pName.includes(q) || lMsg.includes(q);
    });
    renderThreadsList(filtered);
}

// Select active conversation
async function selectPopupConversation(convId, convData = null) {
    currentPopupConversationId = convId;
    popupLastMessageId = 0;

    // Update Full Screen link
    const fullScreenLink = document.getElementById('popupFullScreenLink');
    if (fullScreenLink) {
        fullScreenLink.href = `${popupBaseUrl}/buyer/messages?conversation_id=${convId}`;
    }

    // Switch view on mobile
    if (window.innerWidth < 768) {
        const tPane = document.getElementById('popupThreadsPane');
        const cPane = document.getElementById('popupChatPane');
        if (tPane) tPane.classList.add('hidden');
        if (cPane) cPane.classList.remove('hidden');
    }

    // Re-render threads list to reflect selected styling
    renderThreadsList(popupThreadsListCache);

    // Load conversation details & messages
    await loadPopupConversationDetails(convId, convData);

    // Initialize Real-time synchronization (SSE + Polling Fallback)
    startPopupPolling();
    initPopupSse(convId);
}

// Load conversation details & messages from API
async function loadPopupConversationDetails(convId, prefetchedData = null) {
    const container = document.getElementById('popupMessagesContainer');
    if (container) {
        container.innerHTML = `
            <div class="p-8 text-center text-slate-400 text-xs">
                <span class="material-symbols-outlined text-[24px] animate-spin mb-1 text-blue-900">progress_activity</span>
                <p>Loading messages & documents...</p>
            </div>
        `;
    }

    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/conversations/${convId}`);
        const data = await res.json();
        if (data.success && data.conversation) {
            updateConversationHeaderUI(data.conversation);
            updateProductCardUI(data.conversation);

            // Populate offers lookup
            popupOffersMap = {};
            if (Array.isArray(data.offers)) {
                data.offers.forEach(o => {
                    popupOffersMap[o.id] = o;
                });
            }
            if (data.latest_offer) {
                popupOffersMap[data.latest_offer.id] = data.latest_offer;
            }

            if (container) container.innerHTML = '';
            if (Array.isArray(data.messages)) {
                data.messages.forEach(msg => {
                    appendPopupMessage(msg);
                    popupLastMessageId = Math.max(popupLastMessageId, parseInt(msg.id));
                });
            }
            scrollMessagesToBottom();
        }
    } catch(e) {}
}

// Update Top Header UI
function updateConversationHeaderUI(conv) {
    const name = conv.supplier_company_name || conv.supplier_name || 'Manufacturer';
    const sub = conv.product_name ? `Product: ${conv.product_name}` : 'Direct Factory Negotiation';

    const nameEl = document.getElementById('popupSupplierName');
    const subEl = document.getElementById('popupProductSub');
    const avatarEl = document.getElementById('popupSupplierAvatar');

    if (nameEl) nameEl.innerText = name;
    if (subEl) subEl.innerText = sub;
    if (avatarEl) avatarEl.innerText = (name.trim().charAt(0) || 'M').toUpperCase();
}

// Update Product Card UI
function updateProductCardUI(data) {
    const card = document.getElementById('popupProductCard');
    if (!card) return;

    const title = data.product_name || data.title;
    if (!title) {
        card.style.display = 'none';
        return;
    }

    card.style.display = 'flex';
    document.getElementById('popupProductTitle').innerText = title;
    document.getElementById('popupProductPrice').innerText = data.price ? ('$' + parseFloat(data.price).toLocaleString('en-US', {minimumFractionDigits: 2})) : 'Wholesale Quote';
    document.getElementById('popupProductMoq').innerText = 'MOQ: ' + (data.moq || data.moq_quantity || 1) + ' units';

    const imgEl = document.getElementById('popupProductImg');
    if (data.image || data.product_image) {
        imgEl.src = data.image || data.product_image;
        imgEl.style.display = 'block';
    } else {
        imgEl.style.display = 'none';
    }

    const linkEl = document.getElementById('popupProductLink');
    const prodId = data.product_id || data.id;
    if (linkEl && prodId) {
        linkEl.href = `${popupBaseUrl}/products/${prodId}`;
        linkEl.style.display = 'flex';
    } else if (linkEl) {
        linkEl.style.display = 'none';
    }
}

// Polling Fallback (ensures supplier & admin messages always arrive even on SSE drop)
function startPopupPolling() {
    stopPopupPolling();
    popupPollInterval = setInterval(pollPopupMessages, 2500);
}

function stopPopupPolling() {
    if (popupPollInterval) {
        clearInterval(popupPollInterval);
        popupPollInterval = null;
    }
}

async function pollPopupMessages() {
    if (!currentPopupConversationId || !isChatWindowOpen) return;
    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/poll?conversation_id=${currentPopupConversationId}&last_id=${popupLastMessageId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
            if (data.latest_offer) {
                popupOffersMap[data.latest_offer.id] = data.latest_offer;
            }
            data.messages.forEach(msg => {
                if (parseInt(msg.id) > popupLastMessageId) {
                    popupLastMessageId = Math.max(popupLastMessageId, parseInt(msg.id));
                    appendPopupMessage(msg);
                }
            });
            scrollMessagesToBottom();
        }
    } catch(e) {}
}

// SSE Real-Time Stream with Auto-Reconnect
function initPopupSse(convId) {
    if (popupSse) {
        popupSse.close();
        popupSse = null;
    }
    if (!window.EventSource || !convId) return;

    try {
        popupSse = new EventSource(`${popupBaseUrl}/api/chat/stream?conversation_id=${convId}&last_id=${popupLastMessageId}`);
        
        popupSse.addEventListener('message', function(e) {
            try {
                const msg = JSON.parse(e.data);
                if (msg && parseInt(msg.id) > popupLastMessageId) {
                    popupLastMessageId = Math.max(popupLastMessageId, parseInt(msg.id));
                    appendPopupMessage(msg);
                    scrollMessagesToBottom();
                }
            } catch(err) {}
        });

        popupSse.addEventListener('timeout', function() {
            if (popupSse) popupSse.close();
            if (isChatWindowOpen) {
                setTimeout(() => initPopupSse(currentPopupConversationId), 1000);
            }
        });

        popupSse.onerror = function() {
            if (popupSse) popupSse.close();
            if (isChatWindowOpen) {
                setTimeout(() => initPopupSse(currentPopupConversationId), 3000);
            }
        };
    } catch(err) {}
}

// Append Message with Rich Previews (Fixing Supplier/Admin visibility & Attachment cards)
function appendPopupMessage(msg) {
    const container = document.getElementById('popupMessagesContainer');
    if (!container) return;

    // Check if element already exists (avoid duplicates)
    if (document.getElementById(`popup-msg-${msg.id}`)) {
        return;
    }

    const isMe = (parseInt(msg.sender_user_id) === parseInt(currentUserId));
    const bubble = document.createElement('div');
    bubble.id = `popup-msg-${msg.id}`;

    // System Message Pill
    if (msg.message_type === 'SYSTEM') {
        bubble.className = 'flex justify-center my-2 animate-in fade-in duration-150';
        bubble.innerHTML = `
            <div class="px-3 py-1 rounded-full bg-slate-200/80 border border-slate-300 text-slate-700 text-[10px] font-semibold text-center max-w-xs shadow-2xs">
                <span class="material-symbols-outlined text-[13px] align-middle text-blue-900 mr-0.5">info</span>
                ${escapePopupHtml(msg.message_body || '')}
            </div>
        `;
        container.appendChild(bubble);
        return;
    }

    // Structured Offer Card
    if (msg.message_type === 'OFFER') {
        bubble.className = `flex ${isMe ? 'justify-end' : 'justify-start'} my-2 animate-in fade-in duration-150`;
        bubble.innerHTML = renderPopupOfferCard(msg, isMe);
        container.appendChild(bubble);
        return;
    }

    bubble.className = `flex ${isMe ? 'justify-end' : 'justify-start'} my-1 animate-in fade-in duration-150`;

    // Sender Label Badge for Incoming Messages (Supplier vs Admin Support)
    let senderBadge = '';
    if (!isMe) {
        if (msg.is_on_behalf_of_supplier || msg.sender_role === 'SUPER_ADMIN') {
            senderBadge = `
                <div class="flex items-center gap-1 font-bold text-[10px] text-amber-700 mb-1">
                    <span class="material-symbols-outlined text-[13px] text-amber-600">shield_person</span>
                    <span>Sports Junction Support</span>
                    <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-900 text-[9px] font-semibold">Official Rep</span>
                </div>
            `;
        } else {
            const senderName = escapePopupHtml(msg.sender_company || msg.sender_first_name || 'Manufacturer');
            senderBadge = `
                <div class="flex items-center gap-1 font-bold text-[10px] text-teal-800 mb-1">
                    <span class="material-symbols-outlined text-[13px] text-teal-600">verified</span>
                    <span>${senderName}</span>
                    <span class="px-1.5 py-0.2 rounded bg-teal-50 text-teal-700 border border-teal-200 text-[9px] font-semibold">Supplier</span>
                </div>
            `;
        }
    }

    // Attachment Preview Card (PDF preview card & Image thumbnail)
    let meta = msg.metadata;
    if (typeof meta === 'string') {
        try { meta = JSON.parse(meta); } catch(e) { meta = {}; }
    }
    meta = meta || {};
    const rawBody = msg.message_body || '';
    const isAutoAttachText = typeof rawBody === 'string' && rawBody.startsWith('Sent an attachment:');
    const hasAttach = !!msg.attachment_path || !!msg.attachment_id || !!meta.attachment_id || msg.message_type === 'ATTACHMENT' || isAutoAttachText;
    const attachmentHtml = hasAttach ? renderPopupAttachment(msg, isMe) : '';

    // Suppress redundant raw text if attachment card is already displayed
    const textHtml = (!isAutoAttachText && rawBody.trim()) 
        ? `<div class="leading-relaxed break-words whitespace-pre-wrap">${escapePopupHtml(rawBody)}</div>`
        : '';

    const timeStr = msg.created_at ? msg.created_at.substr(11, 5) : 'Just now';

    let content = `
        <div class="max-w-[82%] rounded-2xl px-3.5 py-2.5 text-xs shadow-xs relative ${isMe ? 'bg-blue-900 text-white rounded-br-none' : 'bg-white border border-slate-200/90 text-slate-800 rounded-bl-none'}">
            ${senderBadge}
            ${textHtml}
            ${attachmentHtml}
            <div class="mt-1 flex items-center justify-end gap-1 text-[9px] ${isMe ? 'text-blue-200' : 'text-slate-400'}">
                <span>${timeStr}</span>
                ${isMe ? '<span class="material-symbols-outlined text-[13px] text-teal-300">done_all</span>' : ''}
            </div>
        </div>
    `;

    bubble.innerHTML = content;
    container.appendChild(bubble);
}

// Rich Attachment Rendering: PDF Card & Image Preview
function renderPopupAttachment(msg, isMe) {
    let meta = msg.metadata;
    if (typeof meta === 'string') {
        try { meta = JSON.parse(meta); } catch(e) { meta = {}; }
    }
    meta = meta || {};
    const attachId = msg.attachment_id || meta.attachment_id || 0;
    let fileName = msg.attachment_name || '';
    let ext = (msg.attachment_type || '').toLowerCase();

    const rawBody = msg.message_body || '';
    const isAutoAttachText = typeof rawBody === 'string' && rawBody.startsWith('Sent an attachment:');
    const hasAttach = !!msg.attachment_path || !!msg.attachment_id || !!meta.attachment_id || msg.message_type === 'ATTACHMENT' || isAutoAttachText;
    if (!hasAttach) {
        return '';
    }

    // Parse filename & ext from message body if missing
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
    const fileUrl = msg.attachment_path ? `${popupBaseUrl}${msg.attachment_path}` : (attachId ? `${popupBaseUrl}/api/chat/attachments/${attachId}/download` : '#');
    const downloadUrl = attachId ? `${popupBaseUrl}/api/chat/attachments/${attachId}/download` : fileUrl;
    const safeFileName = escapePopupHtml(fileName);
    const fileSize = msg.attachment_size ? `${Math.round(msg.attachment_size / 1024 * 10) / 10} KB` : '';

    // IMAGE THUMBNAIL PREVIEW
    if (isImg) {
        return `
            <div class="mt-2 rounded-xl overflow-hidden border ${isMe ? 'border-blue-800 bg-blue-950/40' : 'border-slate-200 bg-slate-100'} max-w-xs">
                <a href="${fileUrl}" target="_blank" rel="noopener noreferrer" class="block group relative">
                    <img src="${fileUrl}" alt="${fileName}" class="max-h-40 w-full object-cover group-hover:opacity-95 transition-opacity">
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

    // PDF PREVIEW & DOWNLOAD CARD
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

    // GENERIC DOCUMENT CARD
    return `
        <div class="mt-2 p-2 rounded-xl ${isMe ? 'bg-blue-950/60 border border-blue-800 text-white' : 'bg-slate-50 border border-slate-200 text-slate-800'} flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 overflow-hidden">
                <span class="material-symbols-outlined text-[20px] ${isMe ? 'text-blue-300' : 'text-blue-700'}">attach_file</span>
                <div class="truncate text-[11px]">
                    <span class="font-bold block truncate">${fileName}</span>
                    <span class="text-[9px] opacity-75">${ext.toUpperCase()} • ${fileSize}</span>
                </div>
            </div>
            <a href="${downloadUrl}" class="p-1.5 rounded-lg ${isMe ? 'bg-blue-800 hover:bg-blue-700 text-white' : 'bg-white border border-slate-200 hover:bg-slate-100 text-slate-700'} transition-colors" title="Download Document">
                <span class="material-symbols-outlined text-[15px]">download</span>
            </a>
        </div>
    `;
}

// Render Structured Offer Card for Popup Widget
function renderPopupOfferCard(msg, isMe) {
    let meta = {};
    try {
        meta = typeof msg.metadata === 'object' ? (msg.metadata || {}) : JSON.parse(msg.metadata || '{}');
    } catch(e) {
        meta = {};
    }

    const offerId = parseInt(meta.offer_id || 0);
    const existingOffer = (offerId > 0 && popupOffersMap[offerId]) ? popupOffersMap[offerId] : null;
    const version = meta.version || (existingOffer ? existingOffer.offer_version : 1);
    const leadTime = meta.lead_time || (existingOffer ? existingOffer.lead_time : '');
    const status = (existingOffer && existingOffer.status) ? existingOffer.status : (meta.status || 'PENDING_ACCEPTANCE');

    const isAccepted = (status === 'ACCEPTED');
    const isCountered = (status === 'COUNTERED');
    const isRejected = (status === 'REJECTED');
    const isPending = (status === 'PENDING_ACCEPTANCE');

    let badgeHtml = '';
    if (isAccepted) {
        badgeHtml = `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1" id="popup-offer-badge-${offerId}"><span class="material-symbols-outlined text-[12px]">done_all</span> Agreed</span>`;
    } else if (isCountered) {
        badgeHtml = `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-200 text-slate-700" id="popup-offer-badge-${offerId}">Countered</span>`;
    } else if (isRejected) {
        badgeHtml = `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-800" id="popup-offer-badge-${offerId}">Declined</span>`;
    } else {
        badgeHtml = `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800" id="popup-offer-badge-${offerId}">Pending Review</span>`;
    }

    let actionsHtml = '';
    if (!isMe && offerId > 0 && isPending) {
        actionsHtml = `
            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center gap-1.5" id="popup-offer-actions-${offerId}">
                <button type="button" onclick="popupAcceptOffer(${offerId})" class="flex-1 py-1.5 px-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition-colors flex items-center justify-center gap-1 shadow-xs">
                    <span class="material-symbols-outlined text-[14px]">done</span> Accept Deal
                </button>
                <a href="${popupBaseUrl}/buyer/messages?conversation_id=${currentPopupConversationId}" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-900 hover:bg-blue-950 text-white font-bold text-[11px] transition-colors flex items-center justify-center gap-1 shadow-xs text-center" title="Open full negotiation to counter">
                    <span class="material-symbols-outlined text-[14px]">sync_alt</span> Counter
                </a>
                <button type="button" onclick="popupRejectOffer(${offerId})" class="py-1.5 px-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-[11px] transition-colors">
                    Decline
                </button>
            </div>
        `;
    } else if (isAccepted) {
        actionsHtml = `
            <div class="mt-2 pt-2 border-t border-emerald-200 flex items-center justify-between text-[11px] text-emerald-800 font-bold bg-emerald-50/70 p-2 rounded-lg" id="popup-offer-actions-${offerId}">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-emerald-600 text-[15px]">verified</span>
                    <span>${isMe ? 'Offer Accepted by Partner' : 'Deal Officially Agreed & Locked'}</span>
                </span>
                <span class="text-[9px] text-emerald-700 font-semibold">Terms Binding</span>
            </div>
        `;
    }

    const timeStr = msg.created_at ? msg.created_at.substr(11, 5) : 'Just now';

    return `
        <div class="w-full max-w-[340px] rounded-2xl p-3 bg-white border-2 ${isAccepted ? 'border-emerald-500 bg-emerald-50/20 shadow-md' : (isMe ? 'border-blue-600 shadow-xs' : 'border-emerald-600 shadow-md')} relative text-slate-800">
            <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-100">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined ${isAccepted ? 'text-emerald-600' : (isMe ? 'text-blue-900' : 'text-emerald-700')} text-[18px]">
                        ${isAccepted ? 'check_circle' : 'request_quote'}
                    </span>
                    <span class="font-headline font-bold text-[11px] text-slate-900">
                        ${isMe ? 'Your Offer Proposal' : 'Manufacturer Formal Offer'} (v${version})
                    </span>
                </div>
                ${badgeHtml}
            </div>

            <div class="space-y-1 text-slate-700">
                <div class="font-bold text-slate-900 text-xs">
                    ${escapePopupHtml(msg.message_body || '')}
                </div>
                ${leadTime ? `<div class="text-[10px] text-slate-500">Estimated Lead Time: <span class="font-semibold text-slate-700">${escapePopupHtml(leadTime)}</span></div>` : ''}
            </div>

            ${actionsHtml}

            <div class="mt-1.5 text-right text-[9px] text-slate-400">
                ${timeStr}
            </div>
        </div>
    `;
}

// Accept Offer in Popup Widget
async function popupAcceptOffer(offerId) {
    if (!confirm('Are you sure you want to officially accept this offer? This will mark the deal as agreed.')) return;
    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/offers/${offerId}/accept`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if (data.success) {
            if (popupOffersMap[offerId]) {
                popupOffersMap[offerId].status = 'ACCEPTED';
            }
            const actionsEl = document.getElementById(`popup-offer-actions-${offerId}`);
            if (actionsEl) {
                actionsEl.outerHTML = `
                    <div class="mt-2 pt-2 border-t border-emerald-200 flex items-center justify-between text-[11px] text-emerald-800 font-bold bg-emerald-50/70 p-1.5 rounded-lg">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-emerald-600 text-[15px]">verified</span>
                            <span>Deal Officially Agreed & Locked</span>
                        </span>
                        <span class="text-[9px] text-emerald-700 font-semibold">Terms Binding</span>
                    </div>
                `;
            }
            const badgeEl = document.getElementById(`popup-offer-badge-${offerId}`);
            if (badgeEl) {
                badgeEl.outerHTML = `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1" id="popup-offer-badge-${offerId}"><span class="material-symbols-outlined text-[12px]">done_all</span> Agreed</span>`;
            }
            loadPopupThreads(false);
            pollPopupMessages();
        } else {
            alert(data.message || 'Could not accept offer.');
        }
    } catch(e) {
        alert('Network error accepting offer.');
    }
}

// Decline Offer in Popup Widget
async function popupRejectOffer(offerId) {
    const reason = prompt('Please specify a brief reason for declining this offer (optional):');
    if (reason === null) return;
    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/offers/${offerId}/reject`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({reason: reason || 'Not suitable'})
        });
        const data = await res.json();
        if (data.success) {
            if (popupOffersMap[offerId]) {
                popupOffersMap[offerId].status = 'REJECTED';
            }
            const actionsEl = document.getElementById(`popup-offer-actions-${offerId}`);
            if (actionsEl) {
                actionsEl.outerHTML = `
                    <div class="mt-2 pt-1.5 text-xs text-rose-700 font-semibold border-t border-slate-100">
                        Offer Declined
                    </div>
                `;
            }
            const badgeEl = document.getElementById(`popup-offer-badge-${offerId}`);
            if (badgeEl) {
                badgeEl.outerHTML = `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-800" id="popup-offer-badge-${offerId}">Declined</span>`;
            }
            loadPopupThreads(false);
            pollPopupMessages();
        } else {
            alert(data.message || 'Could not decline offer.');
        }
    } catch(e) {
        alert('Network error declining offer.');
    }
}

function scrollMessagesToBottom() {
    const c = document.getElementById('popupMessagesContainer');
    if (c) c.scrollTop = c.scrollHeight;
}

function escapePopupHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatPopupTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(/-/g, '/'));
    if (isNaN(d.getTime())) return '';
    const now = new Date();
    if (d.toDateString() === now.toDateString()) {
        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    return d.toLocaleDateString([], {month: 'short', day: 'numeric'});
}

// Send Message Handler
async function handlePopupSend(e) {
    e.preventDefault();
    const input = document.getElementById('popupInputText');
    const text = input ? input.value.trim() : '';
    if (!text || !currentPopupConversationId) return;

    input.value = '';
    const alertBox = document.getElementById('popupWarningAlert');
    if (alertBox) alertBox.classList.add('hidden');

    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/messages`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                conversation_id: currentPopupConversationId,
                message_body: text,
                message_type: 'TEXT'
            })
        });
        const data = await res.json();
        if (data.success && data.message) {
            popupLastMessageId = Math.max(popupLastMessageId, parseInt(data.message.id));
            appendPopupMessage(data.message);
            scrollMessagesToBottom();
            loadPopupThreads(false);

            if (data.has_restricted_contact && data.warning && alertBox) {
                alertBox.classList.remove('hidden');
                document.getElementById('popupWarningText').innerText = data.warning;
            }
        }
    } catch(err) {}
}

// File Upload Handler (PDF, Images)
async function handlePopupFileUpload(fileInput) {
    if (!fileInput.files || !fileInput.files[0] || !currentPopupConversationId) return;
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('conversation_id', currentPopupConversationId);
    formData.append('attachment', file);

    try {
        const res = await fetch(`${popupBaseUrl}/api/chat/attachments/upload`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success && data.message) {
            popupLastMessageId = Math.max(popupLastMessageId, parseInt(data.message.id));
            appendPopupMessage(data.message);
            scrollMessagesToBottom();
            loadPopupThreads(false);
        } else {
            alert(data.message || 'File upload failed. Allowed: PDF, JPG, PNG, WEBP (Max 10MB)');
        }
    } catch(e) {
        alert('File upload failed.');
    } finally {
        fileInput.value = '';
    }
}

function popupInsertTemplate(t) {
    const input = document.getElementById('popupInputText');
    if (input) {
        input.value = t;
        input.focus();
    }
}

// Dual-pane toggles & mobile responsiveness
function toggleThreadsPaneDesktop() {
    const pane = document.getElementById('popupThreadsPane');
    const win = document.getElementById('chatExpandedWindow');
    if (!pane || !win) return;

    if (pane.classList.contains('hidden')) {
        pane.classList.remove('hidden');
        win.classList.remove('w-[420px]');
        win.classList.add('w-[780px]');
    } else {
        pane.classList.add('hidden');
        win.classList.remove('w-[780px]');
        win.classList.add('w-[420px]');
    }
}

function showThreadsMobile() {
    const tPane = document.getElementById('popupThreadsPane');
    const cPane = document.getElementById('popupChatPane');
    if (tPane) tPane.classList.remove('hidden');
    if (cPane) cPane.classList.add('hidden');
}

function toggleFloatingChat() {
    const win = document.getElementById('chatExpandedWindow');
    if (win.classList.contains('hidden')) {
        maximizeFloatingChat();
    } else {
        minimizeFloatingChat();
    }
}

function maximizeFloatingChat() {
    isChatWindowOpen = true;
    document.getElementById('chatExpandedWindow').classList.remove('hidden');
    document.getElementById('chatLauncherBtn').classList.add('hidden');
    if (isUserLoggedIn) {
        loadPopupThreads(true);
        startPopupPolling();
    }
}

function minimizeFloatingChat() {
    isChatWindowOpen = false;
    document.getElementById('chatExpandedWindow').classList.add('hidden');
    document.getElementById('chatLauncherBtn').classList.remove('hidden');
    stopPopupPolling();
    if (popupSse) {
        popupSse.close();
        popupSse = null;
    }
}

function closeFloatingChat() {
    minimizeFloatingChat();
    if (!isUserLoggedIn || currentUserRole !== 'BUYER') {
        const widget = document.getElementById('sjFloatingChatWidget');
        if (widget) widget.style.display = 'none';
    }
}
</script>
