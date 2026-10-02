<?php

declare(strict_types=1);

namespace Apps\Iam\Actions;

use Apps\Iam\Events\UserRegistered;
use Apps\Iam\Models\User;
use Foundation\Iam\Events\UserRegisteredPayload;
use Illuminate\Support\Facades\DB;
use Modulith\Contracts\Stream\Bus;

final readonly class RegisterUser
{
    public function __construct(private Bus $bus, private IssueToken $tokens) {}

    /** @return array{user: User, token: string} */
    public function execute(string $name, string $email, string $password): array
    {
        return DB::transaction(function () use ($name, $email, $password): array {
            $user = User::query()->create(['name' => $name, 'email' => $email, 'password' => $password]);
            $token = $this->tokens->execute($user);

            $this->bus->emit(new UserRegistered(new UserRegisteredPayload(id: $user->id)));

            return ['user' => $user, 'token' => $token];
        });
    }
}
