<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ApiDocsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_api_documentation(): void
    {
        $this->get('/api/documentation')->assertStatus(401);
    }

    public function test_authenticated_user_can_view_inertia_api_documentation(): void
    {
        config()->set('auth.mode', 'local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/documentation');

        $response
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ApiDocs/Index')
                ->where('authMode', 'local'));
    }

    public function test_api_documentation_passes_sso_auth_mode_prop(): void
    {
        config()->set('auth.mode', 'sso');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/api/documentation')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ApiDocs/Index')
                ->where('authMode', 'sso'));
    }
}
