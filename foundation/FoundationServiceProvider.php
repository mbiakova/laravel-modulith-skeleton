<?php

declare(strict_types=1);

namespace Foundation;

use Foundation\Common\Auth\PermissionSource;
use Foundation\Common\Auth\PrincipalResolver;
use Foundation\Common\Auth\TokenValidator;
use Foundation\Iam\Auth\GatewayTokens;
use Foundation\Iam\Auth\JwtTokens;
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

    /** Each of the three is a class named in config/auth.php, so another one is a line of configuration. */
    public function register(): void
    {
        $this->app->when(JwtTokens::class)->needs('$publicKey')->give(fn (): string => (string) config('auth.token_validation.jwt.public_key'));
        $this->app->when(GatewayTokens::class)->needs('$secret')->give(fn (): string => (string) config('auth.token_validation.gateway.secret'));

        $this->app->bind(TokenValidator::class, function (Application $app): TokenValidator {
            $strategy = (string) config('auth.token_validation.strategy');
            $class = config("auth.token_validation.strategies.{$strategy}");

            return is_string($class) ? $app->make($class) : throw new InvalidArgumentException("Unknown token validation strategy [{$strategy}].");
        });

        $this->app->bind(PrincipalResolver::class, fn (Application $app): PrincipalResolver => $app->make((string) config('auth.principal_resolver')));
        $this->app->bind(PermissionSource::class, fn (Application $app): PermissionSource => $app->make((string) config('auth.permission_source')));
    }
}
