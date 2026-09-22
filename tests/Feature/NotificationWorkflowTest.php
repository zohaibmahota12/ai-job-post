<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_notification_read_and_mark_all(): void
    {
        $user = User::factory()->create();
        $one = UserNotification::factory()->create([
            'user_id' => $user->id,
            'title' => 'First',
            'read_at' => null,
        ]);
        $two = UserNotification::factory()->create([
            'user_id' => $user->id,
            'title' => 'Second',
            'read_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('First')
            ->assertSee('2 unread');

        $this->actingAs($user)
            ->patch(route('notifications.update', $one))
            ->assertRedirect();

        $this->assertNotNull($one->fresh()->read_at);
        $this->assertNull($two->fresh()->read_at);

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertNotNull($two->fresh()->read_at);
    }

    public function test_notifications_are_isolated_per_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = UserNotification::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Secret notice',
        ]);

        $this->actingAs($other)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Secret notice');

        $this->actingAs($other)
            ->patch(route('notifications.update', $notification))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }
}
