<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login_for_private_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_members_cannot_open_admin_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_users_and_disable_another_account(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($member->email);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $member), ['is_active' => '0'])
            ->assertRedirect();

        $this->assertFalse($member->fresh()->is_active);
    }

    public function test_admin_cannot_disable_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), ['is_active' => '0'])
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_make_admin_command_promotes_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'lead@example.com']);

        $this->artisan('user:make-admin', ['email' => 'lead@example.com'])->assertSuccessful();

        $this->assertTrue($user->fresh()->isAdmin());
    }
}
