<?php

declare(strict_types=1);

namespace Apps\Analytics\Rules;

use Closure;
use Foundation\Iam\Contracts\IamService;
use Illuminate\Contracts\Validation\ValidationRule;

/** Asks iam through its contract: a plain call when iam runs here, a signed RPC call otherwise. */
final readonly class ExistingUser implements ValidationRule
{
    public function __construct(private IamService $iam) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || $this->iam->findUser((int) $value) === null) {
            $fail('No user has this id.');
        }
    }
}
