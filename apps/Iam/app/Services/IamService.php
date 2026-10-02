<?php

declare(strict_types=1);

namespace Apps\Iam\Services;

use Apps\Iam\Models\User;
use Foundation\Iam\Contracts\IamService as Contract;

/** iam's answers to its contract; every caller reaches it through IamRpcService, in iam's context. */
final readonly class IamService implements Contract
{
    public function findUser(int $id): ?array
    {
        return $this->present(User::query()->find($id));
    }

    public function findUserByToken(string $token): ?array
    {
        return $this->present(User::query()->where('api_token', hash('sha256', $token))->first());
    }

    /** @return array{id: int, name: string}|null */
    private function present(?User $user): ?array
    {
        return $user === null ? null : ['id' => (int) $user->getKey(), 'name' => (string) $user->name];
    }
}
