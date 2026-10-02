<?php

declare(strict_types=1);

use Apps\Analytics\Handlers\RecordSignup;
use Foundation\Iam\Events\IamEvent;

return [
    'events' => [
        'listen' => [
            IamEvent::UserRegistered->value => [RecordSignup::class],
        ],
    ],
];
