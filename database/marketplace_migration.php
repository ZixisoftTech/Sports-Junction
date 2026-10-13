<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — MARKETPLACE DATABASE MIGRATION & SEEDER
 * Creates categories, subcategories, product_images, product_inquiries
 * and expands products table with all B2B marketplace fields.
 * =======================================================================
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$db = Database::getConnection();

echo "1. Creating categories table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    image VARCHAR(255) NULL,
    icon VARCHAR(100) NULL DEFAULT 'sports_tennis',
    description TEXT NULL,
    status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cat_status (status),
    INDEX idx_cat_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "2. Creating subcategories table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS subcategories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    image VARCHAR(255) NULL,
    description TEXT NULL,
    status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_subcat_category (category_id),
    INDEX idx_subcat_status (status),
    INDEX idx_subcat_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "3. Updating products table schema...\n";

// Function to check column existence
$cols = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
$hasCol = function(string $col) use ($cols): bool {
    return in_array($col, $cols, true);
};

if (!$hasCol('category_id')) {
    $db->exec("ALTER TABLE products ADD COLUMN category_id INT UNSIGNED NULL AFTER supplier_id");
}
if (!$hasCol('subcategory_id')) {
    $db->exec("ALTER TABLE products ADD COLUMN subcategory_id INT UNSIGNED NULL AFTER category_id");
}
if (!$hasCol('slug')) {
    $db->exec("ALTER TABLE products ADD COLUMN slug VARCHAR(260) NULL AFTER title");
}
if (!$hasCol('sku')) {
    $db->exec("ALTER TABLE products ADD COLUMN sku VARCHAR(100) NULL AFTER slug");
}
if (!$hasCol('brand_name')) {
    $db->exec("ALTER TABLE products ADD COLUMN brand_name VARCHAR(150) NULL AFTER sku");
}
if (!$hasCol('short_description')) {
    $db->exec("ALTER TABLE products ADD COLUMN short_description TEXT NULL AFTER brand_name");
}
if (!$hasCol('description')) {
    $db->exec("ALTER TABLE products ADD COLUMN description LONGTEXT NULL AFTER short_description");
}
if (!$hasCol('primary_image')) {
    $db->exec("ALTER TABLE products ADD COLUMN primary_image VARCHAR(255) NULL AFTER description");
}
if (!$hasCol('video_url')) {
    $db->exec("ALTER TABLE products ADD COLUMN video_url VARCHAR(255) NULL AFTER primary_image");
}
if (!$hasCol('min_price')) {
    $db->exec("ALTER TABLE products ADD COLUMN min_price DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER video_url");
}
if (!$hasCol('max_price')) {
    $db->exec("ALTER TABLE products ADD COLUMN max_price DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER min_price");
}
if (!$hasCol('available_quantity')) {
    $db->exec("ALTER TABLE products ADD COLUMN available_quantity INT NOT NULL DEFAULT 0 AFTER moq_unit_id");
}
if (!$hasCol('sample_available')) {
    $db->exec("ALTER TABLE products ADD COLUMN sample_available TINYINT(1) NOT NULL DEFAULT 0 AFTER available_quantity");
}
if (!$hasCol('sample_price')) {
    $db->exec("ALTER TABLE products ADD COLUMN sample_price DECIMAL(12, 2) NULL AFTER sample_available");
}
if (!$hasCol('material')) {
    $db->exec("ALTER TABLE products ADD COLUMN material VARCHAR(200) NULL AFTER sample_price");
}
if (!$hasCol('colors')) {
    $db->exec("ALTER TABLE products ADD COLUMN colors VARCHAR(255) NULL AFTER material");
}
if (!$hasCol('size')) {
    $db->exec("ALTER TABLE products ADD COLUMN size VARCHAR(200) NULL AFTER colors");
}
if (!$hasCol('weight')) {
    $db->exec("ALTER TABLE products ADD COLUMN weight VARCHAR(100) NULL AFTER size");
}
if (!$hasCol('customization_details')) {
    $db->exec("ALTER TABLE products ADD COLUMN customization_details TEXT NULL AFTER weight");
}
if (!$hasCol('custom_logo')) {
    $db->exec("ALTER TABLE products ADD COLUMN custom_logo TINYINT(1) NOT NULL DEFAULT 0 AFTER customization_details");
}
if (!$hasCol('custom_color')) {
    $db->exec("ALTER TABLE products ADD COLUMN custom_color TINYINT(1) NOT NULL DEFAULT 0 AFTER custom_logo");
}
if (!$hasCol('custom_packaging')) {
    $db->exec("ALTER TABLE products ADD COLUMN custom_packaging TINYINT(1) NOT NULL DEFAULT 0 AFTER custom_color");
}
if (!$hasCol('oem_available')) {
    $db->exec("ALTER TABLE products ADD COLUMN oem_available TINYINT(1) NOT NULL DEFAULT 0 AFTER custom_packaging");
}
if (!$hasCol('odm_available')) {
    $db->exec("ALTER TABLE products ADD COLUMN odm_available TINYINT(1) NOT NULL DEFAULT 0 AFTER oem_available");
}
if (!$hasCol('private_label')) {
    $db->exec("ALTER TABLE products ADD COLUMN private_label TINYINT(1) NOT NULL DEFAULT 0 AFTER odm_available");
}
if (!$hasCol('lead_time')) {
    $db->exec("ALTER TABLE products ADD COLUMN lead_time VARCHAR(100) NULL AFTER city_id");
}
if (!$hasCol('shipping_info')) {
    $db->exec("ALTER TABLE products ADD COLUMN shipping_info TEXT NULL AFTER lead_time");
}
if (!$hasCol('is_featured')) {
    $db->exec("ALTER TABLE products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
}
if (!$hasCol('featured_order')) {
    $db->exec("ALTER TABLE products ADD COLUMN featured_order INT NOT NULL DEFAULT 0 AFTER is_featured");
}
if (!$hasCol('view_count')) {
    $db->exec("ALTER TABLE products ADD COLUMN view_count INT NOT NULL DEFAULT 0 AFTER featured_order");
}

// Modify status enum
$db->exec("ALTER TABLE products MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE', 'DRAFT', 'PUBLISHED') NOT NULL DEFAULT 'PUBLISHED'");

echo "4. Creating product_images table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_img_prod (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "5. Creating product_inquiries table...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS product_inquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    buyer_name VARCHAR(150) NOT NULL,
    buyer_email VARCHAR(150) NOT NULL,
    buyer_phone VARCHAR(50) NULL,
    company_name VARCHAR(150) NULL,
    target_quantity INT NOT NULL DEFAULT 1,
    lead_time_req VARCHAR(100) NULL,
    specifications TEXT NOT NULL,
    status ENUM('PENDING', 'RESPONDED', 'CLOSED') NOT NULL DEFAULT 'PENDING',
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_inq_prod (product_id),
    INDEX idx_inq_supp (supplier_id),
    INDEX idx_inq_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "6. Seeding initial categories...\n";
$categories = [
    [
        'name' => 'Sports & Fitness',
        'slug' => 'sports-and-fitness',
        'icon' => 'fitness_center',
        'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=600&q=80',
        'description' => 'Professional gym equipment, cardio machines, strength training weights, and athletic gear.',
        'display_order' => 1
    ],
    [
        'name' => 'Apparel & Accessories',
        'slug' => 'apparel-and-accessories',
        'icon' => 'apparel',
        'image' => 'https://images.unsplash.com/photo-1556906781-9a412961c28c?auto=format&fit=crop&w=600&q=80',
        'description' => 'Athletic sportswear, dry-fit jerseys, compression wear, sports socks, and wristbands.',
        'display_order' => 2
    ],
    [
        'name' => 'Cricket & Field Sports',
        'slug' => 'cricket-and-field-sports',
        'icon' => 'sports_cricket',
        'image' => 'https://images.unsplash.com/photo-1531415074868-036b1c57e329?auto=format&fit=crop&w=600&q=80',
        'description' => 'Handcrafted English and Kashmir willow cricket bats, protective gear, balls, and wickets.',
        'display_order' => 3
    ],
    [
        'name' => 'Football & Inflatables',
        'slug' => 'football-and-inflatables',
        'icon' => 'sports_soccer',
        'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
        'description' => 'Hand-stitched & thermally bonded match footballs, volleyballs, and training accessories.',
        'display_order' => 4
    ],
    [
        'name' => 'Racquet & Court Sports',
        'slug' => 'racquet-and-court-sports',
        'icon' => 'sports_tennis',
        'image' => 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?auto=format&fit=crop&w=600&q=80',
        'description' => 'Badminton racquets, tennis equipment, shuttlecocks, and court netting systems.',
        'display_order' => 5
    ],
    [
        'name' => 'Agility & Speed Training',
        'slug' => 'agility-and-speed-training',
        'icon' => 'timer',
        'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
        'description' => 'Training hurdles, agility ladders, slalom poles, reaction lights, and speed cones.',
        'display_order' => 6
    ],
    [
        'name' => 'Boxing & Combat Sports',
        'slug' => 'boxing-and-combat-sports',
        'icon' => 'sports_mma',
        'image' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=600&q=80',
        'description' => 'Genuine leather boxing gloves, punch bags, hand wraps, and MMA protection gear.',
        'display_order' => 7
    ],
    [
        'name' => 'Outdoor & Adventure Gear',
        'slug' => 'outdoor-and-adventure-gear',
        'icon' => 'hiking',
        'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=600&q=80',
        'description' => 'Trekking backpacks, camping tents, hydration bladders, and tactical outdoor ropes.',
        'display_order' => 8
    ]
];

$catStmt = $db->prepare("
    INSERT INTO categories (name, slug, icon, image, description, status, display_order)
    VALUES (:name, :slug, :icon, :image, :description, 'ACTIVE', :display_order)
    ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon), image = VALUES(image), description = VALUES(description), display_order = VALUES(display_order)
");

foreach ($categories as $cat) {
    $catStmt->execute($cat);
}

// Fetch category map
$categoryMap = $db->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
$categoryMap = array_flip($categoryMap); // slug => id

echo "7. Seeding initial subcategories...\n";
$subcategories = [
    // Sports & Fitness subcategories
    [
        'category_id' => $categoryMap['sports-and-fitness'],
        'name' => 'Gym & Strength Equipment',
        'slug' => 'gym-and-strength-equipment',
        'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=600&q=80',
        'description' => 'Power racks, barbells, bumper plates, and dumbbell sets.',
        'display_order' => 1
    ],
    [
        'category_id' => $categoryMap['sports-and-fitness'],
        'name' => 'Aerobic & Cardio Machines',
        'slug' => 'aerobic-and-cardio-machines',
        'image' => 'https://images.unsplash.com/photo-1576678927484-cc907957088c?auto=format&fit=crop&w=600&q=80',
        'description' => 'Commercial treadmills, spin bikes, rowers, and stair climbers.',
        'display_order' => 2
    ],
    // Agility & Speed Training
    [
        'category_id' => $categoryMap['agility-and-speed-training'],
        'name' => 'Training Hurdles & Ladders',
        'slug' => 'training-hurdles-and-ladders',
        'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
        'description' => 'Adjustable height agility hurdles, quick-foot speed ladders, and markers.',
        'display_order' => 1
    ],
    [
        'category_id' => $categoryMap['agility-and-speed-training'],
        'name' => 'Cones & Marker Sets',
        'slug' => 'cones-and-marker-sets',
        'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=600&q=80',
        'description' => 'Saucer marker cones, traffic training cones, and carry stands.',
        'display_order' => 2
    ],
    // Cricket & Field Sports
    [
        'category_id' => $categoryMap['cricket-and-field-sports'],
        'name' => 'Cricket Bats (English & Kashmir)',
        'slug' => 'cricket-bats',
        'image' => 'https://images.unsplash.com/photo-1531415074868-036b1c57e329?auto=format&fit=crop&w=600&q=80',
        'description' => 'Professional Grade 1 English Willow and durable Kashmir Willow bats.',
        'display_order' => 1
    ],
    [
        'category_id' => $categoryMap['cricket-and-field-sports'],
        'name' => 'Protective Gear & Pads',
        'slug' => 'cricket-protective-gear',
        'image' => 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?auto=format&fit=crop&w=600&q=80',
        'description' => 'Leg guards, batting gloves, helmets, and thigh pads.',
        'display_order' => 2
    ],
    // Football & Inflatables
    [
        'category_id' => $categoryMap['football-and-inflatables'],
        'name' => 'Match & Training Footballs',
        'slug' => 'match-training-footballs',
        'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
        'description' => 'Hand-stitched synthetic PU footballs certified to standard weights.',
        'display_order' => 1
    ]
];

$subStmt = $db->prepare("
    INSERT INTO subcategories (category_id, name, slug, image, description, status, display_order)
    VALUES (:category_id, :name, :slug, :image, :description, 'ACTIVE', :display_order)
    ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), name = VALUES(name), image = VALUES(image), description = VALUES(description), display_order = VALUES(display_order)
");

foreach ($subcategories as $sub) {
    $subStmt->execute($sub);
}

// Fetch subcategory map
$subMap = $db->query("SELECT id, slug FROM subcategories")->fetchAll(PDO::FETCH_KEY_PAIR);
$subMap = array_flip($subMap); // slug => id

echo "8. Seeding rich products matching Stitch specifications...\n";

// Update existing 2 test products first
$db->exec("
    UPDATE products SET 
        category_id = {$categoryMap['cricket-and-field-sports']},
        subcategory_id = {$subMap['cricket-bats']},
        slug = 'grade-a-english-willow-cricket-bat',
        sku = 'SJ-CRK-ENG01',
        brand_name = 'Meerut Pro Series',
        short_description = 'Hand-crafted Grade 1 English Willow cricket bat with balanced pick up and thick edges.',
        description = 'Crafted from selected hand-split Grade 1 English willow grown in sustainable UK forests and seasoned in Meerut. Features massive contoured 40mm edges, balanced light pick up, semi-oval 12-piece cane handle with rubber grip, and toe guard protection.',
        primary_image = 'https://images.unsplash.com/photo-1531415074868-036b1c57e329?auto=format&fit=crop&w=800&q=80',
        min_price = 3800.00,
        max_price = 4500.00,
        available_quantity = 500,
        sample_available = 1,
        sample_price = 4200.00,
        material = 'Grade-1 English Willow, 12-Piece Cane Handle',
        colors = 'Natural Wood, Black & Blue Grip',
        size = 'Full Size Short Handle (SH)',
        weight = '1160g - 1220g (2.9 - 2.11 lbs)',
        custom_logo = 1,
        custom_color = 1,
        custom_packaging = 1,
        oem_available = 1,
        odm_available = 1,
        private_label = 1,
        lead_time = '10 - 20 Business Days',
        shipping_info = 'Ex-Factory Meerut or FOB Nhava Sheva Port, Mumbai',
        is_featured = 1,
        featured_order = 2,
        status = 'PUBLISHED'
    WHERE id = 1
");

$db->exec("
    UPDATE products SET 
        category_id = {$categoryMap['football-and-inflatables']},
        subcategory_id = {$subMap['match-training-footballs']},
        slug = 'fifa-quality-pro-synthetic-leather-football',
        sku = 'SJ-BALL-FB02',
        brand_name = 'Jalandhar Match Elite',
        short_description = 'Thermally bonded 32-panel FIFA standard size 5 football made with Japanese PU.',
        description = 'Engineered in Jalandhar leather & sports cluster. 32-panel aerodynamic design made with textured microfiber PU synthetic leather, 4-ply poly-cotton lining, high-rebound butyl bladder for maximum air retention and true flight precision.',
        primary_image = 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=800&q=80',
        min_price = 720.00,
        max_price = 850.00,
        available_quantity = 2500,
        sample_available = 1,
        sample_price = 900.00,
        material = 'Textured Microfiber Japanese PU, Butyl Bladder',
        colors = 'White/Blue/Neon Red',
        size = 'Official Size 5',
        weight = '420g - 440g',
        custom_logo = 1,
        custom_color = 1,
        custom_packaging = 1,
        oem_available = 1,
        odm_available = 1,
        private_label = 1,
        lead_time = '14 - 25 Business Days',
        shipping_info = 'FOB Mundra Port / CIF Worldwide Sea Freight',
        is_featured = 1,
        featured_order = 3,
        status = 'PUBLISHED'
    WHERE id = 2
");

// Insert the flagship product from Stitch screen: Adjustable Agility Training Hurdle
$prodStmt = $db->prepare("
    INSERT INTO products (
        id, supplier_id, category_id, subcategory_id, title, slug, sku, brand_name,
        short_description, description, primary_image, video_url,
        price, min_price, max_price, price_unit_id, moq_quantity, moq_unit_id,
        available_quantity, sample_available, sample_price,
        material, colors, size, weight, customization_details,
        custom_logo, custom_color, custom_packaging, oem_available, odm_available, private_label,
        country_id, state_id, city_id, lead_time, shipping_info,
        status, is_featured, featured_order, view_count
    ) VALUES (
        3, 1, :cat_id, :subcat_id, :title, :slug, :sku, :brand,
        :short_desc, :desc, :image, :video,
        :price, :min_p, :max_p, 1, 50, 1,
        12000, 1, 480.00,
        :material, :colors, :size, :weight, :custom_details,
        1, 1, 1, 1, 1, 1,
        1, 1, 1, :lead_time, :shipping,
        'PUBLISHED', 1, 1, 1420
    )
    ON DUPLICATE KEY UPDATE
        category_id = VALUES(category_id), subcategory_id = VALUES(subcategory_id),
        title = VALUES(title), slug = VALUES(slug), sku = VALUES(sku),
        primary_image = VALUES(primary_image), min_price = VALUES(min_price), max_price = VALUES(max_price),
        is_featured = VALUES(is_featured), featured_order = VALUES(featured_order), status = 'PUBLISHED'
");

$prodStmt->execute([
    ':cat_id' => $categoryMap['agility-and-speed-training'],
    ':subcat_id' => $subMap['training-hurdles-and-ladders'],
    ':title' => 'Adjustable Agility Training Hurdle (6" to 12")',
    ':slug' => 'adjustable-agility-training-hurdle',
    ':sku' => 'SJ-AGL-HD03',
    ':brand' => 'ApexSport Manufacturing',
    ':short_desc' => 'High-impact shatterproof PVC agility training hurdle with quick dual-height twist adjustment.',
    ':desc' => 'Engineered for high-intensity athletic conditioning, soccer drills, track and field, and functional rehabilitation. Built from ultra-durable shatterproof PVC tubing that bends and snaps back when stepped on. Features an ergonomic rotating base with secure locking pins for instant height switching between 6-inch and 12-inch elevations. Lightweight, easily nestable, and weather-resistant for indoor courts or outdoor turf.',
    ':image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=800&q=80',
    ':video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ':price' => 320.00,
    ':min_p' => 280.00,
    ':max_p' => 380.00,
    ':material' => 'High-Impact Shatterproof PVC & ABS Resin',
    ':colors' => 'Hi-Vis Fluorescent Green, Safety Orange, Electric Yellow',
    ':size' => 'Width 45cm x Height 15cm-30cm (6"-12" Adjustable)',
    ':weight' => '380g per unit',
    ':custom_details' => 'Silk-screen logo on horizontal crossbar, custom Pantone colors on orders > 500 units, customized carry bag with branded drawstrings.',
    ':lead_time' => '7 - 15 Business Days',
    ':shipping' => 'Express air courier for samples (4-6 days), Sea freight LCL/FCL worldwide.'
]);

// Insert more showcase products for rich listing
$productsData = [
    [
        'id' => 4,
        'title' => 'Olympic Cast Iron Barbell Plates (Set of 100kg)',
        'slug' => 'olympic-cast-iron-barbell-plates-set',
        'cat' => $categoryMap['sports-and-fitness'],
        'sub' => $subMap['gym-and-strength-equipment'],
        'sku' => 'SJ-GYM-PL04',
        'brand' => 'ApexIron Foundry',
        'min_p' => 12500.00,
        'max_p' => 14800.00,
        'moq' => 10,
        'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
        'material' => 'Precision Heavy Cast Iron with Hammertone Powder Coat',
        'is_featured' => 1,
        'feat_order' => 4
    ],
    [
        'id' => 5,
        'title' => 'Speed Agility Footwork Ladder (6m Quick-Fold)',
        'slug' => 'speed-agility-footwork-ladder-6m',
        'cat' => $categoryMap['agility-and-speed-training'],
        'sub' => $subMap['training-hurdles-and-ladders'],
        'sku' => 'SJ-AGL-LD05',
        'brand' => 'ApexTrack Pro',
        'min_p' => 240.00,
        'max_p' => 310.00,
        'moq' => 100,
        'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=800&q=80',
        'material' => 'Heavy-Duty PP Rungs & Reinforced Nylon Webbing',
        'is_featured' => 1,
        'feat_order' => 5
    ],
    [
        'id' => 6,
        'title' => 'Saucer Field Cones with Metal Carrier (50-Pack)',
        'slug' => 'saucer-field-cones-metal-carrier-50-pack',
        'cat' => $categoryMap['agility-and-speed-training'],
        'sub' => $subMap['cones-and-marker-sets'],
        'sku' => 'SJ-AGL-CN06',
        'brand' => 'ApexSport Manufacturing',
        'min_p' => 380.00,
        'max_p' => 450.00,
        'moq' => 50,
        'image' => 'https://images.unsplash.com/photo-1517649763962-0c623266ddc0?auto=format&fit=crop&w=800&q=80',
        'material' => 'Flexible Low-Density Polyethylene (LDPE)',
        'is_featured' => 0,
        'feat_order' => 6
    ]
];

$insMore = $db->prepare("
    INSERT INTO products (
        id, supplier_id, category_id, subcategory_id, title, slug, sku, brand_name,
        short_description, description, primary_image,
        price, min_price, max_price, price_unit_id, moq_quantity, moq_unit_id,
        available_quantity, sample_available, sample_price,
        material, colors, size, weight,
        custom_logo, custom_color, custom_packaging, oem_available, odm_available, private_label,
        country_id, state_id, city_id, lead_time, shipping_info,
        status, is_featured, featured_order
    ) VALUES (
        :id, 1, :cat_id, :subcat_id, :title, :slug, :sku, :brand,
        :short_desc, :desc, :image,
        :price, :min_p, :max_p, 1, :moq, 1,
        5000, 1, :sample_p,
        :material, 'Multi-Color / Branded Pantone', 'Standard Competition', 'Commercial Grade',
        1, 1, 1, 1, 1, 1,
        1, 1, 1, '12-20 Days', 'Standard B2B Global Freight',
        'PUBLISHED', :is_feat, :feat_order
    )
    ON DUPLICATE KEY UPDATE
        title = VALUES(title), slug = VALUES(slug), primary_image = VALUES(primary_image),
        min_price = VALUES(min_price), max_price = VALUES(max_price), is_featured = VALUES(is_featured),
        status = 'PUBLISHED'
");

foreach ($productsData as $pd) {
    $insMore->execute([
        ':id' => $pd['id'],
        ':cat_id' => $pd['cat'],
        ':subcat_id' => $pd['sub'],
        ':title' => $pd['title'],
        ':slug' => $pd['slug'],
        ':sku' => $pd['sku'],
        ':brand' => $pd['brand'],
        ':short_desc' => 'High performance commercial sports equipment engineered for durability and athlete performance.',
        ':desc' => 'Precision manufactured at certified sports facilities in India. Built for commercial gyms, sports academies, clubs, and international distributors with full OEM & custom branding support.',
        ':image' => $pd['image'],
        ':price' => $pd['min_p'],
        ':min_p' => $pd['min_p'],
        ':max_p' => $pd['max_p'],
        ':moq' => $pd['moq'],
        ':sample_p' => $pd['min_p'],
        ':material' => $pd['material'],
        ':is_feat' => $pd['is_featured'],
        ':feat_order' => $pd['feat_order']
    ]);
}

echo "9. Seeding product gallery images for flagship product #3...\n";
$images = [
    [
        'product_id' => 3,
        'image_path' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=800&q=80',
        'is_primary' => 1,
        'display_order' => 1
    ],
    [
        'product_id' => 3,
        'image_path' => 'https://images.unsplash.com/photo-1517649763962-0c623266ddc0?auto=format&fit=crop&w=800&q=80',
        'is_primary' => 0,
        'display_order' => 2
    ],
    [
        'product_id' => 3,
        'image_path' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=800&q=80',
        'is_primary' => 0,
        'display_order' => 3
    ],
    [
        'product_id' => 3,
        'image_path' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=800&q=80',
        'is_primary' => 0,
        'display_order' => 4
    ]
];

$imgStmt = $db->prepare("
    INSERT INTO product_images (product_id, image_path, is_primary, display_order)
    VALUES (:product_id, :image_path, :is_primary, :display_order)
");
$db->exec("DELETE FROM product_images WHERE product_id = 3");
foreach ($images as $img) {
    $imgStmt->execute($img);
}

echo "Migration and Seeding completed successfully!\n";
