<?php

declare(strict_types=1);

namespace Apps\Iam\Actions;

use Apps\Iam\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

/** rpc: an opaque token only iam can read. jwt and gateway: a JWT anyone with iam's public key can verify. */
final readonly class IssueToken
{
    public function execute(User $user): string
    {
        if (config('auth.token_validation.strategy') !== 'rpc') {
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
