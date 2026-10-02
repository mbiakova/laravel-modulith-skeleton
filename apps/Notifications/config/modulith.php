<?php

declare(strict_types=1);

use Apps\Notifications\Handlers\SendWelcome;
use Foundation\Iam\Events\IamEvent;

return [
    'events' => [
        'listen' => [
            IamEvent::UserRegistered->value => [SendWelcome::class],
        ],
    ],
];
