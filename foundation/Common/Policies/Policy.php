<?php

declare(strict_types=1);

namespace Foundation\Common\Policies;

use BackedEnum;
use Foundation\Common\Auth\Principal;
use Foundation\Iam\Contracts\IamService;

/** Base of every module's policies: a gesture is allowed when iam granted its permission to the user. */
abstract class Policy
{
    public function __construct(protected readonly IamService $iam) {}

    protected function allows(mixed $user, BackedEnum $permission): bool
    {
        return $user instanceof Principal && in_array((string) $permission->value, $this->iam->grants($user->id()), true);
    }
}
