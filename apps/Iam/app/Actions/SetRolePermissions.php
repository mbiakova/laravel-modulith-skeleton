<?php

declare(strict_types=1);

namespace Apps\Iam\Actions;

use BackedEnum;
use Foundation\Iam\Services\IamRpcService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/** A role's permissions change for every user holding it: all cached grants are dropped once it is committed. */
final readonly class SetRolePermissions
{
    public function __construct(private IamRpcService $iam) {}

    /** @param list<BackedEnum> $permissions */
    public function execute(string $role, array $permissions): void
    {
        Role::findOrCreate($role, 'web')
            ->syncPermissions(array_map(static fn (BackedEnum $permission): string => (string) $permission->value, $permissions));

        DB::afterCommit(fn () => $this->iam->flushGrants());
    }
}
