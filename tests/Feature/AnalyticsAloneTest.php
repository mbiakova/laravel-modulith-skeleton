<?php

namespace Tests\Feature;

use Foundation\Iam\Contracts\IamService;
use Foundation\Iam\Services\IamRpcService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modulith\Services\Rpc\RpcSignature;
use Tests\TestCase;

/** analytics runs in this process, iam in another one: every read of iam is a signed HTTP call. */
class AnalyticsAloneTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setEnvironment(['MODULITH_RUNS' => 'analytics', 'MODULITH_IAM_HOST' => 'http://iam.test']);

        parent::setUp();

        Http::fake([
            'iam.test/iam/rpc/v1/users/find-by-token' => fn (Request $request) => $request['token'] === 'ada-token'
                ? Http::response(['id' => 1, 'name' => 'Ada'])
                : Http::response(null, 404),
            'iam.test/iam/rpc/v1/users/find' => fn (Request $request) => $request['id'] === 1
                ? Http::response(['id' => 1, 'name' => 'Ada'])
                : Http::response(null, 404),
        ]);
    }

    protected function tearDown(): void
    {
        $this->setEnvironment(['MODULITH_RUNS' => null, 'MODULITH_IAM_HOST' => null]);

        parent::tearDown();
    }

    public function test_the_contract_is_answered_by_the_rpc_service(): void
    {
        $this->assertInstanceOf(IamRpcService::class, $this->app->make(IamService::class));
        $this->getJson('/iam/api/v1/me')->assertNotFound();
    }

    public function test_a_route_of_analytics_authenticates_through_a_signed_call_to_iam(): void
    {
        $this->getJson('/analytics/api/v1/signups', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();

        $this->getJson('/analytics/api/v1/signups', ['Authorization' => 'Bearer ada-token'])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://iam.test/iam/rpc/v1/users/find-by-token'
            && $request->hasHeader(RpcSignature::SIGNATURE_HEADER));
    }

    public function test_the_validation_rule_asks_iam_over_http(): void
    {
        $headers = ['Authorization' => 'Bearer ada-token'];

        $this->getJson('/analytics/api/v1/signups?user_id=1', $headers)->assertOk();

        $this->getJson('/analytics/api/v1/signups?user_id=999', $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id' => 'No user has this id.']);
    }

    /** @param array<string, string|null> $variables */
    private function setEnvironment(array $variables): void
    {
        foreach ($variables as $name => $value) {
            if ($value === null) {
                putenv($name);
                unset($_ENV[$name], $_SERVER[$name]);
            } else {
                putenv("{$name}={$value}");
                $_ENV[$name] = $_SERVER[$name] = $value;
            }
        }
    }
}
