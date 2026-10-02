<?php

namespace Tests\Feature;

use Apps\Iam\Actions\GrantRole;
use Apps\Iam\Actions\SetRolePermissions;
use Apps\Iam\Models\User;
use Foundation\Analytics\Enums\AnalyticsPermission;
use Foundation\Iam\Contracts\IamService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    private string $token;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('iam:sync-permissions')->expectsOutputToContain('1 permissions declared, 0 deleted.')->assertSuccessful();
        $this->token = $this->postJson('/iam/api/v1/users', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse'])->json('data.token');
        $this->artisan('modulith:events:consume --module=analytics')->assertSuccessful();
        $this->user = $this->inModuleOf(User::class, fn (): User => User::query()->firstOrFail());
    }

    public function test_a_gesture_needs_its_permission_and_the_grant_takes_effect_at_once(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];

        $this->getJson('/analytics/api/v1/datasets', $headers)->assertForbidden();

        $this->inModuleOf(User::class, function (): void {
            app(SetRolePermissions::class)->execute('analyst', [AnalyticsPermission::ReadDatasets]);
            app(GrantRole::class)->grant($this->user, 'analyst');
        });

        $this->getJson('/analytics/api/v1/datasets', $headers)->assertOk();

        $this->inModuleOf(User::class, fn () => app(GrantRole::class)->revoke($this->user, 'analyst'));

        $this->getJson('/analytics/api/v1/datasets', $headers)->assertForbidden();
    }

    public function test_changing_a_role_drops_the_cached_grants_of_every_user_holding_it(): void
    {
        $this->inModuleOf(User::class, function (): void {
            app(SetRolePermissions::class)->execute('analyst', [AnalyticsPermission::ReadDatasets]);
            app(GrantRole::class)->grant($this->user, 'analyst');
        });

        $this->assertSame(['analytics.datasets.read'], app(IamService::class)->grants($this->user->id));

        $this->inModuleOf(User::class, fn () => app(SetRolePermissions::class)->execute('analyst', []));

        $this->assertSame([], app(IamService::class)->grants($this->user->id));
    }

    public function test_only_prune_deletes_a_permission_no_module_declares(): void
    {
        $this->inModuleOf(User::class, fn () => DB::table('iam_permissions')->insert(['name' => 'gone.permission', 'guard_name' => 'web']));

        $this->artisan('iam:sync-permissions')->expectsOutputToContain('1 permissions declared, 0 deleted.')->assertSuccessful();
        $this->artisan('iam:sync-permissions --prune')->expectsOutputToContain('1 permissions declared, 1 deleted.')->assertSuccessful();
    }
}
