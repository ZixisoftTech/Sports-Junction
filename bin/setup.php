<?php
declare(strict_types=1);

/**
 * Sports Junction — Database Migration and Super Admin Setup CLI
 */

require_once dirname(__DIR__) . '/app/Helpers/Env.php';
\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

// Register autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\User;

echo "========================================================\n";
echo " SPORTS JUNCTION — SUPER ADMIN SETUP & DATABASE SEEDER \n";
echo "========================================================\n\n";

try {
    $pdo = Database::getConnection();
    echo "[✓] Connected to MySQL database successfully.\n";

    // 1. Run Schema
    $schemaFile = dirname(__DIR__) . '/database/schema.sql';
    if (file_exists($schemaFile)) {
        $schemaSql = file_get_contents($schemaFile);
        $pdo->exec($schemaSql);
        echo "[✓] Database schema initialized.\n";
    }

    // 2. Run Seeders
    $seederFile = dirname(__DIR__) . '/database/seeders.sql';
    if (file_exists($seederFile)) {
        $seederSql = file_get_contents($seederFile);
        $pdo->exec($seederSql);
        echo "[✓] Master seeds and test fixtures imported.\n";
    }

    // 3. Initialize Super Admin Account
    $adminEmail = \App\Helpers\Env::get('SUPER_ADMIN_EMAIL', 'sportjunctions@gmail.com');
    $adminName = \App\Helpers\Env::get('SUPER_ADMIN_NAME', 'Super Administrator');
    $adminPassword = \App\Helpers\Env::get('SUPER_ADMIN_PASSWORD', 'Sport@123');

    $existing = User::findByEmail($adminEmail);

    if ($existing) {
        echo "[!] Super Admin account already exists: {$adminEmail}. Skipping recreation.\n";
    } else {
        $userId = User::create([
            'name'                 => $adminName,
            'email'                => $adminEmail,
            'password'             => $adminPassword,
            'role'                 => 'SUPER_ADMIN',
            'status'               => 'ACTIVE',
            'must_change_password' => 0,
            'phone'                => '+91 9876543210',
        ]);
        echo "[✓] Super Admin account created successfully! (User ID: {$userId})\n";
        echo "    Email: {$adminEmail}\n";
        echo "    Role:  SUPER_ADMIN\n";
        echo "    Status: ACTIVE\n";
        echo "    [Security Notice: Password hashed securely with Bcrypt cost 12]\n";
    }

    echo "\n[✓] Setup completed successfully!\n";
    exit(0);
} catch (\Throwable $e) {
    echo "\n[✗] Setup Error: " . $e->getMessage() . "\n";
    exit(1);
}
