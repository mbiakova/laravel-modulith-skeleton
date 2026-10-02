<?php

declare(strict_types=1);

namespace Foundation\Iam\Auth;

use Foundation\Common\Auth\PermissionSource;
use Foundation\Common\Auth\Principal;
use Foundation\Iam\Contracts\IamService;

/** iam holds the roles and permissions: every module asks it, through its contract. */
final readonly class IamPermissions implements PermissionSource
{
    public function __construct(private IamService $iam) {}

    public function grants(Principal $principal): array
    {
        return $this->iam->grants((int) $principal->id());
    }
}
