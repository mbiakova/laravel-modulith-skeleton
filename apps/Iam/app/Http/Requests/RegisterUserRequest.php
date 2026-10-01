<?php

declare(strict_types=1);

namespace Apps\Iam\Http\Requests;

use Shared\Http\ApiRequest;

final class RegisterUserRequest extends ApiRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:iam_users,email'],
        ];
    }
}
