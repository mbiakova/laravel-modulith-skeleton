<?php

declare(strict_types=1);

namespace Foundation\Iam\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Foundation\Common\Auth\TokenValidator;
use InvalidArgumentException;
use Throwable;

/** The token is a JWT iam signed: the module checks it with iam's public key, without calling iam. */
final readonly class JwtTokens implements TokenValidator
{
    private Key $key;

    public function __construct(string $publicKey)
    {
        if ($publicKey === '') {
            throw new InvalidArgumentException('The jwt strategy needs AUTH_JWT_PUBLIC_KEY.');
        }

        $this->key = new Key($publicKey, 'RS256');
    }

    public function validate(string $token): ?int
    {
        try {
            $claims = JWT::decode($token, $this->key);
        } catch (Throwable) {
            return null;
        }

        return isset($claims->sub) && is_numeric($claims->sub) ? (int) $claims->sub : null;
    }
}
