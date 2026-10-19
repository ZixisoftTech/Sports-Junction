<?php
use App\Models\SitePopup;
use App\Core\Response;

$activePopup = SitePopup::getActivePopup();
if ($activePopup):
    $popupImg = Response::formatImageUrl($activePopup['image']);
    $popupUrl = !empty($activePopup['target_url']) ? (str_starts_with($activePopup['target_url'], 'http') ? $activePopup['target_url'] : $baseUrl . '/' . ltrim($activePopup['target_url'], '/')) : '';
    $delayMs = max(0, (int)($activePopup['delay_seconds'] ?? 2)) * 1000;
?>
<!-- Autoload Marketing Popup -->
<div id="siteAutoloadPopup" class="fixed inset-0 z-[99999] hidden flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity duration-300 opacity-0" role="dialog" aria-modal="true" aria-labelledby="popupTitleText">
    <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden max-w-md w-full transform scale-95 transition-transform duration-300 border border-slate-200" id="siteAutoloadPopupBox">
        <!-- Close Button -->
        <button type="button" id="closeSiteAutoloadPopup" class="absolute top-3 right-3 z-30 w-8 h-8 rounded-full bg-black/60 hover:bg-black/85 text-white flex items-center justify-center transition-all cursor-pointer shadow-md" aria-label="Close popup">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>

        <span id="popupTitleText" class="sr-only"><?= htmlspecialchars($activePopup['title']) ?></span>

        <!-- Popup Creative -->
        <?php if (!empty($popupUrl)): ?>
        <a href="<?= htmlspecialchars($popupUrl) ?>" class="block group">
            <img src="<?= htmlspecialchars($popupImg) ?>" alt="<?= htmlspecialchars($activePopup['title']) ?>" class="w-full h-auto object-cover max-h-[75vh] group-hover:opacity-95 transition-opacity" onerror="this.closest('#siteAutoloadPopup').remove();">
        </a>
        <?php else: ?>
        <img src="<?= htmlspecialchars($popupImg) ?>" alt="<?= htmlspecialchars($activePopup['title']) ?>" class="w-full h-auto object-cover max-h-[75vh]" onerror="this.closest('#siteAutoloadPopup').remove();">
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const popupId = '<?= $activePopup['id'] ?>';
    const storageKey = 'sj_popup_dismissed_' + popupId;
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
        if (e.target === popupEl) dismissPopup();
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
