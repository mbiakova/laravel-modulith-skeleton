<?php

namespace Apps\Iam\Tests\Feature;

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

    public function test_a_password_shorter_than_eight_characters_is_refused_at_registration(): void
    {
        $this->postJson('/iam/api/v1/users', ['name' => 'Grace', 'email' => 'grace@example.com', 'password' => 'short'])
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }
}
