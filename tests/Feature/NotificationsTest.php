<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotificationsTest extends TestCase
{
    public function test_a_registered_user_finds_a_welcome_in_the_inbox_and_marks_it_read(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->register('ada')];

        $this->getJson('/notifications/api/v1/notifications', $headers)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'user.welcome')
            ->assertJsonPath('data.0.title', 'Welcome, ada!')
            ->assertJsonPath('data.0.read_at', null);

        $id = $this->getJson('/notifications/api/v1/notifications?filter[unread]=1', $headers)->json('data.0.id');
        $this->patchJson("/notifications/api/v1/notifications/{$id}/read", [], $headers)->assertOk();

        $this->getJson('/notifications/api/v1/notifications?filter[unread]=1', $headers)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/notifications/api/v1/notifications?filter[unread]=0', $headers)->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_the_inbox_pages_and_refuses_a_filter_it_does_not_allow(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->register('ada')];

        $this->getJson('/notifications/api/v1/notifications?paginate=1', $headers)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/notifications/api/v1/notifications?filter[recipient_user_id]=2', $headers)->assertBadRequest();
    }

    public function test_nobody_reads_or_marks_the_notification_of_someone_else(): void
    {
        $ada = ['Authorization' => 'Bearer '.$this->register('ada')];
        $grace = ['Authorization' => 'Bearer '.$this->register('grace')];
        $adas = $this->getJson('/notifications/api/v1/notifications', $ada)->json('data.0.id');

        $this->getJson('/notifications/api/v1/notifications', $grace)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Welcome, grace!');
        $this->patchJson("/notifications/api/v1/notifications/{$adas}/read", [], $grace)->assertNotFound();
    }

    public function test_a_welcome_is_sent_once_however_many_times_the_event_arrives(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->register('ada')];
        $this->artisan('modulith:events:consume --module=notifications')->assertSuccessful();

        $this->getJson('/notifications/api/v1/notifications', $headers)->assertOk()->assertJsonCount(1, 'data');
    }

    private function register(string $name): string
    {
        $token = $this->postJson('/iam/api/v1/users', ['name' => $name, 'email' => "{$name}@example.com", 'password' => 'correct-horse'])->json('data.token');
        $this->artisan('modulith:events:consume --module=notifications')->assertSuccessful();

        return $token;
    }
}
