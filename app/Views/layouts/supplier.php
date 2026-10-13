<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$user = Session::get('auth_user') ?? [];
$supplier = Session::get('auth_supplier') ?? [];
$flashSuccess = Session::flash('success');
$flashError = Session::flash('error');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Supplier Portal — Sports Junction') ?></title>
    
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
    <!-- Supplier Top Header -->
    <header class="w-full bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="flex items-center gap-2" title="Sports Junction Supplier Partner Portal">
                    <img src="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.jpg" alt="Sports Junction" class="h-9 sm:h-10 w-auto object-contain max-w-[170px]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-full ml-1">Supplier Portal</span>
                </a>

                <nav class="hidden md:flex items-center gap-4 text-xs font-bold uppercase tracking-wider text-slate-600">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/dashboard" class="hover:text-blue-900 transition-colors py-1">Dashboard</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products" class="hover:text-blue-900 transition-colors py-1">My Products</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/products/add" class="hover:text-blue-900 transition-colors py-1">Add Product</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/enquiries" class="hover:text-blue-900 transition-colors py-1">Customer Enquiries</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/supplier/profile" class="hover:text-blue-900 transition-colors py-1">My Profile</a>
                </nav>
            </div>

            <!-- User Menu -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-xs font-bold text-slate-900"><?= htmlspecialchars($supplier['company_name'] ?? $user['name'] ?? 'Supplier') ?></span>
                    <span class="text-[10px] font-semibold text-teal-700">Verified Manufacturer</span>
                </div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/logout" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold transition-colors">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

    <footer class="w-full py-6 border-t border-slate-200 bg-white text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> Sports Junction Supplier Portal. All product additions and modifications undergo strict Super Admin audit.
    </footer>
</body>
</html>
