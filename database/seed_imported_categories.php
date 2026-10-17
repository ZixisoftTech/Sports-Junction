<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — AUTHORITATIVE CATEGORIES & SUBCATEGORIES SEEDER
 * Imports and synchronizes the 44 categories and 132 subcategories
 * from the master source list into the MySQL database.
 * Idempotent: Can be run multiple times safely without duplicate rows.
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Helpers/Uploader.php';

use App\Core\Database;
use App\Helpers\Uploader;

$db = Database::getConnection();

echo "=======================================================================\n";
echo "SPORTS JUNCTION — CATEGORY & SUBCATEGORY MASTER DATA SEEDER\n";
echo "=======================================================================\n";

$sourceFile = __DIR__ . '/../categories_master_source.json';
if (!file_exists($sourceFile)) {
    echo "ERROR: Source file {$sourceFile} not found!\n";
    exit(1);
}

$categoriesSource = json_decode(file_get_contents($sourceFile), true);
if (!is_array($categoriesSource) || empty($categoriesSource)) {
    echo "ERROR: Invalid or empty categories source file!\n";
    exit(1);
}

// Icon and visual asset mappings for top-level categories
$categoryVisuals = [
    'Agility & Speed Training Equipment' => [
        'icon'  => 'timer',
        'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'High-performance agility ladders, slalom poles, training hurdles, and speed development gear.'
    ],
    'Athletics Track & Field Equipment' => [
        'icon'  => 'directions_run',
        'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'IAAF-standard track & field equipment, landing pits, starting blocks, hurdles, and throwing implements.'
    ],
    'Basketball Equipment' => [
        'icon'  => 'sports_basketball',
        'image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Commercial basketball systems, backboards, breakaway rings, heavy-duty nets, and official match balls.'
    ],
    'Beach Toys' => [
        'icon'  => 'beach_access',
        'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Durable beach buckets, shovels, recreational sand sets, and water play accessories.'
    ],
    'Board Games' => [
        'icon'  => 'casino',
        'image' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Classic wooden chess boards, crossword sets, snakes & ladders, and educational tabletop games.'
    ],
    'Boxing Equipment' => [
        'icon'  => 'sports_mma',
        'image' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Competition boxing rings, heavy punch bags, hand wraps, focus mitts, and ring accessories.'
    ],
    'Carrom Board' => [
        'icon'  => 'grid_view',
        'image' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Tournament-grade English ply carrom boards, coins, precision strikers, and smooth surface powders.'
    ],
    'Coaching Clipboards' => [
        'icon'  => 'assignment',
        'image' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Magnetic tactics boards, dry-erase coach clipboards, and strategic play-diagramming equipment.'
    ],
    'Cricket Equipment' => [
        'icon'  => 'sports_cricket',
        'image' => 'https://images.unsplash.com/photo-1531415074868-036b107e775a?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Grade 1 English willow bats, protective batting pads, match balls, boundary ropes, and practice nets.'
    ],
    'Fitness Equipment' => [
        'icon'  => 'fitness_center',
        'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Commercial dumbbell sets, barbells, power racks, kettlebells, and cardiovascular gym machines.'
    ],
    'Flying Disk and Ring' => [
        'icon'  => 'album',
        'image' => 'https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Aerodynamic flying discs, ultimate frisbee rings, and precision throwing discs for sports & games.'
    ],
    'Goal Posts' => [
        'icon'  => 'sports_soccer',
        'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'All-weather aluminium and steel soccer goal posts, mini targets, futsal frames, and ground anchors.'
    ],
    'Golf Accessories' => [
        'icon'  => 'sports_golf',
        'image' => 'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Practice putting mats, golf tees, divot repair tools, range accessories, and club protection covers.'
    ],
    'Gym and Crash Mats' => [
        'icon'  => 'layers',
        'image' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'High-density impact landing mats, folding gym mats, wrestling crash pads, and EVA interlocking foam.'
    ],
    'Gymnastic Equipment' => [
        'icon'  => 'accessibility_new',
        'image' => 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Balance beams, parallel bars, vaulting horses, gymnastic rings, and competition springboards.'
    ],
    'Lacrosse' => [
        'icon'  => 'sports_hockey',
        'image' => 'https://images.unsplash.com/photo-1580748141549-71748dbe0bdc?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Competition lacrosse sticks, protective gloves, helmets, goals, and official game balls.'
    ],
    'Miscellaneous Sports' => [
        'icon'  => 'sports',
        'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Multi-sport training accessories, corner flags, whistles, referee gear, and event coordination tools.'
    ],
    'Netball Equipment' => [
        'icon'  => 'sports_volleyball',
        'image' => 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Freestanding netball posts, regulation rings, match bibs, and high-grip netball balls.'
    ],
    'Outdoor Gym / Fitness Equipment' => [
        'icon'  => 'park',
        'image' => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Weather-resistant outdoor calisthenics rigs, community fitness stations, and street workout bars.'
    ],
    'Pickleballs' => [
        'icon'  => 'sports_tennis',
        'image' => 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'USAPA-approved pickleball paddles, indoor & outdoor balls, portable net systems, and court markers.'
    ],
    'Playground Equipment' => [
        'icon'  => 'child_care',
        'image' => 'https://images.unsplash.com/photo-1588075592446-265fd1e6e76f?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'School playground activity modules, safe swing assemblies, slides, climbers, and balance trails.'
    ],
    'Primary Education Equipment' => [
        'icon'  => 'school',
        'image' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Early motor skills development sets, soft foam shapes, balance beams, and primary classroom active gear.'
    ],
    'Primary Sports Equipment' => [
        'icon'  => 'sports_handball',
        'image' => 'https://images.unsplash.com/photo-1562771242-a02d9090c90c?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Junior size cricket, soccer, tennis, and athletics equipment tailored for junior school athletes.'
    ],
    'Rebounders' => [
        'icon'  => 'replay',
        'image' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Multi-angle football bounceboards, volleyball reaction rebounders, and solo skill rebound nets.'
    ],
    'Recycled Products' => [
        'icon'  => 'recycling',
        'image' => 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Eco-friendly recycled rubber crumb playground tiles, reclaimed plastic sports cones, and green gear.'
    ],
    'Rhythmic Gymnastics Equipment' => [
        'icon'  => 'flare',
        'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Satin ribbons, dynamic hoops, clubs, rhythmic balls, and FIG compliant flexibility training accessories.'
    ],
    'Rounder Training' => [
        'icon'  => 'sports_baseball',
        'image' => 'https://images.unsplash.com/photo-1508344928928-7165b67de128?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Wooden & composite rounders bats, match balls, boundary posts, and school sports rounder kits.'
    ],
    'Rugby Equipment' => [
        'icon'  => 'sports_rugby',
        'image' => 'https://images.unsplash.com/photo-1563299796-17596ed6b017?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Tackle bags, hit shields, kicking tees, corner flags, scrum machines, and match rugby balls.'
    ],
    'Scooter Board / Scooter Hockey' => [
        'icon'  => 'skateboarding',
        'image' => 'https://images.unsplash.com/photo-1564982752979-3f7bc974d29a?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Heavy-duty non-marring caster scooter boards, paddle sticks, safety handles, and gym hockey kits.'
    ],
    'Score Boards' => [
        'icon'  => 'scoreboard',
        'image' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Digital multi-sport electronic scoreboards, tabletop flip score counters, and LED match displays.'
    ],
    'Soccer Cones Markers' => [
        'icon'  => 'change_history',
        'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'High-visibility saucer disc cones, saucer markers with carry stands, and boundary marker dome cones.'
    ],
    'Soccer Training Equipment and Accessories' => [
        'icon'  => 'sports_soccer',
        'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Tactical coaching boards, free-kick mannequins, speed rings, ball rebounders, and training bibs.'
    ],
    'Sports Apparel' => [
        'icon'  => 'apparel',
        'image' => 'https://images.unsplash.com/photo-1556906781-9a412961c28c?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Sublimated team jerseys, sweat-wicking athletic shorts, compression layers, and sports tracksuits.'
    ],
    'Sports Balls' => [
        'icon'  => 'sports_soccer',
        'image' => 'https://images.unsplash.com/photo-1614632537423-1e6c2e7e0aab?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Official FIFA/FIBA match and training balls for soccer, basketball, volleyball, rugby, and handball.'
    ],
    'Sports Bottles / Bottles Carriers' => [
        'icon'  => 'local_drink',
        'image' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'BPA-free team squeeze water bottles, folding wire bottle cages, and insulated hydration coolers.'
    ],
    'Sports Facility Equipment' => [
        'icon'  => 'stadium',
        'image' => 'https://images.unsplash.com/photo-1577223625816-7546f13df25d?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Stadium seating benches, spectator bleachers, player shelters, team dugouts, and field line markers.'
    ],
    'Sports Flooring Tiles' => [
        'icon'  => 'dashboard',
        'image' => 'https://images.unsplash.com/photo-1584824486509-112e4181ff6b?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Modular polypropylene outdoor court tiles, interlocking indoor sports flooring, and gym rubber mats.'
    ],
    'Sports Nets' => [
        'icon'  => 'grid_on',
        'image' => 'https://images.unsplash.com/photo-1563299796-17596ed6b017?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Heavy knotless nylon soccer nets, tennis court nets, volleyball barrier nets, and golf perimeter netting.'
    ],
    'Storage and Carrying Accessories' => [
        'icon'  => 'inventory_2',
        'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Wheeled ball cages, heavy-duty duffel equipment bags, mesh ball sacks, and equipment organizers.'
    ],
    'Swimming Accessories' => [
        'icon'  => 'pool',
        'image' => 'https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Anti-fog racing goggles, silicone swim caps, kickboards, pull buoys, and training swim fins.'
    ],
    'Table Tennis Tables' => [
        'icon'  => 'table_restaurant',
        'image' => 'https://images.unsplash.com/photo-1534158914592-062992fbe900?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'ITTF standard indoor & weatherproof outdoor ping pong tables, net sets, paddles, and 3-star balls.'
    ],
    'Tchoukball Frames' => [
        'icon'  => 'filter_frames',
        'image' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Official 1m x 1m steel tchoukball bounce frames, high-tension bungee cords, and non-slip feet.'
    ],
    'Tents and Tunnels' => [
        'icon'  => 'holiday_village',
        'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Pop-up agility crawl tunnels, team event canopies, youth obstacle tunnels, and shelter shade tents.'
    ],
    'Wooden Toys' => [
        'icon'  => 'toys',
        'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=600&q=80',
        'desc'  => 'Handcrafted wooden bowling skittles, wooden balancing toys, stacking rings, and outdoor wooden games.'
    ]
];

