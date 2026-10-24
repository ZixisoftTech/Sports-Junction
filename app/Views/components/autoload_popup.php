<?php
use App\Models\SitePopup;
use App\Core\Response;

$activePopup = $activePopup ?? SitePopup::getActivePopup();
if ($activePopup):
    $contentType = strtoupper((string)($activePopup['content_type'] ?? 'IMAGE'));
    $hasImage = !empty($activePopup['image']);
    $popupImg = $hasImage ? Response::formatImageUrl($activePopup['image']) : '';
    $altText = !empty($activePopup['alt_text']) ? $activePopup['alt_text'] : $activePopup['title'];
    $popupUrl = !empty($activePopup['target_url']) ? (str_starts_with($activePopup['target_url'], 'http') ? $activePopup['target_url'] : $baseUrl . '/' . ltrim($activePopup['target_url'], '/')) : '';
    $delayMs = max(0, (int)($activePopup['delay_seconds'] ?? 2)) * 1000;
    $updatedVer = !empty($activePopup['updated_at']) ? strtotime($activePopup['updated_at']) : time();
?>
<!-- Autoload Marketing Popup -->
<div id="siteAutoloadPopup" class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-black/65 backdrop-blur-sm transition-opacity duration-300 opacity-0 p-3 sm:p-5" role="dialog" aria-modal="true" aria-labelledby="popupTitleHeading" style="-webkit-overflow-scrolling: touch;">
    <div id="siteAutoloadPopupWrapper" class="min-h-full flex items-center justify-center py-4 w-full">
        <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden w-[95vw] sm:w-[92vw] lg:w-[960px] max-w-[960px] max-h-[80vh] flex flex-col transform scale-95 transition-transform duration-300 border border-slate-200" id="siteAutoloadPopupBox" style="width: 960px; max-width: 95vw; max-height: 80vh;">
            <!-- Close Button (Pinned Top-Right, high z-index) -->
            <button type="button" id="closeSiteAutoloadPopup" class="absolute top-3 right-3 z-50 w-10 h-10 rounded-full bg-slate-900/90 hover:bg-black text-white flex items-center justify-center transition-all cursor-pointer shadow-xl hover:scale-105 border-2 border-white" aria-label="Close popup">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>

            <?php if ($contentType === 'TEXT'): ?>
                <!-- TEXT POPUP MODE -->
                <div class="popup-scroll-body p-6 sm:p-8 overflow-y-auto flex-1 min-h-0 text-slate-800 overscroll-contain">
                    <div class="pr-8 mb-4 border-b border-slate-100 pb-3">
                        <h3 id="popupTitleHeading" class="text-xl sm:text-2xl font-bold font-headline text-slate-900 tracking-tight">
                            <?= htmlspecialchars($activePopup['title']) ?>
                        </h3>
                    </div>

                    <div class="popup-rich-content text-slate-700 text-sm sm:text-base leading-relaxed space-y-3">
                        <?= $activePopup['content_html'] ?? '' ?>
                    </div>

                    <?php if (!empty($popupUrl)): ?>
                    <div class="pt-5 mt-6 border-t border-slate-100 flex justify-end">
                        <a href="<?= htmlspecialchars($popupUrl) ?>" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-headline font-bold text-sm shadow-sm transition-all">
                            <span>Learn More &amp; Explore</span>
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- IMAGE POPUP MODE -->
                <div class="popup-scroll-body overflow-y-auto flex-1 min-h-0 w-full bg-white overscroll-contain">
                    <h3 id="popupTitleHeading" class="sr-only"><?= htmlspecialchars($activePopup['title']) ?></h3>

                    <?php if (!empty($popupUrl)): ?>
                    <a href="<?= htmlspecialchars($popupUrl) ?>" class="block w-full text-center group cursor-pointer">
                        <img src="<?= htmlspecialchars($popupImg) ?>" 
                             alt="<?= htmlspecialchars($altText) ?>" 
                             class="w-full h-auto block mx-auto group-hover:opacity-95 transition-opacity" 
                             onerror="this.closest('#siteAutoloadPopup').remove();">
                    </a>
                    <?php else: ?>
                    <img src="<?= htmlspecialchars($popupImg) ?>" 
                         alt="<?= htmlspecialchars($altText) ?>" 
                         class="w-full h-auto block mx-auto" 
                         onerror="this.closest('#siteAutoloadPopup').remove();">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Visible, smooth custom scrollbars for popup modal */
.popup-scroll-body {
    scrollbar-width: thin;
    scrollbar-color: #94a3b8 #f1f5f9;
    -webkit-overflow-scrolling: touch;
}
.popup-scroll-body::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.popup-scroll-body::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.popup-scroll-body::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 4px;
}
.popup-scroll-body::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* Safe styling for rich-text popup content */
.popup-rich-content h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a; }
.popup-rich-content h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a; }
.popup-rich-content h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.4rem; color: #0f172a; }
.popup-rich-content p { margin-bottom: 0.75rem; }
.popup-rich-content ul { list-style-type: disc; padding-left: 1.25rem; margin-bottom: 0.75rem; }
.popup-rich-content ol { list-style-type: decimal; padding-left: 1.25rem; margin-bottom: 0.75rem; }
.popup-rich-content li { margin-bottom: 0.25rem; }
.popup-rich-content a { color: #1d4ed8; text-decoration: underline; }
.popup-rich-content a:hover { color: #1e40af; }
.popup-rich-content blockquote { border-left: 4px solid #cbd5e1; padding-left: 1rem; font-style: italic; color: #475569; margin: 0.75rem 0; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const popupId = '<?= $activePopup['id'] ?>';
    const popupVer = '<?= $updatedVer ?>';
    const storageKey = 'sj_popup_dismissed_' + popupId + '_' + popupVer;
    if (sessionStorage.getItem(storageKey)) {
        return; // Already dismissed this session
    }

    const popupEl = document.getElementById('siteAutoloadPopup');
    const popupBox = document.getElementById('siteAutoloadPopupBox');
    const closeBtn = document.getElementById('closeSiteAutoloadPopup');
    if (!popupEl) return;

    function dismissPopup() {
        sessionStorage.setItem(storageKey, '1');
        popupEl.classList.add('opacity-0');
        if (popupBox) popupBox.classList.add('scale-95');
        setTimeout(() => {
            popupEl.classList.add('hidden');
        }, 300);
    }

    if (closeBtn) closeBtn.addEventListener('click', dismissPopup);
    popupEl.addEventListener('click', function (e) {
        if (e.target === popupEl || e.target.id === 'siteAutoloadPopupWrapper') dismissPopup();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !popupEl.classList.contains('hidden')) {
            dismissPopup();
        }
    });

    setTimeout(function () {
        popupEl.classList.remove('hidden');
        setTimeout(() => {
            popupEl.classList.remove('opacity-0');
            if (popupBox) popupBox.classList.remove('scale-95');
        }, 20);
    }, <?= $delayMs ?>);
});
</script>
<?php endif; ?>
