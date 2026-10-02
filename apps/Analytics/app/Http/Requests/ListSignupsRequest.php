<?php

declare(strict_types=1);

namespace Apps\Analytics\Http\Requests;

use Apps\Analytics\Rules\ExistingUser;
use Foundation\Common\Http\ApiRequest;

final class ListSignupsRequest extends ApiRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', app(ExistingUser::class)],
        ];
    }
}
