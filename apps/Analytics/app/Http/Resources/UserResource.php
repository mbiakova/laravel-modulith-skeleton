<?php

declare(strict_types=1);

namespace Apps\Analytics\Http\Resources;

use Apps\Analytics\Models\UserShadow;
use Foundation\Common\Http\Resource;
use Illuminate\Http\Request;

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
