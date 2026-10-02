<?php

namespace Tests\Feature;

use Apps\Iam\Actions\GrantRole;
use Apps\Iam\Actions\SetRolePermissions;
use Apps\Iam\Models\User;
use Foundation\Analytics\Enums\AnalyticsPermission;
use Tests\TestCase;

class DatasetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('iam:sync-permissions')->assertSuccessful();
    }

    public function test_a_reading_folds_the_hourly_rows_a_flow_adding_up_and_a_state_keeping_the_last(): void
    {
        $token = $this->signUpAt('ada', '2026-03-02 09:15');
        $this->signUpAt('grace', '2026-03-02 09:40');
        $this->signUpAt('linus', '2026-03-03 14:05');

        $this->artisan('analytics:compute')->expectsOutput('2 hourly buckets computed.')->assertSuccessful();
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/analytics/api/v1/datasets?group=hour', $headers)->assertOk()->assertExactJson(['success' => true, 'data' => [
            ['bucket' => '2026-03-02 09:00', 'signups_count' => 2, 'users_total' => 2],
            ['bucket' => '2026-03-03 14:00', 'signups_count' => 1, 'users_total' => 3],
        ]]);

        $this->getJson('/analytics/api/v1/datasets?group=none&measures[]=signups_count', $headers)
            ->assertOk()->assertExactJson(['success' => true, 'data' => [['bucket' => null, 'signups_count' => 3]]]);

        $this->getJson('/analytics/api/v1/datasets?group=month&from=2026-03-03', $headers)
            ->assertOk()->assertExactJson(['success' => true, 'data' => [['bucket' => '2026-03', 'signups_count' => 1, 'users_total' => 3]]]);
    }

    public function test_an_unknown_group_or_measure_is_refused(): void
    {
        $token = $this->signUpAt('ada', '2026-03-02 09:15');

        $this->getJson('/analytics/api/v1/datasets?group=year&measures[]=revenue', ['Authorization' => "Bearer {$token}"])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['group', 'measures.0']);
    }

    private function signUpAt(string $name, string $at): string
    {
        $this->travelTo($at);
        $token = $this->postJson('/iam/api/v1/users', ['name' => $name, 'email' => "{$name}@example.com", 'password' => 'correct-horse'])->json('data.token');
        $this->artisan('modulith:events:consume --module=analytics')->assertSuccessful();
        $this->travelBack();

        $this->inModuleOf(User::class, function () use ($name): void {
            app(SetRolePermissions::class)->execute('analyst', [AnalyticsPermission::ReadDatasets]);
            app(GrantRole::class)->grant(User::query()->where('email', "{$name}@example.com")->firstOrFail(), 'analyst');
        });

        return $token;
    }
}
