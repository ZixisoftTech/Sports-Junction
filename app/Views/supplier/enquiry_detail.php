<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;

$baseUrl = Response::baseUrl();
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/enquiries" class="inline-flex items-center gap-1 text-xs font-bold text-blue-900 hover:underline mb-2">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to All Customer Enquiries</span>
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h1 class="font-headline font-extrabold text-2xl text-slate-900 tracking-tight">Customer Inquiry #<?= (int)$inquiry['id'] ?></h1>
            <span class="px-3 py-1 rounded-full text-xs font-bold border self-start sm:self-auto <?= ($inquiry['status'] === 'RESPONDED') ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' ?>">
                Status: <?= htmlspecialchars($inquiry['status']) ?>
            </span>
        </div>
    </div>

    <!-- Inquiry Details Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-slate-100">
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Product Inquired</label>
                <div class="flex items-center gap-3">
                    <img src="<?= htmlspecialchars(\App\Helpers\Uploader::imageUrl($inquiry['primary_image'])) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg';" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shrink-0">
                    <div>
                        <div class="font-bold text-slate-900"><?= htmlspecialchars($inquiry['product_name'] ?? 'General Catalog') ?></div>
                        <?php if (!empty($inquiry['product_slug'])): ?>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/product/<?= htmlspecialchars($inquiry['product_slug']) ?>" target="_blank" class="text-xs text-blue-900 hover:underline">View Product Page &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Buyer Details</label>
                <div class="font-bold text-slate-900"><?= htmlspecialchars($inquiry['buyer_name']) ?></div>
                <div class="text-xs text-slate-600"><?= htmlspecialchars($inquiry['company_name'] ?? 'Individual / Store') ?></div>
                <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($inquiry['buyer_email']) ?> <?= !empty($inquiry['buyer_phone']) ? '• ' . htmlspecialchars($inquiry['buyer_phone']) : '' ?></div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4">
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Target Quantity</label>
                <div class="font-bold text-slate-900 text-sm mt-0.5"><?= (int)$inquiry['target_quantity'] ?> units</div>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Lead Time Req</label>
                <div class="font-bold text-slate-900 text-sm mt-0.5"><?= htmlspecialchars($inquiry['lead_time_req'] ?? 'Standard') ?></div>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Enquiry Date</label>
                <div class="text-slate-600 text-xs mt-0.5"><?= date('d M Y, h:i A', strtotime($inquiry['created_at'])) ?></div>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Buyer Specifications &amp; Requirements</label>
            <div class="bg-slate-50 p-4 rounded-xl text-slate-800 text-sm whitespace-pre-line border border-slate-200/60 font-sans">
                <?= htmlspecialchars($inquiry['specifications']) ?>
            </div>
        </div>
    </div>

    <!-- Messages / Reply History -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6">
        <h2 class="font-headline font-bold text-base text-slate-900 mb-4">Conversation &amp; Quotations</h2>

        <div class="space-y-4 mb-6" id="repliesContainer">
            <?php if (empty($replies)): ?>
                <div class="text-center py-6 text-slate-400 text-xs">
                    No messages yet. Send a direct quotation or reply to the buyer below.
                </div>
            <?php else: ?>
                <?php foreach ($replies as $r): ?>
                    <?php $isMe = ($r['sender_role'] === 'SUPPLIER'); ?>
                    <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?>">
                        <div class="max-w-lg <?= $isMe ? 'bg-blue-900 text-white rounded-2xl rounded-tr-none' : 'bg-slate-100 text-slate-800 rounded-2xl rounded-tl-none' ?> p-4 text-xs shadow-xs">
                            <div class="font-bold mb-1 text-[11px] <?= $isMe ? 'text-blue-200' : 'text-slate-600' ?>">
                                <?= $isMe ? 'You (Manufacturer)' : htmlspecialchars($r['sender_name'] ?? 'Buyer') ?>
                                <span class="font-normal opacity-75 ml-1">• <?= date('d M, h:i A', strtotime($r['created_at'])) ?></span>
                            </div>
                            <div class="whitespace-pre-line text-sm"><?= htmlspecialchars($r['message']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply Form -->
        <form id="replyForm" onsubmit="submitReply(event)">
            <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Send Message / Quotation to Buyer</label>
            <textarea id="replyMessage" name="message" rows="3" required placeholder="Type your pricing, minimum lead time, or specifications reply here..." class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-blue-700 focus:ring-2 focus:ring-blue-100 outline-none transition-all"></textarea>
            <div class="mt-3 flex justify-end">
                <button type="submit" id="btnSendReply" class="px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">send</span>
                    <span>Send Reply</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function submitReply(e) {
    e.preventDefault();
    const replyInput = document.getElementById('replyMessage');
    const sendBtn = document.getElementById('btnSendReply');
    if (!replyInput || !sendBtn) return;

    const msg = replyInput.value.trim();
    if (!msg) return;

    sendBtn.disabled = true;
    const origHtml = sendBtn.innerHTML;
    sendBtn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span><span>Sending...</span>';

    fetch('<?= htmlspecialchars($baseUrl) ?>/supplier/enquiries/<?= (int)$inquiry['id'] ?>/reply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= htmlspecialchars(CSRF::token()) ?>',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            message: msg,
            _csrf: '<?= htmlspecialchars(CSRF::token()) ?>'
        })
    })
    .then(r => r.json())
    .then(res => {
        sendBtn.disabled = false;
        sendBtn.innerHTML = origHtml;
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Error sending reply');
        }
    })
    .catch(() => {
        sendBtn.disabled = false;
        sendBtn.innerHTML = origHtml;
        alert('Server communication error.');
    });
}
</script>
