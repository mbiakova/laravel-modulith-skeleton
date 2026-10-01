<?php

declare(strict_types=1);

namespace Apps\Analytics\Http\Resources;

use Apps\Analytics\Models\Signup;
use Illuminate\Http\Request;
use Shared\Http\Resource;

/**
 * The user comes from analytics' own copy of iam's users: no call to iam per row.
 *
 * @mixin Signup
 */
final class SignupResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'user' => $this->userResource(),
        ];
    }

    private function userResource(): ?UserResource
    {
        return $this->user === null ? null : UserResource::make($this->user);
    }
}
