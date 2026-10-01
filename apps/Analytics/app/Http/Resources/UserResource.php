<?php

declare(strict_types=1);

namespace Apps\Analytics\Http\Resources;

use Apps\Analytics\Models\UserShadow;
use Illuminate\Http\Request;
use Shared\Http\Resource;

/** @mixin UserShadow */
final class UserResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
