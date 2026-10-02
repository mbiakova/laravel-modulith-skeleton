<?php

namespace Apps\Analytics\Tests\Feature;

use Foundation\Analytics\Enums\AnalyticsPermission;
use Foundation\Iam\Events\IamEvent;
use Tests\ModuleTestCase;

class DatasetsTest extends ModuleTestCase
{
    protected string $module = 'analytics';

    public function test_a_reading_folds_the_hourly_rows_a_flow_adding_up_and_a_state_keeping_the_last(): void
    {
        $headers = $this->signedUpAt(1, 'ada', '2026-03-02 09:15');
        $this->signedUpAt(2, 'grace', '2026-03-02 09:40');
        $this->signedUpAt(3, 'linus', '2026-03-03 14:05');

        $this->artisan('analytics:compute')->expectsOutput('2 hourly buckets computed.')->assertSuccessful();

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
        $this->getJson('/analytics/api/v1/datasets?group=year&measures[]=revenue', $this->signedUpAt(1, 'ada', '2026-03-02 09:15'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['group', 'measures.0']);
    }

    public function test_reading_the_datasets_needs_the_permission_iam_grants(): void
    {
        $this->getJson('/analytics/api/v1/datasets', $this->user(1, 'ada'))->assertForbidden();
        $this->getJson('/analytics/api/v1/datasets', $this->user(2, 'grace', [AnalyticsPermission::ReadDatasets->value]))->assertOk();
    }

    /** @return array{Authorization: string} */
    private function signedUpAt(int $id, string $name, string $at): array
    {
        $this->travelTo($at);
        $this->receive('analytics', IamEvent::UserRegistered->value, ['id' => $id]);
        $this->travelBack();

        return $this->user($id, $name, [AnalyticsPermission::ReadDatasets->value]);
    }
}
