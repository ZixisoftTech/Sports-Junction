<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — COMPREHENSIVE CATEGORIES & SUBCATEGORIES SEEDER
 * Idempotently seeds all marketplace parent categories & subcategories.
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$db = Database::getConnection();

echo "Starting Categories & Subcategories Audit and Seeder...\n";

$dataset = [
    [
        'name' => 'Sports & Fitness',
        'slug' => 'sports-and-fitness',
        'icon' => 'fitness_center',
        'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=600&q=80',
        'description' => 'Professional gym equipment, cardio machines, strength training weights, and athletic gear.',
        'display_order' => 1,
        'subcategories' => [
            [
                'name' => 'Gym & Strength Equipment',
                'slug' => 'gym-and-strength-equipment',
                'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=600&q=80',
                'description' => 'Power racks, barbells, bumper plates, kettlebells, and dumbbell sets.',
                'display_order' => 1
            ],
            [
                'name' => 'Aerobic & Cardio Machines',
                'slug' => 'aerobic-and-cardio-machines',
                'image' => 'https://images.unsplash.com/photo-1576678927484-cc907957088c?auto=format&fit=crop&w=600&q=80',
                'description' => 'Commercial treadmills, spin bikes, rowers, and stair climbers.',
                'display_order' => 2
            ],
            [
                'name' => 'Yoga & Fitness Mats',
                'slug' => 'yoga-and-fitness-mats',
                'image' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=600&q=80',
                'description' => 'High-density TPE yoga mats, exercise mats, foam rollers, and balance discs.',
                'display_order' => 3
            ]
        ]
    ],
    [
        'name' => 'Apparel & Accessories',
        'slug' => 'apparel-and-accessories',
        'icon' => 'apparel',
        'image' => 'https://images.unsplash.com/photo-1556906781-9a412961c28c?auto=format&fit=crop&w=600&q=80',
        'description' => 'Athletic sportswear, dry-fit jerseys, compression wear, sports socks, and wristbands.',
        'display_order' => 2,
        'subcategories' => [
            [
                'name' => 'Athletic Sportswear & Jerseys',
                'slug' => 'athletic-sportswear-and-jerseys',
                'image' => 'https://images.unsplash.com/photo-1556906781-9a412961c28c?auto=format&fit=crop&w=600&q=80',
                'description' => 'Custom sublimated team jerseys, breathable running tops, and training shorts.',
                'display_order' => 1
            ],
            [
                'name' => 'Compression Wear & Base Layers',
                'slug' => 'compression-wear-and-base-layers',
                'image' => 'https://images.unsplash.com/photo-1506152983158-b4a74a01c721?auto=format&fit=crop&w=600&q=80',
                'description' => 'Performance compression tights, rashguards, and muscle recovery base layers.',
                'display_order' => 2
            ],
            [
                'name' => 'Sports Socks & Wristbands',
                'slug' => 'sports-socks-and-wristbands',
                'image' => 'https://images.unsplash.com/photo-1582966772680-860e372bb558?auto=format&fit=crop&w=600&q=80',
                'description' => 'Cushioned athletic crew socks, sweatbands, headbands, and grip socks.',
                'display_order' => 3
            ],
            [
                'name' => 'Sports Caps & Headwear',
                'slug' => 'sports-caps-and-headwear',
                'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=600&q=80',
                'description' => 'Performance running caps, moisture-wicking visors, and cold-weather beanies.',
                'display_order' => 4
            ]
        ]
    ],
    [
        'name' => 'Cricket & Field Sports',
        'slug' => 'cricket-and-field-sports',
        'icon' => 'sports_cricket',
        'image' => 'https://images.unsplash.com/photo-1531415074868-036b1c57e329?auto=format&fit=crop&w=600&q=80',
        'description' => 'Handcrafted English and Kashmir willow cricket bats, protective gear, balls, and field sports equipment.',
        'display_order' => 3,
        'subcategories' => [
            [
                'name' => 'Cricket Bats (English & Kashmir)',
                'slug' => 'cricket-bats',
                'image' => 'https://images.unsplash.com/photo-1531415074868-036b1c57e329?auto=format&fit=crop&w=600&q=80',
                'description' => 'Professional Grade 1 English Willow and durable Kashmir Willow cricket bats.',
                'display_order' => 1
            ],
            [
                'name' => 'Protective Gear & Pads',
                'slug' => 'cricket-protective-gear',
                'image' => 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?auto=format&fit=crop&w=600&q=80',
                'description' => 'Batting pads, wicketkeeping leg guards, thigh pads, and chest protectors.',
                'display_order' => 2
            ],
            [
                'name' => 'Leather & Tennis Cricket Balls',
                'slug' => 'leather-and-tennis-cricket-balls',
                'image' => 'https://images.unsplash.com/photo-1587280501635-68a0e82cd5ff?auto=format&fit=crop&w=600&q=80',
                'description' => 'Four-piece alumed leather match balls, white balls, and heavy rubber tennis balls.',
                'display_order' => 3
            ],
            [
                'name' => 'Cricket Helmets & Gloves',
                'slug' => 'cricket-helmets-and-gloves',
                'image' => 'https://images.unsplash.com/photo-1593766827228-8737b4534aa6?auto=format&fit=crop&w=600&q=80',
                'description' => 'Titanium grill safety helmets and premium sheepskin batting gloves.',
                'display_order' => 4
            ],
            [
                'name' => 'Field Hockey Sticks & Balls',
                'slug' => 'field-hockey-sticks-and-balls',
                'image' => 'https://images.unsplash.com/photo-1580748141549-71748dbe0bdc?auto=format&fit=crop&w=600&q=80',
                'description' => 'Composite carbon field hockey sticks, dimple match turf balls, and shinguards.',
                'display_order' => 5
            ]
        ]
    ],
    [
        'name' => 'Football & Inflatables',
        'slug' => 'football-and-inflatables',
        'icon' => 'sports_soccer',
        'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
        'description' => 'Hand-stitched & thermally bonded match footballs, volleyballs, and inflatable training accessories.',
        'display_order' => 4,
        'subcategories' => [
            [
                'name' => 'Match & Training Footballs',
                'slug' => 'match-training-footballs',
                'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
                'description' => 'FIFA Quality Pro certified PU match balls and durable club training footballs.',
                'display_order' => 1
            ],
            [
                'name' => 'Volleyballs & Beach Balls',
                'slug' => 'volleyballs-and-beach-balls',
                'image' => 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=600&q=80',
                'description' => 'Indoor soft-touch microfiber volleyballs and outdoor waterproof beach balls.',
                'display_order' => 2
            ],
            [
                'name' => 'Basketballs & Inflatable Balls',
                'slug' => 'basketballs-and-inflatable-balls',
                'image' => 'https://images.unsplash.com/photo-1519766304817-4f37bda74a29?auto=format&fit=crop&w=600&q=80',
                'description' => 'Deep channel composite leather indoor/outdoor basketballs in sizes 5, 6, and 7.',
                'display_order' => 3
            ],
            [
                'name' => 'Goalkeeper Gloves & Netting',
                'slug' => 'goalkeeper-gloves-and-netting',
                'image' => 'https://images.unsplash.com/photo-1522778119026-d647f0596c20?auto=format&fit=crop&w=600&q=80',
                'description' => 'Negative cut latex goalkeeper match gloves and braided goalpost net sets.',
                'display_order' => 4
            ]
        ]
    ],
    [
        'name' => 'Racquet & Court Sports',
        'slug' => 'racquet-and-court-sports',
        'icon' => 'sports_tennis',
        'image' => 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?auto=format&fit=crop&w=600&q=80',
        'description' => 'Badminton racquets, tennis equipment, shuttlecocks, squash, and court netting systems.',
        'display_order' => 5,
        'subcategories' => [
            [
                'name' => 'Badminton Racquets & Grips',
                'slug' => 'badminton-racquets-and-grips',
                'image' => 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?auto=format&fit=crop&w=600&q=80',
                'description' => 'High-modulus graphite badminton frames, synthetic gut strings, and polyurethane grips.',
                'display_order' => 1
            ],
            [
                'name' => 'Shuttlecocks (Feather & Nylon)',
                'slug' => 'shuttlecocks-feather-and-nylon',
                'image' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=600&q=80',
                'description' => 'Tournament goose feather speed 77/78 shuttlecocks and durable nylon shuttles.',
                'display_order' => 2
            ],
            [
                'name' => 'Tennis Racquets & Balls',
                'slug' => 'tennis-racquets-and-balls',
                'image' => 'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&w=600&q=80',
                'description' => 'Pressurized ITF approved felt tennis balls and aerodynamic tennis racquets.',
                'display_order' => 3
            ],
            [
                'name' => 'Table Tennis Paddles & Tables',
                'slug' => 'table-tennis-paddles-and-tables',
                'image' => 'https://images.unsplash.com/photo-1534158914592-062992fbe900?auto=format&fit=crop&w=600&q=80',
                'description' => 'ITTF approved rubber paddles, 3-star 40+ ABS balls, and foldable tables.',
                'display_order' => 4
            ],
            [
                'name' => 'Squash & Pickleball Gear',
                'slug' => 'squash-and-pickleball-gear',
                'image' => 'https://images.unsplash.com/photo-1592656094267-764a45160876?auto=format&fit=crop&w=600&q=80',
                'description' => 'Carbon fiber pickleball paddles, USAPA balls, and teardrop squash racquets.',
                'display_order' => 5
            ]
        ]
    ],
    [
        'name' => 'Agility & Speed Training',
        'slug' => 'agility-and-speed-training',
        'icon' => 'timer',
        'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
        'description' => 'Training hurdles, agility ladders, slalom poles, reaction lights, and speed cones.',
        'display_order' => 6,
        'subcategories' => [
            [
                'name' => 'Training Hurdles & Ladders',
                'slug' => 'training-hurdles-and-ladders',
                'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
                'description' => 'Adjustable height agility hurdles, quick-foot speed ladders, and markers.',
                'display_order' => 1
            ],
            [
                'name' => 'Cones & Marker Sets',
                'slug' => 'cones-and-marker-sets',
                'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=600&q=80',
                'description' => 'Saucer marker field cones, collapsible traffic training cones, and carry stands.',
                'display_order' => 2
            ],
            [
                'name' => 'Speed Chutes & Resistance Bands',
                'slug' => 'speed-chutes-and-resistance-bands',
                'image' => 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=600&q=80',
                'description' => '54-inch speed running parachutes and heavy duty latex sprint resistance cords.',
                'display_order' => 3
            ],
            [
                'name' => 'Slalom Training Poles',
                'slug' => 'slalom-training-poles',
                'image' => 'https://images.unsplash.com/photo-1517649763962-0c623266ddc0?auto=format&fit=crop&w=600&q=80',
                'description' => 'Spring-loaded turf agility poles with weighted bases for football drills.',
                'display_order' => 4
            ]
        ]
    ],
    [
        'name' => 'Boxing & Combat Sports',
        'slug' => 'boxing-and-combat-sports',
        'icon' => 'sports_mma',
        'image' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=600&q=80',
        'description' => 'Genuine leather boxing gloves, punch bags, hand wraps, and MMA protection gear.',
        'display_order' => 7,
        'subcategories' => [
            [
                'name' => 'Boxing Gloves (Training & Sparring)',
                'slug' => 'boxing-gloves-training-and-sparring',
                'image' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=600&q=80',
                'description' => 'Multi-layer foam injected leather gloves in 10oz, 12oz, 14oz, and 16oz.',
                'display_order' => 1
            ],
            [
                'name' => 'Punching Bags & Wall Mounts',
                'slug' => 'punching-bags-and-wall-mounts',
                'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=600&q=80',
                'description' => 'Heavy hanging punching bags, tear-drop kickboxing bags, and ceiling swivel chains.',
                'display_order' => 2
            ],
            [
                'name' => 'Hand Wraps & Inner Gloves',
                'slug' => 'hand-wraps-and-inner-gloves',
                'image' => 'https://images.unsplash.com/photo-1583473848882-f9a5bc7fd208?auto=format&fit=crop&w=600&q=80',
                'description' => 'Semi-elastic 4.5m Mexican style cotton hand wraps and gel knuckle guard wraps.',
                'display_order' => 3
            ],
            [
                'name' => 'MMA Shin Guards & Headgear',
                'slug' => 'mma-shin-guards-and-headgear',
                'image' => 'https://images.unsplash.com/photo-1517438322307-e67111335449?auto=format&fit=crop&w=600&q=80',
                'description' => 'Full contact sparring headguards and instep neoprene padded shin guards.',
                'display_order' => 4
            ]
        ]
    ],
    [
        'name' => 'Outdoor & Adventure Gear',
        'slug' => 'outdoor-and-adventure-gear',
        'icon' => 'hiking',
        'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80',
        'description' => 'Trekking backpacks, camping tents, hydration bladders, and tactical outdoor ropes.',
        'display_order' => 8,
        'subcategories' => [
            [
                'name' => 'Trekking Backpacks & Daypacks',
                'slug' => 'trekking-backpacks-and-daypacks',
                'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80',
                'description' => 'Internal frame 45L-75L hiking rucksacks with rain covers and ergonomic hip belts.',
                'display_order' => 1
            ],
            [
                'name' => 'Camping Tents & Bivvy Bags',
                'slug' => 'camping-tents-and-bivvy-bags',
                'image' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=600&q=80',
                'description' => 'Double-walled 2-person and 4-person waterproof dome tents with aluminum poles.',
                'display_order' => 2
            ],
            [
                'name' => 'Hydration Bladders & Flasks',
                'slug' => 'hydration-bladders-and-flasks',
                'image' => 'https://images.unsplash.com/photo-1527786356703-4b100091cd2c?auto=format&fit=crop&w=600&q=80',
                'description' => 'BPA-free TPU leakproof water reservoirs and double-wall insulated sports bottles.',
                'display_order' => 3
            ],
            [
                'name' => 'Climbing Ropes & Carabiners',
                'slug' => 'climbing-ropes-and-carabiners',
                'image' => 'https://images.unsplash.com/photo-1522163182402-834f871fd851?auto=format&fit=crop&w=600&q=80',
                'description' => 'Static rappel cords, dynamic kernmantle climbing ropes, and locking screwgate carabiners.',
                'display_order' => 4
            ]
        ]
    ]
];

