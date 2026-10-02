<?php

declare(strict_types=1);

namespace Foundation\Iam\Services;

use Foundation\Iam\Contracts\IamService;
use Modulith\Services\Rpc\RpcService;

final class IamRpcService extends RpcService implements IamService
{
    public function findUser(int $id): ?array
    {
        return $this->readThrough(
            self::userKey($id),
            self::DEFAULT_TTL,
            fn (): mixed => $this->call('findUser', ['id' => $id]),
            fn (array $raw): array => ['id' => (int) $raw['id'], 'name' => (string) $raw['name']],
        );
    }

    /** Not cached: a token is checked on every request, and a revoked one must stop working at once. */
    public function findUserByToken(string $token): ?array
    {
        $raw = $this->call('findUserByToken', ['token' => $token]);

        return is_array($raw) ? ['id' => (int) $raw['id'], 'name' => (string) $raw['name']] : null;
    }

    /** iam calls it when a user changes, so the other processes read the new one. */
    public function forgetUser(int $id): void
    {
        $this->forget(self::userKey($id));
    }

    private static function userKey(int $id): string
    {
        return "iam:user:{$id}";
    }
}
