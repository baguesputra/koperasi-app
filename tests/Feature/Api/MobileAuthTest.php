<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function headerToken(string $token): static
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_login_sukses_dengan_no_karyawan(): void
    {
        $anggota = $this->buatAnggota('TOP-960001');

        $res = $this->postJson('/api/v1/login', [
            'no_karyawan' => strtolower($anggota->user->no_karyawan),
            'password' => 'TOP-960001',
            'device_name' => 'Pixel Uji',
        ])->assertOk();

        $res->assertJsonStructure([
            'user' => ['id', 'name', 'no_karyawan', 'email', 'roles', 'permissions', 'is_anggota'],
            'token',
            'harus_ganti_password',
        ]);
        $res->assertJsonPath('user.no_karyawan', 'TOP-960001');
        $res->assertJsonPath('user.is_anggota', true);
        $res->assertJsonPath('harus_ganti_password', false);
        $this->assertNotEmpty($res->json('token'));
        $this->assertContains('anggota', $res->json('user.roles'));
        $this->assertIsArray($res->json('user.permissions'));

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $anggota->user->id,
            'name' => 'Pixel Uji',
        ]);
    }

    public function test_login_tanpa_device_name_memakai_nama_mobile(): void
    {
        $anggota = $this->buatAnggota('TOP-960002');

        $this->postJson('/api/v1/login', [
            'no_karyawan' => 'TOP-960002',
            'password' => 'TOP-960002',
        ])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $anggota->user->id,
            'name' => 'mobile',
        ]);
    }

    public function test_login_gagal_mengembalikan_422(): void
    {
        $this->buatAnggota('TOP-960003');

        $this->postJson('/api/v1/login', [
            'no_karyawan' => 'TOP-960003',
            'password' => 'salah',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Nomor karyawan atau kata sandi tidak sesuai.')
            ->assertJsonPath('errors.no_karyawan.0', 'Nomor karyawan atau kata sandi tidak sesuai.');

        $this->postJson('/api/v1/login', [
            'no_karyawan' => 'TIDAK-ADA',
            'password' => 'apapun',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.no_karyawan.0', 'Nomor karyawan atau kata sandi tidak sesuai.');

        $this->postJson('/api/v1/login', [])->assertUnprocessable();
    }

    public function test_login_diblokir_saat_mode_sso(): void
    {
        config(['auth.mode' => 'sso']);
        $this->buatAnggota('TOP-960004');

        $this->postJson('/api/v1/login', [
            'no_karyawan' => 'TOP-960004',
            'password' => 'TOP-960004',
        ])->assertForbidden();
    }

    public function test_me_butuh_auth_dan_mengembalikan_user(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();

        $anggota = $this->buatAnggota('TOP-960005');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'no_karyawan', 'email', 'roles', 'permissions', 'is_anggota']])
            ->assertJsonPath('user.no_karyawan', 'TOP-960005')
            ->assertJsonPath('user.is_anggota', true);
    }

    public function test_me_user_bukan_anggota(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.is_anggota', false)
            ->assertJsonPath('user.roles', []);
    }

    public function test_logout_mencabut_token(): void
    {
        $anggota = $this->buatAnggota('TOP-960006');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil keluar.');

        auth()->forgetGuards();

        $this->headerToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
