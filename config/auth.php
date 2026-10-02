<?php

use Foundation\Analytics\Enums\AnalyticsPermission;

return [

    // Authenticate sets the guard user itself: the provider never loads one, so it names no model.
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL'),
        ],
    ],

    // jwt | rpc | gateway: how a token becomes a user id, see the README's Authentication section.
    'token_validation' => [
        'strategy' => env('AUTH_TOKEN_VALIDATION_STRATEGY', 'jwt'),
        'jwt' => [
            // The PEM itself, or else the file `php artisan auth:jwt-keys` writes.
            'public_key' => env('AUTH_JWT_PUBLIC_KEY') ?: (is_file($public = storage_path('jwt-public.key')) ? file_get_contents($public) : null),
            'private_key' => env('AUTH_JWT_PRIVATE_KEY') ?: (is_file($private = storage_path('jwt-private.key')) ? file_get_contents($private) : null),
            'ttl' => (int) env('AUTH_JWT_TTL', 3600),
        ],
        'gateway' => [
            'header' => 'X-Identity',
            'secret' => env('AUTH_GATEWAY_SECRET'),
        ],
    ],

    // module => the model its users are read from; each module adds its entry in its config/auth.php.
    'principals' => [],

    // The permission enums of every module, in the foundation so iam knows them wherever it runs: iam:sync-permissions creates them.
    'permissions' => [
        AnalyticsPermission::class,
    ],

];
