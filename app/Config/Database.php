<?php
declare(strict_types=1);

use App\Helpers\Env;

return [
    'connection' => Env::get('DB_CONNECTION', 'mysql'),
    'host'       => Env::get('DB_HOST', '127.0.0.1'),
    'port'       => (int) Env::get('DB_PORT', 3306),
    'socket'     => Env::get('DB_SOCKET', ''),
    'database'   => Env::get('DB_DATABASE', 'sports_junction'),
    'username'   => Env::get('DB_USERNAME', 'root'),
    'password'   => Env::get('DB_PASSWORD', ''),
    'charset'    => 'utf8mb4',
    'options'    => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+05:30'",
    ]
];
