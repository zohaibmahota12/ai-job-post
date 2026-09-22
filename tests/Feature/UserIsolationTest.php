<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Proposal;
use App\Models\SavedOpportunity;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_read_or_change_another_users_private_records(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $proposal = Proposal::factory()->create([
            'user_id' => $owner->id,
            'content' => 'Owner only draft text',
        ]);
        $application = Application::factory()->create([
            'user_id' => $owner->id,
            'opportunity_id' => $proposal->opportunity_id,
        ]);
        $saved = SavedOpportunity::factory()->create([
            'user_id' => $owner->id,
            'opportunity_id' => $proposal->opportunity_id,
        ]);
        $notification = UserNotification::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Private alert',
        ]);

        $this->assertTrue($owner->can('view', $proposal));
        $this->assertFalse($other->can('view', $proposal));
        $this->assertFalse($other->can('update', $application));
        $this->assertFalse($other->can('delete', $saved));
        $this->assertFalse($other->can('update', $notification));

        $this->actingAs($other)->get(route('proposals.edit', $proposal))->assertNotFound();
        $this->actingAs($other)->put(route('proposals.update', $proposal), [
            'content' => 'Stolen draft',
            'status' => 'ready',
        ])->assertNotFound();
        $this->actingAs($other)->get(route('applications.edit', $application))->assertNotFound();
        $this->actingAs($other)->put(route('applications.update', $application), [
            'status' => 'HIRED',
            'notes' => 'Changed by someone else',
        ])->assertNotFound();
        $this->actingAs($other)->delete(route('saved.destroy', $saved))->assertNotFound();
        $this->actingAs($other)->patch(route('notifications.update', $notification))->assertNotFound();

        $this->actingAs($other)
            ->get(route('proposals.index'))
            ->assertDontSee('Owner only draft text');

        $this->assertSame('Owner only draft text', $proposal->fresh()->content);
        $this->assertNull($notification->fresh()->read_at);
        $this->assertDatabaseHas('saved_opportunities', ['id' => $saved->id]);
    }
}
