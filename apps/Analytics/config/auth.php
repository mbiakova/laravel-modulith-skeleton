<?php

declare(strict_types=1);

use Apps\Analytics\Models\UserShadow;

return [
    'principals' => [
        'analytics' => UserShadow::class,
    ],
];
