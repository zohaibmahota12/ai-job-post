<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_receives_a_verification_email(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => 'Amina Cole',
            'email' => 'amina@example.com',
            'password' => 'Hunter-Pass-12',
            'password_confirmation' => 'Hunter-Pass-12',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'amina@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->profile);
        $this->assertTrue(password_verify('Hunter-Pass-12', $user->password));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'Amina Cole',
            'email' => 'amina@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_can_verify_email_with_a_signed_link(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
