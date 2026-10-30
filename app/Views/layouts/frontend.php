<?php
declare(strict_types=1);

use App\Core\Response;
use App\Models\SiteSetting;
use App\Models\CmsPage;
use App\Models\ChatConversation;

$baseUrl = Response::baseUrl();
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');

try {
    $siteSettings = SiteSetting::getAllKeyed();
} catch (\Throwable $e) {
    $siteSettings = [];
}
try {
    $siteLogo = \App\Models\SiteSetting::getLogoUrl();
} catch (\Throwable $e) {
    $siteLogo = $baseUrl . '/assets/images/logo.png';
}
$siteName = $siteSettings['site_name'] ?? 'Sports Junction';

$authUser = \App\Helpers\Session::get('auth_user');
$authSupplierId = 0;
try {
    $authSupplierId = \App\Models\Supplier::currentSupplierId();
} catch (\Throwable $e) {}

$buyerUnreadCount = 0;
$buyerFavCount = 0;
$buyerSortCount = 0;
if ($authUser && ($authUser['role'] ?? '') === 'BUYER') {
    try {
        $buyerUnreadCount = ChatConversation::getTotalUnreadCountForUser((int)$authUser['id'], 'BUYER');
        $buyerFavCount = \App\Models\BuyerFavourite::countForBuyer((int)$authUser['id']);
        $buyerSortCount = $buyerFavCount;
    } catch (\Throwable $e) {
        $buyerUnreadCount = 0;
        $buyerFavCount = 0;
        $buyerSortCount = 0;
    }
} else {
    $buyerSortCount = 0;
    $buyerFavCount = 0;
}
try {
    $footerColumns = CmsPage::getByColumns(true);
} catch (\Throwable $e) {
    $footerColumns = [];
}
$categories = !empty($categories) ? $categories : \App\Models\Category::allActive();
$allCountries = !empty($countries) ? $countries : \App\Models\Country::allActive();
$buyerCountryId = null;
if ($authUser && ($authUser['role'] ?? '') === 'BUYER') {
    try {
        $dbBuyer = \App\Models\User::findById((int)$authUser['id']);
        if ($dbBuyer && !empty($dbBuyer['country_id'])) {
            $buyerCountryId = (int)$dbBuyer['country_id'];
        }
    } catch (\Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sports Junction — India\'s Sports Manufacturing Marketplace') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Direct factory wholesale marketplace connecting international buyers with verified Indian sports manufacturers.') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/favicon.png">
    <link rel="shortcut icon" href="<?= htmlspecialchars($baseUrl) ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/favicon.png">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    <!-- Suppress Tailwind Play CDN in production console warning -->
    <script>
        (function() {
            var origWarn = console.warn;
            console.warn = function() {
                if (arguments[0] && typeof arguments[0] === 'string' && arguments[0].indexOf('cdn.tailwindcss.com should not be used in production') !== -1) {
                    return;
                }
                origWarn.apply(console, arguments);
            };
        })();
    </script>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=typography,forms,aspect-ratio"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#00288e",
                        "primary-container": "#1e40af",
                        "on-primary": "#ffffff",
                        secondary: "#006a61",
                        "secondary-container": "#86f2e4",
                        "on-secondary": "#ffffff",
                        surface: "#f8f9ff",
                        "on-surface": "#0b1c30",
                        "on-surface-variant": "#444653",
                        outline: "#757684",
                        "outline-variant": "#c4c5d5",
                        "surface-container": "#e5eeff",
                        "surface-container-low": "#eff4ff",
                        "surface-container-high": "#dce9ff",
                        "surface-container-lowest": "#ffffff"
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        headline: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
            font-family: 'Material Symbols Outlined' !important;
            font-style: normal;
            font-weight: normal;
            display: inline-block;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
        .material-symbols-outlined.fill-1 {
            font-variation-settings: 'FILL' 1;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Prevent Google Translate and browser translation tools from mangling icons and coding UI elements */
        .notranslate,
        [translate="no"],
        .material-symbols-outlined,
        .material-symbols,
        [class*="material-symbols"] {
            -webkit-user-select: none;
            user-select: none;
        }

        /* Google Translate clean integration styles */
        .goog-te-banner-frame.skiptranslate,
        iframe.goog-te-banner-frame,
        .goog-te-banner-frame {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
        }
        body {
            top: 0px !important;
            position: static !important;
        }
        .skiptranslate:not(#google_translate_element) {
            display: none !important;
        }
        #goog-gt-tt, .goog-te-balloon-frame {
            display: none !important;
        }
        .goog-text-highlight {
            background: none !important;
            box-shadow: none !important;
        }
    </style>
    <script>
    // Bulletproof protection against translation tools corrupting icon ligatures or coding UI elements
    (function() {
        var ICON_SELECTOR = '.material-symbols-outlined, .material-symbols, [class*="material-symbols"], i[class*="bi-"], .bi, i[class*="fa-"], .fa, .fas, .far, .fab';
        var CODE_SELECTOR = 'code, pre, kbd, var, samp, .font-mono';
        var ALL_PROTECTED_SELECTOR = ICON_SELECTOR + ', ' + CODE_SELECTOR;

        function tagElement(el) {
            if (!el || el.nodeType !== 1) return;
            el.setAttribute('translate', 'no');
            if (!el.classList.contains('notranslate')) {
                el.classList.add('notranslate');
            }
            if (el.matches && el.matches(ICON_SELECTOR)) {
                var iconName = el.getAttribute('data-icon-name');
                if (!iconName) {
                    var txt = (el.textContent || '').trim();
                    if (txt) {
                        el.setAttribute('data-icon-name', txt);
                    }
                } else if (el.textContent && el.textContent.trim() !== iconName) {
                    el.textContent = iconName;
                }
            }
        }

        function restoreIcon(el) {
            if (!el || el.nodeType !== 1) return;
            var iconEl = el.closest ? el.closest(ICON_SELECTOR) : null;
            if (iconEl) {
                var orig = iconEl.getAttribute('data-icon-name');
                if (orig && iconEl.textContent.trim() !== orig) {
                    iconEl.textContent = orig;
                    iconEl.setAttribute('translate', 'no');
                    if (!iconEl.classList.contains('notranslate')) {
                        iconEl.classList.add('notranslate');
                    }
                }
            }
        }

        function protectElements(root) {
            try {
                var els = (root || document).querySelectorAll(ALL_PROTECTED_SELECTOR);
                for (var i = 0; i < els.length; i++) {
                    tagElement(els[i]);
                }
            } catch (e) {}
        }

        if (document.documentElement) {
            protectElements(document.documentElement);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { protectElements(document); });
        } else {
            protectElements(document);
        }

        if (window.MutationObserver) {
            var obs = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (m.type === 'childList') {
                        // Check if the container itself is an icon being modified by translation wrappers (e.g. <font>)
                        if (m.target && m.target.nodeType === 1) {
                            restoreIcon(m.target);
                        }
                        for (var j = 0; j < m.addedNodes.length; j++) {
                            var node = m.addedNodes[j];
                            if (node && node.nodeType === 1) {
                                restoreIcon(node);
                                if (node.matches && node.matches(ALL_PROTECTED_SELECTOR)) {
                                    tagElement(node);
                                }
                                protectElements(node);
                            }
                        }
                    } else if (m.type === 'characterData') {
                        if (m.target && m.target.parentElement) {
                            restoreIcon(m.target.parentElement);
                        }
                    }
                }
            });
            obs.observe(document.documentElement || document, { childList: true, subtree: true, characterData: true });
        }

        // Safety fallback checks during initial load and language switch passes
        var intervalCount = 0;
        var safetyInterval = setInterval(function() {
            intervalCount++;
            protectElements(document);
            if (intervalCount > 25) { // Run for ~5 seconds
                clearInterval(safetyInterval);
            }
        }, 200);

        window.protectUiElementsFromTranslation = protectElements;
    })();
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex flex-col min-h-screen">
    <?php 
    $impersonatorAdmin = \App\Helpers\Session::get('impersonator_admin'); 
    $authUser = \App\Helpers\Session::get('auth_user');
    ?>
    <?php if (!empty($impersonatorAdmin)): ?>
        <div class="bg-amber-500 text-slate-950 px-4 py-2 text-xs font-bold flex items-center justify-between shadow-md z-[60] fixed top-0 left-0 right-0 border-b border-amber-600 h-9">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
                <span>IMPERSONATION MODE: Currently viewing as <strong><?= htmlspecialchars($authUser['name'] ?? 'User') ?></strong> (<?= htmlspecialchars($authUser['role'] ?? '') ?> &bull; <?= htmlspecialchars($authUser['email'] ?? '') ?>)</span>
            </div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/exit-impersonation" class="px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">logout</span>
                <span>Return to Super Admin</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Global Header -->
    <header class="fixed <?= !empty($impersonatorAdmin) ? 'top-9' : 'top-0' ?> left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm border-b border-surface-container">
        <!-- Top Utility & Search Bar -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="flex items-center shrink-0 group" title="<?= htmlspecialchars($siteName) ?>">
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="h-12 sm:h-14 w-auto object-contain max-w-[210px] sm:max-w-[240px]" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.png';">
            </a>

            <!-- Central Search Bar -->
            <div class="flex-1 max-w-2xl mx-2 sm:mx-6">
                <form action="<?= htmlspecialchars($baseUrl) ?>/products" method="GET" class="relative flex items-center w-full bg-surface-container-lowest rounded-full border border-outline-variant focus-within:border-primary focus-within:ring-2 focus-within:ring-primary-container/20 transition-all shadow-sm">
                    <input type="text" name="q" id="headerSearchInput" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search sports gear, manufacturers, cricket bats, hurdles..." class="w-full bg-transparent pl-5 pr-12 py-2.5 text-on-surface placeholder:text-outline text-sm focus:outline-none rounded-full">
                    <button type="submit" class="absolute right-1.5 flex items-center justify-center w-9 h-9 rounded-full bg-primary-container text-white hover:bg-primary transition-colors shrink-0" title="Search Marketplace">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                    </button>
                </form>
            </div>

            <!-- Right Actions -->
            <?php $authUser = \App\Helpers\Session::get('auth_user'); ?>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0 text-sm font-semibold">
                <!-- Sort List for All Users (Guests & Authenticated) -->
                <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/sort-list" class="text-on-surface hover:text-blue-900 transition-colors flex items-center gap-1 relative py-1 px-1.5 rounded-lg hover:bg-blue-50/60" title="Sort List">
                    <span class="material-symbols-outlined text-[20px] text-blue-900 fill-1">checklist</span>
                    <span class="hidden lg:inline text-xs font-bold text-slate-800">Sort List</span>
                    <span id="topNavSortBadge" class="<?= ($buyerSortCount > 0 ? '' : 'hidden') ?> inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-blue-900 text-white font-black text-[10px] leading-tight ml-0.5 shadow-xs"><?= $buyerSortCount > 99 ? '99+' : $buyerSortCount ?></span>
                </a>

                <?php if ($authUser): ?>
                    <?php if (($authUser['role'] ?? '') === 'BUYER'): ?>
                        <div class="hidden sm:flex items-center gap-3">
                            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/enquiries" class="text-on-surface hover:text-primary transition-colors flex items-center gap-1" title="My Enquiries">
                                <span class="material-symbols-outlined text-[18px]">request_quote</span>
                                <span class="hidden lg:inline text-xs font-bold">Enquiries</span>
                            </a>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/sample-enquiries" class="text-on-surface hover:text-teal-700 transition-colors flex items-center gap-1" title="Prototype Samples">
                                <span class="material-symbols-outlined text-[18px] text-teal-600">science</span>
                                <span class="hidden lg:inline text-xs font-bold">Samples</span>
                            </a>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/messages" class="text-on-surface hover:text-primary transition-colors flex items-center gap-1 relative" title="Messages & Negotiations">
                                <span class="material-symbols-outlined text-[18px]">chat</span>
                                <span class="hidden lg:inline text-xs font-bold">Messages</span>
                                <span id="topNavMessagesBadge" class="<?= ($buyerUnreadCount > 0 ? '' : 'hidden') ?> inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-red-600 text-white font-black text-[10px] leading-tight ml-1 shadow-xs"><?= $buyerUnreadCount > 99 ? '99+' : $buyerUnreadCount ?></span>
                            </a>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/dashboard" class="px-3 py-1.5 rounded-full bg-surface-container text-primary font-bold text-xs hover:bg-primary hover:text-white transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">person</span>
                                <span><?= htmlspecialchars(explode(' ', $authUser['name'])[0]) ?></span>
                            </a>
                        </div>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/logout" class="text-xs font-bold text-rose-600 hover:underline px-2">Logout</a>
                    <?php elseif (($authUser['role'] ?? '') === 'SUPPLIER'): ?>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="px-3.5 py-1.5 rounded-full bg-primary text-white font-bold text-xs shadow-xs">Seller Portal</a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/logout" class="text-xs font-bold text-rose-600 hover:underline px-2">Logout</a>
                    <?php elseif (($authUser['role'] ?? '') === 'SUPER_ADMIN'): ?>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="px-3.5 py-1.5 rounded-full bg-primary text-white font-bold text-xs shadow-xs">Admin Panel</a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/logout" class="text-xs font-bold text-rose-600 hover:underline px-2">Logout</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="hidden sm:flex items-center gap-2">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="text-on-surface hover:text-primary transition-colors py-1 px-2">Login</a>
                        <span class="text-outline-variant">|</span>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/register" class="text-on-surface hover:text-primary transition-colors py-1 px-2">Register</a>
                    </div>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/seller/login" class="rounded-full border-2 border-primary text-primary font-bold px-4 py-1.5 hover:bg-primary hover:text-white transition-all uppercase tracking-wider text-xs shadow-sm">
                        SELL
                    </a>
                <?php endif; ?>
                <!-- Google Website Translation Dropdown -->
                <div class="relative" id="googleTranslateWrapper">
                    <button type="button" id="btnGoogleTranslateToggle" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full border border-slate-200 hover:border-slate-300 bg-white text-on-surface-variant hover:text-on-surface text-xs font-semibold shadow-2xs transition-all cursor-pointer" title="Translate Website">
                        <span class="material-symbols-outlined text-[17px] text-primary">translate</span>
                        <span id="currentLangCode" class="font-bold">EN</span>
                        <span class="material-symbols-outlined text-[13px] text-slate-400">expand_more</span>
                    </button>
                    <div id="googleTranslateDropdown" class="hidden absolute right-0 top-full mt-1.5 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50 max-h-72 overflow-y-auto">
                        <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 mb-1">Translate Page</div>
                        <button type="button" onclick="setSiteLanguage('en')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="en">
                            <span>English (Default)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('hi')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="hi">
                            <span>हिन्दी (Hindi)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('es')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="es">
                            <span>Español (Spanish)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('fr')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="fr">
                            <span>Français (French)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('de')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="de">
                            <span>Deutsch (German)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('ar')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="ar">
                            <span>العربية (Arabic)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('zh-CN')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="zh-CN">
                            <span>中文 (Chinese)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('ja')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="ja">
                            <span>日本語 (Japanese)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('ru')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="ru">
                            <span>Русский (Russian)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('pt')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="pt">
                            <span>Português (Portuguese)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <button type="button" onclick="setSiteLanguage('it')" class="w-full text-left px-3 py-1.5 text-xs font-medium hover:bg-slate-50 flex items-center justify-between transition-colors lang-opt-btn" data-lang="it">
                            <span>Italiano (Italian)</span>
                            <span class="lang-check text-primary font-bold hidden">&check;</span>
                        </button>
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <button type="button" onclick="setSiteLanguage('en')" class="w-full text-left px-3 py-1.5 text-[11px] font-bold text-blue-900 hover:bg-blue-50 transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">restart_alt</span>
                                <span>Reset to Original (English)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Categories Navigation Bar -->
        <div class="w-full bg-surface-container-lowest border-t border-surface-container relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-11 text-xs uppercase tracking-wider font-semibold">
                <nav class="flex items-center gap-4 sm:gap-6 overflow-x-auto lg:overflow-visible py-2 no-scrollbar">
                    <!-- Dropdown Trigger -->
                    <div class="relative" id="allCategoriesNavContainer">
                        <button type="button" id="allCategoriesNavBtn" class="flex items-center gap-1.5 text-primary font-bold hover:text-primary-container transition-colors shrink-0 cursor-pointer focus:outline-none">
                            <span class="material-symbols-outlined text-[16px]">menu</span>
                            <span>ALL CATEGORIES</span>
                            <span class="material-symbols-outlined text-[14px] transition-transform duration-200" id="allCategoriesNavCaret">expand_more</span>
                        </button>
                        <!-- Mega Menu Dropdown with Flyout Subcategories -->
                        <div id="allCategoriesNavMenu" class="absolute top-full left-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 hidden z-[9999] flex flex-col md:flex-row overflow-hidden max-w-[95vw] md:max-w-none min-w-[280px]">
                            <!-- Left: Categories List -->
                            <div class="w-full md:w-72 shrink-0 bg-white p-2 border-b md:border-b-0 md:border-r border-slate-100 max-h-[75vh] overflow-y-auto no-scrollbar" id="categoryNavList">
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $idx => $catItem): ?>
                                        <?php 
                                        $hasSubs = !empty($catItem['subcategories']); 
                                        $catIcon = trim($catItem['icon'] ?? 'sports_tennis');
                                        $isImageIcon = (str_starts_with($catIcon, '/') || str_starts_with($catIcon, 'http') || str_contains($catIcon, '.'));
                                        ?>
                                        <div class="category-nav-group mb-0.5" data-cat-index="<?= $idx ?>">
                                            <div class="category-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-800 hover:bg-blue-50 hover:text-blue-900 text-xs font-semibold cursor-pointer transition-all <?= $idx === 0 ? 'bg-blue-50/80 text-blue-900' : '' ?>"
                                                 data-category-slug="<?= htmlspecialchars($catItem['slug']) ?>"
                                                 data-category-name="<?= htmlspecialchars($catItem['name']) ?>"
                                                 data-has-subcats="<?= $hasSubs ? '1' : '0' ?>"
                                                 onmouseenter="switchFlyoutCategory(<?= $idx ?>)">
                                                <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($catItem['slug']) ?>" class="flex items-center gap-2.5 flex-1 min-w-0 pr-1 text-inherit">
                                                    <?php if ($isImageIcon): ?>
                                                        <img src="<?= htmlspecialchars(Response::formatImageUrl($catIcon)) ?>" class="w-4 h-4 object-contain rounded shrink-0" alt="">
                                                    <?php else: ?>
                                                        <span class="material-symbols-outlined text-[18px] text-blue-900 shrink-0"><?= htmlspecialchars($catIcon) ?></span>
                                                    <?php endif; ?>
                                                    <span class="truncate"><?= htmlspecialchars($catItem['name']) ?></span>
                                                </a>

                                                <div class="flex items-center gap-1 shrink-0">
                                                    <?php if ($hasSubs): ?>
                                                        <!-- Desktop Chevron Indicator -->
                                                        <span class="hidden md:inline-flex material-symbols-outlined text-[14px] text-slate-400">chevron_right</span>
                                                        <!-- Mobile Accordion Trigger -->
                                                        <button type="button" class="md:hidden p-1 rounded-md text-slate-400 hover:text-slate-800 hover:bg-slate-200 transition-colors cursor-pointer" onclick="toggleMobileSubcat(event, <?= $idx ?>)" title="Toggle subcategories">
                                                            <span class="material-symbols-outlined text-[16px] transition-transform duration-200" id="mobileChevron-<?= $idx ?>">expand_more</span>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php if ($hasSubs): ?>
                                                <!-- Mobile Inline Drawer (collapsible) -->
                                                <div class="mobile-subcat-drawer hidden md:hidden pl-7 pr-2 py-1.5 space-y-1 bg-slate-50 border-l-2 border-blue-600 rounded-r-lg my-1" id="mobileDrawer-<?= $idx ?>">
                                                    <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($catItem['slug']) ?>" class="block py-1 text-[11px] font-bold text-blue-800 hover:underline">
                                                        View All in <?= htmlspecialchars($catItem['name']) ?> &rarr;
                                                    </a>
                                                    <?php foreach ($catItem['subcategories'] as $sub): ?>
                                                        <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($catItem['slug']) ?>/<?= htmlspecialchars($sub['slug']) ?>" class="flex items-center justify-between py-1 text-xs text-slate-700 hover:text-blue-900 font-medium">
                                                            <span class="truncate"><?= htmlspecialchars($sub['name']) ?></span>
                                                            <span class="text-[10px] text-slate-400 font-semibold"><?= (int)($sub['product_count'] ?? 0) ?></span>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="border-t border-slate-100 mt-2 pt-2">
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="block text-center text-blue-900 font-bold py-1.5 hover:underline text-[11px]">
                                        Browse All Products &rarr;
                                    </a>
                                </div>
                            </div>

                            <!-- Right: Desktop Subcategory Flyout Panel -->
                            <div class="hidden md:flex flex-col w-80 lg:w-96 p-4 bg-slate-50/70 border-l border-slate-100 max-h-[75vh] overflow-y-auto no-scrollbar" id="desktopFlyoutPanel">
                                <div class="flex items-center justify-between pb-2.5 border-b border-slate-200/80 mb-3">
                                    <div class="min-w-0 pr-2">
                                        <h4 id="flyoutCatTitle" class="font-bold text-sm text-slate-900 truncate">
                                            <?= htmlspecialchars($categories[0]['name'] ?? 'Categories') ?>
                                        </h4>
                                        <span id="flyoutSubcatCount" class="text-[11px] text-slate-500 font-medium">
                                            <?= !empty($categories[0]['subcategories']) ? count($categories[0]['subcategories']) . ' active subcategories' : 'Verified Manufacturing Hub' ?>
                                        </span>
                                    </div>
                                    <a id="flyoutCatAllLink" href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($categories[0]['slug'] ?? '') ?>" class="text-[11px] font-bold text-blue-700 hover:text-blue-900 hover:underline shrink-0 flex items-center gap-0.5">
                                        <span>View All</span>
                                        <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                                    </a>
                                </div>

                                <!-- Subcategories Container -->
                                <div id="flyoutSubcatList" class="flex-1 space-y-1.5">
                                    <!-- Rendered dynamically by JavaScript -->
                                </div>

                                <!-- Bottom Quick OEM / RFQ strip -->
                                <div class="mt-4 pt-3 border-t border-slate-200/80 flex items-center justify-between text-[11px] text-slate-600 bg-white p-2.5 rounded-xl border border-slate-100 shadow-2xs">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px] text-teal-600">verified</span>
                                        <span class="font-medium">Direct Factory Pricing</span>
                                    </div>
                                    <button type="button" onclick="openRfqModal()" class="font-bold text-blue-900 hover:underline cursor-pointer">
                                        Post RFQ &rarr;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="text-on-surface-variant hover:text-primary transition-colors shrink-0 font-medium">Products</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="text-on-surface-variant hover:text-primary transition-colors shrink-0 font-medium">Help Center</a>
                </nav>

                <div class="hidden lg:flex items-center gap-5 text-on-surface-variant normal-case shrink-0 text-xs">
                    <button type="button" onclick="openRfqModal()" class="flex items-center gap-1 hover:text-primary transition-colors font-medium cursor-pointer">
                        <span class="material-symbols-outlined text-[16px] text-secondary">request_quote</span>
                        <span>Request for Quotation</span>
                    </button>
                    <button type="button" onclick="openFastOemModal()" class="flex items-center gap-1 hover:text-primary transition-colors font-medium cursor-pointer">
                        <span class="material-symbols-outlined text-[16px] text-primary">bolt</span>
                        <span>Fast OEM Customization</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container with Top Padding for Fixed Header -->
    <main class="w-full flex-grow <?= !empty($impersonatorAdmin) ? 'pt-40' : 'pt-32' ?>">
        <?= $content ?>
    </main>

    <!-- Quick Factory RFQ Modal -->
    <div id="inquiry-modal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
        <div id="inquiry-modal-card" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col relative transform scale-95 transition-transform duration-200 overflow-hidden">
            <!-- Modal Header (Fixed) -->
            <div class="p-5 pb-3 border-b border-slate-100 flex-shrink-0 relative">
                <button type="button" onclick="closeInquiryModal()" class="absolute top-4 right-4 p-1.5 text-gray-400 hover:text-gray-700 rounded-lg bg-gray-100 transition-colors" title="Close">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
                <div class="flex items-center gap-2 mb-1.5 text-teal-700 font-semibold text-xs uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[18px]">verified</span>
                    <span>Direct Factory Inquiries (Zero Commission)</span>
                </div>
                <h3 id="modal-product-title" class="font-headline font-bold text-lg sm:text-xl text-on-surface line-clamp-1 pr-8">Send Factory Inquiry</h3>
                <p id="modal-supplier-name" class="text-xs text-on-surface-variant line-clamp-1 mt-0.5">Target: Sports Junction Certified Manufacturing Hub</p>
            </div>

            <!-- Form Wrapper with scrollable body and sticky footer -->
            <form id="publicInquiryForm" class="flex flex-col flex-1 overflow-hidden m-0" onsubmit="handleInquirySubmit(event)">
                <input type="hidden" name="product_id" id="modal-product-id" value="">
                <input type="hidden" name="supplier_id" id="modal-supplier-id" value="">
                <input type="hidden" name="enquiry_source" id="modal-enquiry-source" value="GENERAL">
                <input type="hidden" name="subject" id="modal-subject" value="">

                <!-- Scrollable Body -->
                <div class="p-5 overflow-y-auto flex-1 space-y-3.5">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Your Full Name *</label>
                            <input type="text" name="buyer_name" id="modal-buyer-name" required placeholder="John Doe" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Business Email *</label>
                            <input type="email" name="buyer_email" id="modal-buyer-email" required placeholder="Enter your business email" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Phone / WhatsApp</label>
                            <input type="text" name="buyer_phone" id="modal-buyer-phone" placeholder="Enter contact number" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Company Name</label>
                            <input type="text" name="company_name" id="modal-company-name" placeholder="Apex Imports LLC" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Country *</label>
                            <select name="country_id" id="modal-country-id" required class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                                <option value="">Select Country *</option>
                                <?php foreach ($allCountries as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= ($buyerCountryId === (int)$c['id'] || (empty($buyerCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                        <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">City / Location</label>
                            <input type="text" name="delivery_location" id="modal-delivery-location" placeholder="e.g., Delhi, India" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Target Order Qty *</label>
                        <input type="number" name="target_quantity" id="modal-qty" required min="1" value="100" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Custom Specifications & Packaging *</label>
                        <textarea name="specifications" id="modal-specs" required rows="3" placeholder="Specify custom logo requirements, colors, packaging boxes, or destination port for CIF quote..." class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Tech Pack / Specs File Attachment (Optional)</label>
                        <input type="file" name="attachment" id="modal-attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" class="w-full bg-surface-container-low px-3 py-1.5 text-on-surface rounded-lg text-xs outline-none focus:bg-white focus:ring-2 focus:ring-primary file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                        <div class="text-[11px] text-slate-500 mt-1">Accepted: PDF, Word (.doc, .docx), Images (.jpg, .png, .webp) &bull; Max 10MB</div>
                    </div>

                    <div class="p-3 bg-surface-container-low rounded-lg flex items-center justify-between text-xs text-on-surface-variant">
                        <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-teal-600">shield</span> Guaranteed Response</span>
                        <span class="font-mono text-primary font-bold">&lt; 2.4 Hours Avg.</span>
                    </div>
                </div>

                <!-- Sticky Footer -->
                <div class="p-4 border-t border-slate-100 bg-slate-50 flex-shrink-0">
                    <button type="submit" id="modal-submit-btn" class="w-full py-3 bg-blue-700 text-white rounded-xl font-bold hover:bg-blue-800 shadow-md transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        <span>Dispatch Direct Factory Inquiry</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dedicated Prototype Sample Order Modal -->
    <div id="sample-order-modal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
        <div id="sample-order-modal-card" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col relative transform scale-95 transition-transform duration-200 overflow-hidden">
            <!-- Modal Header (Fixed) -->
            <div class="p-5 pb-3 border-b border-slate-100 flex-shrink-0 relative">
                <button type="button" onclick="closeSampleOrderModal()" class="absolute top-4 right-4 p-1.5 text-gray-400 hover:text-gray-700 rounded-lg bg-gray-100 transition-colors" title="Close">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
                <div class="flex items-center gap-2 mb-1.5 text-teal-700 font-semibold text-xs uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[18px] text-teal-600">science</span>
                    <span>Order Prototype Sample (5-7 Days Dispatch)</span>
                </div>
                <h3 id="sample-modal-product-title" class="font-headline font-bold text-lg sm:text-xl text-on-surface line-clamp-1 pr-8">Request Prototype Sample</h3>
                <div class="flex items-center gap-3 text-xs text-on-surface-variant mt-1">
                    <span id="sample-modal-supplier-name" class="line-clamp-1 font-semibold text-slate-700">Target: Verified Indian Factory</span>
                    <span id="sample-modal-price-badge" class="px-2 py-0.5 rounded-full bg-teal-50 text-teal-800 font-bold hidden"></span>
                </div>
            </div>

            <!-- Form Wrapper with scrollable body and sticky footer -->
            <form id="publicSampleOrderForm" class="flex flex-col flex-1 overflow-hidden m-0" onsubmit="handleSampleOrderSubmit(event)">
                <input type="hidden" name="product_id" id="sample-modal-product-id" value="">
                <input type="hidden" name="supplier_id" id="sample-modal-supplier-id" value="">
                <input type="hidden" name="enquiry_source" value="SAMPLE">
                <input type="hidden" name="subject" id="sample-modal-subject" value="Order Prototype Sample">
                <input type="hidden" name="target_price" id="sample-modal-target-price" value="">

                <!-- Scrollable Body -->
                <div class="p-5 overflow-y-auto flex-1 space-y-3.5">
                    <div class="p-3 bg-teal-50 border border-teal-200 rounded-xl text-xs text-teal-900 flex items-start gap-2">
                        <span class="material-symbols-outlined text-teal-700 text-[18px] shrink-0 mt-0.5">local_shipping</span>
                        <div>
                            <span class="font-bold">Fast-Track Prototype Delivery:</span> Verified Indian manufacturers produce and dispatch physical evaluation samples within 5-7 business days with door-to-door courier tracking.
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Your Full Name *</label>
                            <input type="text" name="buyer_name" id="sample-modal-buyer-name" required value="<?= htmlspecialchars($authUser['name'] ?? '') ?>" placeholder="John Doe" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Business Email *</label>
                            <input type="email" name="buyer_email" id="sample-modal-buyer-email" required value="<?= htmlspecialchars($authUser['email'] ?? '') ?>" placeholder="Enter business email" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Phone / WhatsApp</label>
                            <input type="text" name="buyer_phone" id="sample-modal-buyer-phone" value="<?= htmlspecialchars($authUser['phone'] ?? '') ?>" placeholder="Enter contact number" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Company / Brand Name</label>
                            <input type="text" name="company_name" id="sample-modal-company-name" value="<?= htmlspecialchars($authUser['company_name'] ?? '') ?>" placeholder="Sports Brand LLC" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Destination Country *</label>
                            <select name="country_id" id="sample-modal-country-id" required class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all">
                                <option value="">Select Country *</option>
                                <?php foreach ($allCountries as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" <?= ($buyerCountryId === (int)$c['id'] || (empty($buyerCountryId) && $c['name'] === 'India')) ? 'selected' : '' ?>>
                                        <?= $c['flag'] ?? '' ?> <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Sample Quantity (Pcs) *</label>
                            <input type="number" name="target_quantity" id="sample-modal-qty" required min="1" max="50" value="1" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Delivery Shipping Address *</label>
                        <input type="text" name="delivery_location" id="sample-modal-delivery-location" required placeholder="Complete delivery address with postal / ZIP code for courier dispatch" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Prototype Testing Requirements / Notes *</label>
                        <textarea name="specifications" id="sample-modal-specs" required rows="3" placeholder="Describe required sample specs: size, colorways, material weights, testing protocols, custom logo embossing, or packaging requirements..." class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-teal-700 outline-none transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Artwork / Tech Pack (Optional)</label>
                        <input type="file" name="attachment" id="sample-modal-attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" class="w-full bg-surface-container-low px-3 py-1.5 text-on-surface rounded-lg text-xs outline-none focus:bg-white focus:ring-2 focus:ring-teal-700 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-800 hover:file:bg-teal-100 transition-all">
                        <div class="text-[11px] text-slate-500 mt-1">Accepted: PDF, Word (.doc, .docx), Images (.jpg, .png, .webp) &bull; Max 10MB</div>
                    </div>
                </div>

                <!-- Sticky Footer -->
                <div class="p-4 border-t border-slate-100 bg-slate-50 flex-shrink-0">
                    <button type="submit" id="sample-modal-submit-btn" class="w-full py-3 bg-teal-800 text-white rounded-xl font-bold hover:bg-teal-900 shadow-md transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">science</span>
                        <span>Submit Prototype Sample Request</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 bg-gray-900 text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
        <span class="material-symbols-outlined text-teal-400">check_circle</span>
        <span id="toast-text" class="text-sm font-semibold">Direct factory RFQ successfully transmitted!</span>
    </div>

    <!-- Footer: Clean 5-Column Responsive Layout -->
    <footer class="bg-[#030712] text-slate-300 border-t border-slate-900 mt-20 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Column Grid: 5 Dynamic Columns -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-8 lg:gap-8 pb-12">
                <?php
                    $footerSections = [
                        'ABOUT' => [
                            'title' => 'About Sports Junction',
                            'items' => $footerColumns['ABOUT'] ?? []
                        ],
                        'PROTECTION' => [
                            'title' => 'Buyer & Seller Protection',
                            'items' => $footerColumns['PROTECTION'] ?? []
                        ],
                        'HELP' => [
                            'title' => 'Help & Support',
                            'items' => $footerColumns['HELP'] ?? []
                        ],
                        'SOURCING' => [
                            'title' => 'Sourcing & Logistics',
                            'items' => $footerColumns['SOURCING'] ?? []
                        ],
                        'LEGAL' => [
                            'title' => 'Legal & Platform Policies',
                            'items' => $footerColumns['LEGAL'] ?? []
                        ]
                    ];
                ?>

                <?php foreach ($footerSections as $secKey => $section): ?>
                    <div>
                        <h4 class="font-headline font-bold text-xs sm:text-sm text-white mb-5 tracking-wider uppercase">
                            <?= htmlspecialchars($section['title']) ?>
                        </h4>
                        <ul class="space-y-3 text-xs text-slate-400">
                            <?php if (!empty($section['items'])): ?>
                                <?php foreach ($section['items'] as $p): ?>
                                    <?php
                                        $label = !empty($p['footer_label']) ? $p['footer_label'] : $p['title'];
                                        $rawUrl = trim((string)($p['external_url'] ?? ''));
                                        if (!empty($rawUrl)) {
                                            if (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://') || str_starts_with($rawUrl, '//')) {
                                                $targetUrl = $rawUrl;
                                            } else {
                                                $targetUrl = $baseUrl . '/' . ltrim($rawUrl, '/');
                                            }
                                        } else {
                                            $targetUrl = $baseUrl . '/' . ltrim($p['slug'], '/');
                                        }
                                        $isExternal = !empty($rawUrl) && (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://') || str_ends_with($rawUrl, '.xml'));
                                    ?>
                                    <li>
                                        <a href="<?= htmlspecialchars($targetUrl) ?>" <?= $isExternal ? 'target="_blank" rel="noopener noreferrer"' : '' ?> class="hover:text-white transition-colors block leading-relaxed">
                                            <?= htmlspecialchars($label) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Bottom Sub-Footer: Legal & Social Media in Dark Theme -->
            <div class="border-t border-slate-900 pt-8 pb-6 flex flex-col md:flex-row items-center justify-between text-[11px] text-slate-400 gap-4">
                <div>
                    <span>&copy; <?= date('Y') ?> Sports Junction | All rights reserved.</span>
                </div>

                <!-- Right: Stay Connected -->
                <?php 
                    $hasFacebook = !empty(trim($siteSettings['social_facebook'] ?? ''));
                    $hasTwitter = !empty(trim($siteSettings['social_twitter'] ?? ''));
                    $hasInstagram = !empty(trim($siteSettings['social_instagram'] ?? ''));
                    $hasAnySocial = $hasFacebook || $hasTwitter || $hasInstagram;
                ?>
                <?php if ($hasAnySocial): ?>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="font-headline font-bold text-xs text-white tracking-tight">Stay Connected</span>
                    <div class="flex items-center gap-2">
                        
                        <!-- Facebook -->
                        <?php if ($hasFacebook): ?>
                            <a href="<?= htmlspecialchars(trim($siteSettings['social_facebook'])) ?>" target="_blank" rel="noopener noreferrer" class="w-7 h-7 rounded-full bg-slate-900 hover:bg-blue-600 hover:text-white text-slate-400 border border-slate-800 flex items-center justify-center transition-all" title="Facebook" aria-label="Facebook">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                        <?php endif; ?>

                        <!-- Twitter / X -->
                        <?php if ($hasTwitter): ?>
                            <a href="<?= htmlspecialchars(trim($siteSettings['social_twitter'])) ?>" target="_blank" rel="noopener noreferrer" class="w-7 h-7 rounded-full bg-slate-900 hover:bg-white hover:text-black text-slate-400 border border-slate-800 flex items-center justify-center transition-all" title="X" aria-label="X">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        <?php endif; ?>

                        <!-- Instagram -->
                        <?php if ($hasInstagram): ?>
                            <a href="<?= htmlspecialchars(trim($siteSettings['social_instagram'])) ?>" target="_blank" rel="noopener noreferrer" class="w-7 h-7 rounded-full bg-slate-900 hover:bg-pink-600 hover:text-white text-slate-400 border border-slate-800 flex items-center justify-center transition-all" title="Instagram" aria-label="Instagram">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </footer>

    <!-- Interactive Scripts for Modal & Inquiries -->
    <script>
        const baseUrl = '<?= $baseUrl ?>';
        const navCategoriesData = <?= json_encode(array_values(array_map(function($c) {
            return [
                'name' => (string)($c['name'] ?? ''),
                'slug' => (string)($c['slug'] ?? ''),
                'subcategories' => array_values(array_map(function($s) {
                    return [
                        'name' => (string)($s['name'] ?? ''),
                        'slug' => (string)($s['slug'] ?? ''),
                        'product_count' => (int)($s['product_count'] ?? 0)
                    ];
                }, $c['subcategories'] ?? []))
            ];
        }, $categories ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function switchFlyoutCategory(index) {
            if (!navCategoriesData || !navCategoriesData[index]) return;
            const cat = navCategoriesData[index];

            // Update active highlight on category list
            const groups = document.querySelectorAll('.category-nav-group');
            groups.forEach((group, idx) => {
                const item = group.querySelector('.category-nav-item');
                if (!item) return;
                if (idx === index) {
                    item.classList.add('bg-blue-50/80', 'text-blue-900', 'font-bold');
                } else {
                    item.classList.remove('bg-blue-50/80', 'text-blue-900', 'font-bold');
                }
            });

            // Update flyout header
            const titleEl = document.getElementById('flyoutCatTitle');
            const countEl = document.getElementById('flyoutSubcatCount');
            const allLinkEl = document.getElementById('flyoutCatAllLink');
            const listEl = document.getElementById('flyoutSubcatList');

            if (titleEl) titleEl.textContent = cat.name;
            if (allLinkEl) allLinkEl.href = `${baseUrl}/category/${encodeURIComponent(cat.slug)}`;

            const subs = cat.subcategories || [];
            if (countEl) {
                countEl.textContent = subs.length > 0 ? `${subs.length} active subcategories` : 'Verified Manufacturing Hub';
            }

            if (!listEl) return;
            listEl.innerHTML = '';

            if (subs.length === 0) {
                listEl.innerHTML = `
                    <div class="py-8 text-center bg-white rounded-xl border border-slate-200/60 p-4">
                        <span class="material-symbols-outlined text-[32px] text-slate-300 mb-1">category</span>
                        <p class="text-xs text-slate-600 font-medium">Explore all verified products in ${escapeHtml(cat.name)}</p>
                        <a href="${baseUrl}/category/${encodeURIComponent(cat.slug)}" class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition-colors">
                            <span>Browse ${escapeHtml(cat.name)} Products</span>
                            <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                        </a>
                    </div>
                `;
                return;
            }

            subs.forEach(sub => {
                const link = document.createElement('a');
                link.href = `${baseUrl}/category/${encodeURIComponent(cat.slug)}/${encodeURIComponent(sub.slug)}`;
                link.className = 'flex items-center justify-between px-3 py-2.5 rounded-xl bg-white border border-slate-200/70 hover:border-blue-500 hover:bg-blue-50/60 hover:text-blue-900 text-xs font-semibold text-slate-700 transition-all shadow-2xs group';
                link.innerHTML = `
                    <span class="flex items-center gap-2 truncate">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 group-hover:scale-125 transition-transform shrink-0"></span>
                        <span class="truncate group-hover:translate-x-0.5 transition-transform">${escapeHtml(sub.name)}</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-bold bg-slate-100 group-hover:bg-blue-100 group-hover:text-blue-800 px-1.5 py-0.5 rounded-md shrink-0 ml-2">${sub.product_count}</span>
                `;
                listEl.appendChild(link);
            });
        }

        function toggleMobileSubcat(event, index) {
            event.stopPropagation();
            event.preventDefault();
            const drawer = document.getElementById(`mobileDrawer-${index}`);
            const chevron = document.getElementById(`mobileChevron-${index}`);
            if (!drawer) return;
            const isHidden = drawer.classList.contains('hidden');
            if (isHidden) {
                drawer.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            } else {
                drawer.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
            }
        }

        // Toggle All Categories Dropdown on Click/Tap
        document.addEventListener('DOMContentLoaded', function() {
            const catBtn = document.getElementById('allCategoriesNavBtn');
            const catMenu = document.getElementById('allCategoriesNavMenu');
            const catCaret = document.getElementById('allCategoriesNavCaret');
            const catContainer = document.getElementById('allCategoriesNavContainer');

            if (catBtn && catMenu) {
                catBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const isClosed = catMenu.classList.contains('hidden');
                    if (isClosed) {
                        catMenu.classList.remove('hidden');
                        if (catCaret) catCaret.classList.add('rotate-180');
                        switchFlyoutCategory(0);
                    } else {
                        catMenu.classList.add('hidden');
                        if (catCaret) catCaret.classList.remove('rotate-180');
                    }
                });

                document.addEventListener('click', function(e) {
                    if (catContainer && !catContainer.contains(e.target)) {
                        catMenu.classList.add('hidden');
                        if (catCaret) catCaret.classList.remove('rotate-180');
                    }
                });
            }
        });

        function showNotification(msg) {
            const toast = document.getElementById('toast');
            const toastText = document.getElementById('toast-text');
            if (!toast || !toastText) return;
            toastText.textContent = msg;
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 4500);
        }

        function openRfqModal() {
            const modal = document.getElementById('inquiry-modal');
            const card = document.getElementById('inquiry-modal-card');
            if (!modal || !card) return;

            document.getElementById('modal-product-title').textContent = 'Request for Quotation (RFQ)';
            document.getElementById('modal-supplier-name').textContent = 'Target: Sports Junction Verified Manufacturing Network';
            const qtyInput = document.getElementById('modal-qty');
            if (qtyInput) qtyInput.value = 500;
            document.getElementById('modal-product-id').value = '';
            document.getElementById('modal-supplier-id').value = '';
            document.getElementById('modal-enquiry-source').value = 'RFQ';
            document.getElementById('modal-subject').value = 'Request for Quotation (RFQ)';

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95');
            }, 10);
        }

        function openFastOemModal() {
            const modal = document.getElementById('inquiry-modal');
            const card = document.getElementById('inquiry-modal-card');
            if (!modal || !card) return;

            document.getElementById('modal-product-title').textContent = 'Fast OEM Customization Request';
            document.getElementById('modal-supplier-name').textContent = 'Target: Sports Junction OEM / ODM Custom Lab';
            const qtyInput = document.getElementById('modal-qty');
            if (qtyInput) qtyInput.value = 100;
            document.getElementById('modal-product-id').value = '';
            document.getElementById('modal-supplier-id').value = '';
            document.getElementById('modal-enquiry-source').value = 'FAST_OEM';
            document.getElementById('modal-subject').value = 'Fast OEM Customization Request';

            document.body.classList.add('overflow-hidden');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95');
            }, 10);
        }

        const sjCurrentSupplierId = <?= (int)$authSupplierId ?>;
        const sjCurrentUserRole = <?= json_encode($authUser['role'] ?? '') ?>;

        function openInquiryModal(productTitle, supplierName, defaultQty, productId, supplierId) {
            if (sjCurrentUserRole === 'SUPPLIER' && sjCurrentSupplierId > 0 && supplierId) {
                if (parseInt(supplierId) === sjCurrentSupplierId) {
                    alert('You cannot submit inquiries to your own supplier store or products.');
                    return;
                }
            }
            const modal = document.getElementById('inquiry-modal');
            const card = document.getElementById('inquiry-modal-card');
            if (!modal || !card) return;

            document.getElementById('modal-product-title').textContent = productTitle ? `Send Inquiry: ${productTitle}` : 'Send Factory Inquiry';
            document.getElementById('modal-supplier-name').textContent = supplierName ? `Target: ${supplierName}` : 'Target: Sports Junction Verified Pool';
            const qtyInput = document.getElementById('modal-qty');
            if (qtyInput) qtyInput.value = defaultQty || 100;

            document.getElementById('modal-product-id').value = productId || '';
            document.getElementById('modal-supplier-id').value = supplierId || '';
            document.getElementById('modal-enquiry-source').value = productId ? 'PRODUCT_PAGE' : 'GENERAL';
            document.getElementById('modal-subject').value = productTitle || 'General Bulk Requirement / RFQ';

            document.body.classList.add('overflow-hidden');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95');
            }, 10);
        }

        function closeInquiryModal() {
            const modal = document.getElementById('inquiry-modal');
            const card = document.getElementById('inquiry-modal-card');
            if (!modal || !card) return;

            document.body.classList.remove('overflow-hidden');
            card.classList.add('scale-95');
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        // Close on background click
        document.getElementById('inquiry-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeInquiryModal();
        });

        // Submit Inquiry via AJAX
        function handleInquirySubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = document.getElementById('modal-submit-btn');
            const origText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Transmitting RFQ...';

            const formData = new FormData(form);
            const countrySelect = document.getElementById('modal-country-id');
            if (countrySelect && countrySelect.selectedIndex >= 0) {
                const opt = countrySelect.options[countrySelect.selectedIndex];
                if (opt && opt.dataset.name) {
                    formData.set('country_name', opt.dataset.name);
                }
            }

            fetch(`${baseUrl}/api/inquiries`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
                if (data.success) {
                    closeInquiryModal();
                    form.reset();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'RFQ Transmitted!',
                            text: data.message || 'Direct factory RFQ successfully transmitted!',
                            confirmButtonColor: '#1d4ed8'
                        });
                    } else {
                        showNotification(data.message || 'Direct factory RFQ successfully transmitted!');
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Inquiry Error', data.message || 'Error sending inquiry. Please check your inputs.', 'error');
                    } else {
                        alert(data.message || 'Error sending inquiry. Please check your inputs.');
                    }
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Connection error. Please try again.', 'error');
                } else {
                    alert('Connection error. Please try again.');
                }
            });
        }

        function updateSortBadge(count) {
            const badges = [
                document.getElementById('topNavSortBadge'),
                document.getElementById('topNavFavouritesBadge')
            ];
            badges.forEach(badge => {
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            });
        }
        function updateFavouritesBadge(count) {
            updateSortBadge(count);
        }

        function handleBuyerLoginRequired(customMsg) {
            let currentPath = window.location.pathname + window.location.search;
            if (typeof baseUrl !== 'undefined' && baseUrl && currentPath.startsWith(baseUrl)) {
                currentPath = currentPath.substring(baseUrl.length) || '/';
            }
            const loginUrl = `${baseUrl}/login?redirect=${encodeURIComponent(currentPath)}`;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Buyer Login Required',
                    text: customMsg || 'Please sign in to your Buyer Account to shortlist products.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#1e3a8a',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sign In / Register',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = loginUrl;
                    }
                });
            } else {
                if (confirm((customMsg || 'Please sign in to your Buyer Account to shortlist products.') + '\n\nClick OK to go to the login page.')) {
                    window.location.href = loginUrl;
                }
            }
        }

        function toggleSortProduct(productId, btn) {
            if (sjCurrentUserRole !== 'BUYER' && sjCurrentUserRole !== 'SUPER_ADMIN') {
                handleBuyerLoginRequired('Buyer login is required to shortlist products.');
                return;
            }

            const csrfToken = '<?= htmlspecialchars(\App\Helpers\CSRF::token()) ?>';
            fetch(`${baseUrl}/api/buyer/sort-list/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    product_id: productId,
                    _csrf: csrfToken
                })
            })
            .then(res => {
                if (res.status === 401) {
                    return res.json().then(data => {
                        handleBuyerLoginRequired(data.message || 'Please sign in to your Buyer Account to shortlist products.');
                        return null;
                    });
                }
                return res.json();
            })
            .then(data => {
                if (!data) return;
                if (data.success) {
                    const isInList = !!(data.in_sort_list !== undefined ? data.in_sort_list : data.favourited);

                    // Sync all buttons on the page referencing this product
                    const matchingElements = document.querySelectorAll(`[data-product-sort-id="${productId}"]`);
                    matchingElements.forEach(el => {
                        const icon = el.querySelector('.material-symbols-outlined');
                        const textSpan = el.querySelector('.sort-btn-text');
                        const isFooter = el.classList.contains('sort-footer-btn');

                        if (isInList) {
                            if (isFooter) {
                                el.className = 'sort-footer-btn w-full py-2 text-center rounded-xl bg-blue-50 text-blue-900 border border-blue-400 text-xs font-bold transition-colors inline-flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs';
                                if (icon) {
                                    icon.textContent = 'check';
                                    icon.className = 'material-symbols-outlined text-[15px] text-blue-900 font-bold';
                                }
                                if (textSpan) textSpan.textContent = 'In Sort List';
                            } else {
                                el.classList.add('text-blue-900', 'border-blue-500', 'bg-blue-50');
                                el.classList.remove('text-slate-600', 'text-slate-400', 'border-slate-200');
                                if (icon) icon.classList.add('fill-1');
                                if (textSpan) textSpan.textContent = 'In Sort List';
                            }
                            el.setAttribute('title', 'In Sort List');
                        } else {
                            if (isFooter) {
                                el.className = 'sort-footer-btn w-full py-2 text-center rounded-xl bg-surface-container-low hover:bg-surface-container-high text-blue-900 text-xs font-bold transition-colors inline-flex items-center justify-center gap-1.5 cursor-pointer border border-slate-200/80';
                                if (icon) {
                                    icon.textContent = 'playlist_add';
                                    icon.className = 'material-symbols-outlined text-[15px] text-blue-900';
                                }
                                if (textSpan) textSpan.textContent = 'Shortlist';
                            } else {
                                el.classList.remove('text-blue-900', 'border-blue-500', 'bg-blue-50');
                                el.classList.add('text-slate-600', 'border-slate-200');
                                if (icon) icon.classList.remove('fill-1');
                                if (textSpan) textSpan.textContent = 'Add to Sort';
                            }
                            el.setAttribute('title', 'Add to Sort');
                        }
                    });

                    // Direct element fallback if not queried
                    if (btn && !btn.hasAttribute('data-product-sort-id')) {
                        const icon = btn.querySelector('.material-symbols-outlined');
                        const textSpan = btn.querySelector('.sort-btn-text');
                        if (isInList) {
                            btn.classList.add('text-blue-900', 'border-blue-500', 'bg-blue-50');
                            btn.classList.remove('text-slate-600');
                            if (icon) icon.classList.add('fill-1');
                            if (textSpan) textSpan.textContent = 'In Sort List';
                            btn.setAttribute('title', 'In Sort List');
                        } else {
                            btn.classList.remove('text-blue-900', 'border-blue-500', 'bg-blue-50');
                            btn.classList.add('text-slate-600');
                            if (icon) icon.classList.remove('fill-1');
                            if (textSpan) textSpan.textContent = 'Shortlist';
                            btn.setAttribute('title', 'Add to Sort');
                        }
                    }

                    if (data.count !== undefined) {
                        updateSortBadge(data.count);
                    }
                    if (typeof showNotification === 'function') {
                        showNotification(data.message);
                    }
                } else {
                    alert(data.message || 'Unable to update Sort List.');
                }
            })
            .catch(() => {
                alert('Connection error. Please try again.');
            });
        }
        function toggleFavouriteProduct(productId, btn) {
            toggleSortProduct(productId, btn);
        }

        function openSampleOrderModal(productId, productTitle, supplierName, supplierId, samplePrice) {
            if (sjCurrentUserRole === 'SUPPLIER' && sjCurrentSupplierId > 0 && supplierId) {
                if (parseInt(supplierId) === sjCurrentSupplierId) {
                    alert('You cannot request samples from your own supplier store or products.');
                    return;
                }
            }
            const modal = document.getElementById('sample-order-modal');
            const card = document.getElementById('sample-order-modal-card');
            if (!modal || !card) return;

            document.getElementById('sample-modal-product-title').textContent = productTitle ? `Prototype Sample: ${productTitle}` : 'Request Prototype Sample';
            document.getElementById('sample-modal-supplier-name').textContent = supplierName ? `Target: ${supplierName}` : 'Target: Verified Indian Factory';
            document.getElementById('sample-modal-product-id').value = productId || '';
            document.getElementById('sample-modal-supplier-id').value = supplierId || '';
            document.getElementById('sample-modal-subject').value = productTitle ? `Prototype Sample Order: ${productTitle}` : 'Order Prototype Sample';
            document.getElementById('sample-modal-target-price').value = samplePrice || '';

            const priceBadge = document.getElementById('sample-modal-price-badge');
            if (priceBadge) {
                if (samplePrice && parseFloat(samplePrice) > 0) {
                    priceBadge.textContent = `Sample Price: $${parseFloat(samplePrice).toFixed(2)}`;
                    priceBadge.classList.remove('hidden');
                } else {
                    priceBadge.textContent = 'Prototype Evaluation (Quote on Request)';
                    priceBadge.classList.remove('hidden');
                }
            }

            document.body.classList.add('overflow-hidden');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95');
            }, 10);
        }

        function closeSampleOrderModal() {
            const modal = document.getElementById('sample-order-modal');
            const card = document.getElementById('sample-order-modal-card');
            if (!modal || !card) return;

            document.body.classList.remove('overflow-hidden');
            card.classList.add('scale-95');
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        document.getElementById('sample-order-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeSampleOrderModal();
        });

        function handleSampleOrderSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = document.getElementById('sample-modal-submit-btn');
            const origText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Submitting Sample Order...';

            const formData = new FormData(form);
            formData.set('enquiry_source', 'SAMPLE');
            const countrySelect = document.getElementById('sample-modal-country-id');
            if (countrySelect && countrySelect.selectedIndex >= 0) {
                const opt = countrySelect.options[countrySelect.selectedIndex];
                if (opt && opt.dataset.name) {
                    formData.set('country_name', opt.dataset.name);
                }
            }

            fetch(`${baseUrl}/api/inquiries`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
                if (data.success) {
                    closeSampleOrderModal();
                    form.reset();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sample Request Submitted!',
                            text: data.message || 'Prototype sample request transmitted! The factory will review and arrange dispatch in 5-7 business days.',
                            confirmButtonColor: '#0f766e'
                        });
                    } else {
                        alert(data.message || 'Prototype sample request submitted successfully!');
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Failed',
                            text: data.message || 'Failed to submit prototype sample request. Please check inputs and try again.',
                            confirmButtonColor: '#0f766e'
                        });
                    } else {
                        alert(data.message || 'Failed to submit prototype sample request.');
                    }
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
                alert('A network or server error occurred. Please try again.');
            });
        }

        // Google Website Translation Controller
        function getCookie(name) {
            const v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
            return v ? v[2] : null;
        }

        function updateLangUI(lang) {
            const upper = (lang || 'en').toUpperCase();
            const labelEl = document.getElementById('currentLangCode');
            if (labelEl) labelEl.textContent = upper;

            document.querySelectorAll('.lang-opt-btn').forEach(btn => {
                const blang = btn.getAttribute('data-lang');
                const check = btn.querySelector('.lang-check');
                if (blang === lang) {
                    btn.classList.add('bg-blue-50', 'text-blue-900', 'font-bold');
                    if (check) check.classList.remove('hidden');
                } else {
                    btn.classList.remove('bg-blue-50', 'text-blue-900', 'font-bold');
                    if (check) check.classList.add('hidden');
                }
            });
        }

        function setSiteLanguage(lang) {
            const domain = window.location.hostname;
            if (lang === 'en') {
                // Reset translation to original English
                document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + domain + ';';
                document.cookie = 'googtrans=/en/en; path=/;';
                document.cookie = 'googtrans=/en/en; path=/; domain=' + domain + ';';
            } else {
                const val = '/en/' + lang;
                document.cookie = 'googtrans=' + val + '; path=/;';
                document.cookie = 'googtrans=' + val + '; path=/; domain=' + domain + ';';
            }
            updateLangUI(lang);
            window.location.reload();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const cookie = getCookie('googtrans');
            let currentLang = 'en';
            if (cookie) {
                const parts = cookie.split('/');
                if (parts.length >= 3 && parts[2]) {
                    currentLang = parts[2];
                }
            }
            updateLangUI(currentLang);

            const toggleBtn = document.getElementById('btnGoogleTranslateToggle');
            const dropdown = document.getElementById('googleTranslateDropdown');
            if (toggleBtn && dropdown) {
                toggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdown.classList.toggle('hidden');
                });
                document.addEventListener('click', (e) => {
                    if (!dropdown.contains(e.target) && !toggleBtn.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
        });
    </script>
    <div id="google_translate_element" style="display:none;"></div>
    <script type="text/javascript">
    function googleTranslateElementInit() {
        if (window.protectUiElementsFromTranslation) {
            window.protectUiElementsFromTranslation(document);
        }
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,hi,es,fr,de,ar,zh-CN,ja,ru,pt,it',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async></script>
    <?php require_once dirname(__DIR__) . '/components/autoload_popup.php'; ?>
    <?php require_once dirname(__DIR__) . '/components/chat_popup.php'; ?>
</body>
</html>
