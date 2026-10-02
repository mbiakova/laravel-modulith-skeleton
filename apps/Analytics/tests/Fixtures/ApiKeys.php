<?php

namespace Apps\Analytics\Tests\Fixtures;

use Foundation\Common\Auth\Identity;
use Foundation\Common\Auth\TokenValidator;

final class ApiKeys implements TokenValidator
{
    public function validate(string $token): ?Identity
    {
        return $token === 'key-of-the-billing-service' ? new Identity('billing-service', ['permissions' => ['analytics.datasets.read']]) : null;
    }
}
