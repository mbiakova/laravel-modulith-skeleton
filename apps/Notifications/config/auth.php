<?php

declare(strict_types=1);

use Apps\Notifications\Models\UserShadow;

return [
    'principals' => [
        'notifications' => UserShadow::class,
    ],
];
