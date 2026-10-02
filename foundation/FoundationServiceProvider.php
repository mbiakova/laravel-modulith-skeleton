<?php

declare(strict_types=1);

namespace Foundation;

use Foundation\Common\Auth\TokenValidator;
use Foundation\Iam\Auth\GatewayTokens;
use Foundation\Iam\Auth\JwtTokens;
use Foundation\Iam\Auth\RpcTokens;
use Foundation\Iam\Contracts\IamService;
use Foundation\Iam\Events\IamEvent;
use Foundation\Iam\Events\UserRegisteredPayload;
use Foundation\Iam\Services\IamRpcService;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Modulith\Providers\FoundationServiceProvider as BaseServiceProvider;

final class FoundationServiceProvider extends BaseServiceProvider
{
    protected array $rpc = [
        IamService::class => IamRpcService::class,
    ];

    protected array $payloads = [
        IamEvent::UserRegistered->value => UserRegisteredPayload::class,
    ];

    public function register(): void
    {
        $this->app->bind(TokenValidator::class, fn (Application $app): TokenValidator => match ($strategy = config('auth.token_validation.strategy')) {
            'jwt' => new JwtTokens((string) config('auth.token_validation.jwt.public_key')),
            'rpc' => $app->make(RpcTokens::class),
            'gateway' => new GatewayTokens((string) config('auth.token_validation.gateway.secret')),
            default => throw new InvalidArgumentException("Unknown token validation strategy [{$strategy}]."),
        });
    }
}
