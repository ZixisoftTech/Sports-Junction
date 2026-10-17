<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

$pdo = \App\Core\Database::getConnection();

echo "Truncating cms_pages table...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE `cms_pages`;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

$pages = [
    // Column 1 — TOP SECTORS (5 items)
    [
        'title' => 'Cricket Bats & Pads',
        'slug' => 'cricket-bats-pads',
        'column_category' => 'TOP_SECTORS',
        'external_url' => '/category/cricket-and-field-sports',
        'sort_order' => 1,
        'meta_title' => 'Cricket Bats & Pads Wholesale — Sports Junction B2B',
        'meta_description' => 'Source direct factory English willow bats, Kashmir willow bats, protective batting leg pads, and wicketkeeping gear from verified sports manufacturers.',
        'content' => '<h2>Wholesale Cricket Equipment Manufacturing</h2><p>Sports Junction connects verified global cricket equipment importers, brands, and sporting academies with direct tier-1 manufacturing hubs specializing in English and Kashmir willow cricket bats, batting leg guards, wicketkeeping equipment, and protective gear engineered to international test match standards.</p><p>All manufacturers adhere to strict wood-curing standards, compression testing, and grain alignment inspection to ensure top durability and performance.</p>'
    ],
    [
        'title' => 'Match Footballs',
        'slug' => 'match-footballs',
        'column_category' => 'TOP_SECTORS',
        'external_url' => '/category/football-and-inflatables',
        'sort_order' => 2,
        'meta_title' => 'Match Footballs Factory Direct — Sports Junction B2B',
        'meta_description' => 'Bulk supply of FIFA standard match footballs, thermo-bonded match balls, hand-stitched training balls, and custom OEM branded soccer balls.',
        'content' => '<h2>Professional Match & Training Football Production</h2><p>Discover direct OEM and ODM football manufacturers producing FIFA-standard match balls, thermo-bonded PU casing balls, hand-stitched training soccer balls, and custom club branded inflatables. Certified for low water absorption, rebound accuracy, and seam durability under intense tournament conditions.</p>'
    ],
    [
        'title' => 'Agility Hurdles & Ladders',
        'slug' => 'agility-hurdles-ladders',
        'column_category' => 'TOP_SECTORS',
        'external_url' => '/category/agility-and-speed-training',
        'sort_order' => 3,
        'meta_title' => 'Agility Hurdles & Ladders Wholesale — Sports Junction',
        'meta_description' => 'High-impact agility hurdles, speed ladders, coordination grids, and coaching training gear direct from certified sporting goods factories.',
        'content' => '<h2>High-Performance Speed & Agility Equipment</h2><p>Precision-molded shatterproof agility hurdles, flat-rung nylon speed ladders, agility poles, and reaction training accessories tailored for professional athletic teams, schools, and fitness training academies. Available for custom color branding and bulk container shipments.</p>'
    ],
    [
        'title' => 'Commercial Gym Equipment',
        'slug' => 'commercial-gym-equipment',
        'column_category' => 'TOP_SECTORS',
        'external_url' => '/category/sports-and-fitness',
        'sort_order' => 4,
        'meta_title' => 'Commercial Gym Equipment Wholesale — Sports Junction',
        'meta_description' => 'Direct factory heavy-duty strength equipment, selectorized gym machines, Olympic bars, bumper plates, and functional cross-training rigs.',
        'content' => '<h2>Heavy-Duty Commercial Fitness Equipment</h2><p>Engineered for high-volume health clubs, athletic training centers, and commercial fitness franchises. Our verified manufacturing partners produce heavy-gauge laser-cut steel racks, precision plate-loaded machines, Olympic urethane bumper plates, and ergonomic cardiovascular machines backed by commercial warranties.</p>'
    ],
    [
        'title' => 'Sportswear & Uniforms',
        'slug' => 'sportswear-uniforms',
        'column_category' => 'TOP_SECTORS',
        'external_url' => '/category/apparel-and-accessories',
        'sort_order' => 5,
        'meta_title' => 'Sportswear & Uniforms Custom OEM — Sports Junction',
        'meta_description' => 'OEM custom sublimated jerseys, technical sports uniforms, tracksuits, compression wear, and team kits direct from verified apparel factories.',
        'content' => '<h2>Custom Technical Teamwear & Athletic Apparel</h2><p>Full-coverage sublimation printing, moisture-wicking quick-dry fabrics, antimicrobial treatment, and bespoke sizing for team uniforms across football, cricket, basketball, and track sports. Low MOQs with rapid turnaround on private label OEM manufacturing orders.</p>'
    ],

    // Column 2 — SOURCING SOLUTIONS (5 items)
    [
        'title' => 'Direct Factory Catalog',
        'slug' => 'direct-factory-catalog',
        'column_category' => 'SOURCING',
        'external_url' => '/products',
        'sort_order' => 1,
        'meta_title' => 'Direct Factory Catalog — Sports Junction Wholesale',
        'meta_description' => 'Browse verified sports factory products, compare FOB pricing, and connect directly with certified manufacturing facilities worldwide.',
        'content' => '<h2>Direct Factory Product Directory</h2><p>Explore thousands of verified sporting goods listings direct from vetted factories across Asia and global export hubs. Skip intermediate broker markups, inspect actual factory capabilities, and initiate direct trade negotiations with confidence.</p>'
    ],
    [
        'title' => 'Request for Quotation (RFQ)',
        'slug' => 'request-for-quotation',
        'column_category' => 'SOURCING',
        'external_url' => '/page/request-for-quotation',
        'sort_order' => 2,
        'meta_title' => 'Request for Quotation (RFQ) — Sports Junction B2B',
        'meta_description' => 'Broadcast your custom sports goods specifications to certified manufacturers and receive competitive wholesale quotations within 24 hours.',
        'content' => '<h2>Submit a Direct Request for Quotation (RFQ)</h2><p>Can’t find exactly what you need? Post your exact product specifications, tech packs, target order volume, and destination port. Sports Junction matches your requirement with qualified verified suppliers who submit direct FOB and CIF factory bids within 24 business hours.</p>'
    ],
    [
        'title' => 'Contact Us & Support',
        'slug' => 'contact-us-support',
        'column_category' => 'SOURCING',
        'external_url' => '/contact',
        'sort_order' => 3,
        'meta_title' => 'Contact Us & Support — Sports Junction',
        'meta_description' => 'Get in touch with the Sports Junction sourcing team, vendor verification officers, and international trade assistance desk.',
        'content' => '<h2>Global Sourcing & Trade Support</h2><p>Our dedicated team of trade specialists, sourcing consultants, and supplier verification agents is ready to assist with custom sourcing briefs, factory audits, dispute resolution, and platform inquiries.</p>'
    ],
    [
        'title' => 'Container Freight Logistics',
        'slug' => 'container-freight-logistics',
        'column_category' => 'SOURCING',
        'external_url' => '/page/container-freight-logistics',
        'sort_order' => 4,
        'meta_title' => 'Container Freight Logistics — Sports Junction',
        'meta_description' => 'Comprehensive FCL and LCL container shipping, door-to-port freight forwarding, customs clearance, and marine cargo insurance solutions.',
        'content' => '<h2>International Freight Forwarding & Logistics</h2><p>Seamlessly move sports equipment shipments from factory doors to your destination warehouse. We coordinate Full Container Load (FCL) and Less than Container Load (LCL) sea freight, expedited air cargo, export customs documentation, and bill of lading releases with world-class maritime logistics partners.</p>'
    ],
    [
        'title' => 'XML Sitemap',
        'slug' => 'xml-sitemap',
        'column_category' => 'SOURCING',
        'external_url' => '/sitemap.xml',
        'sort_order' => 5,
        'meta_title' => 'XML Sitemap — Sports Junction B2B Platform',
        'meta_description' => 'Complete search engine indexing sitemap covering product catalogues, verified suppliers, categories, and manufacturing resources.',
        'content' => '<h2>Sports Junction Search Index & Sitemap</h2><p>Complete indexing directory for search engine crawlers and automated discovery agents to index our live factory products, categories, subcategories, and trade documentation.</p>'
    ],

    // Column 3 — PLATFORM & ADMIN (4 items)
    [
        'title' => 'Super Admin Portal →',
        'slug' => 'super-admin-portal',
        'column_category' => 'PLATFORM_ADMIN',
        'external_url' => '/admin/login',
        'sort_order' => 1,
        'meta_title' => 'Super Admin Portal — Sports Junction Management',
        'meta_description' => 'Restricted administrative gateway for Sports Junction platform management, security audits, and executive operations.',
        'content' => '<h2>Super Admin Portal</h2><p>Secure portal for authorized Sports Junction system administrators. Access master configuration panels, system activity logs, and platform operations.</p>'
    ],
    [
        'title' => 'Master CRUD Management',
        'slug' => 'master-crud-management',
        'column_category' => 'PLATFORM_ADMIN',
        'external_url' => '/admin/dashboard',
        'sort_order' => 2,
        'meta_title' => 'Master CRUD Management — Sports Junction Administration',
        'meta_description' => 'Super admin console for managing categories, cities, countries, units, buyers, suppliers, and website settings.',
        'content' => '<h2>Master CRUD Management</h2><p>Comprehensive administrative tools to manage all core database entities including categories, subcategories, certification tags, measurement units, country lists, and dynamic CMS pages.</p>'
    ],
    [
        'title' => 'Product Publishing Hub',
        'slug' => 'product-publishing-hub',
        'column_category' => 'PLATFORM_ADMIN',
        'external_url' => '/admin/products',
        'sort_order' => 3,
        'meta_title' => 'Product Publishing Hub — Sports Junction Admin',
        'meta_description' => 'Super admin approval queue for incoming supplier catalog submissions, specification audits, and live marketplace publishing.',
        'content' => '<h2>Product Approval & Publishing Hub</h2><p>Super admin console for reviewing submitted factory products, verifying OEM certifications, managing image quality standards, and approving products for live buyer search.</p>'
    ],
    [
        'title' => 'Terms & Manufacturing SLA',
        'slug' => 'terms-manufacturing-sla',
        'column_category' => 'PLATFORM_ADMIN',
        'external_url' => '/page/terms-manufacturing-sla',
        'sort_order' => 4,
        'meta_title' => 'Terms & Manufacturing SLA — Sports Junction',
        'meta_description' => 'Platform terms of service, manufacturing quality service level agreements, trade dispute procedures, and buyer protection guidelines.',
        'content' => '<h2>Manufacturing Service Level Agreement (SLA) & Trade Terms</h2><p>Sports Junction enforces rigorous manufacturing SLAs across all supplier contracts. Our policies govern acceptable defect rates (AQL 2.5 standard), on-time dispatch guarantees, pre-shipment quality inspection protocols, and escrow-backed payment protection for international wholesale buyers.</p>'
    ]
];

$stmt = $pdo->prepare("
    INSERT INTO `cms_pages` 
    (`title`, `slug`, `column_category`, `content`, `external_url`, `meta_title`, `meta_description`, `sort_order`, `is_active`, `created_at`, `updated_at`)
    VALUES 
    (:title, :slug, :cat, :content, :url, :meta_title, :meta_description, :sort_order, 1, NOW(), NOW())
");

foreach ($pages as $p) {
    $stmt->execute([
        ':title' => $p['title'],
        ':slug' => $p['slug'],
        ':cat' => $p['column_category'],
        ':content' => $p['content'],
        ':url' => $p['external_url'],
        ':meta_title' => $p['meta_title'],
        ':meta_description' => $p['meta_description'],
        ':sort_order' => $p['sort_order']
    ]);
    echo "Inserted: [{$p['column_category']}] (Sort: {$p['sort_order']}) {$p['title']} => {$p['external_url']}\n";
}

$count = (int) $pdo->query("SELECT COUNT(*) FROM cms_pages")->fetchColumn();
echo "\nTotal pages now in cms_pages: $count\n";
