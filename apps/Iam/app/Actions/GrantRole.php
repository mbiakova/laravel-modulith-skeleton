<?php

declare(strict_types=1);

namespace Apps\Iam\Actions;

use Apps\Iam\Models\User;
use Foundation\Iam\Services\IamRpcService;
use Illuminate\Support\Facades\DB;

/** Every change to a user's roles goes through here, so its cached grants are forgotten once it is committed. */
final readonly class GrantRole
{
    public function __construct(private IamRpcService $iam) {}

    public function grant(User $user, string $role): void
    {
        $user->assignRole($role);
        DB::afterCommit(fn () => $this->iam->forgetGrants($user->id));
    }

    public function revoke(User $user, string $role): void
    {
        $user->removeRole($role);
        DB::afterCommit(fn () => $this->iam->forgetGrants($user->id));
    }
}
