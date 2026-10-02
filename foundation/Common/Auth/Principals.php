<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

use Modulith\Services\Modules\ModuleContext;

/** Loads a user from the database of the running module, through the model auth.principals names for it. */
final readonly class Principals
{
    public function __construct(private ModuleContext $context) {}

    public function find(int $id): ?Principal
    {
        $model = config('auth.principals.'.$this->context->current()?->name);

        if (! is_string($model)) {
            return null;
        }

        $principal = $model::query()->find($id);

        return $principal instanceof Principal ? $principal : null;
    }
}
