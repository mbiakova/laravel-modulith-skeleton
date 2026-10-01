<?php

declare(strict_types=1);

namespace Apps\Iam\Events;

use Modulith\Events\Event;

final class UserRegistered extends Event
{
    public function __construct(private readonly int $id) {}

    public function name(): string
    {
        return 'iam.user.registered';
    }

    /** @return array{id: int} */
    public function payload(): array
    {
        return ['id' => $this->id];
    }
}