$totalCats = 0;
$totalSubs = 0;

$db->beginTransaction();

try {
    foreach ($dataset as $catData) {
        $subList = $catData['subcategories'] ?? [];
        unset($catData['subcategories']);

        // Check if category exists by slug or name
        $existing = $db->prepare("SELECT id, name, slug FROM categories WHERE slug = :slug OR name = :name LIMIT 1");
        $existing->execute([':slug' => $catData['slug'], ':name' => $catData['name']]);
        $catRow = $existing->fetch(PDO::FETCH_ASSOC);

        if ($catRow) {
            $catId = (int)$catRow['id'];
            $update = $db->prepare("
                UPDATE categories 
                SET display_order = :ord, description = COALESCE(description, :desc), icon = COALESCE(icon, :icon)
                WHERE id = :id
            ");
            $update->execute([
                ':ord'  => $catData['display_order'],
                ':desc' => $catData['description'],
                ':icon' => $catData['icon'],
                ':id'   => $catId
            ]);
            echo " [EXISTING] Category: {$catRow['name']} (ID: {$catId})\n";
        } else {
            $insert = $db->prepare("
                INSERT INTO categories (name, slug, icon, image, description, status, display_order, created_at, updated_at)
                VALUES (:name, :slug, :icon, :image, :description, 'ACTIVE', :ord, NOW(), NOW())
            ");
            $insert->execute([
                ':name'        => $catData['name'],
                ':slug'        => $catData['slug'],
                ':icon'        => $catData['icon'],
                ':image'       => $catData['image'],
                ':description' => $catData['description'],
                ':ord'         => $catData['display_order']
            ]);
            $catId = (int)$db->lastInsertId();
            echo " [CREATED] Category: {$catData['name']} (ID: {$catId})\n";
        }
        $totalCats++;

        // Process Subcategories
        foreach ($subList as $subData) {
            $subExisting = $db->prepare("SELECT id, name, slug FROM subcategories WHERE category_id = :cid AND (slug = :slug OR name = :name) LIMIT 1");
            $subExisting->execute([
                ':cid'  => $catId,
                ':slug' => $subData['slug'],
                ':name' => $subData['name']
            ]);
            $subRow = $subExisting->fetch(PDO::FETCH_ASSOC);

            if ($subRow) {
                $subId = (int)$subRow['id'];
                $subUpdate = $db->prepare("
                    UPDATE subcategories
                    SET display_order = :ord, description = COALESCE(description, :desc)
                    WHERE id = :id
                ");
                $subUpdate->execute([
                    ':ord'  => $subData['display_order'],
                    ':desc' => $subData['description'],
                    ':id'   => $subId
                ]);
                echo "    -> [EXISTING] Subcategory: {$subRow['name']} (ID: {$subId})\n";
            } else {
                // Ensure unique slug
                $subSlug = $subData['slug'];
                $slugCheck = $db->prepare("SELECT id FROM subcategories WHERE slug = :slug LIMIT 1");
                $slugCheck->execute([':slug' => $subSlug]);
                if ($slugCheck->fetch()) {
                    $subSlug = "{$subData['slug']}-{$catId}";
                }

                $subInsert = $db->prepare("
                    INSERT INTO subcategories (category_id, name, slug, image, description, status, display_order, created_at, updated_at)
                    VALUES (:cid, :name, :slug, :image, :description, 'ACTIVE', :ord, NOW(), NOW())
                ");
                $subInsert->execute([
                    ':cid'         => $catId,
                    ':name'        => $subData['name'],
                    ':slug'        => $subSlug,
                    ':image'       => $subData['image'],
                    ':description' => $subData['description'],
                    ':ord'         => $subData['display_order']
                ]);
                $subId = (int)$db->lastInsertId();
                echo "    -> [CREATED] Subcategory: {$subData['name']} (ID: {$subId})\n";
            }
            $totalSubs++;
        }
    }

    $db->commit();
    echo "\n=== CATEGORY & SUBCATEGORY AUDIT COMPLETE ===\n";
    echo "Processed Categories: {$totalCats}\n";
    echo "Processed Subcategories: {$totalSubs}\n";
    echo "Status: SUCCESS\n";
} catch (\Throwable $e) {
    $db->rollBack();
    echo "\nERROR: Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
