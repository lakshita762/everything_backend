<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:*',
        'http://127.0.0.1:*',
        'http://10.0.2.2:*',
        'https://localhost:*',
        'https://127.0.0.1:*',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', '*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];


