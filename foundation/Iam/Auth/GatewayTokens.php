<?php

declare(strict_types=1);

namespace Foundation\Iam\Auth;

use Foundation\Common\Auth\TokenValidator;

/** The token is `{id}.{exp}.{hmac}`, signed by a gateway that already authenticated the client. */
final readonly class GatewayTokens implements TokenValidator
{
    public function __construct(private string $secret) {}

    public static function sign(int $id, int $expiresAt, string $secret): string
    {
        return "{$id}.{$expiresAt}.".hash_hmac('sha256', "{$id}.{$expiresAt}", $secret);
    }

    public function validate(string $token): ?int
    {
        [$id, $expiresAt] = explode('.', $token, 3) + ['', ''];

        if (! ctype_digit($id) || ! ctype_digit($expiresAt) || (int) $expiresAt < time()) {
            return null;
        }

        return hash_equals(self::sign((int) $id, (int) $expiresAt, $this->secret), $token) ? (int) $id : null;
    }
}
