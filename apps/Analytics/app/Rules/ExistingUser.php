<?php

declare(strict_types=1);

namespace Apps\Analytics\Rules;

use Foundation\Common\Validation\ReferenceRule;
use Foundation\Iam\Contracts\IamService;

/**
 * Asks iam through its contract: a plain call when iam runs here, a signed RPC call otherwise.
 *
 * @extends ReferenceRule<array{id: int, name: string}>
 */
final class ExistingUser extends ReferenceRule
{
    public function __construct(private readonly IamService $iam) {}

    protected function resolve(int $id): ?array
    {
        return $this->iam->findUser($id);
    }

    protected function missing(): string
    {
        return 'No user has this id.';
    }
}
