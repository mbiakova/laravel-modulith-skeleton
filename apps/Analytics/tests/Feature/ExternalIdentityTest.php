<?php

namespace Apps\Analytics\Tests\Feature;

use Apps\Analytics\Tests\Fixtures\ApiKeys;
use Firebase\JWT\JWT;
use Foundation\Analytics\Enums\AnalyticsPermission;
use Foundation\Common\Auth\ClaimsPermissions;
use Foundation\Common\Auth\ClaimsPrincipals;
use Illuminate\Support\Facades\Http;
use Tests\ModuleTestCase;

/** The identity provider is outside the application: no iam, no copy of the user, the token says it all. */
class ExternalIdentityTest extends ModuleTestCase
{
    protected string $module = 'analytics';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.principal_resolver', ClaimsPrincipals::class);
        config()->set('auth.permission_source', ClaimsPermissions::class);
    }

    public function test_a_token_issued_elsewhere_authenticates_and_carries_the_permissions(): void
    {
        $this->getJson('/analytics/api/v1/datasets', $this->tokenOf('usr_7f3a', [AnalyticsPermission::ReadDatasets->value]))->assertOk();
        $this->getJson('/analytics/api/v1/datasets', $this->tokenOf('usr_7f3a', []))->assertForbidden();
        $this->getJson('/analytics/api/v1/datasets', ['Authorization' => 'Bearer forged'])->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_a_strategy_of_your_own_is_one_line_of_configuration(): void
    {
        config()->set('auth.token_validation.strategies.api_key', ApiKeys::class);
        config()->set('auth.token_validation.api_key.header', 'X-Api-Key');
        config()->set('auth.token_validation.strategy', 'api_key');

        $this->getJson('/analytics/api/v1/datasets', ['X-Api-Key' => 'key-of-the-billing-service'])->assertOk();
        $this->getJson('/analytics/api/v1/datasets', ['X-Api-Key' => 'another-key'])->assertUnauthorized();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{Authorization: string}
     */
    private function tokenOf(string $subject, array $permissions): array
    {
        $token = JWT::encode(['sub' => $subject, 'exp' => time() + 60, 'permissions' => $permissions], (string) config('auth.token_validation.jwt.private_key'), 'RS256');

        return ['Authorization' => "Bearer {$token}"];
    }
}
