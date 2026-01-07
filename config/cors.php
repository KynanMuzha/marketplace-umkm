<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls your application's handling of Cross-Origin
    | Resource Sharing (CORS). This determines which cross-origin operations
    | may be executed in web browsers. You are free to adjust these settings
    | as needed for your application.
    |
    */

    'paths' => [
        'api/*',              // izinkan semua endpoint API
        'login',
        'logout',
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => ['*'], // semua metode: GET, POST, PUT, PATCH, DELETE

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // semua header boleh

    'exposed_headers' => [],

    'max_age' => 0,

    // Jika tidak pakai cookie (karena Sanctum token mode), maka false
    'supports_credentials' => false,
];