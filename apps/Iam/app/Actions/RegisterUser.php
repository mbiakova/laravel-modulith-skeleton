<?php

declare(strict_types=1);

namespace Apps\Iam\Actions;

use Apps\Iam\Events\UserRegistered;
use Apps\Iam\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modulith\Contracts\Stream\Bus;

final readonly class RegisterUser
{
    public function __construct(private Bus $bus) {}

    /** @return array{user: User, token: string} the token is returned once; only its hash is stored */
    public function execute(string $name, string $email): array
    {
        $token = Str::random(40);

        $user = DB::transaction(function () use ($name, $email, $token): User {
            $user = User::query()->create(['name' => $name, 'email' => $email, 'api_token' => hash('sha256', $token)]);

            $this->bus->emit(new UserRegistered((int) $user->getKey()));

            return $user;
        });

        return ['user' => $user, 'token' => $token];
    }
}
