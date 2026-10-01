<?php

declare(strict_types=1);

namespace Foundation\Iam\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Shared\Auth\Principal;

/** What every module knows of the user behind a request: what iam answered, not iam's model. */
final class AuthenticatedUser extends GenericUser implements Principal
{
    use Authorizable;

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }
}
