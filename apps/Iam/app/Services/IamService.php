<?php

declare(strict_types=1);

namespace Apps\Iam\Services;

use Apps\Iam\Models\User;
use Foundation\Iam\Contracts\IamService as Contract;
use Modulith\Services\Modules\ModuleContext;
use Modulith\Services\Modules\ModuleRegistry;

final readonly class IamService implements Contract
{
    public function __construct(
        private ModuleContext $context,
        private ModuleRegistry $registry,
    ) {}

    public function findUser(int $id): ?array
    {
        return $this->present($this->inIam(fn (): ?User => User::query()->find($id)));
    }

    public function findUserByToken(string $token): ?array
    {
        return $this->present($this->inIam(fn (): ?User => User::query()->where('api_token', hash('sha256', $token))->first()));
    }

    /** @return array{id: int, name: string}|null */
    private function present(?User $user): ?array
    {
        return $user === null ? null : ['id' => (int) $user->getKey(), 'name' => (string) $user->name];
    }

    /**
     * Another module may call this in its own request: the query still has to run on iam's database.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    private function inIam(\Closure $callback): mixed
    {
        return $this->context->within($this->registry->get('iam'), $callback);
    }
}
