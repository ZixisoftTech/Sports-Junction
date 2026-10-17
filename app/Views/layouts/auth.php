<?php
declare(strict_types=1);

use App\Core\Response;
$baseUrl = Response::baseUrl();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sports Junction — India\'s Sports Manufacturing Marketplace') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/favicon.png">
    <link rel="shortcut icon" href="<?= htmlspecialchars($baseUrl) ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
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
                        "primary-hover": "#001f6e",
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
        /* Autofill subtle styling */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px white inset !important;
            -webkit-text-fill-color: #0b1c30 !important;
        }
    </style>
</head>
<body class="min-h-full flex flex-col font-sans bg-slate-50 text-on-surface antialiased">
    <!-- Top Utility Navigation -->
    <header class="w-full bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="<?= htmlspecialchars($baseUrl) ?>/" class="flex items-center shrink-0 group" title="Sports Junction — India's Sports Manufacturing Marketplace">
                <img src="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.png" alt="Sports Junction Logo" class="h-10 sm:h-11 w-auto object-contain max-w-[200px]">
            </a>

            <!-- Right Links -->
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-600">
                <a href="<?= htmlspecialchars($baseUrl) ?>/" class="hover:text-blue-900 transition-colors hidden sm:inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">storefront</span>
                    <span>Marketplace</span>
                </a>
                <span class="text-slate-300 hidden sm:inline">|</span>
                <div class="flex items-center gap-1.5 text-slate-500">
                    <span class="material-symbols-outlined text-[16px]">support_agent</span>
                    <span>Help Center</span>
                </div>
                <span class="text-slate-300 hidden md:inline">|</span>
                <span class="text-slate-500 font-medium hidden md:inline">Enterprise Procurement Desk</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Background subtle glow -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-gradient-to-tr from-blue-100/50 via-teal-50/40 to-indigo-100/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
        
        <?= $content ?>
    </main>

    <!-- Global Footer -->
    <footer class="w-full py-6 border-t border-slate-200/80 bg-white/50 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-4 text-[11px] font-medium text-slate-500">
                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-teal-600">verified</span> ISO 9001:2015 Verified Protocol</span>
                <span>•</span>
                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-blue-700">lock</span> 256-Bit SSL Data Security</span>
            </div>
            <p>&copy; <?= date('Y') ?> Sports Junction B2B Marketplace. Direct Factory Wholesale Network.</p>
        </div>
    </footer>
</body>
</html>
