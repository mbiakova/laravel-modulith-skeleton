<?php

declare(strict_types=1);

namespace Apps\Iam\Actions;

use Apps\Iam\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

/** The token the client sends back: a JWT under the jwt strategy, an opaque one iam stores the hash of otherwise. */
final readonly class IssueToken
{
    public function execute(User $user): string
    {
        if (config('auth.token_validation.strategy') === 'jwt') {
            return JWT::encode([
                'sub' => (string) $user->getKey(),
                'iat' => time(),
                'exp' => time() + (int) config('auth.token_validation.jwt.ttl'),
            ], (string) config('auth.token_validation.jwt.private_key'), 'RS256');
        }

        $token = Str::random(40);
        $user->forceFill(['api_token' => hash('sha256', $token)])->save();

        return $token;
    }
}
