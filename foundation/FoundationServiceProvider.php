<?php

declare(strict_types=1);

namespace Foundation;

use Foundation\Iam\Auth\Tokens;
use Foundation\Iam\Contracts\IamService;
use Foundation\Iam\Services\IamRpcService;
use Modulith\Providers\FoundationServiceProvider as BaseServiceProvider;
use Shared\Auth\TokenValidator;

final class FoundationServiceProvider extends BaseServiceProvider
{
    protected array $rpc = [
        IamService::class => IamRpcService::class,
    ];

    public function register(): void
    {
        $this->app->bind(TokenValidator::class, Tokens::class);
    }
}