// Helper to get next unique slug for a table
function getUniqueSlug(PDO $db, string $table, string $baseSlug, ?int $ignoreId = null): string {
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = :slug";
        $params = [':slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignoreId;
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = "{$baseSlug}-{$counter}";
        $counter++;
    }
}

// Track metrics
$metrics = [
    'total_source_categories'    => count($categoriesSource),
    'total_source_subcategories' => 0,
    'categories_inserted'        => 0,
    'categories_reused'          => 0,
    'subcategories_inserted'     => 0,
    'subcategories_reused'       => 0,
    'standalone_categories'      => 0,
    'parent_categories'          => 0,
    'conflicts_detected'         => []
];

foreach ($categoriesSource as $item) {
    $metrics['total_source_subcategories'] += count($item['subcategories'] ?? []);
}

echo "Found {$metrics['total_source_categories']} source categories and {$metrics['total_source_subcategories']} source subcategories.\n";

$db->beginTransaction();

try {
    // Determine initial display order for newly inserted categories
    $maxOrderStmt = $db->query("SELECT COALESCE(MAX(display_order), 0) FROM categories");
    $currentCategoryOrder = (int)$maxOrderStmt->fetchColumn() + 1;

    $catStmt = $db->prepare("SELECT id, name, slug, status FROM categories WHERE name = :name LIMIT 1");
    $catInsertStmt = $db->prepare("
        INSERT INTO categories (name, slug, image, icon, description, status, display_order, created_at, updated_at)
        VALUES (:name, :slug, :image, :icon, :description, 'ACTIVE', :display_order, NOW(), NOW())
    ");
    $catUpdateStatusStmt = $db->prepare("UPDATE categories SET status = 'ACTIVE' WHERE id = :id AND status != 'ACTIVE'");

    $subStmt = $db->prepare("SELECT id, category_id, name, slug, status FROM subcategories WHERE category_id = :cid AND name = :name LIMIT 1");
    $subCrossCheckStmt = $db->prepare("SELECT id, category_id, name FROM subcategories WHERE name = :name AND category_id != :cid LIMIT 1");
    $subInsertStmt = $db->prepare("
        INSERT INTO subcategories (category_id, name, slug, image, description, status, display_order, created_at, updated_at)
        VALUES (:category_id, :name, :slug, :image, :description, 'ACTIVE', :display_order, NOW(), NOW())
    ");
    $subUpdateStatusStmt = $db->prepare("UPDATE subcategories SET status = 'ACTIVE' WHERE id = :id AND status != 'ACTIVE'");

    foreach ($categoriesSource as $catData) {
        $catName = trim($catData['name']);
        $subNames = $catData['subcategories'] ?? [];
        $isStandalone = empty($subNames);

        if ($isStandalone) {
            $metrics['standalone_categories']++;
        } else {
            $metrics['parent_categories']++;
        }

        // Check if category exists
        $catStmt->execute([':name' => $catName]);
        $existingCat = $catStmt->fetch(PDO::FETCH_ASSOC);

        $catVisual = $categoryVisuals[$catName] ?? [
            'icon'  => 'sports_tennis',
            'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=600&q=80',
            'desc'  => "Wholesale high quality {$catName} for schools, clubs, sports facilities, and professional athletes."
        ];

        if ($existingCat) {
            $catId = (int)$existingCat['id'];
            $metrics['categories_reused']++;
            $catUpdateStatusStmt->execute([':id' => $catId]);
            echo "  [REUSED] Category #{$catId}: {$catName}\n";
        } else {
            $catSlug = getUniqueSlug($db, 'categories', Uploader::slugify($catName));
            $catInsertStmt->execute([
                ':name'          => $catName,
                ':slug'          => $catSlug,
                ':image'         => $catVisual['image'],
                ':icon'          => $catVisual['icon'],
                ':description'   => $catVisual['desc'],
                ':display_order' => $currentCategoryOrder++
            ]);
            $catId = (int)$db->lastInsertId();
            $metrics['categories_inserted']++;
            echo "  [INSERTED] Category #{$catId}: {$catName} ({$catSlug})\n";
        }

        // Process subcategories
        $subOrder = 1;
        foreach ($subNames as $subName) {
            $subName = trim($subName);

            // Cross-check if subcategory exists under another parent (Rule 12)
            $subCrossCheckStmt->execute([':name' => $subName, ':cid' => $catId]);
            $crossConflict = $subCrossCheckStmt->fetch(PDO::FETCH_ASSOC);
            if ($crossConflict) {
                $metrics['conflicts_detected'][] = [
                    'subcategory'     => $subName,
                    'target_cat_id'   => $catId,
                    'target_cat_name' => $catName,
                    'existing_cat_id' => $crossConflict['category_id']
                ];
                echo "    [WARNING] Subcategory '{$subName}' already exists under Category #{$crossConflict['category_id']}! Creating dedicated child for #{$catId}.\n";
            }

            // Check if subcategory already exists under this parent category
            $subStmt->execute([':cid' => $catId, ':name' => $subName]);
            $existingSub = $subStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingSub) {
                $subId = (int)$existingSub['id'];
                $metrics['subcategories_reused']++;
                $subUpdateStatusStmt->execute([':id' => $subId]);
                echo "    -> [REUSED] Subcategory #{$subId}: {$subName}\n";
            } else {
                $baseSubSlug = Uploader::slugify($subName);
                $subSlug = getUniqueSlug($db, 'subcategories', $baseSubSlug);
                $subDesc = "Wholesale {$subName} manufactured for professional training, sports academies, and athletic performance.";

                $subInsertStmt->execute([
                    ':category_id'   => $catId,
                    ':name'          => $subName,
                    ':slug'          => $subSlug,
                    ':image'         => $catVisual['image'],
                    ':description'   => $subDesc,
                    ':display_order' => $subOrder++
                ]);
                $subId = (int)$db->lastInsertId();
                $metrics['subcategories_inserted']++;
                echo "    -> [INSERTED] Subcategory #{$subId}: {$subName} ({$subSlug})\n";
            }
        }
    }

    $db->commit();
    echo "\n=======================================================================\n";
    echo "SEEDING COMPLETED SUCCESSFULLY\n";
    echo "=======================================================================\n";
    echo "Total Source Categories:       {$metrics['total_source_categories']}\n";
    echo "Total Source Subcategories:    {$metrics['total_source_subcategories']}\n";
    echo "Categories Inserted:           {$metrics['categories_inserted']}\n";
    echo "Categories Reused:             {$metrics['categories_reused']}\n";
    echo "Subcategories Inserted:        {$metrics['subcategories_inserted']}\n";
    echo "Subcategories Reused:          {$metrics['subcategories_reused']}\n";
    echo "Standalone Categories (0 subs):{$metrics['standalone_categories']}\n";
    echo "Parent Categories (with subs): {$metrics['parent_categories']}\n";
    echo "Cross Conflicts Count:         " . count($metrics['conflicts_detected']) . "\n";
    echo "=======================================================================\n";

    // Write seeding result file for reference
    file_put_contents(__DIR__ . '/../scratch/seeder_last_run.json', json_encode($metrics, JSON_PRETTY_PRINT));

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "\nFATAL ERROR: Seeder rolled back due to error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
