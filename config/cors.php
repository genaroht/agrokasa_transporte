<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    // Lee los orígenes permitidos desde .env
    // Ejemplo: CORS_ALLOWED_ORIGINS="http://localhost:4200,http://localhost:8080"
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
