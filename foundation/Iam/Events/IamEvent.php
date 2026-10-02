<?php

declare(strict_types=1);

namespace Foundation\Iam\Events;

/** The names of the events iam publishes, shared with the modules that listen to them. */
enum IamEvent: string
{
    case UserRegistered = 'iam.user.registered';
}
