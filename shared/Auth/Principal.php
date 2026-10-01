<?php

declare(strict_types=1);

namespace Shared\Auth;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The authenticated identity the kernel binds on a request. The consumer's identity module
 * decides what a principal carries; the kernel only requires what its own machinery uses:
 * a typed id, Authenticatable (so it can be the guard user) and Authorizable (Policies).
 */
interface Principal extends Authenticatable, Authorizable
{
    public function id(): int;
}
