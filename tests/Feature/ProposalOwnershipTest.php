<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_and_update_only_their_own_proposal(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)->post(route('proposals.store'), [
            'opportunity_id' => $opportunity->id,
            'content' => 'I can start next week.',
            'status' => 'draft',
        ])->assertRedirect();

        $proposal = Proposal::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($proposal);
        $this->assertSame($opportunity->id, $proposal->opportunity_id);
        $this->assertNull($proposal->ai_provider);
        $this->assertNull($proposal->ai_model);

        $this->actingAs($user)->put(route('proposals.update', $proposal), [
            'content' => 'Updated draft.',
            'status' => 'ready',
        ])->assertRedirect(route('proposals.edit', $proposal));

        $this->assertSame('Updated draft.', $proposal->fresh()->content);
        $this->assertSame('ready', $proposal->fresh()->status->value);
    }
}
