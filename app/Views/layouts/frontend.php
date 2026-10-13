<?php
declare(strict_types=1);

use App\Core\Response;
$baseUrl = Response::baseUrl();
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sports Junction — India\'s Sports Manufacturing Marketplace') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Direct factory wholesale marketplace connecting international buyers with verified Indian sports manufacturers.') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
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
        }
        .material-symbols-outlined.fill-1 {
            font-variation-settings: 'FILL' 1;
        }
    </style>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex flex-col min-h-screen">

    <!-- Global Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm border-b border-surface-container">
        <!-- Top Utility & Search Bar -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="flex items-center shrink-0 group" title="Sports Junction — India's Sports Manufacturing Marketplace">
                <img src="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg" alt="Sports Junction — India's Sports Manufacturing Marketplace" class="h-12 sm:h-14 w-auto object-contain max-w-[210px] sm:max-w-[240px]">
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
            <div class="flex items-center gap-3 shrink-0 text-sm font-semibold">
                <?php if ($authUser): ?>
                    <?php if (($authUser['role'] ?? '') === 'BUYER'): ?>
                        <div class="hidden sm:flex items-center gap-3">
                            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/favourites" class="text-on-surface hover:text-rose-600 transition-colors flex items-center gap-1" title="Saved Favourites">
                                <span class="material-symbols-outlined text-[18px]">favorite</span>
                                <span class="hidden lg:inline text-xs font-bold">Favourites</span>
                            </a>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/buyer/enquiries" class="text-on-surface hover:text-primary transition-colors flex items-center gap-1" title="My Enquiries">
                                <span class="material-symbols-outlined text-[18px]">request_quote</span>
                                <span class="hidden lg:inline text-xs font-bold">Enquiries</span>
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
                <div class="hidden md:flex items-center gap-1 text-on-surface-variant hover:text-on-surface cursor-pointer py-1 pl-2">
                    <span class="material-symbols-outlined text-[18px]">language</span>
                    <span class="text-xs font-semibold">IN (₹)</span>
                </div>
            </div>
        </div>

        <!-- Secondary Categories Navigation Bar -->
        <div class="w-full bg-surface-container-lowest border-t border-surface-container">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-11 text-xs uppercase tracking-wider font-semibold">
                <nav class="flex items-center gap-6 overflow-x-auto py-2 scrollbar-none">
                    <!-- Dropdown Trigger -->
                    <div class="relative group">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="flex items-center gap-1.5 text-primary font-bold hover:text-primary-container transition-colors shrink-0">
                            <span class="material-symbols-outlined text-[16px]">menu</span>
                            <span>ALL CATEGORIES</span>
                            <span class="material-symbols-outlined text-[14px]">expand_more</span>
                        </a>
                        <!-- Mega Menu Dropdown -->
                        <div class="absolute top-full left-0 w-64 bg-white rounded-xl shadow-xl border border-surface-container p-2 hidden group-hover:block z-50">
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $catItem): ?>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($catItem['slug']) ?>" class="flex items-center justify-between px-3 py-2 rounded-lg text-on-surface hover:bg-surface-container-low hover:text-primary text-xs font-semibold transition-all">
                                        <span class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px] text-primary"><?= htmlspecialchars($catItem['icon'] ?? 'sports_tennis') ?></span>
                                            <span><?= htmlspecialchars($catItem['name']) ?></span>
                                        </span>
                                        <span class="material-symbols-outlined text-[14px] text-outline">chevron_right</span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="border-t border-surface-container mt-1 pt-1">
                                <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="block text-center text-primary font-bold py-1.5 hover:underline text-[11px]">
                                    Browse All Products &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="<?= htmlspecialchars($baseUrl) ?>/products?verified=1" class="text-on-surface-variant hover:text-primary transition-colors shrink-0 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-teal-600">verified</span>
                        <span>VERIFIED FACTORIES</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="text-on-surface-variant hover:text-primary transition-colors shrink-0">ALL PRODUCTS</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/category/sports-and-fitness" class="text-on-surface-variant hover:text-primary transition-colors shrink-0">FITNESS & GYM</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/category/cricket-and-field-sports" class="text-on-surface-variant hover:text-primary transition-colors shrink-0">CRICKET HUB</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="text-on-surface-variant hover:text-primary transition-colors shrink-0">CONTACT US</a>
                </nav>

                <div class="hidden lg:flex items-center gap-5 text-on-surface-variant normal-case shrink-0 text-xs">
                    <button type="button" onclick="openInquiryModal('Direct Sourcing Request', 'Sports Junction Verified Hub', 100)" class="flex items-center gap-1 hover:text-primary transition-colors font-medium">
                        <span class="material-symbols-outlined text-[16px] text-secondary">request_quote</span>
                        <span>Request for Quotation</span>
                    </button>
                    <button type="button" onclick="openInquiryModal('Custom OEM Inquiry', 'Sports Junction Custom Lab', 50)" class="flex items-center gap-1 hover:text-primary transition-colors font-medium">
                        <span class="material-symbols-outlined text-[16px] text-primary">bolt</span>
                        <span>Fast OEM Customization</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container with Top Padding for Fixed Header -->
    <main class="w-full flex-grow pt-32">
        <?= $content ?>
    </main>

    <!-- Quick Factory RFQ Modal -->
    <div id="inquiry-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
        <div id="inquiry-modal-card" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 relative transform scale-95 transition-transform duration-200">
            <button type="button" onclick="closeInquiryModal()" class="absolute top-4 right-4 p-1.5 text-gray-400 hover:text-gray-700 rounded-lg bg-gray-100 transition-colors" title="Close">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
            <div class="flex items-center gap-2 mb-2 text-teal-700 font-semibold text-xs uppercase tracking-wider">
                <span class="material-symbols-outlined text-[18px]">verified</span>
                <span>Direct Factory Inquiries (Zero Commission)</span>
            </div>
            <h3 id="modal-product-title" class="font-headline font-bold text-xl text-on-surface mb-1">Send Factory Inquiry</h3>
            <p id="modal-supplier-name" class="text-xs text-on-surface-variant mb-4">Target: Sports Junction Certified Manufacturing Hub</p>

            <form id="publicInquiryForm" class="space-y-3" onsubmit="handleInquirySubmit(event)">
                <input type="hidden" name="product_id" id="modal-product-id" value="">
                <input type="hidden" name="supplier_id" id="modal-supplier-id" value="">
                <input type="hidden" name="enquiry_source" id="modal-enquiry-source" value="GENERAL">
                <input type="hidden" name="subject" id="modal-subject" value="">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Your Full Name *</label>
                        <input type="text" name="buyer_name" id="modal-buyer-name" required placeholder="John Doe" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Business Email *</label>
                        <input type="email" name="buyer_email" id="modal-buyer-email" required placeholder="buyer@company.com" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Phone / WhatsApp</label>
                        <input type="text" name="buyer_phone" id="modal-buyer-phone" placeholder="+1 555-0199" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Company Name</label>
                        <input type="text" name="company_name" id="modal-company-name" placeholder="Apex Imports LLC" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Target Order Qty *</label>
                        <input type="number" name="target_quantity" id="modal-qty" required min="1" value="100" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Delivery Location / City</label>
                        <input type="text" name="delivery_location" id="modal-delivery-location" placeholder="e.g., Delhi, India" class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Custom Specifications & Packaging *</label>
                    <textarea name="specifications" id="modal-specs" required rows="3" placeholder="Specify custom logo requirements, colors, packaging boxes, or destination port for CIF quote..." class="w-full bg-surface-container-low px-3 py-2 text-on-surface rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all"></textarea>
                </div>

                <div class="p-3 bg-surface-container-low rounded-lg flex items-center justify-between text-xs text-on-surface-variant">
                    <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-teal-600">shield</span> Guaranteed Response</span>
                    <span class="font-mono text-primary font-bold">&lt; 2.4 Hours Avg.</span>
                </div>

                <button type="submit" id="modal-submit-btn" class="w-full py-3 bg-blue-700 text-white rounded-xl font-bold hover:bg-blue-800 shadow-md transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    <span>Dispatch Direct Factory Inquiry</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 bg-gray-900 text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
        <span class="material-symbols-outlined text-teal-400">check_circle</span>
        <span id="toast-text" class="text-sm font-semibold">Direct factory RFQ successfully transmitted!</span>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-950 text-gray-400 border-t border-gray-800 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-10">
                <!-- Col 1: Brand Info -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg" alt="Sports Junction Logo" class="h-10 w-auto object-contain bg-white rounded-lg p-1 shadow-sm">
                    </div>
                    <p class="text-xs leading-relaxed text-gray-400 max-w-sm">
                        India's premier B2B sports manufacturing marketplace connecting international importers, sports brands, academies, and distributors directly with audited production facilities in Meerut, Jalandhar, and Gujarat.
                    </p>
                    <div class="flex items-center gap-3 text-xs text-gray-300">
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-teal-400">verified</span> ISO 9001:2015</span>
                        <span class="text-gray-600">•</span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-teal-400">verified</span> BSCI Audited</span>
                        <span class="text-gray-600">•</span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-teal-400">lock</span> Zero Commission</span>
                    </div>
                </div>

                <!-- Col 2: Categories -->
                <div>
                    <h4 class="text-white font-headline font-bold text-xs uppercase tracking-wider mb-4">Top Sectors</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/category/cricket-and-field-sports" class="hover:text-white transition-colors">Cricket Bats & Pads</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/category/football-and-inflatables" class="hover:text-white transition-colors">Match Footballs</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/category/agility-and-speed-training" class="hover:text-white transition-colors">Agility Hurdles & Ladders</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/category/sports-and-fitness" class="hover:text-white transition-colors">Commercial Gym Equipment</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/category/apparel-and-accessories" class="hover:text-white transition-colors">Sportswear & Uniforms</a></li>
                    </ul>
                </div>

                <!-- Col 3: Sourcing Solutions -->
                <div>
                    <h4 class="text-white font-headline font-bold text-xs uppercase tracking-wider mb-4">Sourcing Solutions</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/products" class="hover:text-white transition-colors">Direct Factory Catalog</a></li>
                        <li><a href="#" onclick="openInquiryModal('Custom Wholesale RFP', 'Sports Junction Pool', 500); return false;" class="hover:text-white transition-colors">Request for Quotation (RFQ)</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="hover:text-white transition-colors">Contact Us & Support</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Container Freight Logistics</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/sitemap.xml" target="_blank" class="hover:text-white transition-colors">XML Sitemap</a></li>
                    </ul>
                </div>

                <!-- Col 4: Platform -->
                <div>
                    <h4 class="text-white font-headline font-bold text-xs uppercase tracking-wider mb-4">Platform & Admin</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/admin/login" class="text-teal-400 hover:underline font-semibold">Super Admin Portal &rarr;</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="hover:text-white transition-colors">Master CRUD Management</a></li>
                        <li><a href="<?= htmlspecialchars($baseUrl) ?>/admin/products" class="hover:text-white transition-colors">Product Publishing Hub</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms & Manufacturing SLA</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 gap-4">
                <div>
                    &copy; <?= date('Y') ?> Sports Junction Private Limited. All rights reserved. India's Sports Manufacturing Marketplace.
                </div>
                <div class="flex items-center gap-4">
                    <span class="hover:text-gray-400 cursor-pointer">Privacy Policy</span>
                    <span>•</span>
                    <span class="hover:text-gray-400 cursor-pointer">Terms of Sourcing</span>
                    <span>•</span>
                    <span class="hover:text-gray-400 cursor-pointer">Export Compliance</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts for Modal & Inquiries -->
    <script>
        const baseUrl = '<?= $baseUrl ?>';

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

        function openInquiryModal(productTitle, supplierName, defaultQty, productId, supplierId) {
            const modal = document.getElementById('inquiry-modal');
            const card = document.getElementById('inquiry-modal-card');
            if (!modal || !card) return;

            document.getElementById('modal-product-title').textContent = productTitle ? `Send Inquiry: ${productTitle}` : 'Send Factory Inquiry';
            document.getElementById('modal-supplier-name').textContent = supplierName ? `Target: ${supplierName}` : 'Target: Sports Junction Verified Pool';
            document.getElementById('modal-qty').value = defaultQty || 100;

            document.getElementById('modal-product-id').value = productId || '';
            document.getElementById('modal-supplier-id').value = supplierId || '';
            document.getElementById('modal-enquiry-source').value = productId ? 'PRODUCT_PAGE' : 'GENERAL';
            document.getElementById('modal-subject').value = productTitle || 'General Bulk Requirement / RFQ';

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
                    showNotification(data.message || 'Direct factory RFQ successfully transmitted!');
                } else {
                    alert(data.message || 'Error sending inquiry. Please check your inputs.');
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
                alert('Connection error. Please try again.');
            });
        }

        function toggleFavouriteProduct(productId, btn) {
            fetch(`${baseUrl}/api/buyer/favourites/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': '<?= htmlspecialchars(\App\Helpers\CSRF::token()) ?>'
                },
                body: JSON.stringify({
                    product_id: productId,
                    _csrf: '<?= htmlspecialchars(\App\Helpers\CSRF::token()) ?>'
                })
            })
            .then(res => {
                if (res.status === 401 || res.status === 403) {
                    window.location.href = `${baseUrl}/login`;
                    return null;
                }
                return res.json();
            })
            .then(data => {
                if (!data) return;
                if (data.success) {
                    const icon = btn.querySelector('.material-symbols-outlined');
                    if (data.favourited) {
                        btn.classList.add('text-rose-600', 'border-rose-200', 'bg-rose-50');
                        if (icon) icon.classList.add('fill-1');
                    } else {
                        btn.classList.remove('text-rose-600', 'border-rose-200', 'bg-rose-50');
                        if (icon) icon.classList.remove('fill-1');
                    }
                    showNotification(data.message);
                } else {
                    alert(data.message || 'Unable to update favourite.');
                }
            })
            .catch(() => {
                alert('Connection error. Please try again.');
            });
        }
    </script>
</body>
</html>
