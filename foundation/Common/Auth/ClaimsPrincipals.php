<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

/** For an identity provider outside the application: the token is all the module knows of the user. */
final readonly class ClaimsPrincipals implements PrincipalResolver
{
    public function resolve(Identity $identity): Principal
    {
        return new ClaimsPrincipal($identity);
    }
}
