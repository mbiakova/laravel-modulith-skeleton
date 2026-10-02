<?php

declare(strict_types=1);

namespace Foundation\Common\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A field holding the id of a record another module owns, resolved through that module's contract.
 * Existence is the base check; a subclass adds constraints by pushing closures into $checks.
 *
 * @template T
 */
abstract class ReferenceRule implements ValidationRule
{
    /** @var list<Closure(T): ?string> each returns the error message, or null when the record passes */
    protected array $checks = [];

    final public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $record = is_numeric($value) ? $this->resolve((int) $value) : null;

        if ($record === null) {
            $fail($this->missing());

            return;
        }

        foreach ($this->checks as $check) {
            if (($error = $check($record)) !== null) {
                $fail($error);

                return;
            }
        }
    }

    /** @return T|null */
    abstract protected function resolve(int $id): mixed;

    protected function missing(): string
    {
        return 'The selected :attribute does not exist.';
    }
}
