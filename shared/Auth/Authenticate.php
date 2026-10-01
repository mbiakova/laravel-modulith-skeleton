<?php

declare(strict_types=1);

namespace Shared\Auth;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a request on a module's public surface: validates the bearer token through the
 * consumer-bound TokenValidator and binds the resulting Principal, or 401s. `resolve()` is the
 * seam for other resolution strategies (e.g. trusting a gateway-forwarded identity).
 *
 * It throws Laravel's own AuthenticationException: the shape of an error response belongs to the
 * consuming application, not to this package.
 */
class Authenticate
{
    public function __construct(private readonly TokenValidator $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $principal = $this->resolve($request);

        if ($principal === null) {
            throw new AuthenticationException;
        }

        // Set the guard user (not just the request resolver) so authorize()/Gate sees it.
        Auth::setUser($principal);

        return $next($request);
    }

    protected function resolve(Request $request): ?Principal
    {
        $token = $request->bearerToken();

        return $token === null ? null : $this->tokens->validate($token);
    }
}
