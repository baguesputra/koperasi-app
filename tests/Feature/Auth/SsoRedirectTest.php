<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.mode' => 'sso']);
    }

    public function test_halaman_login_lokal_dialihkan_ke_sso(): void
    {
        $this->get('/login')->assertRedirect(route('sso.redirect'));
    }

    public function test_post_login_lokal_ditolak_saat_mode_sso(): void
    {
        $this->post('/login', [
            'no_karyawan' => 'ADM-000001',
            'password' => 'ADM-000001',
        ])->assertRedirect(route('sso.redirect'));

        $this->assertGuest();
    }

    public function test_root_dialihkan_ke_sso_sa_t_belum_login(): void
    {
        $this->get('/')->assertRedirect(route('sso.redirect'));
    }

    public function test_slo_membersihkan_sesi_tanpa_saml(): void
    {
        $user = User::factory()->create(['status' => 'aktif']);
        $this->actingAs($user);

        $this->get(route('sso.slo'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
