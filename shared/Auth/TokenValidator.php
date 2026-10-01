<?php

declare(strict_types=1);

namespace Shared\Auth;

/**
 * Resolves a bearer token into a Principal. The consumer's identity module binds the
 * implementation (direct store lookup, RPC to the identity service, …); the Authenticate
 * middleware only consumes the contract.
 */
interface TokenValidator
{
    public function validate(string $token): ?Principal;
}
