<?php

declare(strict_types=1);

// sqlite by default; with DB_CONNECTION=pgsql, the `notifications` database, written by a least-privilege role and migrated by its owner.
$connection = static fn (string $username, string $password): array => env('DB_CONNECTION') === 'pgsql' ? [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('NOTIFICATIONS_DATABASE', 'notifications'),
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8',
    'search_path' => 'public',
    'sslmode' => 'prefer',
] : [
    'driver' => 'sqlite',
    'database' => env('NOTIFICATIONS_DATABASE', database_path('notifications.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => true,
];

return [
    'connections' => [
        'notifications' => $connection((string) env('DB_APP_USERNAME', 'modulith_app'), (string) env('DB_APP_PASSWORD', '')),
        'notifications_owner' => $connection((string) env('DB_USERNAME', 'modulith'), (string) env('DB_PASSWORD', '')),
    ],
];
