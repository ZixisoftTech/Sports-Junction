<?php
declare(strict_types=1);

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

use App\Models\User;

$adminEmail = \App\Helpers\Env::get('SUPER_ADMIN_EMAIL', 'sportjunctions@gmail.com');
$adminName = \App\Helpers\Env::get('SUPER_ADMIN_NAME', 'Super Administrator');
$adminPassword = \App\Helpers\Env::get('SUPER_ADMIN_PASSWORD', 'Sport@123');

$existing = User::findByEmail($adminEmail);
if ($existing) {
    echo "[!] Super Admin account already exists: {$adminEmail}. Skipping recreation.\n";
    exit(0);
}

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
exit(0);
