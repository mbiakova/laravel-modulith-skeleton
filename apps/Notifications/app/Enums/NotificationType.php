<?php

declare(strict_types=1);

namespace Apps\Notifications\Enums;

use Apps\Notifications\Models\Notification;

/** What a notification is about: the client routes on it, the server renders its title from it. */
enum NotificationType: string
{
    case Welcome = 'user.welcome';

    public function title(Notification $notification): string
    {
        return match ($this) {
            self::Welcome => __('notifications::messages.welcome', ['name' => $notification->recipient->name ?? '']),
        };
    }
}
