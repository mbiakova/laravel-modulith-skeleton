<?php

declare(strict_types=1);

namespace Apps\Iam\Http\Controllers;

use Apps\Iam\Actions\IssueToken;
use Apps\Iam\Http\Requests\IssueTokenRequest;
use Apps\Iam\Models\User;
use Foundation\Common\Http\Controller;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

final class TokenController extends Controller
{
    /** The same 401 for an unknown email and a wrong password: the answer never tells which emails exist. */
    public function store(IssueTokenRequest $request, IssueToken $tokens): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw new AuthenticationException;
        }

        return $this->created(['token' => $tokens->execute($user)]);
    }
}
