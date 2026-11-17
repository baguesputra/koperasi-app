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

    public function test_get_logout_membersihkan_sesi_user(): void
    {
        $user = User::factory()->create(['status' => 'aktif']);
        $this->actingAs($user);

        $res = $this->get('/logout');
        $this->assertGuest();
    }

    public function test_post_logout_membersihkan_sesi_user(): void
    {
        $user = User::factory()->create(['status' => 'aktif']);
        $this->actingAs($user);

        $this->post('/logout');
        $this->assertGuest();
    }

    public function test_sso_redirect_web_dan_mobile_menghasilkan_url_idp(): void
    {
        config()->set('services.perusahaan.metadata', implode('', [
            '<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://gate.appdutamall.com/saml">',
            '<IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">',
            '<SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://gate.appdutamall.com/saml/sso"/>',
            '</IDPSSODescriptor>',
            '</EntityDescriptor>',
        ]));

        $web = $this->get(route('sso.redirect'));
        $web->assertRedirect();
        $this->assertStringStartsWith('https://gate.appdutamall.com/saml/sso', (string) $web->headers->get('Location'));

        $mobile = $this->get(route('sso.mobile.redirect', [
            'state' => str_repeat('s', 48),
            'code_challenge' => str_repeat('c', 43),
        ]));
        $mobile->assertRedirect();
        $this->assertStringStartsWith('https://gate.appdutamall.com/saml/sso', (string) $mobile->headers->get('Location'));
    }
}
