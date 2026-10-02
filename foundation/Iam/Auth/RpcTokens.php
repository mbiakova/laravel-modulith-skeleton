<?php

declare(strict_types=1);

namespace Foundation\Iam\Auth;

use Foundation\Common\Auth\TokenValidator;
use Foundation\Iam\Contracts\IamService;

/** The token is opaque: only iam knows whose it is. */
final readonly class RpcTokens implements TokenValidator
{
    public function __construct(private IamService $iam) {}

    public function validate(string $token): ?int
    {
        return $this->iam->findUserByToken($token)['id'] ?? null;
    }
}
