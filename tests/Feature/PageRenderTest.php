<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_pages_render(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        $this->actingAs($user)->get(route('opportunities.index'))->assertOk();
        $this->actingAs($user)->get(route('saved.index'))->assertOk();
        $this->actingAs($user)->get(route('proposals.index'))->assertOk();
        $this->actingAs($user)->get(route('proposals.create'))->assertOk();
        $this->actingAs($user)->get(route('applications.index'))->assertOk();
        $this->actingAs($user)->get(route('applications.create'))->assertOk();
        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.sources.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.source-runs.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.errors.index'))->assertOk();
    }

    public function test_guest_auth_pages_render(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('password.request'))->assertOk();
    }
}
