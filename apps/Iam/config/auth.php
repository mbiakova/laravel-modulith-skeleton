<?php

declare(strict_types=1);

use Apps\Iam\Models\User;

return [
    'principals' => [
        'iam' => User::class,
    ],
];
