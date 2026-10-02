<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** The token gives a user id, the running module's database gives the user, or the request 401s. */
class Authenticate
{
    public function __construct(private readonly TokenValidator $tokens, private readonly Principals $principals) {}

    public function handle(Request $request, Closure $next): Response
    {
        $id = $this->resolve($request);
        $principal = $id === null ? null : $this->principals->find($id);

        if ($principal === null) {
            throw new AuthenticationException;
        }

        // Set the guard user (not just the request resolver) so authorize()/Gate sees it.
        Auth::setUser($principal);

        return $next($request);
    }

    protected function resolve(Request $request): ?int
    {
        $token = config('auth.token_validation.strategy') === 'gateway'
            ? $request->header((string) config('auth.token_validation.gateway.header'))
            : $request->bearerToken();

        return is_string($token) && $token !== '' ? $this->tokens->validate($token) : null;
    }
}
