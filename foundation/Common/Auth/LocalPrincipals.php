<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

use Modulith\Services\Modules\ModuleContext;

/** Loads the user from the database of the running module, through the model auth.principals names for it. */
final readonly class LocalPrincipals implements PrincipalResolver
{
    public function __construct(private ModuleContext $context) {}

    public function resolve(Identity $identity): ?Principal
    {
        $model = config('auth.principals.'.$this->context->current()?->name);

        if (! is_string($model)) {
            return null;
        }

        $principal = $model::query()->find($identity->id);

        return $principal instanceof Principal ? $principal : null;
    }
}
