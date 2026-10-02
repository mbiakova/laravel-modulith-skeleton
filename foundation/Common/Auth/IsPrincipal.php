<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;

/** What an Eloquent model needs to be a module's Principal. */
trait IsPrincipal
{
    use Authenticatable;
    use Authorizable;

    public function id(): int|string
    {
        $key = $this->getKey();

        return is_int($key) ? $key : (string) $key;
    }
}
