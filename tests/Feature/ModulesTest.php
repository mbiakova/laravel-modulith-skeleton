<?php

namespace Tests\Feature;

use Apps\Analytics\Models\Signup;
use Apps\Iam\Models\User;
use Apps\Iam\Services\IamService as LocalIamService;
use Foundation\Iam\Contracts\IamService;
use Illuminate\Support\Facades\DB;
use Modulith\Testing\Boundaries;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    public function test_registering_a_user_writes_to_the_iam_database_only(): void
    {
        $this->postJson('/iam/api/v1/users', ['name' => 'Ada', 'email' => 'ada@example.com'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ada')
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'token']]);

        $this->assertSame(1, DB::connection('iam')->table('iam_users')->count());
        $this->assertFalse(DB::connection('analytics')->getSchemaBuilder()->hasTable('iam_users'));
    }

    public function test_analytics_records_the_signup_and_keeps_a_copy_of_the_user_once_it_consumes_the_events(): void
    {
        $id = $this->postJson('/iam/api/v1/users', ['name' => 'Ada', 'email' => 'ada@example.com'])->json('data.id');

        $this->assertSame(0, $this->inModule('analytics', fn (): int => Signup::query()->count()));

        $this->artisan('modulith:events:consume --module=analytics')->assertSuccessful();

        $signup = $this->inModule('analytics', fn (): ?Signup => Signup::query()->with('user')->first());

        $this->assertSame($id, $signup?->user_id);
        $this->assertSame('Ada', $signup?->user?->name);
    }

    public function test_a_route_of_another_module_authenticates_with_a_token_issued_by_iam(): void
    {
        $token = $this->postJson('/iam/api/v1/users', ['name' => 'Ada', 'email' => 'ada@example.com'])->json('data.token');
        $this->artisan('modulith:events:consume --module=analytics')->assertSuccessful();

        $this->getJson('/analytics/api/v1/signups')->assertUnauthorized();

        $this->getJson('/analytics/api/v1/signups', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.0.user.name', 'Ada');
    }

    public function test_a_validation_rule_checks_a_value_against_another_module_through_its_contract(): void
    {
        $response = $this->postJson('/iam/api/v1/users', ['name' => 'Ada', 'email' => 'ada@example.com']);
        $headers = ['Authorization' => 'Bearer '.$response->json('data.token')];
        $this->artisan('modulith:events:consume --module=analytics')->assertSuccessful();

        $this->getJson('/analytics/api/v1/signups?user_id='.$response->json('data.id'), $headers)
            ->assertOk()
            ->assertJsonPath('data.0.user.name', 'Ada');

        $this->getJson('/analytics/api/v1/signups?user_id=999', $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id' => 'No user has this id.']);
    }

    public function test_a_module_reads_another_module_through_its_contract(): void
    {
        $id = $this->inModule('iam', fn (): int => User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'api_token' => 'x'])->getKey());

        $this->assertInstanceOf(LocalIamService::class, $this->app->make(IamService::class));
        $this->assertSame(
            ['id' => $id, 'name' => 'Ada'],
            $this->inModule('analytics', fn (): ?array => $this->app->make(IamService::class)->findUser($id)),
        );
    }

    public function test_no_module_uses_another_module_and_every_module_can_run(): void
    {
        $this->assertSame([], $this->app->make(Boundaries::class)->violations());
        $this->artisan('modulith:doctor')->assertSuccessful();
    }
}
