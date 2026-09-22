<?php

namespace Tests\Feature;

use App\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_applied_is_manual_and_records_history(): void
    {
        $user = User::factory()->create();
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'status' => ApplicationStatus::ProposalDraft,
        ]);

        $this->actingAs($user)
            ->post(route('applications.mark-applied', $application))
            ->assertRedirect(route('applications.edit', $application));

        $application->refresh();
        $this->assertSame(ApplicationStatus::Applied, $application->status);
        $this->assertNotNull($application->applied_at);

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'old_status' => 'PROPOSAL_DRAFT',
            'new_status' => 'APPLIED',
            'changed_by' => $user->id,
        ]);

        $this->assertTrue(
            UserNotification::query()
                ->where('user_id', $user->id)
                ->where('type', 'application.applied')
                ->exists()
        );
    }

    public function test_status_updates_create_history_and_notifications(): void
    {
        $user = User::factory()->create();
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'status' => ApplicationStatus::Applied,
            'applied_at' => now(),
        ]);

        $this->actingAs($user)->put(route('applications.update', $application), [
            'status' => 'INTERVIEW',
            'notes' => 'Screening call booked',
            'contact_name' => 'Jamie',
        ])->assertRedirect(route('applications.edit', $application));

        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);
        $this->assertSame('Jamie', $application->fresh()->contact_name);
        $this->assertSame(1, ApplicationStatusHistory::query()->where('application_id', $application->id)->count());
        $this->assertTrue(
            UserNotification::query()
                ->where('user_id', $user->id)
                ->where('type', 'application.interview')
                ->exists()
        );
    }

    public function test_user_cannot_update_another_users_application(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $application = Application::factory()->create([
            'user_id' => $owner->id,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($other)->post(route('applications.mark-applied', $application))->assertNotFound();
        $this->actingAs($other)->put(route('applications.update', $application), [
            'status' => 'HIRED',
            'notes' => 'nope',
        ])->assertNotFound();

        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
        $this->assertSame(0, ApplicationStatusHistory::query()->count());
    }

    public function test_application_details_accept_optional_fields(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)->post(route('applications.store'), [
            'opportunity_id' => $opportunity->id,
            'status' => 'SAVED',
            'notes' => 'Look later',
            'contact_name' => 'Alex',
            'external_url' => 'https://example.com/apply/123',
            'follow_up_at' => '2026-10-01',
        ])->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(ApplicationStatus::Saved, $application->status);
        $this->assertSame('Alex', $application->contact_name);
        $this->assertSame('https://example.com/apply/123', $application->safeExternalUrl());
    }
}
