<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_tracks_an_application_and_updates_the_status_manually(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)->post(route('applications.store'), [
            'opportunity_id' => $opportunity->id,
            'status' => 'NEW',
            'notes' => 'Need to send this myself.',
        ])->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($application);
        $this->assertSame('NEW', $application->status->value);
        $this->assertNull($application->applied_at);

        $this->actingAs($user)->put(route('applications.update', $application), [
            'status' => 'APPLIED',
            'notes' => 'Sent from the company site.',
        ])->assertRedirect(route('applications.edit', $application));

        $application->refresh();
        $this->assertSame('APPLIED', $application->status->value);
        $this->assertNotNull($application->applied_at);
        $this->assertSame(1, Application::query()->count());
    }
}
