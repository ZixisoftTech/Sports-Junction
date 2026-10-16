<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$db = Database::getConnection();

$pages = [
    // Column 1: ABOUT
    [
        'title' => 'Why choose Sports Junction',
        'slug' => 'why-choose-sports-junction',
        'column_category' => 'ABOUT',
        'content' => '<h2>Why Choose Sports Junction</h2><p>Sports Junction is India’s dedicated B2B manufacturing and wholesale sports goods marketplace. We bridge international and domestic sports brands, academies, clubs, and retailers directly with certified Indian manufacturers in Jalandhar, Meerut, Gurgaon, and beyond.</p><h3>Direct Factory Access</h3><p>Eliminate unnecessary broker markups and negotiate directly with verified factories producing international-standard sports equipment.</p>',
        'external_url' => null,
        'sort_order' => 1,
        'is_active' => 1
    ],
    [
        'title' => 'Corporate responsibility',
        'slug' => 'corporate-responsibility',
        'column_category' => 'ABOUT',
        'content' => '<h2>Corporate & Social Responsibility</h2><p>Sports Junction is committed to sustainable sports equipment manufacturing, fair factory labor standards, child-labor-free sporting goods production, and eco-friendly packaging across our certified vendor network.</p>',
        'external_url' => null,
        'sort_order' => 2,
        'is_active' => 1
    ],
    [
        'title' => 'Careers',
        'slug' => 'careers',
        'column_category' => 'ABOUT',
        'content' => '<h2>Careers at Sports Junction</h2><p>Join the team modernizing India’s \$1.5B+ sports export manufacturing sector. We are always seeking passionate individuals across engineering, supply chain logistics, B2B business development, and quality audit management.</p>',
        'external_url' => null,
        'sort_order' => 3,
        'is_active' => 1
    ],

    // Column 2: TOP_SECTORS
    [
        'title' => 'Cricket Bats & Pads',
        'slug' => 'cricket-bats-pads',
        'column_category' => 'TOP_SECTORS',
        'content' => '<h2>Cricket Bats, Pads & Protective Equipment</h2><p>Source English Willow and Kashmir Willow cricket bats, batting pads, wicket keeping gloves, and helmets directly from premier manufacturers in Meerut and Jalandhar.</p>',
        'external_url' => '/category/cricket-and-field-sports',
        'sort_order' => 1,
        'is_active' => 1
    ],
    [
        'title' => 'Match Footballs',
        'slug' => 'match-footballs',
        'column_category' => 'TOP_SECTORS',
        'content' => '<h2>Match & Training Footballs</h2><p>Hand-stitched, hybrid, and thermally bonded FIFA-standard footballs manufactured with certified polyurethane and bladder retention technologies.</p>',
        'external_url' => '/category/football-and-inflatables',
        'sort_order' => 2,
        'is_active' => 1
    ],
    [
        'title' => 'Agility Hurdles & Ladders',
        'slug' => 'agility-hurdles-ladders',
        'column_category' => 'TOP_SECTORS',
        'content' => '<h2>Speed, Agility & Fitness Training Equipment</h2><p>High-durability speed hurdles, agility ladders, marker cones, and slalom poles for professional sports academies and conditioning centers.</p>',
        'external_url' => '/category/agility-and-speed-training',
        'sort_order' => 3,
        'is_active' => 1
    ],
    [
        'title' => 'Commercial Gym Equipment',
        'slug' => 'commercial-gym-equipment',
        'column_category' => 'TOP_SECTORS',
        'content' => '<h2>Commercial & Functional Gym Equipment</h2><p>Olympic barbells, rubber bumper plates, cast iron kettlebells, and heavy-duty strength power cages crafted for commercial gym facilities.</p>',
        'external_url' => '/category/sports-and-fitness',
        'sort_order' => 4,
        'is_active' => 1
    ],
    [
        'title' => 'Sportswear & Uniforms',
        'slug' => 'sportswear-uniforms',
        'column_category' => 'TOP_SECTORS',
        'content' => '<h2>Custom Sportswear & Team Uniforms</h2><p>Breathable, moisture-wicking sublimation printed team jerseys, training tracksuits, and athletic performance wear tailored to OEM/ODM specifications.</p>',
        'external_url' => '/category/apparel-and-accessories',
        'sort_order' => 5,
        'is_active' => 1
    ],

    // Column 3: SOURCING
    [
        'title' => 'Direct Factory Catalog',
        'slug' => 'direct-factory-catalog',
        'column_category' => 'SOURCING',
        'content' => '<h2>Direct Factory Catalog</h2><p>Browse thousands of factory-direct sporting equipment listings verified for manufacturing authenticity, tier pricing, and export readiness.</p>',
        'external_url' => '/products',
        'sort_order' => 1,
        'is_active' => 1
    ],
    [
        'title' => 'Request for Quotation (RFQ)',
        'slug' => 'request-for-quotation',
        'column_category' => 'SOURCING',
        'content' => '<h2>Request for Quotation (RFQ)</h2><p>Submit customized bulk requirements, OEM specifications, tech packs, or sample evaluation requests directly to verified suppliers.</p>',
        'external_url' => '/page/request-for-quotation',
        'sort_order' => 2,
        'is_active' => 1
    ],
    [
        'title' => 'Contact Us & Support',
        'slug' => 'contact-us-support',
        'column_category' => 'SOURCING',
        'content' => '<h2>Contact & B2B Trade Desk</h2><p>Get in touch with our centralized B2B trade desk for bulk orders, vendor verification, or technical platform assistance.</p>',
        'external_url' => '/contact',
        'sort_order' => 3,
        'is_active' => 1
    ],
    [
        'title' => 'Container Freight Logistics',
        'slug' => 'container-freight-logistics',
        'column_category' => 'SOURCING',
        'content' => '<h2>Container Freight & Export Logistics</h2><p>Sports Junction coordinates with leading freight forwarding partners to provide FOB, CIF, and DDP sea and air shipping logistics from Indian dry ports and seaports worldwide.</p>',
        'external_url' => null,
        'sort_order' => 4,
        'is_active' => 1
    ],
    [
        'title' => 'XML Sitemap',
        'slug' => 'xml-sitemap',
        'column_category' => 'SOURCING',
        'content' => '<h2>XML Sitemap</h2><p>Search engine index file for all products, categories, and marketplace pages.</p>',
        'external_url' => '/sitemap.xml',
        'sort_order' => 5,
        'is_active' => 1
    ],

    // Column 4: PLATFORM_ADMIN
    [
        'title' => 'Super Admin Portal →',
        'slug' => 'super-admin-portal',
        'column_category' => 'PLATFORM_ADMIN',
        'content' => '<h2>Super Admin Portal</h2><p>Dedicated gateway for sports junction administrative management and marketplace operations.</p>',
        'external_url' => '/admin/login',
        'sort_order' => 1,
        'is_active' => 1
    ],
    [
        'title' => 'Master CRUD Management',
        'slug' => 'master-crud-management',
        'column_category' => 'PLATFORM_ADMIN',
        'content' => '<h2>Master CRUD Management</h2><p>Management portal for product certificates, certification tags, categories, subcategories, units, and site branding.</p>',
        'external_url' => '/admin/dashboard',
        'sort_order' => 2,
        'is_active' => 1
    ],
    [
        'title' => 'Product Publishing Hub',
        'slug' => 'product-publishing-hub',
        'column_category' => 'PLATFORM_ADMIN',
        'content' => '<h2>Product Publishing Hub</h2><p>Review submitted supplier goods, audit OEM specifications, approve catalog listings, and highlight featured merchandise.</p>',
        'external_url' => '/admin/products',
        'sort_order' => 3,
        'is_active' => 1
    ],
    [
        'title' => 'Terms & Manufacturing SLA',
        'slug' => 'terms-manufacturing-sla',
        'column_category' => 'PLATFORM_ADMIN',
        'content' => '<h2>Terms of Service & Manufacturing SLA</h2><p>Review our standard service level agreements governing sample delivery timelines, manufacturing tolerance standards, and dispute resolution guidelines.</p>',
        'external_url' => '/page/policies-and-rules',
        'sort_order' => 4,
        'is_active' => 1
    ],

    // Column 5: ORDER_PROTECTION
    [
        'title' => 'Secure Payments',
        'slug' => 'secure-payments',
        'column_category' => 'ORDER_PROTECTION',
        'content' => '<h2>Secure B2B Payment Processing</h2><p>All transactions, escrow safeguards, and commercial invoices comply with standard commercial banking safeguards and PCI DSS compliance protocols.</p>',
        'external_url' => null,
        'sort_order' => 1,
        'is_active' => 1
    ],
    [
        'title' => 'Guaranteed Delivery',
        'slug' => 'guaranteed-delivery',
        'column_category' => 'ORDER_PROTECTION',
        'content' => '<h2>Guaranteed Delivery & Shipping SLA</h2><p>We ensure verified factory dispatch dates and real-time container tracking with compensation terms for unexcused manufacturing delays.</p>',
        'external_url' => null,
        'sort_order' => 2,
        'is_active' => 1
    ],
    [
        'title' => 'Inspection Services',
        'slug' => 'inspection-services',
        'column_category' => 'ORDER_PROTECTION',
        'content' => '<h2>Production Monitoring & Third-Party Inspection</h2><p>Buyers can request pre-shipment quality inspection, material tensile testing, and factory compliance audits before container loading.</p>',
        'external_url' => null,
        'sort_order' => 3,
        'is_active' => 1
    ],
    [
        'title' => 'Buyer Help Center',
        'slug' => 'buyer-help-center',
        'column_category' => 'ORDER_PROTECTION',
        'content' => '<h2>Buyer Help Center</h2><p>Comprehensive guides on creating requests for quotation, evaluating vendor audit tags, arranging sample orders, and managing multi-supplier procurement.</p>',
        'external_url' => null,
        'sort_order' => 4,
        'is_active' => 1
    ]
];

