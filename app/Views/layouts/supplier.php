<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$user = Session::get('auth_user') ?? [];
$supplier = Session::get('auth_supplier') ?? [];
$flashSuccess = Session::flash('success');
$flashError = Session::flash('error');

$supplierUnreadCount = 0;
if (!empty($user['id'])) {
    try {
        $supplierUnreadCount = \App\Models\ChatConversation::getTotalUnreadCountForUser((int)$user['id'], 'SUPPLIER');
    } catch (\Throwable $e) {
        $supplierUnreadCount = 0;
    }
}
$isMessagesPage = strpos($_SERVER['REQUEST_URI'] ?? '', '/supplier/messages') !== false;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Supplier Portal — Sports Junction') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/favicon.png">
    <link rel="shortcut icon" href="<?= htmlspecialchars($baseUrl) ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    <!-- Global Namespace & App Config -->
    <script>
        window.SportsJunction = window.SportsJunction || {};
        window.SportsJunction.baseUrl = '<?= htmlspecialchars($baseUrl) ?>';
        window.SportsJunction.csrfToken = '<?= htmlspecialchars(\App\Helpers\CSRF::token()) ?>';
        window.baseUrl = window.SportsJunction.baseUrl;
        window.csrfToken = window.SportsJunction.csrfToken;
        var baseUrl = window.baseUrl;
        var csrfToken = window.csrfToken;
    </script>

    <!-- Production Compiled Tailwind CSS Stylesheet (Standalone Local File, 0ms FOUC, Adblocker Proof) -->
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/tailwind.css">

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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#00288e",
                        "on-primary": "#ffffff",
                        secondary: "#006a61",
                        surface: "#f8f9ff",
                        "on-surface": "#0b1c30",
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
        }
    </style>
