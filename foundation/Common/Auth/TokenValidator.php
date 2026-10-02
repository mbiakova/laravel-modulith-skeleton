<?php

declare(strict_types=1);

namespace Foundation\Common\Auth;

/** Turns the token of a request into the id of the user behind it, or null when it proves nothing. */
interface TokenValidator
{
    public function validate(string $token): ?int;
}
