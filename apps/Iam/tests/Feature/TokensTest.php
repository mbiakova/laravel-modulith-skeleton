<?php

namespace Apps\Iam\Tests\Feature;

use Apps\Iam\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Foundation\Iam\Auth\GatewayTokens;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->postJson('/iam/api/v1/users', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse'])->assertCreated();
    }

    public function test_the_right_password_issues_a_token_that_authenticates(): void
    {
        $token = $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'correct-horse'])
            ->assertCreated()->json('data.token');

        $this->getJson('/iam/api/v1/me', ['Authorization' => "Bearer {$token}"])->assertOk();
    }

    public function test_a_wrong_password_and_an_unknown_email_get_the_same_401(): void
    {
        $wrongPassword = $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'wrong-horse']);
        $unknownEmail = $this->postJson('/iam/api/v1/tokens', ['email' => 'nobody@example.com', 'password' => 'correct-horse']);

        $wrongPassword->assertUnauthorized();
        $unknownEmail->assertUnauthorized();
        $this->assertSame($wrongPassword->json(), $unknownEmail->json());
    }

    public function test_the_password_is_stored_hashed_and_never_returned(): void
    {
        $stored = (string) DB::connection('iam')->table('iam_users')->value('password');

        $this->assertNotSame('correct-horse', $stored);
        $this->assertTrue(password_verify('correct-horse', $stored));
        $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'correct-horse'])->assertJsonMissingPath('data.password');
    }

    public function test_a_seventh_attempt_within_a_minute_is_throttled(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'wrong-horse'])->assertUnauthorized();
        }

        $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'wrong-horse'])->assertTooManyRequests();
    }

    public function test_iam_issues_a_jwt_the_modules_verify_with_its_public_key(): void
    {
        $token = $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'correct-horse'])->json('data.token');
        [$header, $payload] = explode('.', $token);

        $this->assertSame('RS256', json_decode(base64_decode($header), true)['alg']);
        $this->getJson('/iam/api/v1/me', ['Authorization' => "Bearer {$header}.{$payload}.forged"])->assertUnauthorized();
    }

    public function test_the_gateway_strategy_trusts_only_an_identity_signed_with_the_shared_secret(): void
    {
        config()->set('auth.token_validation.strategy', 'gateway');
        $id = $this->inModuleOf(User::class, fn (): int => (int) User::query()->value('id'));

        $this->getJson('/iam/api/v1/me', ['X-Identity' => GatewayTokens::sign($id, time() + 60, 'testing-gateway-secret')])->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/iam/api/v1/me', ['X-Identity' => GatewayTokens::sign($id, time() + 60, 'wrong')])->assertUnauthorized();
        $this->getJson('/iam/api/v1/me', ['X-Identity' => GatewayTokens::sign($id, time() - 1, 'testing-gateway-secret')])->assertUnauthorized();
    }

    public function test_under_the_gateway_strategy_iam_issues_a_jwt_the_gateway_verifies_with_its_public_key(): void
    {
        config()->set('auth.token_validation.strategy', 'gateway');
        $token = $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'correct-horse'])->json('data.token');

        $claims = JWT::decode($token, new Key((string) config('auth.token_validation.jwt.public_key'), 'RS256'));

        $this->assertSame((string) $this->inModuleOf(User::class, fn (): int => (int) User::query()->value('id')), $claims->sub);
    }

    public function test_identity_turns_a_valid_jwt_into_the_x_identity_the_gateway_strategy_trusts(): void
    {
        $token = $this->postJson('/iam/api/v1/tokens', ['email' => 'ada@example.com', 'password' => 'correct-horse'])->json('data.token');

        $identity = (string) $this->get('/iam/api/v1/identity', ['Authorization' => "Bearer {$token}"])->assertNoContent()->headers->get('X-Identity');
        config()->set('auth.token_validation.strategy', 'gateway');

        $this->getJson('/iam/api/v1/me', ['X-Identity' => $identity])->assertOk()->assertJsonPath('data.id', 1);
        $this->get('/iam/api/v1/identity', ['Authorization' => 'Bearer forged'])->assertUnauthorized();
        $this->get('/iam/api/v1/identity')->assertUnauthorized();
    }

    public function test_a_password_shorter_than_eight_characters_is_refused_at_registration(): void
    {
        $this->postJson('/iam/api/v1/users', ['name' => 'Grace', 'email' => 'grace@example.com', 'password' => 'short'])
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }
}