</head>
<body class="min-h-full flex flex-col font-sans bg-slate-50 text-slate-900 antialiased">
    <?php $impersonatorAdmin = Session::get('impersonator_admin'); ?>
    <?php if (!empty($impersonatorAdmin)): ?>
        <div class="bg-amber-500 text-slate-950 px-4 py-2 text-xs font-bold flex items-center justify-between shadow-md z-50 sticky top-0 border-b border-amber-600">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
                <span>IMPERSONATION MODE: Currently viewing as <strong><?= htmlspecialchars($user['name'] ?? 'Seller') ?></strong> (<?= htmlspecialchars($user['role'] ?? 'SUPPLIER') ?> &bull; <?= htmlspecialchars($user['email'] ?? '') ?>)</span>
            </div>
            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/exit-impersonation" class="px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">logout</span>
                <span>Return to Super Admin</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Supplier Top Header -->
    <header class="w-full bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-4 lg:gap-6 min-w-0">
                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="flex items-center gap-2 shrink-0" title="Sports Junction Supplier Partner Portal">
                    <img src="<?= htmlspecialchars(\App\Models\SiteSetting::getLogoUrl()) ?>" alt="Sports Junction" class="h-8 sm:h-10 w-auto object-contain max-w-[150px] sm:max-w-[170px]" onerror="this.onerror=null;this.src='<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.png';">
                    <span class="hidden sm:inline text-[10px] font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-full ml-1">Supplier Portal</span>
                </a>

                <!-- Desktop Navigation Links (Always visible from md up) -->
                <?php
                $currentUri = $_SERVER['REQUEST_URI'] ?? '';
                $isDashboard = str_contains($currentUri, '/supplier/dashboard');
                $isProducts = str_contains($currentUri, '/supplier/products');
                $isEnquiries = str_contains($currentUri, '/supplier/enquiries');
                $isSamples = str_contains($currentUri, '/supplier/sample-enquiries');
                $isMessages = str_contains($currentUri, '/supplier/messages');
                ?>
                <nav class="hidden md:flex items-center gap-1 lg:gap-3 text-xs font-bold uppercase tracking-wider text-slate-600">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg transition-colors <?= $isDashboard ? 'text-blue-900 bg-blue-50/80 font-black' : 'hover:text-blue-900 hover:bg-slate-50' ?>">
                        <span class="material-symbols-outlined text-[17px]">dashboard</span>
                        <span>Dashboard</span>
                    </a>
                    
                    <!-- Catalogue Dropdown -->
                    <div class="relative" id="supplierCatalogueMenu">
                        <button type="button" id="btnSupplierCatalogue" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg transition-colors focus:outline-none cursor-pointer <?= $isProducts ? 'text-blue-900 bg-blue-50/80 font-black' : 'hover:text-blue-900 hover:bg-slate-50' ?>">
                            <span class="material-symbols-outlined text-[17px]">inventory_2</span>
                            <span>Catalogue</span>
                            <span class="material-symbols-outlined text-[15px] text-slate-400 transition-transform" id="catalogueCaret">expand_more</span>
                        </button>
                        <div id="dropdownCatalogueMenu" class="absolute left-0 top-full pt-1 w-48 hidden z-50">
                            <div class="bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 overflow-hidden">
                                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/add" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-teal-50 hover:text-teal-800 transition-colors">
                                    <span class="material-symbols-outlined text-[18px] text-teal-600">add_circle</span>
                                    <span>Add Products</span>
                                </a>
                                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-teal-50 hover:text-teal-800 transition-colors">
                                    <span class="material-symbols-outlined text-[18px] text-teal-600">inventory_2</span>
                                    <span>View Products</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/enquiries" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg transition-colors <?= $isEnquiries ? 'text-blue-900 bg-blue-50/80 font-black' : 'hover:text-blue-900 hover:bg-slate-50' ?>">
                        <span class="material-symbols-outlined text-[17px]">request_quote</span>
                        <span>Enquiries</span>
                    </a>

                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/sample-enquiries" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg transition-colors <?= $isSamples ? 'text-teal-800 bg-teal-50/80 font-black' : 'hover:text-teal-800 hover:bg-teal-50/50 text-slate-600' ?>">
                        <span class="material-symbols-outlined text-[17px] text-teal-600">science</span>
                        <span>Samples</span>
                    </a>
                    
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-colors relative <?= $isMessages ? 'text-teal-800 bg-teal-50/80 font-black' : 'hover:text-teal-800 hover:bg-teal-50/50 text-slate-700 font-extrabold' ?>">
                        <span class="material-symbols-outlined text-[18px] text-teal-700">chat</span>
                        <span>Messages</span>
                        <?php if ($supplierUnreadCount > 0): ?>
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-black leading-none text-white bg-red-600 rounded-full shadow-xs animate-pulse">
                                <?= $supplierUnreadCount > 99 ? '99+' : $supplierUnreadCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </nav>
            </div>

            <!-- User Menu & Mobile Toggle -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/profile" class="hidden sm:flex flex-col text-right group hover:opacity-90 transition cursor-pointer" title="Manage Supplier Profile">
                    <span class="text-xs font-bold text-slate-900 group-hover:text-teal-800 transition flex items-center justify-end gap-1">
                        <?= htmlspecialchars($supplier['company_name'] ?? $user['name'] ?? 'Supplier') ?>
                        <span class="material-symbols-outlined text-[15px] text-slate-400 group-hover:text-teal-700">account_circle</span>
                    </span>
                    <span class="text-[10px] font-semibold text-teal-700 group-hover:underline">Verified Manufacturer</span>
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/logout" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold transition-colors">
                    Logout
                </a>

                <!-- Mobile Hamburger Button (< md) -->
                <button type="button" id="btnToggleSupplierMobileNav" class="md:hidden p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 transition" aria-label="Toggle Supplier Menu">
                    <span class="material-symbols-outlined text-[22px]">menu</span>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Panel -->
        <div id="supplierMobileNavPanel" class="hidden md:hidden bg-white border-t border-slate-200 px-4 py-3 space-y-1 text-sm font-semibold shadow-lg">
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-blue-900 <?= $isDashboard ? 'bg-blue-50 text-blue-900 font-bold' : '' ?>">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-blue-900 <?= $isProducts ? 'bg-blue-50 text-blue-900 font-bold' : '' ?>">
                <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                <span>View Products</span>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/add" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-blue-900">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Add Product</span>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/enquiries" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-blue-900 <?= $isEnquiries ? 'bg-blue-50 text-blue-900 font-bold' : '' ?>">
                <span class="material-symbols-outlined text-[18px]">request_quote</span>
                <span>Customer Enquiries</span>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/sample-enquiries" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-teal-800 <?= $isSamples ? 'bg-teal-50 text-teal-800 font-bold' : '' ?>">
                <span class="material-symbols-outlined text-[18px] text-teal-600">science</span>
                <span>Prototype Samples</span>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/messages" class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-teal-800 <?= $isMessages ? 'bg-teal-50 text-teal-800 font-bold' : '' ?>">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-teal-600">chat</span>
                    <span>Messages & Deals</span>
                </div>
                <?php if ($supplierUnreadCount > 0): ?>
                    <span class="px-2 py-0.5 text-xs font-black bg-red-600 text-white rounded-full">
                        <?= $supplierUnreadCount > 99 ? '99+' : $supplierUnreadCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/profile" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-blue-900">
                <span class="material-symbols-outlined text-[18px]">account_circle</span>
                <span>Supplier Profile</span>
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 <?= $isMessagesPage ? 'py-2 sm:py-3' : 'py-8' ?>">
        <?php if ($flashSuccess): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2.5 shadow-xs">
                <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
                <div><?= htmlspecialchars($flashSuccess) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm flex items-center gap-2.5 shadow-xs">
                <span class="material-symbols-outlined text-red-600 text-[20px]">error</span>
                <div><?= htmlspecialchars($flashError) ?></div>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <?php if (!$isMessagesPage): ?>
    <footer class="w-full py-6 border-t border-slate-200 bg-white text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> Sports Junction Supplier Portal. All product additions and modifications undergo strict Super Admin audit.
    </footer>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const catMenu = document.getElementById('supplierCatalogueMenu');
        const catBtn = document.getElementById('btnSupplierCatalogue');
        const catDropdown = document.getElementById('dropdownCatalogueMenu');
        const catCaret = document.getElementById('catalogueCaret');

        if (catMenu && catDropdown) {
            let closeTimeout;

            function openMenu() {
                clearTimeout(closeTimeout);
                catDropdown.classList.remove('hidden');
                if (catCaret) catCaret.classList.add('rotate-180');
            }

            function closeMenu() {
                closeTimeout = setTimeout(() => {
                    catDropdown.classList.add('hidden');
                    if (catCaret) catCaret.classList.remove('rotate-180');
                }, 150);
            }

            catMenu.addEventListener('mouseenter', openMenu);
            catMenu.addEventListener('mouseleave', closeMenu);

            if (catBtn) {
                catBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const isHidden = catDropdown.classList.contains('hidden');
                    if (isHidden) {
                        openMenu();
                    } else {
                        catDropdown.classList.add('hidden');
                        if (catCaret) catCaret.classList.remove('rotate-180');
                    }
                });
            }

            document.addEventListener('click', function (e) {
                if (!catMenu.contains(e.target)) {
                    catDropdown.classList.add('hidden');
                    if (catCaret) catCaret.classList.remove('rotate-180');
                }
            });
        }

        // Mobile Nav Toggle
        const mobileToggleBtn = document.getElementById('btnToggleSupplierMobileNav');
        const mobileNavPanel = document.getElementById('supplierMobileNavPanel');
        if (mobileToggleBtn && mobileNavPanel) {
            mobileToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                mobileNavPanel.classList.toggle('hidden');
            });
            document.addEventListener('click', function(e) {
                if (!mobileNavPanel.contains(e.target) && !mobileToggleBtn.contains(e.target)) {
                    mobileNavPanel.classList.add('hidden');
                }
            });
        }
    });
    </script>
</body>
</html>
