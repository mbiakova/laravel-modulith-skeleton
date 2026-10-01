<?php

declare(strict_types=1);

namespace Foundation\Iam\Auth;

use Foundation\Iam\Contracts\IamService;
use Shared\Auth\Principal;
use Shared\Auth\TokenValidator;

final readonly class Tokens implements TokenValidator
{
    public function __construct(private IamService $iam) {}

    public function validate(string $token): ?Principal
    {
        $user = $this->iam->findUserByToken($token);

        return $user === null ? null : new AuthenticatedUser($user);
    }
}
