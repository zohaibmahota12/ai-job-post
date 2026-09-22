<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'amina@example.com',
        ]);

        $this->post(route('login'), [
            'email' => 'amina@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_unverified_user_cannot_open_the_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create([
            'email' => 'blocked@example.com',
        ]);

        $this->post(route('login'), [
            'email' => 'blocked@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_request_and_complete_a_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'amina@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => 'amina@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'New-Hunter-12',
                'password_confirmation' => 'New-Hunter-12',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(password_verify('New-Hunter-12', $user->fresh()->password));
    }

    public function test_unknown_email_does_not_reveal_whether_an_account_exists(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [
            'email' => 'missing@example.com',
        ])->assertSessionHas('status');

        Notification::assertNothingSent();
    }
}
