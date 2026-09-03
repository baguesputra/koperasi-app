<?php

namespace Tests\Feature\Auth;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class SsoCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.mode' => 'sso']);
    }

    protected function mockSsoUser(array $attributes = []): void
    {
        $default = [
            'id' => 'sso-123',
            'email' => 'test@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ];

        $user = new class(array_merge($default, $attributes))
        {
            public function __construct(public array $attributes) {}

            public function __get(string $name): mixed
            {
                return $this->attributes[$name] ?? null;
            }
        };

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($user);
    }

    public function test_sso_callback_success_with_existing_user_and_anggota(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        Anggota::factory()->create([
            'user_id' => $user->id,
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => 'test@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $response = $this->get(route('sso.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertEquals('sso-123', $user->sso_id);
        $this->assertEquals('sso', $user->auth_provider);
    }

    public function test_sso_callback_fails_when_email_missing_from_sso(): void
    {
        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => null,
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $response = $this->get(route('sso.callback'));

        $response->assertRedirect(route('sso.gagal'));
        $response->assertSessionHas('error', 'Email tidak tersedia dari SSO. Hubungi admin koperasi untuk mendaftarkan email Anda.');
        $this->assertGuest();
    }

    public function test_sso_callback_fails_when_user_not_found(): void
    {
        $this->mockSsoUser([
            'id' => 'sso-new',
            'email' => 'notfound@example.com',
            'nik' => 'TOP-999999',
            'name' => 'Not Found',
        ]);

        $response = $this->get(route('sso.callback'));

        $response->assertRedirect(route('sso.gagal'));
        $response->assertSessionHas('error', 'Akun SSO tidak terdaftar di sistem koperasi. Hubungi admin koperasi untuk mendaftarkan Anda sebagai anggota.');
        $this->assertGuest();
    }

    public function test_sso_callback_fails_when_user_has_no_anggota_relation(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => 'test@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $response = $this->get(route('sso.callback'));

        $response->assertRedirect(route('sso.gagal'));
        $response->assertSessionHas('error', 'Anda belum terdaftar sebagai anggota koperasi. Hubungi admin koperasi untuk mendaftarkan keanggotaan Anda.');
        $this->assertGuest();
    }

    public function test_sso_callback_fails_when_email_mismatch(): void
    {
        $user = User::factory()->create([
            'email' => 'local@example.com',
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        Anggota::factory()->create([
            'user_id' => $user->id,
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => 'sso@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $response = $this->get(route('sso.callback'));

        $response->assertRedirect(route('sso.gagal'));
        $response->assertSessionHas('error', 'Email SSO tidak cocok dengan data lokal. Hubungi admin koperasi untuk memperbarui data Anda.');
        $this->assertGuest();
    }

    public function test_sso_callback_fails_when_user_inactive(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'no_karyawan' => 'TOP-100001',
            'status' => 'nonaktif',
        ]);

        Anggota::factory()->create([
            'user_id' => $user->id,
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => 'test@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $response = $this->get(route('sso.callback'));

        $response->assertRedirect(route('sso.gagal'));
        $response->assertSessionHas('error', 'Akun Anda dinonaktifkan. Silakan hubungi pengurus koperasi untuk mengaktifkan kembali.');
        $this->assertGuest();
    }

    public function test_sso_callback_fails_when_email_mismatch_even_with_sso_id(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'no_karyawan' => 'TOP-100001',
            'sso_id' => 'sso-123',
            'status' => 'aktif',
        ]);

        Anggota::factory()->create([
            'user_id' => $user->id,
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => 'new@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $response = $this->get(route('sso.callback'));

        $response->assertRedirect(route('sso.gagal'));
        $response->assertSessionHas('error', 'Email SSO tidak cocok dengan data lokal. Hubungi admin koperasi untuk memperbarui data Anda.');
        $this->assertGuest();
    }

    public function test_sso_callback_audit_logs_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        Anggota::factory()->create([
            'user_id' => $user->id,
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => 'test@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $this->get(route('sso.callback'));

        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'sso_login',
            'user_id' => $user->id,
        ]);
    }

    public function test_sso_callback_audit_logs_failure_user_not_found(): void
    {
        $this->mockSsoUser([
            'id' => 'sso-new',
            'email' => 'notfound@example.com',
            'nik' => 'TOP-999999',
            'name' => 'Not Found',
        ]);

        $this->get(route('sso.callback'));

        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'sso_login_failed',
            'keterangan' => 'user_not_found',
            'user_id' => null,
        ]);
    }

    public function test_sso_callback_audit_logs_failure_email_mismatch(): void
    {
        $user = User::factory()->create([
            'email' => 'local@example.com',
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        Anggota::factory()->create([
            'user_id' => $user->id,
            'no_karyawan' => 'TOP-100001',
            'status' => 'aktif',
        ]);

        $this->mockSsoUser([
            'id' => 'sso-123',
            'email' => 'sso@example.com',
            'nik' => 'TOP-100001',
            'name' => 'Test User',
        ]);

        $this->get(route('sso.callback'));

        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'sso_login_failed',
            'keterangan' => 'email_mismatch',
            'user_id' => $user->id,
        ]);
    }
}
