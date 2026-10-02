<?php

declare(strict_types=1);

namespace Apps\Iam\Http\Controllers;

use Apps\Iam\Models\User;
use Foundation\Iam\Auth\GatewayTokens;
use Foundation\Iam\Auth\JwtTokens;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The target of a proxy's forward-auth (Traefik ForwardAuth, nginx auth_request): a valid JWT gets the X-Identity the modules trust. */
final class IdentityController
{
    public function __invoke(Request $request, JwtTokens $tokens): Response
    {
        $id = $tokens->validate((string) $request->bearerToken())?->id;

        if (! is_int($id) || ! User::query()->whereKey($id)->exists()) {
            return response()->noContent(401);
        }

        $signed = GatewayTokens::sign($id, time() + 60, (string) config('auth.token_validation.gateway.secret'));

        return response()->noContent()->header((string) config('auth.token_validation.gateway.header'), $signed);
    }
}
