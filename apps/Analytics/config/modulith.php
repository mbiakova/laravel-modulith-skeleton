<?php

declare(strict_types=1);

use Apps\Analytics\Handlers\RecordSignup;

return [
    'events' => [
        'listen' => [
            'iam.user.registered' => [RecordSignup::class],
        ],
    ],
];
