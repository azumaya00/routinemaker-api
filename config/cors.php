<?php

return [
    // SPA の認証関連も CORS 対象に含める。
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],

    'allowed_methods' => ['*'],

    // 本番環境では CORS_ALLOWED_ORIGINS を明示的に設定する必要がある
    // local では localhost:3000 を許可、production では本番ドメインのみ許可
    'allowed_origins' => array_filter(array_map(
        static fn (string $origin): string => trim($origin),
        explode(',', env('CORS_ALLOWED_ORIGINS', env('APP_ENV') === 'production' 
            ? 'https://routinemaker.yuru-labo.com' 
            : 'http://localhost:3000'))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),
];