$stmtCheck = $db->prepare("SELECT id FROM cms_pages WHERE slug = :slug LIMIT 1");
$stmtUpdate = $db->prepare("
    UPDATE cms_pages 
    SET title = :title, column_category = :cat, content = :content, external_url = :url, sort_order = :order, is_active = :active
    WHERE slug = :slug
");
$stmtInsert = $db->prepare("
    INSERT INTO cms_pages (title, slug, column_category, content, external_url, sort_order, is_active, created_at, updated_at)
    VALUES (:title, :slug, :cat, :content, :url, :order, :active, NOW(), NOW())
");

foreach ($pages as $p) {
    $stmtCheck->execute([':slug' => $p['slug']]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        $stmtUpdate->execute([
            ':title' => $p['title'],
            ':cat' => $p['column_category'],
            ':content' => $p['content'],
            ':url' => $p['external_url'],
            ':order' => $p['sort_order'],
            ':active' => $p['is_active'],
            ':slug' => $p['slug']
        ]);
        echo "Updated page: {$p['title']} ({$p['slug']})\n";
    } else {
        $stmtInsert->execute([
            ':title' => $p['title'],
            ':slug' => $p['slug'],
            ':cat' => $p['column_category'],
            ':content' => $p['content'],
            ':url' => $p['external_url'],
            ':order' => $p['sort_order'],
            ':active' => $p['is_active']
        ]);
        echo "Inserted page: {$p['title']} ({$p['slug']})\n";
    }
}

echo "Footer CMS seeding completed successfully.\n";
