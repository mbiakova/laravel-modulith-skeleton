<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** The token proves an identity, the resolver turns it into the user, or the request 401s. */
class Authenticate
{
    public function __construct(private readonly TokenValidator $tokens, private readonly PrincipalResolver $principals) {}

    public function handle(Request $request, Closure $next): Response
    {
        $identity = $this->resolve($request);
        $principal = $identity === null ? null : $this->principals->resolve($identity);

        if ($principal === null) {
            throw new AuthenticationException;
        }

        // Set the guard user (not just the request resolver) so authorize()/Gate sees it.
        Auth::setUser($principal);

        return $next($request);
    }

    /** A strategy that names a `header` in its config reads its token there; the others read the bearer token. */
    protected function resolve(Request $request): ?Identity
    {
        $header = config('auth.token_validation.'.config('auth.token_validation.strategy').'.header');
        $token = is_string($header) ? $request->header($header) : $request->bearerToken();

        return is_string($token) && $token !== '' ? $this->tokens->validate($token) : null;
    }
}
