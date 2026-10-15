<?php
declare(strict_types=1);

/**
 * Migration: Create site_settings and cms_pages tables for Dynamic Footer & Website Settings
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    echo "Connected to database successfully.\n";

    // 1. Create site_settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `site_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` LONGTEXT NULL,
            `setting_group` VARCHAR(50) DEFAULT 'general',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (`setting_key`),
            INDEX idx_setting_group (`setting_group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table site_settings ensured.\n";

    // 2. Create cms_pages table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cms_pages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL UNIQUE,
            `column_category` VARCHAR(50) NOT NULL DEFAULT 'ABOUT',
            `content` LONGTEXT NULL,
            `external_url` VARCHAR(255) NULL,
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (`slug`),
            INDEX idx_category (`column_category`),
            INDEX idx_active (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table cms_pages ensured.\n";

    // 3. Seed Default Site Settings
    $defaultSettings = [
        'site_name' => ['Sports Junction', 'general'],
        'site_tagline' => ["India's Premier Sports Manufacturing B2B Marketplace", 'general'],
        'site_logo' => ['/assets/images/logo.jpg', 'general'],
        'contact_phone' => ['+91 98765 43210', 'contact'],
        'contact_email' => ['support@sportsjunction.in', 'contact'],
        'contact_address' => ['Sports Goods Complex, Meerut Road, Jalandhar & Meerut, India', 'contact'],
        'working_hours' => ['Mon - Sat: 9:00 AM - 7:00 PM IST', 'contact'],
        'social_facebook' => ['https://facebook.com/sportsjunction', 'social'],
        'social_linkedin' => ['https://linkedin.com/company/sportsjunction', 'social'],
        'social_twitter' => ['https://x.com/sportsjunction', 'social'],
        'social_instagram' => ['https://instagram.com/sportsjunction', 'social'],
        'social_youtube' => ['https://youtube.com/@sportsjunction', 'social'],
        'social_tiktok' => ['https://tiktok.com/@sportsjunction', 'social']
    ];

    $stmtCheck = $pdo->prepare("SELECT id FROM `site_settings` WHERE `setting_key` = :k LIMIT 1");
    $stmtInsert = $pdo->prepare("INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES (:k, :v, :g)");

    foreach ($defaultSettings as $k => [$v, $g]) {
        $stmtCheck->execute([':k' => $k]);
        if (!$stmtCheck->fetch()) {
            $stmtInsert->execute([':k' => $k, ':v' => $v, ':g' => $g]);
            echo "Seeded setting: {$k}\n";
        }
    }

    // 4. Seed Alibaba-Style Footer CMS Pages
    $defaultPages = [
        // Column 1: About Sports Junction
        [
            'title' => 'Why choose Sports Junction',
            'slug' => 'why-choose-sports-junction',
            'category' => 'ABOUT',
            'order' => 1,
            'content' => "<h2>Why Choose Sports Junction</h2><p>Sports Junction is India's leading B2B marketplace dedicated to connecting worldwide buyers, sports brands, academies, and institutional buyers directly with audited and verified Indian sports manufacturers in Meerut, Jalandhar, and Gujarat.</p><ul><li>Direct factory wholesale pricing without trading intermediary markup.</li><li>Strict physical and compliance audits for all verified manufacturers.</li><li>Secure milestone-based negotiation and formal deal agreements.</li><li>Comprehensive export packaging, laboratory testing, and logistics coordination.</li></ul>"
        ],
        [
            'title' => 'Corporate responsibility',
            'slug' => 'corporate-responsibility',
            'category' => 'ABOUT',
            'order' => 2,
            'content' => "<h2>Corporate Responsibility & Ethical Sourcing</h2><p>We are deeply committed to ethical manufacturing practices across India's sports equipment industry. Sports Junction strictly enforces zero child labour policies, equitable worker wages, safe working conditions, and environmentally sustainable production standards.</p>"
        ],
        [
            'title' => 'Careers',
            'slug' => 'careers',
            'category' => 'ABOUT',
            'order' => 3,
            'content' => "<h2>Careers at Sports Junction</h2><p>Join our mission to revolutionize global sports supply chains and empower local Indian manufacturers on the international stage. We are hiring talented engineers, supply chain specialists, and relationship managers.</p><p>Send your resume to <strong>careers@sportsjunction.in</strong>.</p>"
        ],

        // Column 2: Order Protection
        [
            'title' => 'Secure payments',
            'slug' => 'secure-payments',
            'category' => 'ORDER_PROTECTION',
            'order' => 1,
            'content' => "<h2>Secure Payments & Buyer Protection</h2><p>Sports Junction provides end-to-end security for all wholesale business transactions. From encrypted digital communications to bank escrow and letter of credit (LC) support, your business funds remain protected at every stage.</p>"
        ],
        [
            'title' => 'Guaranteed delivery',
            'slug' => 'guaranteed-delivery',
            'category' => 'ORDER_PROTECTION',
            'order' => 2,
            'content' => "<h2>Guaranteed On-Time Dispatch & Delivery</h2><p>Every formal negotiation on Sports Junction includes legally binding production lead times. If a verified manufacturer fails to meet the agreed dispatch milestones without mutual consent, buyers are eligible for penalty credits and prioritized expedited fulfillment.</p>"
        ],
        [
            'title' => 'Production monitoring & inspection services',
            'slug' => 'inspection-services',
            'category' => 'ORDER_PROTECTION',
            'order' => 3,
            'content' => "<h2>Quality Inspection & Production Monitoring</h2><p>Our network of certified third-party QA inspectors conducts pre-production raw material testing, during-production batch checks, and pre-shipment container audits to ensure strict adherence to international sports standards.</p>"
        ],
        [
            'title' => 'Policies and rules',
            'slug' => 'policies-and-rules',
            'category' => 'ORDER_PROTECTION',
            'order' => 4,
            'content' => "<h2>Platform Policies & Marketplace Guidelines</h2><p>Sports Junction maintains strict trading conduct. Direct off-platform payment diversion, counterfeit manufacturing, and deceptive specifications result in immediate vendor suspension and forfeiture of platform verification badges.</p>"
        ],

        // Column 3: Source on Sports Junction
        [
            'title' => 'Verified manufacturers',
            'slug' => 'verified-manufacturers',
            'category' => 'SOURCE',
            'order' => 1,
            'content' => "<h2>Verified Indian Manufacturers</h2><p>Explore audited manufacturing facilities with verified GST, IEC export licenses, and verified plant machinery in Meerut, Jalandhar, and Ahmedabad.</p>",
            'external_url' => '/products?verified=1'
        ],
        [
            'title' => 'Request for Quotation',
            'slug' => 'request-for-quotation',
            'category' => 'SOURCE',
            'order' => 2,
            'content' => "<h2>Request for Quotation (RFQ)</h2><p>Submit customized requirements including custom branding, export grade packaging, and target wholesale pricing to receive multiple verified quotes within 24 hours.</p>",
            'external_url' => '/products'
        ],

        // Column 4: Help Center
        [
            'title' => 'Buyer Help Center',
            'slug' => 'buyer-help-center',
            'category' => 'HELP_CENTER',
            'order' => 1,
            'content' => "<h2>Buyer Help Center & FAQs</h2><p>Find answers to common inquiries regarding buyer registration, requesting production samples, negotiating wholesale MOQs, and downloading export documentation.</p>"
        ],
        [
            'title' => 'Live chat',
            'slug' => 'live-chat',
            'category' => 'HELP_CENTER',
            'order' => 2,
            'content' => "<h2>Real-Time Live Chat & Direct Negotiation</h2><p>Sports Junction features built-in real-time chat with instant document exchange and binding counter-offers. Open the chat widget at the bottom right of any product page to chat directly with verified factory representatives.</p>",
            'external_url' => '/buyer/messages'
        ],

        // Column 5: Sell on Sports Junction
        [
            'title' => 'Sell on Sports Junction',
            'slug' => 'sell-on-sports-junction',
            'category' => 'SELL',
            'order' => 1,
            'content' => "<h2>Sell on Sports Junction — Supplier Onboarding</h2><p>Expand your sports manufacturing factory to international importers in the USA, UK, Europe, Australia, and GCC. Register your factory and start receiving direct bulk purchase orders.</p>",
            'external_url' => '/seller/login'
        ],
        [
            'title' => 'Become a Verified Supplier',
            'slug' => 'become-verified-supplier',
            'category' => 'SELL',
            'order' => 2,
            'content' => "<h2>Become a Verified Sports Junction Manufacturer</h2><p>Undergo our comprehensive background verification and facility audit to earn the coveted Verified Supplier Badge, boosting your catalog visibility by over 300%.</p>",
            'external_url' => '/seller/login'
        ]
    ];

    $stmtPageCheck = $pdo->prepare("SELECT id FROM `cms_pages` WHERE `slug` = :s LIMIT 1");
    $stmtPageInsert = $pdo->prepare("
        INSERT INTO `cms_pages` (`title`, `slug`, `column_category`, `content`, `external_url`, `sort_order`, `is_active`)
        VALUES (:title, :slug, :cat, :content, :url, :order, 1)
    ");

    foreach ($defaultPages as $p) {
        $stmtPageCheck->execute([':s' => $p['slug']]);
        if (!$stmtPageCheck->fetch()) {
            $stmtPageInsert->execute([
                ':title' => $p['title'],
                ':slug' => $p['slug'],
                ':cat' => $p['category'],
                ':content' => $p['content'] ?? '',
                ':url' => $p['external_url'] ?? null,
                ':order' => $p['order'] ?? 0
            ]);
            echo "Seeded CMS page: {$p['title']} ({$p['slug']})\n";
        }
    }

    echo "\n=== Migration Completed Successfully ===\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
