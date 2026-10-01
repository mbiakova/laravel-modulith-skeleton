<?php

declare(strict_types=1);

namespace Shared\Auth;

trait HasPrincipal
{
    /** The principal the Authenticate middleware bound, on every surface. */
    protected function principal(): ?Principal
    {
        $user = request()->user();

        return $user instanceof Principal ? $user : null;
    }

    protected function principalId(): ?int
    {
        return $this->principal()?->id();
    }
}
