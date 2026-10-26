<?php

/*
| CORS for browser frontends hosted on another origin.
| CORS_ALLOWED_ORIGINS: comma-separated origins, default "*" (any; the API is token based).
| When specific origins are listed, cookies (credentials) are allowed as well.
*/
$origins = array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*')))));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins ?: ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 600,

    'supports_credentials' => ! in_array('*', $origins ?: ['*'], true),

];
