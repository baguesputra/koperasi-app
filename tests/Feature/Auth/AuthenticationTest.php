<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['no_karyawan' => 'TST-000001']);

        $response = $this->post('/login', [
            'no_karyawan' => $user->no_karyawan,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['no_karyawan' => 'TST-000002']);

        $this->post('/login', [
            'no_karyawan' => $user->no_karyawan,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_logout_sso_dialihkan_ke_idp(): void
    {
        config(['auth.mode' => 'sso']);
        config()->set('services.perusahaan.metadata', 'https://gate.appdutamall.com/saml/metadata');

        $user = User::factory()->create(['sso_id' => 'admin@koperasi.test']);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect();
        $this->assertStringStartsWith('https://gate.appdutamall.com/saml/slo', $response->headers->get('Location'));
    }
}
