<?php
declare(strict_types=1);

use App\Helpers\Env;

return [
    'name'             => Env::get('APP_NAME', 'Sports Junction'),
    'tagline'          => Env::get('APP_TAGLINE', "India's Sports Manufacturing Marketplace"),
    'env'              => Env::get('APP_ENV', 'local'),
    'debug'            => (bool) Env::get('APP_DEBUG', true),
    'url'              => Env::get('APP_URL', 'http://localhost/Antigravity/Sports-Junction'),
    'session_lifetime' => (int) Env::get('SESSION_LIFETIME', 7200),
    'timezone'         => 'Asia/Kolkata',
];
