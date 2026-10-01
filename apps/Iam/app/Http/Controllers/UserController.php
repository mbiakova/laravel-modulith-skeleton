<?php

declare(strict_types=1);

namespace Apps\Iam\Http\Controllers;

use Apps\Iam\Actions\RegisterUser;
use Apps\Iam\Http\Requests\RegisterUserRequest;
use Illuminate\Http\JsonResponse;
use Shared\Http\Controller;

final class UserController extends Controller
{
    public function store(RegisterUserRequest $request, RegisterUser $register): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $register->execute(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
        );

        return $this->created(['id' => $user->getKey(), 'name' => $user->name, 'token' => $token]);
    }

    public function me(): JsonResponse
    {
        return $this->success(['id' => $this->principalId()]);
    }
}
